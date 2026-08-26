import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_alert.dart';
import '../../../../core/widgets/app_card.dart';
import '../../domain/entities/user_device.dart';
import '../../domain/entities/user_session.dart';
import '../controllers/auth_controller.dart';
import '../controllers/security_sessions_controller.dart';

class SecuritySessionsScreen extends ConsumerStatefulWidget {
  const SecuritySessionsScreen({super.key});

  @override
  ConsumerState<SecuritySessionsScreen> createState() =>
      _SecuritySessionsScreenState();
}

class _SecuritySessionsScreenState
    extends ConsumerState<SecuritySessionsScreen> {
  String? _revokingId;
  bool _isLoggingOutAll = false;

  Future<void> _revoke(String id) async {
    setState(() => _revokingId = id);
    try {
      await ref.read(authRepositoryProvider).revokeSession(id);
      ref.invalidate(userSessionsProvider);
    } on ApiFailure catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _revokingId = null);
    }
  }

  Future<void> _logoutAll() async {
    setState(() => _isLoggingOutAll = true);
    await ref.read(authControllerProvider.notifier).logoutAll();
    if (mounted) context.go(RoutePaths.login);
  }

  @override
  Widget build(BuildContext context) {
    final sessionsAsync = ref.watch(userSessionsProvider);
    final devicesAsync = ref.watch(userDevicesProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Keamanan'),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: AppSpacing.md),
            child: TextButton.icon(
              onPressed: _isLoggingOutAll ? null : _logoutAll,
              icon: _isLoggingOutAll
                  ? const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.logout, size: 18),
              label: const Text('Keluar Semua'),
            ),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(userSessionsProvider);
          ref.invalidate(userDevicesProvider);
        },
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.lg),
          children: [
            Text('Sesi Aktif', style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: AppSpacing.sm),
            sessionsAsync.when(
              loading: () => const Padding(
                padding: EdgeInsets.all(AppSpacing.xl2),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => AppAlert(
                message: error is ApiFailure
                    ? error.message
                    : 'Gagal memuat sesi aktif.',
                variant: AppAlertVariant.danger,
              ),
              data: (sessions) => AppCard(
                noPadding: true,
                child: sessions.isEmpty
                    ? const Padding(
                        padding: EdgeInsets.all(AppSpacing.lg),
                        child: Text('Tidak ada sesi aktif.'),
                      )
                    : Column(
                        children: [
                          for (final session in sessions)
                            _SessionTile(
                              session: session,
                              isRevoking: _revokingId == session.id,
                              onRevoke: () => _revoke(session.id),
                            ),
                        ],
                      ),
              ),
            ),
            const SizedBox(height: AppSpacing.xl2),
            Text(
              'Perangkat Terdaftar',
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: AppSpacing.sm),
            devicesAsync.when(
              loading: () => const Padding(
                padding: EdgeInsets.all(AppSpacing.xl2),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => AppAlert(
                message: error is ApiFailure
                    ? error.message
                    : 'Gagal memuat perangkat.',
                variant: AppAlertVariant.danger,
              ),
              data: (devices) => AppCard(
                noPadding: true,
                child: devices.isEmpty
                    ? const Padding(
                        padding: EdgeInsets.all(AppSpacing.lg),
                        child: Text('Tidak ada perangkat terdaftar.'),
                      )
                    : Column(
                        children: [
                          for (final device in devices)
                            _DeviceTile(device: device),
                        ],
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SessionTile extends StatelessWidget {
  const _SessionTile({
    required this.session,
    required this.isRevoking,
    required this.onRevoke,
  });

  final UserSession session;
  final bool isRevoking;
  final VoidCallback onRevoke;

  @override
  Widget build(BuildContext context) {
    final title =
        session.device?.deviceName ??
        session.userAgent ??
        'Perangkat tidak dikenal';
    final lastActivity = session.lastActivityAt != null
        ? DateFormat('d MMM yyyy, HH:mm').format(session.lastActivityAt!)
        : '-';

    return ListTile(
      leading: Icon(_iconForPlatform(session.device?.platform)),
      title: Text(title, maxLines: 1, overflow: TextOverflow.ellipsis),
      subtitle: Text('${session.ipAddress ?? '-'} · $lastActivity'),
      trailing: session.isActive
          ? IconButton(
              icon: isRevoking
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.close),
              tooltip: 'Cabut sesi',
              onPressed: isRevoking ? null : onRevoke,
            )
          : const Text('Dicabut', style: TextStyle(fontSize: 12)),
    );
  }

  IconData _iconForPlatform(String? platform) {
    final normalized = platform?.toLowerCase() ?? '';
    if (normalized.contains('android') || normalized.contains('ios')) {
      return Icons.smartphone;
    }
    return Icons.computer;
  }
}

class _DeviceTile extends StatelessWidget {
  const _DeviceTile({required this.device});

  final UserDevice device;

  @override
  Widget build(BuildContext context) {
    final lastUsed = device.lastUsedAt != null
        ? DateFormat('d MMM yyyy, HH:mm').format(device.lastUsedAt!)
        : '-';

    return ListTile(
      leading: const Icon(Icons.devices_other),
      title: Text(
        device.deviceName ?? device.deviceType,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
      ),
      subtitle: Text('${device.platform ?? '-'} · Terakhir dipakai $lastUsed'),
      trailing: device.isTrusted
          ? const Chip(
              label: Text('Dipercaya', style: TextStyle(fontSize: 11)),
              padding: EdgeInsets.zero,
            )
          : null,
    );
  }
}
