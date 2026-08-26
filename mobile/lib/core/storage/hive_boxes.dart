import 'package:hive_ce_flutter/hive_ce_flutter.dart';

/// Box names for read-only reference-data caching (BAB 8.6).
/// Boxes are opened lazily by each feature's LocalDataSource as needed —
/// this file only centralizes naming so keys never collide across features.
abstract final class HiveBoxes {
  static const studyPrograms = 'cache_study_programs';
  static const curriculums = 'cache_curriculums';
  static const courses = 'cache_courses';
}

Future<void> initHive() async {
  await Hive.initFlutter();
}
