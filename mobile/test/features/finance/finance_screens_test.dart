import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/core/utils/file_actions.dart';
import 'package:civitasone/features/finance/presentation/providers.dart';
import 'package:civitasone/features/finance/presentation/screens/awards_screen.dart';
import 'package:civitasone/features/finance/presentation/screens/clearance_screen.dart';
import 'package:civitasone/features/finance/presentation/screens/deposit_screen.dart';
import 'package:civitasone/features/finance/presentation/screens/finance_home_screen.dart';
import 'package:civitasone/features/finance/presentation/screens/invoice_detail_screen.dart';
import 'package:civitasone/features/finance/presentation/screens/invoices_screen.dart';
import 'package:civitasone/features/finance/presentation/screens/pay_screen.dart';
import 'package:civitasone/features/finance/presentation/screens/statement_screen.dart';
import 'package:civitasone/features/finance/presentation/screens/transactions_screen.dart';
import 'package:civitasone/features/finance/presentation/screens/ukt_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';
import 'finance_fixtures.dart';

const _p = '/student/finance';
const _meta = {'current_page': 1, 'per_page': 20, 'total': 1, 'last_page': 1};

FakeApi _api() => FakeApi()
  ..get('$_p/summary', summaryJson())
  ..get('/notifications/unread-count', {'unread_count': 0})
  ..get('$_p/invoices', [invoiceJson()], meta: _meta)
  ..get('$_p/invoices/INV1', invoiceJson(detail: true, withInstallments: true))
  ..get('$_p/invoices/INV1/installment-plans', [
    {
      'id': 'P1',
      'code': 'C3',
      'name': 'Cicilan 3x',
      'terms': [
        {'percent': 50},
        {'percent': 50},
      ],
      'auto_approve': true,
    },
  ])
  ..get('$_p/channels', channelsJson())
  ..get('$_p/transactions', [
    manualTxJson(),
    onlineTxJson(status: 'success')..['receipt_number'] = 'KW-1',
  ], meta: _meta)
  ..get('$_p/deposit', depositJson())
  ..get('$_p/statement', {
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
    ],
    'balance': '2000000.00',
  })
  ..get('$_p/ukt', {
    'current': {
      'group': {'id': 'G1', 'code': 'U3', 'name': 'UKT 3'},
      'basis': 'decree',
      'decree_number': 'SK/9',
      'effective_from': '2026-08-01',
    },
    'groups': [
      {'id': 'G1', 'code': 'U3', 'name': 'UKT 3'},
    ],
    'requests': [],
  })
  ..get('$_p/awards', [
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

Future<void> _scroll(WidgetTester tester, Finder f) async {
  await tester.scrollUntilVisible(
    f,
    200,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.pumpAndSettle();
}

void main() {
  group('home', () {
    testWidgets('shows summary figures, cekal banner and shortcuts (ID)', (
      tester,
    ) async {
      await pumpApp(tester, const FinanceHomeScreen(), api: _api());
      expect(find.text('Keuangan'), findsWidgets);
      expect(find.text('Rp 2.500.000'), findsOneWidget);
      expect(find.text('Rp 500.000'), findsOneWidget);
      expect(find.text('Rp 300.000'), findsOneWidget);
      expect(find.text('Cekal aktif'), findsOneWidget);
      expect(
        find.textContaining('1 pembayaran menunggu verifikasi'),
        findsOneWidget,
      );
      await _scroll(tester, find.text('Status cekal'));
      expect(find.text('Rekening koran'), findsOneWidget);
    });

    testWidgets('shortcut navigates to invoices and clearance', (tester) async {
      await pumpApp(tester, const FinanceHomeScreen(), api: _api());
      await _scroll(tester, find.text('Rekening koran'));
      await tester.tap(find.text('Rekening koran'));
      await tester.pumpAndSettle();
      expect(find.text('route:/s/finance/statement'), findsOneWidget);
    });

    testWidgets('renders in English, dark mode, small phone', (tester) async {
      await pumpApp(
        tester,
        const FinanceHomeScreen(),
        api: _api(),
        language: AppLanguage.en,
        themeMode: ThemeMode.dark,
        size: TestScreens.smallPhone,
      );
      expect(find.text('Total outstanding · 2 open invoices'), findsOneWidget);
      expect(find.text('Active restrictions'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });

    testWidgets('renders in Mandarin without overflow', (tester) async {
      await pumpApp(
        tester,
        const FinanceHomeScreen(),
        api: _api(),
        language: AppLanguage.zh,
        size: TestScreens.smallPhone,
      );
      expect(find.text('财务'), findsWidgets);
      expect(tester.takeException(), isNull);
    });

    testWidgets('error state offers retry', (tester) async {
      final api = FakeApi()
        ..on('GET', '$_p/summary', (_) => FakeResponse.error(500, 'boom'))
        ..get('/notifications/unread-count', {'unread_count': 0});
      await pumpApp(tester, const FinanceHomeScreen(), api: api);
      expect(find.text('Coba lagi'), findsOneWidget);
    });
  });

  group('invoices', () {
    testWidgets('lists invoices, filter sends status, tap opens detail', (
      tester,
    ) async {
      final api = _api();
      await pumpApp(tester, const InvoicesScreen(), api: api);
      expect(find.text('Registrasi Semester'), findsOneWidget);
      expect(find.text('Rp 2.000.000'), findsOneWidget);
      await tester.tap(find.text('Lunas'));
      await tester.pumpAndSettle();
      expect(
        api.requests
            .where((r) => r.path == '$_p/invoices')
            .last
            .query['status'],
        'paid',
      );
      await tester.tap(find.text('Registrasi Semester'));
      await tester.pumpAndSettle();
      expect(find.text('route:/s/finance/invoices/INV1'), findsOneWidget);
    });

    testWidgets('empty state', (tester) async {
      final api = FakeApi()..get('$_p/invoices', [], meta: _meta);
      await pumpApp(tester, const InvoicesScreen(), api: api);
      expect(find.text('Belum ada tagihan'), findsOneWidget);
    });

    testWidgets(
      'detail shows components, installments, allocations, pending tx',
      (tester) async {
        await pumpApp(
          tester,
          const InvoiceDetailScreen(invoiceId: 'INV1'),
          api: _api(),
        );
        expect(find.text('Bayar sekarang'), findsOneWidget);
        expect(find.textContaining('Bayar termin berikutnya'), findsOneWidget);
        await _scroll(tester, find.text('UKT Semester Ganjil'));
        expect(find.text('Beasiswa Prestasi'), findsOneWidget);
        expect(find.text('- Rp 500.000'), findsWidgets);
        await _scroll(tester, find.text('Termin 2'));
        await _scroll(tester, find.text('TRX-20261018-001'));
        expect(find.text('Menunggu pembayaran'), findsWidgets);
      },
    );

    testWidgets('pay now navigates to pay route; installment carries id', (
      tester,
    ) async {
      await pumpApp(
        tester,
        const InvoiceDetailScreen(invoiceId: 'INV1'),
        api: _api(),
      );
      await tester.tap(find.textContaining('Bayar termin berikutnya'));
      await tester.pumpAndSettle();
      expect(
        find.text('route:/s/finance/invoices/INV1/pay?installment=T2'),
        findsOneWidget,
      );
    });

    testWidgets('voucher: validates length then posts uppercase code', (
      tester,
    ) async {
      final api = _api()
        ..on(
          'POST',
          '$_p/invoices/INV1/voucher',
          (_) => FakeResponse.ok({
            'discount_amount': '10000.00',
          }, message: 'Voucher berhasil dipakai'),
        );
      await pumpApp(
        tester,
        const InvoiceDetailScreen(invoiceId: 'INV1'),
        api: api,
      );
      await tester.tap(find.text('Gunakan voucher'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextFormField), 'ab');
      await tester.tap(find.widgetWithText(FilledButton, 'Terapkan'));
      await tester.pumpAndSettle();
      expect(find.text('4 sampai 30 karakter'), findsWidgets);
      expect(api.requests.where((r) => r.method == 'POST'), isEmpty);
      await tester.enterText(find.byType(TextFormField), 'hemat10');
      await tester.tap(find.widgetWithText(FilledButton, 'Terapkan'));
      await tester.pumpAndSettle();
      final post = api.requests.firstWhere((r) => r.method == 'POST');
      expect(post.body, {'code': 'HEMAT10'});
      expect(find.text('Voucher berhasil dipakai'), findsOneWidget);
    });

    testWidgets('voucher server error (409) is shown in the form', (
      tester,
    ) async {
      final api = _api()
        ..on(
          'POST',
          '$_p/invoices/INV1/voucher',
          (_) => FakeResponse.error(
            409,
            'Kode voucher tidak dikenal.',
            errors: {'code': 'VOUCHER_INVALID'},
          ),
        );
      await pumpApp(
        tester,
        const InvoiceDetailScreen(invoiceId: 'INV1'),
        api: api,
      );
      await tester.tap(find.text('Gunakan voucher'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextFormField), 'SALAH1');
      await tester.tap(find.widgetWithText(FilledButton, 'Terapkan'));
      await tester.pumpAndSettle();
      expect(find.text('Kode voucher tidak dikenal.'), findsOneWidget);
    });

    testWidgets('use deposit posts and shows backend message', (tester) async {
      final api = _api()
        ..on(
          'POST',
          '$_p/invoices/INV1/deposit',
          (_) => FakeResponse.ok(
            {},
            message: 'Saldo deposit dipakai sebesar Rp300.000.',
          ),
        );
      await pumpApp(
        tester,
        const InvoiceDetailScreen(invoiceId: 'INV1'),
        api: api,
      );
      await tester.tap(find.text('Pakai saldo deposit'));
      await tester.pumpAndSettle();
      await tester.tap(
        find.widgetWithText(FilledButton, 'Pakai saldo deposit'),
      );
      await tester.pumpAndSettle();
      expect(
        api.requests.firstWhere((r) => r.method == 'POST').body,
        <String, dynamic>{},
      );
      expect(
        find.text('Saldo deposit dipakai sebesar Rp300.000.'),
        findsOneWidget,
      );
    });

    testWidgets('request installment with a standard plan', (tester) async {
      final api = _api()
        ..on(
          'POST',
          '$_p/invoices/INV1/installments',
          (_) => FakeResponse.ok({
            'id': 'x',
            'status': 'approved',
          }, message: 'Cicilan disetujui'),
        );
      await pumpApp(
        tester,
        const InvoiceDetailScreen(invoiceId: 'INV1'),
        api: api,
      );
      await tester.tap(find.text('Ajukan cicilan'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Cicilan 3x'));
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Ajukan cicilan'));
      await tester.pumpAndSettle();
      final post = api.requests.firstWhere((r) => r.method == 'POST');
      expect(post.body, {'installment_plan_id': 'P1', 'kind': 'reschedule'});
    });

    testWidgets('custom installments require a reason', (tester) async {
      final api = _api();
      await pumpApp(
        tester,
        const InvoiceDetailScreen(invoiceId: 'INV1'),
        api: api,
      );
      await tester.tap(find.text('Ajukan cicilan'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Skema khusus'));
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Ajukan cicilan'));
      await tester.pumpAndSettle();
      expect(find.text('Wajib diisi'), findsOneWidget);
      expect(api.requests.where((r) => r.method == 'POST'), isEmpty);
    });
  });

  group('pay', () {
    testWidgets(
      'online: posts with Idempotency-Key, opens redirect_url, can refresh',
      (tester) async {
        final launched = <Uri>[];
        final api = _api()
          ..on(
            'POST',
            '$_p/payments/online',
            (_) => FakeResponse.ok(onlineTxJson()),
          )
          ..on(
            'POST',
            '$_p/transactions/TX2/refresh',
            (_) => FakeResponse.ok(
              onlineTxJson(status: 'success')..['receipt_number'] = 'KW-2',
            ),
          );
        await pumpApp(
          tester,
          const PayScreen(invoiceId: 'INV1'),
          api: api,
          overrides: [
            externalLauncherProvider.overrideWithValue((u) async {
              launched.add(u);
              return true;
            }),
          ],
        );
        expect(find.text('BCA Virtual Account'), findsOneWidget);
        expect(find.text('Biaya layanan Rp 4.000'), findsOneWidget);
        await tester.tap(find.text('BCA Virtual Account'));
        await tester.pumpAndSettle();
        await tester.tap(find.widgetWithText(FilledButton, 'Bayar sekarang'));
        await tester.pumpAndSettle();
        final post = api.requests.firstWhere(
          (r) => r.path == '$_p/payments/online',
        );
        expect(post.headers['Idempotency-Key'], isNotEmpty);
        expect(post.body, {
          'payment_channel_id': 'CH1',
          'target_type': 'invoice',
          'target_id': 'INV1',
        });
        expect(
          launched.single.toString(),
          'https://checkout.xendit.co/web/abc',
        );
        // Transaction sheet is open with VA number and refresh button.
        expect(find.text('88001234'), findsOneWidget);
        await tester.tap(find.text('Cek status pembayaran'));
        await tester.pumpAndSettle();
        expect(
          api.requests.any((r) => r.path == '$_p/transactions/TX2/refresh'),
          isTrue,
        );
        expect(find.text('Berhasil'), findsOneWidget);
      },
    );

    testWidgets('retry of the same attempt reuses the Idempotency-Key', (
      tester,
    ) async {
      var calls = 0;
      final api = _api()
        ..on('POST', '$_p/payments/online', (_) {
          calls++;
          return calls == 1
              ? FakeResponse.error(500, 'gagal')
              : FakeResponse.ok(onlineTxJson());
        });
      await pumpApp(
        tester,
        const PayScreen(invoiceId: 'INV1'),
        api: api,
        overrides: [
          externalLauncherProvider.overrideWithValue((_) async => true),
        ],
      );
      await tester.tap(find.text('QRIS').last);
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Bayar sekarang'));
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Bayar sekarang'));
      await tester.pumpAndSettle();
      final keys = api.requests
          .where((r) => r.path == '$_p/payments/online')
          .map((r) => r.headers['Idempotency-Key'])
          .toList();
      expect(keys, hasLength(2));
      expect(keys[0], keys[1]);
    });

    testWidgets(
      '409 CHANNEL_NOT_CONNECTED explains and keeps manual transfer',
      (tester) async {
        final api = _api()
          ..on(
            'POST',
            '$_p/payments/online',
            (_) => FakeResponse.error(
              409,
              'x',
              errors: {'code': 'CHANNEL_NOT_CONNECTED'},
            ),
          );
        await pumpApp(tester, const PayScreen(invoiceId: 'INV1'), api: api);
        await tester.tap(find.text('QRIS').last);
        await tester.pumpAndSettle();
        await tester.tap(find.widgetWithText(FilledButton, 'Bayar sekarang'));
        await tester.pumpAndSettle();
        expect(
          find.textContaining('belum aktif atau belum terhubung'),
          findsOneWidget,
        );
        expect(find.text('Buat instruksi transfer'), findsOneWidget);
      },
    );

    testWidgets('no online channels: banner + manual transfer', (tester) async {
      final api = _api()..get('$_p/channels', channelsJson(online: false));
      await pumpApp(tester, const PayScreen(invoiceId: 'INV1'), api: api);
      expect(
        find.textContaining('Pembayaran online belum aktif'),
        findsOneWidget,
      );
      expect(find.text('Buat instruksi transfer'), findsOneWidget);
    });

    testWidgets(
      'manual: shows bank account, unique-code amount, then uploads proof (two steps)',
      (tester) async {
        final uploaded = <String>[];
        final api = _api()
          ..on(
            'POST',
            '$_p/payments/manual',
            (_) => FakeResponse.ok(manualTxJson()),
          )
          ..on(
            'POST',
            '$_p/transactions/TX1/proof',
            (_) => FakeResponse.ok(
              manualTxJson(
                status: 'awaiting_verification',
                proof: {
                  'id': 'PR',
                  'status': 'submitted',
                  'status_label': 'Menunggu',
                  'sender_bank': 'BCA',
                  'sender_name': 'Andi',
                  'transfer_date': '2026-10-18',
                  'transferred_amount': '2000123.00',
                },
              ),
            ),
          );
        await pumpApp(
          tester,
          const PayScreen(invoiceId: 'INV1'),
          api: api,
          overrides: [
            uploadPickerProvider.overrideWithValue(
              (context, {required maxMb}) async => const PickedUpload(
                path: '/tmp/bukti.jpg',
                name: 'bukti.jpg',
                size: 3,
              ),
            ),
            fileUploaderProvider.overrideWithValue((file) async {
              uploaded.add(file.name);
              return 'F1';
            }),
          ],
        );
        await tester.tap(find.text('Buat instruksi transfer'));
        await tester.pumpAndSettle();
        expect(
          api.requests.firstWhere((r) => r.path == '$_p/payments/manual').body,
          {'target_type': 'invoice', 'target_id': 'INV1'},
        );
        expect(find.text('Rp 2.000.123'), findsOneWidget);
        expect(find.text('1234567890'), findsOneWidget);
        expect(find.textContaining('Universitas Contoh'), findsWidgets);
        await tester.tap(find.text('Unggah bukti transfer'));
        await tester.pumpAndSettle();
        // validation
        await tester.tap(
          find.widgetWithText(FilledButton, 'Unggah bukti transfer'),
        );
        await tester.pumpAndSettle();
        expect(find.text('Wajib diisi'), findsWidgets);
        await tester.enterText(
          find.widgetWithText(TextFormField, 'Bank pengirim'),
          'BCA',
        );
        await tester.enterText(
          find.widgetWithText(TextFormField, 'Nama pengirim'),
          'Andi',
        );
        await tester.tap(find.textContaining('Pilih tanggal'));
        await tester.pumpAndSettle();
        await tester.tap(
          find
              .descendant(
                of: find.byType(Dialog),
                matching: find.byType(TextButton),
              )
              .last,
        );
        await tester.pumpAndSettle();
        await tester.tap(find.textContaining('Berkas bukti'));
        await tester.pumpAndSettle();
        await tester.tap(
          find.widgetWithText(FilledButton, 'Unggah bukti transfer'),
        );
        await tester.pumpAndSettle();
        expect(uploaded, ['bukti.jpg']);
        final proof = api.requests.firstWhere(
          (r) => r.path == '$_p/transactions/TX1/proof',
        );
        expect((proof.body as Map)['file_upload_id'], 'F1');
        expect((proof.body as Map)['sender_bank'], 'BCA');
        expect((proof.body as Map)['transferred_amount'], '2000123');
        expect(find.text('Menunggu verifikasi'), findsWidgets);
      },
    );

    testWidgets('cancel unpaid transaction asks confirmation', (tester) async {
      final api = _api()
        ..on(
          'POST',
          '$_p/payments/manual',
          (_) => FakeResponse.ok(manualTxJson()),
        )
        ..on(
          'POST',
          '$_p/transactions/TX1/cancel',
          (_) => FakeResponse.ok(manualTxJson(status: 'cancelled')),
        );
      await pumpApp(tester, const PayScreen(invoiceId: 'INV1'), api: api);
      await tester.tap(find.text('Buat instruksi transfer'));
      await tester.pumpAndSettle();
      await tester.ensureVisible(find.text('Batalkan transaksi'));
      await tester.tap(find.text('Batalkan transaksi'));
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Konfirmasi'));
      await tester.pumpAndSettle();
      expect(
        api.requests.any((r) => r.path == '$_p/transactions/TX1/cancel'),
        isTrue,
      );
    });

    testWidgets('paid invoice has nothing to pay', (tester) async {
      final api = _api()
        ..get('$_p/invoices/INV1', invoiceJson(status: 'paid', detail: true));
      await pumpApp(tester, const PayScreen(invoiceId: 'INV1'), api: api);
      expect(
        find.text('Tagihan ini tidak memiliki sisa untuk dibayar.'),
        findsOneWidget,
      );
    });
  });

  group('transactions', () {
    testWidgets('lists with status chips and filters', (tester) async {
      await pumpApp(tester, const TransactionsScreen(), api: _api());
      expect(find.text('TRX-20261018-001'), findsOneWidget);
      expect(find.text('TRX-20261018-002'), findsOneWidget);
      await tester.ensureVisible(find.widgetWithText(ChoiceChip, 'Berhasil'));
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(ChoiceChip, 'Berhasil'));
      await tester.pumpAndSettle();
      expect(find.text('TRX-20261018-001'), findsNothing);
      expect(find.text('TRX-20261018-002'), findsOneWidget);
    });

    testWidgets('successful tx offers receipt', (tester) async {
      await pumpApp(tester, const TransactionsScreen(), api: _api());
      await tester.tap(find.text('TRX-20261018-002'));
      await tester.pumpAndSettle();
      expect(find.textContaining('KW-1'), findsOneWidget);
    });

    testWidgets('empty state', (tester) async {
      final api = FakeApi()..get('$_p/transactions', [], meta: _meta);
      await pumpApp(tester, const TransactionsScreen(), api: api);
      expect(find.text('Belum ada transaksi'), findsOneWidget);
    });
  });

  group('deposit, statement, ukt, awards', () {
    testWidgets('deposit shows balance, entries, refunds; refund validates', (
      tester,
    ) async {
      final api = _api()
        ..on(
          'POST',
          '$_p/refunds',
          (_) => FakeResponse.ok({}, message: 'Pengajuan dikirim'),
        );
      await pumpApp(tester, const DepositScreen(), api: api);
      expect(find.text('Rp 300.000'), findsWidgets);
      expect(find.text('RF-001'), findsOneWidget);
      await tester.tap(find.text('Ajukan pengembalian dana'));
      await tester.pumpAndSettle();
      await tester.tap(
        find.widgetWithText(FilledButton, 'Ajukan pengembalian dana').last,
      );
      await tester.pumpAndSettle();
      expect(find.text('Nominal harus lebih dari nol.'), findsOneWidget);
      expect(api.requests.where((r) => r.method == 'POST'), isEmpty);
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Nominal pengembalian'),
        '100000',
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Keterangan (min. 10 karakter)'),
        'Kelebihan bayar semester lalu',
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Bank tujuan'),
        'BCA',
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Nomor rekening (6-20 digit)'),
        '123',
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Nama pemilik rekening'),
        'Andi',
      );
      await tester.tap(
        find.widgetWithText(FilledButton, 'Ajukan pengembalian dana').last,
      );
      await tester.pumpAndSettle();
      expect(find.text('Nomor rekening 6-20 digit angka.'), findsOneWidget);
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Nomor rekening (6-20 digit)'),
        '1234567890',
      );
      await tester.tap(
        find.widgetWithText(FilledButton, 'Ajukan pengembalian dana').last,
      );
      await tester.pumpAndSettle();
      final post = api.requests.firstWhere((r) => r.method == 'POST');
      expect(post.body, containsPair('beneficiary_account', '1234567890'));
      expect(post.body, containsPair('reason_code', 'OVERPAYMENT'));
      expect(post.body, containsPair('beneficiary_relation', 'self'));
    });

    testWidgets('statement lists ledger entries', (tester) async {
      await pumpApp(tester, const StatementScreen(), api: _api());
      expect(find.text('Tagihan'), findsOneWidget);
      expect(find.text('Rp 2.000.000'), findsWidgets);
    });

    testWidgets('ukt shows current group and request form requires evidence', (
      tester,
    ) async {
      final api = _api();
      await pumpApp(tester, const UktScreen(), api: api);
      expect(find.text('UKT 3'), findsOneWidget);
      await tester.tap(find.text('Ajukan peninjauan UKT'));
      await tester.pumpAndSettle();
      await tester.enterText(
        find.byType(TextFormField).last,
        'Penghasilan orang tua menurun drastis sejak tahun ini',
      );
      await tester.tap(
        find.widgetWithText(FilledButton, 'Ajukan peninjauan UKT').last,
      );
      await tester.pumpAndSettle();
      expect(find.text('Lampirkan bukti pendukung.'), findsOneWidget);
      expect(api.requests.where((r) => r.method == 'POST'), isEmpty);
    });

    testWidgets('awards list + pending scholarships entry', (tester) async {
      await pumpApp(tester, const AwardsScreen(), api: _api());
      expect(find.text('KIP Kuliah'), findsWidgets);
      expect(find.text('Aktif'), findsOneWidget);
      expect(find.text('Pendaftaran beasiswa'), findsOneWidget);
    });
  });

  group('clearance', () {
    testWidgets(
      'shows blocked services, reason, shortfall, dispensation status',
      (tester) async {
        await pumpApp(tester, const ClearanceScreen(), api: _api());
        expect(find.text('Belum bebas tanggungan'), findsOneWidget);
        expect(find.textContaining('Pengisian KRS: Diblokir'), findsOneWidget);
        expect(
          find.textContaining('Kewajiban keuangan belum terpenuhi'),
          findsOneWidget,
        );
        await _scroll(tester, find.text('Rp 500.000').last);
        expect(find.text('Menunggu keputusan'), findsOneWidget);
      },
    );

    testWidgets('no blocks shows all clear', (tester) async {
      final api = _api()..get('$_p/summary', summaryJson(blocked: false));
      await pumpApp(tester, const ClearanceScreen(), api: api);
      expect(find.textContaining('Tidak ada cekal keuangan'), findsOneWidget);
    });

    testWidgets('dispensation form validates and posts', (tester) async {
      final api = _api()
        ..on(
          'POST',
          '$_p/blocks/B1/dispensations',
          (_) => FakeResponse.ok({}, message: 'Pengajuan dispensasi dikirim'),
        );
      await pumpApp(tester, const ClearanceScreen(), api: api);
      final btn = find.text('Ajukan dispensasi');
      await _scroll(tester, btn);
      await tester.tap(btn);
      await tester.pumpAndSettle();
      await tester.tap(
        find.widgetWithText(FilledButton, 'Ajukan dispensasi').last,
      );
      await tester.pumpAndSettle();
      expect(find.text('Tanggal wajib diisi.'), findsOneWidget);
      await tester.tap(find.textContaining('Berlaku sampai'));
      await tester.pumpAndSettle();
      await tester.tap(
        find
            .descendant(
              of: find.byType(Dialog),
              matching: find.byType(TextButton),
            )
            .last,
      );
      await tester.pumpAndSettle();
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Alasan (min. 20 karakter)'),
        'Menunggu pencairan beasiswa dari yayasan',
      );
      await tester.tap(
        find.widgetWithText(FilledButton, 'Ajukan dispensasi').last,
      );
      await tester.pumpAndSettle();
      final post = api.requests.firstWhere((r) => r.method == 'POST');
      expect(post.body, containsPair('service', 'krs'));
      expect((post.body as Map)['valid_until'], isNotNull);
      expect(find.text('Pengajuan dispensasi dikirim'), findsOneWidget);
    });
  });
}
