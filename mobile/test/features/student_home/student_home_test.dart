import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/student_home/domain/finance_summary.dart';
import 'package:civitasone/features/student_home/domain/portal_links.dart';
import 'package:civitasone/features/student_home/domain/student_dashboard.dart';
import 'package:civitasone/features/student_home/student_home_routes.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

Map<String, dynamic> dashboardJson() => {
  'profile': {
    'name': 'Andi Pratama',
    'nim': '2101001',
    'photo_file_id': null,
    'study_program': 'Teknik Informatika',
    'faculty': 'Teknik',
    'admission_year': 2021,
    'semester': 5,
    'status': {'value': 'active', 'label': 'Aktif'},
  },
  'academic': {
    'ipk': 3.45,
    'last_ips': {
      'academic_term_id': 't1',
      'label': '2026/2027 Ganjil',
      'ips': 3.6,
    },
    'total_credits': 90,
    'passed_credits': 88,
    'current_term_credits': 20,
    'max_credits': 24,
    'remaining_credits': 4,
    'current_term': {'id': 't2', 'label': '2026/2027 Genap'},
    'academic_advisor': {'id': 'l1', 'name': 'Dr. Maria', 'email': null},
  },
  'krs': {
    'status': 'approved',
    'status_label': 'Disetujui',
    'total_credits': 20,
    'max_credits': 24,
    'course_count': 7,
    'is_open': false,
    'krs_end_date': null,
    'decision_note': null,
  },
  'today_schedule': [
    {
      'course_name': 'Basis Data',
      'course_code': 'IF301',
      'start_time': '08:00',
      'end_time': '09:40',
      'room': 'Lab 1',
      'lecturer': {'id': 'l2', 'name': 'Pak Budi', 'email': null},
      'status': 'upcoming',
      'date': '2026-10-19',
    },
  ],
  'next_class': null,
  'attendance': {
    'overall': {'total_meetings': 10, 'present': 8, 'percentage': 80.0},
    'minimum_percent': 75.0,
    'below_minimum': [
      {'course_name': 'Kalkulus', 'percentage': 60.0, 'krs_item_id': 'k1'},
    ],
  },
  'upcoming_exams': [
    {
      'id': 'e1',
      'title': 'UTS Basis Data',
      'course_name': 'Basis Data',
      'starts_at': '2026-10-25T02:00:00+00:00',
      'ends_at': null,
      'duration_minutes': 90,
      'status': 'upcoming',
    },
  ],
  'assignments_due': [
    {
      'id': 'a1',
      'title': 'Tugas ERD',
      'due_at': '2026-10-21T10:00:00+00:00',
      'course': {'name': 'Basis Data'},
    },
  ],
  'recent_grades': [
    {'course_code': 'IF201', 'course_name': 'Algoritma', 'letter_grade': 'A'},
  ],
  'announcements': {
    'unread_count': 2,
    'latest': [
      {
        'id': 'n1',
        'title': 'Libur Nasional',
        'published_at': '2026-10-18T01:00:00+00:00',
        'is_pinned': true,
        'is_read': false,
      },
    ],
  },
  'notifications_unread': 3,
  'alerts': [
    {
      'type': 'krs',
      'severity': 'warning',
      'message': 'KRS semester ini belum diambil.',
      'link': '/portal/krs',
    },
    {
      'type': 'x',
      'severity': 'info',
      'message': 'Pesan tanpa tujuan.',
      'link': '/portal/unknown',
    },
  ],
};

Map<String, dynamic> financeJson() => {
  'outstanding': '7500000.00',
  'arrears': '1500000.00',
  'open_invoices': 2,
  'next_due': {'date': '2026-11-01', 'amount': '2500000.00'},
  'deposit_available': '0.00',
  'pending_verification': 1,
  'services': [
    {'service': 'krs', 'label': 'Pengisian KRS', 'blocked': true},
    {'service': 'exam', 'label': 'Ujian', 'blocked': false},
  ],
  'blocks': <dynamic>[],
};

FakeApi api({bool finance = true, Map<String, dynamic>? dash}) {
  final a = FakeApi()
    ..get('/student/dashboard', dash ?? dashboardJson())
    ..get('/notifications/unread-count', {'unread_count': 3});
  if (finance) a.get('/student/finance/summary', financeJson());
  return a;
}

void main() {
  group('parsing', () {
    test('dashboard model', () {
      final d = StudentDashboard.fromJson(dashboardJson());
      expect(d.profile!.nim, '2101001');
      expect(d.academic!.ipk, 3.45);
      expect(d.academic!.ips, 3.6);
      expect(d.attendance!.belowMinimum.single.courseName, 'Kalkulus');
      expect(d.unreadAnnouncements, 2);
      expect(d.alerts, hasLength(2));
    });

    test('tolerates empty / missing sections', () {
      final d = StudentDashboard.fromJson(<dynamic>[]);
      expect(d.profile, isNull);
      expect(d.todaySchedule, isEmpty);
      expect(d.nextClass, isNull);
    });

    test('finance summary decimal strings', () {
      final f = FinanceSummary.fromJson(financeJson());
      expect(f.hasOutstanding, isTrue);
      expect(f.hasArrears, isTrue);
      expect(f.nextDueAmount, '2500000.00');
      expect(f.blockedServices, ['Pengisian KRS']);
      expect(
        FinanceSummary.fromJson({'outstanding': '0.00'}).hasOutstanding,
        isFalse,
      );
    });

    test('portal link mapping', () {
      expect(routeForPortalLink('/portal/krs'), '/s/krs');
      expect(routeForPortalLink('/portal/ujian'), '/s/exams');
      expect(routeForPortalLink('/portal/tugas/abc'), '/s/assignments/abc');
      expect(routeForPortalLink('/portal/nilai'), '/s/grades');
      expect(routeForPortalLink('/portal/absensi'), '/s/attendance');
      expect(routeForPortalLink('/portal/pengumuman'), '/s/announcements');
      expect(routeForPortalLink('/portal/akademik'), '/s/profile');
      expect(routeForPortalLink('/portal/keuangan?x=1'), '/s/finance');
      expect(routeForPortalLink('/portal/unknown'), isNull);
      expect(routeForPortalLink('https://x.test/portal/krs'), isNull);
      expect(routeForPortalLink(null), isNull);
    });
  });

  group('screen', () {
    testWidgets('renders data in Indonesian', (tester) async {
      await pumpApp(tester, const StudentHomeScreen(), api: api());
      expect(find.text('Andi Pratama'), findsOneWidget);
      expect(find.textContaining('2101001'), findsOneWidget);
      expect(find.text('Basis Data'), findsWidgets);
      expect(find.text('Libur Nasional'), findsOneWidget);
      expect(find.text('Kalkulus · 60%'), findsOneWidget);
      expect(find.text('Rp 7.500.000'), findsOneWidget);
      expect(find.text('Tagihan'), findsWidgets);
      expect(tester.takeException(), isNull);
    });

    testWidgets('finance failure does not break dashboard', (tester) async {
      await pumpApp(
        tester,
        const StudentHomeScreen(),
        api: api(finance: false),
      );
      expect(find.text('Andi Pratama'), findsOneWidget);
      expect(
        find.text('Ringkasan keuangan tidak dapat dimuat.'),
        findsOneWidget,
      );
    });

    testWidgets('alert navigates; unknown link does nothing', (tester) async {
      await pumpApp(tester, const StudentHomeScreen(), api: api());
      await tester.tap(find.text('Pesan tanpa tujuan.'));
      await tester.pumpAndSettle();
      expect(find.text('Andi Pratama'), findsOneWidget);
      await tester.tap(find.text('KRS semester ini belum diambil.'));
      await tester.pumpAndSettle();
      expect(find.text('route:/s/krs'), findsOneWidget);
    });

    testWidgets('finance card goes to finance tab', (tester) async {
      await pumpApp(tester, const StudentHomeScreen(), api: api());
      await tester.scrollUntilVisible(
        find.text('Rp 7.500.000'),
        300,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Rp 7.500.000'));
      await tester.pumpAndSettle();
      expect(find.text('route:/s/finance'), findsOneWidget);
    });

    testWidgets('quick action navigates', (tester) async {
      await pumpApp(tester, const StudentHomeScreen(), api: api());
      await tester.scrollUntilVisible(
        find.text('Kalender'),
        300,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Kalender'));
      await tester.pumpAndSettle();
      expect(find.text('route:/s/calendar'), findsOneWidget);
    });

    testWidgets('empty sections degrade gracefully', (tester) async {
      await pumpApp(
        tester,
        const StudentHomeScreen(),
        api: api(
          dash: {
            'profile': {'name': 'Andi Pratama', 'nim': '1'},
          },
        ),
      );
      expect(find.text('Tidak ada kelas hari ini.'), findsOneWidget);
      expect(find.text('Tidak ada ujian mendatang.'), findsOneWidget);
      expect(find.text('Belum ada pengumuman.'), findsOneWidget);
    });

    testWidgets('error state with retry', (tester) async {
      await pumpApp(tester, const StudentHomeScreen(), api: FakeApi());
      expect(find.text('Coba lagi'), findsOneWidget);
    });

    testWidgets('English + dark + small phone', (tester) async {
      await pumpApp(
        tester,
        const StudentHomeScreen(),
        api: api(),
        language: AppLanguage.en,
        themeMode: ThemeMode.dark,
        size: TestScreens.smallPhone,
      );
      expect(find.text('Home'), findsOneWidget);
      expect(find.text('Quick actions'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });

    testWidgets('Chinese renders', (tester) async {
      await pumpApp(
        tester,
        const StudentHomeScreen(),
        api: api(),
        language: AppLanguage.zh,
        size: TestScreens.smallPhone,
      );
      expect(find.text('首页'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  });
}
