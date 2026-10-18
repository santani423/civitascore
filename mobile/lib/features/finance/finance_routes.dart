import 'package:flutter/widgets.dart';
import 'package:go_router/go_router.dart';
import '../../core/widgets/feature_placeholder.dart';

// STUB — replaced by the `finance` feature module.
class FinanceHomeScreen extends StatelessWidget {
  const FinanceHomeScreen({super.key});
  @override
  Widget build(BuildContext context) =>
      const FeaturePlaceholder(title: 'FinanceHomeScreen');
}

final List<RouteBase> financeRoutes = <RouteBase>[];
