import 'package:civitasone/core/config/env.dart';
import 'package:civitasone/core/config/flavor.dart';
import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/account/presentation/settings_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

/// Daftar pengaturan memakai ListView lazy: baris di bawah layar baru
/// dibangun setelah digulir.
/// Menggulir sampai baris terlihat penuh (bukan sekadar sudah dibangun).
Future<void> _scrollTo(WidgetTester tester, String text) async {
  await tester.scrollUntilVisible(
    find.text(text),
    200,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.ensureVisible(find.text(text));
  await tester.pumpAndSettle();
}

void main() {
  // Pengaturan membaca Env (mis. baris server pada build non-produksi).
  setUpAll(() => Env.init(Flavor.dev));

  testWidgets('groups settings into account, preferences, and help', (
    tester,
  ) async {
    await pumpApp(tester, const SettingsScreen(), api: FakeApi());
    expect(find.text('Andi Pratama'), findsOneWidget);
    expect(find.text('Akun & keamanan'), findsOneWidget);
    expect(find.text('Preferensi aplikasi'), findsOneWidget);
    expect(find.text('Bantuan & informasi'), findsOneWidget);
    expect(find.text('Ubah profil'), findsOneWidget);
    expect(find.text('Ganti kata sandi'), findsOneWidget);
    expect(find.text('Mode tampilan'), findsOneWidget);
    expect(find.text('Pengaturan notifikasi'), findsOneWidget);
    await _scrollTo(tester, 'Kebijakan privasi');
    expect(find.text('Kebijakan privasi'), findsOneWidget);
    expect(find.text('Belum tersedia'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('profile card and edit row open the student profile', (
    tester,
  ) async {
    await pumpApp(tester, const SettingsScreen(), api: FakeApi());
    await tester.tap(find.text('Ubah profil'));
    await tester.pumpAndSettle();
    expect(find.text('route:/s/profile'), findsOneWidget);
  });

  testWidgets('change password row navigates', (tester) async {
    await pumpApp(tester, const SettingsScreen(), api: FakeApi());
    await tester.tap(find.text('Ganti kata sandi'));
    await tester.pumpAndSettle();
    expect(find.text('route:/change-password'), findsOneWidget);
  });

  testWidgets('notification settings open the preferences screen', (
    tester,
  ) async {
    await pumpApp(tester, const SettingsScreen(), api: FakeApi());
    await tester.tap(find.text('Pengaturan notifikasi'));
    await tester.pumpAndSettle();
    expect(find.text('route:/notifications/preferences'), findsOneWidget);
  });

  testWidgets('privacy policy stays a labelled pending entry', (tester) async {
    await pumpApp(tester, const SettingsScreen(), api: FakeApi());
    await _scrollTo(tester, 'Kebijakan privasi');
    await tester.tap(find.text('Kebijakan privasi'));
    await tester.pumpAndSettle();
    expect(find.text('route:/pending/feature/privacy_consent'), findsOneWidget);
  });

  for (final size in TestScreens.all) {
    testWidgets(
      'renders in dark mode in Mandarin at ${size.width}x${size.height}',
      (tester) async {
        await pumpApp(
          tester,
          const SettingsScreen(),
          api: FakeApi(),
          language: AppLanguage.zh,
          themeMode: ThemeMode.dark,
          size: size,
        );
        expect(find.text('设置'), findsOneWidget);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
