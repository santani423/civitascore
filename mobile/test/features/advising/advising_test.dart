import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/advising/domain/krs_submission.dart';
import 'package:civitasone/features/advising/presentation/screens/advising_screen.dart';
import 'package:civitasone/features/advising/presentation/screens/krs_submission_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

Map<String, dynamic> krs({String status = 'submitted'}) => {
  'id': 'K1',
  'status': status,
  'status_label': 'Diajukan',
  'total_credits': 21,
  'max_credits': 24,
  'submitted_at': '2026-10-01T08:00:00+07:00',
  'decided_at': null,
  'decided_by': null,
  'decision_note': null,
  'term': {'id': 'T1', 'label': '2026/2027 Ganjil'},
  'student': {
    'id': 'S1',
    'nim': '2101001',
    'name': 'Andi Pratama',
    'admission_year': 2021,
    'status': 'active',
    'study_program': {'name': 'Informatika'},
    'academic_advisor': {'id': 'L1', 'name': 'Dr. Maria'},
  },
  'items': [
    {
      'id': 'I1',
      'status': 'pending',
      'status_label': 'Menunggu',
      'class_section': {
        'id': 'C1',
        'class_code': 'IF101-A',
        'course': {
          'id': 'CR1',
          'code': 'IF101',
          'name': 'Algoritma',
          'credits': 3,
        },
        'lecturer': {'id': 'L1', 'name': 'Dr. Maria'},
        'schedules': [
          {
            'id': 'SC',
            'day_of_week': 1,
            'start_time': '08:00',
            'end_time': '10:00',
            'room': 'R1',
          },
        ],
      },
    },
  ],
};

const meta = {'current_page': 1, 'per_page': 20, 'total': 1, 'last_page': 1};

void main() {
  test('parses submission', () {
    final k = KrsSubmission.fromJson(krs());
    expect(k.totalCredits, 21);
    expect(k.student!.studyProgram, 'Informatika');
    expect(k.items.single.schedules.single.room, 'R1');
    expect(k.isSubmitted, isTrue);
    expect(k.overLimit, isFalse);
  });

  testWidgets('list renders, filters by status and opens detail', (t) async {
    final api = FakeApi()..get('/krs-submissions', [krs()], meta: meta);
    await pumpApp(t, const AdvisingScreen(), api: api, user: testLecturer);
    expect(find.text('Andi Pratama'), findsOneWidget);
    expect(api.requests.first.query['status'], 'submitted');
    await t.tap(find.text('Disetujui'));
    await t.pumpAndSettle();
    expect(api.requests.last.query['status'], 'approved');
    await t.tap(find.text('Andi Pratama'));
    await t.pumpAndSettle();
    expect(find.text('route:/l/advising/krs/K1'), findsOneWidget);
  });

  testWidgets('empty and small phone dark', (t) async {
    final api = FakeApi()..get('/krs-submissions', [], meta: meta);
    await pumpApp(
      t,
      const AdvisingScreen(),
      api: api,
      user: testLecturer,
      size: TestScreens.smallPhone,
      themeMode: ThemeMode.dark,
    );
    expect(find.text('Tidak ada pengajuan KRS'), findsOneWidget);
    expect(t.takeException(), isNull);
  });

  testWidgets('error state', (t) async {
    final api = FakeApi()
      ..on('GET', '/krs-submissions', (_) => FakeResponse.error(500, 'x'));
    await pumpApp(t, const AdvisingScreen(), api: api, user: testLecturer);
    expect(find.text('Andi Pratama'), findsNothing);
    expect(find.byType(OutlinedButton), findsWidgets);
  });

  testWidgets('no permission blocks', (t) async {
    await pumpApp(t, const AdvisingScreen(), api: FakeApi());
    expect(find.text('Andi Pratama'), findsNothing);
  });

  testWidgets('detail approve posts and shows 409 message', (t) async {
    final api = FakeApi()
      ..get('/krs-submissions/K1', krs())
      ..on(
        'POST',
        '/krs-submissions/K1/approve',
        (_) => FakeResponse.error(409, 'Mahasiswa dicekal (ACADEMIC_BLOCKED).'),
      );
    await pumpApp(
      t,
      const KrsSubmissionScreen(id: 'K1'),
      api: api,
      user: testLecturer,
    );
    expect(find.text('Algoritma'), findsOneWidget);
    expect(find.text('21 / 24 SKS'), findsOneWidget);
    await t.tap(find.text('Setujui'));
    await t.pumpAndSettle();
    await t.tap(find.widgetWithText(FilledButton, 'Setujui').last);
    await t.pumpAndSettle();
    expect(
      api.requests.any((r) => r.path == '/krs-submissions/K1/approve'),
      isTrue,
    );
    expect(find.textContaining('dicekal'), findsOneWidget);
  });

  testWidgets('detail reject requires note and posts it (EN)', (t) async {
    final api = FakeApi()
      ..get('/krs-submissions/K1', krs())
      ..on(
        'POST',
        '/krs-submissions/K1/reject',
        (_) => FakeResponse.ok(krs(status: 'rejected'), message: 'Ditolak'),
      );
    await pumpApp(
      t,
      const KrsSubmissionScreen(id: 'K1'),
      api: api,
      user: testLecturer,
      language: AppLanguage.en,
    );
    await t.tap(find.text('Reject / return'));
    await t.pumpAndSettle();
    await t.enterText(find.byType(TextFormField), 'Terlalu banyak SKS');
    await t.tap(find.widgetWithText(FilledButton, 'Reject / return'));
    await t.pumpAndSettle();
    final req = api.requests.lastWhere((r) => r.path.endsWith('/reject'));
    expect((req.body as Map)['note'], 'Terlalu banyak SKS');
  });
}
