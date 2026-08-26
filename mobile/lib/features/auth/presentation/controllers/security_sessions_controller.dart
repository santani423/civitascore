import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../domain/entities/user_device.dart';
import '../../domain/entities/user_session.dart';
import 'auth_controller.dart';

final userSessionsProvider = FutureProvider.autoDispose<List<UserSession>>((
  ref,
) {
  return ref.watch(authRepositoryProvider).listSessions();
});

final userDevicesProvider = FutureProvider.autoDispose<List<UserDevice>>((ref) {
  return ref.watch(authRepositoryProvider).listDevices();
});
