<?php

namespace Modules\Admission\Database\Seeders;

use App\Support\Tenancy\TenantContext;
use Modules\Admission\Models\DocumentType;
use Modules\Admission\Models\StudySystem;
use Modules\Admission\Services\Approval\PmbApprovalService;
use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\Tenancy\Models\University;

/**
 * Data awal modul PMB per tenant: katalog jenis dokumen default (docs/pmb/04
 * §E.2), sistem kuliah dasar, dan alur persetujuan default (pengesahan hasil,
 * koreksi, refund, perpanjangan massal, percepatan pengumuman).
 *
 * university_id selalu diisi eksplisit: DatabaseSeeder memakai
 * WithoutModelEvents sehingga pengisian otomatis TenantScoped (event
 * `creating`) tidak berjalan selama seeding (lihat UniversitySeeder).
 *
 * Idempoten dan tidak menimpa data tenant: hanya membuat yang belum ada
 * (firstOrCreate berdasarkan kode). Periode, jalur, kuota, biaya, dan dokumen
 * legal sengaja TIDAK dibuat — semuanya keputusan kebijakan tenant.
 */
class AdmissionSeeder
{
    private const PDF = ['application/pdf'];

    private const IMG = ['image/jpeg', 'image/png'];

    /** code => [nama, mime, maks KB, multi, cek asli, sensitif] */
    private const DOCUMENT_TYPES = [
        'PHOTO' => ['Pas foto latar polos', self::IMG, 1024, false, false, false],
        'ID_CARD' => ['KTP / Kartu Pelajar / Paspor', [...self::IMG, ...self::PDF], 2048, false, true, true],
        'FAMILY_CARD' => ['Kartu Keluarga', [...self::IMG, ...self::PDF], 2048, false, true, true],
        'REPORT_CARD' => ['Rapor semester 1–5 (gabung 1 PDF)', self::PDF, 5120, false, true, false],
        'DIPLOMA_OR_SKL' => ['Ijazah / Surat Keterangan Lulus', [...self::PDF, 'image/jpeg'], 2048, false, true, false],
        'TRANSCRIPT' => ['Transkrip nilai PT asal', self::PDF, 5120, false, true, false],
        'TRANSFER_LETTER' => ['Surat keterangan pindah / bebas tanggungan', self::PDF, 2048, false, true, false],
        'ACHIEVEMENT_CERT' => ['Sertifikat prestasi', [...self::PDF, 'image/jpeg'], 2048, true, true, false],
        'KIP_CARD' => ['Kartu KIP / bukti terdaftar KIP Kuliah', [...self::PDF, 'image/jpeg'], 2048, false, false, true],
        'INCOME_STATEMENT' => ['Surat keterangan penghasilan / SKTM', self::PDF, 2048, false, false, true],
        'RECOMMENDATION' => ['Surat rekomendasi', self::PDF, 2048, false, false, false],
        'WORK_EXPERIENCE' => ['Surat keterangan kerja / portofolio', self::PDF, 10240, true, false, false],
        'PARTNERSHIP_LETTER' => ['Surat penugasan instansi mitra', self::PDF, 2048, false, false, false],
        'HEALTH_CERT' => ['Surat keterangan sehat', self::PDF, 2048, false, false, true],
        'STATEMENT_LETTER' => ['Surat pernyataan bermeterai', self::PDF, 2048, false, true, false],
    ];

    private const STUDY_SYSTEMS = [
        'REG' => ['Reguler', 'Kuliah pagi–sore, Senin–Jumat'],
        'KRY' => ['Kelas Karyawan', 'Kuliah malam & akhir pekan'],
    ];

    /** @return array{document_types: int, study_systems: int, workflows: int} */
    public function run(University $university): array
    {
        $tenant = app(TenantContext::class);
        $previous = $tenant->universityId();
        $tenant->setUniversityId($university->getKey());
        $created = ['document_types' => 0, 'study_systems' => 0, 'workflows' => 0];

        try {
            foreach (self::DOCUMENT_TYPES as $code => [$name, $mimes, $maxKb, $multiple, $original, $sensitive]) {
                $type = DocumentType::query()->firstOrCreate(['code' => $code], [
                    'university_id' => $university->getKey(),
                    'name' => $name, 'allowed_mime_types' => $mimes, 'max_size_kb' => $maxKb, 'allows_multiple' => $multiple,
                    'requires_original_check' => $original, 'is_sensitive' => $sensitive, 'retention_class' => $sensitive ? 'sensitive' : 'standard',
                ]);
                $created['document_types'] += (int) $type->wasRecentlyCreated;
            }

            foreach (self::STUDY_SYSTEMS as $code => [$name, $note]) {
                $system = StudySystem::query()->firstOrCreate(['code' => $code], ['university_id' => $university->getKey(), 'name' => $name, 'schedule_note' => $note, 'is_active' => true]);
                $created['study_systems'] += (int) $system->wasRecentlyCreated;
            }

            $approvals = app(PmbApprovalService::class);

            foreach (PmbApprovalService::types() as $type) {
                $existing = ApprovalWorkflow::query()->where('workflowable_type', $type)->exists();
                $approvals->createDefault($type);
                $created['workflows'] += (int) ! $existing;
            }
        } finally {
            $tenant->setUniversityId($previous);
        }

        return $created;
    }
}
