import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/academic_records_api.dart';
import '../domain/academic_models.dart';

final academicSummaryProvider = FutureProvider.autoDispose<AcademicSummary>(
  (ref) => ref.watch(academicRecordsApiProvider).summary(),
);

final academicHistoryProvider = FutureProvider.autoDispose<List<HistoryEntry>>(
  (ref) => ref.watch(academicRecordsApiProvider).history(),
);

final gradesProvider = FutureProvider.autoDispose<GradesOverview>(
  (ref) => ref.watch(academicRecordsApiProvider).grades(),
);

/// Family key: academic term id, or null for the backend default.
final khsProvider = FutureProvider.autoDispose.family<KhsData, String?>(
  (ref, termId) => ref.watch(academicRecordsApiProvider).khs(termId: termId),
);

final transcriptProvider = FutureProvider.autoDispose<TranscriptData>(
  (ref) => ref.watch(academicRecordsApiProvider).transcript(),
);

final studentDocumentsProvider =
    FutureProvider.autoDispose<List<StudentDocument>>(
      (ref) => ref.watch(academicRecordsApiProvider).documents(),
    );

final studentProfileProvider = FutureProvider.autoDispose<StudentProfile>(
  (ref) => ref.watch(academicRecordsApiProvider).profile(),
);
