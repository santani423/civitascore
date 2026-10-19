import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/widgets/state_views.dart';
import '../../domain/learning_models.dart';
import '../../l10n/learning_strings.dart';
import '../providers.dart';
import '../widgets/learning_widgets.dart';

class MaterialsScreen extends ConsumerStatefulWidget {
  const MaterialsScreen({super.key});
  @override
  ConsumerState<MaterialsScreen> createState() => _MaterialsScreenState();
}

class _MaterialsScreenState extends ConsumerState<MaterialsScreen> {
  String? _courseId; // client-side filter: backend has no course filter

  @override
  Widget build(BuildContext context) {
    final s = context.learningL10n;
    final value = ref.watch(materialsProvider);
    return Scaffold(
      appBar: AppBar(title: Text(s.materialsTitle)),
      body: LearningBody(
        child: AsyncValueView<MaterialsOverview>(
          value: value,
          onRetry: () => ref.invalidate(materialsProvider),
          isEmpty: (d) => d.courses.every((c) => c.materials.isEmpty),
          empty: EmptyView(
            icon: Icons.menu_book_outlined,
            title: s.noMaterials,
            message: s.noMaterialsBody,
          ),
          data: (d) {
            final courses = d.courses
                .where((c) => c.materials.isNotEmpty)
                .where((c) => _courseId == null || c.info.id == _courseId)
                .toList();
            return RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(materialsProvider);
                await ref
                    .read(materialsProvider.future)
                    .catchError((_) => const MaterialsOverview());
              },
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.only(bottom: 24),
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
                        Padding(
                          padding: const EdgeInsets.only(right: 8),
                          child: ChoiceChip(
                            label: Text(s.allCourses),
                            selected: _courseId == null,
                            onSelected: (_) => setState(() => _courseId = null),
                          ),
                        ),
                        for (final c in d.courses)
                          Padding(
                            padding: const EdgeInsets.only(right: 8),
                            child: ChoiceChip(
                              label: Text(c.info.courseName),
                              selected: _courseId == c.info.id,
                              onSelected: (_) =>
                                  setState(() => _courseId = c.info.id),
                            ),
                          ),
                      ],
                    ),
                  ),
                  if (courses.isEmpty)
                    EmptyView(
                      icon: Icons.menu_book_outlined,
                      title: s.noMaterials,
                      message: s.noMaterialsBody,
                    ),
                  for (final c in courses) ...[
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 12, 16, 4),
                      child: Text(
                        c.info.courseName,
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                    ),
                    for (final m in c.materials) MaterialTile(material: m),
                  ],
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}
