<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Services\Payment\WebhookService;
use Modules\Finance\Payments\XenditGateway as FinanceXenditGateway;
use Modules\Finance\Services\OnlinePaymentService;

/**
 * Callback invoice Xendit untuk PMB & KEU. Satu akun Xendit hanya memiliki
 * satu URL callback invoice di dashboard, sedangkan kedua modul membuat
 * invoice; endpoint ini meneruskan callback ke modul pemiliknya berdasarkan
 * prefiks `external_id` (KEU- → KEU, selain itu → PMB). Verifikasi token,
 * dedupe, dan cek ulang status tetap dilakukan oleh modul masing-masing.
 */
class XenditWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (str_starts_with((string) $request->json('external_id', ''), FinanceXenditGateway::EXTERNAL_ID_PREFIX)) {
            $result = app(OnlinePaymentService::class)->handleWebhook('xendit', $request);

            return response()->json(['message' => $result['message']], $result['status']);
        }

        $result = app(WebhookService::class)->receive('xendit', $request);

        return response()->json(['success' => $result['status'] === 200, 'message' => $result['message']], $result['status']);
    }
}
