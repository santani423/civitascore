<?php

namespace Modules\Finance\Support;

/**
 * Katalog permission KEU (rancangan KEU §4.3), format `resource.action`
 * dengan action standar PermissionAction. Dipakai route middleware,
 * RolePermissionSeeder, dan OrganizationalRoleSeeder.
 */
final class KeuPermission
{
    /** @var array<string, array<int, string>> */
    public const MAP = [
        'finance_dashboard' => ['read'],
        'fee_components' => ['read', 'create', 'update', 'delete'],
        'fee_rates' => ['read', 'create', 'update', 'approve'],
        'invoices' => ['read', 'create', 'update', 'export'],
        'billing_runs' => ['read', 'create', 'approve'],
        'invoice_adjustments' => ['read', 'create', 'approve'],
        'installments' => ['read', 'create', 'update', 'approve'],
        'penalties' => ['read', 'create', 'update', 'approve'],
        'financial_aid' => ['read', 'create', 'update', 'approve'],
        'vouchers' => ['read', 'create', 'update', 'delete'],
        'ukt' => ['read', 'create', 'update', 'approve'],
        'payment_channels' => ['read', 'update'],
        'payment_transactions' => ['read', 'create', 'update', 'approve'],
        'cashier_sessions' => ['read', 'create', 'approve'],
        'reconciliations' => ['read', 'create', 'update', 'finalize'],
        'refunds' => ['read', 'create', 'approve', 'finalize'],
        'student_deposits' => ['read', 'create', 'update'],
        'finance_clearance' => ['read', 'create', 'update', 'approve'],
        'finance_reports' => ['read', 'export'],
        'finance_settings' => ['read', 'update'],
        'student_finance' => ['read', 'create'],
        'academic_blocks' => ['read'],
    ];

    /** @return array<int, string> */
    public static function all(): array
    {
        $slugs = [];

        foreach (self::MAP as $resource => $actions) {
            foreach ($actions as $action) {
                $slugs[] = "{$resource}.{$action}";
            }
        }

        return $slugs;
    }

    /** @return array<int, string> semua slug baca (tanpa layanan mandiri) */
    public static function reads(): array
    {
        return array_values(array_filter(self::all(), fn ($s) => str_ends_with($s, '.read') && ! str_starts_with($s, 'student_finance')));
    }

    /** @return array<string, array<int, string>> peran KEU (OrganizationalRoleSeeder) */
    public static function roles(): array
    {
        $reads = self::reads();
        $staff = [
            ...$reads, 'invoices.create', 'invoices.update', 'invoices.export', 'fee_components.create', 'fee_components.update', 'fee_rates.create', 'fee_rates.update',
            'billing_runs.create', 'invoice_adjustments.create', 'installments.create', 'installments.update', 'installments.approve', 'penalties.create', 'penalties.update',
            'financial_aid.create', 'financial_aid.update', 'vouchers.create', 'vouchers.update', 'vouchers.delete', 'ukt.create', 'ukt.update', 'ukt.approve',
            'payment_transactions.create', 'payment_transactions.update', 'payment_transactions.approve', 'cashier_sessions.create', 'cashier_sessions.approve',
            'reconciliations.create', 'reconciliations.update', 'reconciliations.finalize', 'refunds.create', 'refunds.finalize', 'student_deposits.create', 'student_deposits.update',
            'finance_clearance.create', 'finance_clearance.update', 'finance_reports.export',
        ];

        return [
            'finance_administrator' => $staff,
            'finance_manager' => self::adminSlugs(),
            'finance_cashier' => ['finance_dashboard.read', 'invoices.read', 'payment_transactions.read', 'payment_transactions.create', 'cashier_sessions.read', 'cashier_sessions.create', 'student_deposits.read'],
            'vice_rector' => [...$reads, 'finance_reports.export', 'fee_rates.approve', 'billing_runs.approve', 'invoice_adjustments.approve', 'penalties.approve', 'financial_aid.approve', 'refunds.approve', 'finance_clearance.approve'],
            'rector' => ['finance_dashboard.read', 'finance_reports.read', 'finance_reports.export'],
            'university_owner' => ['finance_dashboard.read', 'finance_reports.read'],
            'university_administrator' => ['finance_dashboard.read', 'finance_reports.read', 'payment_channels.read', 'payment_channels.update', 'finance_settings.read'],
            'auditor' => [...$reads, 'finance_reports.export'],
            'academic_administrator' => ['academic_blocks.read', 'finance_clearance.read'],
            'student' => ['student_finance.read', 'student_finance.create'],
        ];
    }

    /** @return array<int, string> */
    private static function adminSlugs(): array
    {
        return array_values(array_filter(self::all(), fn ($s) => ! str_starts_with($s, 'student_finance')));
    }
}
