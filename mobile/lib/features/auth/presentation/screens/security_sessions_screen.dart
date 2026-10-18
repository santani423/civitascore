import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/auth_user.dart';
import '../../l10n/auth_strings.dart';
import '../controllers/auth_controller.dart';

final _sessionsProvider = FutureProvider.autoDispose<List<UserSession>>(
  (ref) => ref.watch(authApiProvider).sessions(),
);
final _devicesProvider = FutureProvider.autoDispose<List<UserDevice>>(
  (ref) => ref.watch(authApiProvider).devices(),
);

/// `GET /sessions`, `DELETE /sessions/{id}`, `GET /devices`, `POST /logout-all`.
class SecuritySessionsScreen extends ConsumerWidget {
  const SecuritySessionsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final s = context.l10n;
    final a = context.authL10n;
    final sessions = ref.watch(_sessionsProvider);
    final devices = ref.watch(_devicesProvider);

    Future<void> refresh() async {
      ref.invalidate(_sessionsProvider);
      ref.invalidate(_devicesProvider);
      await ref.read(_sessionsProvider.future);
    }

    return Scaffold(
      appBar: AppBar(title: Text(a.sessionsTitle)),
      body: RefreshIndicator(
        onRefresh: refresh,
        child: ListView(
          padding: const EdgeInsets.only(bottom: AppSpacing.xl2),
          children: [
            SectionHeader(title: a.activeSessions),
            sessions.when(
              loading: () => const SizedBox(
                height: 200,
                child: SkeletonList(itemCount: 2),
              ),
              error: (e, _) => SizedBox(
                height: 260,
                child: ErrorView(error: e, onRetry: refresh),
              ),
              data: (list) => list.isEmpty
                  ? const SizedBox(height: 200, child: EmptyView())
                  : Column(
                      children: [
                        for (final session in list)
                          ListTile(
                            leading: const Icon(Icons.phone_android_outlined),
                            title: Text(
                              session.deviceName ??
                                  session.userAgent ??
                                  a.unknownDevice,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            subtitle: Text(
                              '${a.lastActive}: ${context.formatDateTime(session.lastActivityAt)}'
                              '${session.ipAddress == null ? '' : ' · ${session.ipAddress}'}',
                            ),
                            trailing:
                                session.isActive && session.revokedAt == null
                                ? TextButton(
                                    onPressed: () async {
                                      final ok = await confirmAction(
                                        context,
                                        title: a.revoke,
                                        message: a.revokeConfirm,
                                        destructive: true,
                                        confirmLabel: a.revoke,
                                      );
                                      if (!ok) return;
                                      try {
                                        await ref
                                            .read(authApiProvider)
                                            .revokeSession(session.id);
                                        if (context.mounted) {
                                          showAppSnack(context, a.revoked);
                                        }
                                        ref.invalidate(_sessionsProvider);
                                      } catch (e) {
                                        if (context.mounted) {
                                          showErrorSnack(context, e);
                                        }
                                      }
                                    },
                                    child: Text(a.revoke),
                                  )
                                : StatusChip(
                                    label: a.revokedLabel,
                                    dense: true,
                                  ),
                          ),
                      ],
                    ),
            ),
            SectionHeader(title: a.devices),
            devices.when(
              loading: () => const SizedBox(
                height: 120,
                child: SkeletonList(itemCount: 1),
              ),
              error: (e, _) => Padding(
                padding: const EdgeInsets.all(AppSpacing.lg),
                child: Text(s.errorFor(e)),
              ),
              data: (list) => Column(
                children: [
                  for (final d in list)
                    ListTile(
                      leading: const Icon(Icons.devices_other_outlined),
                      title: Text(d.deviceName ?? a.unknownDevice),
                      subtitle: Text(
                        '${d.platform ?? '-'} · ${context.formatDateTime(d.lastUsedAt)}',
                      ),
                      trailing: d.isTrusted
                          ? StatusChip(
                              label: a.trusted,
                              tone: StatusTone.success,
                              dense: true,
                            )
                          : null,
                    ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: OutlinedButton.icon(
                icon: const Icon(Icons.logout),
                label: Text(a.logoutAll),
                onPressed: () async {
                  final ok = await confirmAction(
                    context,
                    title: a.logoutAll,
                    message: a.logoutAllConfirm,
                    destructive: true,
                  );
                  if (ok) {
                    await ref
                        .read(authControllerProvider.notifier)
                        .logout(allDevices: true);
                  }
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
