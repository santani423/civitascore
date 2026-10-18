import 'package:flutter/widgets.dart';
import 'package:intl/intl.dart';

import '../l10n/app_language.dart';

/// Locale-aware formatting. Dates from the API are ISO-8601 (mostly UTC) and
/// are shown in device local time. Money is always Indonesian Rupiah.
extension FormatX on BuildContext {
  String get _tag => AppLanguage.of(this).intlTag;

  /// 17 Okt 2026 / Oct 17, 2026 / 2026年10月17日
  String formatDate(DateTime? d) =>
      d == null ? '—' : DateFormat.yMMMd(_tag).format(d);

  /// Jumat, 17 Oktober 2026
  String formatDateLong(DateTime? d) =>
      d == null ? '—' : DateFormat.yMMMMEEEEd(_tag).format(d);

  /// 17 Okt 2026 14.30 (24-hour clock everywhere, as on campus)
  String formatDateTime(DateTime? d) => d == null
      ? '—'
      : '${DateFormat.yMMMd(_tag).format(d)} ${DateFormat.Hm(_tag).format(d)}';

  String formatTime(DateTime? d) =>
      d == null ? '—' : DateFormat.Hm(_tag).format(d);

  /// "08:00:00" or "08:00" → "08.00" (id) / "08:00".
  String formatClock(String? hhmmss) {
    if (hhmmss == null || hhmmss.isEmpty) return '—';
    final parts = hhmmss.split(':');
    if (parts.length < 2) return hhmmss;
    final d = DateTime(
      2000,
      1,
      1,
      int.tryParse(parts[0]) ?? 0,
      int.tryParse(parts[1]) ?? 0,
    );
    return DateFormat.Hm(_tag).format(d);
  }

  /// Rp 1.500.000 — accepts num or decimal strings ("1500000.00").
  String formatMoney(Object? amount, {bool withSymbol = true}) {
    final value = amount is num ? amount : num.tryParse('${amount ?? ''}') ?? 0;
    final f = NumberFormat.currency(
      locale: 'id_ID',
      symbol: withSymbol ? 'Rp ' : '',
      decimalDigits: value % 1 == 0 ? 0 : 2,
    );
    return f.format(value).trim();
  }

  /// Plain number with locale separators; "85.00" → 85 / 85,5.
  String formatNumber(Object? value, {int maxDecimals = 2}) {
    final n = value is num ? value : num.tryParse('${value ?? ''}');
    if (n == null) return '—';
    final f = NumberFormat.decimalPattern(_tag)
      ..maximumFractionDigits = maxDecimals;
    return f.format(n);
  }

  /// "Hari ini", "Besok" or a short date — used in agendas.
  String formatRelativeDay(
    DateTime d, {
    required String today,
    required String tomorrow,
    required String yesterday,
  }) {
    final now = DateTime.now();
    final a = DateTime(now.year, now.month, now.day);
    final b = DateTime(d.year, d.month, d.day);
    final diff = b.difference(a).inDays;
    if (diff == 0) return today;
    if (diff == 1) return tomorrow;
    if (diff == -1) return yesterday;
    return formatDate(d);
  }
}

/// Human file size: 1.2 MB.
String formatBytes(int? bytes) {
  if (bytes == null || bytes <= 0) return '—';
  const units = ['B', 'KB', 'MB', 'GB'];
  var size = bytes.toDouble();
  var unit = 0;
  while (size >= 1024 && unit < units.length - 1) {
    size /= 1024;
    unit++;
  }
  return '${size.toStringAsFixed(unit == 0 ? 0 : 1)} ${units[unit]}';
}
