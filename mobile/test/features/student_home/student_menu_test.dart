import 'package:civitasone/features/auth/domain/auth_user.dart';
import 'package:civitasone/features/student_home/presentation/screens/student_menu_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/pump_app.dart';

/// Mahasiswa dengan izin terbatas: tanpa keuangan dan tanpa KRS.
const _limitedStudent = AuthUser(
  id: '01LIMITED',
  name: 'Budi Santoso',
  email: 'budi@student.test',
  nim: '2101002',
  roles: ['student'],
  permissions: ['student_portal.read'],
);

/// Daftar memakai ListView lazy: item di bawah layar baru dibangun setelah
/// digulir. Hanya ListView yang digulir, bukan baris chip kategori.
Future<void> _scrollToEntry(WidgetTester tester, String title) async {
  await tester.scrollUntilVisible(
    find.text(title),
    200,
    scrollable: find.descendant(
      of: find.byType(ListView),
      matching: find.byType(Scrollable),
    ),
  );
  // scrollUntilVisible berhenti begitu item sudah dibangun, belum tentu
  // sudah di dalam layar; ensureVisible memastikan tap mengenai area layar.
  await tester.ensureVisible(find.text(title));
  await tester.pumpAndSettle();
}

void main() {
  group('Menu Utama', () {
    testWidgets('lists the services the student may open', (tester) async {
      await pumpApp(tester, const StudentMenuScreen());
      expect(find.text('Menu Utama'), findsOneWidget);
      expect(find.text('KRS Online'), findsOneWidget);
      await _scrollToEntry(tester, 'Beasiswa');
      expect(find.text('Beasiswa'), findsOneWidget);
      await _scrollToEntry(tester, 'Pembayaran');
      expect(find.text('Pembayaran'), findsOneWidget);
      await _scrollToEntry(tester, 'Notifikasi');
      expect(find.text('Notifikasi'), findsOneWidget);
    });

    testWidgets('hides services without the matching permission', (
      tester,
    ) async {
      await pumpApp(tester, const StudentMenuScreen(), user: _limitedStudent);
      expect(find.text('KHS'), findsOneWidget);
      expect(find.text('KRS Online'), findsNothing);
      expect(find.text('Pembayaran'), findsNothing);
      expect(find.text('Beasiswa'), findsNothing);
    });

    testWidgets('category chip filters the list', (tester) async {
      await pumpApp(tester, const StudentMenuScreen());
      await tester.tap(find.widgetWithText(ChoiceChip, 'Kemahasiswaan'));
      await tester.pumpAndSettle();
      expect(find.text('Beasiswa'), findsOneWidget);
      expect(find.text('Kegiatan Kemahasiswaan'), findsOneWidget);
      expect(find.text('KRS Online'), findsNothing);
    });

    testWidgets('initial category comes from the kategori query', (
      tester,
    ) async {
      await pumpApp(
        tester,
        const StudentMenuScreen(initialCategory: 'layanan'),
      );
      expect(find.text('Pembayaran'), findsOneWidget);
      expect(find.text('KRS Online'), findsNothing);
    });

    testWidgets('search matches title and description', (tester) async {
      await pumpApp(tester, const StudentMenuScreen());
      await tester.tap(find.byIcon(Icons.search));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'transkrip');
      await tester.pumpAndSettle();
      expect(find.text('Transkrip'), findsOneWidget);
      expect(find.text('KRS Online'), findsNothing);

      // Deskripsi juga ikut dicari ("unduh" hanya ada di Dokumen Akademik).
      await tester.enterText(find.byType(TextField), 'unduh');
      await tester.pumpAndSettle();
      expect(find.text('Dokumen Akademik'), findsOneWidget);
      expect(find.text('Transkrip'), findsNothing);
    });

    testWidgets('search without matches shows the empty message', (
      tester,
    ) async {
      await pumpApp(tester, const StudentMenuScreen());
      await tester.tap(find.byIcon(Icons.search));
      await tester.pumpAndSettle();
      await tester.enterText(find.byType(TextField), 'zzzz');
      await tester.pumpAndSettle();
      expect(find.text('Layanan tidak ditemukan.'), findsOneWidget);
    });

    testWidgets('tapping an entry opens its route', (tester) async {
      await pumpApp(tester, const StudentMenuScreen());
      await tester.tap(find.text('KRS Online'));
      await tester.pumpAndSettle();
      expect(find.text('route:/s/krs'), findsOneWidget);
    });

    testWidgets('services without backend open the pending screen', (
      tester,
    ) async {
      await pumpApp(tester, const StudentMenuScreen());
      await _scrollToEntry(tester, 'Kegiatan Kemahasiswaan');
      expect(find.text('Segera hadir'), findsOneWidget);
      await tester.tap(find.text('Kegiatan Kemahasiswaan'));
      await tester.pumpAndSettle();
      expect(
        find.text('route:/pending/feature/student_affairs'),
        findsOneWidget,
      );
    });
  });
}
