<?php

namespace Modules\Academic\Controllers;

use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Academic\Enums\AcademicCalendarCategory;
use Modules\Academic\Models\AcademicCalendarEvent;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\StudyProgram;

/**
 * Agenda kalender akademik (UTS, UAS, libur, wisuda, ...) yang dikelola
 * Bagian Akademik dan tampil di kalender Portal Mahasiswa.
 */
class AcademicCalendarEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'academic_term_id' => ['nullable', 'string', 'max:26'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $events = AcademicCalendarEvent::query()
            ->with(['academicTerm', 'studyProgram'])
            ->when($filters['academic_term_id'] ?? null, fn ($query, $termId) => $query->where('academic_term_id', $termId))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('end_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('start_date', '<=', $to))
            ->orderBy('start_date')
            ->limit(500)
            ->get();

        return ApiResponse::success($events->map(fn (AcademicCalendarEvent $event) => $this->format($event))->values());
    }

    public function store(Request $request): JsonResponse
    {
        $event = new AcademicCalendarEvent(['created_by' => $request->user()->id]);
        $this->fillFromRequest($event, $request, creating: true);

        return ApiResponse::success($this->format($event->load(['academicTerm', 'studyProgram'])), 'Agenda ditambahkan.', status: 201);
    }

    public function update(Request $request, AcademicCalendarEvent $academicCalendarEvent): JsonResponse
    {
        $this->fillFromRequest($academicCalendarEvent, $request, creating: false);

        return ApiResponse::success($this->format($academicCalendarEvent->load(['academicTerm', 'studyProgram'])), 'Agenda diperbarui.');
    }

    public function destroy(AcademicCalendarEvent $academicCalendarEvent): JsonResponse
    {
        $academicCalendarEvent->delete();

        return ApiResponse::success(null, 'Agenda dihapus.');
    }

    private function fillFromRequest(AcademicCalendarEvent $event, Request $request, bool $creating): void
    {
        $required = $creating ? 'required' : 'sometimes';

        $data = $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => [$required, Rule::enum(AcademicCalendarCategory::class)],
            'start_date' => [$required, 'date'],
            'end_date' => [$required, 'date', 'after_or_equal:start_date'],
            'academic_term_id' => ['nullable', 'string', 'max:26'],
            'study_program_id' => ['nullable', 'string', 'max:26'],
        ], [
            'end_date.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
        ]);

        if (! empty($data['academic_term_id']) && ! AcademicTerm::query()->whereKey($data['academic_term_id'])->exists()) {
            throw ValidationException::withMessages(['academic_term_id' => 'Semester tidak ditemukan.']);
        }

        if (! empty($data['study_program_id']) && ! StudyProgram::query()->whereKey($data['study_program_id'])->exists()) {
            throw ValidationException::withMessages(['study_program_id' => 'Program studi tidak ditemukan.']);
        }

        $event->fill($data)->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function format(AcademicCalendarEvent $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'category' => $event->category->value,
            'category_label' => $event->category->label(),
            'start_date' => $event->start_date->toDateString(),
            'end_date' => $event->end_date->toDateString(),
            'academic_term' => $event->academicTerm ? ['id' => $event->academicTerm->id, 'label' => $event->academicTerm->label()] : null,
            'study_program' => $event->studyProgram ? ['id' => $event->studyProgram->id, 'name' => $event->studyProgram->name] : null,
        ];
    }
}
