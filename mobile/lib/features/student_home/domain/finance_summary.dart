import '../../../core/network/api_envelope.dart';

/// Subset of `GET /student/finance/summary` used by the home card.
/// Money values are decimal strings (e.g. "1500000.00").
class FinanceSummary {
  const FinanceSummary({
    required this.outstanding,
    required this.arrears,
    required this.openInvoices,
    required this.pendingVerification,
    this.depositAvailable,
    this.nextDueDate,
    this.nextDueAmount,
    this.blockedServices = const [],
  });

  factory FinanceSummary.fromJson(Object? data) {
    final j = asMap(data);
    final nextDue = j['next_due'] is Map ? asMap(j['next_due']) : null;
    return FinanceSummary(
      outstanding: asString(j['outstanding']) ?? '0',
      arrears: asString(j['arrears']) ?? '0',
      openInvoices: asInt(j['open_invoices']) ?? 0,
      pendingVerification: asInt(j['pending_verification']) ?? 0,
      depositAvailable: asString(j['deposit_available']),
      nextDueDate: nextDue == null ? null : asDateTime(nextDue['date']),
      nextDueAmount: nextDue == null ? null : asString(nextDue['amount']),
      blockedServices: asMapList(j['services'])
          .where((s) => asBool(s['blocked']))
          .map((s) => asString(s['label']) ?? asString(s['service']) ?? '')
          .where((s) => s.isNotEmpty)
          .toList(),
    );
  }

  final String outstanding;
  final String arrears;
  final int openInvoices;
  final int pendingVerification;
  final String? depositAvailable;
  final DateTime? nextDueDate;
  final String? nextDueAmount;

  /// Labels of services currently blocked (cekal).
  final List<String> blockedServices;

  bool get hasOutstanding => (num.tryParse(outstanding) ?? 0) > 0;
  bool get hasArrears => (num.tryParse(arrears) ?? 0) > 0;
}
