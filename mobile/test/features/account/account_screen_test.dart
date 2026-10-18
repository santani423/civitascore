import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/core/session/app_experience.dart';
import 'package:civitasone/features/account/presentation/account_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

FakeApi _api() =>
    FakeApi()..get('/notifications/unread-count', {'unread_count': 3});

void main() {
  testWidgets('student account menu in Indonesian with unread badge', (
    tester,
  ) async {
    await pumpApp(tester, const AccountScreen(), api: _api());
    expect(find.text('Andi Pratama'), findsOneWidget);
    expect(find.text('Layanan & pengajuan'), findsOneWidget);
    expect(find.text('Pengumuman'), findsOneWidget);
    expect(find.text('3'), findsOneWidget);
  });

  testWidgets('lecturer account menu in English shows HR self-service', (
    tester,
  ) async {
    await pumpApp(
      tester,
      const AccountScreen(),
      api: _api(),
      user: testLecturer,
      language: AppLanguage.en,
      overrides: [
        activeExperienceProvider.overrideWith(
          () => _FixedExperience(AppExperience.lecturer),
        ),
      ],
    );
    expect(find.text('HR self-service'), findsOneWidget);
    expect(find.text('Services & requests'), findsNothing);
  });

  for (final size in TestScreens.all) {
    testWidgets(
      'renders in dark mode in Mandarin at ${size.width}x${size.height}',
      (tester) async {
        await pumpApp(
          tester,
          const AccountScreen(),
          api: _api(),
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

class _FixedExperience extends ActiveExperienceController {
  _FixedExperience(this.value);
  final AppExperience value;
  @override
  AppExperience? build() => value;
}
