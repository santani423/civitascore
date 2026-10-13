<?php

use Illuminate\Support\Facades\Route;
use Modules\Admission\Controllers\Admin;
use Modules\Admission\Controllers\Applicant;
use Modules\Admission\Controllers\FileDownloadController;
use Modules\Admission\Controllers\IntegrationController;
use Modules\Admission\Controllers\PublicSiteController;
use Modules\Admission\Controllers\WebhookController;
use Modules\Admission\Support\PmbPermission as P;

/*
| Modul PMB (Admission) — docs/pmb/11. Grup & batas laju 11 §A.2:
|   /pmb/public       publik, tenant dari domain / X-University-ID / X-University-Code
|   /pmb/applicant    portal pendaftar (guard token PmbApplicantAccount, tenant diikat akun)
|   /pmb/admin        staf (users) + permission + scope + tenant.access
|   /pmb/webhooks     tanda tangan penyedia
|   /pmb/integrations token klien ber-ability
| Route model binding TIDAK dipakai di portal pendaftar: record selalu dicari
| lewat relasi akun (kepemilikan) setelah tenant diikat EnsureApplicant.
*/

Route::prefix('pmb')->group(function (): void {
    // ------------------------------------------------------------------ Unduhan bertanda tangan
    Route::get('files/{file}', FileDownloadController::class)->name('pmb.files.download')->middleware('signed');

    // ------------------------------------------------------------------ Publik
    Route::prefix('public')->middleware(['pmb.public', 'throttle:pmb-public'])->group(function (): void {
        Route::get('site', [PublicSiteController::class, 'site']);
        Route::get('periods/active', [PublicSiteController::class, 'activePeriods']);
        Route::get('open-wave-paths', [PublicSiteController::class, 'openWavePaths']);
        Route::get('programs', [PublicSiteController::class, 'programs']);
        Route::get('programs/{slug}', [PublicSiteController::class, 'program']);
        Route::get('paths', [PublicSiteController::class, 'paths']);
        Route::get('paths/{code}', [PublicSiteController::class, 'path']);
        Route::get('fees', [PublicSiteController::class, 'fees']);
        Route::get('contents', [PublicSiteController::class, 'contents']);
        Route::get('contents/{type}/{slug}', [PublicSiteController::class, 'content']);
        Route::get('sitemap.xml', [PublicSiteController::class, 'sitemap']);
        Route::get('robots.txt', [PublicSiteController::class, 'robots']);
        Route::get('loa/{code}', [PublicSiteController::class, 'verifyLetter']);
        Route::post('status-check', [PublicSiteController::class, 'statusCheck'])->middleware('throttle:pmb-status-check');
    });

    // ------------------------------------------------------------------ Autentikasi pendaftar
    Route::prefix('applicant/auth')->middleware(['pmb.public'])->group(function (): void {
        Route::post('register', [Applicant\AuthController::class, 'register'])->middleware('throttle:pmb-register');
        Route::post('email/verify', [Applicant\AuthController::class, 'verifyEmail'])->middleware('throttle:pmb-login');
        Route::post('login', [Applicant\AuthController::class, 'login'])->middleware('throttle:pmb-login');
        Route::post('password/forgot', [Applicant\AuthController::class, 'forgotPassword'])->middleware('throttle:pmb-otp');
        Route::post('password/reset', [Applicant\AuthController::class, 'resetPassword'])->middleware('throttle:pmb-login');
        Route::post('otp/send', [Applicant\AuthController::class, 'sendOtp'])->middleware('throttle:pmb-otp');
        Route::post('otp/verify', [Applicant\AuthController::class, 'verifyOtp'])->middleware('throttle:pmb-login');
        Route::post('email-change/confirm', [Applicant\AuthController::class, 'confirmEmailChange'])->middleware('throttle:pmb-login');
        Route::post('recovery-requests', [Applicant\AuthController::class, 'recoveryRequest'])->middleware('throttle:pmb-register');
    });

    Route::prefix('applicant')->middleware(['auth:sanctum', 'pmb.applicant', 'throttle:pmb-applicant'])->group(function (): void {
        Route::post('auth/email/resend', [Applicant\AuthController::class, 'resendVerification'])->middleware('throttle:pmb-otp');
        Route::post('auth/logout', [Applicant\AuthController::class, 'logout']);
        Route::post('auth/logout-all', [Applicant\AuthController::class, 'logoutAll']);
        Route::get('auth/sessions', [Applicant\AuthController::class, 'sessions']);
        Route::delete('auth/sessions/{tokenId}', [Applicant\AuthController::class, 'revokeSession']);

        Route::get('me', [Applicant\ProfileController::class, 'me']);
        Route::patch('me', [Applicant\ProfileController::class, 'updateMe']);
        Route::put('me/password', [Applicant\ProfileController::class, 'changePassword']);
        Route::post('me/email-change', [Applicant\ProfileController::class, 'requestEmailChange']);
        Route::put('me/marketing-consent', [Applicant\ProfileController::class, 'marketingConsent']);
        Route::get('me/data-export', [Applicant\ProfileController::class, 'dataExport']);
        Route::post('me/deactivate', [Applicant\ProfileController::class, 'deactivate']);
        Route::get('profile', [Applicant\ProfileController::class, 'profile']);
        Route::put('profile', [Applicant\ProfileController::class, 'saveProfile']);
        Route::post('profile/identity-claim', [Applicant\ProfileController::class, 'claimIdentity']);
        Route::get('educations', [Applicant\ProfileController::class, 'educations']);
        Route::post('educations', [Applicant\ProfileController::class, 'storeEducation']);
        Route::put('educations/{id}', [Applicant\ProfileController::class, 'updateEducation']);
        Route::delete('educations/{id}', [Applicant\ProfileController::class, 'destroyEducation']);
        Route::put('educations/{id}/report-scores', [Applicant\ProfileController::class, 'reportScores']);
        Route::get('guardians', [Applicant\ProfileController::class, 'guardians']);
        Route::post('guardians', [Applicant\ProfileController::class, 'storeGuardian']);
        Route::put('guardians/{id}', [Applicant\ProfileController::class, 'updateGuardian']);
        Route::delete('guardians/{id}', [Applicant\ProfileController::class, 'destroyGuardian']);
        Route::get('achievements', [Applicant\ProfileController::class, 'achievements']);
        Route::post('achievements', [Applicant\ProfileController::class, 'storeAchievement']);
        Route::put('achievements/{id}', [Applicant\ProfileController::class, 'updateAchievement']);
        Route::delete('achievements/{id}', [Applicant\ProfileController::class, 'destroyAchievement']);
        Route::get('notifications', [Applicant\ProfileController::class, 'notifications']);
        Route::post('notifications/{id}/read', [Applicant\ProfileController::class, 'readNotification']);

        Route::get('open-wave-paths', [Applicant\ApplicationController::class, 'openWavePaths']);
        Route::get('applications', [Applicant\ApplicationController::class, 'index']);
        Route::post('applications', [Applicant\ApplicationController::class, 'store'])->middleware(['pmb.applicant:verified', 'pmb.idempotent']);
        Route::get('applications/{id}', [Applicant\ApplicationController::class, 'show']);
        Route::patch('applications/{id}', [Applicant\ApplicationController::class, 'saveSection']);
        Route::put('applications/{id}/choices', [Applicant\ApplicationController::class, 'saveChoices']);
        Route::put('applications/{id}/answers', [Applicant\ApplicationController::class, 'saveAnswers']);
        Route::get('applications/{id}/completeness', [Applicant\ApplicationController::class, 'completeness']);
        Route::post('applications/{id}/submit', [Applicant\ApplicationController::class, 'submit'])->middleware('pmb.idempotent');
        Route::post('applications/{id}/withdraw', [Applicant\ApplicationController::class, 'withdraw']);
        Route::delete('applications/{id}', [Applicant\ApplicationController::class, 'destroy']);
        Route::get('applications/{id}/notes', [Applicant\ApplicationController::class, 'notes']);

        Route::get('applications/{id}/documents', [Applicant\DocumentController::class, 'index']);
        Route::post('applications/{id}/documents/slots', [Applicant\DocumentController::class, 'addSlot']);
        Route::post('applications/{id}/documents/{documentId}/versions', [Applicant\DocumentController::class, 'upload'])->middleware('throttle:pmb-upload');
        Route::get('applications/{id}/documents/{documentId}/download', [Applicant\DocumentController::class, 'download']);

        Route::get('payment-channels', [Applicant\PaymentController::class, 'channels']);
        Route::get('applications/{id}/invoices', [Applicant\PaymentController::class, 'invoices']);
        Route::post('invoices/{invoiceId}/voucher', [Applicant\PaymentController::class, 'applyVoucher'])->middleware('pmb.idempotent');
        Route::post('invoices/{invoiceId}/transactions', [Applicant\PaymentController::class, 'createTransaction'])->middleware('pmb.idempotent');
        Route::get('invoices/{invoiceId}/receipt', [Applicant\PaymentController::class, 'receipt']);
        Route::get('transactions/{transactionId}', [Applicant\PaymentController::class, 'transaction']);
        Route::post('transactions/{transactionId}/cancel', [Applicant\PaymentController::class, 'cancel']);
        Route::post('transactions/{transactionId}/manual-proof', [Applicant\PaymentController::class, 'manualProof'])->middleware(['pmb.idempotent', 'throttle:pmb-upload']);

        Route::get('applications/{id}/exams', [Applicant\ExamController::class, 'index']);
        Route::get('exam-participants/{participantId}/card', [Applicant\ExamController::class, 'card']);
        Route::post('exam-participants/{participantId}/reschedule-requests', [Applicant\ExamController::class, 'reschedule']);
        Route::post('exam-participants/{participantId}/cbt/start', [Applicant\ExamController::class, 'cbtStart']);
        Route::put('exam-participants/{participantId}/cbt/answers', [Applicant\ExamController::class, 'cbtAnswer']);
        Route::post('exam-participants/{participantId}/cbt/violations', [Applicant\ExamController::class, 'cbtViolation']);
        Route::post('exam-participants/{participantId}/cbt/submit', [Applicant\ExamController::class, 'cbtSubmit']);

        Route::get('applications/{id}/result', [Applicant\ResultController::class, 'result']);
        Route::get('applications/{id}/loa', [Applicant\ResultController::class, 'letter']);
        Route::get('applications/{id}/re-registration', [Applicant\ReRegistrationController::class, 'show']);
        Route::post('applications/{id}/re-registration/accept', [Applicant\ReRegistrationController::class, 'accept'])->middleware('pmb.idempotent');
        Route::post('applications/{id}/re-registration/decline', [Applicant\ReRegistrationController::class, 'decline'])->middleware('pmb.idempotent');
        Route::put('applications/{id}/re-registration/data', [Applicant\ReRegistrationController::class, 'saveData']);
        Route::post('applications/{id}/re-registration/submit', [Applicant\ReRegistrationController::class, 'submit']);
        Route::get('applications/{id}/re-registration/proof', [Applicant\ReRegistrationController::class, 'proof']);

        Route::get('chat-threads', [Applicant\ChatController::class, 'index']);
        Route::post('chat-threads', [Applicant\ChatController::class, 'store']);
        Route::get('chat-threads/{threadId}/messages', [Applicant\ChatController::class, 'messages']);
        Route::post('chat-threads/{threadId}/messages', [Applicant\ChatController::class, 'reply']);
    });

    // ------------------------------------------------------------------ Admin
    Route::prefix('admin')->middleware(['auth:sanctum', 'pmb.staff', 'tenant.access', 'throttle:pmb-admin'])->group(function (): void {
        // Konfigurasi (11 §A.9)
        Route::middleware('permission:'.P::CONFIG_READ)->group(function (): void {
            Route::get('periods', [Admin\ConfigurationController::class, 'periods']);
            Route::get('periods/{period}', [Admin\ConfigurationController::class, 'period']);
            Route::get('periods/{period}/publish-checklist', [Admin\ConfigurationController::class, 'publishChecklist']);
            Route::get('periods/{period}/waves', [Admin\ConfigurationController::class, 'waves']);
            Route::get('waves/{wave}/paths', [Admin\ConfigurationController::class, 'wavePaths']);
            Route::get('admission-paths', [Admin\ConfigurationController::class, 'admissionPaths']);
            Route::get('study-systems', [Admin\ConfigurationController::class, 'studySystems']);
            Route::get('periods/{period}/quotas', [Admin\ConfigurationController::class, 'quotas']);
            Route::get('document-types', [Admin\ConfigurationController::class, 'documentTypes']);
            Route::get('wave-paths/{wavePath}/document-requirements', [Admin\ConfigurationController::class, 'documentRequirements']);
            Route::get('periods/{period}/fee-components', [Admin\ConfigurationController::class, 'feeComponents']);
            Route::get('form-templates', [Admin\FormTemplateController::class, 'index']);
            Route::get('form-templates/{formTemplate}', [Admin\FormTemplateController::class, 'show']);
            Route::get('form-templates/{formTemplate}/preview', [Admin\FormTemplateController::class, 'preview']);
            Route::get('selection-schemes', [Admin\SelectionSchemeController::class, 'index']);
            Route::get('selection-schemes/{selectionScheme}', [Admin\SelectionSchemeController::class, 'show']);
            Route::get('program-profiles', [Admin\ContentController::class, 'programProfiles']);
            Route::get('contents', [Admin\ContentController::class, 'index']);
            Route::get('contents/{content}', [Admin\ContentController::class, 'show']);
            Route::get('settings', [Admin\SettingsController::class, 'show']);
            Route::get('nim-settings', [Admin\SettingsController::class, 'nim']);
            Route::get('vouchers', [Admin\SettingsController::class, 'vouchers']);
            Route::get('lookups', [Admin\ConfigurationController::class, 'lookups']);
        });

        Route::middleware('permission:'.P::CONFIG_MANAGE)->group(function (): void {
            Route::post('periods', [Admin\ConfigurationController::class, 'storePeriod']);
            Route::patch('periods/{period}', [Admin\ConfigurationController::class, 'updatePeriod']);
            Route::delete('periods/{period}', [Admin\ConfigurationController::class, 'destroyPeriod']);
            Route::post('periods/{period}/close', [Admin\ConfigurationController::class, 'closePeriod']);
            Route::post('periods/{period}/archive', [Admin\ConfigurationController::class, 'archivePeriod']);
            Route::post('periods/{period}/waves', [Admin\ConfigurationController::class, 'storeWave']);
            Route::patch('waves/{wave}', [Admin\ConfigurationController::class, 'updateWave']);
            Route::delete('waves/{wave}', [Admin\ConfigurationController::class, 'destroyWave']);
            Route::patch('waves/{wave}/extend', [Admin\ConfigurationController::class, 'extendWave']);
            Route::post('waves/{wave}/paths', [Admin\ConfigurationController::class, 'storeWavePath']);
            Route::patch('wave-paths/{wavePath}', [Admin\ConfigurationController::class, 'updateWavePath']);
            Route::delete('wave-paths/{wavePath}', [Admin\ConfigurationController::class, 'destroyWavePath']);
            Route::patch('wave-paths/{wavePath}/toggle-open', [Admin\ConfigurationController::class, 'toggleWavePath']);
            Route::post('admission-paths', [Admin\ConfigurationController::class, 'storeAdmissionPath']);
            Route::patch('admission-paths/{admissionPath}', [Admin\ConfigurationController::class, 'updateAdmissionPath']);
            Route::delete('admission-paths/{admissionPath}', [Admin\ConfigurationController::class, 'destroyAdmissionPath']);
            Route::post('study-systems', [Admin\ConfigurationController::class, 'storeStudySystem']);
            Route::patch('study-systems/{studySystem}', [Admin\ConfigurationController::class, 'updateStudySystem']);
            Route::delete('study-systems/{studySystem}', [Admin\ConfigurationController::class, 'destroyStudySystem']);
            Route::put('periods/{period}/quotas/bulk', [Admin\ConfigurationController::class, 'upsertQuotas']);
            Route::post('periods/{period}/quotas/copy-from/{source}', [Admin\ConfigurationController::class, 'copyQuotas']);
            Route::post('document-types', [Admin\ConfigurationController::class, 'storeDocumentType']);
            Route::patch('document-types/{documentType}', [Admin\ConfigurationController::class, 'updateDocumentType']);
            Route::delete('document-types/{documentType}', [Admin\ConfigurationController::class, 'destroyDocumentType']);
            Route::put('wave-paths/{wavePath}/document-requirements/bulk', [Admin\ConfigurationController::class, 'syncDocumentRequirements']);
            Route::post('form-templates', [Admin\FormTemplateController::class, 'store']);
            Route::patch('form-templates/{formTemplate}', [Admin\FormTemplateController::class, 'update']);
            Route::post('form-templates/{formTemplate}/fields', [Admin\FormTemplateController::class, 'storeField']);
            Route::patch('form-templates/{formTemplate}/fields/{formField}', [Admin\FormTemplateController::class, 'updateField']);
            Route::delete('form-templates/{formTemplate}/fields/{formField}', [Admin\FormTemplateController::class, 'destroyField']);
            Route::post('form-templates/{formTemplate}/new-version', [Admin\FormTemplateController::class, 'newVersion']);
            Route::post('form-templates/{formTemplate}/retire', [Admin\FormTemplateController::class, 'retire']);
            Route::put('settings', [Admin\SettingsController::class, 'update']);
            Route::post('vouchers', [Admin\SettingsController::class, 'storeVoucher']);
            Route::patch('vouchers/{voucher}', [Admin\SettingsController::class, 'updateVoucher']);
            Route::delete('vouchers/{voucher}', [Admin\SettingsController::class, 'destroyVoucher']);
            Route::get('notification-templates', [Admin\CommunicationController::class, 'templates']);
            Route::put('notification-templates', [Admin\CommunicationController::class, 'saveTemplate']);
            Route::post('notification-templates/test', [Admin\CommunicationController::class, 'testTemplate']);
            Route::patch('announcement-batches/{announcementBatch}', [Admin\AnnouncementController::class, 'rescheduleBatch']);
            Route::post('announcement-batches/{announcementBatch}/cancel', [Admin\AnnouncementController::class, 'cancelBatch']);
            Route::post('selection-runs/{selectionRun}/announcement-batches', [Admin\AnnouncementController::class, 'scheduleBatch']);
        });

        Route::post('periods/{period}/publish', [Admin\ConfigurationController::class, 'publishPeriod'])->middleware('permission:'.P::CONFIG_PUBLISH);
        Route::post('form-templates/{formTemplate}/publish', [Admin\FormTemplateController::class, 'publish'])->middleware('permission:'.P::CONFIG_PUBLISH);
        Route::put('nim-settings', [Admin\SettingsController::class, 'updateNim'])->middleware('permission:'.P::NIM_CONFIGURE);
        Route::post('nim-settings/preview', [Admin\SettingsController::class, 'previewNim'])->middleware('permission:'.P::NIM_CONFIGURE);

        Route::middleware('permission:'.P::FEES_MANAGE)->group(function (): void {
            Route::put('periods/{period}/fee-components/bulk', [Admin\ConfigurationController::class, 'upsertFees']);
            Route::post('periods/{period}/fee-components/copy-from/{source}', [Admin\ConfigurationController::class, 'copyFees']);
            Route::delete('fee-components/{feeComponent}', [Admin\ConfigurationController::class, 'destroyFee']);
        });

        Route::middleware('permission:'.P::SELECTION_MANAGE)->group(function (): void {
            Route::post('selection-schemes', [Admin\SelectionSchemeController::class, 'store']);
            Route::patch('selection-schemes/{selectionScheme}', [Admin\SelectionSchemeController::class, 'update']);
            Route::post('selection-schemes/{selectionScheme}/components', [Admin\SelectionSchemeController::class, 'storeComponent']);
            Route::patch('selection-schemes/{selectionScheme}/components/{selectionComponent}', [Admin\SelectionSchemeController::class, 'updateComponent']);
            Route::delete('selection-schemes/{selectionScheme}/components/{selectionComponent}', [Admin\SelectionSchemeController::class, 'destroyComponent']);
            Route::post('selection-schemes/{selectionScheme}/new-version', [Admin\SelectionSchemeController::class, 'newVersion']);
            Route::post('selection-schemes/{selectionScheme}/simulate', [Admin\SelectionSchemeController::class, 'simulate']);
        });
        Route::post('selection-schemes/{selectionScheme}/lock', [Admin\SelectionSchemeController::class, 'lock'])->middleware('permission:'.P::SELECTION_LOCK_SCHEME);

        Route::middleware('permission:'.P::PROGRAM_PROFILE_MANAGE)->group(function (): void {
            Route::put('program-profiles/{studyProgram}', [Admin\ContentController::class, 'saveProgramProfile']);
            Route::post('program-profiles/{studyProgram}/publish', [Admin\ContentController::class, 'publishProgramProfile']);
        });

        Route::middleware('permission:'.P::CONTENT_MANAGE)->group(function (): void {
            Route::post('contents', [Admin\ContentController::class, 'store']);
            Route::patch('contents/{content}', [Admin\ContentController::class, 'update']);
            Route::delete('contents/{content}', [Admin\ContentController::class, 'destroy']);
            Route::post('contents/{content}/publish', [Admin\ContentController::class, 'publish']);
            Route::post('contents/{content}/unpublish', [Admin\ContentController::class, 'unpublish']);
            Route::post('contents/{content}/new-version', [Admin\ContentController::class, 'newVersion']);
            Route::post('uploads/images', [Admin\ContentController::class, 'uploadImage']);
        });

        // Pendaftar & verifikasi (11 §A.8)
        Route::middleware('permission:'.P::APPLICATIONS_READ)->group(function (): void {
            Route::get('applications', [Admin\ApplicationController::class, 'index']);
            Route::get('applications/{application}', [Admin\ApplicationController::class, 'show']);
            Route::get('applications/{application}/timeline', [Admin\ApplicationController::class, 'timeline']);
            Route::get('applications/{application}/documents', [Admin\ApplicationController::class, 'documents']);
            Route::post('applications/{application}/notes', [Admin\ApplicationController::class, 'storeNote']);
            Route::get('decisions', [Admin\AnnouncementController::class, 'decisions']);
        });
        Route::post('applications/{application}/reveal', [Admin\ApplicationController::class, 'reveal'])->middleware('permission:'.P::SENSITIVE_READ);
        Route::patch('applications/{application}/corrections', [Admin\ApplicationController::class, 'correct'])->middleware('permission:'.P::APPLICATIONS_CORRECT);
        Route::put('applications/{application}/choices', [Admin\ApplicationController::class, 'changeChoices'])->middleware('permission:'.P::APPLICATIONS_CORRECT);
        Route::post('applicant-accounts/{applicantAccount}/suspension', [Admin\ApplicationController::class, 'suspend'])->middleware('permission:'.P::APPLICATIONS_CORRECT);
        Route::post('assisted-applications', [Admin\ApplicationController::class, 'assisted'])->middleware(['permission:'.P::APPLICATIONS_ASSISTED_CREATE, 'pmb.idempotent']);
        Route::get('document-versions/{documentVersion}/download', [Admin\VerificationController::class, 'downloadVersion'])->middleware('permission:'.P::DOCUMENTS_DOWNLOAD);
        Route::post('application-documents/{applicationDocument}/reviews', [Admin\VerificationController::class, 'reviewDocument'])->middleware('permission:'.P::VERIFICATION_DECIDE.','.P::REREG_DECIDE);

        Route::middleware('permission:'.P::VERIFICATION_DECIDE)->group(function (): void {
            Route::post('verification/claim-next', [Admin\VerificationController::class, 'claimNext']);
            Route::post('applications/{application}/claim', [Admin\VerificationController::class, 'claim']);
            Route::post('applications/{application}/release', [Admin\VerificationController::class, 'release']);
            Route::post('applications/{application}/verify', [Admin\VerificationController::class, 'verify']);
            Route::post('applications/{application}/request-revision', [Admin\VerificationController::class, 'requestRevision']);
            Route::post('applications/{application}/reject', [Admin\VerificationController::class, 'reject']);
            Route::post('applications/{application}/conflict-declarations', [Admin\VerificationController::class, 'declareConflict']);
            Route::post('verification/bulk-verify', [Admin\VerificationController::class, 'bulkVerify'])->middleware('pmb.idempotent');
            Route::post('verification/bulk-photos', [Admin\VerificationController::class, 'bulkPhotos']);
            Route::get('duplicate-flags', [Admin\VerificationController::class, 'duplicateFlags']);
            Route::get('verification/stats', [Admin\VerificationController::class, 'stats']);
        });
        Route::post('verification/assignments', [Admin\VerificationController::class, 'assign'])->middleware('permission:'.P::VERIFICATION_ASSIGN);
        Route::middleware('permission:'.P::VERIFICATION_OVERRIDE)->group(function (): void {
            Route::post('applications/{application}/reopen', [Admin\VerificationController::class, 'reopen']);
            Route::patch('duplicate-flags/{duplicateFlag}', [Admin\VerificationController::class, 'resolveFlag']);
            Route::get('recovery-requests', [Admin\VerificationController::class, 'recoveryRequests']);
            Route::post('recovery-requests/{accountRecoveryRequest}/decision', [Admin\VerificationController::class, 'decideRecovery']);
        });

        // Keuangan (11 §A.7)
        Route::middleware('permission:'.P::PAYMENTS_READ)->group(function (): void {
            Route::get('invoices', [Admin\FinanceController::class, 'invoices']);
            Route::get('invoices/{pmbInvoice}', [Admin\FinanceController::class, 'invoice']);
            Route::get('transactions', [Admin\FinanceController::class, 'transactions']);
            Route::get('transactions/{paymentTransaction}/callbacks', [Admin\FinanceController::class, 'callbacks']);
            Route::get('finance/stats', [Admin\FinanceController::class, 'stats']);
        });
        Route::middleware('permission:'.P::PAYMENTS_VERIFY_MANUAL)->group(function (): void {
            Route::get('manual-proofs', [Admin\FinanceController::class, 'manualProofs']);
            Route::get('manual-proofs/{manualPaymentProof}/file', [Admin\FinanceController::class, 'proofFile']);
            Route::post('manual-proofs/{manualPaymentProof}/decision', [Admin\FinanceController::class, 'decideProof'])->middleware('pmb.idempotent');
            Route::post('invoices/{pmbInvoice}/cashier-payments', [Admin\FinanceController::class, 'cashier'])->middleware('pmb.idempotent');
        });
        Route::middleware('permission:'.P::PAYMENTS_RECONCILE)->group(function (): void {
            Route::post('transactions/{paymentTransaction}/recheck', [Admin\FinanceController::class, 'recheck']);
            Route::post('invoices/{pmbInvoice}/resolve', [Admin\FinanceController::class, 'resolve']);
            Route::get('reconciliation-batches', [Admin\FinanceController::class, 'batches']);
            Route::post('reconciliation-batches', [Admin\FinanceController::class, 'storeBatch']);
            Route::get('reconciliation-batches/{reconciliationBatch}', [Admin\FinanceController::class, 'batch']);
            Route::post('reconciliation-batches/{reconciliationBatch}/import', [Admin\FinanceController::class, 'importStatement']);
            Route::patch('reconciliation-items/{reconciliationItem}', [Admin\FinanceController::class, 'resolveItem']);
            Route::post('reconciliation-batches/{reconciliationBatch}/close', [Admin\FinanceController::class, 'closeBatch']);
        });
        Route::middleware('permission:'.P::REFUNDS_REQUEST)->group(function (): void {
            Route::get('refunds', [Admin\FinanceController::class, 'refunds']);
            Route::post('refunds', [Admin\FinanceController::class, 'requestRefund'])->middleware('pmb.idempotent');
            Route::post('refunds/{refund}/submit', [Admin\FinanceController::class, 'submitDraftRefund']);
            Route::post('refunds/{refund}/complete', [Admin\FinanceController::class, 'completeRefund']);
            Route::post('wave-paths/{wavePath}/bulk-refunds', [Admin\FinanceController::class, 'bulkRefund']);
        });

        // Ujian (11 §A.10)
        Route::middleware('permission:'.P::EXAMS_MANAGE)->group(function (): void {
            Route::get('exam-venues', [Admin\ExamController::class, 'venues']);
            Route::post('exam-venues', [Admin\ExamController::class, 'storeVenue']);
            Route::patch('exam-venues/{examVenue}', [Admin\ExamController::class, 'updateVenue']);
            Route::delete('exam-venues/{examVenue}', [Admin\ExamController::class, 'destroyVenue']);
            Route::post('exam-venues/{examVenue}/rooms', [Admin\ExamController::class, 'storeRoom']);
            Route::patch('exam-rooms/{examRoom}', [Admin\ExamController::class, 'updateRoom']);
            Route::delete('exam-rooms/{examRoom}', [Admin\ExamController::class, 'destroyRoom']);
            Route::get('waves/{wave}/exam-schedules', [Admin\ExamController::class, 'schedules']);
            Route::post('waves/{wave}/exam-schedules', [Admin\ExamController::class, 'storeSchedule']);
            Route::get('exam-schedules/{examSchedule}', [Admin\ExamController::class, 'schedule']);
            Route::patch('exam-schedules/{examSchedule}', [Admin\ExamController::class, 'updateSchedule']);
            Route::post('exam-schedules/{examSchedule}/publish', [Admin\ExamController::class, 'publishSchedule']);
            Route::post('exam-schedules/{examSchedule}/cancel', [Admin\ExamController::class, 'cancelSchedule']);
            Route::post('exam-schedules/{examSchedule}/sessions', [Admin\ExamController::class, 'storeSession']);
            Route::patch('exam-sessions/{examSession}', [Admin\ExamController::class, 'updateSession']);
            Route::delete('exam-sessions/{examSession}', [Admin\ExamController::class, 'destroySession']);
            Route::post('exam-schedules/{examSchedule}/auto-assign', [Admin\ExamController::class, 'autoAssign']);
            Route::post('exam-schedules/{examSchedule}/auto-examiners', [Admin\ExamController::class, 'autoExaminers']);
            Route::post('exam-participants/{examParticipant}/move', [Admin\ExamController::class, 'move']);
            Route::put('exam-sessions/{examSession}/examiners', [Admin\ExamController::class, 'examiners']);
            Route::post('exam-sessions/{examSession}/bulk-reschedule', [Admin\ExamController::class, 'bulkReschedule']);
            Route::get('reschedule-requests', [Admin\ExamController::class, 'rescheduleRequests']);
            Route::post('reschedule-requests/{examRescheduleRequest}/decision', [Admin\ExamController::class, 'decideReschedule']);
            Route::get('exam-schedules/{examSchedule}/cbt-questions', [Admin\ExamController::class, 'cbtQuestions']);
            Route::post('exam-schedules/{examSchedule}/cbt-questions', [Admin\ExamController::class, 'storeCbtQuestion']);
            Route::patch('cbt-questions/{cbtQuestion}', [Admin\ExamController::class, 'updateCbtQuestion']);
            Route::delete('cbt-questions/{cbtQuestion}', [Admin\ExamController::class, 'destroyCbtQuestion']);
        });
        Route::middleware('permission:'.P::EXAMS_ATTENDANCE)->group(function (): void {
            Route::get('exam-sessions/{examSession}', [Admin\ExamController::class, 'session']);
            Route::post('exam-sessions/{examSession}/check-in', [Admin\ExamController::class, 'checkIn']);
            Route::get('exam-sessions/{examSession}/attendance-sheet', [Admin\ExamController::class, 'attendanceSheet']);
        });
        Route::middleware('permission:'.P::SCORES_INPUT.','.P::EXAMS_MANAGE)->group(function (): void {
            Route::get('examiner/sessions', [Admin\ExamController::class, 'mySessions']);
            Route::get('exam-sessions/{examSession}/scores', [Admin\ExamController::class, 'sessionScores']);
            Route::put('exam-sessions/{examSession}/scores', [Admin\ExamController::class, 'inputScores']);
        });
        Route::middleware('permission:'.P::SCORES_IMPORT)->group(function (): void {
            Route::post('score-imports', [Admin\SelectionController::class, 'validateImport']);
            Route::post('score-imports/{scoreImport}/commit', [Admin\SelectionController::class, 'commitImport']);
        });
        Route::post('exam-sessions/{examSession}/lock-scores', [Admin\ExamController::class, 'lockScores'])->middleware('permission:'.P::SCORES_LOCK);

        // Seleksi (11 §A.11)
        Route::middleware('permission:'.P::SELECTION_MANAGE)->group(function (): void {
            Route::get('wave-paths/{wavePath}/scores', [Admin\SelectionController::class, 'scores']);
            Route::patch('selection-scores/{selectionScore}', [Admin\SelectionController::class, 'correctScore']);
            Route::post('selection-runs', [Admin\SelectionController::class, 'storeRun']);
            Route::post('selection-runs/{selectionRun}/review', [Admin\SelectionController::class, 'review']);
            Route::patch('selection-run-results/{selectionRunResult}/override', [Admin\SelectionController::class, 'override']);
            Route::post('selection-runs/{selectionRun}/resolve-ties', [Admin\SelectionController::class, 'resolveTies']);
            Route::post('decisions/{admissionDecision}/scholarship-award', [Admin\AnnouncementController::class, 'scholarshipAward']);
        });
        Route::post('selection-runs/{selectionRun}/submit', [Admin\SelectionController::class, 'submit'])->middleware('permission:'.P::SELECTION_SUBMIT);
        Route::middleware('permission:'.P::RESULTS_READ_UNPUBLISHED)->group(function (): void {
            Route::get('selection-runs', [Admin\SelectionController::class, 'runs']);
            Route::get('selection-runs/{selectionRun}', [Admin\SelectionController::class, 'run']);
            Route::get('selection-runs/{selectionRun}/results', [Admin\SelectionController::class, 'results']);
            Route::get('announcement-batches', [Admin\AnnouncementController::class, 'batches']);
        });
        Route::middleware('permission:'.P::RESULTS_CORRECT)->group(function (): void {
            Route::post('score-corrections', [Admin\SelectionController::class, 'requestScoreCorrection']);
            Route::post('result-corrections', [Admin\AnnouncementController::class, 'requestCorrection']);
            Route::post('re-registrations/{reRegistration}/cancel', [Admin\ReRegistrationController::class, 'cancel']);
            Route::get('change-requests', [Admin\AnnouncementController::class, 'changeRequests']);
        });
        Route::middleware('permission:'.P::WAITLIST_PROMOTE)->group(function (): void {
            Route::get('periods/{period}/seats', [Admin\AnnouncementController::class, 'seats']);
            Route::post('quotas/{quota}/promote-waitlist', [Admin\AnnouncementController::class, 'promote'])->middleware('pmb.idempotent');
        });

        // Daftar ulang & konversi (11 §A.13–A.14)
        Route::middleware('permission:'.P::REREG_READ)->group(function (): void {
            Route::get('re-registrations', [Admin\ReRegistrationController::class, 'index']);
            Route::get('re-registrations/{reRegistration}', [Admin\ReRegistrationController::class, 'show']);
        });
        Route::middleware('permission:'.P::REREG_DECIDE)->group(function (): void {
            Route::post('re-registrations/{reRegistration}/checks/{code}', [Admin\ReRegistrationController::class, 'markCheck']);
            Route::post('re-registrations/{reRegistration}/request-revision', [Admin\ReRegistrationController::class, 'requestRevision']);
            Route::post('re-registrations/{reRegistration}/approve', [Admin\ReRegistrationController::class, 'approve'])->middleware('pmb.idempotent');
        });
        Route::middleware('permission:'.P::REREG_EXTEND)->group(function (): void {
            Route::post('re-registrations/{reRegistration}/extend', [Admin\ReRegistrationController::class, 'extend']);
            Route::post('re-registrations/{reRegistration}/restore', [Admin\ReRegistrationController::class, 'restore']);
            Route::post('re-registrations/bulk-extensions', [Admin\ReRegistrationController::class, 'bulkExtension']);
        });
        Route::middleware('permission:'.P::CONVERSION_READ)->group(function (): void {
            Route::get('conversions', [Admin\ReRegistrationController::class, 'conversions']);
            Route::get('conversions/{studentConversion}', [Admin\ReRegistrationController::class, 'conversion']);
        });
        Route::post('conversions/{studentConversion}/retry', [Admin\ReRegistrationController::class, 'retry'])->middleware('permission:'.P::CONVERSION_RETRY);

        // Laporan (11 §A.15)
        Route::middleware('permission:'.P::REPORTS_READ)->group(function (): void {
            Route::get('dashboard/summary', [Admin\ReportController::class, 'summary']);
            Route::get('dashboard/trend', [Admin\ReportController::class, 'trend']);
            Route::get('dashboard/comparison', [Admin\ReportController::class, 'comparison']);
            Route::get('reports/{code}', [Admin\ReportController::class, 'report']);
            Route::get('exports', [Admin\ReportController::class, 'myExports']);
            Route::get('exports/{exportLog}', [Admin\ReportController::class, 'export']);
        });
        Route::post('reports/{code}/exports', [Admin\ReportController::class, 'requestExport'])->middleware(['permission:'.P::REPORTS_EXPORT, 'throttle:pmb-export']);

        // Komunikasi (11 §A.16)
        Route::middleware('permission:'.P::COMMUNICATION_BROADCAST)->group(function (): void {
            Route::get('broadcasts', [Admin\CommunicationController::class, 'broadcasts']);
            Route::post('broadcasts', [Admin\CommunicationController::class, 'storeBroadcast']);
            Route::patch('broadcasts/{broadcast}', [Admin\CommunicationController::class, 'updateBroadcast']);
            Route::post('broadcasts/{broadcast}/preview-audience', [Admin\CommunicationController::class, 'previewAudience']);
            Route::post('broadcasts/{broadcast}/schedule', [Admin\CommunicationController::class, 'scheduleBroadcast']);
            Route::post('broadcasts/{broadcast}/cancel', [Admin\CommunicationController::class, 'cancelBroadcast']);
        });
        Route::middleware('permission:'.P::COMMUNICATION_CHAT)->group(function (): void {
            Route::get('chat-threads', [Admin\CommunicationController::class, 'threads']);
            Route::get('chat-threads/{chatThread}', [Admin\CommunicationController::class, 'thread']);
            Route::post('chat-threads/{chatThread}/messages', [Admin\CommunicationController::class, 'reply']);
            Route::patch('chat-threads/{chatThread}', [Admin\CommunicationController::class, 'updateThread']);
        });
        Route::get('notification-logs', [Admin\CommunicationController::class, 'logs'])->middleware('permission:'.P::AUDIT_READ.','.P::CONFIG_MANAGE);

        // Audit (11 §A.17)
        Route::middleware('permission:'.P::AUDIT_READ)->group(function (): void {
            Route::get('audit-logs', [Admin\AuditController::class, 'auditLogs']);
            Route::get('sensitive-access-logs', [Admin\AuditController::class, 'sensitiveLogs']);
            Route::get('export-logs', [Admin\AuditController::class, 'exportLogs']);
            Route::get('audit/summary', [Admin\AuditController::class, 'summary']);
        });
    });

    // ------------------------------------------------------------------ Webhook & integrasi
    Route::post('webhooks/payments/{provider}', [WebhookController::class, 'payment'])->middleware('throttle:pmb-webhook');
    Route::post('webhooks/cbt/{provider}', [WebhookController::class, 'cbt'])->middleware('throttle:pmb-webhook');
    Route::post('integrations/leads', [IntegrationController::class, 'lead'])->middleware(['auth:sanctum', 'pmb.staff', 'tenant.access', 'throttle:pmb-integration']);
});
