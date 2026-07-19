import { useEffect, useState } from 'react'
import { Building2, LogOut } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { useIsSuperAdmin } from '@/hooks/useIsSuperAdmin'
import { useTenantStore } from '@/stores/tenantStore'
import { universityService, supportSessionService } from '@/services/tenancyService'
import type { NormalizedApiError } from '@/services/api'
import type { University } from '@/types/tenancy'

/**
 * Satu-satunya jalur Super Admin masuk ke konteks satu universitas — lihat
 * docs/RANCANGAN-SUPER-ADMIN.md §1 dan §6.6 (Support Session). Tidak
 * dirender sama sekali untuk pengguna tenant biasa.
 */
export function TenantSwitcher() {
  const isSuperAdmin = useIsSuperAdmin()
  const selectedUniversity = useTenantStore((state) => state.selectedUniversity)
  const supportSessionId = useTenantStore((state) => state.supportSessionId)
  const enterTenant = useTenantStore((state) => state.enterTenant)
  const exitTenant = useTenantStore((state) => state.exitTenant)

  const [pickerOpen, setPickerOpen] = useState(false)
  const [isExiting, setIsExiting] = useState(false)

  if (!isSuperAdmin) return null

  const handleExit = async () => {
    if (!supportSessionId) {
      exitTenant()
      return
    }

    setIsExiting(true)
    try {
      await supportSessionService.end(supportSessionId)
    } catch {
      // Sesi mungkin sudah berakhir/dihapus di sisi lain — tetap keluarkan
      // dari mode tenant di frontend, jangan sampai pengguna terjebak.
    } finally {
      exitTenant()
      setIsExiting(false)
    }
  }

  if (selectedUniversity) {
    return (
      <div className="flex items-center gap-1.5 rounded-lg border border-border bg-surface-hover px-2.5 py-1.5 text-sm">
        <Building2 className="size-4 text-ink-secondary" />
        <span className="max-w-32 truncate font-medium text-ink-primary sm:max-w-none">{selectedUniversity.name}</span>
        <button
          type="button"
          onClick={handleExit}
          disabled={isExiting}
          aria-label="Keluar dari mode tenant"
          className="ml-1 flex size-6 items-center justify-center rounded text-ink-tertiary transition-colors hover:bg-surface hover:text-danger"
        >
          <LogOut className="size-3.5" />
        </button>
      </div>
    )
  }

  return (
    <>
      <Button variant="outline" size="sm" leftIcon={<Building2 className="size-4" />} onClick={() => setPickerOpen(true)}>
        Pilih Universitas
      </Button>

      {pickerOpen && (
        <TenantPickerModal
          onClose={() => setPickerOpen(false)}
          onEntered={(university, sessionId) => {
            enterTenant(university, sessionId)
            setPickerOpen(false)
          }}
        />
      )}
    </>
  )
}

function TenantPickerModal({
  onClose,
  onEntered,
}: {
  onClose: () => void
  onEntered: (university: { id: string; name: string }, supportSessionId: string) => void
}) {
  const [universities, setUniversities] = useState<University[] | null>(null)
  const [selectedId, setSelectedId] = useState('')
  const [reason, setReason] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    universityService
      .index({ per_page: 100 })
      .then((result) => setUniversities(result.data))
      .catch((fetchError: NormalizedApiError) => setError(fetchError.message))
  }, [])

  const handleSubmit = async () => {
    if (!selectedId || !reason.trim()) return

    setError(null)
    setIsSubmitting(true)

    try {
      const session = await supportSessionService.start(selectedId, reason.trim())
      const university = universities?.find((item) => item.id === selectedId)
      onEntered({ id: selectedId, name: university?.name ?? selectedId }, session.id)
    } catch (submitError) {
      setError((submitError as NormalizedApiError).message ?? 'Gagal memulai support session.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Modal open onClose={onClose} title="Masuk sebagai Universitas">
      <p className="mb-4 text-sm text-ink-secondary">
        Anda akan melihat data operasional satu universitas untuk keperluan dukungan teknis. Setiap sesi tercatat —
        wajib isi alasan.
      </p>

      {error && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setError(null)}>
          {error}
        </Alert>
      )}

      <div className="flex flex-col gap-4">
        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink-primary">Universitas</label>
          {!universities ? (
            <p className="text-sm text-ink-tertiary">Memuat...</p>
          ) : (
            <select
              value={selectedId}
              onChange={(event) => setSelectedId(event.target.value)}
              className="h-10 w-full rounded-lg border border-border-strong bg-surface px-3 text-sm text-ink-primary focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="">Pilih universitas...</option>
              {universities.map((university) => (
                <option key={university.id} value={university.id}>
                  {university.name} ({university.code})
                </option>
              ))}
            </select>
          )}
        </div>

        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink-primary">Alasan</label>
          <textarea
            rows={3}
            value={reason}
            onChange={(event) => setReason(event.target.value)}
            placeholder="mis. Membantu admin universitas memperbaiki data yang salah input."
            className="w-full rounded-lg border border-border-strong bg-surface px-3 py-2 text-sm text-ink-primary placeholder:text-ink-tertiary focus:outline-none focus:ring-2 focus:ring-primary"
          />
        </div>

        <div className="mt-1 flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button isLoading={isSubmitting} disabled={!selectedId || !reason.trim()} onClick={handleSubmit}>
            Masuk
          </Button>
        </div>
      </div>
    </Modal>
  )
}
