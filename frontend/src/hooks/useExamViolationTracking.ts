import { useCallback, useEffect, useRef } from 'react'
import type { ExamViolationType } from '@/types/academic'

const VIOLATION_DEBOUNCE_MS = 1000

/**
 * Deteksi pelanggaran ujian dari event browser yang tersedia (spec §4) —
 * TAB_SWITCH (visibilitychange), WINDOW_BLUR (blur), FULLSCREEN_EXIT
 * (fullscreenchange), COPY_ATTEMPT/PASTE_ATTEMPT/CONTEXT_MENU (event
 * clipboard/contextmenu level dokumen, bukan per-elemen, supaya tertangkap
 * di mana pun fokus berada). Ini HANYA penghalang & deteksi sisi klien —
 * backend (ExamService::recordViolation) tetap satu-satunya sumber
 * kebenaran jumlah pelanggaran & penalti, lihat komentar di
 * PortalExamTakingPage soal keterbatasan ini (screenshot OS-level dsb tidak
 * bisa dideteksi dari halaman web).
 *
 * Dipakai bersama oleh PortalExamTakingPage (mahasiswa login) dan
 * ExamPublicAttemptPage (akses publik lewat NIM) — satu-satunya bagian dari
 * exam-taking yang identik di kedua alur, jadi diekstrak ke sini alih-alih
 * digandakan.
 */
export function useExamViolationTracking(isActive: boolean, onViolation: (type: ExamViolationType) => void): void {
  const lastViolationAtRef = useRef(0)

  const report = useCallback(
    (type: ExamViolationType) => {
      const now = Date.now()
      if (now - lastViolationAtRef.current < VIOLATION_DEBOUNCE_MS) return
      lastViolationAtRef.current = now
      onViolation(type)
    },
    [onViolation],
  )

  useEffect(() => {
    if (!isActive) return

    function handleVisibilityChange() {
      if (document.visibilityState === 'hidden') report('tab_switch')
    }

    function handleBlur() {
      report('window_blur')
    }

    function handleFullscreenChange() {
      if (!document.fullscreenElement) report('fullscreen_exit')
    }

    function handleCopy(event: ClipboardEvent) {
      event.preventDefault()
      report('copy_attempt')
    }

    function handlePaste(event: ClipboardEvent) {
      event.preventDefault()
      report('paste_attempt')
    }

    function handleContextMenu(event: MouseEvent) {
      event.preventDefault()
      report('context_menu')
    }

    document.addEventListener('visibilitychange', handleVisibilityChange)
    window.addEventListener('blur', handleBlur)
    document.addEventListener('fullscreenchange', handleFullscreenChange)
    document.addEventListener('copy', handleCopy)
    document.addEventListener('paste', handlePaste)
    document.addEventListener('contextmenu', handleContextMenu)

    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange)
      window.removeEventListener('blur', handleBlur)
      document.removeEventListener('fullscreenchange', handleFullscreenChange)
      document.removeEventListener('copy', handleCopy)
      document.removeEventListener('paste', handlePaste)
      document.removeEventListener('contextmenu', handleContextMenu)
    }
  }, [isActive, report])
}
