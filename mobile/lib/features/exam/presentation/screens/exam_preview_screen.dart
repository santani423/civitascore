import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../domain/entities/exam_question.dart';
import '../controllers/question_bank_controller.dart';

/// "Preview sebagai Mahasiswa" (spec §12) — mensimulasikan tampilan soal ke
/// peserta dari draft bank soal saat ini. Ini HANYA pratinjau di sisi wizard
/// (tidak memanggil API attempt) — layar pengerjaan ujian sungguhan untuk
/// mahasiswa ada di luar cakupan implementasi ini, lihat catatan di plan.
class ExamPreviewScreen extends ConsumerStatefulWidget {
  const ExamPreviewScreen({
    super.key,
    required this.examId,
    required this.title,
  });

  final String examId;
  final String title;

  @override
  ConsumerState<ExamPreviewScreen> createState() => _ExamPreviewScreenState();
}

class _ExamPreviewScreenState extends ConsumerState<ExamPreviewScreen> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final questionsAsync = ref.watch(
      questionBankControllerProvider(widget.examId),
    );

    return Scaffold(
      appBar: AppBar(title: Text(widget.title)),
      body: questionsAsync.when(
        data: (questions) {
          if (questions.isEmpty) {
            return const Center(
              child: Text('Belum ada soal untuk dipratinjau.'),
            );
          }

          final question = questions[_index.clamp(0, questions.length - 1)];

          return Padding(
            padding: const EdgeInsets.all(AppSpacing.lg),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Soal ${_index + 1} dari ${questions.length}',
                  style: Theme.of(context).textTheme.labelLarge,
                ),
                const SizedBox(height: AppSpacing.md),
                Text(
                  question.questionText,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: AppSpacing.lg),
                Expanded(child: _OptionsList(question: question)),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    TextButton(
                      onPressed: _index > 0
                          ? () => setState(() => _index -= 1)
                          : null,
                      child: const Text('< Sebelumnya'),
                    ),
                    TextButton(
                      onPressed: _index < questions.length - 1
                          ? () => setState(() => _index += 1)
                          : null,
                      child: const Text('Berikutnya >'),
                    ),
                  ],
                ),
              ],
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text('Gagal memuat soal: $error')),
      ),
    );
  }
}

class _OptionsList extends StatelessWidget {
  const _OptionsList({required this.question});

  final ExamQuestion question;

  static const _labels = ['A', 'B', 'C', 'D', 'E', 'F'];

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      itemCount: question.options.length,
      separatorBuilder: (_, _) => const SizedBox(height: AppSpacing.sm),
      itemBuilder: (context, index) {
        final option = question.options[index];
        final label = index < _labels.length ? _labels[index] : '${index + 1}';

        return Card(
          margin: EdgeInsets.zero,
          child: ListTile(
            leading: CircleAvatar(child: Text(label)),
            // is_correct sengaja tidak ditampilkan — pratinjau ini meniru apa
            // yang dilihat mahasiswa, bukan tampilan admin.
            title: Text(option.optionText),
          ),
        );
      },
    );
  }
}
