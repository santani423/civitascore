import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/auth/domain/auth_user.dart';
import 'package:civitasone/features/pmb_duty/data/pmb_duty_api.dart';
import 'package:civitasone/features/pmb_duty/domain/pmb_duty_models.dart';
import 'package:civitasone/features/pmb_duty/presentation/screens/duty_scores_screen.dart';
import 'package:civitasone/features/pmb_duty/presentation/screens/duty_session_screen.dart';
import 'package:civitasone/features/pmb_duty/presentation/screens/duty_sessions_screen.dart';
import 'package:civitasone/features/pmb_duty/presentation/widgets/home_duty_section.dart';
import 'package:civitasone/features/pmb_duty/presentation/widgets/score_edit_sheet.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

/// Lecturer with the PMB examiner grants of `OrganizationalRoleSeeder`.
final pmbLecturer = AuthUser(
  id: testLecturer.id,
  name: testLecturer.name,
  email: testLecturer.email,
  roles: testLecturer.roles,
  permissions: [
    ...testLecturer.permissions,
    'pmb_scores.create',
    'pmb_exam_attendance.update',
  ],
);

String iso(Duration fromNow) =>
    DateTime.now().toUtc().add(fromNow).toIso8601String();

const token =
    '01PARTICIPANT0000000000001.01SESSION00000000000000001.0123456789abcdef0123456789abcdef';

/// `GET /pmb/admin/examiner/sessions` (ExamController::mySessions).
List<Map<String, dynamic>> sessionsJson() => [
  {
    'id': 'old',
    'code': 'W-01',
    'starts_at': iso(const Duration(days: -3)),
    'ends_at': iso(const Duration(days: -3, hours: 1)),
    'schedule': 'Wawancara Jalur Prestasi',
    'mode': 'interview',
    'component': 'Wawancara',
    'room': null,
    'participants_count': 1,
    'scores_locked': true,
    'roles': ['interviewer'],
  },
  {
    'id': 's1',
    'code': 'S1-LAB1',
    'starts_at': iso(const Duration(days: 1)),
    'ends_at': iso(const Duration(days: 1, minutes: 90)),
    'schedule': 'Tes Potensi Akademik (CBT) Gelombang 1',
    'mode': 'cbt',
    'component': 'Tes Potensi Akademik',
    'room': 'Kampus Semanggi — Lab Komputer 1',
    'participants_count': 2,
    'scores_locked': false,
    'roles': ['proctor', 'examiner'],
  },
];

/// `GET /pmb/admin/exam-sessions/{id}` — `$examSession->toArray()` + extras.
Map<String, dynamic> sessionJson({
  List<String> roles = const ['proctor', 'examiner'],
  Duration startsIn = const Duration(minutes: -10),
}) => {
  'id': 's1',
  'university_id': 'u1',
  'schedule_id': 'sc1',
  'room_id': 'r1',
  'code': 'S1-LAB1',
  'starts_at': iso(startsIn),
  'ends_at': iso(startsIn + const Duration(minutes: 90)),
  'checkin_opens_at': iso(startsIn - const Duration(minutes: 30)),
  'capacity': 30,
  'participants_count': 2,
  'online_url': null,
  'scores_locked_at': null,
  'scores_locked_by': null,
  'lock_version': 0,
  'schedule': {
    'id': 'sc1',
    'title': 'Tes Potensi Akademik (CBT) Gelombang 1',
    'mode': 'cbt',
    'exam_date': '2026-11-02',
    'selection_component_id': 'c1',
  },
  'room': {
    'id': 'r1',
    'code': 'LAB1',
    'name': 'Lab Komputer 1',
    'venue': {'id': 'v1', 'name': 'Kampus Semanggi'},
  },
  'examiners': [
    for (final r in roles)
      {
        'id': 'e-$r',
        'session_id': 's1',
        'user_id': testLecturer.id,
        'role': r,
        'user': {'id': testLecturer.id, 'name': testLecturer.name},
      },
    {
      'id': 'e-other',
      'session_id': 's1',
      'user_id': '01OTHER',
      'role': 'proctor',
      'user': {'id': '01OTHER', 'name': 'Pengawas Lain'},
    },
  ],
  'participants': [
    {
      'id': 'p1',
      'participant_number': 'PMB-0001',
      'name': 'Calon Satu',
      'attendance_status': 'registered',
      'checked_in_at': null,
      'note': null,
    },
    {
      'id': 'p2',
      'participant_number': 'PMB-0002',
      'name': 'Calon Dua',
      'attendance_status': 'present',
      'checked_in_at': iso(const Duration(minutes: -5)),
      'note': null,
    },
  ],
  'attendance_statuses': [
    'registered',
    'present',
    'absent',
    'excused',
    'disqualified',
  ],
};

/// `GET /pmb/admin/exam-sessions/{id}/scores` (ExamController::sessionScores).
Map<String, dynamic> scoresJson({
  bool rubric = true,
  String scoreType = 'numeric',
  String? lockedAt,
}) => {
  'session': {
    'id': 's1',
    'code': 'S1-LAB1',
    'starts_at': iso(const Duration(hours: -1)),
    'scores_locked_at': lockedAt,
  },
  'component': {
    'id': 'c1',
    'code': 'WWC',
    'name': 'Wawancara',
    'source': 'interview',
    'score_type': scoreType,
    'raw_min': '0.00',
    'raw_max': '100.00',
    'rubric': rubric
        ? {
            'criteria': [
              {'key': 'komunikasi', 'label': 'Komunikasi', 'weight': 60},
              {'key': 'motivasi', 'label': 'Motivasi', 'weight': 40},
            ],
          }
        : null,
  },
  'rows': [
    {
      'participant_id': 'p1',
      'participant_number': 'PMB-0001',
      'name': 'Calon Satu',
      'attendance_status': 'present',
      'scores': [
        {
          'examiner_user_id': testLecturer.id,
          'raw_score': '82.0000',
          'passed': null,
          'rubric_scores': {'komunikasi': 80, 'motivasi': 85},
          'note': 'Komunikatif',
        },
      ],
    },
    {
      'participant_id': 'p2',
      'participant_number': 'PMB-0002',
      'name': 'Calon Dua',
      'attendance_status': 'registered',
      'scores': <dynamic>[],
    },
    {
      'participant_id': 'p3',
      'participant_number': 'PMB-0003',
      'name': 'Calon Tiga',
      'attendance_status': 'absent',
      'scores': <dynamic>[],
    },
  ],
};

Map<String, dynamic> checkedIn(String id, String status) => {
  'id': id,
  'participant_number': id == 'p1' ? 'PMB-0001' : 'PMB-0002',
  'name': id == 'p1' ? 'Calon Satu' : 'Calon Dua',
  'attendance_status': status,
  'checked_in_at': status == 'present' ? iso(Duration.zero) : null,
};

/// The home section renders nothing while loading (no animation frames),
/// so let the fake HTTP response arrive before settling.
Future<void> settle(WidgetTester tester) async {
  await tester.pump(const Duration(milliseconds: 50));
  await tester.pumpAndSettle();
}

void main() {
  group('api & models', () {
    test('sessions parse roles and split active/finished', () async {
      final api = FakeApi()
        ..get('/pmb/admin/examiner/sessions', sessionsJson());
      final list = await PmbDutyApi(api.client()).mySessions();
      expect(list, hasLength(2));
      final s1 = list.firstWhere((s) => s.id == 's1');
      expect(s1.roles, {PmbExaminerRole.proctor, PmbExaminerRole.examiner});
      expect(s1.canScore, isTrue);
      expect(s1.participantsCount, 2);
      final split = splitSessions(list, DateTime.now());
      expect(split.active.map((s) => s.id), ['s1']);
      expect(split.finished.single.scoresLocked, isTrue);
      expect(split.finished.single.canScore, isTrue); // interviewer
    });

    test('session detail: room, my roles, counts', () async {
      final api = FakeApi()
        ..get('/pmb/admin/exam-sessions/{id}', sessionJson());
      final d = await PmbDutyApi(api.client()).session('s1');
      expect(d.roomLabel, 'Kampus Semanggi — Lab Komputer 1');
      expect(d.rolesOf(testLecturer.id), {
        PmbExaminerRole.proctor,
        PmbExaminerRole.examiner,
      });
      expect(d.rolesOf('01OTHER'), {PmbExaminerRole.proctor});
      expect(d.count(PmbAttendance.registered), 1);
      expect(d.participants.last.checkedInAt, isNotNull);
      expect(d.participants.first.matches('pmb-0001'), isTrue);
      expect(d.participants.first.matches('dua'), isFalse);
    });

    test('check-in sends token or participant id, never both', () async {
      final api = FakeApi()
        ..on(
          'POST',
          '/pmb/admin/exam-sessions/{id}/check-in',
          (_) => FakeResponse.ok(
            checkedIn('p1', 'present'),
            message: 'Kehadiran dicatat.',
          ),
        );
      final a = PmbDutyApi(api.client());
      final p = await a.checkIn('s1', token: token);
      expect(p.attendance, PmbAttendance.present);
      expect(api.requests.last.path, '/pmb/admin/exam-sessions/s1/check-in');
      expect(api.requests.last.body, {'token': token, 'status': 'present'});
      await a.checkIn('s1', participantId: 'p2', status: PmbAttendance.excused);
      expect(api.requests.last.body, {
        'participant_id': 'p2',
        'status': 'excused',
      });
    });

    test('card token format', () {
      expect(looksLikeCardToken(token), isTrue);
      expect(looksLikeCardToken(' $token '), isTrue);
      expect(looksLikeCardToken('https://example.com'), isFalse);
      expect(looksLikeCardToken('a.b.c'), isFalse);
    });

    test('scores parse rubric, compute raw like the backend', () async {
      final api = FakeApi()
        ..get('/pmb/admin/exam-sessions/{id}/scores', scoresJson());
      final sheet = await PmbDutyApi(
        api.client(),
      ).scores('s1', userId: testLecturer.id);
      final c = sheet.component;
      expect(c.hasRubric, isTrue);
      expect(c.isMultiExaminer, isTrue); // interview
      expect(c.criteria.first.title, 'Komunikasi');
      expect(c.criteria.first.max, 100);
      expect(c.rubricRaw({'komunikasi': 80, 'motivasi': 85}), 82);
      expect(c.rubricRaw({'komunikasi': 80}), isNull);
      expect(c.inRange(100), isTrue);
      expect(c.inRange(100.5), isFalse);
      expect(sheet.rows.first.score!.rawScore, 82);
      expect(sheet.rows.first.score!.note, 'Komunikatif');
      expect(sheet.scoredCount, 1);
      expect(sheet.rows[1].attendance.scorable, isTrue); // registered
      expect(sheet.rows[2].attendance.scorable, isFalse); // absent
    });

    test('rubric raw is rounded to 4 decimals like the backend', () {
      const c = ScoreComponent(
        name: 'Wawancara',
        scoreType: 'numeric',
        rawMin: 0,
        rawMax: 100,
        criteria: [
          RubricCriterion(key: 'a', weight: 0.06),
          RubricCriterion(key: 'b', weight: 0.59),
          RubricCriterion(key: 'c', weight: 0.35),
        ],
      );
      final raw = c.rubricRaw({'a': 100, 'b': 100, 'c': 100})!;
      expect(raw, 100);
      expect(c.inRange(raw), isTrue);
      // 0.06·33 + 0.59·67 + 0.35·71 = 66.36 (exact after 4-decimal rounding).
      expect(c.rubricRaw({'a': 33, 'b': 67, 'c': 71}), 66.36);
    });

    test('own score: other examiners and shared rows are not mine', () {
      Map<String, dynamic> row(List<Map<String, dynamic>> scores) => {
        'participant_id': 'p1',
        'participant_number': 'PMB-0001',
        'name': 'Calon Satu',
        'attendance_status': 'present',
        'scores': scores,
      };
      final other = {
        'examiner_user_id': '01OTHER',
        'raw_score': '90.0000',
        'passed': null,
        'rubric_scores': {'komunikasi': 90},
        'note': 'Y: ok',
      };
      final shared = {
        'examiner_user_id': null,
        'raw_score': '70.0000',
        'passed': null,
        'rubric_scores': null,
        'note': 'Impor',
      };
      final mine = {...other, 'examiner_user_id': testLecturer.id, 'note': 'X'};

      // Interview/portfolio: only the caller's own row counts.
      final interview = ScoreRow.fromJson(
        row([other, shared]),
        userId: testLecturer.id,
        multiExaminer: true,
      );
      expect(interview.scored, isFalse);
      expect(interview.score, isNull);
      expect(
        ScoreRow.fromJson(
          row([mine, other, shared]),
          userId: testLecturer.id,
          multiExaminer: true,
        ).score!.note,
        'X',
      );

      // Other components keep one shared row.
      expect(
        ScoreRow.fromJson(
          row([shared]),
          userId: testLecturer.id,
          multiExaminer: false,
        ).score!.rawScore,
        70,
      );
    });

    test('saveScores and attendance sheet payloads', () async {
      final api = FakeApi()
        ..on(
          'PUT',
          '/pmb/admin/exam-sessions/{id}/scores',
          (_) => FakeResponse.ok({'saved': 1}, message: 'Nilai disimpan.'),
        )
        ..get('/pmb/admin/exam-sessions/{id}/attendance-sheet', {
          'url': 'https://campus.test/api/v1/pmb/files/f1?signature=x',
        });
      final a = PmbDutyApi(api.client());
      final saved = await a.saveScores('s1', [
        const ScoreInput(participantId: 'p2', rawScore: 75, note: '  '),
        const ScoreInput(participantId: 'p1', passed: true),
      ]);
      expect(saved, 1);
      expect(api.requests.first.body, {
        'rows': [
          {
            'participant_id': 'p2',
            'raw_score': 75.0,
            'rubric_scores': null,
            'passed': null,
            'note': null,
          },
          {
            'participant_id': 'p1',
            'raw_score': null,
            'rubric_scores': null,
            'passed': true,
            'note': null,
          },
        ],
      });
      final url = await a.attendanceSheetUrl('s1');
      expect(url?.queryParameters['signature'], 'x');
    });

    test('attendance closes 2 hours after the session ends', () {
      final end = DateTime(2026, 10, 27, 10);
      expect(attendanceClosedAt(end, DateTime(2026, 10, 27, 11, 59)), isFalse);
      expect(attendanceClosedAt(end, DateTime(2026, 10, 27, 12, 1)), isTrue);
      expect(attendanceClosedAt(null, DateTime(2030)), isFalse);
    });
  });

  group('sessions screen', () {
    for (final lang in AppLanguage.values) {
      testWidgets('lists active and finished sessions (${lang.name})', (
        tester,
      ) async {
        final api = FakeApi()
          ..get('/pmb/admin/examiner/sessions', sessionsJson());
        await pumpApp(
          tester,
          const DutySessionsScreen(),
          api: api,
          user: pmbLecturer,
          language: lang,
          size: TestScreens.smallPhone,
        );
        expect(find.text('S1-LAB1'), findsOneWidget);
        expect(find.text('W-01'), findsOneWidget);
        expect(tester.takeException(), isNull);
      });
    }

    testWidgets('tapping a session opens its detail', (tester) async {
      final api = FakeApi()
        ..get('/pmb/admin/examiner/sessions', sessionsJson());
      await pumpApp(
        tester,
        const DutySessionsScreen(),
        api: api,
        user: pmbLecturer,
      );
      expect(find.text('Berlangsung & mendatang'), findsOneWidget);
      expect(find.text('Selesai'), findsOneWidget);
      expect(find.text('Nilai terkunci'), findsOneWidget);
      await tester.tap(find.text('Tes Potensi Akademik (CBT) Gelombang 1'));
      await tester.pumpAndSettle();
      expect(find.text('route:/l/pmb/sessions/s1'), findsOneWidget);
    });

    testWidgets('long session codes do not squeeze the title (360, 1.3x)', (
      tester,
    ) async {
      tester.platformDispatcher.textScaleFactorTestValue = 1.3;
      addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
      final sessions = sessionsJson();
      sessions.last['code'] = 'WAWANCARA-PRESTASI-GEL2-SESI3';
      final api = FakeApi()..get('/pmb/admin/examiner/sessions', sessions);
      await pumpApp(
        tester,
        const DutySessionsScreen(),
        api: api,
        user: pmbLecturer,
        language: AppLanguage.en,
        size: TestScreens.smallPhone,
      );
      expect(tester.takeException(), isNull);
      final title = tester.getSize(
        find.text('Tes Potensi Akademik (CBT) Gelombang 1'),
      );
      expect(title.width, greaterThan(150));
    });

    testWidgets('empty state', (tester) async {
      final api = FakeApi()..get('/pmb/admin/examiner/sessions', <dynamic>[]);
      await pumpApp(
        tester,
        const DutySessionsScreen(),
        api: api,
        user: pmbLecturer,
      );
      expect(find.text('Tidak ada sesi yang ditugaskan'), findsOneWidget);
    });
  });

  group('session screen', () {
    testWidgets('changes attendance from the participant sheet', (
      tester,
    ) async {
      final api = FakeApi()
        ..get('/pmb/admin/exam-sessions/{id}', sessionJson())
        ..on(
          'POST',
          '/pmb/admin/exam-sessions/{id}/check-in',
          (_) => FakeResponse.ok(checkedIn('p1', 'present')),
        );
      await pumpApp(
        tester,
        const DutySessionScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
      );
      expect(find.text('Calon Satu'), findsOneWidget);
      expect(find.text('Input nilai'), findsOneWidget); // examiner role
      expect(find.text('Pindai QR'), findsOneWidget);
      expect(find.text('Terdaftar 1'), findsOneWidget);

      await tester.tap(find.text('Calon Satu'));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const ValueKey('attendance:present')));
      await tester.pumpAndSettle();

      final post = api.requests.last;
      expect(post.method, 'POST');
      expect(post.body, {'participant_id': 'p1', 'status': 'present'});
      expect(find.text('Hadir 2'), findsOneWidget);
      expect(find.text('Terdaftar 0'), findsOneWidget);
    });

    testWidgets('disqualifying asks for confirmation first', (tester) async {
      final api = FakeApi()
        ..get('/pmb/admin/exam-sessions/{id}', sessionJson())
        ..on(
          'POST',
          '/pmb/admin/exam-sessions/{id}/check-in',
          (_) => FakeResponse.ok(checkedIn('p2', 'disqualified')),
        );
      await pumpApp(
        tester,
        const DutySessionScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
      );
      await tester.tap(find.text('Calon Dua'));
      await tester.pumpAndSettle();
      await tester.tap(find.byKey(const ValueKey('attendance:disqualified')));
      await tester.pumpAndSettle();
      expect(find.text('Ubah status kehadiran?'), findsOneWidget);
      await tester.tap(find.text('Batal'));
      await tester.pumpAndSettle();
      expect(api.requests.where((r) => r.method == 'POST'), isEmpty);
    });

    testWidgets('search filters participants', (tester) async {
      final api = FakeApi()
        ..get('/pmb/admin/exam-sessions/{id}', sessionJson());
      await pumpApp(
        tester,
        const DutySessionScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
      );
      await tester.enterText(find.byType(TextField), '0002');
      await tester.pumpAndSettle();
      expect(find.text('Calon Satu'), findsNothing);
      expect(find.text('Calon Dua'), findsOneWidget);
    });

    testWidgets('proctor only: no score entry; closed session read-only', (
      tester,
    ) async {
      final api = FakeApi()
        ..get(
          '/pmb/admin/exam-sessions/{id}',
          sessionJson(roles: ['proctor'], startsIn: const Duration(hours: -5)),
        );
      await pumpApp(
        tester,
        const DutySessionScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
        language: AppLanguage.en,
      );
      expect(find.text('Enter scores'), findsNothing);
      expect(find.text('Scan QR'), findsNothing);
      expect(find.textContaining('more than 2 hours ago'), findsOneWidget);
      await tester.tap(find.text('Calon Satu'));
      await tester.pumpAndSettle();
      expect(find.byKey(const ValueKey('attendance:present')), findsNothing);
    });
  });

  group('scores screen', () {
    testWidgets('rubric entry saves criteria for a registered participant', (
      tester,
    ) async {
      final api = FakeApi()
        ..get('/pmb/admin/exam-sessions/{id}/scores', scoresJson())
        ..on(
          'PUT',
          '/pmb/admin/exam-sessions/{id}/scores',
          (_) => FakeResponse.ok({'saved': 1}),
        );
      await pumpApp(
        tester,
        const DutyScoresScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
        size: TestScreens.smallPhone,
      );
      expect(find.text('1/2 dinilai'), findsOneWidget);
      expect(find.text('82'), findsOneWidget);
      await tester.scrollUntilVisible(find.text('Calon Tiga'), 200);
      expect(find.text('Tidak dinilai'), findsOneWidget); // absent row

      await tester.ensureVisible(find.text('Calon Dua'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Calon Dua'));
      await tester.pumpAndSettle();
      await tester.enterText(
        find.byKey(const ValueKey('criterion:komunikasi')),
        '90',
      );
      await tester.enterText(
        find.byKey(const ValueKey('criterion:motivasi')),
        '70',
      );
      await tester.pump();
      expect(find.text('Nilai dihitung: 82'), findsOneWidget);
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();

      final put = api.requests.firstWhere((r) => r.method == 'PUT');
      expect(put.body, {
        'rows': [
          {
            'participant_id': 'p2',
            'raw_score': null,
            'rubric_scores': {'komunikasi': 90.0, 'motivasi': 70.0},
            'passed': null,
            'note': null,
          },
        ],
      });
      expect(tester.takeException(), isNull);
    });

    testWidgets('numeric score is validated against the range', (tester) async {
      final api = FakeApi()
        ..get(
          '/pmb/admin/exam-sessions/{id}/scores',
          scoresJson(rubric: false),
        );
      await pumpApp(
        tester,
        const DutyScoresScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
      );
      await tester.tap(find.text('Calon Dua'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byKey(const ValueKey('raw_score')), '120');
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(api.requests.where((r) => r.method == 'PUT'), isEmpty);
      expect(find.text('Nilai harus 0–100'), findsOneWidget);
    });

    testWidgets('pass/fail entry and save & next', (tester) async {
      final api = FakeApi()
        ..get(
          '/pmb/admin/exam-sessions/{id}/scores',
          scoresJson(rubric: false, scoreType: 'pass_fail'),
        )
        ..on(
          'PUT',
          '/pmb/admin/exam-sessions/{id}/scores',
          (_) => FakeResponse.ok({'saved': 1}),
        );
      await pumpApp(
        tester,
        const DutyScoresScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
      );
      await tester.tap(find.text('Calon Satu'));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Lulus').last);
      await tester.tap(find.text('Simpan & berikutnya'));
      await tester.pumpAndSettle();
      final put = api.requests.firstWhere((r) => r.method == 'PUT');
      expect((put.body as Map)['rows'][0]['passed'], isTrue);
      // The next scorable participant (registered) opens automatically.
      expect(find.text('PMB-0002 · Wawancara'), findsOneWidget);
    });

    Finder inSheet(String text) => find.descendant(
      of: find.byType(ScoreEditSheet),
      matching: find.text(text),
    );

    Future<FakeApi> openSheet(
      WidgetTester tester, {
      required Map<String, dynamic> sheet,
      required FakeHandler onPut,
      String participant = 'Calon Dua',
      AppLanguage language = AppLanguage.id,
    }) async {
      final api = FakeApi()
        ..get('/pmb/admin/exam-sessions/{id}/scores', sheet)
        ..on('PUT', '/pmb/admin/exam-sessions/{id}/scores', onPut);
      await pumpApp(
        tester,
        const DutyScoresScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
        language: language,
      );
      await tester.tap(find.text(participant));
      await tester.pumpAndSettle();
      return api;
    }

    testWidgets('423 locked while saving is shown inside the sheet', (
      tester,
    ) async {
      final api = await openSheet(
        tester,
        sheet: scoresJson(rubric: false),
        onPut: (_) => FakeResponse.error(
          423,
          'Nilai sesi sudah dikunci.',
          errors: {'code': 'SCORES_LOCKED'},
        ),
      );
      int scoreGets() => api.requests
          .where((r) => r.method == 'GET' && r.path.endsWith('/scores'))
          .length;
      final before = scoreGets();
      await tester.enterText(find.byKey(const ValueKey('raw_score')), '75');
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(inSheet('Nilai sesi sudah dikunci.'), findsOneWidget);
      // The list behind the sheet is reloaded (it will show the lock).
      expect(scoreGets(), before + 1);
    });

    testWidgets('pass/fail requires a choice; saved=0 keeps the sheet open', (
      tester,
    ) async {
      final api = await openSheet(
        tester,
        sheet: scoresJson(rubric: false, scoreType: 'pass_fail'),
        onPut: (_) => FakeResponse.ok({'saved': 0}),
      );
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(inSheet('Pilih hasil'), findsOneWidget);
      expect(api.requests.where((r) => r.method == 'PUT'), isEmpty);

      await tester.tap(find.text('Lulus').last);
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(
        inSheet('Nilai tidak disimpan karena status kehadiran peserta.'),
        findsOneWidget,
      );
    });

    testWidgets('thousands separators are rejected, not read as decimals', (
      tester,
    ) async {
      final api = await openSheet(
        tester,
        sheet: scoresJson(rubric: false),
        onPut: (_) => FakeResponse.ok({'saved': 1}),
      );
      await tester.enterText(find.byKey(const ValueKey('raw_score')), '1.000');
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(
        find.text(
          'Gunakan angka dengan maks. 2 desimal, tanpa pemisah ribuan.',
        ),
        findsOneWidget,
      );
      expect(api.requests.where((r) => r.method == 'PUT'), isEmpty);

      await tester.enterText(find.byKey(const ValueKey('raw_score')), '72,5');
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      final put = api.requests.firstWhere((r) => r.method == 'PUT');
      expect((put.body as Map)['rows'][0]['raw_score'], 72.5);
    });

    testWidgets('range message uses the same locale format as the helper', (
      tester,
    ) async {
      final json = scoresJson(rubric: false);
      (json['component'] as Map)['raw_max'] = '4.50';
      await openSheet(
        tester,
        sheet: json,
        onPut: (_) => FakeResponse.ok({'saved': 1}),
      );
      expect(inSheet('Rentang nilai 0–4,5'), findsOneWidget);
      await tester.enterText(find.byKey(const ValueKey('raw_score')), '5');
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(find.text('Nilai harus 0–4,5'), findsOneWidget);
    });

    testWidgets('summary ignores scores of absent participants', (
      tester,
    ) async {
      final json = scoresJson();
      ((json['rows'] as List)[2] as Map)['scores'] = [
        {
          'examiner_user_id': testLecturer.id,
          'raw_score': '60.0000',
          'passed': null,
          'rubric_scores': {'komunikasi': 60, 'motivasi': 60},
          'note': null,
        },
      ];
      final api = FakeApi()..get('/pmb/admin/exam-sessions/{id}/scores', json);
      await pumpApp(
        tester,
        const DutyScoresScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
      );
      expect(find.text('1/2 dinilai'), findsOneWidget);
      // Value + chevron stay right-aligned in each row (411-wide phone).
      final chevron = tester.getTopRight(
        find.byIcon(Icons.chevron_right).first,
      );
      expect(chevron.dx, greaterThan(411 - 60));
    });

    testWidgets('score rows do not overflow at 2x font on 360', (tester) async {
      tester.platformDispatcher.textScaleFactorTestValue = 2.0;
      addTearDown(tester.platformDispatcher.clearTextScaleFactorTestValue);
      final api = FakeApi()
        ..get('/pmb/admin/exam-sessions/{id}/scores', scoresJson());
      await pumpApp(
        tester,
        const DutyScoresScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
        language: AppLanguage.en,
        size: TestScreens.smallPhone,
      );
      await tester.scrollUntilVisible(find.text('Calon Tiga'), 300);
      expect(tester.takeException(), isNull);
      expect(tester.getSize(find.text('Calon Tiga')).width, greaterThan(60));
    });

    testWidgets('a saved note cannot be emptied (backend keeps it)', (
      tester,
    ) async {
      final api = FakeApi()
        ..get('/pmb/admin/exam-sessions/{id}/scores', scoresJson(rubric: false))
        ..on(
          'PUT',
          '/pmb/admin/exam-sessions/{id}/scores',
          (_) => FakeResponse.ok({'saved': 1}),
        );
      await pumpApp(
        tester,
        const DutyScoresScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
      );
      await tester.tap(find.text('Calon Satu'));
      await tester.pumpAndSettle();
      expect(find.text('Komunikatif'), findsWidgets);
      await tester.enterText(find.byKey(const ValueKey('note')), '');
      await tester.tap(find.text('Simpan'));
      await tester.pumpAndSettle();
      expect(
        find.text(
          'Catatan yang tersimpan tidak dapat dikosongkan; ganti dengan teks lain.',
        ),
        findsOneWidget,
      );
      expect(api.requests.where((r) => r.method == 'PUT'), isEmpty);
    });

    testWidgets('locked sheet is read-only', (tester) async {
      final api = FakeApi()
        ..get(
          '/pmb/admin/exam-sessions/{id}/scores',
          scoresJson(lockedAt: iso(const Duration(hours: -1))),
        );
      await pumpApp(
        tester,
        const DutyScoresScreen(sessionId: 's1'),
        api: api,
        user: pmbLecturer,
        language: AppLanguage.zh,
      );
      expect(find.text('本场成绩已被委员会锁定，仅可查看。'), findsOneWidget);
      await tester.tap(find.text('Calon Dua'));
      await tester.pumpAndSettle();
      expect(find.byKey(const ValueKey('criterion:komunikasi')), findsNothing);
    });
  });

  group('home section', () {
    testWidgets('hidden without assignments', (tester) async {
      final api = FakeApi()..get('/pmb/admin/examiner/sessions', <dynamic>[]);
      await pumpApp(
        tester,
        const Scaffold(body: PmbDutyHomeSection()),
        api: api,
        user: pmbLecturer,
      );
      await settle(tester);
      expect(api.requests, hasLength(1));
      expect(find.text('Tugas ujian PMB'), findsNothing);
    });

    testWidgets('hidden and not requested without permission', (tester) async {
      final api = FakeApi()
        ..get('/pmb/admin/examiner/sessions', sessionsJson());
      await pumpApp(
        tester,
        const Scaffold(body: PmbDutyHomeSection()),
        api: api,
        user: testLecturer,
      );
      await settle(tester);
      expect(find.text('Tugas ujian PMB'), findsNothing);
      expect(api.requests, isEmpty);
    });

    testWidgets('hidden when the request fails', (tester) async {
      final api = FakeApi()
        ..on(
          'GET',
          '/pmb/admin/examiner/sessions',
          (_) => FakeResponse.error(403, 'Anda tidak memiliki izin.'),
        );
      await pumpApp(
        tester,
        const Scaffold(body: PmbDutyHomeSection()),
        api: api,
        user: pmbLecturer,
      );
      await settle(tester);
      expect(find.text('Tugas ujian PMB'), findsNothing);
      expect(tester.takeException(), isNull);
    });

    testWidgets('shows summary and the next session', (tester) async {
      final api = FakeApi()
        ..get('/pmb/admin/examiner/sessions', sessionsJson());
      await pumpApp(
        tester,
        const Scaffold(
          body: SingleChildScrollView(child: PmbDutyHomeSection()),
        ),
        api: api,
        user: pmbLecturer,
      );
      await settle(tester);
      expect(find.text('Tugas ujian PMB'), findsOneWidget);
      expect(find.text('1 sesi aktif dari 2 sesi ditugaskan'), findsOneWidget);
      expect(find.text('S1-LAB1'), findsOneWidget);
      expect(find.text('W-01'), findsNothing);
    });
  });
}
