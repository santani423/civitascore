import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/dio_client.dart';
import '../../data/exam_remote_data_source.dart';
import '../../data/exam_repository_impl.dart';
import '../../domain/exam_repository.dart';

final examRepositoryProvider = Provider<ExamRepository>((ref) {
  return ExamRepositoryImpl(ExamRemoteDataSource(ref.watch(dioProvider)));
});
