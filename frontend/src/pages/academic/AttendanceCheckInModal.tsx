import { useEffect, useState } from 'react'
import { QRCodeSVG } from 'qrcode.react'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Select } from '@/components/ui/Select'
import { attendanceService, classSectionService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import type { AttendanceQr, AttendanceSession, ClassSection } from '@/types/academic'
import { fetchAllPages } from '@/utils/fetchAllPages'
import { toLocalIsoDate } from '@/utils/formatters'

const TIME_FORMAT: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit' }

/**
 * Presensi mandiri: dosen membuka sesi untuk satu pertemuan, lalu mahasiswa
 * memindai QR yang ditampilkan di sini. QR diminta ulang setiap kali slotnya
 * berganti. Menutup jendela tidak menghentikan sesi; itu dilakukan lewat
 * "Tutup Sesi" atau dibiarkan berakhir sendiri.
 */
export function AttendanceCheckInModal({ onClose }: { onClose: () => void }) {
  const [classSections, setClassSections] = useState<ClassSection[] | null>(null)
  const [classSectionId, setClassSectionId] = useState('')
  const [meetingNumber, setMeetingNumber] = useState('1')
  const [meetingDate, setMeetingDate] = useState(() => toLocalIsoDate())
  const [durationMinutes, setDurationMinutes] = useState('15')

  const [session, setSession] = useState<AttendanceSession | null>(null)
  const [qr, setQr] = useState<AttendanceQr | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [isBusy, setIsBusy] = useState(false)

  useEffect(() => {
    fetchAllPages((params) => classSectionService.index(params))
      .then(setClassSections)
      .catch((err: NormalizedApiError) => setError(err.message))
  }, [])

  useEffect(() => {
    if (!session) return

    let cancelled = false
    let timer = 0

    const refresh = async () => {
      try {
        const next = await attendanceService.currentQr(session.id)
        if (cancelled) return

        setQr(next)
        const wait = new Date(next.expires_at).getTime() - Date.now()
        timer = window.setTimeout(refresh, Math.max(wait, 1000))
      } catch (err) {
        if (!cancelled) setError((err as NormalizedApiError).message)
      }
    }

    void refresh()

    return () => {
      cancelled = true
      window.clearTimeout(timer)
    }
  }, [session])

  const handleOpen = async () => {
    setError(null)
    setIsBusy(true)

    try {
      setSession(
        await attendanceService.openSession(classSectionId, {
          meeting_number: Number(meetingNumber),
          meeting_date: meetingDate,
          duration_minutes: Number(durationMinutes),
        }),
      )
    } catch (err) {
      setError((err as NormalizedApiError).message)
    } finally {
      setIsBusy(false)
    }
  }

  const handleClose = async () => {
    if (!session) return onClose()

    setError(null)
    setIsBusy(true)

    try {
      await attendanceService.closeSession(session.id)
      onClose()
    } catch (err) {
      setError((err as NormalizedApiError).message)
      setIsBusy(false)
    }
  }

  return (
    <Modal open onClose={onClose} title="Presensi Mandiri (QR)" className="max-w-md">
      {error && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setError(null)}>
          {error}
        </Alert>
      )}

      {!session && (
        <div className="flex flex-col gap-4">
          {classSections === null ? (
            <p className="text-sm text-ink-secondary">Memuat daftar kelas...</p>
          ) : (
            <Select
              label="Kelas"
              placeholder="Pilih kelas"
              value={classSectionId}
              onChange={(event) => setClassSectionId(event.target.value)}
              options={classSections.map((classSection) => ({
                value: classSection.id,
                label: `${classSection.course_name ?? '-'} (${classSection.class_code})`,
              }))}
            />
          )}

          <div className="grid grid-cols-2 gap-3">
            <Input
              type="number"
              min={1}
              label="Pertemuan ke-"
              value={meetingNumber}
              onChange={(event) => setMeetingNumber(event.target.value)}
            />
            <Input
              type="date"
              label="Tanggal"
              value={meetingDate}
              onChange={(event) => setMeetingDate(event.target.value)}
            />
          </div>

          <Input
            type="number"
            min={5}
            max={120}
            label="Durasi (menit)"
            value={durationMinutes}
            onChange={(event) => setDurationMinutes(event.target.value)}
          />

          <div className="mt-2 flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={onClose}>
              Batal
            </Button>
            <Button
              type="button"
              isLoading={isBusy}
              disabled={!classSectionId || !meetingNumber || !meetingDate}
              onClick={handleOpen}
            >
              Buka Sesi
            </Button>
          </div>
        </div>
      )}

      {session && (
        <div className="flex flex-col items-center gap-4">
          <div className="rounded-xl bg-white p-4">
            {qr ? (
              <QRCodeSVG value={qr.payload} size={224} includeMargin />
            ) : (
              <p className="flex h-56 items-center text-sm text-ink-secondary">Memuat QR...</p>
            )}
          </div>

          <div className="text-center">
            <p className="text-sm font-medium text-ink-primary">Pertemuan ke-{session.meeting_number}</p>
            <p className="text-xs text-ink-tertiary">
              QR diperbarui otomatis setiap 30 detik. Sesi berakhir pukul{' '}
              {new Date(session.expires_at).toLocaleTimeString('id-ID', TIME_FORMAT)}.
            </p>
            <p className="mt-2 text-xs text-ink-tertiary">
              Menutup jendela ini tidak menghentikan sesi. Tekan Tutup Sesi untuk menghentikannya.
            </p>
          </div>

          <div className="flex w-full justify-end">
            <Button type="button" isLoading={isBusy} onClick={handleClose}>
              Tutup Sesi
            </Button>
          </div>
        </div>
      )}
    </Modal>
  )
}
