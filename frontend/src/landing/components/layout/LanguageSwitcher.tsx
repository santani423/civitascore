import { useEffect, useId, useRef, useState, type KeyboardEvent } from 'react'
import { Check, ChevronDown, Globe } from 'lucide-react'
import { cn } from '@/utils/cn'
import { LOCALES, useChangeLocale, useLandingI18n } from '../../i18n'

/** Navbar dropdown: Globe + short code, menu of native language names. */
export function LanguageMenu({ className }: { className?: string }) {
  const { m, locale } = useLandingI18n()
  const changeLocale = useChangeLocale()
  const [open, setOpen] = useState(false)
  const rootRef = useRef<HTMLDivElement>(null)
  const triggerRef = useRef<HTMLButtonElement>(null)
  const itemRefs = useRef<Array<HTMLButtonElement | null>>([])
  const menuId = useId()
  const current = LOCALES.find((l) => l.code === locale) ?? LOCALES[0]

  useEffect(() => {
    if (!open) return
    const selectedIndex = Math.max(0, LOCALES.findIndex((l) => l.code === locale))
    itemRefs.current[selectedIndex]?.focus()
    const onDown = (e: MouseEvent) => {
      if (!rootRef.current?.contains(e.target as Node)) setOpen(false)
    }
    document.addEventListener('mousedown', onDown)
    return () => document.removeEventListener('mousedown', onDown)
  }, [open, locale])

  const close = (restoreFocus: boolean) => {
    setOpen(false)
    if (restoreFocus) triggerRef.current?.focus()
  }

  const onMenuKeyDown = (e: KeyboardEvent<HTMLDivElement>) => {
    const index = itemRefs.current.findIndex((el) => el === document.activeElement)
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault()
      const delta = e.key === 'ArrowDown' ? 1 : -1
      itemRefs.current[(index + delta + LOCALES.length) % LOCALES.length]?.focus()
    } else if (e.key === 'Home' || e.key === 'End') {
      e.preventDefault()
      itemRefs.current[e.key === 'Home' ? 0 : LOCALES.length - 1]?.focus()
    } else if (e.key === 'Escape') {
      e.preventDefault()
      close(true)
    } else if (e.key === 'Tab') {
      close(false)
    }
  }

  return (
    <div ref={rootRef} className={cn('relative', className)}>
      <button
        ref={triggerRef}
        type="button"
        aria-haspopup="menu"
        aria-expanded={open}
        aria-controls={open ? menuId : undefined}
        aria-label={`${m.nav.chooseLanguage}: ${current.label}`}
        onClick={() => setOpen((o) => !o)}
        onKeyDown={(e) => {
          if (e.key === 'ArrowDown' && !open) {
            e.preventDefault()
            setOpen(true)
          }
        }}
        className="inline-flex h-10 items-center gap-1.5 rounded-full px-3 text-[13.5px] font-semibold text-lp-muted transition-colors hover:bg-lp-sunken hover:text-lp-fg"
      >
        <Globe size={16} aria-hidden />
        <span>{current.short}</span>
        <ChevronDown size={14} aria-hidden className={cn('transition-transform duration-200', open && 'rotate-180')} />
      </button>
      {open && (
        <div
          id={menuId}
          role="menu"
          aria-label={m.nav.language}
          onKeyDown={onMenuKeyDown}
          className="absolute right-0 top-full z-50 mt-2 w-52 animate-slide-in rounded-lp border border-lp-line bg-lp-elev p-1.5 shadow-lp-lg"
        >
          {LOCALES.map((l, i) => {
            const selected = l.code === locale
            return (
              <button
                key={l.code}
                ref={(el) => {
                  itemRefs.current[i] = el
                }}
                type="button"
                role="menuitemradio"
                aria-checked={selected}
                lang={l.code}
                tabIndex={-1}
                onClick={() => {
                  changeLocale(l.code)
                  close(true)
                }}
                className={cn(
                  'flex w-full items-center justify-between gap-3 rounded-lp-sm px-3 py-2.5 text-left text-[14px] transition-colors hover:bg-lp-sunken focus-visible:bg-lp-sunken',
                  selected ? 'font-semibold text-lp-fg' : 'text-lp-muted',
                )}
              >
                <span className="flex items-center gap-3">
                  <span className="w-7 text-[11px] font-semibold text-lp-subtle">{l.short}</span>
                  {l.label}
                </span>
                {selected && <Check size={16} aria-hidden className="text-lp-brand-text" />}
              </button>
            )
          })}
        </div>
      )}
    </div>
  )
}

/** Inline three-way switch for the mobile drawer and the footer. */
export function LanguageSegmented({ className, tone = 'default' }: { className?: string; tone?: 'default' | 'compact' }) {
  const { m, locale } = useLandingI18n()
  const changeLocale = useChangeLocale()
  return (
    <div role="group" aria-label={m.nav.language} className={cn('inline-flex rounded-full border border-lp-line bg-lp-sunken p-1', className)}>
      {LOCALES.map((l) => {
        const selected = l.code === locale
        return (
          <button
            key={l.code}
            type="button"
            lang={l.code}
            aria-pressed={selected}
            aria-label={l.label}
            onClick={() => changeLocale(l.code)}
            className={cn(
              'rounded-full font-semibold transition-[background-color,color,box-shadow] duration-200',
              tone === 'compact' ? 'h-8 px-3 text-[12px]' : 'h-10 flex-1 px-4 text-[13.5px]',
              selected ? 'bg-lp-surface text-lp-fg shadow-lp-sm ring-1 ring-lp-line' : 'text-lp-muted hover:text-lp-fg',
            )}
          >
            {tone === 'compact' ? l.short : l.label}
          </button>
        )
      })}
    </div>
  )
}
