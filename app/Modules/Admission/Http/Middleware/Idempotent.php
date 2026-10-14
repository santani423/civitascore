<?php

namespace Modules\Admission\Http\Middleware;

use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Models\IdempotencyKey;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header `Idempotency-Key` wajib untuk endpoint bertanda [IDEM] (11 §A.1):
 * kunci & body sama → respons pertama diulang; kunci sama, body berbeda →
 * 422 IDEMPOTENCY_KEY_REUSED; request pertama masih berjalan → 409.
 * Disimpan 24 jam di DB (tidak bergantung Redis).
 */
class Idempotent
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header('Idempotency-Key', '');

        if ($key === '' || strlen($key) > 100 || ! preg_match('/^[A-Za-z0-9_\-:.]+$/', $key)) {
            return ApiResponse::error('Header Idempotency-Key wajib diisi (maks. 100 karakter alfanumerik).', ['code' => 'IDEMPOTENCY_KEY_REQUIRED'], 400);
        }

        $actor = $request->user();
        $scope = implode('|', [
            $this->tenant->universityId() ?? '-',
            $actor ? class_basename($actor).':'.$actor->getKey() : 'guest:'.$request->ip(),
            $request->method().' '.$request->route()?->uri(),
        ]);
        $hash = hash('sha256', $request->method().'|'.$request->path().'|'.json_encode($request->except(['file']), JSON_UNESCAPED_UNICODE));

        IdempotencyKey::query()->where('expires_at', '<', now())->where('scope', $scope)->where('key', $key)->delete();

        try {
            $record = IdempotencyKey::query()->create([
                'scope' => mb_substr($scope, 0, 120),
                'key' => $key,
                'request_hash' => $hash,
                'expires_at' => now()->addDay(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $existing = IdempotencyKey::query()->where('scope', mb_substr($scope, 0, 120))->where('key', $key)->first();

            if ($existing === null) {
                return ApiResponse::error('Permintaan sedang diproses. Coba lagi.', ['code' => 'REQUEST_IN_PROGRESS'], 409);
            }

            if ($existing->request_hash !== $hash) {
                return ApiResponse::error('Idempotency-Key sudah dipakai untuk permintaan yang berbeda.', ['code' => 'IDEMPOTENCY_KEY_REUSED'], 422);
            }

            if ($existing->response_status === null) {
                return ApiResponse::error('Permintaan sedang diproses. Coba lagi.', ['code' => 'REQUEST_IN_PROGRESS'], 409);
            }

            return new JsonResponse(json_decode((string) $existing->response_body, true), $existing->response_status, ['Idempotent-Replayed' => 'true']);
        }

        $response = $next($request);

        if ($response->getStatusCode() < 500 && $response instanceof JsonResponse) {
            $record->forceFill(['response_status' => $response->getStatusCode(), 'response_body' => $response->getContent()])->save();
        } else {
            // Error server: kunci dilepas agar klien dapat mengulang dengan aman.
            $record->delete();
        }

        return $response;
    }
}
