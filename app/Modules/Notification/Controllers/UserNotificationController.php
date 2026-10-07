<?php

namespace Modules\Notification\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Pusat notifikasi pengguna yang login — notifikasi in-app (kanal database)
 * yang dikirim lewat NotificationDispatcher/TemplatedNotification oleh modul
 * mana pun. Selalu hanya milik user sendiri ($user->notifications()), tanpa
 * permission khusus, sama seperti /sessions dan /devices.
 */
class UserNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unread' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();

        $paginator = $user->notifications()
            ->when($request->boolean('unread'), fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate((int) ($validated['per_page'] ?? 15));

        return response()->json([
            'success' => true,
            'message' => 'Berhasil.',
            'data' => collect($paginator->items())->map(fn (DatabaseNotification $notification) => $this->format($notification))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return ApiResponse::success(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        /** @var DatabaseNotification|null $record */
        $record = $request->user()->notifications()->whereKey($notification)->first();

        abort_if($record === null, 404, 'Notifikasi tidak ditemukan.');

        $record->markAsRead();

        return ApiResponse::success([
            ...$this->format($record->refresh()),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ], 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return ApiResponse::success(['unread_count' => 0], 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * @return array<string, mixed>
     */
    private function format(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'event_key' => $data['event_key'] ?? null,
            'message' => $data['message'] ?? null,
            'link' => $data['link'] ?? null,
            'read_at' => $this->iso($notification->getAttribute('read_at')),
            'created_at' => $this->iso($notification->getAttribute('created_at')),
        ];
    }

    private function iso(mixed $value): ?string
    {
        return $value instanceof \DateTimeInterface ? CarbonImmutable::instance($value)->toIso8601String() : null;
    }
}
