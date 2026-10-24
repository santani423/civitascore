import 'package:civitasone/core/network/api_failure.dart';
import 'package:civitasone/features/exam/data/exam_api.dart';
import 'package:civitasone/features/exam/domain/attempt_report.dart';
import 'package:civitasone/features/exam/domain/exam_monitoring.dart';
import 'package:civitasone/features/exam/domain/grade_range.dart';
import 'package:civitasone/features/exam/domain/question_selection_mode.dart';
import 'package:civitasone/features/exam/presentation/widgets/overview_tab.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import 'exam_fixtures.dart';

void main() {
  late FakeApi fake;
  late ExamApi api;

  setUp(() {
    fake = FakeApi();
    api = ExamApi(fake.client());
  });

  test(
    'listExams parses ExamResource incl. decimal weight and nulls',
    () async {
      fake.get(
        '/exams',
        [examJson(token: 'tok123')],
        meta: {'current_page': 1, 'per_page': 20, 'total': 1, 'last_page': 1},
      );
      final page = await api.listExams(isPublished: false);
      final e = page.items.single;
      expect(e.title, 'UTS Algoritma');
      expect(e.weightPercentage, 30.0);
      expect(e.endsAt, isNull);
      expect(e.startsAt, isNotNull);
      expect(e.questionSelectionMode, QuestionSelectionMode.random);
      expect(e.hasAccessLink, isTrue);
      expect(fake.requests.single.query['filter[is_published]'], '0');
    },
  );

  test('listExams tolerates [] meta', () async {
    fake.get('/exams', [], meta: null);
    final page = await api.listExams();
    expect(page.items, isEmpty);
    expect(page.hasNextPage, isFalse);
  });

  test('createExam / updateExam / publish / delete / access-link', () async {
    fake
      ..on('POST', '/exams', (_) => FakeResponse.ok(examJson()))
      ..on('PUT', '/exams/{id}', (_) => FakeResponse.ok(examJson()))
      ..on(
        'PATCH',
        '/exams/{id}/publish',
        (_) => FakeResponse.ok(examJson(published: true)),
      )
      ..on(
        'PATCH',
        '/exams/{id}/access-link',
        (_) => FakeResponse.ok(examJson(token: 'new')),
      )
      ..on('DELETE', '/exams/{id}', (_) => FakeResponse.ok(null));
    await api.createExam({'title': 'X'});
    await api.updateExam('e1', {'title': 'Y'});
    final published = await api.publish('e1');
    final linked = await api.generateAccessLink('e1');
    await api.deleteExam('e1');
    expect(published.isPublished, isTrue);
    expect(linked.accessToken, 'new');
    expect(fake.requests.map((r) => '${r.method} ${r.path}'), [
      'POST /exams',
      'PUT /exams/e1',
      'PATCH /exams/e1/publish',
      'PATCH /exams/e1/access-link',
      'DELETE /exams/e1',
    ]);
    expect((fake.requests[1].body as Map)['title'], 'Y');
  });

  test('publish surfaces the 409 message', () async {
    fake.on(
      'PATCH',
      '/exams/{id}/publish',
      (_) => FakeResponse.error(
        409,
        'Question pool masih kosong. Tambahkan soal terlebih dahulu.',
      ),
    );
    expect(
      () => api.publish('e1'),
      throwsA(
        isA<ApiFailure>()
            .having((f) => f.kind, 'kind', ApiFailureKind.conflict)
            .having((f) => f.serverDetail, 'detail', contains('Question pool')),
      ),
    );
  });

  test('question mutations send options and apply-bank payloads', () async {
    fake
      ..on(
        'POST',
        '/exams/{id}/questions',
        (_) => FakeResponse.ok(questionJson('q1')),
      )
      ..on(
        'PUT',
        '/exam-questions/{id}',
        (_) => FakeResponse.ok(questionJson('q1')),
      )
      ..on('DELETE', '/exam-questions/{id}', (_) => FakeResponse.ok(null))
      ..on(
        'POST',
        '/exams/{id}/questions/apply-bank',
        (_) => FakeResponse.ok([questionJson('q2'), questionJson('q3')]),
      );
    await api.addQuestion(
      'e1',
      questionText: 'Q?',
      points: 2,
      options: [
        (optionText: 'a', isCorrect: true),
        (optionText: 'b', isCorrect: false),
      ],
    );
    await api.updateQuestion('q1', isSelected: false);
    await api.deleteQuestion('q1');
    final applied = await api.applyBank('e1', ['b1', 'b2']);
    final add = fake.requests[0].body as Map;
    expect(add['points'], 2.0);
    expect((add['options'] as List).first, {
      'option_text': 'a',
      'is_correct': true,
    });
    expect(fake.requests[1].body, {'is_selected': false});
    expect(applied, hasLength(2));
    expect(fake.requests.last.body, {
      'question_bank_item_ids': ['b1', 'b2'],
    });
  });

  test('question bank list filters and CRUD', () async {
    fake
      ..get('/question-bank', [
        {
          'id': 'b1',
          'course_id': 'crs1',
          'course_name': 'Algoritma',
          'question_text': 'Soal bank',
          'points': '2.50',
          'options': [],
          'usage_count': 3,
          'created_at': '2026-10-19T01:00:00+00:00',
        },
      ])
      ..on(
        'POST',
        '/question-bank',
        (_) => FakeResponse.ok({'id': 'b2', 'question_text': 'n'}),
      )
      ..on(
        'PUT',
        '/question-bank/{id}',
        (_) => FakeResponse.ok({'id': 'b1', 'question_text': 'n'}),
      )
      ..on('DELETE', '/question-bank/{id}', (_) => FakeResponse.ok(null));
    final page = await api.listBank(search: 'rekursi', courseId: 'crs1');
    expect(page.items.single.points, 2.5);
    expect(page.items.single.usageCount, 3);
    expect(fake.requests.single.query['search'], 'rekursi');
    expect(fake.requests.single.query['filter[course_id]'], 'crs1');
    await api.createBankItem(
      courseId: 'crs1',
      questionText: 'n',
      points: 1,
      options: [
        (optionText: 'a', isCorrect: true),
        (optionText: 'b', isCorrect: false),
      ],
    );
    await api.updateBankItem(
      'b1',
      courseId: null,
      questionText: 'n',
      points: 1,
      options: [
        (optionText: 'a', isCorrect: false),
        (optionText: 'b', isCorrect: true),
      ],
    );
    await api.deleteBankItem('b1');
    expect((fake.requests[1].body as Map)['course_id'], 'crs1');
    expect(fake.requests.last.method, 'DELETE');
  });

  test('participants, violations, recap and attempt timeline parse', () async {
    fake
      ..get('/exams/{id}/participants', [
        participantJson(
          '2101001',
          'Andi',
          'completed',
          attemptId: 'a1',
          violations: 2,
        ),
      ])
      ..get('/exams/{id}/violations/recent', [
        {
          'id': 'v1',
          'exam_attempt_id': 'a1',
          'sequence_number': 2,
          'violation_type': 'tab_switch',
          'violation_label': 'Berpindah Tab/Aplikasi',
          'penalty_points': '1.00',
          'occurred_at': '2026-11-02T01:05:00+00:00',
          'student_name': 'Andi',
          'student_nim': '2101001',
        },
      ])
      ..get('/exams/{id}/recap', recapJson())
      ..get('/exam-attempts/{id}/violations', {
        'student': {'name': 'Andi', 'nim': '2101001'},
        'raw_score': '80.00',
        'penalty_score': '2.00',
        'score': '78.00',
        'grade': 'AB',
        'weighted_score': '23.40',
        'violation_count': 1,
        'violations': [],
      });
    final p = (await api.participants('e1')).single;
    expect(p.status, ParticipantStatus.completed);
    expect(p.score, 78.0);
    expect(p.weightedScore, 23.4);
    final v = await api.recentViolations(
      'e1',
      since: DateTime.utc(2026, 11, 2),
    );
    expect(v.single.sequenceNumber, 2);
    expect(fake.requests.last.query['since'], '2026-11-02T00:00:00.000Z');
    final recap = await api.recap('e1');
    expect(recap.summary.completed, 1);
    expect(recap.summary.averageScore, 78.0);
    expect(recap.participants, hasLength(3));
    final t = await api.attemptViolations('a1');
    expect(t.score, 78.0);
    expect(t.violations, isEmpty);
  });

  test('attempt report parses summary, questions and timeline', () async {
    fake.get('/exam-attempts/a1/report', attemptReportJson());
    final r = await api.attemptReport('a1');
    expect(r.isSubmitted, isTrue);
    expect(r.autoSubmitted, isFalse);
    expect(r.score, 49.0);
    expect(r.weightPercentage, 30.0);
    expect(r.timeUsage, 0.5);
    expect(r.questions, hasLength(2));
    expect(r.questions.first.status, ReportQuestionStatus.correct);
    expect(r.questions.first.options.last.isCorrect, isTrue);
    expect(r.questions.last.selectedOptionId, isNull);
    expect(r.violations.single.occurredAfterSeconds, 600);
    expect(r.violationBreakdown.single.count, 1);
    expect(r.timeline.map((e) => e.type), [
      TimelineEventType.started,
      TimelineEventType.answered,
      TimelineEventType.violation,
      TimelineEventType.submitted,
    ]);
    expect(ExamApi.attemptReportPdfPath('a1'), '/exam-attempts/a1/report/pdf');
  });

  test('grade ranges GET/PUT payload', () async {
    fake
      ..get('/exams/{id}/grade-ranges', [
        {'id': 'g1', 'grade': 'A', 'min_score': '85.00', 'max_score': '100.00'},
      ])
      ..on(
        'PUT',
        '/exams/{id}/grade-ranges',
        (_) => FakeResponse.ok([
          {
            'id': 'g1',
            'grade': 'A',
            'min_score': '85.00',
            'max_score': '100.00',
          },
        ]),
      );
    final r = await api.gradeRanges('e1');
    expect(r.single.minScore, 85.0);
    await api.saveGradeRanges('e1', const [
      GradeRange(grade: 'A', minScore: 85, maxScore: 100),
    ]);
    expect(fake.requests.last.body, {
      'ranges': [
        {'grade': 'A', 'min_score': 85.0, 'max_score': 100.0},
      ],
    });
  });

  test(
    'ownClasses uses profile id; none without profile unless admin',
    () async {
      ownClassRoutes(fake);
      final own = await api.ownClasses(canSeeAll: false);
      expect(own.single.courseId, 'crs1');
      expect(fake.requests.last.query['filter[lecturer_id]'], 'lec1');

      final f2 = FakeApi()
        ..on(
          'GET',
          '/lecturer-profile',
          (_) => FakeResponse.error(404, 'Akun belum tertaut'),
        )
        ..get('/class-sections', [classJson()]);
      expect(await ExamApi(f2.client()).ownClasses(canSeeAll: false), isEmpty);
      expect(
        await ExamApi(f2.client()).ownClasses(canSeeAll: true),
        hasLength(1),
      );
    },
  );

  group('GradeRangeValidation', () {
    GradeRange r(String g, double a, double b) =>
        GradeRange(grade: g, minScore: a, maxScore: b);

    test('defaults are valid', () {
      final defaults = [
        for (final d in kDefaultGradeRanges) r(d.$1, d.$2, d.$3),
      ];
      expect(GradeRangeValidation.check(defaults), isNull);
    });
    test('detects overlap, gap, duplicates and bounds', () {
      expect(
        GradeRangeValidation.check([r('A', 80, 100), r('B', 70, 85)]),
        GradeRangeIssue.overlap,
      );
      expect(
        GradeRangeValidation.check([r('A', 85, 100), r('B', 60, 79)]),
        GradeRangeIssue.gap,
      );
      expect(
        GradeRangeValidation.check([r('A', 85, 100), r('A', 0, 84.99)]),
        GradeRangeIssue.duplicateGrade,
      );
      expect(
        GradeRangeValidation.check([r('A', 90, 80)]),
        GradeRangeIssue.invalidBounds,
      );
      expect(GradeRangeValidation.check([]), GradeRangeIssue.empty);
    });
  });

  test('examAccessUrl derives the host from the API base URL', () {
    expect(
      examAccessUrl('https://campus.test/api/v1', 'abc'),
      'https://campus.test/exam/abc',
    );
    expect(
      examAccessUrl('http://10.0.2.2:8000/api/v1', 'abc'),
      'http://10.0.2.2:8000/exam/abc',
    );
  });
}
