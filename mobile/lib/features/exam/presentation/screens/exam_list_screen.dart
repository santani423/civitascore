import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../controllers/exam_list_controller.dart';

class ExamListScreen extends ConsumerWidget {
  const ExamListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final examsAsync = ref.watch(examListControllerProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Ujian')),
      floatingActionButton: FloatingActionButton(
        onPressed: () => context.push('/akademik/ujian/baru'),
        child: const Icon(Icons.add),
      ),
      body: examsAsync.when(
        data: (exams) {
          if (exams.isEmpty) {
            return const Center(
              child: Text('Belum ada ujian. Buat ujian pertama Anda.'),
            );
          }

          return RefreshIndicator(
            onRefresh: () =>
                ref.read(examListControllerProvider.notifier).refresh(),
            child: ListView.builder(
              padding: const EdgeInsets.all(AppSpacing.lg),
              itemCount: exams.length,
              itemBuilder: (context, index) {
                final exam = exams[index];

                return Card(
                  margin: const EdgeInsets.only(bottom: AppSpacing.md),
                  child: ListTile(
                    title: Text(exam.title),
                    subtitle: Text(
                      '${exam.questionsPerParticipant} soal per peserta • '
                      '${exam.questionPoolSize} soal tersedia',
                    ),
                    trailing: Chip(
                      label: Text(
                        exam.isPublished ? 'Dipublikasikan' : 'Draft',
                      ),
                      backgroundColor: exam.isPublished
                          ? Colors.green.withValues(alpha: 0.15)
                          : Colors.grey.withValues(alpha: 0.15),
                    ),
                    onTap: () =>
                        context.push('/akademik/ujian/${exam.id}/edit'),
                  ),
                );
              },
            ),
          );
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => Center(child: Text('Gagal memuat ujian: $error')),
      ),
    );
  }
}
