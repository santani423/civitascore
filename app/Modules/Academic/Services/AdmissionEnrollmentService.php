<?php

namespace Modules\Academic\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudentProfile;
use Modules\Academic\Models\StudentStatusHistory;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Enums\MembershipStatus;
use Modules\Tenancy\Enums\MembershipType;
use Modules\Tenancy\Models\UserUniversity;
use Modules\UserManagement\Models\Role;
use Modules\UserManagement\Models\UserRole;

/**
 * Kontrak pembentukan mahasiswa dari PMB (docs/pmb/11 §B.1, K.6). Dipanggil
 * di dalam transaksi pemanggil (bersama penomoran NIM) dan idempoten:
 * `students.admission_application_id` unik → pemanggilan ulang mengembalikan
 * mahasiswa yang sudah ada.
 */
class AdmissionEnrollmentService
{
    public function createFromAdmission(AdmissionEnrollmentData $data): Student
    {
        $existing = Student::query()->withoutGlobalScopes()->where('admission_application_id', $data->admissionApplicationId)->first();

        if ($existing !== null) {
            return $existing;
        }

        $program = StudyProgram::query()->withoutGlobalScopes()->whereKey($data->studyProgramId)->first();

        if ($program === null || ! $program->is_active) {
            throw new AdmissionEnrollmentException('PROGRAM_INACTIVE', 'Program studi tidak aktif atau tidak ditemukan.');
        }

        return DB::transaction(function () use ($data): Student {
            $user = $this->provisionUser($data);

            $student = Student::query()->create([
                'university_id' => $data->universityId,
                'study_program_id' => $data->studyProgramId,
                'user_id' => $user->getKey(),
                'nim' => $data->nim,
                'name' => $data->name,
                'email' => $data->accountEmail ?? $data->personalEmail,
                'phone' => $data->phone,
                'address' => $data->address,
                'photo_file_id' => $data->photoFileId,
                'tanggal_lahir' => $data->birthDate,
                'birth_place' => $data->birthPlace,
                'gender' => $data->gender,
                'admission_year' => $data->admissionYear,
                'status' => StudentStatus::Active,
                'enrolled_at' => $data->enrolledAt,
                'admission_application_id' => $data->admissionApplicationId,
                'admission_path_code' => $data->admissionPathCode,
                'study_system_code' => $data->studySystemCode,
            ]);

            StudentProfile::query()->create([...$data->profile, 'university_id' => $data->universityId, 'student_id' => $student->getKey()]);

            StudentStatusHistory::query()->create([
                'university_id' => $data->universityId,
                'student_id' => $student->getKey(),
                'from_status' => null,
                'to_status' => StudentStatus::Active,
                'effective_date' => $data->enrolledAt,
                'academic_term_id' => $data->academicTermId,
                'reason' => $data->reason,
            ]);

            return $student;
        });
    }

    /**
     * BR-38: email yang sudah ada di `users` (global) TIDAK ditautkan otomatis
     * — gunakan email institusi/alternatif. Akun baru: must_change_password,
     * tautan aktivasi (reset sandi) dikirim; role `student` + keanggotaan tenant.
     */
    private function provisionUser(AdmissionEnrollmentData $data): User
    {
        $email = mb_strtolower(trim((string) ($data->accountEmail ?? $data->personalEmail)));

        if ($email === '') {
            throw new AdmissionEnrollmentException('EMAIL_MISSING', 'Email akun mahasiswa tidak tersedia.');
        }

        if (User::query()->where('email', $email)->exists()) {
            throw new AdmissionEnrollmentException('USER_EMAIL_CONFLICT', 'Email sudah dipakai akun lain; tetapkan email institusi/alternatif lalu coba lagi.');
        }

        $user = User::query()->create([
            'name' => $data->name,
            'email' => $email,
            'password' => Str::password(32),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        UserUniversity::query()->create([
            'user_id' => $user->getKey(),
            'university_id' => $data->universityId,
            'membership_type' => MembershipType::Student,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
            'is_default' => true,
        ]);

        $role = Role::query()->where('slug', 'student')->whereNull('university_id')->first();

        if ($role !== null) {
            UserRole::query()->create([
                'user_id' => $user->getKey(),
                'role_id' => $role->getKey(),
                'university_id' => $data->universityId,
                'assigned_at' => now(),
            ]);
        }

        // Tautan aktivasi: alur reset sandi bawaan (ResetPassword → FRONTEND_URL/reset-password).
        DB::afterCommit(fn () => rescue(fn () => Password::broker()->sendResetLink(['email' => $email]), report: true));

        return $user;
    }
}
