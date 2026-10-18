import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/core/l10n/core_strings.dart';
import 'package:civitasone/core/utils/validators.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

/// Pumps a tiny widget to obtain a [CoreStrings] for [language].
Future<CoreStrings> stringsFor(
  WidgetTester tester,
  AppLanguage language,
) async {
  late CoreStrings strings;
  await tester.pumpWidget(
    Localizations(
      locale: language.locale,
      delegates: const [DefaultWidgetsLocalizations.delegate],
      child: Builder(
        builder: (context) {
          strings = context.l10n;
          return const SizedBox();
        },
      ),
    ),
  );
  return strings;
}

void main() {
  testWidgets('required/email/minLength produce localized messages', (
    tester,
  ) async {
    final s = await stringsFor(tester, AppLanguage.id);
    expect(Validators.required(s)(''), 'Wajib diisi');
    expect(Validators.required(s)('  '), 'Wajib diisi');
    expect(Validators.required(s)('x'), isNull);
    expect(Validators.email(s)('not-an-email'), 'Format email tidak valid');
    expect(Validators.email(s)('user@civitasone.dev'), isNull);
    expect(Validators.minLength(s, 8)('1234567'), 'Minimal 8 karakter');
    expect(Validators.minLength(s, 8)('12345678'), isNull);
  });

  testWidgets('numberInRange accepts comma decimals and enforces bounds', (
    tester,
  ) async {
    final s = await stringsFor(tester, AppLanguage.en);
    final v = Validators.numberInRange(s, 0, 100);
    expect(v('85,5'), isNull);
    expect(v('101'), 'Value must be 0–100');
    expect(v('abc'), 'Enter a valid number');
    expect(v(''), isNull);
  });

  testWidgets('compose returns the first failing message', (tester) async {
    final s = await stringsFor(tester, AppLanguage.zh);
    final v = Validators.compose([Validators.required(s), Validators.email(s)]);
    expect(v(''), '必填');
    expect(v('bad'), '邮箱格式无效');
    expect(v('a@b.co'), isNull);
  });

  testWidgets('matches compares against the other field', (tester) async {
    final s = await stringsFor(tester, AppLanguage.id);
    var other = 'secret123';
    final v = Validators.matches(s, () => other);
    expect(v('secret123'), isNull);
    other = 'changed';
    expect(v('secret123'), s.validationPasswordMismatch);
  });
}
