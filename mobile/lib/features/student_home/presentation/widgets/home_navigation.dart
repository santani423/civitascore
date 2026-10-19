import 'package:flutter/widgets.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';

const _tabRoots = {
  RoutePaths.studentHome,
  RoutePaths.studentAcademic,
  RoutePaths.studentLearning,
  RoutePaths.studentFinance,
  RoutePaths.studentAccount,
};

/// Tab roots switch the bottom-bar tab; every other screen is pushed.
void openAppRoute(BuildContext context, String path) {
  if (_tabRoots.contains(path)) {
    context.go(path);
  } else {
    context.push(path);
  }
}
