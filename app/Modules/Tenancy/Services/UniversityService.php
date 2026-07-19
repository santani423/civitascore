<?php

namespace Modules\Tenancy\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Tenancy\Enums\UniversityStatus;
use Modules\Tenancy\Models\University;

class UniversityService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $createdBy = null): University
    {
        $data['slug'] ??= Str::slug($data['name']);
        $data['created_by'] = $createdBy?->id;
        $data['status'] ??= UniversityStatus::Trial;

        return DB::transaction(fn (): University => University::create($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(University $university, array $data): University
    {
        DB::transaction(fn () => $university->update($data));

        return $university;
    }

    public function activate(University $university): University
    {
        DB::transaction(fn () => $university->update([
            'status' => UniversityStatus::Active,
            'is_active' => true,
            'activated_at' => now(),
            'suspended_at' => null,
        ]));

        return $university;
    }

    public function suspend(University $university): University
    {
        DB::transaction(fn () => $university->update([
            'status' => UniversityStatus::Suspended,
            'is_active' => false,
            'suspended_at' => now(),
        ]));

        return $university;
    }
}
