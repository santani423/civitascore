<?php

namespace Modules\Admission\Support;

/** Kode alasan keputusan (04 §E.3, 05 §G.2 FR-VER-03, 05 §F.5). */
final class DocumentReasonCodes
{
    public const DOCUMENT = ['UNREADABLE', 'WRONG_DOCUMENT', 'INCOMPLETE_PAGES', 'EXPIRED', 'NAME_MISMATCH', 'DATA_MISMATCH', 'NOT_LEGALIZED', 'SUSPECTED_FORGERY', 'PHOTO_NOT_COMPLIANT', 'OTHER'];

    public const APPLICATION_REJECTION = ['INELIGIBLE_EDUCATION', 'INELIGIBLE_AGE', 'FALSIFIED_DATA', 'DUPLICATE_APPLICATION', 'INCOMPLETE_AFTER_REVISION', 'OTHER'];

    public const MANUAL_PROOF_REJECTION = ['NOT_FOUND_IN_STATEMENT', 'AMOUNT_MISMATCH', 'UNREADABLE', 'DUPLICATE_PROOF', 'OTHER'];

    public const REFUND = ['DUPLICATE_PAYMENT', 'PERIOD_CANCELLED', 'WITHDRAWAL', 'LATE_PAYMENT', 'OVERPAYMENT', 'POLICY', 'OTHER'];

    /** Alasan yang tidak ditampilkan apa adanya ke pendaftar (04 §E.3). */
    public const MASKED_FOR_APPLICANT = ['SUSPECTED_FORGERY'];

    public const MASKED_MESSAGE = 'Dokumen tidak dapat diterima, hubungi panitia.';
}
