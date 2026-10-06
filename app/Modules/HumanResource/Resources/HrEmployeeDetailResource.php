<?php

namespace Modules\HumanResource\Resources;

use Illuminate\Http\Request;
use Modules\Academic\Models\Employee;

/**
 * Detail pegawai — termasuk data pribadi (NIK, tanggal lahir, alamat).
 * Hanya dikirim oleh endpoint detail yang sudah diotorisasi
 * (hr.employees.view) atau profil milik sendiri (self-service).
 *
 * @mixin Employee
 */
class HrEmployeeDetailResource extends HrEmployeeResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lecturer = $this->relationLoaded('lecturer') ? $this->lecturer : null;

        return [
            ...parent::toArray($request),
            'nik' => $this->nik,
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->toDateString(),
            'address' => $this->address,
            'rank_id' => $this->rank_id,
            'rank' => $this->whenLoaded('rank', fn () => $this->rank === null ? null : [
                'id' => $this->rank->id,
                'name' => $this->rank->name,
                'grade' => $this->rank->grade,
            ]),
            'work_unit_name' => $this->whenLoaded('workUnit', fn () => $this->workUnit?->name),
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
