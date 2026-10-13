<?php

namespace Modules\Admission\Controllers\Applicant;

use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admission\Models\ChatThread;
use Modules\Admission\Services\Communication\CommunicationService;
use Modules\Admission\Support\PmbFileStorage;

/** Quick chat pendaftar ↔ panitia (FR-COM-01, POR-10). */
class ChatController extends ApplicantController
{
    public function __construct(private readonly CommunicationService $communication) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(ChatThread::query()->where('applicant_account_id', $this->account($request)->getKey())->withCount(['messages as unread' => fn ($q) => $q->where('sender_type', 'user')->whereNull('read_at')])
            ->latest('last_message_at')->get()->map->only(['id', 'subject', 'status', 'application_id', 'last_message_at', 'unread']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['subject' => ['required', 'string', 'max:200'], 'message' => ['required', 'string', 'max:5000'], 'application_id' => ['nullable', 'string', 'size:26'], 'attachment' => ['nullable', 'file', 'max:2048']]);
        $thread = $this->communication->openThread($this->account($request), $data['subject'], $data['message'], $data['application_id'] ?? null, $request->file('attachment'));

        return ApiResponse::success($thread->only(['id', 'subject', 'status']), 'Pesan terkirim.', status: 201);
    }

    public function messages(Request $request, string $threadId, PmbFileStorage $storage): JsonResponse
    {
        $thread = $this->thread($request, $threadId);
        $this->communication->markRead($thread, 'applicant');

        return ApiResponse::success([
            'thread' => $thread->only(['id', 'subject', 'status']),
            'messages' => $thread->messages()->with('attachment')->get()->map(fn ($m) => [
                'id' => $m->getKey(), 'sender_type' => $m->sender_type, 'body' => $m->body, 'read_at' => $m->read_at?->toIso8601String(), 'created_at' => $m->created_at?->toIso8601String(),
                'attachment_url' => $m->attachment && $m->attachment->status->value === 'clean' ? $storage->temporaryUrl($m->attachment) : null,
            ]),
        ]);
    }

    public function reply(Request $request, string $threadId): JsonResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000'], 'attachment' => ['nullable', 'file', 'max:2048']]);
        $message = $this->communication->postMessage($this->thread($request, $threadId), 'applicant', (string) $this->account($request)->getKey(), $data['body'], $request->file('attachment'));

        return ApiResponse::success($message->only(['id', 'body', 'created_at']), 'Pesan terkirim.', status: 201);
    }

    private function thread(Request $request, string $threadId): ChatThread
    {
        return ChatThread::query()->where('applicant_account_id', $this->account($request)->getKey())->whereKey($threadId)->firstOrFail();
    }
}
