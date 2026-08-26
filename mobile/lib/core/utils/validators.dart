/// Padanan skema Zod React (BAB 6.1) — validasi client-side sebelum submit.
abstract final class Validators {
  static final _emailPattern = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');

  static String? email(String? value) {
    if (value == null || value.trim().isEmpty) {
      return 'Email wajib diisi';
    }
    if (!_emailPattern.hasMatch(value.trim())) {
      return 'Format email tidak valid';
    }
    return null;
  }

  static String? Function(String?) minLength(int length, {String? fieldName}) {
    return (value) {
      if (value == null || value.isEmpty) {
        return '${fieldName ?? 'Kolom ini'} wajib diisi';
      }
      if (value.length < length) {
        return '${fieldName ?? 'Kolom ini'} minimal $length karakter';
      }
      return null;
    };
  }
}
