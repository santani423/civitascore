import 'package:civitasone/core/utils/validators.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('Validators.email', () {
    test('rejects empty value', () {
      expect(Validators.email(''), isNotNull);
    });

    test('rejects malformed email', () {
      expect(Validators.email('not-an-email'), isNotNull);
    });

    test('accepts valid email', () {
      expect(Validators.email('user@civitasone.dev'), isNull);
    });
  });

  group('Validators.minLength', () {
    test('rejects value shorter than minimum', () {
      final validate = Validators.minLength(6, fieldName: 'Kata sandi');
      expect(validate('12345'), isNotNull);
    });

    test('accepts value meeting minimum', () {
      final validate = Validators.minLength(6, fieldName: 'Kata sandi');
      expect(validate('123456'), isNull);
    });
  });
}
