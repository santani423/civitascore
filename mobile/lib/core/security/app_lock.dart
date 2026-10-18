import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:local_auth/local_auth.dart';

import '../storage/local_prefs.dart';
import '../storage/storage_keys.dart';

final localAuthProvider = Provider<LocalAuthentication>(
  (ref) => LocalAuthentication(),
);

/// User preference: biometric lock on/off (per device).
class BiometricPreference extends Notifier<bool> {
  @override
  bool build() =>
      ref
          .watch(sharedPreferencesProvider)
          .getBool(StorageKeys.biometricEnabled) ??
      false;

  Future<void> setEnabled(bool value) async {
    state = value;
    await ref
        .read(sharedPreferencesProvider)
        .setBool(StorageKeys.biometricEnabled, value);
  }
}

final biometricEnabledProvider = NotifierProvider<BiometricPreference, bool>(
  BiometricPreference.new,
);

/// Biometric/device-credential unlock. `state == true` means locked. When
/// enabled, the app starts locked and re-locks after [relockAfter] in the
/// background. The Sanctum token stays in secure storage; the lock only
/// gates the UI on this device.
class AppLockController extends Notifier<bool> {
  static const relockAfter = Duration(minutes: 2);
  DateTime? _backgroundedAt;

  bool get enabled => ref.read(biometricEnabledProvider);

  // Read (not watch): enabling the lock in Settings must not lock instantly.
  @override
  bool build() => ref.read(biometricEnabledProvider);

  void onPaused() => _backgroundedAt = DateTime.now();

  void onResumed() {
    final since = _backgroundedAt;
    _backgroundedAt = null;
    if (enabled &&
        since != null &&
        DateTime.now().difference(since) > relockAfter) {
      state = true;
    }
  }

  Future<bool> isSupported() async {
    final auth = ref.read(localAuthProvider);
    try {
      return await auth.isDeviceSupported();
    } catch (_) {
      return false;
    }
  }

  Future<bool> authenticate(String reason) async {
    try {
      final ok = await ref
          .read(localAuthProvider)
          .authenticate(localizedReason: reason);
      if (ok) state = false;
      return ok;
    } catch (_) {
      return false;
    }
  }

  void unlockForSignedOut() => state = false;
}

final appLockProvider = NotifierProvider<AppLockController, bool>(
  AppLockController.new,
);

/// Feeds app lifecycle events to [AppLockController].
class AppLockLifecycle extends ConsumerStatefulWidget {
  const AppLockLifecycle({super.key, required this.child});
  final Widget child;

  @override
  ConsumerState<AppLockLifecycle> createState() => _AppLockLifecycleState();
}

class _AppLockLifecycleState extends ConsumerState<AppLockLifecycle>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final lock = ref.read(appLockProvider.notifier);
    if (state == AppLifecycleState.paused) lock.onPaused();
    if (state == AppLifecycleState.resumed) lock.onResumed();
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
