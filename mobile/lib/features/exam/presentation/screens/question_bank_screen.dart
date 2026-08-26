import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../domain/entities/exam_question.dart';
import '../controllers/question_bank_controller.dart';

/// Bank soal satu ujian (spec §7) — tambah/ubah/hapus soal & opsi jawaban,
/// dan menandai soal untuk mode pemilihan manual.
class QuestionBankScreen extends ConsumerWidget {
  const QuestionBankScreen({super.key, required this.examId});

  final String examId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final questionsAsync = ref.watch(questionBankControllerProvider(examId));

    return Scaffold(
      appBar: AppBar(
        title: questionsAsync.when(
          data: (questions) => Text('Bank Soal (${questions.length})'),
          loading: () => const Text('Bank Soal'),
          error: (_, _) => const Text('Bank Soal'),
        ),
      ),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _openQuestionEditor(context, ref),
        child: const Icon(Icons.add),
      ),
      body: questionsAsync.when(
        data: (questions) {
          if (questions.isEmpty) {
            return const Center(
              child: Text('Belum ada soal. Tambahkan soal pertama Anda.'),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.all(AppSpacing.lg),
            itemCount: questions.length,
            itemBuilder: (context, index) {
              final question = questions[index];

              return Card(
                margin: const EdgeInsets.only(bottom: AppSpacing.md),
                child: ListTile(
                  title: Text(question.questionText),
                  subtitle: Text(
                    '${question.options.length} opsi • '
                    '${question.options.where((o) => o.isCorrect).length} jawaban benar'
                    '${question.isSelected ? '' : ' • tidak dipilih (manual)'}',
                  ),
                  trailing: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Switch(
                        value: question.isSelected,
                        onChanged: (value) => ref
                            .read(
                              questionBankControllerProvider(examId).notifier,
                            )
                            .updateQuestion(question.id, isSelected: value),
                      ),
                      IconButton(
                        icon: const Icon(Icons.delete_outline),
                        onPressed: () => ref
                            .read(
                              questionBankControllerProvider(examId).notifier,
                            )
                            .deleteQuestion(question.id),
                      ),
                    ],
                  ),
                  onTap: () =>
                      _openQuestionEditor(context, ref, question: question),
                ),
              );
            },
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text('Gagal memuat soal: $error')),
      ),
    );
  }

  Future<void> _openQuestionEditor(
    BuildContext context,
    WidgetRef ref, {
    ExamQuestion? question,
  }) {
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (_) => _QuestionEditorSheet(examId: examId, question: question),
    );
  }
}

class _QuestionEditorSheet extends ConsumerStatefulWidget {
  const _QuestionEditorSheet({required this.examId, this.question});

  final String examId;
  final ExamQuestion? question;

  @override
  ConsumerState<_QuestionEditorSheet> createState() =>
      _QuestionEditorSheetState();
}

class _QuestionEditorSheetState extends ConsumerState<_QuestionEditorSheet> {
  late final TextEditingController _questionController;
  late List<TextEditingController> _optionControllers;
  late List<bool> _isCorrect;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final question = widget.question;
    _questionController = TextEditingController(
      text: question?.questionText ?? '',
    );
    _optionControllers = question != null
        ? question.options
              .map((o) => TextEditingController(text: o.optionText))
              .toList()
        : [TextEditingController(), TextEditingController()];
    _isCorrect = question != null
        ? question.options.map((o) => o.isCorrect).toList()
        : [true, false];
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: AppSpacing.lg,
        right: AppSpacing.lg,
        top: AppSpacing.lg,
        bottom: MediaQuery.of(context).viewInsets.bottom + AppSpacing.lg,
      ),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              widget.question == null ? 'Tambah Soal' : 'Ubah Soal',
              style: Theme.of(context).textTheme.titleLarge,
            ),
            const SizedBox(height: AppSpacing.md),
            AppTextField(label: 'Pertanyaan', controller: _questionController),
            const SizedBox(height: AppSpacing.md),
            Text('Opsi Jawaban', style: Theme.of(context).textTheme.labelLarge),
            const SizedBox(height: AppSpacing.sm),
            for (var i = 0; i < _optionControllers.length; i++)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                child: Row(
                  children: [
                    Checkbox(
                      value: _isCorrect[i],
                      onChanged: (value) => setState(() {
                        // Radio-semantics: hanya satu opsi benar per soal.
                        for (var j = 0; j < _isCorrect.length; j++) {
                          _isCorrect[j] = j == i && (value ?? false);
                        }
                      }),
                    ),
                    Expanded(
                      child: AppTextField(
                        label: 'Opsi ${i + 1}',
                        controller: _optionControllers[i],
                      ),
                    ),
                    if (_optionControllers.length > 2)
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => setState(() {
                          _optionControllers.removeAt(i);
                          _isCorrect.removeAt(i);
                        }),
                      ),
                  ],
                ),
              ),
            AppButton(
              label: 'Tambah Opsi',
              variant: AppButtonVariant.ghost,
              onPressed: () => setState(() {
                _optionControllers.add(TextEditingController());
                _isCorrect.add(false);
              }),
            ),
            const SizedBox(height: AppSpacing.md),
            AppButton(
              label: 'Simpan',
              expand: true,
              isLoading: _saving,
              onPressed: _save,
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _save() async {
    final options = [
      for (var i = 0; i < _optionControllers.length; i++)
        (optionText: _optionControllers[i].text, isCorrect: _isCorrect[i]),
    ];

    setState(() => _saving = true);
    try {
      final notifier = ref.read(
        questionBankControllerProvider(widget.examId).notifier,
      );
      if (widget.question == null) {
        await notifier.addQuestion(
          questionText: _questionController.text,
          points: 1,
          options: options,
        );
      } else {
        await notifier.updateQuestion(
          widget.question!.id,
          questionText: _questionController.text,
          options: options,
        );
      }
      if (mounted) Navigator.of(context).pop();
    } on ApiFailure catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}
