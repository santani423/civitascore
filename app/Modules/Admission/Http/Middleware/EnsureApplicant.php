<?php

namespace Modules\Admission\Http\Middleware;

use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Admission\Enums\AccountStatus;
use Modules\Admission\Models\ApplicantAccount;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang portal pendaftar (13 §B.1): hanya token milik ApplicantAccount
 * ber-ability `applicant`; tenant DIIKAT dari akun (bukan dari header klien)
 * dan ditolak bila header/domain menunjuk tenant lain; token kedaluwarsa
 * sliding 12 jam diperpanjang setiap pemakaian (FR-ACC-03).
 *
 * Parameter `verified` mewajibkan akun terverifikasi (BR-08).
 */
class EnsureApplicant
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next, ?string $requirement = null): Response
    {
        $account = $request->user();

        if (! $account instanceof ApplicantAccount) {
            return ApiResponse::error('Tidak terautentikasi.', status: 401);
        }

        $token = $account->currentAccessToken();

        if (! $token instanceof PersonalAccessToken || ! $token->can(ApplicantAccount::TOKEN_ABILITY)) {
            return ApiResponse::error('Tidak terautentikasi.', status: 401);
        }

        $resolved = $this->tenant->universityId();

        if ($resolved !== null && $resolved !== (string) $account->university_id) {
            return ApiResponse::error('Tidak terautentikasi.', status: 401);
        }

        $this->tenant->setUniversityId((string) $account->university_id);

        if (in_array($account->status, [AccountStatus::Suspended, AccountStatus::Deactivated], true)) {
            $token->delete();

            return ApiResponse::error('Akun tidak aktif. Hubungi panitia PMB.', status: 403);
        }

        if ($requirement === 'verified' && ! $account->isVerified()) {
            return ApiResponse::error('Verifikasi akun Anda terlebih dahulu.', ['code' => 'ACCOUNT_NOT_VERIFIED'], 403);
        }

        // Sliding expiration (hemat tulis: hanya bila sisa < 11 jam).
        $ttl = (int) config('pmb.applicant_token_ttl_hours', 12);

        if ($token->expires_at === null || $token->expires_at->lt(now()->addHours($ttl - 1))) {
            $token->forceFill(['expires_at' => now()->addHours($ttl)])->save();
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
