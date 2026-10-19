import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/core/network/api_failure.dart';
import 'package:civitasone/features/exam_student/data/exam_student_api.dart';
import 'package:civitasone/features/exam_student/domain/exam_models.dart';
import 'package:civitasone/features/exam_student/presentation/screens/exam_attempt_screen.dart';
import 'package:civitasone/features/exam_student/presentation/screens/exam_detail_screen.dart';
import 'package:civitasone/features/exam_student/presentation/screens/exam_list_screen.dart';
import 'package:civitasone/features/exam_student/presentation/screens/exam_result_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

Map<String, dynamic> examJson({
  String status = 'available',
  bool allowBack = true,
  bool resultVisible = false,
  String? latest,
  int used = 0,
}) => {
  'id': 'E1',
  'title': 'UTS Basis Data',
  'course_name': 'Basis Data',
  'lecturer_name': 'Dr. Maria',
  'duration_minutes': 60,
  'starts_at': '2026-10-01T01:00:00+00:00',
  'ends_at': '2099-10-01T01:00:00+00:00',
  'questions_per_participant': 2,
  'allow_back_navigation': allowBack,
  'max_attempts': 2,
  'attempts_used': used,
  'status': status,
  'result_visible': resultVisible,
  'score': resultVisible ? '80.00' : null,
  'latest_attempt_id': latest,
};

Map<String, dynamic> attemptJson({
  String status = 'in_progress',
  int violations = 0,
  Map<String, String?> selected = const {},
}) => {
  'id': 'A1',
  'exam_id': 'E1',
  'krs_item_id': 'K1',
  'attempt_number': 1,
  'status': status,
  'started_at': DateTime.now()
      .subtract(const Duration(minutes: 1))
      .toUtc()
      .toIso8601String(),
  'submitted_at': null,
  'score': null,
  'raw_score': null,
  'penalty_score': violations.toString(),
  'violation_count': violations,
  'grade': null,
  'weighted_score': null,
  'result_visible': false,
  'questions': [
    {
      'id': 'Q1',
      'question_text': 'Apa itu SQL?',
      'points': '10.00',
      'options': [
        {'id': 'O1', 'option_text': 'Bahasa query'},
        {'id': 'O2', 'option_text': 'Sistem operasi'},
      ],
      'selected_option_id': selected['Q1'],
    },
    {
      'id': 'Q2',
      'question_text': 'Apa itu PK?',
      'points': '10.00',
      'options': [
        {'id': 'O3', 'option_text': 'Primary key'},
      ],
      'selected_option_id': selected['Q2'],
    },
  ],
};

Map<String, dynamic> resultJson() => {
  'attempt': {
    'id': 'A1',
    'attempt_number': 1,
    'started_at': '2026-10-10T01:00:00+00:00',
    'submitted_at': '2026-10-10T01:30:00+00:00',
    'duration_seconds': 1800,
  },
  'student': {'name': 'Andi', 'nim': '2101001'},
  'exam': {'title': 'UTS Basis Data', 'course_name': 'Basis Data'},
  'summary': {
    'total_questions': 2,
    'correct_answers': 1,
    'wrong_answers': 1,
    'score': '49.00',
    'percentage': '49.00',
    'raw_score': '50.00',
    'penalty_score': '1.00',
    'violation_count': 1,
    'grade': 'C',
    'weighted_score': null,
  },
  'questions': [
    {
      'number': 1,
      'question_text': 'Apa itu SQL?',
      'options': [
        {'id': 'O1', 'option_text': 'Bahasa query'},
        {'id': 'O2', 'option_text': 'Sistem operasi'},
      ],
      'selected_option_id': 'O2',
      'correct_option_id': 'O1',
      'is_correct': false,
      'explanation': null,
    },
  ],
};

FakeApi attemptApi({Map<String, dynamic>? attempt}) {
  final api = FakeApi()
    ..get('/student/exam-attempts/A1', attempt ?? attemptJson())
    ..get('/student/exams/E1', examJson(status: 'in_progress'))
    ..on(
      'PUT',
      '/student/exam-attempts/A1/answer',
      (_) => FakeResponse.ok(attemptJson()),
    )
    ..on(
      'PATCH',
      '/student/exam-attempts/A1/submit',
      (_) => FakeResponse.ok(attemptJson(status: 'submitted')),
    )
    ..on(
      'POST',
      '/student/exam-attempts/A1/violations',
      (_) => FakeResponse.ok(attemptJson(violations: 1)),
    );
  return api;
}

void main() {
  group('api / models', () {
    test('exam list parsing and status mapping', () async {
      final api = FakeApi()
        ..get('/student/exams', [
          examJson(status: 'in_progress', latest: 'A1', used: 1),
          examJson(status: 'wat'),
        ]);
      final list = await ExamStudentApi(api.client()).exams();
      expect(list.first.status, StudentExamStatus.inProgress);
      expect(list.first.isResuming, isTrue);
      expect(list.last.status, StudentExamStatus.unknown);
      expect(list.first.durationMinutes, 60);
    });

    test('answer / violation payloads', () async {
      final api = attemptApi();
      final a = ExamStudentApi(api.client());
      await a.answer('A1', questionId: 'Q1', optionId: 'O1');
      await a.violation('A1', ExamViolationType.tabSwitch, metadata: {'x': 1});
      await a.submit('A1');
      expect(api.requests[0].body, {
        'exam_question_id': 'Q1',
        'exam_question_option_id': 'O1',
      });
      expect((api.requests[1].body as Map)['violation_type'], 'tab_switch');
      expect(api.requests[2].method, 'PATCH');
    });

    test('deadline is min(started+duration, ends_at)', () {
      final exam = StudentExam.fromJson(examJson());
      final attempt = ExamAttempt.fromJson(attemptJson());
      final d = computeAttemptDeadline(attempt, exam)!;
      expect(d.difference(attempt.startedAt!), const Duration(minutes: 60));
      final short = StudentExam.fromJson({
        ...examJson(),
        'ends_at': attempt.startedAt!
            .add(const Duration(minutes: 5))
            .toUtc()
            .toIso8601String(),
      });
      expect(
        computeAttemptDeadline(attempt, short)!.difference(attempt.startedAt!),
        const Duration(minutes: 5),
      );
    });

    test('result parsing with decimal strings', () async {
      final api = FakeApi()
        ..get('/student/exam-attempts/A1/result', resultJson());
      final r = await ExamStudentApi(api.client()).result('A1');
      expect(r.score, '49.00');
      expect(r.grade, 'C');
      expect(r.questions.single.correctOptionId, 'O1');
    });
  });

  group('list screen', () {
    testWidgets('renders exams in Indonesian and navigates', (tester) async {
      final api = FakeApi()
        ..get('/student/exams', [
          examJson(
            status: 'completed',
            resultVisible: true,
            latest: 'A1',
            used: 1,
          ),
        ]);
      await pumpApp(tester, const ExamListScreen(), api: api);
      await tester.pumpAndSettle();
      expect(find.text('UTS Basis Data'), findsOneWidget);
      expect(find.text('Selesai'), findsOneWidget);
      expect(find.textContaining('1/2'), findsOneWidget);
      await tester.tap(find.text('UTS Basis Data'));
      await tester.pumpAndSettle();
      expect(find.text('route:/s/exams/E1'), findsOneWidget);
    });

    testWidgets('empty state', (tester) async {
      final api = FakeApi()..get('/student/exams', []);
      await pumpApp(tester, const ExamListScreen(), api: api);
      await tester.pumpAndSettle();
      expect(find.text('Belum ada ujian'), findsOneWidget);
    });

    testWidgets('error state with retry', (tester) async {
      final api = FakeApi()
        ..on('GET', '/student/exams', (_) => FakeResponse.error(500, 'x'));
      await pumpApp(tester, const ExamListScreen(), api: api);
      await tester.pumpAndSettle();
      expect(find.text('Coba lagi'), findsOneWidget);
    });

    testWidgets('small phone dark mode no overflow', (tester) async {
      final api = FakeApi()..get('/student/exams', [examJson()]);
      await pumpApp(
        tester,
        const ExamListScreen(),
        api: api,
        themeMode: ThemeMode.dark,
        size: TestScreens.smallPhone,
      );
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
    });
  });

  group('detail screen', () {
    testWidgets('shows rules, confirms and starts attempt', (tester) async {
      final api = FakeApi()
        ..get('/student/exams/E1', examJson())
        ..on(
          'POST',
          '/student/exams/E1/start',
          (_) => FakeResponse.ok(attemptJson()),
        );
      await pumpApp(tester, const ExamDetailScreen(examId: 'E1'), api: api);
      await tester.pumpAndSettle();
      expect(find.text('Aturan ujian'), findsOneWidget);
      await tester.scrollUntilVisible(
        find.text('Mulai mengerjakan'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.tap(find.text('Mulai mengerjakan'));
      await tester.pumpAndSettle();
      expect(find.text('Mulai ujian sekarang?'), findsOneWidget);
      await tester.tap(
        find.descendant(
          of: find.byType(AlertDialog),
          matching: find.widgetWithText(FilledButton, 'Mulai mengerjakan'),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('route:/s/exam-attempts/A1'), findsOneWidget);
    });

    testWidgets('ACADEMIC_BLOCKED shows banner linking to clearance', (
      tester,
    ) async {
      final api = FakeApi()
        ..get('/student/exams/E1', examJson())
        ..on(
          'POST',
          '/student/exams/E1/start',
          (_) => FakeResponse.error(
            409,
            'Akses ujian dicekal.',
            errors: {
              'code': 'ACADEMIC_BLOCKED',
              'blocks': [
                {'block_id': 'B1', 'reason': 'Tagihan UTS belum lunas'},
              ],
            },
          ),
        );
      await pumpApp(tester, const ExamDetailScreen(examId: 'E1'), api: api);
      await tester.pumpAndSettle();
      await tester.scrollUntilVisible(
        find.text('Mulai mengerjakan'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.tap(find.text('Mulai mengerjakan'));
      await tester.pumpAndSettle();
      await tester.tap(
        find.descendant(
          of: find.byType(AlertDialog),
          matching: find.widgetWithText(FilledButton, 'Mulai mengerjakan'),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Akses ujian dicekal'), findsOneWidget);
      expect(find.textContaining('Tagihan UTS belum lunas'), findsOneWidget);
      await tester.tap(find.text('Buka status cekal & keuangan'));
      await tester.pumpAndSettle();
      expect(find.text('route:/s/finance/clearance'), findsOneWidget);
    });

    testWidgets('English, finished exam without visible result', (
      tester,
    ) async {
      final api = FakeApi()
        ..get(
          '/student/exams/E1',
          examJson(status: 'completed', latest: 'A1', used: 2),
        );
      await pumpApp(
        tester,
        const ExamDetailScreen(examId: 'E1'),
        api: api,
        language: AppLanguage.en,
      );
      await tester.pumpAndSettle();
      expect(find.text('Exam rules'), findsOneWidget);
      await tester.scrollUntilVisible(
        find.text('Waiting for the lecturer to publish the result.'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
    });
  });

  group('attempt screen', () {
    testWidgets('select option autosaves, then submit with summary', (
      tester,
    ) async {
      final api = attemptApi();
      await pumpApp(tester, const ExamAttemptScreen(attemptId: 'A1'), api: api);
      await tester.pumpAndSettle();
      expect(find.text('Apa itu SQL?'), findsOneWidget);
      expect(find.text('0 dari 2 soal terjawab'), findsOneWidget);
      await tester.tap(find.text('Bahasa query'));
      await tester.pump(const Duration(milliseconds: 500));
      await tester.pump();
      final put = api.requests.where((r) => r.method == 'PUT').single;
      expect(put.body, {
        'exam_question_id': 'Q1',
        'exam_question_option_id': 'O1',
      });
      expect(find.text('Tersimpan'), findsOneWidget);

      await tester.tap(find.text('Kumpulkan').first);
      await tester.pumpAndSettle();
      expect(find.text('Kumpulkan ujian?'), findsOneWidget);
      expect(find.textContaining('1 soal belum dijawab'), findsOneWidget);
      await tester.tap(find.widgetWithText(FilledButton, 'Kumpulkan'));
      await tester.pumpAndSettle();
      expect(api.requests.where((r) => r.method == 'PATCH'), hasLength(1));
      expect(find.text('route:/s/exam-attempts/A1/result'), findsOneWidget);
    });

    testWidgets('no back navigation disables previous', (tester) async {
      final api = attemptApi()
        ..get(
          '/student/exams/E1',
          examJson(status: 'in_progress', allowBack: false),
        );
      await pumpApp(tester, const ExamAttemptScreen(attemptId: 'A1'), api: api);
      await tester.pumpAndSettle();
      await tester.tap(find.widgetWithText(FilledButton, 'Berikutnya'));
      await tester.pumpAndSettle();
      expect(find.text('Apa itu PK?'), findsOneWidget);
      final prev = tester.widget<OutlinedButton>(
        find.widgetWithText(OutlinedButton, 'Sebelumnya'),
      );
      expect(prev.onPressed, isNull);
    });

    testWidgets('background then resume reports tab_switch violation', (
      tester,
    ) async {
      final api = attemptApi();
      await pumpApp(tester, const ExamAttemptScreen(attemptId: 'A1'), api: api);
      await tester.pumpAndSettle();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      final v = api.requests.where((r) => r.path.endsWith('/violations'));
      expect(v, hasLength(1));
      expect((v.single.body as Map)['violation_type'], 'tab_switch');
      expect(find.textContaining('Pelanggaran terdeteksi'), findsOneWidget);
    });

    testWidgets('back gesture asks for confirmation', (tester) async {
      await pumpApp(
        tester,
        const ExamAttemptScreen(attemptId: 'A1'),
        api: attemptApi(),
        size: TestScreens.smallPhone,
      );
      await tester.pumpAndSettle();
      await tester.tap(find.byIcon(Icons.close));
      await tester.pumpAndSettle();
      expect(find.text('Tinggalkan ujian?'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });

    testWidgets('already submitted attempt redirects to result', (
      tester,
    ) async {
      final api = attemptApi(attempt: attemptJson(status: 'submitted'));
      await pumpApp(tester, const ExamAttemptScreen(attemptId: 'A1'), api: api);
      await tester.pumpAndSettle();
      expect(find.text('route:/s/exam-attempts/A1/result'), findsOneWidget);
    });

    testWidgets('failed save shows unsaved indicator', (tester) async {
      final api = attemptApi()
        ..on(
          'PUT',
          '/student/exam-attempts/A1/answer',
          (_) => FakeResponse.error(500, 'boom'),
        );
      await pumpApp(tester, const ExamAttemptScreen(attemptId: 'A1'), api: api);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Bahasa query'));
      await tester.pump(const Duration(milliseconds: 500));
      await tester.pump();
      expect(find.textContaining('Belum tersimpan'), findsOneWidget);
      // dispose pending retry timer
      await tester.pumpWidget(const SizedBox());
    });
  });

  group('result screen', () {
    testWidgets('shows score and review', (tester) async {
      final api = FakeApi()
        ..get('/student/exam-attempts/A1/result', resultJson());
      await pumpApp(tester, const ExamResultScreen(attemptId: 'A1'), api: api);
      await tester.pumpAndSettle();
      expect(find.text('49.00'), findsOneWidget);
      expect(find.text('C'), findsOneWidget);
      expect(find.text('Review jawaban'), findsOneWidget);
      expect(find.byTooltip('Unduh PDF'), findsOneWidget);
    });

    testWidgets('409 shows pending state', (tester) async {
      final api = FakeApi()
        ..on(
          'GET',
          '/student/exam-attempts/A1/result',
          (_) => FakeResponse.error(409, 'Menunggu dipublikasikan oleh dosen.'),
        );
      await pumpApp(tester, const ExamResultScreen(attemptId: 'A1'), api: api);
      await tester.pumpAndSettle();
      expect(find.text('Hasil belum tersedia'), findsOneWidget);
    });
  });

  test('ApiFailure code used for block detection', () {
    final f = ApiFailure.fromResponse(409, {
      'success': false,
      'message': 'm',
      'errors': {'code': 'ACADEMIC_BLOCKED'},
    });
    expect(f.code, 'ACADEMIC_BLOCKED');
  });
}
