<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Enums\LetterGrade;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Attendance;
use Modules\Academic\Models\ClassSection;
use Modules\Academic\Models\Grade;
use Modules\Academic\Models\KrsItem;
use Modules\Academic\Models\Student;

/**
 * Aturan bisnis inti KRS/Nilai/Absensi (RANCANGAN-APLIKASI.md §4.10, §4.16,
 * §4.14, §4.17) — enrollment, penilaian, rekap kehadiran, dan perhitungan
 * IP/IPK/batas SKS. Controller tetap tipis; semua validasi state-dependent
 * (kapasitas kelas, duplikasi, batas SKS) hidup di sini supaya bisa dites
 * tanpa HTTP layer.
 */
class AcademicRecordService
{
    /**
     * Batas maksimum SKS berdasarkan IP semester sebelumnya, persis tabel di
     * RANCANGAN-APLIKASI.md §4.10. Dicek berurutan dari tier tertinggi.
     *
     * @var array<int, array{min: float, max: int}>
     */
    private const SKS_LIMIT_TIERS = [
        ['min' => 3.00, 'max' => 24],
        ['min' => 2.50, 'max' => 22],
        ['min' => 2.00, 'max' => 20],
        ['min' => 0.00, 'max' => 18],
    ];

    /** Mahasiswa baru tanpa riwayat IP (semester pertama) belum punya tier IP untuk dipatok. */
    private const DEFAULT_MAX_SKS = 24;

    /**
     * @param  array<string, mixed>  $data  Divalidasi StoreKrsItemRequest — berisi student_id, class_section_id.
     */
    public function enroll(array $data): KrsItem
    {
        return DB::transaction(function () use ($data): KrsItem {
            /** @var ClassSection $classSection */
            $classSection = ClassSection::query()->with('course')->lockForUpdate()->findOrFail((string) $data['class_section_id']);

            if (! $classSection->is_active) {
                throw new ConflictException('Kelas ini sudah tidak aktif dan tidak menerima peserta baru.');
            }

            /** @var Student $student */
            $student = Student::query()->findOrFail((string) $data['student_id']);

            // Unique index-nya (student_id, class_section_id) tanpa memandang
            // status, jadi mahasiswa yang pernah drop dari kelas ini
            // didaftarkan ulang lewat baris yang sama, bukan baris baru.
            $existing = KrsItem::query()
                ->where('student_id', $student->id)
                ->where('class_section_id', $classSection->id)
                ->first();

            if ($existing?->status === KrsItemStatus::Enrolled) {
                throw new ConflictException('Mahasiswa sudah terdaftar pada kelas ini.');
            }

            $enrolledCount = KrsItem::query()
                ->where('class_section_id', $classSection->id)
                ->where('status', KrsItemStatus::Enrolled)
                ->count();

            if ($enrolledCount >= $classSection->capacity) {
                throw new ConflictException('Kelas sudah penuh.');
            }

            $this->assertWithinSksLimit($student, $classSection);

            if ($existing !== null) {
                $existing->update(['status' => KrsItemStatus::Enrolled]);

                return $existing;
            }

            return KrsItem::query()->create([
                'student_id' => $student->id,
                'class_section_id' => $classSection->id,
                'academic_term_id' => $classSection->academic_term_id,
                'status' => KrsItemStatus::Enrolled,
            ]);
        });
    }

    public function drop(KrsItem $krsItem): KrsItem
    {
        if ($krsItem->status === KrsItemStatus::Dropped) {
            throw new ConflictException('KRS ini sudah dibatalkan sebelumnya.');
        }

        if ($krsItem->grade()->exists()) {
            throw new ConflictException('KRS yang sudah dinilai tidak dapat dibatalkan.');
        }

        $krsItem->update(['status' => KrsItemStatus::Dropped]);

        return $krsItem;
    }

    /**
     * @param  array<string, mixed>  $data  Divalidasi UpsertGradeRequest — berisi score, letter_grade opsional.
     */
    public function recordGrade(KrsItem $krsItem, array $data): Grade
    {
        if ($krsItem->status === KrsItemStatus::Dropped) {
            throw new ConflictException('Tidak dapat menilai KRS yang sudah dibatalkan.');
        }

        $letterGrade = isset($data['letter_grade'])
            ? LetterGrade::from((string) $data['letter_grade'])
            : LetterGrade::fromScore((float) $data['score']);

        return DB::transaction(fn (): Grade => Grade::query()->updateOrCreate(
            ['krs_item_id' => $krsItem->id],
            ['score' => $data['score'], 'letter_grade' => $letterGrade, 'submitted_at' => now()],
        ));
    }

    /**
     * @param  array<string, mixed>  $data  Divalidasi RecordAttendanceBatchRequest — berisi meeting_number, meeting_date, entries.
     * @return EloquentCollection<int, Attendance>
     */
    public function recordAttendanceBatch(ClassSection $classSection, array $data): EloquentCollection
    {
        $enrolledKrsItemIds = KrsItem::query()
            ->where('class_section_id', $classSection->id)
            ->where('status', KrsItemStatus::Enrolled)
            ->pluck('id')
            ->all();

        return DB::transaction(function () use ($data, $enrolledKrsItemIds): EloquentCollection {
            $records = new EloquentCollection;

            /** @var array<string, mixed> $entry */
            foreach ((array) $data['entries'] as $entry) {
                if (! in_array($entry['krs_item_id'], $enrolledKrsItemIds, true)) {
                    throw new ConflictException('Salah satu mahasiswa bukan peserta aktif kelas ini.');
                }

                $records->push(Attendance::query()->updateOrCreate(
                    [
                        'krs_item_id' => $entry['krs_item_id'],
                        'meeting_number' => $data['meeting_number'],
                    ],
                    [
                        'meeting_date' => $data['meeting_date'],
                        'status' => $entry['status'],
                        'notes' => $entry['notes'] ?? null,
                    ],
                ));
            }

            return $records->load(['krsItem.student', 'krsItem.classSection.course']);
        });
    }

    /**
     * @return array{terms: array<int, array{academic_term_id: string, label: string, sks: int, ip: float}>, ipk: float, total_sks: int}
     */
    public function transcript(Student $student): array
    {
        $grades = $this->gradedCredits($student);

        $terms = $grades
            ->groupBy(fn (Grade $grade) => $grade->krsItem->academic_term_id)
            ->map(function (EloquentCollection $termGrades) {
                $term = $termGrades->first()->krsItem->academicTerm;
                [$sks, $points] = $this->sumCreditsAndPoints($termGrades);

                return [
                    'academic_term_id' => $term->id,
                    'label' => $term->label(),
                    'sks' => $sks,
                    'ip' => $sks > 0 ? round($points / $sks, 2) : 0.0,
                ];
            })
            ->values()
            ->all();

        [$totalSks, $totalPoints] = $this->sumCreditsAndPoints($grades);

        return [
            'terms' => $terms,
            'ipk' => $totalSks > 0 ? round($totalPoints / $totalSks, 2) : 0.0,
            'total_sks' => $totalSks,
        ];
    }

    /**
     * Batas SKS yang boleh diambil mahasiswa pada suatu periode, dihitung
     * dari IP periode terakhir sebelum periode tersebut (§4.10). Mahasiswa
     * tanpa riwayat nilai (semester pertama) dapat batas default.
     */
    public function maxSks(Student $student, ClassSection $classSection): int
    {
        $currentTerm = $classSection->academicTerm;

        $lastTerm = AcademicTerm::query()
            ->where('id', '!=', $currentTerm->id)
            ->where('start_date', '<', $currentTerm->start_date)
            ->orderByDesc('start_date')
            ->first();

        if ($lastTerm === null) {
            return self::DEFAULT_MAX_SKS;
        }

        $ip = $this->termGpa($student, $lastTerm);

        if ($ip === null) {
            return self::DEFAULT_MAX_SKS;
        }

        foreach (self::SKS_LIMIT_TIERS as $tier) {
            if ($ip >= $tier['min']) {
                return $tier['max'];
            }
        }

        return self::DEFAULT_MAX_SKS;
    }

    private function assertWithinSksLimit(Student $student, ClassSection $classSection): void
    {
        $courseCredits = $classSection->course->credits;

        $currentCredits = KrsItem::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $classSection->academic_term_id)
            ->where('status', KrsItemStatus::Enrolled)
            ->with('classSection.course')
            ->get()
            ->sum(fn (KrsItem $item) => $item->classSection->course->credits);

        $maxSks = $this->maxSks($student, $classSection);

        if ($currentCredits + $courseCredits > $maxSks) {
            throw new ConflictException(
                "Melebihi batas maksimum {$maxSks} SKS untuk semester ini (sudah mengambil {$currentCredits} SKS)."
            );
        }
    }

    private function termGpa(Student $student, AcademicTerm $term): ?float
    {
        $grades = $this->gradedCredits($student, $term->id);

        if ($grades->isEmpty()) {
            return null;
        }

        [$sks, $points] = $this->sumCreditsAndPoints($grades);

        return $sks > 0 ? round($points / $sks, 2) : null;
    }

    /**
     * @return EloquentCollection<int, Grade>
     */
    private function gradedCredits(Student $student, ?string $academicTermId = null): EloquentCollection
    {
        return Grade::query()
            ->whereHas('krsItem', function ($query) use ($student, $academicTermId): void {
                // Enrolled, bukan Dropped — kalau sebuah baris entah bagaimana
                // punya nilai walau sudah di-drop (tidak seharusnya terjadi
                // lewat drop()/recordGrade(), tapi data lama/seed bisa saja
                // begitu), itu tidak boleh ikut dihitung ke IP/IPK.
                $query->where('student_id', $student->id)->where('status', KrsItemStatus::Enrolled);

                if ($academicTermId !== null) {
                    $query->where('academic_term_id', $academicTermId);
                }
            })
            ->whereNotNull('letter_grade')
            ->with(['krsItem.classSection.course', 'krsItem.academicTerm'])
            ->get();
    }

    /**
     * @param  EloquentCollection<int, Grade>  $grades
     * @return array{0: int, 1: float}
     */
    private function sumCreditsAndPoints(EloquentCollection $grades): array
    {
        $sks = 0;
        $points = 0.0;

        foreach ($grades as $grade) {
            $credits = $grade->krsItem->classSection->course->credits;
            $sks += $credits;
            $points += $credits * $grade->letter_grade->weight();
        }

        return [$sks, $points];
    }
}
