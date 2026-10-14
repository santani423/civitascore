<?php

namespace Modules\Admission\Controllers\Admin;

use App\Models\User;
use App\Support\Http\ApiResponse;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\ChatThreadStatus;
use Modules\Admission\Models\Broadcast;
use Modules\Admission\Models\ChatThread;
use Modules\Admission\Models\OutboxEvent;
use Modules\Admission\Notifications\PmbMessageNotification;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Services\Communication\CommunicationService;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\NotificationCatalog;
use Modules\Admission\Support\PmbFileStorage;
use Modules\Admission\Support\PmbNotifier;
use Modules\Notification\Models\NotificationTemplate;

/** Template notifikasi, broadcast, quick chat, log notifikasi (FR-COM, ADM-90..95). */
class CommunicationController extends AdminController
{
    public function __construct(private readonly CommunicationService $communication) {}

    // ------------------------------------------------------------------ Template

    public function templates(): JsonResponse
    {
        $overrides = NotificationTemplate::query()->where('event_key', 'like', 'pmb.%')->get()->groupBy('event_key');

        return ApiResponse::success(collect(NotificationCatalog::EVENTS)->map(fn ($def, $key) => [
            'event_key' => $key,
            'default_channels' => $def[0],
            'priority' => $def[1],
            'default_subject' => $def[2],
            'default_body' => $def[3],
            'placeholders' => NotificationCatalog::allowedPlaceholders($key),
            'overrides' => ($overrides->get($key) ?? collect())->map(fn ($t) => ['id' => $t->getKey(), 'channel' => $t->channel->value, 'subject' => $t->subject, 'body' => $t->body_template, 'is_active' => $t->is_active])->values(),
        ])->values());
    }

    public function saveTemplate(Request $request, HistoryRecorder $history): JsonResponse
    {
        $data = $request->validate([
            'event_key' => ['required', Rule::in(array_keys(NotificationCatalog::EVENTS))],
            'channel' => ['required', Rule::in(['mail', 'database', 'sms'])],
            'subject' => ['nullable', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ]);
        $this->assertPlaceholders($data['event_key'], ($data['subject'] ?? '').' '.$data['body']);

        if ($data['channel'] === 'sms' && mb_strlen($data['body']) > 320) {
            throw ValidationException::withMessages(['body' => 'Template SMS maksimal 320 karakter.']);
        }

        $template = NotificationTemplate::query()->firstOrNew(['event_key' => $data['event_key'], 'channel' => $data['channel']]);
        $old = $template->exists ? $template->only(['subject', 'body_template', 'is_active']) : [];
        $template->forceFill([
            'university_id' => app(TenantContext::class)->universityId(),
            'name' => $data['event_key'],
            'subject' => $data['subject'] ?? null,
            'body_template' => strip_tags($data['body']),
            'is_active' => (bool) $data['is_active'],
        ])->save();
        $history->change($template, $old, $template->only(['subject', 'body_template', 'is_active']));

        return ApiResponse::success(['id' => $template->getKey()], 'Template disimpan.');
    }

    /** Kirim uji ke email staf sendiri dengan placeholder contoh. */
    public function testTemplate(Request $request, PmbNotifier $notifier): JsonResponse
    {
        $data = $request->validate(['event_key' => ['required', Rule::in(array_keys(NotificationCatalog::EVENTS))], 'channel' => ['nullable', Rule::in(['mail', 'database', 'sms'])]]);
        $samples = collect(NotificationCatalog::allowedPlaceholders($data['event_key']))->mapWithKeys(fn ($p) => [$p => '['.$p.']'])->all();
        $content = $notifier->render((string) app(TenantContext::class)->universityId(), $data['event_key'], $data['channel'] ?? 'mail', [...$samples, 'applicant_name' => $this->user($request)->name]);
        Notification::route('mail', (string) $this->user($request)->email)->notifyNow(new PmbMessageNotification($data['event_key'], '[UJI] '.$content['subject'], $content['body'], ['mail']));

        return ApiResponse::success($content, 'Email uji dikirim ke '.$this->user($request)->email.'.');
    }

    // ------------------------------------------------------------------ Broadcast

    public function broadcasts(Request $request): JsonResponse
    {
        return $this->paginate(Broadcast::query()->orderByDesc('created_at'), $request);
    }

    public function storeBroadcast(Request $request): JsonResponse
    {
        return ApiResponse::success($this->communication->saveBroadcast(null, $this->validateBroadcast($request, true), $this->user($request)), 'Broadcast disimpan sebagai draft.', status: 201);
    }

    public function updateBroadcast(Request $request, Broadcast $broadcast): JsonResponse
    {
        return ApiResponse::success($this->communication->saveBroadcast($broadcast, $this->validateBroadcast($request, false), $this->user($request)), 'Broadcast diperbarui.');
    }

    public function previewAudience(Broadcast $broadcast): JsonResponse
    {
        return ApiResponse::success(['count' => $this->communication->audienceCount($broadcast)]);
    }

    public function scheduleBroadcast(Request $request, Broadcast $broadcast): JsonResponse
    {
        $data = $request->validate(['scheduled_at' => ['nullable', 'date', 'after:now']]);

        return ApiResponse::success($this->communication->schedule($broadcast, $data['scheduled_at'] ?? null), empty($data['scheduled_at']) ? 'Broadcast sedang dikirim.' : 'Broadcast dijadwalkan.');
    }

    public function cancelBroadcast(Broadcast $broadcast): JsonResponse
    {
        return ApiResponse::success($this->communication->cancel($broadcast), 'Broadcast dibatalkan.');
    }

    // ------------------------------------------------------------------ Quick chat

    public function threads(Request $request): JsonResponse
    {
        $query = ChatThread::query()->with(['account:id,name,email', 'application:id,registration_number', 'assignee:id,name'])
            ->withCount(['messages as unread' => fn ($q) => $q->where('sender_type', 'applicant')->whereNull('read_at')])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_user_id', $this->user($request)->getKey()))
            ->when($request->boolean('unassigned'), fn ($q) => $q->whereNull('assigned_user_id'))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w->where('subject', 'like', '%'.$request->string('search').'%')
                ->orWhereHas('account', fn ($a) => $a->where('name', 'like', '%'.$request->string('search').'%')->orWhere('email', 'like', '%'.$request->string('search').'%'))))
            ->orderByDesc('last_message_at');

        return $this->paginate($query, $request, fn (ChatThread $t) => [
            'id' => $t->getKey(), 'subject' => $t->subject, 'status' => $t->status->value, 'unread' => $t->unread,
            'applicant' => $t->account?->only(['id', 'name', 'email']), 'registration_number' => $t->application?->registration_number,
            'assignee' => $t->assignee?->only(['id', 'name']), 'last_message_at' => $t->last_message_at?->toIso8601String(),
        ]);
    }

    public function thread(ChatThread $chatThread, PmbFileStorage $storage): JsonResponse
    {
        $this->communication->markRead($chatThread, 'user');
        $chatThread->load(['account:id,name,email,phone', 'application:id,registration_number', 'assignee:id,name']);
        $staff = User::query()->whereIn('id', $chatThread->messages()->where('sender_type', 'user')->pluck('sender_id'))->pluck('name', 'id');

        return ApiResponse::success([
            'thread' => [
                'id' => $chatThread->getKey(), 'subject' => $chatThread->subject, 'status' => $chatThread->status->value,
                'applicant' => $chatThread->account?->only(['id', 'name', 'email', 'phone']), 'application' => $chatThread->application?->only(['id', 'registration_number']),
                'assignee' => $chatThread->assignee?->only(['id', 'name']),
            ],
            'messages' => $chatThread->messages()->with('attachment')->orderBy('created_at')->get()->map(fn ($m) => [
                'id' => $m->getKey(), 'sender_type' => $m->sender_type, 'sender' => $m->sender_type === 'user' ? ($staff[$m->sender_id] ?? 'Panitia') : $chatThread->account?->name,
                'body' => $m->body, 'read_at' => $m->read_at?->toIso8601String(), 'created_at' => $m->created_at?->toIso8601String(),
                'attachment_url' => $m->attachment && $m->attachment->status->value === 'clean' ? $storage->temporaryUrl($m->attachment, 30) : null,
            ]),
            'statuses' => ChatThreadStatus::values(),
            'security_notice' => CommunicationService::SECURITY_NOTICE,
        ]);
    }

    public function reply(Request $request, ChatThread $chatThread): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000'], 'attachment' => ['nullable', 'file', 'max:2048']]);
        $message = $this->communication->reply($chatThread, $this->user($request), $data['body'], $request->file('attachment'));

        return ApiResponse::success($message->only(['id', 'body', 'created_at']), 'Balasan terkirim.', status: 201);
    }

    public function updateThread(Request $request, ChatThread $chatThread): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(ChatThreadStatus::values())],
            'assigned_user_id' => ['sometimes', 'nullable', 'string', Rule::exists('users', 'id')],
        ]);

        return ApiResponse::success($this->communication->updateThread($chatThread, $data)->only(['id', 'status', 'assigned_user_id']), 'Percakapan diperbarui.');
    }

    // ------------------------------------------------------------------ Log notifikasi

    /** Status pengiriman dari outbox; isi pesan & placeholder tidak ditampilkan (bisa memuat tautan/OTP). */
    public function logs(Request $request): JsonResponse
    {
        $query = OutboxEvent::query()->whereIn('event_type', [OutboxRecorder::NOTIFY_APPLICANT, OutboxRecorder::NOTIFY_PERMISSION, OutboxRecorder::BROADCAST])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at');

        return $this->paginate($query, $request, fn (OutboxEvent $e) => [
            'id' => $e->getKey(), 'event_type' => $e->event_type,
            'event_key' => $e->payload['event_key'] ?? null,
            'aggregate_type' => $e->aggregate_type, 'aggregate_id' => $e->aggregate_id,
            'status' => $e->status->value, 'attempts' => $e->attempts,
            'pending_channels' => $e->payload['channels'] ?? null,
            'last_error' => $e->last_error ? mb_substr($e->last_error, 0, 300) : null,
            'available_at' => $e->available_at?->toIso8601String(), 'dispatched_at' => $e->dispatched_at?->toIso8601String(), 'created_at' => $e->created_at?->toIso8601String(),
        ], 50);
    }

    /** @return array<string, mixed> */
    private function validateBroadcast(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'title' => [$req, 'string', 'max:200'],
            'channel' => [$req, Rule::in(['email', 'sms'])],
            'audience_filter' => ['sometimes', 'array'],
            'audience_filter.period_id' => ['nullable', 'string', 'size:26'],
            'audience_filter.wave_id' => ['nullable', 'string', 'size:26'],
            'audience_filter.admission_path_id' => ['nullable', 'string', 'size:26'],
            'audience_filter.form_status' => ['nullable', 'array'],
            'audience_filter.verification_status' => ['nullable', 'array'],
            'audience_filter.registration_payment_status' => ['nullable', 'array'],
            'audience_filter.decision' => ['nullable', 'array'],
            'audience_filter.re_registration_status' => ['nullable', 'array'],
            'audience_filter.marketing' => ['nullable', 'boolean'],
            'content_subject' => ['nullable', 'string', 'max:200'],
            'content_body' => [$req, 'string', 'max:5000'],
        ]);
    }

    private function assertPlaceholders(string $eventKey, string $text): void
    {
        preg_match_all('/\{\{\s*([a-z_]+)\s*\}\}/', $text, $m);
        $unknown = array_diff(array_unique($m[1]), NotificationCatalog::allowedPlaceholders($eventKey));

        if ($unknown !== []) {
            throw ValidationException::withMessages(['body' => 'Placeholder tidak dikenal: '.implode(', ', $unknown)]);
        }
    }
}
