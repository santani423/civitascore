<?php

namespace Modules\Admission\Support;

/**
 * Katalog permission PMB (docs/pmb/08 §1). Slug mengikuti konvensi
 * `resource.action` CivitasOne dengan action dari enum PermissionAction,
 * sehingga nama fungsional di dokumen dipetakan sebagai berikut (kolom kiri
 * = nama dokumen):
 *
 *   pmb_config.manage → pmb_config.update      pmb_verification.decide   → pmb_verification.approve
 *   pmb_fees.manage   → pmb_fees.update        pmb_verification.assign   → pmb_verification.update
 *   pmb_content.manage → pmb_content.update    pmb_verification.override → pmb_verification.finalize
 *   pmb_applications.correct → pmb_applications.update
 *   pmb_applications.assisted_create → pmb_applications.create
 *   pmb_documents.download → pmb_documents.read
 *   pmb_payments.verify_manual → pmb_payments.approve, pmb_payments.reconcile → pmb_payments.finalize
 *   pmb_refunds.request → pmb_refunds.create
 *   pmb_exams.manage → pmb_exams.update, pmb_exams.attendance → pmb_exam_attendance.update
 *   pmb_scores.input → pmb_scores.create, pmb_scores.lock → pmb_scores.finalize
 *   pmb_selection.manage → pmb_selection.update, .lock_scheme → .finalize, .submit → .publish
 *   pmb_results.read_unpublished → pmb_results.read, pmb_results.correct → pmb_results.update
 *   pmb_waitlist.promote → pmb_waitlist.update
 *   pmb_reregistration.decide → .approve, pmb_reregistration.extend → .update
 *   pmb_conversion.retry → pmb_conversion.update, pmb_nim.configure → pmb_nim.update
 *   pmb_communication.broadcast → .publish, pmb_communication.chat → .update
 */
final class PmbPermission
{
    public const CONFIG_READ = 'pmb_config.read';

    public const CONFIG_MANAGE = 'pmb_config.update';

    public const CONFIG_PUBLISH = 'pmb_config.publish';

    public const FEES_MANAGE = 'pmb_fees.update';

    public const CONTENT_MANAGE = 'pmb_content.update';

    public const PROGRAM_PROFILE_MANAGE = 'pmb_program_profiles.update';

    public const APPLICATIONS_READ = 'pmb_applications.read';

    public const APPLICATIONS_CORRECT = 'pmb_applications.update';

    public const APPLICATIONS_ASSISTED_CREATE = 'pmb_applications.create';

    public const SENSITIVE_READ = 'pmb_sensitive.read';

    public const DOCUMENTS_DOWNLOAD = 'pmb_documents.read';

    public const VERIFICATION_DECIDE = 'pmb_verification.approve';

    public const VERIFICATION_ASSIGN = 'pmb_verification.update';

    public const VERIFICATION_OVERRIDE = 'pmb_verification.finalize';

    public const PAYMENTS_READ = 'pmb_payments.read';

    public const PAYMENTS_VERIFY_MANUAL = 'pmb_payments.approve';

    public const PAYMENTS_RECONCILE = 'pmb_payments.finalize';

    public const REFUNDS_REQUEST = 'pmb_refunds.create';

    public const REFUNDS_APPROVE = 'pmb_refunds.approve';

    public const EXAMS_MANAGE = 'pmb_exams.update';

    public const EXAMS_ATTENDANCE = 'pmb_exam_attendance.update';

    public const SCORES_INPUT = 'pmb_scores.create';

    public const SCORES_IMPORT = 'pmb_scores.import';

    public const SCORES_LOCK = 'pmb_scores.finalize';

    public const SELECTION_MANAGE = 'pmb_selection.update';

    public const SELECTION_LOCK_SCHEME = 'pmb_selection.finalize';

    public const SELECTION_SUBMIT = 'pmb_selection.publish';

    public const RESULTS_APPROVE = 'pmb_results.approve';

    public const RESULTS_READ_UNPUBLISHED = 'pmb_results.read';

    public const RESULTS_CORRECT = 'pmb_results.update';

    public const WAITLIST_PROMOTE = 'pmb_waitlist.update';

    public const REREG_READ = 'pmb_reregistration.read';

    public const REREG_DECIDE = 'pmb_reregistration.approve';

    public const REREG_EXTEND = 'pmb_reregistration.update';

    public const CONVERSION_READ = 'pmb_conversion.read';

    public const CONVERSION_RETRY = 'pmb_conversion.update';

    public const NIM_CONFIGURE = 'pmb_nim.update';

    public const REPORTS_READ = 'pmb_reports.read';

    public const REPORTS_EXPORT = 'pmb_reports.export';

    public const COMMUNICATION_BROADCAST = 'pmb_communication.publish';

    public const COMMUNICATION_CHAT = 'pmb_communication.update';

    public const AUDIT_READ = 'pmb_audit.read';

    /**
     * resource => actions, dipakai RolePermissionSeeder.
     *
     * @var array<string, array<int, string>>
     */
    public const MAP = [
        'pmb_config' => ['read', 'update', 'publish'],
        'pmb_fees' => ['update'],
        'pmb_content' => ['update'],
        'pmb_program_profiles' => ['update'],
        'pmb_applications' => ['read', 'update', 'create'],
        'pmb_sensitive' => ['read'],
        'pmb_documents' => ['read'],
        'pmb_verification' => ['approve', 'update', 'finalize'],
        'pmb_payments' => ['read', 'approve', 'finalize'],
        'pmb_refunds' => ['create', 'approve'],
        'pmb_exams' => ['update'],
        'pmb_exam_attendance' => ['update'],
        'pmb_scores' => ['create', 'import', 'finalize'],
        'pmb_selection' => ['update', 'finalize', 'publish'],
        'pmb_results' => ['approve', 'read', 'update'],
        'pmb_waitlist' => ['update'],
        'pmb_reregistration' => ['read', 'approve', 'update'],
        'pmb_conversion' => ['read', 'update'],
        'pmb_nim' => ['update'],
        'pmb_reports' => ['read', 'export'],
        'pmb_communication' => ['publish', 'update'],
        'pmb_audit' => ['read'],
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
}
