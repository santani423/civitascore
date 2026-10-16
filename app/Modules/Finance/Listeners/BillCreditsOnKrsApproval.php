<?php

namespace Modules\Finance\Listeners;

use Modules\Academic\Events\KrsSubmissionApproved;
use Modules\Finance\Services\BillingService;
use Modules\Finance\Support\KeuSettings;
use Throwable;

/**
 * KRS disetujui → tagihan biaya per SKS diterbitkan/disesuaikan
 * (BR-KEU-10). Kegagalan dicatat dan tidak membatalkan persetujuan KRS;
 * petugas dapat menghitung ulang dari menu Tagihan.
 */
class BillCreditsOnKrsApproval
{
    public function __construct(private readonly BillingService $billing, private readonly KeuSettings $settings) {}

    public function handle(KrsSubmissionApproved $event): void
    {
        if (! $this->settings->bool('keu.credit_fee.auto_bill_on_krs_approval')) {
            return;
        }

        try {
            $submission = $event->submission->loadMissing(['student', 'academicTerm']);
            $this->billing->billCredits($submission->student, $submission->academicTerm);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
