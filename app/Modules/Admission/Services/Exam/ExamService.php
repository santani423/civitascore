<?php

namespace Modules\Admission\Services\Exam;

use App\Models\User;
use App\Support\Http\Exceptions\ConflictException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Admission\Enums\AttendanceStatus;
use Modules\Admission\Enums\ExamMode;
use Modules\Admission\Enums\ExamScheduleStatus;
use Modules\Admission\Enums\Lifecycle;
use Modules\Admission\Enums\RescheduleRequestStatus;
use Modules\Admission\Enums\SelectionStatus;
use Modules\Admission\Exceptions\DomainRuleException;
use Modules\Admission\Models\Application;
use Modules\Admission\Models\ConflictDeclaration;
use Modules\Admission\Models\ExamParticipant;
use Modules\Admission\Models\ExamRescheduleRequest;
use Modules\Admission\Models\ExamRoom;
use Modules\Admission\Models\ExamSchedule;
use Modules\Admission\Models\ExamSession;
use Modules\Admission\Models\ExamSessionExaminer;
use Modules\Admission\Models\ExamVenue;
use Modules\Admission\Models\Wave;
use Modules\Admission\Outbox\OutboxRecorder;
use Modules\Admission\Support\Actor;
use Modules\Admission\Support\HistoryRecorder;
use Modules\Admission\Support\PmbSettings;
use Modules\Admission\Support\SequenceGenerator;
use Modules\AuditLog\Enums\AuditAction;

/**
 * Jadwal & pelaksanaan ujian (docs/pmb/06 §I): kapasitas dengan row lock,
 * pencegahan bentrok ruang/peserta/penguji (I.2), pembagian otomatis,
 * kartu peserta ber-QR, kehadiran, jadwal ulang.
 */
class ExamService
{
    public function __construct(
        private readonly SequenceGenerator $sequences,
        private readonly HistoryRecorder $history,
        private readonly OutboxRecorder $outbox,
        private readonly PmbSettings $settings,
    ) {}

    // ------------------------------------------------------------------ Lokasi & ruang

    /** @param  array<string, mixed>  $data */
    public function saveVenue(?ExamVenue $venue, array $data): ExamVenue
    {
        $venue ??= new ExamVenue;
        $venue->forceFill(Arr::only($data, ['name', 'address', 'map_url', 'is_active']))->save();

        return $venue->refresh();
    }

    public function deleteVenue(ExamVenue $venue): void
    {
        if (ExamSession::query()->whereIn('room_id', $venue->rooms()->select('id'))->exists()) {
            throw new ConflictException('Lokasi masih dipakai sesi ujian.', 'IN_USE');
        }

        $venue->rooms()->delete();
        $venue->delete();
    }

    /** @param  array<string, mixed>  $data */
    public function saveRoom(ExamVenue $venue, ?ExamRoom $room, array $data): ExamRoom
    {
        $values = Arr::only($data, ['code', 'name', 'capacity', 'is_accessible', 'facilities', 'is_active']);

        if ($room !== null && isset($values['capacity']) && ExamSession::query()->where('room_id', $room->getKey())->where('capacity', '>', (int) $values['capacity'])->exists()) {
            throw new ConflictException('Kapasitas ruang lebih kecil dari kapasitas sesi yang sudah dijadwalkan.', 'IN_USE');
        }

        $room ??= new ExamRoom(['venue_id' => $venue->getKey()]);
        $room->forceFill($values)->save();

        return $room->refresh();
    }

    public function deleteRoom(ExamRoom $room): void
    {
        if (ExamSession::query()->where('room_id', $room->getKey())->exists()) {
            throw new ConflictException('Ruang masih dipakai sesi ujian.', 'IN_USE');
        }

        $room->delete();
    }

    // ------------------------------------------------------------------ Jadwal & sesi

    /** @param  array<string, mixed>  $data */
    public function saveSchedule(Wave $wave, ?ExamSchedule $schedule, array $data, User $actor): ExamSchedule
    {
        $values = Arr::only($data, ['selection_component_id', 'wave_path_ids', 'mode', 'title', 'exam_date', 'instructions', 'external_reference', 'cbt_duration_minutes', 'cbt_question_count', 'cbt_randomize']);
        $date = CarbonImmutable::parse($values['exam_date'] ?? $schedule?->exam_date);

        if (! $wave->is_ods && $wave->exam_starts_on && $wave->exam_ends_on
            && ($date->lt(CarbonImmutable::parse($wave->exam_starts_on)) || $date->gt(CarbonImmutable::parse($wave->exam_ends_on)))) {
            throw ValidationException::withMessages(['exam_date' => 'Tanggal ujian harus dalam jendela ujian gelombang (I.2).']);
        }

        $pathIds = $values['wave_path_ids'] ?? $schedule?->wave_path_ids ?? [];

        if ($wave->wavePaths()->whereIn('id', $pathIds)->count() !== count($pathIds) || $pathIds === []) {
            throw ValidationException::withMessages(['wave_path_ids' => 'Pilih gelombang-jalur dari gelombang ini.']);
        }

        if ($schedule !== null && $schedule->status === ExamScheduleStatus::Completed) {
            throw new ConflictException('Jadwal yang sudah selesai tidak dapat diubah.', 'IN_USE');
        }

        return DB::transaction(function () use ($wave, $schedule, $values, $actor): ExamSchedule {
            if ($schedule === null) {
                $schedule = ExamSchedule::query()->create([...$values, 'wave_id' => $wave->getKey(), 'status' => ExamScheduleStatus::Draft, 'created_by' => $actor->getKey()]);
                $this->history->change($schedule, [], Arr::except($values, ['instructions']), null, AuditAction::Created);

                return $schedule;
            }

            $old = $schedule->only(array_keys($values));
            $schedule->forceFill($values)->save();
            $this->history->change($schedule, $old, $values);

            return $schedule->refresh();
        });
    }

    public function publishSchedule(ExamSchedule $schedule, User $actor): ExamSchedule
    {
        if ($schedule->sessions()->doesntExist()) {
            throw new DomainRuleException('Jadwal belum memiliki sesi.', 'NO_SESSIONS', 422);
        }

        if ($schedule->status !== ExamScheduleStatus::Draft) {
            throw new ConflictException('Hanya jadwal draft yang dapat dipublikasikan.', 'INVALID_TRANSITION');
        }

        return DB::transaction(function () use ($schedule): ExamSchedule {
            $schedule->forceFill(['status' => ExamScheduleStatus::Published, 'published_at' => now()])->save();
            $this->history->action($schedule, AuditAction::Updated, ['status' => 'published']);

            foreach ($schedule->participants()->with('application.account', 'session.room.venue')->get() as $participant) {
                $this->outbox->record(OutboxRecorder::EXAM_CARD, $participant, ['participant_id' => $participant->getKey()]);
                $this->notifyScheduled($participant, 'pmb.exam.scheduled');
            }

            return $schedule->refresh();
        });
    }

    public function cancelSchedule(ExamSchedule $schedule, string $reason, User $actor): ExamSchedule
    {
        return DB::transaction(function () use ($schedule, $reason): ExamSchedule {
            $schedule->forceFill(['status' => ExamScheduleStatus::Cancelled])->save();

            foreach ($schedule->participants()->with('application')->get() as $participant) {
                $participant->forceFill(['attendance_status' => AttendanceStatus::Excused, 'note' => mb_substr($reason, 0, 255)])->save();
                $this->setSelectionStatus($participant->application, SelectionStatus::Eligible, 'SCHEDULE_CANCELLED', $reason);
            }

            $this->history->action($schedule, AuditAction::Updated, ['status' => 'cancelled'], $reason);

            return $schedule->refresh();
        });
    }

    /** @param  array<string, mixed>  $data */
    public function saveSession(ExamSchedule $schedule, ?ExamSession $session, array $data, ?string $reason = null): ExamSession
    {
        $values = Arr::only($data, ['room_id', 'code', 'starts_at', 'ends_at', 'checkin_opens_at', 'capacity', 'online_url']);
        $starts = CarbonImmutable::parse($values['starts_at'] ?? $session?->starts_at);
        $ends = CarbonImmutable::parse($values['ends_at'] ?? $session?->ends_at);
        $roomId = array_key_exists('room_id', $values) ? $values['room_id'] : $session?->room_id;
        $capacity = (int) ($values['capacity'] ?? $session?->capacity);

        if (! $ends->gt($starts)) {
            throw ValidationException::withMessages(['ends_at' => 'Waktu selesai harus setelah waktu mulai.']);
        }

        if (! $schedule->wave->is_ods && $starts->toDateString() !== CarbonImmutable::parse($schedule->exam_date)->toDateString()) {
            throw ValidationException::withMessages(['starts_at' => 'Sesi harus pada tanggal jadwal ujian.']);
        }

        if ($roomId !== null) {
            $room = ExamRoom::query()->findOrFail($roomId);

            if ($capacity > $room->capacity) {
                throw ValidationException::withMessages(['capacity' => 'Kapasitas sesi melebihi kapasitas ruang ('.$room->capacity.').']);
            }

            // I.2 konflik ruang: interval [starts_at, ends_at) tidak boleh tumpang tindih.
            $clash = ExamSession::query()->where('room_id', $roomId)->when($session, fn ($q) => $q->whereKeyNot($session->getKey()))
                ->where('starts_at', '<', $ends)->where('ends_at', '>', $starts)->exists();

            if ($clash) {
                throw new ConflictException('Ruang sudah dipakai sesi lain pada waktu tersebut.', 'SCHEDULE_CONFLICT');
            }
        } elseif (! in_array($schedule->mode, [ExamMode::OnlineProctored, ExamMode::Cbt, ExamMode::Interview, ExamMode::PortfolioReview], true)) {
            throw ValidationException::withMessages(['room_id' => 'Ujian luring wajib memiliki ruang.']);
        }

        if ($session !== null) {
            if ($capacity < $session->participants_count) {
                throw new ConflictException('Kapasitas tidak boleh di bawah jumlah peserta yang sudah ditempatkan.', 'SESSION_FULL');
            }

            if ($session->participants_count > 0 && $schedule->status === ExamScheduleStatus::Published && ($reason === null || trim($reason) === '')) {
                throw ValidationException::withMessages(['reason' => 'Perubahan sesi setelah kartu peserta terbit wajib disertai alasan.']);
            }
        }

        return DB::transaction(function () use ($schedule, $session, $values, $reason, $starts): ExamSession {
            $isNew = $session === null;
            $session ??= new ExamSession(['schedule_id' => $schedule->getKey(), 'participants_count' => 0]);
            $timeChanged = ! $isNew && (! $session->starts_at->eq($starts) || ($values['room_id'] ?? $session->room_id) !== $session->room_id);
            $session->forceFill($values)->save();

            if ($timeChanged && $schedule->status === ExamScheduleStatus::Published) {
                foreach ($session->participants()->with('application.account', 'session.room.venue')->get() as $participant) {
                    $this->outbox->record(OutboxRecorder::EXAM_CARD, $participant, ['participant_id' => $participant->getKey()]);
                    $this->notifyScheduled($participant, 'pmb.exam.rescheduled');
                }

                $this->history->action($session, AuditAction::Updated, ['starts_at' => (string) $session->starts_at], $reason);
            }

            return $session->refresh();
        });
    }

    public function deleteSession(ExamSession $session): void
    {
        if ($session->participants_count > 0) {
            throw new ConflictException('Sesi yang sudah memiliki peserta tidak dapat dihapus.', 'IN_USE');
        }

        $session->delete();
    }

    /**
     * FR-EXM-03 otomatis: peserta eligible dari wave path jadwal ditempatkan
     * berurutan (no. pendaftaran / prodi / kebutuhan akses) sesuai kapasitas.
     *
     * @return array{assigned: int, unassigned: int}
     */
    public function autoAssign(ExamSchedule $schedule, string $strategy = 'registration_number', bool $onlyUnassigned = true): array
    {
        $sessions = $schedule->sessions()->with('room')->get();
        $totalFree = $sessions->sum(fn ($s) => max(0, $s->capacity - $s->participants_count));
        $applications = Application::query()->whereIn('wave_path_id', (array) $schedule->wave_path_ids)
            ->whereIn('selection_status', [SelectionStatus::Eligible->value, SelectionStatus::Scheduled->value, SelectionStatus::Assessed->value])
            ->when($onlyUnassigned, fn ($q) => $q->whereDoesntHave('examParticipations', fn ($p) => $p->where('schedule_id', $schedule->getKey())))
            ->with(['account.profile', 'choices'])
            ->get();

        if ($applications->count() > $totalFree) {
            throw new ConflictException("Kapasitas sesi kurang: {$totalFree} kursi untuk {$applications->count()} peserta.", 'CAPACITY_INSUFFICIENT');
        }

        $applications = match ($strategy) {
            'study_program' => $applications->sortBy(fn ($a) => ($a->choices->first()?->study_program_id ?? '').'|'.$a->registration_number),
            'accessibility' => $applications->sortBy(fn ($a) => (empty($a->account->profile?->special_needs) ? '1' : '0').'|'.$a->registration_number),
            default => $applications->sortBy('registration_number'),
        };

        // Peserta berkebutuhan khusus diarahkan ke ruang aksesibel lebih dulu.
        $orderedSessions = $strategy === 'accessibility' ? $sessions->sortByDesc(fn ($s) => (int) ($s->room?->is_accessible ?? false))->values() : $sessions->sortBy('starts_at')->values();
        $assigned = 0;

        foreach ($applications as $application) {
            foreach ($orderedSessions as $session) {
                try {
                    $this->placeParticipant($schedule, $session, $application);
                    $assigned++;
                    continue 2;
                } catch (ConflictException) {
                    continue;
                }
            }
        }

        return ['assigned' => $assigned, 'unassigned' => $applications->count() - $assigned];
    }

    /**
     * Penempatan satu peserta dengan lock baris sesi (I.2 kapasitas) & cek
     * bentrok peserta lintas jadwal. Nomor peserta {PERIODE}{GEL}{JALUR}{SEQ:5}
     * (awalan periode mencegah tabrakan nomor antarperiode).
     */
    public function placeParticipant(ExamSchedule $schedule, ExamSession $session, Application $application): ExamParticipant
    {
        return DB::transaction(function () use ($schedule, $session, $application): ExamParticipant {
            $session = ExamSession::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            if ($session->participants_count >= $session->capacity) {
                throw new ConflictException('Sesi sudah penuh.', 'SESSION_FULL');
            }

            $this->assertNoParticipantClash($application, $session);

            $existing = ExamParticipant::query()->where('schedule_id', $schedule->getKey())->where('application_id', $application->getKey())->first();

            if ($existing !== null) {
                throw new ConflictException('Peserta sudah ditempatkan pada jadwal ini.', 'DUPLICATE');
            }

            $snapshot = $application->config_snapshot;
            $prefix = ($snapshot['period_code'] ?? '').($snapshot['wave_code'] ?? '').($snapshot['path_code'] ?? '');
            $seq = $this->sequences->next('participant_number', $prefix, (string) $application->university_id);

            $participant = ExamParticipant::query()->create([
                'university_id' => $application->university_id,
                'schedule_id' => $schedule->getKey(),
                'session_id' => $session->getKey(),
                'application_id' => $application->getKey(),
                'participant_number' => $prefix.SequenceGenerator::pad($seq, 5),
                'attendance_status' => AttendanceStatus::Registered,
            ]);

            $session->forceFill(['participants_count' => $session->participants_count + 1, 'lock_version' => $session->lock_version + 1])->save();
            $this->setSelectionStatus($application, SelectionStatus::Scheduled, 'SCHEDULED');

            if ($schedule->status === ExamScheduleStatus::Published) {
                $this->outbox->record(OutboxRecorder::EXAM_CARD, $participant, ['participant_id' => $participant->getKey()]);
                $this->notifyScheduled($participant->load('application.account', 'session.room.venue'), 'pmb.exam.scheduled');
            }

            return $participant;
        });
    }

    /** Pindah sesi (11 §A.10 `move`) dengan alasan; setelah kartu terbit → notifikasi. */
    public function moveParticipant(ExamParticipant $participant, string $targetSessionId, string $reason, User $actor, bool $isReschedule = false): ExamParticipant
    {
        return DB::transaction(function () use ($participant, $targetSessionId, $reason, $actor, $isReschedule): ExamParticipant {
            $participant = ExamParticipant::query()->whereKey($participant->getKey())->lockForUpdate()->firstOrFail();
            $from = ExamSession::query()->whereKey($participant->session_id)->lockForUpdate()->firstOrFail();
            $to = ExamSession::query()->whereKey($targetSessionId)->where('schedule_id', $participant->schedule_id)->lockForUpdate()->firstOrFail();

            if ($from->getKey() === $to->getKey()) {
                return $participant;
            }

            if ($from->starts_at->isPast() && ! $actor->hasPermissionTo('pmb_scores.import')) {
                abort(403, 'Setelah sesi berlangsung, hanya panitia seleksi yang dapat mengubah peserta.');
            }

            if ($to->participants_count >= $to->capacity) {
                throw new ConflictException('Sesi tujuan sudah penuh.', 'SESSION_FULL');
            }

            $this->assertNoParticipantClash($participant->application, $to, $participant->getKey());

            $from->forceFill(['participants_count' => max(0, $from->participants_count - 1)])->save();
            $to->forceFill(['participants_count' => $to->participants_count + 1])->save();
            $participant->forceFill([
                'session_id' => $to->getKey(),
                'attendance_status' => AttendanceStatus::Registered,
                'reschedule_count' => $participant->reschedule_count + ($isReschedule ? 1 : 0),
                'card_file_id' => null,
                'note' => mb_substr($reason, 0, 255),
            ])->save();

            if ($participant->application->selection_status === SelectionStatus::Absent) {
                $this->setSelectionStatus($participant->application, SelectionStatus::Scheduled, 'RESCHEDULED', $reason);
            }

            $this->history->action($participant, AuditAction::Updated, ['from_session' => $from->code, 'to_session' => $to->code], $reason);

            if ($participant->schedule->status === ExamScheduleStatus::Published) {
                $this->outbox->record(OutboxRecorder::EXAM_CARD, $participant, ['participant_id' => $participant->getKey()]);
                $this->notifyScheduled($participant->load('application.account', 'session.room.venue'), 'pmb.exam.rescheduled');
            }

            return $participant->refresh();
        });
    }

    /**
     * Penguji/pengawas sesi (FR-EXM-04): bentrok waktu penguji ditolak; penguji
     * dengan deklarasi konflik kepentingan atas peserta sesi ditolak.
     *
     * @param  array<int, array{user_id: string, role: string}>  $examiners
     */
    public function setExaminers(ExamSession $session, array $examiners): ExamSession
    {
        return DB::transaction(function () use ($session, $examiners): ExamSession {
            foreach ($examiners as $examiner) {
                $clash = ExamSessionExaminer::query()->where('user_id', $examiner['user_id'])->where('session_id', '!=', $session->getKey())
                    ->whereHas('session', fn ($q) => $q->where('starts_at', '<', $session->ends_at)->where('ends_at', '>', $session->starts_at))->exists();

                if ($clash) {
                    throw new ConflictException('Penguji sudah ditugaskan pada sesi lain di waktu yang sama.', 'SCHEDULE_CONFLICT');
                }

                $conflict = ConflictDeclaration::query()->where('user_id', $examiner['user_id'])
                    ->whereIn('application_id', $session->participants()->select('application_id'))->exists();

                if ($conflict) {
                    throw new ConflictException('Penguji memiliki deklarasi konflik kepentingan dengan peserta sesi ini.', 'CONFLICT_OF_INTEREST');
                }
            }

            ExamSessionExaminer::query()->where('session_id', $session->getKey())->delete();

            foreach ($examiners as $examiner) {
                ExamSessionExaminer::query()->create(['session_id' => $session->getKey(), 'user_id' => $examiner['user_id'], 'role' => $examiner['role']]);
            }

            return $session->load('examiners.user');
        });
    }

    /**
     * @param  array<int, string>  $userIds
     * @return int jumlah sesi yang terisi
     */
    public function autoAssignExaminers(ExamSchedule $schedule, array $userIds, string $role, int $perSession = 1): int
    {
        $filled = 0;
        $index = 0;

        foreach ($schedule->sessions()->orderBy('starts_at')->get() as $session) {
            $chosen = [];

            for ($n = 0; $n < $perSession && $n < count($userIds); $n++) {
                $chosen[] = ['user_id' => $userIds[$index++ % count($userIds)], 'role' => $role];
            }

            try {
                $this->setExaminers($session, $chosen);
                $filled++;
            } catch (ConflictException) {
                continue;
            }
        }

        return $filled;
    }

    /**
     * Check-in (FR-EXM-06): token QR bertanda tangan atau centang manual oleh
     * pengawas yang ditugaskan / panitia.
     */
    public function checkIn(ExamSession $session, ?string $token, ?string $participantId, string $status, User $actor): ExamParticipant
    {
        $this->assertSessionStaff($session, $actor, ['proctor', 'examiner', 'interviewer']);
        $target = AttendanceStatus::from($status);

        if ($token !== null) {
            $participantId = $this->verifyCardToken($token);
        }

        $participant = ExamParticipant::query()->whereKey($participantId)->first();

        if ($participant === null || $participant->session_id !== $session->getKey()) {
            throw new DomainRuleException('Peserta bukan anggota sesi ini.', 'WRONG_SESSION', 422);
        }

        if ($session->starts_at->isPast() && $session->ends_at->addHours(2)->isPast() && ! $actor->hasPermissionTo('pmb_scores.import')) {
            abort(403, 'Kehadiran setelah sesi berakhir hanya dapat diubah panitia seleksi.');
        }

        return DB::transaction(function () use ($participant, $target, $actor): ExamParticipant {
            $participant->forceFill([
                'attendance_status' => $target,
                'checked_in_at' => $target === AttendanceStatus::Present ? now() : $participant->checked_in_at,
                'checked_in_by' => $actor->getKey(),
            ])->save();

            $application = $participant->application;

            if ($target === AttendanceStatus::Absent && $application->selection_status === SelectionStatus::Scheduled) {
                $this->setSelectionStatus($application, SelectionStatus::Absent, 'ABSENT');
                $this->outbox->notifyApplicant($application->account, 'pmb.exam.absent', ['exam_title' => $participant->schedule->title], aggregate: $participant);
            }

            if ($target === AttendanceStatus::Disqualified) {
                $this->setSelectionStatus($application, SelectionStatus::Disqualified, 'DISQUALIFIED');
                $this->outbox->notifyApplicant($application->account, 'pmb.selection.disqualified', [], aggregate: $participant);
            }

            if ($target === AttendanceStatus::Present && $application->selection_status === SelectionStatus::Absent) {
                $this->setSelectionStatus($application, SelectionStatus::Scheduled, 'PRESENT');
            }

            $this->history->action($participant, AuditAction::Updated, ['attendance_status' => $target->value]);

            return $participant->refresh();
        });
    }

    /** Token QR kartu: HMAC(app key) atas id peserta — tidak dapat dipalsukan. */
    public function cardToken(ExamParticipant $participant): string
    {
        $payload = $participant->getKey().'.'.$participant->session_id;

        return $payload.'.'.substr(hash_hmac('sha256', 'pmb-card|'.$payload, (string) config('app.key')), 0, 32);
    }

    public function verifyCardToken(string $token): string
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3 || ! hash_equals(substr(hash_hmac('sha256', 'pmb-card|'.$parts[0].'.'.$parts[1], (string) config('app.key')), 0, 32), $parts[2])) {
            throw new DomainRuleException('Kode QR kartu tidak valid.', 'INVALID_TOKEN', 422);
        }

        return $parts[0];
    }

    // ------------------------------------------------------------------ Jadwal ulang

    public function requestReschedule(ExamParticipant $participant, string $reason, ?string $attachmentFileId): ExamRescheduleRequest
    {
        if ($participant->reschedule_count >= $this->settings->int('pmb.max_reschedules')) {
            throw new DomainRuleException('Batas penjadwalan ulang telah tercapai.', 'RESCHEDULE_LIMIT', 422);
        }

        if (ExamRescheduleRequest::query()->where('participant_id', $participant->getKey())->where('status', RescheduleRequestStatus::Pending->value)->exists()) {
            throw new ConflictException('Permintaan jadwal ulang sebelumnya masih diproses.', 'DUPLICATE');
        }

        return ExamRescheduleRequest::query()->create([
            'participant_id' => $participant->getKey(),
            'reason' => $reason,
            'attachment_file_id' => $attachmentFileId,
            'status' => RescheduleRequestStatus::Pending,
        ]);
    }

    public function decideReschedule(ExamRescheduleRequest $request, bool $approve, ?string $targetSessionId, ?string $note, User $actor): ExamRescheduleRequest
    {
        if ($request->status !== RescheduleRequestStatus::Pending) {
            throw new ConflictException('Permintaan sudah diputuskan.', 'ALREADY_DECIDED');
        }

        return DB::transaction(function () use ($request, $approve, $targetSessionId, $note, $actor): ExamRescheduleRequest {
            if ($approve) {
                if ($targetSessionId === null) {
                    throw ValidationException::withMessages(['target_session_id' => 'Pilih sesi pengganti.']);
                }

                $participant = $request->participant;
                $participant->forceFill(['attendance_status' => AttendanceStatus::Excused])->save();
                $this->moveParticipant($participant, $targetSessionId, 'Jadwal ulang: '.$request->reason, $actor, isReschedule: true);
            }

            $request->forceFill([
                'status' => $approve ? RescheduleRequestStatus::Approved : RescheduleRequestStatus::Rejected,
                'target_session_id' => $approve ? $targetSessionId : null,
                'decision_note' => $note,
                'decided_by' => $actor->getKey(),
                'decided_at' => now(),
            ])->save();

            return $request->refresh();
        });
    }

    /** Jadwal ulang massal (force majeure, FR-EXM-08): seluruh peserta sesi dipindah. */
    public function bulkReschedule(ExamSession $from, ExamSession $to, string $reason, User $actor): int
    {
        $count = 0;

        foreach ($from->participants()->get() as $participant) {
            $participant->forceFill(['attendance_status' => AttendanceStatus::Excused])->save();
            $this->moveParticipant($participant, (string) $to->getKey(), $reason, $actor);
            $count++;
        }

        return $count;
    }

    /** Pengingat H-1 (13 §A.2 `pmb.exam.reminder`). */
    public function sendReminders(): int
    {
        $tomorrow = now()->addDay();
        $count = 0;

        ExamParticipant::query()->with('application.account', 'session.room.venue', 'schedule')
            ->where('attendance_status', AttendanceStatus::Registered->value)
            ->whereHas('schedule', fn ($q) => $q->where('status', ExamScheduleStatus::Published->value))
            ->whereHas('session', fn ($q) => $q->whereBetween('starts_at', [$tomorrow->startOfDay(), $tomorrow->endOfDay()]))
            ->get()
            ->each(function (ExamParticipant $participant) use (&$count): void {
                $this->notifyScheduled($participant, 'pmb.exam.reminder');
                $count++;
            });

        return $count;
    }

    // ------------------------------------------------------------------ Helper

    public function assertSessionStaff(ExamSession $session, User $actor, array $roles): void
    {
        if ($actor->hasPermissionTo('pmb_exams.update')) {
            return;
        }

        if (! $session->examiners()->where('user_id', $actor->getKey())->whereIn('role', $roles)->exists()) {
            abort(403, 'Anda tidak ditugaskan pada sesi ini.');
        }
    }

    private function assertNoParticipantClash(Application $application, ExamSession $session, ?string $ignoreParticipantId = null): void
    {
        // I.2 konflik peserta: tidak boleh dua sesi tumpang tindih (lintas jadwal/komponen).
        $clash = ExamParticipant::query()->where('application_id', $application->getKey())
            ->when($ignoreParticipantId, fn ($q) => $q->whereKeyNot($ignoreParticipantId))
            ->whereNotIn('attendance_status', [AttendanceStatus::Excused->value])
            ->whereHas('session', fn ($q) => $q->where('starts_at', '<', $session->ends_at)->where('ends_at', '>', $session->starts_at))
            ->exists();

        if ($clash) {
            throw new ConflictException('Peserta sudah memiliki sesi lain pada waktu yang sama.', 'SCHEDULE_CONFLICT');
        }
    }

    private function setSelectionStatus(Application $application, SelectionStatus $to, string $reasonCode, ?string $note = null): void
    {
        $application = Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
        $from = $application->selection_status;

        if ($from === $to || ! $from->canTransitionTo($to)) {
            return;
        }

        $application->forceFill(['selection_status' => $to])->save();
        $this->history->record($application, Lifecycle::Selection, $from, $to, Actor::system(), $reasonCode, $note);
    }

    private function notifyScheduled(ExamParticipant $participant, string $eventKey): void
    {
        $session = $participant->session;
        $location = $session->room ? trim(($session->room->venue?->name ?? '').' — '.$session->room->name, ' —') : ($session->online_url ? 'Daring' : '-');
        $timezone = (string) (\Modules\Tenancy\Models\University::query()->whereKey($participant->university_id)->value('timezone') ?: config('app.timezone'));

        $this->outbox->notifyApplicant($participant->application->account, $eventKey, [
            'exam_title' => $participant->schedule->title,
            'exam_date' => $session->starts_at->setTimezone($timezone)->format('d-m-Y H:i'),
            'exam_location' => $location,
        ], aggregate: $participant);
    }
}
