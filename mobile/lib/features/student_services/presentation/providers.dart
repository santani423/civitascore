import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_envelope.dart';
import '../../../core/widgets/paged_list.dart';
import '../data/student_services_api.dart';
import '../domain/student_request.dart';

class StudentRequestsNotifier extends PagedNotifier<StudentRequest> {
  @override
  Future<Paginated<StudentRequest>> fetchPage(int page) =>
      ref.read(studentServicesApiProvider).list(page);
}

final studentRequestsProvider =
    AsyncNotifierProvider.autoDispose<
      StudentRequestsNotifier,
      PagedState<StudentRequest>
    >(StudentRequestsNotifier.new);

final requestOptionsProvider = FutureProvider.autoDispose<RequestOptions>(
  (ref) => ref.watch(studentServicesApiProvider).options(),
);

final studentRequestDetailProvider = FutureProvider.autoDispose
    .family<StudentRequest, String>(
      (ref, id) => ref.watch(studentServicesApiProvider).detail(id),
    );

/// Status filter of the list screen (null = all).
final requestStatusFilterProvider = StateProvider.autoDispose<RequestStatus?>(
  (ref) => null,
);
