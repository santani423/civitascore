<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Enums\AcademicSemester;
use Modules\Academic\Enums\KrsItemStatus;
use Modules\Academic\Models\AcademicTerm;
use Modules\Academic\Models\Student;
use Modules\Finance\Enums\RateTableStatus;
use Modules\Finance\Models\FeeComponent;
use Modules\Finance\Models\FeeRate;
use Modules\Finance\Models\FeeRateTable;
use Modules\Finance\Models\StudentUktAssignment;
use Modules\Finance\Support\KeuSettings;

/**
 * Resolusi tarif (rancangan KEU §8.3.1, BR-KEU-01..05): baris paling
 * spesifik dari tabel `published`; skor = Σ bobot dimensi non-kosong
 * (prodi 64, golongan UKT 32, jalur 16, kelas/sistem 8, tingkat semester 4,
 * angkatan 2). Dua baris berdimensi identik dengan rentang beririsan adalah
 * konflik dan memblokir publikasi.
 */
class TariffResolver
{
    public function __construct(private readonly KeuSettings $settings) {}

    public static function score(FeeRate|array $rate): int
    {
        $get = fn (string $key) => is_array($rate) ? ($rate[$key] ?? null) : $rate->getAttribute($key);

        return ($get('study_program_id') ? 64 : 0)
            + ($get('ukt_group_id') ? 32 : 0)
            + ($get('admission_path_code') ? 16 : 0)
            + ($get('study_system_code') ? 8 : 0)
            + (($get('semester_level_from') || $get('semester_level_to')) ? 4 : 0)
            + (($get('admission_year_from') || $get('admission_year_to')) ? 2 : 0);
    }

    /** Tingkat semester = semester reguler sejak tahun angkatan (cuti belum dikurangkan). */
    public function semesterLevel(Student $student, AcademicTerm $term): int
    {
        return max(1, ($term->startYear() - (int) $student->admission_year) * 2 + ($term->semester === AcademicSemester::Ganjil ? 1 : 2));
    }

    public function tableFor(Student $student, AcademicTerm $term): ?FeeRateTable
    {
        $published = FeeRateTable::query()->where('status', RateTableStatus::Published)->orderByDesc('version');

        if ($this->settings->get('keu.rate_lock_mode') === 'cohort') {
            $cohort = (clone $published)->where('academic_year', 'like', $student->admission_year.'/%')->first();

            if ($cohort !== null) {
                return $cohort;
            }
        }

        return (clone $published)->where('academic_year', $term->academic_year)->first()
            ?? (clone $published)->where('academic_year', '<=', $term->academic_year)->orderByDesc('academic_year')->first();
    }

    /**
     * Golongan UKT yang berlaku untuk semester `$term`: penetapan terbaru yang
     * sudah efektif (tanpa semester mulai, atau semester mulai ≤ `$term`).
     * Penetapan "mulai semester depan" tidak memengaruhi tagihan semester ini —
     * yang dipakai penetapan sebelumnya walau sudah digantikan.
     */
    public function activeUktGroupId(Student $student, ?AcademicTerm $term = null): ?string
    {
        if ($term === null) {
            return StudentUktAssignment::query()->where('student_id', $student->id)->where('status', 'active')->latest()->value('ukt_group_id');
        }

        return StudentUktAssignment::query()->where('student_id', $student->id)->with('effectiveFromTerm')->orderByDesc('created_at')->orderByDesc('id')->get()
            ->first(fn (StudentUktAssignment $a) => $a->effectiveFromTerm === null || $a->effectiveFromTerm->start_date <= $term->start_date)
            ?->ukt_group_id;
    }

    public function resolve(Student $student, AcademicTerm $term, FeeComponent $component, ?FeeRateTable $table = null, ?string $uktGroupId = null): ?FeeRate
    {
        $table ??= $this->tableFor($student, $term);

        if ($table === null) {
            return null;
        }

        $level = $this->semesterLevel($student, $term);
        $uktGroupId ??= $this->activeUktGroupId($student, $term);

        return FeeRate::query()->where('fee_rate_table_id', $table->id)->where('fee_component_id', $component->id)->get()
            ->filter(fn (FeeRate $rate) => $this->matches($rate, $student, $level, $uktGroupId))
            ->sortByDesc('specificity_score')
            ->first();
    }

    public function creditsFor(Student $student, AcademicTerm $term): int
    {
        return (int) DB::table('krs_items')
            ->join('class_sections', 'class_sections.id', '=', 'krs_items.class_section_id')
            ->join('courses', 'courses.id', '=', 'class_sections.course_id')
            ->where('krs_items.student_id', $student->id)
            ->where('krs_items.academic_term_id', $term->id)
            ->where('krs_items.status', KrsItemStatus::Enrolled->value)
            ->sum('courses.credits');
    }

    /**
     * Pasangan baris yang bertabrakan (BR-KEU-02).
     *
     * @return array<int, array{component: string, rate_ids: array<int, string>}>
     */
    public function conflicts(FeeRateTable $table): array
    {
        $conflicts = [];
        $rates = FeeRate::query()->where('fee_rate_table_id', $table->id)->with('component')->get();

        /** @var Collection<string, Collection<int, FeeRate>> $groups */
        $groups = $rates->groupBy(fn (FeeRate $r) => implode('|', [$r->fee_component_id, $r->study_program_id, $r->ukt_group_id, $r->admission_path_code, $r->study_system_code, $r->specificity_score]));

        foreach ($groups as $group) {
            $list = $group->values();

            for ($i = 0; $i < $list->count(); $i++) {
                for ($j = $i + 1; $j < $list->count(); $j++) {
                    if ($this->overlaps($list[$i]->admission_year_from, $list[$i]->admission_year_to, $list[$j]->admission_year_from, $list[$j]->admission_year_to)
                        && $this->overlaps($list[$i]->semester_level_from, $list[$i]->semester_level_to, $list[$j]->semester_level_from, $list[$j]->semester_level_to)) {
                        $conflicts[] = ['component' => (string) $list[$i]->component?->name, 'rate_ids' => [$list[$i]->id, $list[$j]->id]];
                    }
                }
            }
        }

        return $conflicts;
    }

    private function matches(FeeRate $rate, Student $student, int $level, ?string $uktGroupId): bool
    {
        return ($rate->study_program_id === null || $rate->study_program_id === $student->study_program_id)
            && ($rate->ukt_group_id === null || $rate->ukt_group_id === $uktGroupId)
            && ($rate->admission_path_code === null || $rate->admission_path_code === $student->admission_path_code)
            && ($rate->study_system_code === null || $rate->study_system_code === $student->study_system_code)
            && ($rate->admission_year_from === null || (int) $student->admission_year >= $rate->admission_year_from)
            && ($rate->admission_year_to === null || (int) $student->admission_year <= $rate->admission_year_to)
            && ($rate->semester_level_from === null || $level >= $rate->semester_level_from)
            && ($rate->semester_level_to === null || $level <= $rate->semester_level_to);
    }

    private function overlaps(?int $aFrom, ?int $aTo, ?int $bFrom, ?int $bTo): bool
    {
        $aFrom ??= PHP_INT_MIN;
        $aTo ??= PHP_INT_MAX;
        $bFrom ??= PHP_INT_MIN;
        $bTo ??= PHP_INT_MAX;

        return $aFrom <= $bTo && $bFrom <= $aTo;
    }
}
