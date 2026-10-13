<?php

namespace Modules\Admission\Support;

use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\FileManagement\Contracts\VirusScanner;
use Modules\FileManagement\Enums\FileUploadStatus;
use Modules\FileManagement\Models\FileUpload;

/**
 * Penyimpanan berkas PMB yang aman (04 FR-DOC-02/03, 13 §B.4):
 * - MIME dari ISI berkas (finfo, magic bytes), bukan dari klien; ekstensi
 *   harus konsisten dengan isi (menolak poliglot),
 * - gambar di-encode ulang (menghapus EXIF/payload tersembunyi), dimensi foto,
 * - PDF tidak terenkripsi & ≤ 20 halaman,
 * - path ber-tenant `universities/{u}/pmb/...` dengan nama ULID,
 * - `uploaded_by` null (pendaftar bukan `users`, GAP-05), dipindai VirusScanner,
 * - unduhan hanya lewat URL bertanda tangan berumur pendek untuk berkas `clean`.
 */
class PmbFileStorage
{
    private const EXTENSIONS = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'text/csv' => ['csv', 'txt'],
        'text/plain' => ['csv', 'txt'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/zip' => ['xlsx'],
    ];

    public function __construct(private readonly TenantContext $tenant, private readonly VirusScanner $scanner) {}

    /**
     * @param  array<int, string>  $allowedMimes
     * @param  array<int, string>  $pathParts
     * @param  array{min_width?: int, min_height?: int, aspect?: float}  $imageRules
     */
    public function store(UploadedFile $file, array $allowedMimes, int $maxKb, array $pathParts, array $imageRules = [], ?string $uploadedByUserId = null): FileUpload
    {
        if (! $file->isValid()) {
            throw new DomainRuleException('Berkas gagal diunggah.', 'INVALID_FILE');
        }

        if ($file->getSize() > $maxKb * 1024) {
            throw new DomainRuleException("Ukuran berkas melebihi batas {$maxKb} KB.", 'FILE_TOO_LARGE', 422);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) ?: 'application/octet-stream';
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($mime, $allowedMimes, true) || ! in_array($extension, self::EXTENSIONS[$mime] ?? [], true)) {
            throw new DomainRuleException('Tipe berkas tidak diizinkan atau isi berkas tidak sesuai ekstensinya.', 'INVALID_FILE_TYPE', 422);
        }

        $contents = (string) file_get_contents($file->getRealPath());

        if ($mime === 'application/pdf') {
            $this->assertSafePdf($contents);
        }

        if (str_starts_with($mime, 'image/')) {
            $contents = $this->reencodeImage($contents, $mime, $imageRules);
        }

        return $this->persist($contents, $mime, $extension, $file->getClientOriginalName(), $pathParts, $uploadedByUserId);
    }

    /**
     * Menyimpan konten yang dibangkitkan sistem (PDF LoA, kartu, kuitansi, ekspor).
     *
     * @param  array<int, string>  $pathParts
     */
    public function storeGenerated(string $contents, string $mime, string $extension, string $originalName, array $pathParts): FileUpload
    {
        return $this->persist($contents, $mime, $extension, $originalName, $pathParts, null, scan: false);
    }

    public function temporaryUrl(FileUpload $file, ?int $minutes = null): string
    {
        if ($file->status !== FileUploadStatus::Clean) {
            throw new DomainRuleException('Berkas belum selesai dipindai atau ditolak pemindaian.', 'FILE_NOT_CLEAN', 409);
        }

        return URL::temporarySignedRoute(
            'pmb.files.download',
            now()->addMinutes($minutes ?? (int) config('pmb.download_url_ttl_minutes', 5)),
            ['file' => $file->getKey()],
        );
    }

    /**
     * URL bertanda tangan tanpa kedaluwarsa untuk media publik (gambar konten
     * website PMB) — tidak dapat ditebak, tetap hanya untuk berkas `clean`.
     */
    public function publicUrl(FileUpload $file): string
    {
        if ($file->status !== FileUploadStatus::Clean) {
            throw new DomainRuleException('Berkas belum selesai dipindai atau ditolak pemindaian.', 'FILE_NOT_CLEAN', 409);
        }

        return URL::signedRoute('pmb.files.download', ['file' => $file->getKey()]);
    }

    public function contents(FileUpload $file): string
    {
        return (string) Storage::disk($file->disk)->get($file->path);
    }

    /**
     * @param  array<int, string>  $pathParts
     */
    private function persist(string $contents, string $mime, string $extension, string $originalName, array $pathParts, ?string $uploadedBy, bool $scan = true): FileUpload
    {
        $universityId = $this->tenant->universityId();
        $disk = (string) config('pmb.storage_disk', 'local');
        $safeParts = array_map(fn (string $part): string => preg_replace('/[^A-Za-z0-9_\-]/', '_', $part) ?? '_', $pathParts);
        $path = 'universities/'.$universityId.'/pmb/'.implode('/', $safeParts).'/'.Str::ulid().'.'.$extension;

        Storage::disk($disk)->put($path, $contents, ['visibility' => 'private']);

        $upload = DB::transaction(fn (): FileUpload => FileUpload::query()->create([
            'university_id' => $universityId,
            'uploaded_by' => $uploadedBy,
            'disk' => $disk,
            'path' => $path,
            'original_name' => mb_substr(basename(str_replace('\\', '/', $originalName)), 0, 200),
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => strlen($contents),
            'checksum' => hash('sha256', $contents),
            'status' => $scan ? FileUploadStatus::Scanning : FileUploadStatus::Clean,
            'is_public' => false,
        ]));

        if ($scan) {
            $clean = $this->scanner->scan($upload);
            $upload->update(['status' => $clean ? FileUploadStatus::Clean : FileUploadStatus::Rejected]);
        }

        return $upload->refresh();
    }

    private function assertSafePdf(string $contents): void
    {
        if (! str_starts_with(ltrim($contents), '%PDF-')) {
            throw new DomainRuleException('Berkas PDF tidak valid.', 'INVALID_FILE_TYPE');
        }

        if (preg_match('/\/Encrypt\s/', $contents)) {
            throw new DomainRuleException('PDF terenkripsi/berkata sandi tidak dapat diterima.', 'PDF_ENCRYPTED');
        }

        $pages = preg_match_all('/\/Type\s*\/Page(?!s)/', $contents);

        if ($pages > 20) {
            throw new DomainRuleException('PDF maksimal 20 halaman.', 'PDF_TOO_MANY_PAGES');
        }
    }

    /**
     * @param  array{min_width?: int, min_height?: int, aspect?: float}  $rules
     */
    private function reencodeImage(string $contents, string $mime, array $rules): string
    {
        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new DomainRuleException('Gambar tidak dapat dibaca.', 'INVALID_FILE_TYPE');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ((isset($rules['min_width']) && $width < $rules['min_width']) || (isset($rules['min_height']) && $height < $rules['min_height'])) {
            imagedestroy($image);

            throw new DomainRuleException("Resolusi gambar minimal {$rules['min_width']}×{$rules['min_height']} piksel.", 'IMAGE_TOO_SMALL');
        }

        if (isset($rules['aspect']) && $height > 0 && abs(($width / $height) - $rules['aspect']) / $rules['aspect'] > 0.10) {
            imagedestroy($image);

            throw new DomainRuleException('Rasio foto harus 3:4 (toleransi 10%).', 'IMAGE_RATIO');
        }

        ob_start();
        $mime === 'image/png' ? imagepng($image) : imagejpeg($image, null, 90);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
