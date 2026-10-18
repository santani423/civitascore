<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Modules\Academic\Database\Seeders\AtmaJayaQuestionBankSeeder;
use Modules\Academic\Models\Course;
use Modules\Academic\Models\QuestionBankItem;
use Modules\Academic\Models\QuestionBankItemOption;
use Modules\Tenancy\Models\University;

beforeEach(function (): void {
    Storage::fake(config('pmb.storage_disk', 'local'));
});

test('every question has four options and a valid answer key', function (): void {
    expect(array_keys(AtmaJayaQuestionBankSeeder::QUESTIONS))->toBe(['SFT 206', 'SFT 303']);

    foreach (AtmaJayaQuestionBankSeeder::QUESTIONS as $questions) {
        $texts = array_column($questions, 'text');

        expect($questions)->toHaveCount(25)
            ->and(array_unique($texts))->toHaveCount(25);

        foreach ($questions as $question) {
            expect($question['options'])->toHaveCount(4)
                ->and($question['correct'])->toBeGreaterThanOrEqual(0)->toBeLessThan(4);
        }
    }
});

test('DatabaseSeeder fills the Bank Soal per course with exactly one correct option and is idempotent', function (): void {
    $this->seed(DatabaseSeeder::class);
    (new AtmaJayaQuestionBankSeeder())->run();

    $uaj = University::query()->where('code', 'UAJ')->firstOrFail();

    foreach (AtmaJayaQuestionBankSeeder::QUESTIONS as $courseCode => $questions) {
        $course = Course::query()->withoutGlobalScopes()
            ->where('university_id', $uaj->id)->where('code', $courseCode)->firstOrFail();

        $items = QuestionBankItem::query()->withoutGlobalScopes()
            ->where('course_id', $course->id)->get()->keyBy('question_text');

        expect($items)->toHaveCount(count($questions));

        foreach ($questions as $question) {
            $options = QuestionBankItemOption::query()->withoutGlobalScopes()
                ->where('question_bank_item_id', $items[$question['text']]->id)
                ->orderBy('order_index')->get();

            expect($options->pluck('option_text')->all())->toBe($question['options'])
                ->and($options->where('is_correct', true)->pluck('order_index')->all())->toBe([$question['correct']]);
        }
    }
});
