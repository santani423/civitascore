import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_envelope.dart';
import '../../../core/widgets/paged_list.dart';
import '../data/advising_api.dart';
import '../domain/krs_submission.dart';

const advisingStatuses = ['submitted', 'approved', 'rejected', 'all'];

final advisingStatusProvider = StateProvider.autoDispose<String>(
  (ref) => 'submitted',
);
final advisingSearchProvider = StateProvider.autoDispose<String>((ref) => '');

class KrsListNotifier extends PagedNotifier<KrsSubmission> {
  @override
  Future<Paginated<KrsSubmission>> fetchPage(int page) =>
      ref.read(advisingApiProvider).list(
        status: ref.read(advisingStatusProvider),
        search: ref.read(advisingSearchProvider),
        page: page,
      );

  @override
  Future<PagedState<KrsSubmission>> build() {
    ref.watch(advisingStatusProvider);
    ref.watch(advisingSearchProvider);
    return super.build();
  }
}

final krsListProvider =
    AsyncNotifierProvider.autoDispose<KrsListNotifier, PagedState<KrsSubmission>>(
      KrsListNotifier.new,
    );

final krsDetailProvider = FutureProvider.autoDispose
    .family<KrsSubmission, String>(
      (ref, id) => ref.watch(advisingApiProvider).detail(id),
    );
