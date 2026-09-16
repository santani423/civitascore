export function formatCountdown(ms: number): string {
  const totalSeconds = Math.floor(ms / 1000)
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60
  const pad = (value: number) => value.toString().padStart(2, '0')

  return hours > 0 ? `${pad(hours)}:${pad(minutes)}:${pad(seconds)}` : `${pad(minutes)}:${pad(seconds)}`
}

/**
 * Batas waktu efektif percobaan ini di sisi klien — mirror dari
 * ExamService::attemptDeadline() di backend (mana yang lebih dulu antara
 * habisnya durasi dan jadwal berakhir ujian). Ini HANYA untuk tampilan
 * countdown/auto-submit UX; backend tetap satu-satunya sumber kebenaran
 * waktu lewat finalizeIfExpired() di setiap panggilan answer/submit/show.
 * Dipakai bersama oleh PortalExamTakingPage (StudentExam) dan
 * ExamPublicAttemptPage (PublicExamInfo) — keduanya punya bentuk yang sama
 * untuk field yang dibutuhkan di sini.
 */
export function computeDeadlineMs(
  exam: { duration_minutes: number; ends_at: string | null },
  attempt: { started_at: string },
): number {
  const startedAtMs = new Date(attempt.started_at).getTime()
  const durationDeadlineMs = startedAtMs + exam.duration_minutes * 60_000
  const examEndsAtMs = exam.ends_at ? new Date(exam.ends_at).getTime() : null

  return examEndsAtMs !== null && examEndsAtMs < durationDeadlineMs ? examEndsAtMs : durationDeadlineMs
}
