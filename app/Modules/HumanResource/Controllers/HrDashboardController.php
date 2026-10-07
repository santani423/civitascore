<?php

namespace Modules\HumanResource\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Modules\HumanResource\Services\HrAlertService;
use Modules\HumanResource\Services\HrDashboardService;

/**
 * Dashboard SDM & Notifikasi SDM.
 */
class HrDashboardController extends Controller
{
    public function index(HrDashboardService $dashboard): JsonResponse
    {
        return ApiResponse::success($dashboard->build());
    }

    /**
     * Notifikasi SDM = alert yang dihitung langsung dari data (kontrak,
     * dokumen, pengajuan) + notifikasi in-app milik user yang login dengan
     * event_key hr.* (pengingat kontrak, pengajuan baru, keputusan).
     */
    public function notifications(Request $request, HrAlertService $alerts): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $notifications = $user->notifications()
            ->where('data->event_key', 'like', 'hr.%')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'event_key' => $notification->data['event_key'] ?? null,
                'message' => $notification->data['message'] ?? null,
                'read_at' => $this->isoDate($notification->getAttribute('read_at')),
                'created_at' => $this->isoDate($notification->getAttribute('created_at')),
            ]);

        return ApiResponse::success([
            'alerts' => $alerts->all(20),
            'notifications' => $notifications,
            'unread_count' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $data = $request->validate(['ids' => ['nullable', 'array'], 'ids.*' => ['string']]);

        $user->unreadNotifications()
            ->where('data->event_key', 'like', 'hr.%')
            ->when(! empty($data['ids']), fn ($query) => $query->whereIn('id', $data['ids']))
            ->update(['read_at' => now()]);

        return ApiResponse::success(null, 'Notifikasi ditandai sudah dibaca.');
    }

    private function isoDate(mixed $value): ?string
    {
        return $value instanceof \DateTimeInterface ? CarbonImmutable::instance($value)->toIso8601String() : null;
    }
}
