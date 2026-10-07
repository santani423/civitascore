<?php

namespace Modules\Academic\Services;

use App\Support\Http\Exceptions\ConflictException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
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
 *
 * Satu-satunya sumber perhitungan IP/IPK di seluruh sistem — transkrip
 * admin, KHS/transkrip/dashboard Portal Mahasiswa, dan PDF semuanya lewat
 * gradedRecords()/transcript() di sini, supaya angka IPK tidak pernah
 * berbeda antar halaman. Aturan pengulangan (§4.17 "Pengambilan nilai
 * terbaik"): IPS menghitung semua mata kuliah yang dinilai pada semester
 * itu, IPK & total SKS hanya menghitung nilai terbaik per mata kuliah.
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

            // Baris draft/pending milik mahasiswa yang sama sudah memegang
            // kursinya sendiri — tidak dihitung dua kali saat dikukuhkan.
            if ($this->seatsTaken($classSection, except: $existing?->id) >= $classSection->capacity) {
                throw new ConflictException('Kelas sudah penuh.');
            }

            $this->assertWithinSksLimit($student, $classSection, except: $existing?->id);

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

        if ($krsItem->status !== KrsItemStatus::Enrolled) {
            throw new ConflictException('KRS ini belum disetujui dosen wali, sehingga belum dapat dinilai.');
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
     * Ringkasan transkrip: IP per semester (urut kronologis), IPK, dan total
     * SKS yang dihitung ke IPK.
     *
     * @return array{terms: array<int, array{academic_term_id: string, label: string, sks: int, ip: float}>, ipk: float, total_sks: int, passed_sks: int}
     */
    public function transcript(Student $student): array
    {
        $records = $this->gradedRecords($student);

        $terms = $records
            ->groupBy(fn (Grade $grade) => $grade->krsItem->academic_term_id)
            ->map(function (Collection $termGrades) {
                $term = $termGrades->first()->krsItem->academicTerm;
                [$sks, $points] = $this->sumCreditsAndPoints($termGrades);

                return [
                    'academic_term_id' => $term->id,
                    'label' => $term->label(),
                    'start_date' => $term->start_date->toDateString(),
                    'sks' => $sks,
                    'ip' => $this->ratio($points, $sks),
                ];
            })
            ->sortBy('start_date')
            ->map(fn (array $term) => collect($term)->except('start_date')->all())
            ->values()
            ->all();

        $best = $this->bestAttempts($records);
        [$totalSks, $totalPoints] = $this->sumCreditsAndPoints($best);

        return [
            'terms' => $terms,
            'ipk' => $this->ratio($totalPoints, $totalSks),
            'total_sks' => $totalSks,
            'passed_sks' => $best
                ->filter(fn (Grade $grade) => $grade->letter_grade->isPassing())
                ->sum(fn (Grade $grade) => $grade->krsItem->classSection->course->credits),
        ];
    }

    /**
     * IPK & total SKS kumulatif sampai dengan (termasuk) semester tertentu —
     * angka "IPK" pada KHS semester lampau.
     *
     * @return array{ipk: float, total_sks: int}
     */
    public function cumulativeUntil(Student $student, AcademicTerm $term): array
    {
        $records = $this->gradedRecords($student)->filter(
            fn (Grade $grade) => $grade->krsItem->academicTerm->start_date->lessThanOrEqualTo($term->start_date),
        );

        [$sks, $points] = $this->sumCreditsAndPoints($this->bestAttempts($records));

        return ['ipk' => $this->ratio($points, $sks), 'total_sks' => $sks];
    }

    /**
     * Seluruh nilai (huruf terisi) milik mahasiswa dari KRS berstatus
     * Enrolled, beserta mata kuliah & semesternya — bahan baku tunggal
     * transkrip, KHS, dan daftar nilai.
     *
     * @return EloquentCollection<int, Grade>
     */
    public function gradedRecords(Student $student, ?string $academicTermId = null): EloquentCollection
    {
        return Grade::query()
            ->whereHas('krsItem', function ($query) use ($student, $academicTermId): void {
                // Enrolled, bukan Dropped/draft — kalau sebuah baris entah
                // bagaimana punya nilai walau sudah di-drop (tidak seharusnya
                // terjadi lewat drop()/recordGrade(), tapi data lama/seed bisa
                // saja begitu), itu tidak boleh ikut dihitung ke IP/IPK.
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
     * Nilai terbaik per mata kuliah (aturan pengulangan §4.17). Seri bobot
     * dimenangkan percobaan yang lebih baru.
     *
     * @param  Collection<int, Grade>  $records
     * @return Collection<int, Grade>
     */
    public function bestAttempts(Collection $records): Collection
    {
        return $records
            ->groupBy(fn (Grade $grade) => $grade->krsItem->classSection->course_id)
            ->map(fn (Collection $attempts) => $attempts
                ->sortByDesc(fn (Grade $grade) => sprintf(
                    '%05.2f|%s',
                    $grade->letter_grade->weight(),
                    $grade->krsItem->academicTerm->start_date->format('Ymd'),
                ))
                ->first())
            ->values();
    }

    /**
     * Batas SKS yang boleh diambil mahasiswa pada suatu periode, dihitung
     * dari IP semester terakhir yang ia tempuh (ada nilainya) sebelum
     * periode tersebut (§4.10) — semester cuti dilewati, bukan dihitung IP 0.
     * Mahasiswa tanpa riwayat nilai (semester pertama) dapat batas default.
     */
    public function maxSksForTerm(Student $student, AcademicTerm $term): int
    {
        $previous = $this->gradedRecords($student)
            ->filter(fn (Grade $grade) => $grade->krsItem->academicTerm->start_date->lessThan($term->start_date))
            ->groupBy(fn (Grade $grade) => $grade->krsItem->academic_term_id)
            ->sortByDesc(fn (Collection $grades) => $grades->first()->krsItem->academicTerm->start_date->format('Ymd'))
            ->first();

        if ($previous === null) {
            return self::DEFAULT_MAX_SKS;
        }

        [$sks, $points] = $this->sumCreditsAndPoints($previous);

        if ($sks === 0) {
            return self::DEFAULT_MAX_SKS;
        }

        $ip = $this->ratio($points, $sks);

        foreach (self::SKS_LIMIT_TIERS as $tier) {
            if ($ip >= $tier['min']) {
                return $tier['max'];
            }
        }

        return self::DEFAULT_MAX_SKS;
    }

    public function maxSks(Student $student, ClassSection $classSection): int
    {
        return $this->maxSksForTerm($student, $classSection->academicTerm);
    }

    /**
     * Kursi kelas yang sudah terisi (draft/pending/enrolled — lihat
     * KrsItemStatus::seatHolding()).
     */
    public function seatsTaken(ClassSection $classSection, ?string $except = null): int
    {
        return KrsItem::query()
            ->where('class_section_id', $classSection->id)
            ->whereIn('status', KrsItemStatus::seatHolding())
            ->when($except !== null, fn ($query) => $query->where('id', '!=', $except))
            ->count();
    }

    /**
     * SKS yang sedang diambil mahasiswa pada satu semester (draft, pending,
     * maupun enrolled).
     */
    public function creditsTaken(Student $student, string $academicTermId, ?string $except = null): int
    {
        return (int) KrsItem::query()
            ->where('student_id', $student->id)
            ->where('academic_term_id', $academicTermId)
            ->whereIn('status', KrsItemStatus::seatHolding())
            ->when($except !== null, fn ($query) => $query->where('id', '!=', $except))
            ->with('classSection.course')
            ->get()
            ->sum(fn (KrsItem $item) => $item->classSection->course->credits);
    }

    private function assertWithinSksLimit(Student $student, ClassSection $classSection, ?string $except = null): void
    {
        $courseCredits = $classSection->course->credits;
        $currentCredits = $this->creditsTaken($student, $classSection->academic_term_id, $except);
        $maxSks = $this->maxSks($student, $classSection);

        if ($currentCredits + $courseCredits > $maxSks) {
            throw new ConflictException(
                "Melebihi batas maksimum {$maxSks} SKS untuk semester ini (sudah mengambil {$currentCredits} SKS)."
            );
        }
    }

    /**
     * @param  Collection<int, Grade>  $grades
     * @return array{0: int, 1: float}
     */
    private function sumCreditsAndPoints(Collection $grades): array
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

    private function ratio(float $points, int $sks): float
    {
        return $sks > 0 ? round($points / $sks, 2) : 0.0;
    }
}
