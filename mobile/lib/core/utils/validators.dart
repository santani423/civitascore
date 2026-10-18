import '../l10n/core_strings.dart';

typedef FieldValidator = String? Function(String? value);

/// Localized client-side validators. Usage:
/// `validator: Validators.compose([Validators.required(s), Validators.email(s)])`
/// where `s = context.l10n`. Server-side 422 errors are shown in addition.
abstract final class Validators {
  static final _emailPattern = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');

  static FieldValidator required(CoreStrings s) =>
      (v) => (v == null || v.trim().isEmpty) ? s.validationRequired : null;

  static FieldValidator email(CoreStrings s) => (v) {
    if (v == null || v.trim().isEmpty) return null;
    return _emailPattern.hasMatch(v.trim()) ? null : s.validationEmail;
  };

  static FieldValidator minLength(CoreStrings s, int n) =>
      (v) => (v != null && v.isNotEmpty && v.length < n)
      ? s.validationMinLength(n)
      : null;

  static FieldValidator maxLength(CoreStrings s, int n) =>
      (v) => (v != null && v.length > n) ? s.validationMaxLength(n) : null;

  static FieldValidator numberInRange(CoreStrings s, num min, num max) => (v) {
    if (v == null || v.trim().isEmpty) return null;
    final n = num.tryParse(v.trim().replaceAll(',', '.'));
    if (n == null) return s.validationNumber;
    return (n < min || n > max) ? s.validationRange(min, max) : null;
  };

  static FieldValidator matches(CoreStrings s, String Function() other) =>
      (v) => v == other() ? null : s.validationPasswordMismatch;

  static FieldValidator compose(List<FieldValidator> validators) => (v) {
    for (final validate in validators) {
      final error = validate(v);
      if (error != null) return error;
    }
    return null;
  };
}
