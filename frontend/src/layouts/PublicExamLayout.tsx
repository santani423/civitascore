import { Outlet } from 'react-router-dom'
import { APP_NAME } from '@/constants/app'
import { ThemeToggle } from '@/components/layout/ThemeToggle'
import logoAtmaJaya from '@/assets/images/logo-atmajaya.gif'

/**
 * Layout standalone untuk /exam/* — sengaja tidak memakai DashboardLayout
 * (sidebar/header portal) maupun AuthLayout (panel split login): halaman
 * ujian publik dibuka tanpa login sama sekali, lihat komentar rute di
 * App.tsx. Hanya bar merek tipis + area konten penuh, supaya halaman
 * pengerjaan ujian tetap bisa memakai seluruh lebar/tinggi layar.
 */
export function PublicExamLayout() {
  return (
    <div className="min-h-screen bg-background">
      <div className="flex items-center justify-between border-b border-border px-4 py-3 sm:px-6">
        <div className="flex items-center gap-2">
          <img src={logoAtmaJaya} alt={APP_NAME} className="size-7 object-contain" />
          <span className="text-sm font-semibold text-ink-primary">{APP_NAME}</span>
        </div>
        <ThemeToggle />
      </div>

      <Outlet />
    </div>
  )
}
