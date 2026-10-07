<?php

namespace Modules\Academic\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Enums\StudentRequestStatus;
use Modules\Academic\Enums\StudentRequestType;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudentRequest;
use Modules\FileManagement\Support\FileAttacher;

/**
 * Profil mahasiswa milik sendiri. Field dibagi tiga:
 *
 * - EDITABLE_FIELDS: kontak & alamat, diubah langsung oleh mahasiswa.
 * - Foto: berkas FileManagement (jpg/png ≤ 2 MB) yang ditautkan ke Student,
 *   aksesnya dijaga Student::allowsFileAccess().
 * - Data induk (StudentRequestService::DATA_CHANGE_FIELDS): hanya lewat
 *   pengajuan Perubahan Data yang disetujui Bagian Akademik.
 * - Sisanya (NIM, prodi, angkatan, status, dosen wali) read-only.
 */
class StudentProfileService
{
    public const EDITABLE_FIELDS = ['phone', 'address'];

    public const PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public const PHOTO_MAX_MB = 2;

    public function __construct(
        private readonly StudentAcademicService $academics,
        private readonly FileAttacher $files,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function show(Student $student): array
    {
        $student->loadMissing(['photo']);

        $pendingChange = StudentRequest::query()
            ->where('student_id', $student->id)
            ->where('type', StudentRequestType::DataChange)
            ->where('status', StudentRequestStatus::Submitted)
            ->latest('submitted_at')
            ->first();

        return [
            'id' => $student->id,
            'personal' => [
                'name' => $student->name,
                'nim' => $student->nim,
                'birth_place' => $student->birth_place,
                'birth_date' => $student->tanggal_lahir?->toDateString(),
                'gender' => $student->gender,
                'gender_label' => match ($student->gender) {
                    'male' => 'Laki-laki',
                    'female' => 'Perempuan',
                    default => null,
                },
            ],
            'contact' => [
                'email' => $student->email,
                'phone' => $student->phone,
            ],
            'address' => $student->address,
            'photo' => $student->photo
                ? ['file_id' => $student->photo->id, 'mime_type' => $student->photo->mime_type]
                : null,
            'academic' => $this->academics->summary($student),
            'editable_fields' => [...self::EDITABLE_FIELDS, 'photo'],
            'request_only_fields' => StudentRequestService::DATA_CHANGE_FIELDS,
            'pending_data_change' => $pendingChange ? [
                'id' => $pendingChange->id,
                'submitted_at' => $pendingChange->submitted_at?->toIso8601String(),
                'changes' => (array) data_get($pendingChange->payload, 'changes', []),
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data  Divalidasi UpdateStudentProfileRequest — phone, address, photo_file_id.
     */
    public function update(Student $student, array $data, User $actor): Student
    {
        $photo = array_key_exists('photo_file_id', $data) && $data['photo_file_id'] !== null
            ? $this->files->resolve($data['photo_file_id'], 'photo_file_id', $actor, $student, self::PHOTO_EXTENSIONS, self::PHOTO_MAX_MB)
            : null;

        DB::transaction(function () use ($student, $data, $photo): void {
            $changes = Arr::only($data, self::EDITABLE_FIELDS);

            if (array_key_exists('photo_file_id', $data)) {
                $changes['photo_file_id'] = $photo?->id;
            }

            $student->update($changes);
            $this->files->attach($photo, $student);
        });

        return $student->refresh();
    }
}
