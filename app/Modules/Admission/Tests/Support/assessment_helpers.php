<?php

// Helper bersama tes One Atma Talent (dimuat dengan require_once agar aman di run paralel).

use Modules\Admission\Tests\Support\PmbWorld;

if (function_exists('talentDraft')) {
    return;
}

/** Jenis minat dan versi draft; dimensi dan butir ditambahkan oleh pemanggil. */
function talentDraft(PmbWorld $w, ?int $durationMinutes = null, string $purpose = 'exploratory', string $code = 'MINAT'): array
{
    $admin = 'admission_administrator';
    $type = pmbOk($w->asStaff($admin, 'POST', 'assessment-types', ['code' => $code, 'name' => 'Minat RIASEC', 'domain' => 'interest']))->json('data.id');
    $version = pmbOk($w->asStaff($admin, 'POST', "assessment-types/{$type}/versions", [
        'title' => 'Minat & Bakat 2027', 'purpose' => $purpose, 'instructions' => 'Pilih jawaban yang paling sesuai.', 'duration_minutes' => $durationMinutes,
    ]))->json('data.id');

    return ['type' => $type, 'version' => $version];
}

/** Menambah enam dimensi RIASEC dan butir-butirnya. */
function talentFillInstrument(PmbWorld $w, string $version): array
{
    foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $code) {
        pmbOk($w->asStaff('admission_administrator', 'POST', "assessment-versions/{$version}/dimensions", [
            'code' => $code, 'name' => "Dimensi {$code}", 'description' => "Deskripsi {$code}", 'interpretation' => "Interpretasi {$code}",
        ]));
    }

    return talentAddItems($w, $version);
}

/** Dua butir per dimensi yang sudah ada; satu per dimensi bertanda terbalik. */
function talentAddItems(PmbWorld $w, string $version): array
{
    $admin = 'admission_administrator';
    $items = [];

    foreach (pmbOk($w->asStaff($admin, 'GET', "assessment-versions/{$version}"))->json('data.dimensions') as $dimension) {
        foreach ([false, true] as $reversed) {
            $id = pmbOk($w->asStaff($admin, 'POST', "assessment-versions/{$version}/items", [
                'dimension_id' => $dimension['id'], 'stem' => "Butir {$dimension['code']}".($reversed ? ' terbalik' : ''), 'is_reversed' => $reversed,
            ]))->json('data.id');
            $items[] = ['id' => $id, 'dimension' => $dimension['code'], 'reversed' => $reversed];
        }
    }

    return $items;
}

/**
 * Jawaban yang menghasilkan rata-rata sesuai target R 5, I 4, A 3, S 2, E 1, C 1 setelah
 * butir terbalik dibalik. Holland Code yang diharapkan: RIA (E dan C seri, E didahulukan).
 *
 * @return list<array{item_id: string, value: int}>
 */
function talentAnswers(array $items): array
{
    $targets = ['R' => 5, 'I' => 4, 'A' => 3, 'S' => 2, 'E' => 1, 'C' => 1];

    return array_map(fn (array $item): array => [
        'item_id' => $item['id'],
        'value' => $item['reversed'] ? 6 - $targets[$item['dimension']] : $targets[$item['dimension']],
    ], $items);
}

/**
 * Alur persetujuan dua pihak (docs/one-atma-talent/05 §3): penyusun mengajukan, penelaah
 * (administrator lain) menyetujui, lalu pejabat pengesah menyetujui. Penerbitan dilakukan terpisah.
 */
function talentApprove(PmbWorld $w, string $version): void
{
    pmbOk($w->asStaff('admission_administrator', 'POST', "assessment-versions/{$version}/submit", ['note' => 'Siap ditelaah']));

    $reviewer = $w->staffWithRole('admission_administrator');
    pmbOk($w->asStaff($reviewer, 'POST', "assessment-versions/{$version}/decision", ['decision' => 'approve']));

    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-versions/{$version}/decision", ['decision' => 'approve']));
}

/** Instrumen lengkap yang sudah disetujui dan diterbitkan oleh pejabat pengesah. */
function talentPublished(PmbWorld $w, ?int $durationMinutes = null, string $code = 'MINAT'): array
{
    $draft = talentDraft($w, $durationMinutes, code: $code);
    $items = talentFillInstrument($w, $draft['version']);
    talentApprove($w, $draft['version']);
    pmbOk($w->asStaff('admission_approver', 'POST', "assessment-versions/{$draft['version']}/publish"));

    return [...$draft, 'items' => $items];
}

/** Pendaftaran terkirim atas akun baru; mengembalikan token dan id pendaftaran. */
function talentApplicant(PmbWorld $w, array $cfg, string $email, float $score = 85): array
{
    $token = pmbApplicantToken($w, $email);

    return ['token' => $token, 'application' => pmbSubmitApplication($w, $token, $cfg['wavePath'], $score)];
}

function talentStart(PmbWorld $w, string $token, string $application, string $version): array
{
    return pmbOk($w->asApplicant($token, 'POST', "applications/{$application}/assessments/{$version}/sessions"))->json('data');
}
