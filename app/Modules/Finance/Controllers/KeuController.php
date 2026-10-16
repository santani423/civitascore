<?php

namespace Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Http\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Basis controller KEU: daftar berpaginasi dengan presenter (pola PMB). */
abstract class KeuController extends Controller
{
    protected function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    /** @param  callable(mixed): mixed|null  $map */
    protected function paginate(Builder $query, Request $request, ?callable $map = null, int $default = 25): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->integer('per_page', $default)));
        $page = $query->paginate($perPage);
        $items = $map ? $page->getCollection()->map($map)->values() : $page->getCollection()->values();

        return ApiResponse::success($items, meta: [
            'current_page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ]);
    }

    /** @return array<string, mixed> filter laporan/dashboard yang diizinkan */
    protected function filters(Request $request): array
    {
        return array_filter($request->only([
            'academic_term_id', 'study_program_id', 'faculty_id', 'admission_year', 'student_status', 'date_from', 'date_to',
            'bill_group', 'status', 'method', 'search', 'transaction_status',
        ]), fn ($v) => $v !== null && $v !== '');
    }
}
