import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/student_services/domain/student_request.dart';
import 'package:civitasone/features/student_services/presentation/screens/request_detail_screen.dart';
import 'package:civitasone/features/student_services/presentation/screens/request_form_screen.dart';
import 'package:civitasone/features/student_services/presentation/screens/requests_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';
import 'fixtures.dart';

const _meta = {'current_page': 1, 'per_page': 20, 'total': 2, 'last_page': 1};

FakeApi _listApi() => FakeApi()
  ..get('/student/requests', [
    requestJson(),
    requestJson(
      id: '01REQ2',
      type: 'leave',
      status: 'submitted',
      title: 'Cuti semester ganjil',
      payload: {},
      submittedAt: '2026-10-02T03:00:00+00:00',
    ),
  ], meta: _meta);

void main() {
  group('list', () {
    testWidgets('renders requests, filters and opens FAB/detail', (t) async {
      await pumpApp(t, const RequestsScreen(), api: _listApi());
      expect(find.text('Surat aktif kuliah untuk beasiswa'), findsOneWidget);
      expect(find.text('Cuti semester ganjil'), findsOneWidget);
      expect(find.text('Sedang ditinjau'), findsWidgets);

      await t.tap(find.widgetWithText(ChoiceChip, 'Draft'));
      await t.pumpAndSettle();
      expect(find.text('Cuti semester ganjil'), findsNothing);
      expect(find.text('Surat aktif kuliah untuk beasiswa'), findsOneWidget);

      await t.ensureVisible(find.widgetWithText(ChoiceChip, 'Ditolak'));
      await t.tap(find.widgetWithText(ChoiceChip, 'Ditolak'));
      await t.pumpAndSettle();
      expect(find.text('Tidak ada pengajuan'), findsOneWidget);

      await t.ensureVisible(find.widgetWithText(ChoiceChip, 'Semua'));
      await t.tap(find.widgetWithText(ChoiceChip, 'Semua'));
      await t.pumpAndSettle();
      await t.tap(find.text('Cuti semester ganjil'));
      await t.pumpAndSettle();
      expect(find.text('route:/s/requests/01REQ2'), findsOneWidget);
    });

    testWidgets('FAB navigates to the new request form', (t) async {
      await pumpApp(t, const RequestsScreen(), api: _listApi());
      await t.tap(find.text('Pengajuan baru'));
      await t.pumpAndSettle();
      expect(find.text('route:/s/requests/new'), findsOneWidget);
    });

    testWidgets('empty state', (t) async {
      await pumpApp(
        t,
        const RequestsScreen(),
        api: FakeApi()..get('/student/requests', [], meta: _meta),
      );
      expect(find.text('Belum ada pengajuan'), findsOneWidget);
    });

    testWidgets('error state offers retry', (t) async {
      final failing = FakeApi()
        ..on(
          'GET',
          '/student/requests',
          (_) => FakeResponse.error(500, 'boom'),
        );
      await pumpApp(t, const RequestsScreen(), api: failing);
      expect(find.text('Coba lagi'), findsOneWidget);
    });

    testWidgets('English dark mode on small phone', (t) async {
      await pumpApp(
        t,
        const RequestsScreen(),
        api: _listApi(),
        language: AppLanguage.en,
        themeMode: ThemeMode.dark,
        size: TestScreens.smallPhone,
      );
      expect(find.text('My requests'), findsOneWidget);
      expect(find.text('Under review'), findsWidgets);
      expect(t.takeException(), isNull);
    });
  });

  group('detail', () {
    FakeApi detailApi(Map<String, dynamic> json) =>
        FakeApi()..get('/student/requests/01REQ1', json);

    testWidgets('approved letter shows timeline and download', (t) async {
      final api = detailApi(
        requestJson(
          status: 'approved',
          hasLetter: true,
          decisionNote: 'Silakan ambil surat',
          history: [
            {
              'event': 'submitted',
              'description': 'Pengajuan dikirim',
              'created_at': '2026-10-02T03:00:00+00:00',
            },
            {
              'event': 'approved',
              'description': 'Disetujui Bagian Akademik',
              'created_at': '2026-10-03T03:00:00+00:00',
            },
          ],
        ),
      );
      await pumpApp(
        t,
        const RequestDetailScreen(requestId: '01REQ1'),
        api: api,
        size: TestScreens.smallPhone,
      );
      expect(
        find.text('Disetujui Bagian Akademik', skipOffstage: false),
        findsOneWidget,
      );
      expect(
        find.text('Silakan ambil surat', skipOffstage: false),
        findsOneWidget,
      );
      expect(
        find.text('Unduh surat (PDF)', skipOffstage: false),
        findsOneWidget,
      );
      expect(find.text('Batalkan pengajuan'), findsNothing);
      expect(t.takeException(), isNull);
    });

    testWidgets('draft: submit calls endpoint and shows backend 409', (
      t,
    ) async {
      final api = detailApi(requestJson())
        ..on(
          'POST',
          '/student/requests/{id}/submit',
          (_) => FakeResponse.error(
            409,
            'Surat keterangan aktif kuliah hanya untuk mahasiswa berstatus Aktif.',
          ),
        );
      await pumpApp(
        t,
        const RequestDetailScreen(requestId: '01REQ1'),
        api: api,
      );
      await t.ensureVisible(find.text('Kirim sekarang'));
      await t.tap(find.text('Kirim sekarang'));
      await t.pumpAndSettle();
      expect(
        find.text(
          'Surat keterangan aktif kuliah hanya untuk mahasiswa berstatus Aktif.',
        ),
        findsOneWidget,
      );
      expect(
        api.requests.any(
          (r) => r.method == 'POST' && r.path.endsWith('/submit'),
        ),
        isTrue,
      );
    });

    testWidgets('submitted: cancel requires confirmation', (t) async {
      final api = detailApi(requestJson(status: 'submitted'))
        ..on(
          'POST',
          '/student/requests/{id}/cancel',
          (_) => FakeResponse.ok(requestJson(status: 'cancelled')),
        );
      await pumpApp(
        t,
        const RequestDetailScreen(requestId: '01REQ1'),
        api: api,
      );
      expect(find.text('Ubah draft'), findsNothing);
      await t.ensureVisible(find.text('Batalkan pengajuan'));
      await t.tap(find.text('Batalkan pengajuan'));
      await t.pumpAndSettle();
      expect(find.text('Batalkan pengajuan?'), findsOneWidget);
      await t.tap(find.text('Ya, batalkan'));
      await t.pumpAndSettle();
      expect(api.requests.where((r) => r.path.endsWith('/cancel')).length, 1);
    });

    testWidgets('error state offers retry', (t) async {
      await pumpApp(
        t,
        const RequestDetailScreen(requestId: 'x'),
        api: FakeApi(),
      );
      expect(find.text('Coba lagi'), findsOneWidget);
    });

    testWidgets('rejected shows resubmit-pending note', (t) async {
      await pumpApp(
        t,
        const RequestDetailScreen(requestId: '01REQ1'),
        api: detailApi(requestJson(status: 'rejected')),
      );
      await t.ensureVisible(find.text('Kirim ulang pengajuan'));
      expect(find.text('Kirim ulang pengajuan'), findsOneWidget);
    });
  });

  group('form', () {
    FakeApi formApi() => FakeApi()
      ..get('/student/requests/options', optionsJson)
      ..on(
        'POST',
        '/student/requests',
        (_) => FakeResponse.ok(requestJson(id: '01NEW')),
      )
      ..on(
        'POST',
        '/student/requests/{id}/submit',
        (_) => FakeResponse.ok(requestJson(id: '01NEW', status: 'submitted')),
      );

    testWidgets('validates title before sending', (t) async {
      final api = formApi();
      await pumpApp(t, const RequestFormScreen(), api: api);
      await t.ensureVisible(find.text('Simpan draft'));
      await t.tap(find.text('Simpan draft'));
      await t.pumpAndSettle();
      expect(find.text('Wajib diisi'), findsOneWidget);
      expect(api.requests.where((r) => r.method == 'POST'), isEmpty);
    });

    testWidgets('data change requires a field and sends payload.changes', (
      t,
    ) async {
      final api = formApi();
      await pumpApp(t, const RequestFormScreen(), api: api);
      await t.tap(find.byType(DropdownButtonFormField<RequestType>).first);
      await t.pumpAndSettle();
      await t.tap(find.text('Perubahan data').last);
      await t.pumpAndSettle();
      await t.enterText(
        find.widgetWithText(TextFormField, 'Judul'),
        'Perbaikan nama lengkap',
      );
      await t.ensureVisible(find.text('Simpan draft'));
      await t.tap(find.text('Simpan draft'));
      await t.pumpAndSettle();
      expect(
        find.text('Pilih dan isi minimal satu data yang ingin diubah.'),
        findsOneWidget,
      );

      await t.tap(find.text('Nama'));
      await t.pumpAndSettle();
      await t.enterText(
        find.widgetWithText(TextFormField, 'Nama'),
        'Andi Pratama S',
      );
      await t.ensureVisible(find.text('Simpan draft'));
      await t.tap(find.text('Simpan draft'));
      await t.pumpAndSettle();
      final post = api.requests.firstWhere(
        (r) => r.method == 'POST' && r.path == '/student/requests',
      );
      expect((post.body as Map)['type'], 'data_change');
      expect((post.body as Map)['payload'], {
        'changes': {'name': 'Andi Pratama S'},
      });
      expect(find.text('route:/s/requests/01NEW'), findsOneWidget);
    });

    testWidgets('letter: draft then submit as two calls', (t) async {
      final api = formApi();
      await pumpApp(t, const RequestFormScreen(), api: api);
      await t.tap(find.byType(DropdownButtonFormField<RequestType>).first);
      await t.pumpAndSettle();
      await t.tap(find.text('Surat keterangan').last);
      await t.pumpAndSettle();
      await t.enterText(
        find.widgetWithText(TextFormField, 'Judul'),
        'Surat aktif kuliah',
      );
      await t.tap(find.byType(DropdownButtonFormField<String>));
      await t.pumpAndSettle();
      await t.tap(find.text('Surat Keterangan Aktif Kuliah').last);
      await t.pumpAndSettle();
      await t.enterText(
        find.widgetWithText(TextFormField, 'Keperluan'),
        'Beasiswa',
      );
      await t.ensureVisible(find.text('Kirim pengajuan'));
      await t.tap(find.text('Kirim pengajuan'));
      await t.pumpAndSettle();
      final calls = api.requests
          .where((r) => r.method == 'POST')
          .map((r) => r.path)
          .toList();
      expect(calls, ['/student/requests', '/student/requests/01NEW/submit']);
      final body =
          api.requests.firstWhere((r) => r.method == 'POST').body as Map;
      expect(body['payload'], {
        'letter_type': 'active_student',
        'purpose': 'Beasiswa',
      });
      expect(find.text('route:/s/requests/01NEW'), findsOneWidget);
    });

    testWidgets('shows 422 field errors from the backend', (t) async {
      final api = FakeApi()
        ..get('/student/requests/options', optionsJson)
        ..on(
          'POST',
          '/student/requests',
          (_) => FakeResponse.error(
            422,
            'Data tidak valid',
            errors: {
              'title': ['Judul pengajuan minimal 5 karakter.'],
            },
          ),
        );
      await pumpApp(
        t,
        const RequestFormScreen(),
        api: api,
        language: AppLanguage.en,
        themeMode: ThemeMode.dark,
        size: TestScreens.smallPhone,
      );
      await t.enterText(find.widgetWithText(TextFormField, 'Title'), 'Abcde');
      await t.ensureVisible(find.text('Save draft'));
      await t.tap(find.text('Save draft'));
      await t.pumpAndSettle();
      expect(find.text('Judul pengajuan minimal 5 karakter.'), findsWidgets);
      expect(t.takeException(), isNull);
    });

    testWidgets('edit draft sends PUT without type', (t) async {
      final api = FakeApi()
        ..get('/student/requests/options', optionsJson)
        ..get('/student/requests/01REQ1', requestJson())
        ..on(
          'PUT',
          '/student/requests/{id}',
          (_) => FakeResponse.ok(requestJson()),
        );
      await pumpApp(t, const RequestEditScreen(requestId: '01REQ1'), api: api);
      await t.enterText(
        find.widgetWithText(TextFormField, 'Judul'),
        'Judul diperbarui',
      );
      await t.ensureVisible(find.text('Simpan draft'));
      await t.tap(find.text('Simpan draft'));
      await t.pumpAndSettle();
      final put = api.requests.firstWhere((r) => r.method == 'PUT');
      expect(put.path, '/student/requests/01REQ1');
      expect((put.body as Map).containsKey('type'), isFalse);
      expect((put.body as Map)['title'], 'Judul diperbarui');
    });
  });
}
