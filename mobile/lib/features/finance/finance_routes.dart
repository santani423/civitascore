import 'package:go_router/go_router.dart';

import 'presentation/screens/awards_screen.dart';
import 'presentation/screens/clearance_screen.dart';
import 'presentation/screens/deposit_screen.dart';
import 'presentation/screens/invoice_detail_screen.dart';
import 'presentation/screens/invoices_screen.dart';
import 'presentation/screens/pay_screen.dart';
import 'presentation/screens/statement_screen.dart';
import 'presentation/screens/transactions_screen.dart';
import 'presentation/screens/ukt_screen.dart';

export 'presentation/screens/finance_home_screen.dart' show FinanceHomeScreen;

/// Detail routes of the student finance module (tab root is
/// `FinanceHomeScreen`, wired by the app router).
final List<RouteBase> financeRoutes = <RouteBase>[
  GoRoute(
    path: '/s/finance/invoices',
    builder: (_, _) => const InvoicesScreen(),
  ),
  GoRoute(
    path: '/s/finance/invoices/:invoiceId',
    builder: (_, state) =>
        InvoiceDetailScreen(invoiceId: state.pathParameters['invoiceId']!),
  ),
  GoRoute(
    path: '/s/finance/invoices/:invoiceId/pay',
    builder: (_, state) => PayScreen(
      invoiceId: state.pathParameters['invoiceId']!,
      installmentId: state.uri.queryParameters['installment'],
    ),
  ),
  GoRoute(
    path: '/s/finance/transactions',
    builder: (_, _) => const TransactionsScreen(),
  ),
  GoRoute(path: '/s/finance/deposit', builder: (_, _) => const DepositScreen()),
  GoRoute(
    path: '/s/finance/statement',
    builder: (_, _) => const StatementScreen(),
  ),
  GoRoute(path: '/s/finance/ukt', builder: (_, _) => const UktScreen()),
  GoRoute(path: '/s/finance/awards', builder: (_, _) => const AwardsScreen()),
  GoRoute(
    path: '/s/finance/clearance',
    builder: (_, _) => const ClearanceScreen(),
  ),
];
