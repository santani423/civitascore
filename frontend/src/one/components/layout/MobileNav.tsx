import { NavLink } from 'react-router-dom'
import { Bell, BookOpen, CalendarDays, LayoutDashboard, Menu, X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../../i18n'
import { useNotifications, useShell } from '../../hooks/useShell'
import { Drawer } from '../ui/Overlay'
import { LogoMark, Wordmark } from './Logo'
import { NavTree, SidebarFooter } from './Sidebar'
import { LanguageSwitcher } from './Topbar'

/** Phone-only: thumb-reach bottom bar + full navigation drawer. */
export function MobileNav() {
  const { t } = useI18n()
  const { unread } = useNotifications()
  const drawerOpen = useShell((s) => s.drawerOpen)
  const setDrawer = useShell((s) => s.setDrawer)

  const tabs = [
    { to: '/one', label: t('nav.home'), icon: LayoutDashboard, end: true },
    { to: '/one/schedule', label: t('nav.schedule'), icon: CalendarDays },
    { to: '/one/courses', label: t('nav.courses'), icon: BookOpen },
    { to: '/one/notifications', label: t('nav.notifications'), icon: Bell, badge: unread },
  ]

  return (
    <>
      <nav
        aria-label={t('nav.mobileNav')}
        className="fixed inset-x-0 bottom-0 z-40 border-t border-one-line bg-one-card/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-md md:hidden"
      >
        <ul className="grid grid-cols-5">
          {tabs.map((tab) => (
            <li key={tab.to}>
              <NavLink
                to={tab.to}
                end={tab.end}
                className={({ isActive }) =>
                  cn('flex h-16 flex-col items-center justify-center gap-1 text-[11px] font-medium', isActive ? 'text-one-royal' : 'text-one-subtle')
                }
              >
                <span className="relative">
                  <tab.icon size={21} aria-hidden />
                  {!!tab.badge && (
                    <span aria-hidden className="absolute -right-1.5 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-one-bad px-1 text-[10px] font-semibold text-white ring-2 ring-one-card">
                      {tab.badge}
                    </span>
                  )}
                </span>
                <span className="max-w-full truncate px-1">{tab.label}</span>
              </NavLink>
            </li>
          ))}
          <li>
            <button
              type="button"
              onClick={() => setDrawer(true)}
              aria-expanded={drawerOpen}
              className="flex h-16 w-full flex-col items-center justify-center gap-1 text-[11px] font-medium text-one-subtle"
            >
              <Menu size={21} aria-hidden />
              {t('nav.menu')}
            </button>
          </li>
        </ul>
      </nav>

      <Drawer open={drawerOpen} onClose={() => setDrawer(false)} side="left" label={t('nav.mainNav')}>
        <div className="flex h-16 shrink-0 items-center justify-between border-b border-one-line px-4">
          <span className="flex items-center gap-2.5">
            <LogoMark size={32} />
            <Wordmark />
          </span>
          <button
            type="button"
            onClick={() => setDrawer(false)}
            aria-label={t('topbar.closeMenu')}
            className="flex h-9 w-9 items-center justify-center rounded-xl text-one-subtle hover:bg-one-sunken hover:text-one-fg"
          >
            <X size={18} aria-hidden />
          </button>
        </div>
        <div className="px-4 pt-4">
          <LanguageSwitcher className="w-full justify-between [&>button]:flex-1" />
        </div>
        <nav className="flex-1 overflow-y-auto px-4 py-4">
          <NavTree expanded onNavigate={() => setDrawer(false)} />
        </nav>
        <div className="px-4 pb-4">
          <SidebarFooter expanded onNavigate={() => setDrawer(false)} />
        </div>
      </Drawer>
    </>
  )
}
