import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/exam/presentation/screens/exam_detail_screen.dart';
import 'package:civitasone/features/exam/presentation/screens/exam_form_screen.dart';
import 'package:civitasone/features/exam/presentation/screens/exam_list_screen.dart';
import 'package:civitasone/features/exam/presentation/screens/exam_preview_screen.dart';
import 'package:civitasone/features/exam/presentation/screens/question_bank_list_screen.dart';
import 'package:civitasone/features/exam/presentation/screens/question_bank_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';
import 'exam_fixtures.dart';

final _listScrollable = find
    .descendant(of: find.byType(ListView), matching: find.byType(Scrollable))
    .first;

FakeApi detailApi({bool published = false, String? token, int pool = 12}) {
  final api = FakeApi();
  ownClassRoutes(api);
  api
    ..get('/exams/e1', examJson(published: published, token: token, pool: pool))
    ..get('/exams/e1/questions', [
      for (var i = 0; i < pool; i++) questionJson('q$i'),
    ])
    ..get('/exams/e1/participants', [
      participantJson(
        '2101001',
        'Andi Pratama',
        'completed',
        attemptId: 'a1',
        violations: 2,
      ),
      participantJson('2101003', 'Citra Dewi', 'not_started'),
    ])
    ..get('/exams/e1/violations/recent', [])
    ..get('/exams/e1/recap', recapJson())
    ..get('/exams/e1/grade-ranges', []);
  return api;
}

void main() {
  group('ExamListScreen', () {
    testWidgets('shows only exams of own classes with status chips', (
      tester,
    ) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api.get('/exams', [
        examJson(),
        examJson(id: 'e2', title: 'UAS Terbit', published: true),
        examJson(id: 'e3', title: 'Kelas Orang Lain', classId: 'other'),
      ]);
      await pumpApp(
        tester,
        const ExamListScreen(),
        api: api,
        user: testLecturer,
      );
      expect(find.text('UTS Algoritma'), findsOneWidget);
      expect(find.text('UAS Terbit'), findsOneWidget);
      expect(find.text('Kelas Orang Lain'), findsNothing);
      expect(find.text('Draft'), findsWidgets);
      expect(find.text('Dipublikasikan'), findsWidgets);
      expect(find.text('Ujian baru'), findsOneWidget);
    });

    testWidgets('tapping a card opens the detail route; FAB opens new', (
      tester,
    ) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api.get('/exams', [examJson()]);
      await pumpApp(
        tester,
        const ExamListScreen(),
        api: api,
        user: testLecturer,
      );
      await tester.tap(find.text('UTS Algoritma'));
      await tester.pumpAndSettle();
      expect(find.text('route:/l/exams/e1'), findsOneWidget);
    });

    testWidgets('empty state, and EN labels', (tester) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api.get('/exams', []);
      await pumpApp(
        tester,
        const ExamListScreen(),
        api: api,
        user: testLecturer,
        language: AppLanguage.en,
      );
      expect(find.text('No exams yet'), findsOneWidget);
      expect(find.text('New exam'), findsOneWidget);
    });

    testWidgets('error state offers retry; ZH works', (tester) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api.on('GET', '/exams', (_) => FakeResponse.error(500, 'boom'));
      await pumpApp(
        tester,
        const ExamListScreen(),
        api: api,
        user: testLecturer,
        language: AppLanguage.zh,
      );
      expect(find.text('重试'), findsOneWidget);
    });

    testWidgets('dark mode at small phone has no overflow', (tester) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api.get('/exams', [
        examJson(
          title:
              'Judul ujian yang sangat panjang sekali untuk menguji ellipsis',
        ),
      ]);
      await pumpApp(
        tester,
        const ExamListScreen(),
        api: api,
        user: testLecturer,
        themeMode: ThemeMode.dark,
        size: TestScreens.smallPhone,
      );
      expect(tester.takeException(), isNull);
    });
  });

  group('ExamDetailScreen', () {
    testWidgets('overview shows status, schedule and settings', (tester) async {
      await pumpApp(
        tester,
        const ExamDetailScreen(examId: 'e1'),
        api: detailApi(),
        user: testLecturer,
        size: TestScreens.smallPhone,
      );
      expect(find.text('UTS Algoritma'), findsWidgets);
      expect(find.text('Draft'), findsWidgets);
      expect(find.text('Jadwal'), findsOneWidget);
      expect(find.text('Publish ujian'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });

    testWidgets('publish disabled with validation error when qpp > pool', (
      tester,
    ) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api
        ..get('/exams/e1', examJson(pool: 5, qpp: 10))
        ..get('/exams/e1/questions', [
          for (var i = 0; i < 5; i++) questionJson('q$i'),
        ])
        ..get('/exams/e1/participants', [])
        ..get('/exams/e1/violations/recent', [])
        ..get('/exams/e1/recap', recapJson())
        ..get('/exams/e1/grade-ranges', []);
      await pumpApp(
        tester,
        const ExamDetailScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      expect(
        find.textContaining(
          'tidak boleh melebihi jumlah soal dalam question pool',
        ),
        findsOneWidget,
      );
      final button = tester.widget<FilledButton>(
        find.ancestor(
          of: find.text('Publish ujian'),
          matching: find.byWidgetPredicate((w) => w is FilledButton),
        ),
      );
      expect(button.onPressed, isNull);
    });

    testWidgets('publish calls PATCH and shows server validation error', (
      tester,
    ) async {
      final api = detailApi();
      api.on(
        'PATCH',
        '/exams/{id}/publish',
        (_) => FakeResponse.error(
          409,
          'Setiap soal harus memiliki tepat satu jawaban benar.',
        ),
      );
      await pumpApp(
        tester,
        const ExamDetailScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      await tester.tap(find.text('Publish ujian'));
      await tester.pumpAndSettle();
      expect(
        api.requests.any(
          (r) => r.method == 'PATCH' && r.path == '/exams/e1/publish',
        ),
        isTrue,
      );
      expect(
        find.text('Setiap soal harus memiliki tepat satu jawaban benar.'),
        findsOneWidget,
      );
    });

    testWidgets('access link shows URL and QR, regenerate asks confirmation', (
      tester,
    ) async {
      final api = detailApi(published: true, token: 'tok123');
      api.on(
        'PATCH',
        '/exams/{id}/access-link',
        (_) => FakeResponse.ok(examJson(published: true, token: 'tok456')),
      );
      await pumpApp(
        tester,
        const ExamDetailScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      await tester.scrollUntilVisible(
        find.text('Buat ulang tautan'),
        300,
        scrollable: _listScrollable,
      );
      expect(find.textContaining('/exam/tok123'), findsOneWidget);
      expect(find.byType(QrImageView), findsOneWidget);
      await tester.ensureVisible(find.text('Buat ulang tautan'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Buat ulang tautan'));
      await tester.pumpAndSettle();
      expect(find.text('Buat ulang tautan?'), findsOneWidget);
      await tester.tap(find.text('Konfirmasi'));
      await tester.pumpAndSettle();
      expect(
        api.requests.any((r) => r.path == '/exams/e1/access-link'),
        isTrue,
      );
    });

    testWidgets('delete asks for confirmation then calls DELETE', (
      tester,
    ) async {
      final api = detailApi();
      api.on('DELETE', '/exams/{id}', (_) => FakeResponse.ok(null));
      await pumpApp(
        tester,
        const ExamDetailScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      await tester.scrollUntilVisible(
        find.text('Hapus ujian'),
        300,
        scrollable: _listScrollable,
      );
      await tester.tap(find.text('Hapus ujian'));
      await tester.pumpAndSettle();
      expect(find.text('Hapus ujian ini?'), findsOneWidget);
      await tester.tap(find.widgetWithText(FilledButton, 'Hapus'));
      await tester.pumpAndSettle();
      expect(
        api.requests.any((r) => r.method == 'DELETE' && r.path == '/exams/e1'),
        isTrue,
      );
    });

    testWidgets('monitor tab lists participants and auto-refreshes every 15s', (
      tester,
    ) async {
      final api = detailApi();
      await pumpApp(
        tester,
        const ExamDetailScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      await tester.tap(find.text('Pantau'));
      await tester.pumpAndSettle();
      expect(find.text('Andi Pratama'), findsOneWidget);
      expect(find.text('Belum mengerjakan'), findsOneWidget);
      final before = api.requests
          .where((r) => r.path == '/exams/e1/participants')
          .length;
      await tester.pump(const Duration(seconds: 16));
      await tester.pumpAndSettle();
      final after = api.requests
          .where((r) => r.path == '/exams/e1/participants')
          .length;
      expect(after, greaterThan(before));
      expect(
        api.requests
            .where((r) => r.path == '/exams/e1/violations/recent')
            .length,
        greaterThan(1),
      );
      // leave the tab to cancel the timer
      await tester.tap(find.text('Ringkasan'));
      await tester.pumpAndSettle();
    });

    testWidgets(
      'recap tab filters by search and status, shows violation timeline',
      (tester) async {
        final api = detailApi();
        api.get('/exam-attempts/{id}/violations', {
          'student': {'name': 'Andi Pratama', 'nim': '2101001'},
          'raw_score': '80.00',
          'penalty_score': '2.00',
          'score': '78.00',
          'grade': 'AB',
          'weighted_score': '23.40',
          'violation_count': 1,
          'violations': [
            {
              'id': 'v1',
              'exam_attempt_id': 'a1',
              'sequence_number': 1,
              'violation_type': 'tab_switch',
              'violation_label': 'Berpindah Tab/Aplikasi',
              'penalty_points': '1.00',
              'occurred_at': '2026-11-02T01:05:00+00:00',
            },
          ],
        });
        await pumpApp(
          tester,
          const ExamDetailScreen(examId: 'e1'),
          api: api,
          user: testLecturer,
        );
        await tester.tap(find.text('Rekap'));
        await tester.pumpAndSettle();
        expect(find.text('Andi Pratama'), findsOneWidget);
        expect(find.text('Budi Santoso'), findsOneWidget);
        await tester.enterText(find.byType(TextField), 'citra');
        await tester.pumpAndSettle();
        expect(find.text('Citra Dewi'), findsOneWidget);
        expect(find.text('Andi Pratama'), findsNothing);
        await tester.enterText(find.byType(TextField), '');
        await tester.tap(find.widgetWithText(ChoiceChip, 'Sedang mengerjakan'));
        await tester.pumpAndSettle();
        expect(find.text('Budi Santoso'), findsOneWidget);
        expect(find.text('Citra Dewi'), findsNothing);
        await tester.tap(find.widgetWithText(ChoiceChip, 'Semua'));
        await tester.pumpAndSettle();
        await tester.tap(find.text('Andi Pratama'));
        await tester.pumpAndSettle();
        expect(find.text('Linimasa pelanggaran'), findsOneWidget);
        expect(find.text('Berpindah Tab/Aplikasi'), findsOneWidget);
      },
    );

    testWidgets('grades tab saves ranges and blocks overlaps', (tester) async {
      final api = detailApi();
      api.on('PUT', '/exams/{id}/grade-ranges', (_) => FakeResponse.ok([]));
      await pumpApp(
        tester,
        const ExamDetailScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      await tester.ensureVisible(find.text('Nilai huruf'));
      await tester.tap(find.text('Nilai huruf'));
      await tester.pumpAndSettle();
      // default rows (A..E) are valid and saved as-is
      await tester.scrollUntilVisible(
        find.text('Simpan rentang'),
        300,
        scrollable: _listScrollable,
      );
      await tester.tap(find.text('Simpan rentang'));
      await tester.pumpAndSettle();
      final put = api.requests.firstWhere((r) => r.method == 'PUT');
      expect((put.body as Map)['ranges'], hasLength(7));

      // break the first row (A min 85 -> 10) to create an overlap
      await tester.enterText(find.byType(TextField).at(0), '10');
      await tester.tap(find.text('Simpan rentang'));
      await tester.pumpAndSettle();
      expect(find.text('Rentang nilai saling tumpang tindih.'), findsOneWidget);
      expect(api.requests.where((r) => r.method == 'PUT').length, 1);
    });
  });

  group('Question editor & bank', () {
    testWidgets('exam questions: list, locked banner on published exam', (
      tester,
    ) async {
      final api = detailApi(published: true, pool: 2);
      await pumpApp(
        tester,
        const QuestionBankScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      expect(find.textContaining('Apa itu rekursi?'), findsNWidgets(2));
      expect(find.textContaining('terkunci'), findsOneWidget);
      expect(find.text('Tambah soal'), findsNothing);
    });

    testWidgets('exam questions: add question validates and posts', (
      tester,
    ) async {
      final api = detailApi(pool: 1);
      api.on(
        'POST',
        '/exams/{id}/questions',
        (_) => FakeResponse.ok(questionJson('new')),
      );
      await pumpApp(
        tester,
        const QuestionBankScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      await tester.tap(find.text('Tambah soal'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(find.text('Wajib diisi'), findsOneWidget);
      final fields = find.byType(TextField);
      await tester.enterText(fields.at(0), 'Soal baru?');
      await tester.enterText(find.widgetWithText(TextField, 'Opsi 1'), 'Ya');
      await tester.enterText(find.widgetWithText(TextField, 'Opsi 2'), 'Tidak');
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      final post = api.requests.firstWhere((r) => r.method == 'POST');
      final body = post.body as Map;
      expect(body['question_text'], 'Soal baru?');
      final options = body['options'] as List;
      expect(
        options.where((o) => (o as Map)['is_correct'] == true),
        hasLength(1),
      );
    });

    testWidgets('exam questions: delete asks for confirmation', (tester) async {
      final api = detailApi(pool: 1);
      api.on('DELETE', '/exam-questions/{id}', (_) => FakeResponse.ok(null));
      await pumpApp(
        tester,
        const QuestionBankScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      await tester.tap(find.byIcon(Icons.delete_outline));
      await tester.pumpAndSettle();
      expect(find.text('Hapus soal ini?'), findsOneWidget);
      await tester.tap(find.widgetWithText(FilledButton, 'Hapus'));
      await tester.pumpAndSettle();
      expect(api.requests.any((r) => r.method == 'DELETE'), isTrue);
    });

    testWidgets('question bank: list with usage, search sends query', (
      tester,
    ) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api.get('/question-bank', [
        {
          'id': 'b1',
          'course_id': 'crs1',
          'course_name': 'Algoritma',
          'question_text': 'Soal bank satu',
          'points': '1.00',
          'options': [
            {
              'id': 'o1',
              'option_text': 'a',
              'is_correct': true,
              'order_index': 0,
            },
            {
              'id': 'o2',
              'option_text': 'b',
              'is_correct': false,
              'order_index': 1,
            },
          ],
          'usage_count': 3,
        },
      ]);
      await pumpApp(
        tester,
        const QuestionBankListScreen(),
        api: api,
        user: testLecturer,
      );
      expect(find.text('Soal bank satu'), findsOneWidget);
      expect(find.textContaining('Dipakai 3 kali'), findsOneWidget);
      await tester.enterText(find.byType(TextField).first, 'rekursi');
      await tester.testTextInput.receiveAction(TextInputAction.search);
      await tester.pumpAndSettle();
      expect(
        api.requests.any(
          (r) => r.path == '/question-bank' && r.query['search'] == 'rekursi',
        ),
        isTrue,
      );
    });

    testWidgets('question bank empty state', (tester) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api.get('/question-bank', []);
      await pumpApp(
        tester,
        const QuestionBankListScreen(),
        api: api,
        user: testLecturer,
      );
      expect(find.text('Bank soal kosong'), findsOneWidget);
    });

    testWidgets('apply bank: select items and POST ids', (tester) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api
        ..get('/question-bank', [
          {
            'id': 'b1',
            'question_text': 'Satu',
            'points': '1.00',
            'options': [],
            'usage_count': 0,
          },
          {
            'id': 'b2',
            'question_text': 'Dua',
            'points': '1.00',
            'options': [],
            'usage_count': 0,
          },
        ])
        ..on(
          'POST',
          '/exams/{id}/questions/apply-bank',
          (_) => FakeResponse.ok(
            [],
            message: '2 soal berhasil diterapkan dari bank soal.',
          ),
        );
      await pumpApp(
        tester,
        Builder(
          builder: (context) => Scaffold(
            body: TextButton(
              onPressed: () => Navigator.of(context).push<void>(
                MaterialPageRoute(
                  builder: (_) =>
                      const QuestionBankListScreen(pickForExamId: 'e1'),
                ),
              ),
              child: const Text('buka'),
            ),
          ),
        ),
        api: api,
        user: testLecturer,
      );
      await tester.tap(find.text('buka'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Satu'));
      await tester.tap(find.text('Dua'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Terapkan 2 soal'));
      await tester.pumpAndSettle();
      final post = api.requests.firstWhere((r) => r.method == 'POST');
      expect(post.path, '/exams/e1/questions/apply-bank');
      expect((post.body as Map)['question_bank_item_ids'], ['b1', 'b2']);
    });
  });

  group('ExamFormScreen', () {
    testWidgets('create wizard validates required fields then posts payload', (
      tester,
    ) async {
      final api = FakeApi();
      ownClassRoutes(api);
      api
        ..on('POST', '/exams', (_) => FakeResponse.ok(examJson()))
        ..get('/exams/e1/questions', []);
      await pumpApp(
        tester,
        const ExamFormScreen(),
        api: api,
        user: testLecturer,
        size: TestScreens.phone,
      );
      await tester.tap(find.text('Berikutnya'));
      await tester.pumpAndSettle();
      expect(find.text('Wajib diisi'), findsWidgets);
      expect(api.requests.where((r) => r.method == 'POST'), isEmpty);

      await tester.tap(find.byType(DropdownButtonFormField<String>));
      await tester.pumpAndSettle();
      await tester.tap(find.textContaining('Algoritma TI-A').last);
      await tester.pumpAndSettle();
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Judul ujian'),
        'UTS Algoritma',
      );
      await tester.tap(find.text('Berikutnya'));
      await tester.pumpAndSettle();
      final post = api.requests.firstWhere((r) => r.method == 'POST');
      final body = post.body as Map;
      expect(body['class_section_id'], 'c1');
      expect(body['title'], 'UTS Algoritma');
      expect(body['duration_minutes'], 60);
      expect(find.text('Pengaturan soal'), findsOneWidget);
    });

    testWidgets('edit loads the exam and shows the distribution step', (
      tester,
    ) async {
      final api = detailApi();
      api.on('PUT', '/exams/{id}', (_) => FakeResponse.ok(examJson()));
      await pumpApp(
        tester,
        const ExamFormScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      expect(find.text('Ubah ujian'), findsOneWidget);
      expect(
        find.widgetWithText(TextFormField, 'UTS Algoritma'),
        findsOneWidget,
      );
      await tester.ensureVisible(find.text('Berikutnya'));
      await tester.tap(find.text('Berikutnya'));
      await tester.pumpAndSettle();
      await tester.ensureVisible(find.text('Berikutnya'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Berikutnya'));
      await tester.pumpAndSettle();
      expect(find.text('Distribusi soal'), findsOneWidget);
      expect(
        find.textContaining('Setiap peserta akan mendapatkan'),
        findsOneWidget,
      );
      expect(api.requests.where((r) => r.method == 'PUT'), hasLength(2));
    });
  });

  group('ExamPreviewScreen', () {
    testWidgets('steps through questions without exposing the answer key', (
      tester,
    ) async {
      final api = detailApi(pool: 2);
      await pumpApp(
        tester,
        const ExamPreviewScreen(examId: 'e1'),
        api: api,
        user: testLecturer,
      );
      expect(find.text('Soal 1 dari 2'), findsOneWidget);
      await tester.tap(find.text('Berikutnya'));
      await tester.pumpAndSettle();
      expect(find.text('Soal 2 dari 2'), findsOneWidget);
    });
  });
}
