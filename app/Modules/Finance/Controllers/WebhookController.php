<?php

namespace Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Finance\Services\OnlinePaymentService;

/**
 * Callback penyedia pembayaran (publik). Hanya memvalidasi tanda tangan,
 * menyimpan, dan memproses dengan cek ulang status (BR-KEU-41/44).
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, OnlinePaymentService $online): JsonResponse
    {
        $result = $online->handleWebhook($provider, $request);

        return response()->json(['message' => $result['message']], $result['status']);
    }
}
