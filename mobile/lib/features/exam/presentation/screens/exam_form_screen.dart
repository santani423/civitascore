import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/app_alert.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_card.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../../domain/entities/question_selection_mode.dart';
import '../../domain/exam_validation.dart';
import '../controllers/exam_form_controller.dart';
import '../controllers/question_bank_controller.dart';
import 'exam_preview_screen.dart';

/// Wizard pembuatan/edit ujian (spec §3 "Step 2 — Soal", §22). `examId` null
/// berarti alur pembuatan ujian baru.
class ExamFormScreen extends ConsumerStatefulWidget {
  const ExamFormScreen({super.key, this.examId});

  final String? examId;

  @override
  ConsumerState<ExamFormScreen> createState() => _ExamFormScreenState();
}

class _ExamFormScreenState extends ConsumerState<ExamFormScreen> {
  int _step = 0;
  bool _busy = false;

  @override
  Widget build(BuildContext context) {
    final provider = examFormControllerProvider(widget.examId);
    final formAsync = ref.watch(provider);
    final controller = ref.read(provider.notifier);

    return Scaffold(
      appBar: AppBar(
        title: Text(widget.examId == null ? 'Ujian Baru' : 'Edit Ujian'),
      ),
      body: formAsync.when(
        data: (state) => _buildStepper(context, state, controller),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text('Gagal memuat ujian: $error')),
      ),
    );
  }

  Widget _buildStepper(
    BuildContext context,
    ExamFormState state,
    ExamFormController controller,
  ) {
    final questionsAsync = state.examId != null
        ? ref.watch(questionBankControllerProvider(state.examId!))
        : null;
    final poolSize =
        questionsAsync?.valueOrNull?.length ?? state.questionPoolSize;
    final manuallySelected =
        questionsAsync?.valueOrNull?.where((q) => q.isSelected).length ?? 0;

    final errors = ExamValidation.errorsFor(
      questionPoolSize: poolSize,
      questionsPerParticipant: state.questionsPerParticipant,
      selectionMode: state.selectionMode,
      manuallySelectedCount: manuallySelected,
    );

    return Stepper(
      currentStep: _step,
      onStepTapped: (index) => setState(() => _step = index),
      controlsBuilder: (context, details) => Padding(
        padding: const EdgeInsets.only(top: AppSpacing.md),
        child: Row(
          children: [
            if (_step > 0)
              AppButton(
                label: 'Kembali',
                variant: AppButtonVariant.outline,
                onPressed: _busy ? null : () => setState(() => _step -= 1),
              ),
            const SizedBox(width: AppSpacing.sm),
            if (_step < 2)
              AppButton(
                label: 'Lanjut',
                isLoading: _busy,
                onPressed: () => _saveAndContinue(controller),
              )
            else
              AppButton(
                label: state.isPublished
                    ? 'Sudah Dipublikasikan'
                    : 'Publish Ujian',
                isLoading: _busy,
                onPressed: state.isPublished || errors.isNotEmpty
                    ? null
                    : () => _publish(controller),
              ),
          ],
        ),
      ),
      steps: [
        Step(
          title: const Text('Informasi'),
          isActive: _step >= 0,
          state: _step > 0 ? StepState.complete : StepState.indexed,
          content: _InfoStep(state: state, controller: controller),
        ),
        Step(
          title: const Text('Soal'),
          isActive: _step >= 1,
          state: _step > 1 ? StepState.complete : StepState.indexed,
          content: _QuestionSettingsStep(state: state, controller: controller),
        ),
        Step(
          title: const Text('Preview & Publish'),
          isActive: _step >= 2,
          content: _PreviewStep(
            state: state,
            poolSize: poolSize,
            errors: errors,
          ),
        ),
      ],
    );
  }

  Future<void> _saveAndContinue(ExamFormController controller) async {
    setState(() => _busy = true);
    try {
      await controller.save();
      if (mounted) setState(() => _step = (_step + 1).clamp(0, 2));
    } on ApiFailure catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _publish(ExamFormController controller) async {
    setState(() => _busy = true);
    try {
      await controller.publish();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Ujian berhasil dipublikasikan.')),
        );
      }
    } on ApiFailure catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(e.message)));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }
}

class _InfoStep extends StatelessWidget {
  const _InfoStep({required this.state, required this.controller});

  final ExamFormState state;
  final ExamFormController controller;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AppTextField(
          label: 'ID Kelas (Class Section)',
          controller: TextEditingController(text: state.classSectionId),
          onChanged: controller.setClassSectionId,
          enabled: state.examId == null,
        ),
        const SizedBox(height: AppSpacing.md),
        AppTextField(
          label: 'Judul Ujian',
          controller: TextEditingController(text: state.title),
          onChanged: controller.setTitle,
        ),
        const SizedBox(height: AppSpacing.md),
        AppTextField(
          label: 'Durasi (menit)',
          controller: TextEditingController(text: '${state.durationMinutes}'),
          keyboardType: TextInputType.number,
          onChanged: (value) => controller.setDurationMinutes(
            int.tryParse(value) ?? state.durationMinutes,
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        AppTextField(
          label: 'Batas Percobaan',
          controller: TextEditingController(text: '${state.maxAttempts}'),
          keyboardType: TextInputType.number,
          onChanged: (value) => controller.setMaxAttempts(
            int.tryParse(value) ?? state.maxAttempts,
          ),
        ),
      ],
    );
  }
}

class _QuestionSettingsStep extends ConsumerWidget {
  const _QuestionSettingsStep({required this.state, required this.controller});

  final ExamFormState state;
  final ExamFormController controller;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AppCard(
          title: 'Pengaturan Soal',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: AppSpacing.sm),
              Text(
                'Question Pool: ${state.questionPoolSize} soal',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(
                'Semua soal yang tersedia dan dapat dipilih untuk ujian ini.',
                style: Theme.of(context).textTheme.bodySmall,
              ),
              const SizedBox(height: AppSpacing.md),
              if (state.examId != null)
                AppButton(
                  label: 'Kelola Bank Soal',
                  variant: AppButtonVariant.outline,
                  leftIcon: Icons.quiz_outlined,
                  onPressed: () =>
                      context.push('/akademik/ujian/${state.examId}/soal'),
                )
              else
                const Text(
                  'Simpan informasi ujian terlebih dahulu untuk menambah soal.',
                ),
              const SizedBox(height: AppSpacing.lg),
              AppTextField(
                label: 'Jumlah Soal per Peserta',
                controller: TextEditingController(
                  text: '${state.questionsPerParticipant}',
                ),
                keyboardType: TextInputType.number,
                onChanged: (value) => controller.setQuestionsPerParticipant(
                  int.tryParse(value) ?? state.questionsPerParticipant,
                ),
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(
                'Tentukan berapa soal yang harus dikerjakan setiap peserta dari '
                'seluruh soal yang Anda siapkan.',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        AppCard(
          title: 'Pemilihan Soal',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Tentukan bagaimana soal dipilih untuk setiap peserta.',
                style: Theme.of(context).textTheme.bodySmall,
              ),
              for (final mode in QuestionSelectionMode.values)
                RadioListTile<QuestionSelectionMode>(
                  contentPadding: EdgeInsets.zero,
                  title: Text(mode.label),
                  value: mode,
                  groupValue: state.selectionMode,
                  onChanged: (value) {
                    if (value != null) controller.setSelectionMode(value);
                  },
                ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        AppCard(
          title: 'Pengacakan',
          child: Column(
            children: [
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Urutan Soal'),
                subtitle: const Text(
                  'Tentukan apakah posisi soal akan berbeda untuk setiap peserta.',
                ),
                value: state.randomizeQuestions,
                onChanged: controller.setRandomizeQuestions,
              ),
              SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Pilihan Jawaban'),
                subtitle: const Text(
                  'Tentukan apakah opsi A, B, C, D akan diacak.',
                ),
                value: state.randomizeOptions,
                onChanged: controller.setRandomizeOptions,
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        ExpansionTile(
          tilePadding: EdgeInsets.zero,
          title: const Text('Pengaturan Lanjutan'),
          children: [
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Izinkan Kembali ke Soal Sebelumnya'),
              value: state.allowBackNavigation,
              onChanged: controller.setAllowBackNavigation,
            ),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Tampilkan Nilai Setelah Ujian'),
              value: state.showResultAfterSubmission,
              onChanged: controller.setShowResultAfterSubmission,
            ),
          ],
        ),
      ],
    );
  }
}

class _PreviewStep extends StatelessWidget {
  const _PreviewStep({
    required this.state,
    required this.poolSize,
    required this.errors,
  });

  final ExamFormState state;
  final int poolSize;
  final List<String> errors;

  @override
  Widget build(BuildContext context) {
    final summary = ExamValidation.distributionSummary(
      questionPoolSize: poolSize,
      questionsPerParticipant: state.questionsPerParticipant,
      selectionMode: state.selectionMode,
      randomizeQuestions: state.randomizeQuestions,
      randomizeOptions: state.randomizeOptions,
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        AppCard(
          title: 'Distribusi Soal',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: AppSpacing.sm),
              _summaryRow('Question Pool', '$poolSize'),
              _summaryRow(
                'Soal per Peserta',
                '${state.questionsPerParticipant}',
              ),
              _summaryRow('Pemilihan', state.selectionMode.label),
              _summaryRow(
                'Urutan Soal',
                state.randomizeQuestions ? 'Diacak' : 'Sesuai Urutan',
              ),
              _summaryRow(
                'Pilihan Jawaban',
                state.randomizeOptions ? 'Diacak' : 'Sesuai Urutan',
              ),
              const SizedBox(height: AppSpacing.md),
              AppAlert(message: summary),
            ],
          ),
        ),
        if (errors.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.md),
          for (final error in errors) ...[
            AppAlert(message: error, variant: AppAlertVariant.danger),
            const SizedBox(height: AppSpacing.sm),
          ],
        ],
        const SizedBox(height: AppSpacing.md),
        if (state.examId != null)
          AppButton(
            label: 'Preview sebagai Mahasiswa',
            variant: AppButtonVariant.secondary,
            leftIcon: Icons.visibility_outlined,
            onPressed: () => Navigator.of(context).push(
              MaterialPageRoute(
                builder: (_) => ExamPreviewScreen(
                  examId: state.examId!,
                  title: state.title,
                ),
              ),
            ),
          ),
      ],
    );
  }

  Widget _summaryRow(String label, String value) => Padding(
    padding: const EdgeInsets.symmetric(vertical: AppSpacing.xs),
    child: Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label),
        Text(value, style: const TextStyle(fontWeight: FontWeight.w600)),
      ],
    ),
  );
}
