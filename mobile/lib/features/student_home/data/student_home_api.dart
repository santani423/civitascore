import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../domain/finance_summary.dart';
import '../domain/student_dashboard.dart';

class StudentHomeApi {
  StudentHomeApi(this._api);
  final ApiClient _api;

  /// `GET /student/dashboard` (student_portal.read).
  Future<StudentDashboard> dashboard() => _api.get(
    '/student/dashboard',
    decode: StudentDashboard.fromJson,
    cache: true,
  );

  /// `GET /student/finance/summary` (student_finance.read).
  Future<FinanceSummary> financeSummary() => _api.get(
    '/student/finance/summary',
    decode: FinanceSummary.fromJson,
    cache: true,
  );
}

final studentHomeApiProvider = Provider<StudentHomeApi>(
  (ref) => StudentHomeApi(ref.watch(apiClientProvider)),
);
