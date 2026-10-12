import { Moon, Sun } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { useThemeControl } from '../../hooks/useThemeControl'

/** Icon toggle: shows the icon of the mode you'd switch *to*. */
export function ThemeToggle({ className }: { className?: string }) {
  const { m } = useLandingI18n()
  const { isDark, toggle } = useThemeControl()
  return (
    <button
      type="button"
      onClick={toggle}
      aria-label={isDark ? m.nav.toLight : m.nav.toDark}
      title={isDark ? m.nav.toLight : m.nav.toDark}
      className={cn(
        'relative inline-flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full text-lp-muted transition-colors duration-200 hover:bg-lp-sunken hover:text-lp-fg',
        className,
      )}
    >
      <Sun
        size={18}
        aria-hidden
        className={cn('absolute transition-[transform,opacity] duration-300', isDark ? 'rotate-0 scale-100 opacity-100' : '-rotate-90 scale-50 opacity-0')}
      />
      <Moon
        size={18}
        aria-hidden
        className={cn('absolute transition-[transform,opacity] duration-300', isDark ? 'rotate-90 scale-50 opacity-0' : 'rotate-0 scale-100 opacity-100')}
      />
    </button>
  )
}

/** Light / Dark pair for the mobile drawer and the footer. */
export function ThemeSegmented({ className, tone = 'default' }: { className?: string; tone?: 'default' | 'compact' }) {
  const { m } = useLandingI18n()
  const { theme, setTheme } = useThemeControl()
  const options = [
    { key: 'light' as const, label: m.nav.light, icon: Sun },
    { key: 'dark' as const, label: m.nav.dark, icon: Moon },
  ]
  return (
    <div role="group" aria-label={m.nav.theme} className={cn('inline-flex rounded-full border border-lp-line bg-lp-sunken p-1', className)}>
      {options.map(({ key, label, icon: Icon }) => {
        const selected = theme === key
        return (
          <button
            key={key}
            type="button"
            aria-pressed={selected}
            onClick={() => setTheme(key)}
            className={cn(
              'inline-flex items-center justify-center gap-1.5 rounded-full font-semibold transition-[background-color,color,box-shadow] duration-200',
              tone === 'compact' ? 'h-8 px-3 text-[12px]' : 'h-10 flex-1 px-4 text-[13.5px]',
              selected ? 'bg-lp-surface text-lp-fg shadow-lp-sm ring-1 ring-lp-line' : 'text-lp-muted hover:text-lp-fg',
            )}
          >
            <Icon size={tone === 'compact' ? 13 : 15} aria-hidden />
            {label}
          </button>
        )
      })}
    </div>
  )
}
