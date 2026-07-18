import { useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { ChevronDown, ChevronLeft } from 'lucide-react'
import { NAV_ITEMS } from '@/constants/nav'
import { APP_NAME } from '@/constants/app'
import { Tooltip } from '@/components/ui/Tooltip'
import { cn } from '@/utils/cn'
import logoAtmaJaya from '@/assets/images/logo-atmajaya.gif'

export interface SidebarProps {
  collapsed: boolean
  onToggleCollapse: () => void
  mobileOpen: boolean
  onCloseMobile: () => void
}

function isChildActive(pathname: string, children?: { path: string }[]): boolean {
  return children?.some((child) => pathname.startsWith(child.path)) ?? false
}

export function Sidebar({ collapsed, onToggleCollapse, mobileOpen, onCloseMobile }: SidebarProps) {
  const { pathname } = useLocation()
  const [openSubmenus, setOpenSubmenus] = useState<Set<string>>(new Set([NAV_ITEMS[1]?.label ?? '']))

  const toggleSubmenu = (label: string) => {
    setOpenSubmenus((current) => {
      const next = new Set(current)
      if (next.has(label)) {
        next.delete(label)
      } else {
        next.add(label)
      }
      return next
    })
  }

  return (
    <>
      {mobileOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/40 lg:hidden"
          onClick={onCloseMobile}
          aria-hidden
        />
      )}

      <aside
        className={cn(
          'fixed inset-y-0 left-0 z-50 flex flex-col border-r border-border bg-surface transition-all duration-200',
          'lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:translate-x-0',
          collapsed ? 'lg:w-[76px]' : 'lg:w-64',
          'w-64',
          mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        )}
      >
        <div className="flex h-16 shrink-0 items-center gap-2.5 border-b border-border px-4">
          <img src={logoAtmaJaya} alt={APP_NAME} className="size-9 shrink-0 object-contain" />
          {!collapsed && (
            <span className="truncate text-sm font-semibold tracking-tight text-ink-primary">{APP_NAME}</span>
          )}
        </div>

        <nav className="flex-1 overflow-y-auto px-2.5 py-3">
          <ul className="flex flex-col gap-1">
            {NAV_ITEMS.map((item) => {
              const Icon = item.icon
              const hasChildren = Boolean(item.children?.length)
              const active = pathname === item.path || (!hasChildren && pathname.startsWith(item.path))
              const childActive = isChildActive(pathname, item.children)
              const submenuOpen = openSubmenus.has(item.label)

              const linkClasses = cn(
                'group flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                active || childActive
                  ? 'bg-primary-50 text-primary dark:bg-primary-950'
                  : 'text-ink-secondary hover:bg-surface-hover hover:text-ink-primary',
              )

              const content = (
                <>
                  <Icon className="size-[18px] shrink-0" aria-hidden />
                  {!collapsed && <span className="flex-1 truncate text-left">{item.label}</span>}
                  {!collapsed && hasChildren && (
                    <ChevronDown className={cn('size-3.5 shrink-0 transition-transform', submenuOpen && 'rotate-180')} />
                  )}
                </>
              )

              return (
                <li key={item.label}>
                  {hasChildren ? (
                    <button type="button" onClick={() => toggleSubmenu(item.label)} className={linkClasses}>
                      {collapsed ? <Tooltip label={item.label}>{content}</Tooltip> : content}
                    </button>
                  ) : collapsed ? (
                    <Tooltip label={item.label}>
                      <Link to={item.path} onClick={onCloseMobile} className={linkClasses}>
                        {content}
                      </Link>
                    </Tooltip>
                  ) : (
                    <Link to={item.path} onClick={onCloseMobile} className={linkClasses}>
                      {content}
                    </Link>
                  )}

                  {hasChildren && !collapsed && submenuOpen && (
                    <ul className="mt-1 flex flex-col gap-0.5 border-l border-border pl-4">
                      {item.children?.map((child) => (
                        <li key={child.path}>
                          <Link
                            to={child.path}
                            onClick={onCloseMobile}
                            className={cn(
                              'block rounded-lg px-3 py-2 text-sm transition-colors',
                              pathname === child.path
                                ? 'font-medium text-primary'
                                : 'text-ink-secondary hover:bg-surface-hover hover:text-ink-primary',
                            )}
                          >
                            {child.label}
                          </Link>
                        </li>
                      ))}
                    </ul>
                  )}
                </li>
              )
            })}
          </ul>
        </nav>

        <div className="hidden shrink-0 border-t border-border p-2.5 lg:block">
          <button
            type="button"
            onClick={onToggleCollapse}
            className="flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm text-ink-secondary transition-colors hover:bg-surface-hover hover:text-ink-primary"
          >
            <ChevronLeft className={cn('size-4 transition-transform', collapsed && 'rotate-180')} />
            {!collapsed && 'Tutup Sidebar'}
          </button>
        </div>
      </aside>
    </>
  )
}
