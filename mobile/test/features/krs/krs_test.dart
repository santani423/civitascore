import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/krs/data/krs_api.dart';
import 'package:civitasone/features/krs/presentation/screens/krs_history_screen.dart';
import 'package:civitasone/features/krs/presentation/screens/krs_offerings_screen.dart';
import 'package:civitasone/features/krs/presentation/screens/krs_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

Map<String, dynamic> _term({bool open = true}) => {
  'id': 'T1',
  'academic_year': '2026/2027',
  'semester': 'ganjil',
  'label': '2026/2027 Ganjil',
  'start_date': '2026-09-01',
  'end_date': '2027-01-31',
  'is_current': true,
  'krs_start_date': '2026-08-01',
  'krs_end_date': '2026-09-15',
  'is_krs_open': open,
};

Map<String, dynamic> _section(String id, String code, String name) => {
  'id': id,
  'class_code': 'A',
  'course': {
    'id': 'C-$id',
    'code': code,
    'name': name,
    'credits': 3,
    'semester_level': 3,
  },
  'lecturer': {'id': 'L1', 'name': 'Dr. Budi', 'email': null},
  'schedules': [
    {
      'id': 'S1',
      'day_of_week': 1,
      'day_label': 'Senin',
      'start_time': '08:00',
      'end_time': '09:40',
      'room': 'R101',
    },
  ],
};

Map<String, dynamic> _overview({
  String status = 'draft',
  bool canEdit = true,
  bool canSubmit = true,
  bool canCancel = false,
  List<String> notices = const [],
  Map<String, dynamic>? submission,
}) => {
  'term': _term(),
  'status': status,
  'status_label': 'Draf',
  'submission': submission,
  'items': [
    {
      'id': 'I1',
      'status': status == 'submitted' ? 'pending' : 'draft',
      'status_label': 'Draf',
      'class_section': _section('CS1', 'IF201', 'Basis Data'),
    },
  ],
  'total_credits': 3,
  'max_credits': 20,
  'remaining_credits': 17,
  'can_edit': canEdit,
  'can_submit': canSubmit,
  'can_cancel_submission': canCancel,
  'notices': notices,
  'academic_advisor': {'id': 'L2', 'name': 'Dr. Wali', 'email': null},
};

Map<String, dynamic> _offerings() => {
  'term': _term(),
  'courses': [
    {
      'course': {
        'id': 'C9',
        'code': 'IF301',
        'name': 'Jaringan',
        'credits': 3,
        'semester_level': 3,
      },
      'prerequisites': [
        {
          'course_id': 'C1',
          'code': 'IF101',
          'name': 'Dasar',
          'min_grade': 'C',
          'satisfied': true,
        },
      ],
      'best_grade': null,
      'is_retake': false,
      'is_selected': false,
      'selected_class_section_id': null,
      'blockers': [],
      'classes': [
        {
          ..._section('CS9', 'IF301', 'Jaringan'),
          'capacity': 40,
          'seats_taken': 10,
          'seats_left': 30,
          'is_full': false,
          'is_selected': false,
          'conflicts': [],
        },
      ],
    },
    {
      'course': {
        'id': 'C8',
        'code': 'IF302',
        'name': 'Kecerdasan',
        'credits': 3,
        'semester_level': 3,
      },
      'prerequisites': [],
      'best_grade': null,
      'is_retake': false,
      'is_selected': false,
      'selected_class_section_id': null,
      'blockers': ['Prasyarat belum terpenuhi'],
      'classes': [
        {
          ..._section('CS8', 'IF302', 'Kecerdasan'),
          'capacity': 40,
          'seats_taken': 40,
          'seats_left': 0,
          'is_full': true,
          'is_selected': false,
          'conflicts': [],
        },
      ],
    },
  ],
  'total_credits': 3,
  'max_credits': 20,
};

void main() {
  testWidgets('KRS overview renders data in Indonesian', (tester) async {
    final api = FakeApi()..get('/student/krs', _overview());
    await pumpApp(tester, const KrsScreen(), api: api);
    expect(find.text('2026/2027 Ganjil'), findsOneWidget);
    expect(find.text('Basis Data'), findsOneWidget);
    expect(find.text('3 dari 20 SKS'), findsOneWidget);
    expect(find.text('Ajukan KRS'), findsOneWidget);
  });

  testWidgets('submit asks confirmation then posts', (tester) async {
    final api = FakeApi()
      ..get('/student/krs', _overview())
      ..on(
        'POST',
        '/student/krs/submit',
        (_) => FakeResponse.ok(
          _overview(
            status: 'submitted',
            canEdit: false,
            canSubmit: false,
            canCancel: true,
          ),
        ),
      );
    await pumpApp(tester, const KrsScreen(), api: api);
    await tester.tap(find.text('Ajukan KRS'));
    await tester.pumpAndSettle();
    expect(find.text('Ajukan KRS ke dosen wali?'), findsOneWidget);
    await tester.tap(
      find.descendant(
        of: find.byType(AlertDialog),
        matching: find.text('Ajukan KRS'),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      api.requests.where(
        (r) => r.method == 'POST' && r.path.endsWith('/student/krs/submit'),
      ),
      hasLength(1),
    );
    expect(find.text('Tarik pengajuan'), findsOneWidget);
    expect(find.byIcon(Icons.delete_outline), findsNothing);
  });

  testWidgets('remove deletes the item after confirm', (tester) async {
    final api = FakeApi()
      ..get('/student/krs', _overview())
      ..on(
        'DELETE',
        '/student/krs/items/{id}',
        (_) =>
            FakeResponse.ok({..._overview(), 'items': [], 'can_submit': false}),
      );
    await pumpApp(tester, const KrsScreen(), api: api);
    await tester.tap(find.byIcon(Icons.delete_outline));
    await tester.pumpAndSettle();
    await tester.tap(
      find.descendant(
        of: find.byType(AlertDialog),
        matching: find.text('Hapus'),
      ),
    );
    await tester.pumpAndSettle();
    expect(
      api.requests.any(
        (r) => r.method == 'DELETE' && r.path.endsWith('/student/krs/items/I1'),
      ),
      isTrue,
    );
    expect(find.text('Belum ada mata kuliah'), findsOneWidget);
  });

  testWidgets('locked KRS hides mutations and shows notices (EN)', (
    tester,
  ) async {
    final api = FakeApi()
      ..get(
        '/student/krs',
        _overview(
          status: 'approved',
          canEdit: false,
          canSubmit: false,
          notices: ['Pengisian KRS diblokir oleh cekal: Tunggakan'],
        ),
      );
    await pumpApp(
      tester,
      const KrsScreen(),
      api: api,
      language: AppLanguage.en,
    );
    expect(find.text('Submit KRS'), findsNothing);
    expect(find.text('Add courses'), findsNothing);
    expect(find.byIcon(Icons.delete_outline), findsNothing);
    expect(find.text('View hold status'), findsOneWidget);
  });

  testWidgets('no active term shows message', (tester) async {
    final api = FakeApi()
      ..get('/student/krs', {
        'term': null,
        'status': 'no_active_term',
        'status_label': 'x',
        'items': [],
        'notices': [],
      });
    await pumpApp(tester, const KrsScreen(), api: api);
    expect(find.text('Belum ada semester aktif'), findsWidgets);
  });

  testWidgets('error state offers retry', (tester) async {
    final api = FakeApi()
      ..on('GET', '/student/krs', (_) => FakeResponse.error(500, 'boom'));
    await pumpApp(tester, const KrsScreen(), api: api);
    expect(find.text('Coba lagi'), findsOneWidget);
  });

  testWidgets('offerings: add posts class_section_id; blocked ones disabled', (
    tester,
  ) async {
    final api = FakeApi()
      ..get('/student/krs', _overview())
      ..get('/student/krs/offerings', _offerings())
      ..on(
        'POST',
        '/student/krs/items',
        (_) => FakeResponse(201, {
          'success': true,
          'message': 'ok',
          'data': _overview(),
          'meta': [],
        }),
      );
    await pumpApp(
      tester,
      const KrsOfferingsScreen(),
      api: api,
      themeMode: ThemeMode.dark,
    );
    expect(find.text('Jaringan'), findsOneWidget);
    expect(find.text('Sisa 30 dari 40 kursi'), findsOneWidget);
    final buttons = tester
        .widgetList<FilledButton>(find.byType(FilledButton))
        .toList();
    expect(buttons.first.onPressed, isNotNull);
    expect(buttons.last.onPressed, isNull);
    await tester.tap(find.widgetWithText(FilledButton, 'Ambil').first);
    await tester.pumpAndSettle();
    final post = api.requests.firstWhere((r) => r.method == 'POST');
    expect(post.body, {'class_section_id': 'CS9'});
  });

  testWidgets('offerings: ACADEMIC_BLOCKED shows banner with clearance link', (
    tester,
  ) async {
    final api = FakeApi()
      ..get('/student/krs', _overview())
      ..get('/student/krs/offerings', _offerings())
      ..on(
        'POST',
        '/student/krs/items',
        (_) => FakeResponse.error(
          409,
          'Pengisian KRS tidak dapat dilakukan karena terdapat cekal: Tunggakan',
          errors: {'code': 'ACADEMIC_BLOCKED', 'blocks': []},
        ),
      );
    await pumpApp(tester, const KrsOfferingsScreen(), api: api);
    await tester.tap(find.widgetWithText(FilledButton, 'Ambil').first);
    await tester.pumpAndSettle();
    expect(find.textContaining('terdapat cekal'), findsOneWidget);
    await tester.tap(find.text('Lihat status cekal'));
    await tester.pumpAndSettle();
    expect(find.text('route:/s/finance/clearance'), findsOneWidget);
  });

  testWidgets(
    'offerings: SCHEDULE_CONFLICT dialog; search filters; small phone',
    (tester) async {
      final api = FakeApi()
        ..get('/student/krs', _overview())
        ..get('/student/krs/offerings', _offerings())
        ..on(
          'POST',
          '/student/krs/items',
          (_) => FakeResponse.error(
            409,
            'Jadwal bentrok',
            errors: {
              'code': 'SCHEDULE_CONFLICT',
              'conflicts': [
                {
                  'course_name': 'Basis Data',
                  'schedule': 'Senin 08:00–10:30 (R1)',
                },
              ],
            },
          ),
        );
      await pumpApp(
        tester,
        const KrsOfferingsScreen(),
        api: api,
        size: TestScreens.smallPhone,
      );
      await tester.tap(find.widgetWithText(FilledButton, 'Ambil').first);
      await tester.pumpAndSettle();
      expect(find.text('Jadwal bentrok'), findsWidgets);
      expect(find.textContaining('Basis Data'), findsOneWidget);
      await tester.tap(find.text('Tutup'));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'kecerdasan');
      await tester.pumpAndSettle();
      expect(find.text('Jaringan'), findsNothing);
      expect(find.text('Kecerdasan'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );

  testWidgets('offerings disabled when KRS not editable', (tester) async {
    final api = FakeApi()
      ..get('/student/krs', _overview(canEdit: false, notices: ['KRS ditutup']))
      ..get('/student/krs/offerings', _offerings());
    await pumpApp(tester, const KrsOfferingsScreen(), api: api);
    for (final b in tester.widgetList<FilledButton>(
      find.byType(FilledButton),
    )) {
      expect(b.onPressed, isNull);
    }
  });

  testWidgets('history lists terms; empty state', (tester) async {
    final api = FakeApi()
      ..get('/student/krs/history', [
        {
          'term': _term(),
          'status': 'approved',
          'status_label': 'Disetujui',
          'total_credits': 3,
          'course_count': 1,
          'submission': {
            'status': 'approved',
            'status_label': 'Disetujui',
            'total_credits': 3,
            'max_credits': 20,
            'submitted_at': null,
            'decided_at': null,
            'decided_by': null,
            'decision_note': 'Baik',
          },
          'items': [
            {
              'id': 'I1',
              'status': 'enrolled',
              'status_label': 'Terdaftar',
              'course_code': 'IF201',
              'course_name': 'Basis Data',
              'credits': 3,
              'class_code': 'A',
              'letter_grade': 'A',
            },
          ],
        },
      ]);
    await pumpApp(
      tester,
      const KrsHistoryScreen(),
      api: api,
      language: AppLanguage.zh,
    );
    expect(find.text('2026/2027 Ganjil'), findsOneWidget);
    expect(find.text('Basis Data'), findsOneWidget);

    final empty = FakeApi()..get('/student/krs/history', []);
    await pumpApp(tester, const KrsHistoryScreen(), api: empty);
    expect(find.text('Belum ada riwayat KRS'), findsOneWidget);
  });

  test('api paths', () async {
    final fake = FakeApi()
      ..on('POST', '/student/krs/cancel', (_) => FakeResponse.ok(_overview()))
      ..get('/student/krs', _overview());
    final api = KrsApi(fake.client());
    final o = await api.cancel();
    expect(o.maxCredits, 20);
    expect(o.items.first.classSection.schedules.first.room, 'R101');
    expect(fake.requests.single.method, 'POST');
  });
}
