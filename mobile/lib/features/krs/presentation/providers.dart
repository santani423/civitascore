import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/krs_api.dart';
import '../domain/krs_models.dart';

/// Current KRS overview. Every mutation returns the fresh overview, which
/// becomes the new state; offerings are refetched afterwards.
class KrsOverviewController extends AutoDisposeAsyncNotifier<KrsOverview> {
  @override
  Future<KrsOverview> build() => ref.watch(krsApiProvider).overview();

  Future<void> _apply(Future<KrsOverview> Function(KrsApi api) call) async {
    final next = await call(ref.read(krsApiProvider));
    state = AsyncData(next);
    ref.invalidate(krsOfferingsProvider);
    ref.invalidate(krsHistoryProvider);
  }

  Future<void> addItem(String classSectionId) =>
      _apply((a) => a.addItem(classSectionId));
  Future<void> removeItem(String krsItemId) =>
      _apply((a) => a.removeItem(krsItemId));
  Future<void> submit() => _apply((a) => a.submit());
  Future<void> cancel() => _apply((a) => a.cancel());
}

final krsOverviewProvider =
    AsyncNotifierProvider.autoDispose<KrsOverviewController, KrsOverview>(
      KrsOverviewController.new,
    );

final krsOfferingsProvider = FutureProvider.autoDispose<KrsOfferings>(
  (ref) => ref.watch(krsApiProvider).offerings(),
);

final krsHistoryProvider = FutureProvider.autoDispose<List<KrsHistoryEntry>>(
  (ref) => ref.watch(krsApiProvider).history(),
);
