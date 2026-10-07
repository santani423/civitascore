import { NavLink } from 'react-router-dom'
import { CircleQuestionMark, PanelLeftClose, PanelLeftOpen, Settings } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../../i18n'
import { usePrefs } from '../../store/prefs'
import { student } from '../../data/people'
import { Avatar, Tooltip } from '../ui/Display'
import { LogoMark, Wordmark } from './Logo'
import { navGroups, type NavItem } from './nav'

function SideLink({ item, expanded, onNavigate }: { item: NavItem; expanded: boolean; onNavigate?: () => void }) {
  const { t } = useI18n()
  const label = t(item.label)
  const link = (
    <NavLink
      to={item.to}
      end={item.end}
      onClick={onNavigate}
      aria-label={expanded ? undefined : label}
      className={({ isActive }) =>
        cn(
          'group relative flex h-10 items-center gap-3 rounded-xl text-[14px] font-medium transition-colors',
          expanded ? 'px-3' : 'w-10 justify-center',
          isActive ? 'bg-one-royal/10 text-one-royal' : 'text-one-muted hover:bg-one-sunken hover:text-one-fg',
        )
      }
    >
      {({ isActive }) => (
        <>
          {isActive && expanded && <span aria-hidden className="absolute -left-3 top-2 h-6 w-1 rounded-r-full bg-one-royal" />}
          <item.icon size={18} aria-hidden strokeWidth={isActive ? 2.25 : 2} className="shrink-0" />
          {expanded && <span className="truncate">{label}</span>}
        </>
      )}
    </NavLink>
  )
  return expanded ? link : <Tooltip content={label} side="right">{link}</Tooltip>
}

/** Full navigation tree — reused by the desktop sidebar and the mobile drawer. */
export function NavTree({ expanded, onNavigate }: { expanded: boolean; onNavigate?: () => void }) {
  const { t } = useI18n()
  return (
    <div className={cn('space-y-5', !expanded && 'flex flex-col items-center space-y-3')}>
      {navGroups.map((g) => (
        <div key={g.key} className={cn(!expanded && 'flex flex-col items-center')}>
          {expanded ? (
            <p className="t-caption mb-1.5 px-3 text-one-subtle">{t(g.label)}</p>
          ) : (
            <div aria-hidden className="mx-auto mb-3 h-px w-6 bg-one-line first:hidden" />
          )}
          <ul className={cn('space-y-0.5', !expanded && 'flex flex-col items-center')}>
            {g.items.map((item) => (
              <li key={item.key}>
                <SideLink item={item} expanded={expanded} onNavigate={onNavigate} />
              </li>
            ))}
          </ul>
        </div>
      ))}
    </div>
  )
}

export function SidebarFooter({ expanded, onNavigate }: { expanded: boolean; onNavigate?: () => void }) {
  const { t } = useI18n()
  const items: NavItem[] = [
    { key: 'help', label: 'nav.help', to: '/one/help', icon: CircleQuestionMark },
    { key: 'settings', label: 'nav.settings', to: '/one/settings', icon: Settings },
  ]
  return (
    <div className={cn('space-y-0.5 border-t border-one-line pt-3', !expanded && 'flex flex-col items-center')}>
      {items.map((item) => (
        <SideLink key={item.key} item={item} expanded={expanded} onNavigate={onNavigate} />
      ))}
      <NavLink
        to="/one/profile"
        onClick={onNavigate}
        aria-label={expanded ? undefined : t('nav.profile')}
        className={({ isActive }) =>
          cn(
            'mt-2 flex items-center gap-3 rounded-xl transition-colors',
            expanded ? 'p-2' : 'p-1',
            isActive ? 'bg-one-royal/10' : 'hover:bg-one-sunken',
          )
        }
      >
        <Avatar name={student.name} size="sm" />
        {expanded && (
          <span className="min-w-0">
            <span className="block truncate text-[13.5px] font-semibold text-one-fg">{student.name}</span>
            <span className="block truncate text-[12px] text-one-subtle tabular">{student.studentId}</span>
          </span>
        )}
      </NavLink>
    </div>
  )
}

export function Sidebar({ expanded, canToggle }: { expanded: boolean; canToggle: boolean }) {
  const { t } = useI18n()
  const collapsed = usePrefs((s) => s.sidebarCollapsed)
  const setPref = usePrefs((s) => s.set)

  return (
    <aside
      aria-label={t('nav.mainNav')}
      className={cn(
        'sticky top-0 hidden h-screen shrink-0 flex-col border-r border-one-line bg-one-card transition-[width] duration-200 md:flex',
        expanded ? 'w-[264px]' : 'w-[76px]',
      )}
    >
      <div className={cn('flex h-16 shrink-0 items-center', expanded ? 'justify-between px-5' : 'justify-center')}>
        <NavLink to="/one" className="flex items-center gap-2.5 rounded-xl" aria-label={t('app.name')}>
          <LogoMark size={34} />
          {expanded && <Wordmark />}
        </NavLink>
        {expanded && canToggle && (
          <button
            type="button"
            onClick={() => setPref('sidebarCollapsed', true)}
            aria-label={t('nav.collapse')}
            className="flex h-8 w-8 items-center justify-center rounded-lg text-one-subtle hover:bg-one-sunken hover:text-one-fg"
          >
            <PanelLeftClose size={17} aria-hidden />
          </button>
        )}
      </div>

      {!expanded && canToggle && collapsed && (
        <div className="flex justify-center pb-2">
          <Tooltip content={t('nav.expand')} side="right">
            <button
              type="button"
              onClick={() => setPref('sidebarCollapsed', false)}
              aria-label={t('nav.expand')}
              className="flex h-8 w-8 items-center justify-center rounded-lg text-one-subtle hover:bg-one-sunken hover:text-one-fg"
            >
              <PanelLeftOpen size={17} aria-hidden />
            </button>
          </Tooltip>
        </div>
      )}

      <nav className={cn('flex-1 overflow-y-auto py-3', expanded ? 'px-4' : 'px-2')}>
        <NavTree expanded={expanded} />
      </nav>

      <div className={cn('pb-4', expanded ? 'px-4' : 'px-2')}>
        <SidebarFooter expanded={expanded} />
      </div>
    </aside>
  )
}
