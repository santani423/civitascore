import { useEffect, useRef, useState } from 'react'
import { examService } from '@/services/academicService'
import type { ExamViolation } from '@/types/academic'

const POLL_INTERVAL_MS = 5000

/**
 * Alert "near-real-time" pelanggaran ujian ke dosen (spec §6) lewat polling
 * berkala — bukan WebSocket/broadcasting. Tidak ada driver broadcasting
 * (Reverb/Pusher) yang dikonfigurasi di proyek ini (`BROADCAST_CONNECTION=log`,
 * lihat audit sebelum implementasi) dan lingkungan hosting saat ini
 * (shared hosting) kemungkinan besar tidak bisa menjalankan proses
 * WebSocket persisten, jadi polling adalah pilihan yang benar-benar bisa
 * dijalankan tanpa infrastruktur baru — bisa diganti ke channel privat
 * Reverb nanti tanpa mengubah pencatatan pelanggaran di backend sama sekali.
 *
 * Cursor `since` dari `occurred_at` kejadian terakhir mencegah notifikasi
 * dobel; dedup tambahan lewat Set id di pemanggil kalau diperlukan.
 */
export function useExamViolationAlerts(
  examId: string | undefined,
  enabled: boolean,
): { alerts: ExamViolation[]; dismiss: (id: string) => void } {
  const [alerts, setAlerts] = useState<ExamViolation[]>([])
  const sinceRef = useRef<string | null>(new Date().toISOString())
  const seenIdsRef = useRef<Set<string>>(new Set())

  useEffect(() => {
    if (!examId || !enabled) return
    let cancelled = false

    async function poll() {
      try {
        const violations = await examService.getRecentViolations(examId as string, sinceRef.current)
        if (cancelled || violations.length === 0) return

        const fresh = violations.filter((v) => !seenIdsRef.current.has(v.id))
        fresh.forEach((v) => seenIdsRef.current.add(v.id))

        sinceRef.current = violations[violations.length - 1].occurred_at

        if (fresh.length > 0) {
          setAlerts((current) => [...fresh, ...current].slice(0, 20))
        }
      } catch {
        // Polling gagal sesaat (jaringan dsb) — dicoba lagi di interval berikutnya, tidak perlu ditampilkan sebagai error mengganggu.
      }
    }

    const interval = setInterval(poll, POLL_INTERVAL_MS)
    return () => {
      cancelled = true
      clearInterval(interval)
    }
  }, [examId, enabled])

  const dismiss = (id: string) => setAlerts((current) => current.filter((v) => v.id !== id))

  return { alerts, dismiss }
}
