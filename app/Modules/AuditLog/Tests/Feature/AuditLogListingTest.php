<?php

use Modules\AuditLog\Models\AuditLog;

test('listing audit logs requires the audit_logs.read permission', function () {
    actingAsUserWithPermissions([]);

    $response = $this->getJson('/api/v1/audit-logs');

    $response->assertApiError(403);
});

test('a permitted user can list and paginate audit logs', function () {
    actingAsUserWithPermissions(['audit_logs.read']);

    AuditLog::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/audit-logs?per_page=2');

    $response->assertApiSuccess();
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(3);
});
