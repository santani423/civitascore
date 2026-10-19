import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/utils/file_actions.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/academic_models.dart';
import '../../l10n/academic_records_strings.dart';
import '../providers.dart';
import '../widgets/common.dart';

class DocumentsScreen extends ConsumerWidget {
  const DocumentsScreen({super.key});

  IconData _icon(String type) => switch (type) {
    'krs' => Icons.assignment_outlined,
    'khs' => Icons.grading_outlined,
    'transcript' => Icons.workspace_premium_outlined,
    _ => Icons.mail_outline,
  };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final a = context.acadL10n;
    final value = ref.watch(studentDocumentsProvider);
    String typeLabel(String t) => switch (t) {
      'krs' => a.typeKrs,
      'khs' => a.typeKhs,
      'transcript' => a.typeTranscript,
      'letter' => a.typeLetter,
      _ => t,
    };
    return Scaffold(
      appBar: AppBar(title: Text(a.documentsTitle)),
      body: AsyncValueView<List<StudentDocument>>(
        value: value,
        onRetry: () => ref.invalidate(studentDocumentsProvider),
        isEmpty: (d) => d.isEmpty,
        empty: EmptyView(message: a.documentsEmpty),
        data: (docs) => RefreshList(
          onRefresh: () async => ref.refresh(studentDocumentsProvider.future),
          children: [
            for (final d in docs)
              MenuTile(
                icon: _icon(d.type),
                title: d.title,
                subtitle: d.description,
                badge: typeLabel(d.type),
                trailing: const Icon(Icons.download_outlined),
                onTap: () => downloadAndOpen(
                  context,
                  ref,
                  path: d.path,
                  query: d.query,
                  filename: d.filename,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
