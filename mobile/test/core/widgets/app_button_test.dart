import 'package:civitasone/core/widgets/app_button.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('AppButton invokes onPressed when tapped', (tester) async {
    var tapped = false;

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: AppButton(label: 'Masuk', onPressed: () => tapped = true),
        ),
      ),
    );

    await tester.tap(find.text('Masuk'));
    await tester.pump();

    expect(tapped, isTrue);
  });

  testWidgets('AppButton disables interaction while loading', (tester) async {
    var tapped = false;

    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: AppButton(
            label: 'Masuk',
            isLoading: true,
            onPressed: () => tapped = true,
          ),
        ),
      ),
    );

    expect(find.byType(CircularProgressIndicator), findsOneWidget);
    await tester.tap(find.byType(AppButton), warnIfMissed: false);
    await tester.pump();

    expect(tapped, isFalse);
  });
}
