import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

/// `true` while the device has no network interface. Used for the global
/// offline banner; requests still decide via their own failures.
final isOfflineProvider = StreamProvider<bool>((ref) async* {
  final connectivity = Connectivity();
  bool offline(List<ConnectivityResult> r) =>
      r.isEmpty || r.every((c) => c == ConnectivityResult.none);
  try {
    yield offline(await connectivity.checkConnectivity());
  } catch (_) {
    yield false;
  }
  yield* connectivity.onConnectivityChanged.map(offline);
});
