import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Menu, Search, Bell, LogOut, Settings, UserRound, Circle } from 'lucide-react'
import { useAuthStore } from '@/stores/authStore'
import { ROUTES } from '@/constants/routes'
import { Avatar } from '@/components/ui/Avatar'
import { Dropdown, DropdownItem } from '@/components/ui/Dropdown'
import { ThemeToggle } from '@/components/layout/ThemeToggle'
import { NOTIFICATIONS } from '@/data/mock/notifications'
import { formatRelativeTime } from '@/utils/formatters'
import { cn } from '@/utils/cn'

export interface HeaderProps {
  pageTitle: string
  onMenuClick: () => void
}

export function Header({ pageTitle, onMenuClick }: HeaderProps) {
  const navigate = useNavigate()
  const session = useAuthStore((state) => state.session)
  const clearSession = useAuthStore((state) => state.clearSession)
  const [searchValue, setSearchValue] = useState('')

  const unreadCount = NOTIFICATIONS.filter((notification) => !notification.isRead).length

  const handleLogout = () => {
    clearSession()
    navigate(ROUTES.login, { replace: true })
  }

  return (
    <header className="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-border bg-surface/80 px-4 backdrop-blur sm:px-6">
      <button
        type="button"
        onClick={onMenuClick}
        aria-label="Buka atau tutup sidebar"
        className="flex size-9 shrink-0 items-center justify-center rounded-lg text-ink-secondary transition-colors hover:bg-surface-hover hover:text-ink-primary"
      >
        <Menu className="size-[18px]" />
      </button>

      <h2 className="hidden shrink-0 text-sm font-semibold text-ink-primary sm:block">{pageTitle}</h2>

      <div className="relative ml-auto flex-1 sm:ml-4 sm:max-w-sm">
        <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-ink-tertiary" />
        <input
          type="search"
          value={searchValue}
          onChange={(event) => setSearchValue(event.target.value)}
          placeholder="Cari mahasiswa, dosen, dokumen..."
          className="h-9 w-full rounded-lg border border-border-strong bg-surface pl-9 pr-3 text-sm text-ink-primary placeholder:text-ink-tertiary focus:outline-none focus:ring-2 focus:ring-primary"
        />
      </div>

      <ThemeToggle />

      <Dropdown
        align="right"
        trigger={
          <span className="relative flex size-9 items-center justify-center rounded-lg text-ink-secondary transition-colors hover:bg-surface-hover hover:text-ink-primary">
            <Bell className="size-[18px]" />
            {unreadCount > 0 && (
              <span className="absolute right-1.5 top-1.5 flex size-2 rounded-full bg-danger" />
            )}
          </span>
        }
        className="w-80"
      >
        <div className="px-3 py-2 text-sm font-semibold text-ink-primary">Notifikasi</div>
        <div className="max-h-80 overflow-y-auto">
          {NOTIFICATIONS.map((notification) => (
            <div key={notification.id} className="flex items-start gap-2.5 rounded-lg px-3 py-2.5 hover:bg-surface-hover">
              <Circle
                className={cn(
                  'mt-1 size-2 shrink-0 fill-current',
                  notification.isRead ? 'text-ink-tertiary' : 'text-primary',
                )}
              />
              <div className="flex-1">
                <p className="text-sm font-medium text-ink-primary">{notification.title}</p>
                <p className="text-xs text-ink-secondary">{notification.description}</p>
                <p className="mt-0.5 text-xs text-ink-tertiary">{formatRelativeTime(notification.timestamp)}</p>
              </div>
            </div>
          ))}
        </div>
      </Dropdown>

      <Dropdown
        align="right"
        trigger={
          <span className="flex items-center gap-2 rounded-lg py-1 pl-1 pr-2 transition-colors hover:bg-surface-hover">
            <Avatar name={session?.user.name ?? 'Pengguna'} size="sm" />
            <span className="hidden text-left sm:block">
              <span className="block text-sm font-medium leading-tight text-ink-primary">{session?.user.name}</span>
              <span className="block text-xs leading-tight text-ink-secondary">{session?.user.role}</span>
            </span>
          </span>
        }
      >
        <DropdownItem onClick={() => navigate(ROUTES.pengaturan.security)}>
          <UserRound className="size-4" /> Profil Saya
        </DropdownItem>
        <DropdownItem onClick={() => navigate(ROUTES.pengaturan.systemSettings)}>
          <Settings className="size-4" /> Pengaturan
        </DropdownItem>
        <div className="my-1 border-t border-border" />
        <DropdownItem onClick={handleLogout} className="text-danger hover:bg-red-50 dark:hover:bg-red-950">
          <LogOut className="size-4" /> Keluar
        </DropdownItem>
      </Dropdown>
    </header>
  )
}
