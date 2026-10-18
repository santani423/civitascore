import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/controllers/auth_controller.dart';
import '../l10n/core_strings.dart';
import '../widgets/offline_banner.dart';

class _Destination {
  const _Destination(this.branch, this.icon, this.selectedIcon, this.label);
  final int branch;
  final IconData icon;
  final IconData selectedIcon;
  final String label;
}

/// Bottom-navigation shell shared by both experiences.
class _ShellScaffold extends StatelessWidget {
  const _ShellScaffold({required this.shell, required this.destinations});
  final StatefulNavigationShell shell;
  final List<_Destination> destinations;

  @override
  Widget build(BuildContext context) {
    final selected = destinations.indexWhere(
      (d) => d.branch == shell.currentIndex,
    );
    return Scaffold(
      body: Column(
        children: [
          const OfflineBanner(),
          Expanded(child: shell),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: selected < 0 ? 0 : selected,
        onDestinationSelected: (i) {
          final branch = destinations[i].branch;
          shell.goBranch(branch, initialLocation: branch == shell.currentIndex);
        },
        destinations: [
          for (final d in destinations)
            NavigationDestination(
              icon: Icon(d.icon),
              selectedIcon: Icon(d.selectedIcon),
              label: d.label,
            ),
        ],
      ),
    );
  }
}

/// Student shell: Beranda · Akademik · Kuliah · Keuangan · Akun.
class StudentShell extends StatelessWidget {
  const StudentShell({super.key, required this.shell});
  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context) {
    final s = context.l10n;
    return _ShellScaffold(
      shell: shell,
      destinations: [
        _Destination(0, Icons.home_outlined, Icons.home_rounded, s.navHome),
        _Destination(
          1,
          Icons.school_outlined,
          Icons.school_rounded,
          s.navAcademic,
        ),
        _Destination(
          2,
          Icons.menu_book_outlined,
          Icons.menu_book_rounded,
          s.navLearning,
        ),
        _Destination(
          3,
          Icons.account_balance_wallet_outlined,
          Icons.account_balance_wallet_rounded,
          s.navFinance,
        ),
        _Destination(
          4,
          Icons.person_outline_rounded,
          Icons.person_rounded,
          s.navAccount,
        ),
      ],
    );
  }
}

/// Lecturer / advisor shell. Branches: 0 home, 1 teaching, 2 schedule,
/// 3 advising, 4 account. Teaching & schedule need lecturer permissions;
/// advising needs `krs_advising.read`.
class LecturerShell extends ConsumerWidget {
  const LecturerShell({super.key, required this.shell});
  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.l10n;
    final user = ref.watch(currentUserProvider);
    final lecturer = user?.isLecturer ?? false;
    final advisor = user?.isAdvisor ?? false;
    return _ShellScaffold(
      shell: shell,
      destinations: [
        _Destination(0, Icons.home_outlined, Icons.home_rounded, s.navHome),
        if (lecturer)
          _Destination(
            1,
            Icons.co_present_outlined,
            Icons.co_present_rounded,
            s.navTeaching,
          ),
        if (lecturer)
          _Destination(
            2,
            Icons.calendar_month_outlined,
            Icons.calendar_month_rounded,
            s.navSchedule,
          ),
        if (advisor)
          _Destination(
            3,
            Icons.supervisor_account_outlined,
            Icons.supervisor_account_rounded,
            s.navAdvising,
          ),
        _Destination(
          4,
          Icons.person_outline_rounded,
          Icons.person_rounded,
          s.navAccount,
        ),
      ],
    );
  }
}
