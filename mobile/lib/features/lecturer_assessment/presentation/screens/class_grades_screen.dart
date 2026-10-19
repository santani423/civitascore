import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../domain/assessment_models.dart';
import '../../l10n/assessment_strings.dart';
import '../providers.dart';
import '../widgets/bulk_grade_list.dart';
import '../widgets/grade_edit_sheet.dart';
import '../widgets/status_helpers.dart';

class ClassGradesScreen extends ConsumerStatefulWidget {
  const ClassGradesScreen({super.key, required this.classSectionId});
  final String classSectionId;

  @override
  ConsumerState<ClassGradesScreen> createState() => _ClassGradesScreenState();
}

class _ClassGradesScreenState extends ConsumerState<ClassGradesScreen> {
  bool _bulk = false;

  @override
  Widget build(BuildContext context) {
    final s = context.assess;
    final provider = gradeSheetProvider(widget.classSectionId);
    final sheet = ref.watch(provider);
    final user = ref.watch(currentUserProvider);
    final canWrite =
        user?.hasAnyPermission(['grades.create', 'grades.update']) ?? false;

    return Scaffold(
      appBar: AppBar(
        title: Text(s.gradesTitle),
        actions: [
          if (canWrite && (sheet.valueOrNull?.isNotEmpty ?? false))
            IconButton(
              tooltip: _bulk ? s.exitBulk : s.bulkMode,
              icon: Icon(_bulk ? Icons.close : Icons.edit_note),
              onPressed: () => setState(() => _bulk = !_bulk),
            ),
        ],
      ),
      body: AsyncValueView<List<GradeRow>>(
        value: sheet,
        onRetry: () => ref.invalidate(provider),
        isEmpty: (d) => d.isEmpty,
        empty: EmptyView(
          title: s.noStudentsTitle,
          message: s.noStudentsBody,
          icon: Icons.groups_outlined,
        ),
        data: (rows) => _bulk
            ? BulkGradeList(classSectionId: widget.classSectionId, rows: rows)
            : RefreshIndicator(
                onRefresh: () => ref.read(provider.notifier).refresh(),
                child: CenteredBody(
                  child: ListView.separated(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    itemCount: rows.length,
                    separatorBuilder: (_, _) =>
                        const SizedBox(height: AppSpacing.sm),
                    itemBuilder: (context, i) => _GradeTile(
                      row: rows[i],
                      onTap: canWrite
                          ? () => showModalBottomSheet<bool>(
                              context: context,
                              isScrollControlled: true,
                              showDragHandle: true,
                              builder: (_) => GradeEditSheet(
                                classSectionId: widget.classSectionId,
                                row: rows[i],
                              ),
                            )
                          : null,
                    ),
                  ),
                ),
              ),
      ),
    );
  }
}

class _GradeTile extends StatelessWidget {
  const _GradeTile({required this.row, this.onTap});
  final GradeRow row;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final s = context.assess;
    return AppSurfaceCard(
      onTap: onTap,
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  row.student.name,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                Text(
                  row.student.nim,
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ),
          ),
          const SizedBox(width: AppSpacing.md),
          if (row.graded) ...[
            if (row.score != null)
              Text(
                context.formatNumber(row.score),
                style: Theme.of(context).textTheme.titleMedium,
              ),
            const SizedBox(width: AppSpacing.sm),
            StatusChip(
              tone: letterTone(row.letterGrade),
              label: row.letterGrade ?? '—',
            ),
          ] else
            StatusChip(tone: StatusTone.neutral, label: s.notGraded),
        ],
      ),
    );
  }
}
