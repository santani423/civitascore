import 'package:civitasone/core/theme/app_dimensions.dart';
import 'package:civitasone/core/widgets/campus_hero.dart';
import 'package:civitasone/features/student_home/presentation/screens/student_home_screen.dart';
import 'package:civitasone/features/student_home/presentation/screens/student_menu_screen.dart';
import 'package:civitasone/features/student_home/presentation/widgets/home_sections.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/pump_app.dart';
import 'student_home_test.dart' show api, dashboardJson;

/// Memperbesar teks sistem untuk subtree di dalamnya (skala font pengguna).
class _TextScale extends StatelessWidget {
  const _TextScale({required this.scale, required this.child});
  final double scale;
  final Widget child;

  @override
  Widget build(BuildContext context) => MediaQuery(
    data: MediaQuery.of(context).copyWith(textScaler: TextScaler.linear(scale)),
    child: child,
  );
}

void main() {
  // Font aplikasi (Plus Jakarta Sans) dimuat agar pengukuran teks mengikuti
  // perangkat; font default tes (Ahem) jauh lebih tinggi dan lebar.
  setUpAll(() async {
    final loader = FontLoader('PlusJakartaSans');
    for (final w in ['Regular', 'Medium', 'SemiBold', 'Bold']) {
      loader.addFont(rootBundle.load('assets/fonts/PlusJakartaSans-$w.ttf'));
    }
    await loader.load();
  });

  testWidgets('beranda with long name and 1.3x text scale fits 360 dp', (
    tester,
  ) async {
    final dash = dashboardJson();
    (dash['profile'] as Map<String, dynamic>)['name'] =
        'Muhammad Alexander Maximilian Wijayakusuma Santoso Putra';
    await pumpApp(
      tester,
      const _TextScale(scale: 1.3, child: StudentHomeScreen()),
      api: api(dash: dash),
      size: TestScreens.smallPhone,
    );
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
  });

  // Beranda dan Menu Utama harus tetap utuh di 360 dp, 411 dp, dan tablet.
  for (final size in TestScreens.all) {
    final label = '${size.width.toInt()}x${size.height.toInt()}';

    testWidgets('beranda renders without overflow at $label', (tester) async {
      await pumpApp(tester, const StudentHomeScreen(), api: api(), size: size);
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
      expect(find.text('Menu cepat'), findsOneWidget);
      expect(find.text('Andi Pratama'), findsOneWidget);
      // Hero dan kartu semester harus selebar konten (bukan lebar teks).
      // Beranda dibatasi 720 dp di tablet (lihat StudentHomeScreen).
      final contentWidth = size.width.clamp(0, 720).toDouble();
      expect(tester.getSize(find.byType(CampusHeroBanner)).width, contentWidth);
      expect(
        tester.getSize(find.byType(SemesterCard)).width,
        contentWidth - 2 * AppSpacing.lg,
      );
    });

    testWidgets('beranda dark mode renders without overflow at $label', (
      tester,
    ) async {
      await pumpApp(
        tester,
        const StudentHomeScreen(),
        api: api(),
        size: size,
        themeMode: ThemeMode.dark,
      );
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
    });

    testWidgets('menu utama renders without overflow at $label', (
      tester,
    ) async {
      await pumpApp(tester, const StudentMenuScreen(), size: size);
      await tester.pumpAndSettle();
      expect(tester.takeException(), isNull);
      expect(find.text('KRS Online'), findsOneWidget);
    });
  }
}
