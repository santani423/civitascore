<?php

namespace Modules\Admission\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Services\Exam\CbtService;
use Modules\Admission\Services\Payment\WebhookService;

/** Webhook penyedia (11 §A.18.3): tanpa detail internal di respons. */
class WebhookController extends Controller
{
    public function payment(string $provider, Request $request, WebhookService $webhooks): JsonResponse
    {
        $result = $webhooks->receive($provider, $request);

        return response()->json(['success' => $result['status'] === 200, 'message' => $result['message']], $result['status']);
    }

    public function cbt(string $provider, Request $request, CbtService $cbt): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'accepted', 'data' => $cbt->receiveExternal($request)]);
    }
}
