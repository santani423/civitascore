<?php

namespace Modules\Finance\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Finance\Enums\TransactionStatus;
use Modules\Finance\Models\PaymentTransaction;
use Modules\Tenancy\Models\University;

/** Kuitansi PDF (FR-PON-07) — hanya untuk transaksi berhasil. */
class ReceiptController extends KeuController
{
    public function show(PaymentTransaction $paymentTransaction): Response
    {
        return $this->render($paymentTransaction);
    }

    public function render(PaymentTransaction $tx): Response
    {
        abort_unless(in_array($tx->status, [TransactionStatus::Success, TransactionStatus::Refunded], true) && $tx->receipt_number !== null, 409, 'Kuitansi hanya tersedia untuk pembayaran yang berhasil.');
        $tx->load(['student.studyProgram', 'allocations.invoice', 'allocations.installment', 'channel', 'recorder']);
        $pdf = Pdf::loadView('finance::pdf.receipt', ['tx' => $tx, 'university' => University::query()->find($tx->university_id)])->setPaper('a5', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$tx->receipt_number.'.pdf"',
        ]);
    }
}
