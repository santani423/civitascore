import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/hr_api.dart';
import '../domain/hr_models.dart';

final lecturerProfileProvider = FutureProvider.autoDispose<LecturerProfile>(
  (ref) => ref.watch(hrApiProvider).lecturerProfile(),
);

final hrProfileProvider = FutureProvider.autoDispose<HrProfile>(
  (ref) => ref.watch(hrApiProvider).hrProfile(),
);

final leaveRequestsProvider = FutureProvider.autoDispose<List<LeaveRequest>>(
  (ref) => ref.watch(hrApiProvider).leaveRequests(),
);

final hrRequestsProvider = FutureProvider.autoDispose<List<HrRequest>>(
  (ref) => ref.watch(hrApiProvider).requests(),
);
