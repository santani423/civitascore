import 'package:civitasone/core/network/api_failure.dart';
import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/lecturer_assessment/data/assessment_api.dart';
import 'package:civitasone/features/lecturer_assessment/domain/assessment_models.dart';
import 'package:civitasone/features/lecturer_assessment/presentation/screens/class_attendance_screen.dart';
import 'package:civitasone/features/lecturer_assessment/presentation/screens/class_grades_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

Map<String, dynamic> krs(
  String id,
  String name,
  String nim, {
  String? letter,
}) => {
  'id': id,
  'student_id': 's$id',
  'student_name': name,
  'student_nim': nim,
  'class_section_id': 'cs1',
  'status': 'enrolled',
  'letter_grade': letter,
};

Map<String, dynamic> att(String id, String k, int n, String st) => {
  'id': id,
  'krs_item_id': k,
  'student_name': k == 'k1' ? 'Andi' : 'Budi',
  'student_nim': k == 'k1' ? '001' : '002',
  'meeting_number': n,
  'meeting_date': '2026-10-0$n',
  'status': st,
  'notes': null,
  'created_at': '2026-10-01T00:00:00+00:00',
};

FakeApi baseApi() {
  final api = FakeApi();
  api.get(
    '/krs-items',
    [krs('k2', 'Budi', '002'), krs('k1', 'Andi', '001', letter: 'AB')],
    meta: {'current_page': 1, 'per_page': 100, 'total': 2, 'last_page': 1},
  );
  api.get(
    '/attendances',
    [
      att('a1', 'k1', 1, 'present'),
      att('a2', 'k2', 1, 'absent'),
      att('a3', 'k1', 2, 'sick'),
      att('a4', 'k2', 2, 'present'),
    ],
    meta: {'current_page': 1, 'per_page': 100, 'total': 4, 'last_page': 1},
  );
  api.get(
    '/grades',
    [
      {'id': 'g1', 'krs_item_id': 'k1', 'score': '78.00', 'letter_grade': 'AB'},
    ],
    meta: {'current_page': 1, 'per_page': 100, 'total': 1, 'last_page': 1},
  );
  return api;
}

void main() {
  group('models/api', () {
    test('recap and meetings computed client-side', () async {
      final api = baseApi();
      final a = AssessmentApi(api.client());
      final sheet = AttendanceSheet(
        roster: await a.roster('cs1'),
        records: await a.attendances('cs1'),
      );
      expect(sheet.roster.first.nim, '001'); // sorted by NIM
      expect(sheet.meetings.map((m) => m.number), [1, 2]);
      expect(sheet.nextMeetingNumber, 3);
      final r = sheet.recaps;
      expect(r[0].percentage, 50);
      expect(r[0].atRisk, isTrue);
      expect(r[1].absent, 1);
      expect(api.requests.first.query['filter[class_section_id]'], 'cs1');
      expect(api.requests.first.query['filter[status]'], 'enrolled');
    });

    test('grades parse decimal strings and join roster', () async {
      final a = AssessmentApi(baseApi().client());
      final rows = joinGrades(await a.roster('cs1'), await a.grades('cs1'));
      expect(rows[0].score, 78);
      expect(rows[1].graded, isFalse);
    });

    test('recordMeeting and saveGrade payloads', () async {
      final api = baseApi();
      api.on(
        'POST',
        '/class-sections/{id}/attendances',
        (_) => FakeResponse.ok([], message: 'Kehadiran berhasil direkam.'),
      );
      api.on(
        'PUT',
        '/krs-items/{id}/grade',
        (_) => FakeResponse.ok({
          'krs_item_id': 'k1',
          'score': '90.00',
          'letter_grade': 'A',
        }),
      );
      final a = AssessmentApi(api.client());
      final msg = await a.recordMeeting(
        'cs1',
        meetingNumber: 3,
        meetingDate: DateTime(2026, 10, 5),
        entries: {'k1': AttendanceStatus.permitted},
      );
      expect(msg, 'Kehadiran berhasil direkam.');
      final post = api.requests.last;
      expect(post.body, {
        'meeting_number': 3,
        'meeting_date': '2026-10-05',
        'entries': [
          {'krs_item_id': 'k1', 'status': 'permitted'},
        ],
      });
      final g = await a.saveGrade('k1', score: 90);
      expect(g.letterGrade, 'A');
      expect(api.requests.last.body, {'score': 90.0});
      await a.saveGrade('k1', score: 90, letter: 'B');
      expect(api.requests.last.body, {'score': 90.0, 'letter_grade': 'B'});
    });
  });

  group('attendance screen', () {
    testWidgets('lists meetings and recap, opens record form', (t) async {
      final api = baseApi();
      api.on(
        'POST',
        '/class-sections/{id}/attendances',
        (_) => FakeResponse.ok([], message: 'Kehadiran berhasil direkam.'),
      );
      await pumpApp(
        t,
        const ClassAttendanceScreen(classSectionId: 'cs1'),
        api: api,
        user: testLecturer,
      );
      expect(find.text('Pertemuan 2'), findsOneWidget);
      expect(find.text('Pertemuan 1'), findsOneWidget);
      await t.tap(find.text('Rekap'));
      await t.pumpAndSettle();
      expect(find.text('50%'), findsNWidgets(2));
      expect(find.text('Di bawah 75%'), findsNWidgets(2));
      await t.tap(find.text('Pertemuan'));
      await t.pumpAndSettle();
      await t.tap(find.text('Rekam pertemuan'));
      await t.pumpAndSettle();
      expect(find.text('Simpan presensi'), findsOneWidget);
      await t.tap(find.text('Alpa').first); // mark all absent
      await t.pumpAndSettle();
      await t.tap(find.text('Simpan presensi'));
      await t.pumpAndSettle();
      final post = api.requests.lastWhere((r) => r.method == 'POST');
      final body = post.body as Map;
      expect(body['meeting_number'], 3);
      expect(
        (body['entries'] as List).every((e) => e['status'] == 'absent'),
        isTrue,
      );
    });

    testWidgets('empty state, EN, dark, small phone', (t) async {
      final api = FakeApi()
        ..get('/krs-items', [], meta: {})
        ..get('/attendances', [], meta: {});
      await pumpApp(
        t,
        const ClassAttendanceScreen(classSectionId: 'cs1'),
        api: api,
        user: testLecturer,
        language: AppLanguage.en,
        themeMode: ThemeMode.dark,
        size: TestScreens.smallPhone,
      );
      expect(find.text('No meetings yet'), findsOneWidget);
      expect(t.takeException(), isNull);
    });

    testWidgets('403 shows access denied', (t) async {
      final api = FakeApi()
        ..on('GET', '/krs-items', (_) => FakeResponse.error(403, 'Forbidden'))
        ..on(
          'GET',
          '/attendances',
          (_) => FakeResponse.error(403, 'Forbidden'),
        );
      await pumpApp(
        t,
        const ClassAttendanceScreen(classSectionId: 'cs1'),
        api: api,
        user: testLecturer,
      );
      expect(find.text('Akses ditolak'), findsOneWidget);
    });
  });

  group('grades screen', () {
    testWidgets('shows scores and edits with backend letter', (t) async {
      final api = baseApi();
      api.on(
        'PUT',
        '/krs-items/{id}/grade',
        (_) => FakeResponse.ok({
          'krs_item_id': 'k2',
          'score': '66.00',
          'letter_grade': 'B',
        }),
      );
      await pumpApp(
        t,
        const ClassGradesScreen(classSectionId: 'cs1'),
        api: api,
        user: testLecturer,
      );
      expect(find.text('78'), findsOneWidget);
      expect(find.text('Belum dinilai'), findsOneWidget);
      await t.tap(find.text('Budi'));
      await t.pumpAndSettle();
      await t.enterText(find.byType(TextFormField), '101');
      await t.tap(find.text('Simpan'));
      await t.pumpAndSettle();
      expect(find.textContaining('0'), findsWidgets);
      expect(api.requests.where((r) => r.method == 'PUT'), isEmpty);
      await t.enterText(find.byType(TextFormField), '66');
      await t.tap(find.text('Simpan'));
      await t.pumpAndSettle();
      expect(api.requests.last.body, {'score': 66.0});
      expect(find.text('B'), findsOneWidget);
    });

    testWidgets('bulk mode saves sequentially and reports row errors', (
      t,
    ) async {
      final api = baseApi();
      api.on('PUT', '/krs-items/{id}/grade', (r) {
        if (r.path.contains('k2')) {
          return FakeResponse.error(409, 'KRS ini belum disetujui dosen wali.');
        }
        return FakeResponse.ok({
          'krs_item_id': 'k1',
          'score': '80.00',
          'letter_grade': 'AB',
        });
      });
      await pumpApp(
        t,
        const ClassGradesScreen(classSectionId: 'cs1'),
        api: api,
        user: testLecturer,
        size: TestScreens.smallPhone,
      );
      await t.tap(find.byTooltip('Isi massal'));
      await t.pumpAndSettle();
      await t.enterText(find.byKey(const ValueKey('bulk:k1')), '80');
      await t.enterText(find.byKey(const ValueKey('bulk:k2')), '70');
      await t.pump();
      await t.tap(find.text('Simpan 2 nilai'));
      await t.pumpAndSettle();
      final puts = api.requests.where((r) => r.method == 'PUT').toList();
      expect(puts.length, 2);
      expect(find.text('KRS ini belum disetujui dosen wali.'), findsOneWidget);
      expect(t.takeException(), isNull);
    });
  });

  test('forbidden failure type exists', () {
    expect(ApiFailureKind.forbidden, isNotNull);
  });
}
