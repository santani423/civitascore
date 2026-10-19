import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/lecturer_home_api.dart';
import '../domain/home_models.dart';

final homeClassesProvider = FutureProvider.autoDispose<List<TeachingClass>>(
  (ref) => ref.watch(lecturerHomeApiProvider).classes(),
);

final homePendingKrsProvider = FutureProvider.autoDispose<PendingKrsSummary>(
  (ref) => ref.watch(lecturerHomeApiProvider).pendingKrs(),
);
