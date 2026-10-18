import 'package:flutter/widgets.dart';
import 'package:go_router/go_router.dart';
import '../../core/widgets/feature_placeholder.dart';

// STUB — replaced by the `teaching` feature module.
class TeachingHomeScreen extends StatelessWidget {
  const TeachingHomeScreen({super.key});
  @override
  Widget build(BuildContext context) =>
      const FeaturePlaceholder(title: 'TeachingHomeScreen');
}

class LecturerScheduleScreen extends StatelessWidget {
  const LecturerScheduleScreen({super.key});
  @override
  Widget build(BuildContext context) =>
      const FeaturePlaceholder(title: 'LecturerScheduleScreen');
}

final List<RouteBase> teachingRoutes = <RouteBase>[];
