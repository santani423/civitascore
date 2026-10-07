import { useState } from 'react'
import { Outlet, useLocation } from 'react-router-dom'
import { Sidebar } from '@/components/layout/Sidebar'
import { Header } from '@/components/layout/Header'
import { TenantModeBanner } from '@/components/layout/TenantModeBanner'
import { useSessionRefresh } from '@/hooks/useSessionRefresh'

export function DashboardLayout() {
  const [collapsed, setCollapsed] = useState(false)
  const [mobileOpen, setMobileOpen] = useState(false)
  const { pathname } = useLocation()
  const [lastPathname, setLastPathname] = useState(pathname)

  useSessionRefresh()

  // Drawer sidebar di mobile ditutup setiap kali halaman berpindah
  // (state turunan saat render, bukan effect — lihat React docs
  // "Adjusting some state when a prop changes").
  if (pathname !== lastPathname) {
    setLastPathname(pathname)
    if (mobileOpen) setMobileOpen(false)
  }

  const handleMenuClick = () => {
    setMobileOpen((current) => !current)
  }

  return (
    <div className="flex min-h-screen bg-background">
      <Sidebar
        collapsed={collapsed}
        onToggleCollapse={() => setCollapsed((current) => !current)}
        mobileOpen={mobileOpen}
        onCloseMobile={() => setMobileOpen(false)}
      />

      <div className="flex min-h-screen w-full min-w-0 flex-1 flex-col">
        <TenantModeBanner />
        <Header onMenuClick={handleMenuClick} />

        <main className="min-w-0 flex-1 px-4 py-5 sm:px-6 sm:py-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
