<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;
use Modules\AuditLog\Services\AuditLogService;

/**
 * Detail pegawai — termasuk data pribadi. Hanya dikirim oleh endpoint
 * detail yang sudah diotorisasi (hr.employees.view) atau profil milik
 * sendiri (self-service).
 *
 * Data sensitif (NIK, NPWP, rekening) selalu tersamar (****1234), kecuali
 * untuk pemilik data sendiri. Bagian SDM melihat nilai lengkapnya lewat
 * endpoint terpisah GET hr/employees/{id}/sensitive (hr_sensitive.read),
 * yang setiap aksesnya tercatat di audit log (RANCANGAN-AKUN-SDM R18).
 *
 * @mixin Employee
 */
class HrEmployeeDetailResource extends HrEmployeeResource
{
    /** Field yang tersamar di resource ini. */
    public const SENSITIVE_FIELDS = ['nik', 'npwp', 'bank_account_number'];

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lecturer = $this->relationLoaded('lecturer') ? $this->lecturer : null;
        $isOwner = $this->user_id !== null && $request->user()?->id === $this->user_id;
        $reveal = fn (?string $value): ?string => $isOwner ? $value : AuditLogService::maskValue($value);

        return [
            ...parent::toArray($request),
            'full_name' => $this->fullNameWithTitles(),
            'front_title' => $this->front_title,
            'back_title' => $this->back_title,
            'nik' => $reveal($this->nik),
            'npwp' => $reveal($this->npwp),
            'bank_name' => $this->bank_name,
            'bank_account_number' => $reveal($this->bank_account_number),
            'bank_account_name' => $this->bank_account_name,
            'sensitive_masked' => ! $isOwner,
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),
            'religion' => $this->religion?->value,
            'religion_label' => $this->religion?->label(),
            'marital_status' => $this->marital_status?->value,
            'marital_status_label' => $this->marital_status?->label(),
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->toDateString(),
            'address' => $this->address,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'rank_id' => $this->rank_id,
            'rank' => $this->whenLoaded('rank', fn () => $this->rank === null ? null : [
                'id' => $this->rank->id,
                'name' => $this->rank->name,
                'grade' => $this->rank->grade,
            ]),
            'work_unit_name' => $this->whenLoaded('workUnit', fn () => $this->workUnit?->name),
            'supervisor_employee_id' => $this->supervisor_employee_id,
            'supervisor' => $this->whenLoaded('supervisor', fn () => $this->supervisor === null ? null : [
                'id' => $this->supervisor->id,
                'name' => $this->supervisor->name,
                'position' => $this->supervisor->position,
            ]),
            'inactive_reason' => $this->inactive_reason,
            'inactive_at' => $this->inactive_at?->toDateString(),
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'lecturer' => $lecturer === null ? null : [
                'id' => $lecturer->id,
                'nidn' => $lecturer->nidn,
                'nidk' => $lecturer->nidk,
                'serdos_number' => $lecturer->serdos_number,
                'academic_rank' => $lecturer->academic_rank?->value,
                'academic_rank_label' => $lecturer->academic_rank?->label(),
                'expertise' => $lecturer->expertise,
                'lecturer_status' => $lecturer->lecturer_status?->value,
                'lecturer_status_label' => $lecturer->lecturer_status?->label(),
                'teaching_started_at' => $lecturer->teaching_started_at?->toDateString(),
            ],
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
