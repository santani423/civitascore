<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Academic\Enums\StudentStatus;
use Modules\Academic\Models\Faculty;
use Modules\Academic\Models\Student;
use Modules\Academic\Models\StudyProgram;
use Modules\Tenancy\Models\University;

/**
 * Seeds the real Teknik Informatika student roster for Universitas Katolik
 * Indonesia Atma Jaya (university code UAJ) — unlike AcademicSeeder's
 * faker-generated students, every row here is an actual mahasiswa
 * (nim/name/tanggal_lahir supplied by the user), not sample data.
 * admission_year is read off the nim's embedded year segment (e.g.
 * "12025006231" -> 2025) rather than assigned randomly.
 *
 * Idempotent: Faculty/StudyProgram are updateOrCreate by code, and each
 * Student is updateOrCreate by (university_id, nim) — matching the
 * students table's unique constraint — so re-running never duplicates rows.
 */
class AtmaJayaStudentSeeder extends Seeder
{
    /** @var array<int, array{nim: string, name: string, tanggal_lahir: string}> */
    private const ROSTER = [
        ['nim' => '12025006231', 'name' => 'ADHE TRIMADHANI SITANGGANG', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003464', 'name' => 'ADRIAN RAMADHAN', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000794', 'name' => 'ALBERT FLINDERICO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000580', 'name' => 'ALLOYSIUS CHRISTIAN MARVELY', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005027', 'name' => 'ANGELA CLARISSA VANIA PRATISTA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025002894', 'name' => 'ANGGER RIZKY WIDIYANTOKO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025006035', 'name' => 'ANTONIO FRANCISCO MIGUEL VONG EGIDIO AMARAL', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003355', 'name' => 'BENEDICTUS ADRIAN WISNU BROTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000904', 'name' => 'BENEDICTUS CHRISTIAN SETIAJI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000803', 'name' => 'BERNARD NATHANIEL', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004374', 'name' => 'CHEVCHENCO FRANZI RODRIGO DE MENDONÇA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000873', 'name' => 'CHRISTIAN NATHANIEL', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005317', 'name' => 'DEWI FITRIYANI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12023004133', 'name' => 'DIAN VIOLIN KAPISA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004373', 'name' => 'FRANCISCO JANUÁRIO CASIMIRO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005363', 'name' => 'FRANSISKUS APRILIO BAYU PASKALIS', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004913', 'name' => 'FRANSISKUS CONAVARIO ALFIANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000800', 'name' => 'FRISCA CHRISTELLYA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003412', 'name' => 'GABRIELLA STEFANI ALWI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025002726', 'name' => 'GHREGHORHIOES DHAVID SETYADHIRJHA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003949', 'name' => 'GRATIANUS GANESHA GEMILANG PRASAJA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004622', 'name' => 'HOLLY WULAN SHIREEN GONI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025006896', 'name' => 'JEVON OZORA TJANGGULUNG SUSANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000078', 'name' => 'JOSELINE JANNETH OYA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000740', 'name' => 'KORNELIUS WILLIS PANDU PRASETYA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004041', 'name' => 'LATIFA NAYLA PRAHITO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005722', 'name' => 'LEONARD RICHARDO HETO MAREY', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003304', 'name' => 'LUNA SEKAR ARUM KUSUMA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000881', 'name' => 'MELCHIOR CLEMENS PITO HILARION SUBAN', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003546', 'name' => 'NADIA CHRISTY SINAGA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005478', 'name' => 'NAUFAL SAHLAN MAULANA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004323', 'name' => 'NAYYARA ARDINE SHAFIQA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025001300', 'name' => 'PATRIANA ADALIA CHALIS', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025006091', 'name' => 'RAFAEL ANGELO TEGAR SUTANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005553', 'name' => 'RAFAEL GABRIEL SALINDEHO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004575', 'name' => 'RANGGA SATYA KUSNANDAR', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000733', 'name' => 'REYFAN AGUSTIAN', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003095', 'name' => 'ROSA VIRGINIA FILOMENA ROMEA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005029', 'name' => 'RYO COSTARICO PEREIRA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025005790', 'name' => 'STANISLAUS CARMEL FABIAN SIBARANI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004800', 'name' => 'STEFANUS WIDIRA CHRISTIANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025006965', 'name' => 'STELLA MARIS MARTINA VIANE KUKTEM', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025002704', 'name' => 'THERESIA DEVI SANTIKA SYLLA', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025000498', 'name' => 'VALENCIA SHAREEN LIUNARDI', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003229', 'name' => 'VINCENTIO CANIORA BERKAT', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025004347', 'name' => 'WILHELMUS GEORGE AGUNG PUTRANTO', 'tanggal_lahir' => '2026-01-01'],
        ['nim' => '12025003094', 'name' => 'YULIANI POETRI KARLINSYA BOI HENA', 'tanggal_lahir' => '2026-01-01'],
    ];

    public function run(): void
    {
        $university = University::query()->where('code', 'UAJ')->firstOrFail();

        $faculty = Faculty::query()->updateOrCreate(
            ['university_id' => $university->id, 'code' => 'TEK'],
            ['name' => 'Fakultas Teknik', 'is_active' => true],
        );

        $studyProgram = StudyProgram::query()->updateOrCreate(
            ['university_id' => $university->id, 'code' => 'TI1'],
            [
                'faculty_id' => $faculty->id,
                'name' => 'Teknik Informatika',
                'degree_level' => 'S1',
                'is_active' => true,
            ],
        );

        foreach (self::ROSTER as $entry) {
            $admissionYear = (int) substr($entry['nim'], 1, 4);

            Student::query()->updateOrCreate(
                ['university_id' => $university->id, 'nim' => $entry['nim']],
                [
                    'study_program_id' => $studyProgram->id,
                    'name' => Str::title($entry['name']),
                    'tanggal_lahir' => $entry['tanggal_lahir'],
                    'admission_year' => $admissionYear,
                    'status' => StudentStatus::Active,
                    'enrolled_at' => "{$admissionYear}-08-01",
                ],
            );
        }
    }
}
