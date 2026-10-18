import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../auth/presentation/controllers/auth_controller.dart';
import '../data/student_home_api.dart';
import '../domain/finance_summary.dart';
import '../domain/student_dashboard.dart';

final studentDashboardProvider = FutureProvider.autoDispose<StudentDashboard>(
  (ref) => ref.watch(studentHomeApiProvider).dashboard(),
);

/// Finance card data. Fails independently of the dashboard; null when the
/// user lacks `student_finance.read` (no request is made).
final studentFinanceSummaryProvider =
    FutureProvider.autoDispose<FinanceSummary?>((ref) async {
      final user = ref.watch(currentUserProvider);
      if (user == null || !user.hasPermission('student_finance.read')) {
        return null;
      }
      return ref.watch(studentHomeApiProvider).financeSummary();
    });

/// Pull-to-refresh: reload everything; a finance failure never throws.
Future<void> refreshStudentHome(WidgetRef ref) async {
  ref.invalidate(studentDashboardProvider);
  ref.invalidate(studentFinanceSummaryProvider);
  try {
    await ref.read(studentDashboardProvider.future);
  } catch (_) {}
  try {
    await ref.read(studentFinanceSummaryProvider.future);
  } catch (_) {}
}
