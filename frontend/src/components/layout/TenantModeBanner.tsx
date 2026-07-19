import { useState } from 'react'
import { Building2, LogOut } from 'lucide-react'
import { useTenantStore } from '@/stores/tenantStore'
import { supportSessionService } from '@/services/tenancyService'

/**
 * Bar permanen supaya Super Admin tidak pernah lupa sedang berada di
 * konteks tenant mana — tampil di semua halaman, bukan cuma di dropdown
 * Header. Lihat docs/RANCANGAN-SUPER-ADMIN.md §1.
 */
export function TenantModeBanner() {
  const selectedUniversity = useTenantStore((state) => state.selectedUniversity)
  const supportSessionId = useTenantStore((state) => state.supportSessionId)
  const exitTenant = useTenantStore((state) => state.exitTenant)
  const [isExiting, setIsExiting] = useState(false)

  if (!selectedUniversity) return null

  const handleExit = async () => {
    setIsExiting(true)
    try {
      if (supportSessionId) await supportSessionService.end(supportSessionId)
    } catch {
      // Tetap keluarkan dari mode tenant meski gagal menutup sesi di server.
    } finally {
      exitTenant()
      setIsExiting(false)
    }
  }

  return (
    <div className="flex items-center justify-center gap-2 bg-primary px-4 py-1.5 text-xs font-medium text-white">
      <Building2 className="size-3.5" />
      <span>
        Anda sedang melihat data: <strong>{selectedUniversity.name}</strong>
      </span>
      <button
        type="button"
        onClick={handleExit}
        disabled={isExiting}
        className="ml-2 flex items-center gap-1 rounded px-2 py-0.5 underline-offset-2 hover:underline"
      >
        <LogOut className="size-3" /> Keluar
      </button>
    </div>
  )
}
