import { Link, useNavigate } from 'react-router-dom'
import {
  Bell,
  BookOpen,
  Building2,
  CircleQuestionMark,
  ClipboardList,
  FileText,
  HeartHandshake,
  Languages,
  LogOut,
  Monitor,
  Moon,
  Plus,
  Search,
  Settings,
  Sun,
  User,
  Wallet,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { LANGS, useI18n } from '../../i18n'
import { usePrefs, type ThemePref } from '../../store/prefs'
import { useApp } from '../../store/app'
import { useToast } from '../../store/toast'
import { useNotifications, useResolvedTheme, useShell } from '../../hooks/useShell'
import { student } from '../../data/people'
import { IconButton } from '../ui/Button'
import { Dropdown, DropdownItem, DropdownLabel, DropdownSeparator } from '../ui/Dropdown'
import { Avatar, Kbd } from '../ui/Display'
import { NotifIcon, relativeTime } from '../shared/notif'
import { LogoMark } from './Logo'

export function LanguageSwitcher({ className }: { className?: string }) {
  const { t, lang } = useI18n()
  const set = usePrefs((s) => s.set)
  return (
    <div role="radiogroup" aria-label={t('topbar.language')} className={cn('inline-flex items-center rounded-xl bg-one-sunken p-1', className)}>
      {LANGS.map((l) => (
        <button
          key={l.code}
          type="button"
          role="radio"
          aria-checked={lang === l.code}
          aria-label={l.label}
          lang={l.code}
          onClick={() => set('lang', l.code)}
          className={cn(
            'h-7 rounded-lg px-2.5 text-[12.5px] font-semibold transition-colors',
            lang === l.code ? 'bg-one-card text-one-fg shadow-soft' : 'text-one-subtle hover:text-one-fg',
          )}
        >
          {l.short}
        </button>
      ))}
    </div>
  )
}

const themeIcon = { light: Sun, dark: Moon, system: Monitor }

function ThemeMenu() {
  const { t } = useI18n()
  const pref = usePrefs((s) => s.theme)
  const set = usePrefs((s) => s.set)
  const resolved = useResolvedTheme()
  const Icon = pref === 'system' ? Monitor : resolved === 'dark' ? Moon : Sun
  return (
    <Dropdown
      label={t('topbar.theme')}
      width="w-44"
      trigger={(p) => (
        <IconButton {...p} icon={Icon} label={`${t('topbar.theme')}: ${t(`theme.${pref}`)}`} />
      )}
    >
      {(close) =>
        (['light', 'dark', 'system'] as ThemePref[]).map((th) => (
          <DropdownItem
            key={th}
            icon={themeIcon[th]}
            checked={pref === th}
            onSelect={() => {
              set('theme', th)
              close()
            }}
          >
            {t(`theme.${th}`)}
          </DropdownItem>
        ))
      }
    </Dropdown>
  )
}

function QuickActions() {
  const { t } = useI18n()
  const navigate = useNavigate()
  const actions = [
    { icon: ClipboardList, label: t('quick.submitAssignment'), to: '/one/assignments' },
    { icon: Wallet, label: t('quick.payTuition'), to: '/one/finance' },
    { icon: FileText, label: t('quick.requestLetter'), to: '/one/services' },
    { icon: Building2, label: t('quick.bookFacility'), to: '/one/campus/facilities' },
    { icon: HeartHandshake, label: t('quick.counseling'), to: '/one/services' },
  ]
  return (
    <Dropdown
      label={t('topbar.quickActions')}
      width="w-60"
      trigger={(p) => <IconButton {...p} icon={Plus} label={t('topbar.quickActions')} variant="secondary" />}
    >
      {(close) => (
        <>
          <DropdownLabel>{t('topbar.quickActions')}</DropdownLabel>
          {actions.map((a) => (
            <DropdownItem
              key={a.label}
              icon={a.icon}
              onSelect={() => {
                close()
                navigate(a.to)
              }}
            >
              {a.label}
            </DropdownItem>
          ))}
        </>
      )}
    </Dropdown>
  )
}

function NotificationsMenu() {
  const { t, lang } = useI18n()
  const { items, unread } = useNotifications()
  const markRead = useApp((s) => s.markRead)
  const markAllRead = useApp((s) => s.markAllRead)
  return (
    <Dropdown
      kind="dialog"
      label={t('topbar.notifications')}
      width="w-[min(92vw,380px)]"
      trigger={(p) => (
        <IconButton
          {...p}
          icon={Bell}
          badge={unread}
          label={`${t('topbar.notifications')}${unread ? ` — ${t('topbar.unreadCount', { count: unread })}` : ''}`}
        />
      )}
    >
      {(close) => (
        <div>
          <div className="flex items-center justify-between px-2.5 pb-2 pt-1.5">
            <p className="t-h3 text-one-fg">{t('topbar.notifications')}</p>
            {unread > 0 && (
              <button
                type="button"
                onClick={() => markAllRead(items.map((n) => n.id))}
                className="rounded text-[12.5px] font-medium text-one-royal hover:underline"
              >
                {t('notifications.markAllRead')}
              </button>
            )}
          </div>
          <ul className="max-h-[360px] overflow-y-auto">
            {items.slice(0, 5).map((n) => (
              <li key={n.id}>
                <Link
                  to={n.to}
                  onClick={() => {
                    markRead(n.id, true)
                    close()
                  }}
                  className="flex gap-3 rounded-xl px-2.5 py-2.5 hover:bg-one-sunken"
                >
                  <NotifIcon category={n.category} />
                  <span className="min-w-0 flex-1">
                    <span className={cn('block text-[13.5px] leading-snug', n.read ? 'text-one-muted' : 'font-semibold text-one-fg')}>{n.title}</span>
                    <span className="mt-0.5 block text-[12px] text-one-subtle">{relativeTime(n.at, lang)}</span>
                  </span>
                  {!n.read && <span aria-label={t('notifications.unread')} className="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-one-royal" />}
                </Link>
              </li>
            ))}
          </ul>
          <div className="mt-1 border-t border-one-line pt-1.5">
            <Link to="/one/notifications" onClick={close} className="block rounded-xl px-2.5 py-2 text-center text-[13px] font-medium text-one-royal hover:bg-one-sunken">
              {t('topbar.viewAllNotifications')}
            </Link>
          </div>
        </div>
      )}
    </Dropdown>
  )
}

function ProfileMenu() {
  const { t } = useI18n()
  const navigate = useNavigate()
  const toast = useToast()
  return (
    <Dropdown
      label={t('topbar.account')}
      width="w-64"
      trigger={(p) => (
        <button {...p} type="button" aria-label={t('topbar.account')} className="ml-1 rounded-full">
          <Avatar name={student.name} size="sm" />
        </button>
      )}
    >
      {(close) => (
        <>
          <div className="flex items-center gap-3 px-2.5 py-2">
            <Avatar name={student.name} />
            <div className="min-w-0">
              <p className="truncate text-[14px] font-semibold text-one-fg">{student.name}</p>
              <p className="truncate text-[12px] text-one-subtle">{student.email}</p>
            </div>
          </div>
          <DropdownSeparator />
          {[
            { icon: User, label: t('nav.profile'), to: '/one/profile' },
            { icon: Settings, label: t('nav.settings'), to: '/one/settings' },
            { icon: CircleQuestionMark, label: t('nav.help'), to: '/one/help' },
            { icon: BookOpen, label: t('nav.courses'), to: '/one/courses' },
          ].map((i) => (
            <DropdownItem
              key={i.to}
              icon={i.icon}
              onSelect={() => {
                close()
                navigate(i.to)
              }}
            >
              {i.label}
            </DropdownItem>
          ))}
          <DropdownSeparator />
          <DropdownItem
            icon={LogOut}
            danger
            onSelect={() => {
              close()
              toast(t('topbar.signOutDemo'), 'info')
            }}
          >
            {t('topbar.signOut')}
          </DropdownItem>
        </>
      )}
    </Dropdown>
  )
}

function MobileLanguage() {
  const { t, lang } = useI18n()
  const set = usePrefs((s) => s.set)
  return (
    <Dropdown label={t('topbar.language')} width="w-48" trigger={(p) => <IconButton {...p} icon={Languages} label={t('topbar.language')} />}>
      {(close) =>
        LANGS.map((l) => (
          <DropdownItem
            key={l.code}
            checked={lang === l.code}
            hint={l.short}
            onSelect={() => {
              set('lang', l.code)
              close()
            }}
          >
            {l.label}
          </DropdownItem>
        ))
      }
    </Dropdown>
  )
}

export function Topbar() {
  const { t } = useI18n()
  const setPalette = useShell((s) => s.setPalette)
  const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform)

  return (
    <header className="sticky top-0 z-40 border-b border-one-line bg-one-canvas/85 backdrop-blur-md supports-[backdrop-filter]:bg-one-canvas/70">
      <div className="mx-auto flex h-16 max-w-[1440px] items-center gap-2 px-4 sm:gap-3 md:px-6 lg:px-8">
        <Link to="/one" className="flex items-center gap-2 rounded-xl md:hidden" aria-label={t('app.name')}>
          <LogoMark size={32} />
        </Link>

        <button
          type="button"
          onClick={() => setPalette(true)}
          aria-label={t('topbar.search')}
          aria-keyshortcuts="Meta+K Control+K"
          className="group hidden h-10 w-full max-w-md items-center gap-3 rounded-xl border border-one-line bg-one-card px-3.5 text-left text-[14px] text-one-subtle shadow-soft transition-colors hover:border-one-line2 sm:flex"
        >
          <Search size={16} aria-hidden />
          <span className="flex-1 truncate">{t('topbar.searchPlaceholder')}</span>
          <span className="hidden items-center gap-0.5 lg:flex" aria-hidden>
            <Kbd>{isMac ? '⌘' : 'Ctrl'}</Kbd>
            <Kbd>K</Kbd>
          </span>
        </button>

        <div className="ml-auto flex items-center gap-1 sm:gap-1.5">
          <IconButton icon={Search} label={t('topbar.search')} onClick={() => setPalette(true)} className="sm:hidden" />
          <LanguageSwitcher className="hidden lg:inline-flex" />
          <div className="lg:hidden">
            <MobileLanguage />
          </div>
          <div className="hidden sm:block">
            <QuickActions />
          </div>
          <ThemeMenu />
          <NotificationsMenu />
          <ProfileMenu />
        </div>
      </div>
    </header>
  )
}
