<?php

use Modules\ApprovalWorkflow\Models\ApprovalWorkflow;
use Modules\ApprovalWorkflow\Support\WorkflowSelector;

test('an unconditional workflow matches any context', function () {
    $workflow = ApprovalWorkflow::factory()->create([
        'workflowable_type' => 'demo_type',
        'conditions' => null,
    ]);

    $resolved = (new WorkflowSelector)->selectFor('demo_type', ['anything' => 'goes']);

    expect($resolved?->id)->toBe($workflow->id);
});

test('a conditioned workflow only matches when every condition key equals the context value', function () {
    ApprovalWorkflow::factory()->create([
        'workflowable_type' => 'demo_type',
        'conditions' => ['faculty_slug' => 'ilmu-komputer'],
    ]);

    $noMatch = (new WorkflowSelector)->selectFor('demo_type', ['faculty_slug' => 'ekonomi']);
    $match = (new WorkflowSelector)->selectFor('demo_type', ['faculty_slug' => 'ilmu-komputer']);

    expect($noMatch)->toBeNull();
    expect($match)->not->toBeNull();
});

test('an inactive workflow is never selected', function () {
    ApprovalWorkflow::factory()->create(['workflowable_type' => 'demo_type', 'is_active' => false]);

    $resolved = (new WorkflowSelector)->selectFor('demo_type');

    expect($resolved)->toBeNull();
});
