import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/exam_question.dart';
import '../../l10n/exam_strings.dart';
import '../providers.dart';

/// "Preview sebagai mahasiswa" — simulasi tampilan soal dari soal ujian
/// saat ini. Hanya pratinjau lokal: tidak memanggil API attempt dan tidak
/// menampilkan kunci jawaban.
class ExamPreviewScreen extends ConsumerStatefulWidget {
  const ExamPreviewScreen({super.key, required this.examId});

  final String examId;

  @override
  ConsumerState<ExamPreviewScreen> createState() => _ExamPreviewScreenState();
}

class _ExamPreviewScreenState extends ConsumerState<ExamPreviewScreen> {
  int _index = 0;

  static const _labels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

  @override
  Widget build(BuildContext context) {
    final s = context.examL10n;
    final core = context.l10n;
    final questions = ref.watch(examQuestionsProvider(widget.examId));
    final exam = ref.watch(examProvider(widget.examId)).valueOrNull;

    return Scaffold(
      appBar: AppBar(title: Text(exam?.title ?? s.previewTitle)),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: AsyncValueView<List<ExamQuestion>>(
            value: questions,
            onRetry: () => ref.invalidate(examQuestionsProvider(widget.examId)),
            isEmpty: (l) => l.isEmpty,
            empty: EmptyView(
              icon: Icons.visibility_off_outlined,
              title: s.previewEmpty,
            ),
            data: (list) {
              final i = _index.clamp(0, list.length - 1);
              final q = list[i];
              return Padding(
                padding: const EdgeInsets.all(AppSpacing.lg),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      s.questionOf(i + 1, list.length),
                      style: Theme.of(context).textTheme.labelLarge,
                    ),
                    const SizedBox(height: AppSpacing.md),
                    Text(
                      q.questionText,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    Expanded(
                      child: ListView.separated(
                        itemCount: q.options.length,
                        separatorBuilder: (_, _) =>
                            const SizedBox(height: AppSpacing.sm),
                        itemBuilder: (context, k) => Card(
                          margin: EdgeInsets.zero,
                          child: ListTile(
                            leading: CircleAvatar(
                              child: Text(
                                k < _labels.length ? _labels[k] : '${k + 1}',
                              ),
                            ),
                            title: Text(q.options[k].optionText),
                          ),
                        ),
                      ),
                    ),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Flexible(
                          child: TextButton.icon(
                            icon: const Icon(Icons.chevron_left),
                            label: Text(core.previous),
                            onPressed: i > 0
                                ? () => setState(() => _index = i - 1)
                                : null,
                          ),
                        ),
                        Flexible(
                          child: TextButton.icon(
                            iconAlignment: IconAlignment.end,
                            icon: const Icon(Icons.chevron_right),
                            label: Text(core.next),
                            onPressed: i < list.length - 1
                                ? () => setState(() => _index = i + 1)
                                : null,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
