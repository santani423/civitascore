<?php

namespace Modules\Academic\Services;

/**
 * Data mahasiswa baru dari PMB (docs/pmb/11 §B.1). Modul Academic tidak
 * bergantung pada model PMB — hanya menerima DTO ini.
 */
final class AdmissionEnrollmentData
{
    /**
     * @param  array<string, mixed>  $profile  data identitas/PDDikti (student_profiles)
     */
    public function __construct(
        public readonly string $universityId,
        public readonly string $admissionApplicationId,
        public readonly string $studyProgramId,
        public readonly string $nim,
        public readonly string $name,
        public readonly ?string $personalEmail,
        public readonly ?string $accountEmail,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?string $birthDate,
        public readonly ?string $birthPlace,
        public readonly ?string $gender,
        public readonly ?string $photoFileId,
        public readonly int $admissionYear,
        public readonly string $enrolledAt,
        public readonly ?string $admissionPathCode,
        public readonly ?string $studySystemCode,
        public readonly ?string $academicTermId,
        public readonly array $profile,
        public readonly string $reason,
    ) {}
}
