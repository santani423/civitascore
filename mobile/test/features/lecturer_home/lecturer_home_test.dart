import 'package:civitasone/features/lecturer_home/domain/home_models.dart';
import 'package:civitasone/features/lecturer_home/presentation/screens/lecturer_home_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

Map<String, dynamic> cls(int day) => {
  'id': 'C1',
  'class_code': 'IF101-A',
  'course': {'id': 'x', 'code': 'IF101', 'name': 'Algoritma', 'credits': 3},
  'schedules': [
    {
      'id': 's',
      'day_of_week': day,
      'start_time': '00:00',
      'end_time': '23:59',
      'room': 'R1',
    },
  ],
  'enrolled_count': 30,
};

const meta = {'current_page': 1, 'per_page': 20, 'total': 3, 'last_page': 1};

void main() {
  test('slotsForDay and nextSlot', () {
    final c = TeachingClass.fromJson(cls(2));
    final today = slotsForDay([c], 2);
    expect(today, hasLength(1));
    expect(slotsForDay([c], 3), isEmpty);
    expect(nextSlot(today, DateTime(2026, 1, 1, 9)), isNotNull);
    expect(nextSlot(today, DateTime(2026, 1, 1, 23, 59, 30)), isNull);
  });

  testWidgets('renders teaching and advising sections', (t) async {
    final api = FakeApi()
      ..get('/teaching/classes', [cls(DateTime.now().weekday)], meta: meta)
      ..get('/krs-submissions', [
        {
          'id': 'K1',
          'status': 'submitted',
          'total_credits': 20,
          'student': {'id': 'S1', 'nim': '2101001', 'name': 'Andi Pratama'},
        },
      ], meta: meta)
      ..get('/notifications/unread-count', {'unread_count': 2});
    await pumpApp(t, const LecturerHomeScreen(), api: api, user: testLecturer);
    expect(find.text('Algoritma'), findsOneWidget);
    await t.dragUntilVisible(
      find.text('Andi Pratama'),
      find.byType(ListView),
      const Offset(0, -200),
    );
    expect(find.text('Andi Pratama'), findsOneWidget);
    expect(find.textContaining('3 pengajuan'), findsOneWidget);
    await t.ensureVisible(find.text('Jadwal'));
    // Tunggu frame setelah scroll; posisi tap harus dihitung ulang dulu.
    await t.pumpAndSettle();
    await t.tap(find.text('Jadwal'));
    await t.pumpAndSettle();
    expect(find.text('route:/l/schedule'), findsOneWidget);
  });

  testWidgets('one section failing does not break the other (dark, small)', (
    t,
  ) async {
    final api = FakeApi()
      ..on('GET', '/teaching/classes', (_) => FakeResponse.error(500, 'x'))
      ..get(
        '/krs-submissions',
        [],
        meta: {'current_page': 1, 'per_page': 5, 'total': 0, 'last_page': 1},
      );
    await pumpApp(
      t,
      const LecturerHomeScreen(),
      api: api,
      user: testLecturer,
      size: TestScreens.smallPhone,
      themeMode: ThemeMode.dark,
    );
    expect(find.text('Bagian ini gagal dimuat.'), findsOneWidget);
    await t.drag(find.byType(ListView), const Offset(0, -250));
    await t.pumpAndSettle();
    expect(
      find.text('Tidak ada pengajuan KRS yang menunggu.', skipOffstage: false),
      findsOneWidget,
    );
    expect(t.takeException(), isNull);
  });
}
