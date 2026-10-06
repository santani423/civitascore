import type { Exam, QuestionSelectionMode } from '@/types/academic'

/**
 * Aturan validasi konfigurasi ujian (spec §2/§8/§18), diduplikasi murni
 * sebagai umpan balik instan sisi klien — backend (ExamService::publish di
 * app/Modules/Academic) tetap satu-satunya penegak. Pesan di sini sengaja
 * sama persis dengan pesan backend. Padanan mobile:
 * mobile/lib/features/exam/domain/exam_validation.dart.
 */
export function examValidationErrors({
  questionPoolSize,
  questionsPerParticipant,
  selectionMode,
  manuallySelectedCount,
}: {
  questionPoolSize: number
  questionsPerParticipant: number
  selectionMode: QuestionSelectionMode
  manuallySelectedCount: number
}): string[] {
  const errors: string[] = []

  if (questionsPerParticipant <= 0) {
    errors.push('Jumlah soal harus lebih dari 0.')
  }

  if (questionPoolSize === 0) {
    errors.push('Question pool masih kosong. Tambahkan soal terlebih dahulu.')
  }

  if (questionsPerParticipant > questionPoolSize) {
    errors.push('Jumlah soal per peserta tidak boleh melebihi jumlah soal dalam question pool.')
  }

  if (selectionMode === 'manual' && manuallySelectedCount < questionsPerParticipant) {
    errors.push(
      `Soal belum mencukupi. Anda membutuhkan ${questionsPerParticipant} soal, tetapi baru memilih ${manuallySelectedCount} soal.`,
    )
  }

  return errors
}

export function examDistributionSummary({
  questionPoolSize,
  questionsPerParticipant,
  selectionMode,
  randomizeQuestions,
  randomizeOptions,
}: {
  questionPoolSize: number
  questionsPerParticipant: number
  selectionMode: QuestionSelectionMode
  randomizeQuestions: boolean
  randomizeOptions: boolean
}): string {
  let selectionText = `seluruh ${questionPoolSize} soal yang tersedia`
  if (selectionMode === 'random') {
    selectionText = `${questionsPerParticipant} soal yang dipilih secara acak dari ${questionPoolSize} soal`
  } else if (selectionMode === 'manual') {
    selectionText = `${questionsPerParticipant} soal yang telah dipilih secara manual`
  }

  let summary = `Setiap peserta akan mendapatkan ${selectionText}.`

  const randomizedParts: string[] = []
  if (randomizeQuestions) randomizedParts.push('urutan soal')
  if (randomizeOptions) randomizedParts.push('pilihan jawaban')

  if (randomizedParts.length > 0) {
    const joined = randomizedParts.join(' dan ')
    summary += ` ${joined.charAt(0).toUpperCase()}${joined.slice(1)} akan diacak.`
  }

  return summary
}

/**
 * Ujian sudah dipublikasikan DAN waktu sekarang ada dalam jendela
 * starts_at/ends_at-nya — cermin dari ExamService::assertWithinSchedule di
 * backend (app/Modules/Academic/Services/ExamService.php), dipakai untuk
 * menyalakan polling aktivitas/pelanggaran near-real-time di halaman detail
 * ujian (dosen) hanya selama peserta benar-benar bisa mengerjakan.
 */
export function isExamWindowOpen(exam: Pick<Exam, 'starts_at' | 'ends_at'>): boolean {
  const now = new Date()

  if (exam.starts_at && now < new Date(exam.starts_at)) return false
  if (exam.ends_at && now > new Date(exam.ends_at)) return false

  return true
}

export const QUESTION_SELECTION_MODE_LABEL: Record<QuestionSelectionMode, string> = {
  all: 'Semua Soal',
  random: 'Acak dari Question Pool',
  manual: 'Pilih Soal Secara Manual',
}
