import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/schedule_attendance/data/schedule_attendance_api.dart';
import 'package:civitasone/features/schedule_attendance/presentation/screens/attendance_detail_screen.dart';
import 'package:civitasone/features/schedule_attendance/presentation/screens/attendance_screen.dart';
import 'package:civitasone/features/schedule_attendance/presentation/screens/calendar_screen.dart';
import 'package:civitasone/features/schedule_attendance/presentation/screens/schedule_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

Map<String, dynamic> _slot(int day, String name, {String? krs}) => {
  'day_of_week': day,
  'day_label': 'x',
  'start_time': '08:00',
  'end_time': '09:40',
  'room': 'R-101',
  'krs_item_id': krs ?? 'k1',
  'krs_status': 'enrolled',
  'is_pending_approval': false,
  'class_section_id': 'c1',
  'class_code': 'A',
  'course_code': 'IF101',
  'course_name': name,
  'credits': 3,
  'lecturer': 'Dr. Maria',
};

Map<String, dynamic> _schedule() {
  final now = DateTime.now();
  return {
    'term': {'id': 't1', 'label': '2026/2027 Ganjil', 'is_current': true},
    'now': now.toIso8601String(),
    'timezone': 'Asia/Jakarta',
    'is_in_term': true,
    'slots': [for (var d = 1; d <= 7; d++) _slot(d, 'Algoritma')],
    'today': [
      {..._slot(now.weekday, 'Algoritma'), 'status': 'upcoming'},
    ],
    'next_class': {
      ..._slot(now.weekday, 'Basis Data'),
      'starts_at': now.toIso8601String(),
    },
  };
}

Map<String, dynamic> _course(String id, String name, double? p, bool below) => {
  'krs_item_id': id,
  'class_section_id': 'c',
  'course_code': 'IF1',
  'course_name': name,
  'class_code': 'A',
  'credits': 3,
  'lecturer': 'Dr. Maria',
  'total_meetings': 10,
  'present': 6,
  'permitted': 1,
  'sick': 1,
  'absent': 2,
  'percentage': p,
  'is_below_minimum': below,
};

Map<String, dynamic> _summary() => {
  'term': {'id': 't1', 'label': '2026/2027 Ganjil'},
  'terms': [
    {'id': 't1', 'label': '2026/2027 Ganjil'},
    {'id': 't0', 'label': '2025/2026 Genap'},
  ],
  'minimum_percent': 75.0,
  'courses': [
    _course('k1', 'Algoritma', 60.0, true),
    _course('k2', 'Basis Data', 90.5, false),
  ],
  'overall': {
    'total_meetings': 20,
    'present': 15,
    'permitted': 1,
    'sick': 1,
    'absent': 3,
    'percentage': 75.0,
  },
};

void main() {
  test(
    'api parses attendance with null percentage and sends term query',
    () async {
      final api = FakeApi()
        ..get('/student/attendance', {
          'term': null,
          'terms': [],
          'minimum_percent': 75,
          'courses': [_course('k1', 'X', null, false)],
          'overall': {'total_meetings': 0, 'percentage': null},
        }, meta: {});
      final r = await ScheduleAttendanceApi(
        api.client(),
      ).attendance(termId: 'T');
      expect(r.courses.single.totals.percentage, isNull);
      expect(r.minimumPercent, 75.0);
      expect(api.requests.single.query['academic_term_id'], 'T');
    },
  );

  test('api calendar sends from/to', () async {
    final api = FakeApi()
      ..get('/student/calendar', [
        {
          'id': 'event:1',
          'type': 'academic',
          'category': 'holiday',
          'category_label': 'Libur',
          'title': 'Libur',
          'all_day': true,
          'start': '2026-10-17',
          'end': '2026-10-17',
        },
      ]);
    final r = await ScheduleAttendanceApi(
      api.client(),
    ).calendar(from: DateTime(2026, 10, 1), to: DateTime(2026, 10, 31));
    expect(r.single.allDay, isTrue);
    expect(api.requests.single.query, {
      'from': '2026-10-01',
      'to': '2026-10-31',
    });
  });

  testWidgets('schedule renders next class and today badge', (tester) async {
    final api = FakeApi()..get('/student/schedule', _schedule());
    await pumpApp(tester, const StudentScheduleScreen(), api: api);
    expect(find.text('Kelas berikutnya'), findsOneWidget);
    expect(find.text('Hari ini'), findsOneWidget);
    expect(find.text('Algoritma'), findsWidgets);
  });

  testWidgets('schedule EN dark smallPhone no overflow', (tester) async {
    final api = FakeApi()..get('/student/schedule', _schedule());
    await pumpApp(
      tester,
      const StudentScheduleScreen(),
      api: api,
      language: AppLanguage.en,
      themeMode: ThemeMode.dark,
      size: TestScreens.smallPhone,
    );
    expect(find.text('Next class'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('schedule empty and error states', (tester) async {
    final empty = FakeApi()
      ..get('/student/schedule', {'term': null, 'slots': [], 'today': []});
    await pumpApp(tester, const StudentScheduleScreen(), api: empty);
    expect(find.text('Belum ada jadwal'), findsOneWidget);

    final err = FakeApi()
      ..on('GET', '/student/schedule', (_) => FakeResponse.error(500, 'x'));
    await pumpApp(tester, const StudentScheduleScreen(), api: err);
    expect(find.text('Coba lagi'), findsOneWidget);
  });

  testWidgets('calendar shows event with category', (tester) async {
    final now = DateTime.now();
    final d =
        '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
    final api = FakeApi()
      ..get('/student/calendar', [
        {
          'id': 'event:1',
          'type': 'academic',
          'category': 'midterm_exam',
          'category_label': 'UTS',
          'title': 'Ujian Tengah',
          'all_day': true,
          'start': d,
          'end': d,
        },
      ]);
    await pumpApp(tester, const StudentCalendarScreen(), api: api);
    expect(find.text('Ujian Tengah'), findsOneWidget);
    expect(find.text('UTS'), findsOneWidget);
    expect(find.text('Mendatang'), findsOneWidget);
  });

  testWidgets('calendar empty', (tester) async {
    final api = FakeApi()..get('/student/calendar', []);
    await pumpApp(tester, const StudentCalendarScreen(), api: api);
    expect(find.text('Tidak ada agenda bulan ini'), findsOneWidget);
  });

  testWidgets('attendance shows banner, courses, pending tile, navigates', (
    tester,
  ) async {
    final api = FakeApi()..get('/student/attendance', _summary());
    await pumpApp(
      tester,
      const StudentAttendanceScreen(),
      api: api,
      size: TestScreens.smallPhone,
    );
    expect(find.textContaining('di bawah batas minimum'), findsOneWidget);
    expect(find.text('60%'), findsOneWidget);
    expect(find.text('90,5%'), findsOneWidget);
    expect(find.byKey(const Key('term_selector')), findsOneWidget);
    await tester.scrollUntilVisible(find.text('Presensi QR & izin'), 200);
    expect(find.text('Presensi QR & izin'), findsOneWidget);
    await tester.scrollUntilVisible(find.text('Algoritma'), -200);
    await tester.tap(find.text('Algoritma'));
    await tester.pumpAndSettle();
    expect(find.text('route:/s/attendance/k1'), findsOneWidget);
  });

  testWidgets('attendance without threshold shows no banner', (tester) async {
    final s = _summary()..['minimum_percent'] = null;
    final api = FakeApi()..get('/student/attendance', s);
    await pumpApp(tester, const StudentAttendanceScreen(), api: api);
    expect(find.textContaining('di bawah batas minimum'), findsNothing);
  });

  testWidgets('attendance dark EN', (tester) async {
    final api = FakeApi()..get('/student/attendance', _summary());
    await pumpApp(
      tester,
      const StudentAttendanceScreen(),
      api: api,
      language: AppLanguage.en,
      themeMode: ThemeMode.dark,
    );
    expect(find.text('Overall attendance'), findsOneWidget);
  });

  testWidgets('attendance detail lists meetings', (tester) async {
    final api = FakeApi()
      ..get('/student/attendance/k1', {
        'krs_item_id': 'k1',
        'term': {'id': 't1', 'label': '2026/2027 Ganjil'},
        'course_code': 'IF1',
        'course_name': 'Algoritma',
        'class_code': 'A',
        'lecturer': 'Dr. Maria',
        'minimum_percent': 75.0,
        'total_meetings': 2,
        'present': 1,
        'permitted': 0,
        'sick': 0,
        'absent': 1,
        'percentage': 50.0,
        'meetings': [
          {
            'meeting_number': 1,
            'meeting_date': '2026-09-01',
            'status': 'present',
            'status_label': 'Hadir',
            'notes': null,
          },
          {
            'meeting_number': 2,
            'meeting_date': '2026-09-08',
            'status': 'absent',
            'status_label': 'Alpa',
            'notes': 'Tanpa kabar',
          },
        ],
      });
    await pumpApp(
      tester,
      const StudentAttendanceDetailScreen(krsItemId: 'k1'),
      api: api,
    );
    expect(find.text('Pertemuan 1'), findsOneWidget);
    expect(find.text('Tanpa kabar'), findsOneWidget);
    expect(find.text('50%'), findsOneWidget);
  });

  testWidgets('attendance empty', (tester) async {
    final api = FakeApi()
      ..get('/student/attendance', {
        'term': null,
        'terms': [],
        'courses': [],
        'overall': {},
      });
    await pumpApp(tester, const StudentAttendanceScreen(), api: api);
    expect(find.text('Belum ada data presensi'), findsOneWidget);
  });
}
