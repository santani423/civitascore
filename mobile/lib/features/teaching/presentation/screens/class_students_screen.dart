import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/teaching_models.dart';
import '../../l10n/teaching_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

class ClassStudentsScreen extends ConsumerStatefulWidget {
  const ClassStudentsScreen({super.key, required this.classSectionId});
  final String classSectionId;

  @override
  ConsumerState<ClassStudentsScreen> createState() => _State();
}

class _State extends ConsumerState<ClassStudentsScreen> {
  String _q = '';

  @override
  Widget build(BuildContext context) {
    final t = context.tr;
    final roster = ref.watch(rosterProvider(widget.classSectionId));
    Future<void> refresh() async {
      ref.invalidate(rosterProvider(widget.classSectionId));
      try {
        await ref.read(rosterProvider(widget.classSectionId).future);
      } catch (_) {}
    }

    return Scaffold(
      appBar: AppBar(title: Text(t.rosterTitle)),
      body: WideBody(
        child: AsyncValueView<List<RosterEntry>>(
          value: roster,
          onRetry: refresh,
          isEmpty: (l) => l.isEmpty,
          empty: EmptyView(
            icon: Icons.groups_outlined,
            title: t.noStudentsTitle,
            message: t.noStudentsBody,
          ),
          data: (all) {
            final q = _q.trim().toLowerCase();
            final list = q.isEmpty
                ? all
                : all
                      .where(
                        (e) =>
                            e.name.toLowerCase().contains(q) ||
                            e.nim.toLowerCase().contains(q),
                      )
                      .toList();
            return Column(
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(
                    AppSpacing.lg,
                    AppSpacing.md,
                    AppSpacing.lg,
                    AppSpacing.xs,
                  ),
                  child: TextField(
                    onChanged: (v) => setState(() => _q = v),
                    decoration: InputDecoration(
                      hintText: t.searchStudents,
                      prefixIcon: const Icon(Icons.search),
                    ),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.lg,
                    vertical: AppSpacing.xs,
                  ),
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: Text(
                      t.studentCount(all.length),
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ),
                ),
                Expanded(
                  child: RefreshIndicator(
                    onRefresh: refresh,
                    child: list.isEmpty
                        ? ListView(
                            physics: const AlwaysScrollableScrollPhysics(),
                            children: [
                              const SizedBox(height: 80),
                              EmptyView(title: t.noMatches, message: ''),
                            ],
                          )
                        : ListView.separated(
                            physics: const AlwaysScrollableScrollPhysics(),
                            itemCount: list.length,
                            separatorBuilder: (_, _) =>
                                const Divider(height: 1),
                            itemBuilder: (context, i) {
                              final e = list[i];
                              return ListTile(
                                title: Text(
                                  e.name,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                subtitle: Text(e.nim),
                                trailing: const Icon(Icons.chevron_right),
                                onTap: () => context.push(
                                  RoutePaths.lecturerStudent(e.studentId),
                                ),
                              );
                            },
                          ),
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}
