// JSON shaped like the backend (StudentFinanceController / Presenter).

Map<String, dynamic> summaryJson({bool blocked = true}) => {
  'student': {
    'id': 'S1',
    'nim': '2101001',
    'name': 'Andi Pratama',
    'study_program': 'Informatika',
    'admission_year': 2021,
    'status': 'active',
    'status_label': 'Aktif',
  },
  'outstanding': '2500000.00',
  'arrears': '500000.00',
  'open_invoices': 2,
  'next_due': {
    'date': '2026-11-01',
    'amount': '1250000.00',
    'invoice_id': 'INV1',
    'installment_seq': 2,
  },
  'deposit_available': '300000.00',
  'pending_verification': 1,
  'services': [
    {'service': 'krs', 'label': 'Pengisian KRS', 'blocked': blocked},
    {'service': 'exam', 'label': 'Ujian', 'blocked': blocked},
  ],
  'clearance': {
    'clear': false,
    'outstanding': '2500000.00',
    'accruing_penalty': '25000.00',
    'pending_verification': 1,
  },
  'blocks': blocked
      ? [
          {
            'id': 'B1',
            'student': null,
            'source': 'finance',
            'rule_code': 'KRS_RULE',
            'services': ['krs', 'exam'],
            'reason': 'Kewajiban keuangan belum terpenuhi (Aturan KRS).',
            'shortfall': '500000.00',
            'status': 'active',
            'status_label': 'Aktif',
            'effective_from': '2026-10-01T00:00:00+00:00',
            'revoked_at': null,
            'revoke_reason': null,
            'exemptions': [
              {
                'id': 'E1',
                'academic_block_id': 'B1',
                'service': 'krs',
                'valid_from': null,
                'valid_until': '2026-11-10T00:00:00+00:00',
                'reason': 'Menunggu beasiswa cair',
                'promise_amount': null,
                'promise_date': null,
                'status': 'pending',
                'status_label': 'Menunggu',
                'decision_note': null,
              },
            ],
          },
        ]
      : [],
};

Map<String, dynamic> invoiceJson({
  String id = 'INV1',
  String status = 'unpaid',
  String outstanding = '2000000.00',
  bool detail = false,
  bool withInstallments = false,
}) => {
  'id': id,
  'invoice_number': 'INV/2026/0001',
  'student': null,
  'student_id': 'S1',
  'period': 'Ganjil 2026/2027',
  'bill_group': 'registration',
  'bill_group_label': 'Registrasi Semester',
  'gross_amount': '2500000.00',
  'discount_amount': '500000.00',
  'adjustment_amount': '0.00',
  'amount': '2000000.00',
  'paid_amount': status == 'paid' ? '2000000.00' : '0.00',
  'outstanding': status == 'paid' ? '0.00' : outstanding,
  'status': status,
  'status_label': status == 'paid' ? 'Lunas' : 'Belum Dibayar',
  'is_overdue': false,
  'overdue_since': null,
  'due_date': '2026-11-01',
  'issued_at': '2026-10-01T00:00:00+00:00',
  'settled_at': null,
  if (detail) ...{
    'items': [
      {
        'id': 'I1',
        'line_type': 'charge',
        'line_type_label': 'Biaya',
        'description': 'UKT Semester Ganjil',
        'quantity': '1.00',
        'unit_amount': '2500000.00',
        'amount': '2500000.00',
      },
      {
        'id': 'I2',
        'line_type': 'aid',
        'line_type_label': 'Beasiswa/Bantuan',
        'description': 'Beasiswa Prestasi',
        'quantity': '1.00',
        'unit_amount': '500000.00',
        'amount': '500000.00',
      },
    ],
    'installments': withInstallments
        ? [
            {
              'id': 'T1',
              'invoice_id': id,
              'seq': 1,
              'schedule_version': 1,
              'amount': '1000000.00',
              'paid_amount': '1000000.00',
              'remaining': '0.00',
              'due_date': '2026-10-15',
              'status': 'paid',
              'status_label': 'Lunas',
              'is_overdue': false,
            },
            {
              'id': 'T2',
              'invoice_id': id,
              'seq': 2,
              'schedule_version': 1,
              'amount': '1000000.00',
              'paid_amount': '0.00',
              'remaining': '1000000.00',
              'due_date': '2026-11-15',
              'status': 'scheduled',
              'status_label': 'Terjadwal',
              'is_overdue': false,
            },
          ]
        : [],
    'payments': [
      {
        'id': 'A1',
        'invoice_id': id,
        'payment_transaction_id': 'TX0',
        'invoice_installment_id': null,
        'amount': '1000000.00',
        'allocation_type': 'payment',
        'status': 'posted',
        'paid_at': '2026-10-10T03:00:00+00:00',
        'method': 'manual_transfer',
        'reference': 'TRX-0',
        'reversal_reason': null,
      },
    ],
    'adjustments': [],
    'penalty_invoices': [],
    'cancel_reason': null,
    'pending_transactions': [manualTxJson()],
  },
};

Map<String, dynamic> manualTxJson({
  String status = 'pending',
  Map<String, dynamic>? proof,
}) => {
  'id': 'TX1',
  'transaction_number': 'TRX-20261018-001',
  'receipt_number': null,
  'student': null,
  'payer_name': 'Andi Pratama',
  'channel': null,
  'provider': 'manual',
  'method': 'manual_transfer',
  'method_label': 'Transfer Manual',
  'target_type': 'invoice',
  'target_id': 'INV1',
  'amount': '2000123.00',
  'channel_fee': '0.00',
  'status': status,
  'status_label': 'Menunggu Pembayaran',
  'va_number': null,
  'qr_string': null,
  'redirect_url': null,
  'unique_code': '123',
  'instructions': {
    'bank_accounts': [
      {
        'bank': 'BCA',
        'account_number': '1234567890',
        'account_name': 'Universitas Contoh',
      },
    ],
  },
  'expires_at': '2026-10-21T10:00:00+00:00',
  'paid_at': null,
  'reconciliation_status': null,
  'refunded_amount': '0.00',
  'failure_reason': null,
  'proof': proof,
  'created_at': '2026-10-18T10:00:00+00:00',
};

Map<String, dynamic> onlineTxJson({String status = 'pending'}) => {
  ...manualTxJson(status: status),
  'id': 'TX2',
  'transaction_number': 'TRX-20261018-002',
  'provider': 'xendit',
  'method': 'online',
  'method_label': 'Online',
  'channel': {'id': 'CH1', 'code': 'BCA_VA', 'name': 'BCA Virtual Account'},
  'amount': '2004000.00',
  'unique_code': null,
  'redirect_url': 'https://checkout.xendit.co/web/abc',
  'va_number': '88001234',
  'instructions': {
    'steps': [
      {
        'title': 'ATM BCA',
        'steps': ['Pilih Transaksi Lainnya', 'Masukkan nomor VA'],
      },
    ],
    'channel_instructions': null,
  },
};

Map<String, dynamic> channelsJson({bool online = true}) => {
  'online': online
      ? [
          {
            'id': 'CH1',
            'code': 'BCA_VA',
            'name': 'BCA Virtual Account',
            'method': 'va',
            'fee_amount': '4000.00',
            'fee_bearer': 'student',
          },
          {
            'id': 'CH2',
            'code': 'QRIS',
            'name': 'QRIS',
            'method': 'qris',
            'fee_amount': '0.00',
            'fee_bearer': 'campus',
          },
        ]
      : [],
  'manual_transfer': {
    'enabled': true,
    'bank_accounts': [
      {'bank': 'BCA', 'account_number': '1234567890'},
    ],
    'unique_code': true,
  },
};

Map<String, dynamic> depositJson() => {
  'balance': '300000.00',
  'held_amount': '0.00',
  'available': '300000.00',
  'entries': [
    {
      'id': 'D1',
      'type': 'overpayment',
      'type_label': 'Kelebihan Bayar',
      'amount': '300000.00',
      'balance_after': '300000.00',
      'note': null,
      'created_at': '2026-10-05T01:00:00+00:00',
    },
  ],
  'refunds': [
    {
      'id': 'R1',
      'refund_number': 'RF-001',
      'student': null,
      'amount': '100000.00',
      'reason_code': 'OVERPAYMENT',
      'reason_note': 'Kelebihan bayar semester lalu',
      'beneficiary_bank': 'BCA',
      'beneficiary_account': '******7890',
      'beneficiary_name': 'Andi Pratama',
      'beneficiary_relation': 'self',
      'status': 'pending_approval',
      'status_label': 'Menunggu Persetujuan',
      'created_at': '2026-10-06T01:00:00+00:00',
    },
  ],
};
