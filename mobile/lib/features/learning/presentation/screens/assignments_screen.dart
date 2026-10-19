import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/widgets/state_views.dart';
import '../../domain/learning_models.dart';
import '../../l10n/learning_strings.dart';
import '../providers.dart';
import '../widgets/learning_widgets.dart';

const _filters = [
  'all',
  'not_submitted',
  'submitted',
  'late',
  'graded',
  'missed',
];

class AssignmentsScreen extends ConsumerStatefulWidget {
  const AssignmentsScreen({super.key});
  @override
  ConsumerState<AssignmentsScreen> createState() => _AssignmentsScreenState();
}

class _AssignmentsScreenState extends ConsumerState<AssignmentsScreen> {
  String _status = 'all';

  @override
  Widget build(BuildContext context) {
    final s = context.learningL10n;
    final value = ref.watch(assignmentsProvider(_status));
    // Unfiltered counts for chips (same family key => cached by Riverpod).
    final counts = ref.watch(assignmentsProvider('all')).valueOrNull?.counts;
    return Scaffold(
      appBar: AppBar(title: Text(s.assignmentsTitle)),
      body: LearningBody(
        child: Column(
          children: [
            SizedBox(
              height: 56,
              child: ListView(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                children: [
                  for (final f in _filters)
                    Padding(
                      padding: const EdgeInsets.only(right: 8),
                      child: ChoiceChip(
                        label: Text(
                          counts == null
                              ? s.filterLabel(f)
                              : '${s.filterLabel(f)} (${counts[f] ?? 0})',
                        ),
                        selected: _status == f,
                        onSelected: (_) => setState(() => _status = f),
                      ),
                    ),
                ],
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () async {
                  ref.invalidate(assignmentsProvider);
                  await ref
                      .read(assignmentsProvider(_status).future)
                      .catchError((_) => const AssignmentsOverview());
                },
                child: AsyncValueView<AssignmentsOverview>(
                  value: value,
                  onRetry: () => ref.invalidate(assignmentsProvider),
                  isEmpty: (d) => d.assignments.isEmpty,
                  empty: ListView(
                    children: [
                      EmptyView(
                        icon: Icons.assignment_outlined,
                        title: s.noAssignments,
                        message: s.noAssignmentsBody,
                      ),
                    ],
                  ),
                  data: (d) => ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.only(bottom: 24),
                    children: [
                      for (final a in d.assignments)
                        AssignmentTile(assignment: a),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
