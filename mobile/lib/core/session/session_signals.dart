import 'package:flutter_riverpod/flutter_riverpod.dart';

/// Incremented by the HTTP layer whenever an authenticated request returns
/// 401 (token revoked from another device, account deactivated…). The auth
/// controller listens and clears the local session. Kept as a separate
/// provider so the network layer does not depend on the auth feature.
final unauthorizedSignalProvider = StateProvider<int>((ref) => 0);
