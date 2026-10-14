<?php

namespace Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Admission\Models\ApplicantAccount;
use Modules\Admission\Services\Account\AccountService;

/**
 * Penerimaan lead CRM/Maukuliah (FR-INT-03, 11 §B.7): token klien dengan
 * ability `pmb:integration:leads`. Lead disimpan sebagai referensi pada akun
 * yang mendaftar dengan email sama; tidak dikirimi pemasaran tanpa consent.
 * Format API CRM SEVIMA tidak tersedia di sumber (INK-01) — kontrak generik.
 */
class IntegrationController extends Controller
{
    public function lead(Request $request, AccountService $accounts): JsonResponse
    {
        abort_unless($request->user()?->tokenCan((string) config('pmb.integrations.lead_ability')), 403, 'Token tidak memiliki izin integrasi lead.');

        $data = $request->validate([
            'reference' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:20'],
            'program_interest' => ['nullable', 'string', 'max:150'],
            'source' => ['nullable', 'string', 'max:30'],
        ]);

        $account = ApplicantAccount::query()->where('email', $accounts->normalizeEmail($data['email']))->first();

        if ($account !== null) {
            $account->forceFill(['crm_lead_reference' => $data['reference'], 'info_source' => $account->info_source ?? ($data['source'] ?? 'maukuliah')])->save();
        }

        // Hanya referensi (bukan data pribadi) disimpan; dicocokkan saat akun dibuat (AccountService::register).
        Cache::put('pmb:lead:'.sha1($accounts->normalizeEmail($data['email'])), ['reference' => $data['reference'], 'source' => $data['source'] ?? null], now()->addDays(180));

        return ApiResponse::success(['matched_account' => $account !== null], 'Lead diterima.', status: 202);
    }
}
