import 'package:civitasone/features/finance/data/finance_api.dart';
import 'package:civitasone/features/finance/presentation/widgets/invoice_actions.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import 'finance_fixtures.dart';

FinanceApi _api(FakeApi fake) => FinanceApi(fake.client());

void main() {
  test(
    'summary parses decimals strings, next due, blocks and exemptions',
    () async {
      final fake = FakeApi()..get('/student/finance/summary', summaryJson());
      final s = await _api(fake).summary();
      expect(s.outstanding, '2500000.00');
      expect(s.hasArrears, isTrue);
      expect(s.nextDue!.seq, 2);
      expect(s.nextDue!.invoiceId, 'INV1');
      expect(s.services.where((e) => e.blocked), hasLength(2));
      expect(s.clearance!.clear, isFalse);
      expect(s.activeBlocks.single.services, ['krs', 'exam']);
      expect(s.activeBlocks.single.exemptions.single.status, 'pending');
    },
  );

  test('summary tolerates null next_due and empty arrays', () async {
    final json = summaryJson(blocked: false)..['next_due'] = null;
    final fake = FakeApi()..get('/student/finance/summary', json);
    final s = await _api(fake).summary();
    expect(s.nextDue, isNull);
    expect(s.blocks, isEmpty);
  });

  test('invoices page sends status filter and parses meta', () async {
    final fake = FakeApi()
      ..get(
        '/student/finance/invoices',
        [invoiceJson()],
        meta: {'current_page': 1, 'per_page': 20, 'total': 30, 'last_page': 2},
      );
    final page = await _api(fake).invoices(status: 'unpaid,partial');
    expect(page.items.single.invoiceNumber, 'INV/2026/0001');
    expect(page.hasNextPage, isTrue);
    expect(fake.requests.single.query['status'], 'unpaid,partial');
  });

  test(
    'invoice detail parses items, installments, allocations, pending tx',
    () async {
      final fake = FakeApi()
        ..get(
          '/student/finance/invoices/INV1',
          invoiceJson(detail: true, withInstallments: true),
        );
      final inv = await _api(fake).invoice('INV1');
      expect(inv.items, hasLength(2));
      expect(inv.items.last.isDeduction, isTrue);
      expect(inv.nextInstallment!.id, 'T2');
      expect(inv.allocations.single.amount, '1000000.00');
      expect(
        inv.pendingTransactions.single.bankAccounts.single.number,
        '1234567890',
      );
      expect(inv.canPay, isTrue);
    },
  );

  test('channels parse online and manual transfer', () async {
    final fake = FakeApi()..get('/student/finance/channels', channelsJson());
    final c = await _api(fake).channels();
    expect(c.online, hasLength(2));
    expect(c.online.first.studentPaysFee, isTrue);
    expect(c.manualEnabled, isTrue);
    final empty = FakeApi()
      ..get('/student/finance/channels', channelsJson(online: false));
    expect((await _api(empty).channels()).online, isEmpty);
  });

  test('online payment sends Idempotency-Key header and target body', () async {
    final fake = FakeApi()
      ..on(
        'POST',
        '/student/finance/payments/online',
        (_) => FakeResponse.ok(onlineTxJson()),
      );
    final tx = await _api(fake).payOnline(
      channelId: 'CH1',
      targetType: 'invoice',
      targetId: 'INV1',
      idempotencyKey: 'key-1',
    );
    final r = fake.requests.single;
    expect(r.headers['Idempotency-Key'], 'key-1');
    expect(r.body, {
      'payment_channel_id': 'CH1',
      'target_type': 'invoice',
      'target_id': 'INV1',
    });
    expect(tx.redirectUrl, startsWith('https://checkout'));
    expect(tx.steps.single.steps, hasLength(2));
    expect(tx.canRefresh, isTrue);
  });

  test(
    'manual payment, deposit top-up payload omits null target/amount',
    () async {
      final fake = FakeApi()
        ..on(
          'POST',
          '/student/finance/payments/manual',
          (_) => FakeResponse.ok(manualTxJson()),
        );
      await _api(fake).payManual(targetType: 'invoice', targetId: 'INV1');
      await _api(fake).payManual(targetType: 'deposit', amount: '50000');
      expect(fake.requests[0].body, {
        'target_type': 'invoice',
        'target_id': 'INV1',
      });
      expect(fake.requests[1].body, {
        'target_type': 'deposit',
        'amount': '50000',
      });
    },
  );

  test('proof, cancel, refresh, deposit, voucher paths and payloads', () async {
    final fake = FakeApi()
      ..on(
        'POST',
        '/student/finance/transactions/{id}/proof',
        (_) => FakeResponse.ok(manualTxJson(status: 'awaiting_verification')),
      )
      ..on(
        'POST',
        '/student/finance/transactions/{id}/cancel',
        (_) => FakeResponse.ok(manualTxJson(status: 'cancelled')),
      )
      ..on(
        'POST',
        '/student/finance/transactions/{id}/refresh',
        (_) => FakeResponse.ok(onlineTxJson(status: 'success')),
      )
      ..on(
        'POST',
        '/student/finance/invoices/{id}/deposit',
        (_) => FakeResponse.ok(
          {},
          message: 'Saldo deposit dipakai sebesar Rp300.000.',
        ),
      )
      ..on(
        'POST',
        '/student/finance/invoices/{id}/voucher',
        (_) => FakeResponse.ok({'discount_amount': '100000.00'}),
      );
    final api = _api(fake);
    final tx = await api.submitProof(
      'TX1',
      fileUploadId: 'F1',
      senderBank: 'BCA',
      senderName: 'Andi',
      transferDate: '2026-10-18',
      transferredAmount: '2000123',
    );
    expect(tx.status, 'awaiting_verification');
    expect(fake.requests.last.path, '/student/finance/transactions/TX1/proof');
    expect(fake.requests.last.body, {
      'file_upload_id': 'F1',
      'sender_bank': 'BCA',
      'sender_name': 'Andi',
      'transfer_date': '2026-10-18',
      'transferred_amount': '2000123',
    });
    expect((await api.cancel('TX1')).status, 'cancelled');
    expect((await api.refresh('TX2')).status, 'success');
    expect(
      await api.applyDeposit('INV1', amount: '1000'),
      contains('Saldo deposit'),
    );
    expect(fake.requests.last.body, {'amount': '1000'});
    await api.redeemVoucher('INV1', 'HEMAT10');
    expect(fake.requests.last.body, {'code': 'HEMAT10'});
  });

  test('installment, refund, ukt and dispensation payloads', () async {
    final fake = FakeApi()
      ..on(
        'POST',
        '/student/finance/invoices/{id}/installments',
        (_) => FakeResponse.ok({'id': 'x', 'status': 'submitted'}),
      )
      ..on('POST', '/student/finance/refunds', (_) => FakeResponse.ok({}))
      ..on(
        'POST',
        '/student/finance/ukt-requests',
        (_) => FakeResponse.ok({'id': 'u'}),
      )
      ..on(
        'POST',
        '/student/finance/blocks/{id}/dispensations',
        (_) => FakeResponse.ok({}),
      );
    final api = _api(fake);
    await api.requestInstallment('INV1', planId: 'P1');
    expect(fake.requests.last.body, {
      'installment_plan_id': 'P1',
      'kind': 'new',
    });
    await api.requestInstallment(
      'INV1',
      terms: buildEqualTerms(3),
      reason: 'Alasan',
    );
    expect((fake.requests.last.body as Map)['terms'], hasLength(3));
    await api.requestRefund(
      amount: '100000',
      reasonCode: 'OVERPAYMENT',
      reasonNote: 'Kelebihan bayar',
      bank: 'BCA',
      account: '1234567890',
      name: 'Andi',
      relation: 'self',
    );
    expect(
      (fake.requests.last.body as Map)['beneficiary_account'],
      '1234567890',
    );
    await api.requestUktChange(reason: 'x' * 20, evidenceFileId: 'F9');
    expect(fake.requests.last.body, {
      'reason': 'x' * 20,
      'evidence_file_id': 'F9',
    });
    await api.requestDispensation(
      'B1',
      service: 'krs',
      validUntil: '2026-11-10',
      reason: 'y' * 20,
    );
    expect(fake.requests.last.path, '/student/finance/blocks/B1/dispensations');
    expect(fake.requests.last.body, {
      'service': 'krs',
      'valid_until': '2026-11-10',
      'reason': 'y' * 20,
    });
  });

  test('buildEqualTerms always sums to 100 percent', () {
    for (var n = 2; n <= 12; n++) {
      final terms = buildEqualTerms(n);
      final sum = terms.fold<double>(0, (a, t) => a + (t['percent'] as double));
      expect(sum, closeTo(100, 0.001), reason: 'n=$n');
      expect(terms.first['due_offset_days'], 0);
    }
  });

  test('deposit, statement, ukt, awards parse', () async {
    final fake = FakeApi()
      ..get('/student/finance/deposit', depositJson())
      ..get('/student/finance/statement', {
        'student_id': 'S1',
        'entries': [
          {
            'date': '2026-10-01',
            'invoice_number': 'INV/1',
            'type': 'Tagihan',
            'description': 'UKT',
            'amount': '2000000.00',
            'balance': '2000000.00',
          },
          {
            'date': '2026-10-10',
            'invoice_number': 'INV/1',
            'type': 'Pembayaran',
            'description': null,
            'amount': '-1000000.00',
            'balance': '1000000.00',
          },
        ],
        'balance': '1000000.00',
      })
      ..get('/student/finance/ukt', {
        'current': {
          'group': {'id': 'G1', 'code': 'UKT3', 'name': 'UKT 3'},
          'basis': 'decree',
          'decree_number': 'SK/1',
          'effective_from': '2026-08-01',
        },
        'groups': [
          {'id': 'G1', 'code': 'UKT3', 'name': 'UKT 3'},
        ],
        'requests': [
          {
            'id': 'U1',
            'requested_group': null,
            'decided_group': null,
            'reason': 'Alasan',
            'status': 'submitted',
            'status_label': 'Diajukan',
            'decision_note': null,
            'created_at': '2026-10-02T00:00:00+00:00',
          },
        ],
      })
      ..get('/student/finance/awards', [
        {
          'id': 'AW1',
          'policy': {
            'id': 'P',
            'code': 'KIP',
            'name': 'KIP Kuliah',
            'kind': 'kip',
            'kind_label': 'KIP Kuliah',
          },
          'calc_type': 'percent',
          'calc_type_label': 'Persentase',
          'value': '100.00',
          'max_amount': null,
          'start_term': 'Ganjil 2026',
          'end_term': null,
          'status': 'active',
          'status_label': 'Aktif',
        },
      ]);
    final api = _api(fake);
    final d = await api.deposit();
    expect(d.refunds.single.beneficiaryAccount, '******7890');
    expect(d.entries.single.balanceAfter, '300000.00');
    final st = await api.statement();
    expect(st.entries.last.amount, '-1000000.00');
    final u = await api.ukt();
    expect(u.current!.group.name, 'UKT 3');
    expect(u.hasPendingRequest, isTrue);
    final aw = await api.awards();
    expect(aw.single.policyName, 'KIP Kuliah');
    expect(aw.single.maxAmount, isNull);
  });
}
