import { useEffect, useId, useMemo, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { BookOpen, CalendarCheck, CornerDownLeft, FileText, Landmark, LifeBuoy, Newspaper, Search, User, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n, type TKey, type TFn } from '../../i18n'
import { useShell } from '../../hooks/useShell'
import { useDialog } from '../ui/Overlay'
import { Kbd } from '../ui/Display'
import { allCourses } from '../../data/academic'
import { faculties, programs } from '../../data/university'
import { news, events } from '../../data/campus'
import { documents, serviceCatalog } from '../../data/services'
import { lecturers } from '../../data/people'
import { allNavItems } from './nav'

type Group = 'page' | 'course' | 'faculty' | 'news' | 'event' | 'service' | 'lecturer' | 'document'

interface Entry {
  id: string
  group: Group
  title: string
  subtitle?: string
  to: string
  icon: LucideIcon
  keywords: string
}

function buildIndex(t: TFn): Entry[] {
  const e: Entry[] = []
  for (const n of allNavItems) e.push({ id: `p-${n.key}`, group: 'page', title: t(n.label), to: n.to, icon: n.icon, keywords: n.key })
  e.push({ id: 'p-settings', group: 'page', title: t('nav.settings'), to: '/one/settings', icon: FileText, keywords: 'settings preferences theme language' })
  e.push({ id: 'p-profile', group: 'page', title: t('nav.profile'), to: '/one/profile', icon: User, keywords: 'profile account' })
  for (const c of allCourses)
    e.push({ id: `c-${c.id}`, group: 'course', title: c.name, subtitle: `${c.code} · ${t('common.semesterN', { n: c.semester })}`, to: `/one/courses/${c.id}`, icon: BookOpen, keywords: c.code })
  for (const f of faculties) e.push({ id: `f-${f.id}`, group: 'faculty', title: f.name, subtitle: f.short, to: `/one/faculties/${f.id}`, icon: Landmark, keywords: f.departments.join(' ') })
  for (const p of programs)
    e.push({ id: `pr-${p.id}`, group: 'faculty', title: p.name, subtitle: `${t(`admission.levels.${p.level}`)} · ${p.degree}`, to: `/one/programs/${p.id}`, icon: Landmark, keywords: p.degree })
  for (const n of news) e.push({ id: n.id, group: 'news', title: n.title, subtitle: t(`newsCategory.${n.category}`), to: `/one/news/${n.id}`, icon: Newspaper, keywords: n.excerpt })
  for (const ev of events) e.push({ id: ev.id, group: 'event', title: ev.title, subtitle: ev.location, to: `/one/campus/events?e=${ev.id}`, icon: CalendarCheck, keywords: ev.organizer })
  for (const s of serviceCatalog)
    e.push({ id: `s-${s.key}`, group: 'service', title: t(`services.items.${s.key}.title` as TKey), subtitle: t(`services.items.${s.key}.desc` as TKey), to: '/one/services', icon: LifeBuoy, keywords: s.key })
  for (const l of lecturers) e.push({ id: l.id, group: 'lecturer', title: l.name, subtitle: l.expertise, to: '/one/faculties/' + l.facultyId, icon: User, keywords: l.email })
  for (const d of documents) e.push({ id: d.id, group: 'document', title: d.name, subtitle: d.type, to: '/one/profile?tab=documents', icon: FileText, keywords: '' })
  return e
}

const groupOrder: Group[] = ['page', 'course', 'faculty', 'news', 'event', 'service', 'lecturer', 'document']

export function CommandPalette() {
  const { t } = useI18n()
  const open = useShell((s) => s.paletteOpen)
  const setOpen = useShell((s) => s.setPalette)
  const navigate = useNavigate()
  const [query, setQuery] = useState('')
  const [active, setActive] = useState(0)
  const panelRef = useRef<HTMLDivElement>(null)
  const listRef = useRef<HTMLUListElement>(null)
  const listId = useId()

  const close = () => setOpen(false)
  useDialog(open, close, panelRef)

  // Global shortcut: ⌘K / Ctrl+K, and "/" when not typing.
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      const typing = /INPUT|TEXTAREA|SELECT/.test((e.target as HTMLElement)?.tagName ?? '')
      if ((e.key.toLowerCase() === 'k' && (e.metaKey || e.ctrlKey)) || (e.key === '/' && !typing)) {
        e.preventDefault()
        setOpen(!useShell.getState().paletteOpen)
      }
    }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [setOpen])

  useEffect(() => {
    if (open) {
      setQuery('')
      setActive(0)
    }
  }, [open])

  const index = useMemo(() => buildIndex(t), [t])

  const results = useMemo(() => {
    const q = query.trim().toLowerCase()
    const list = q
      ? index.filter((e) => `${e.title} ${e.subtitle ?? ''} ${e.keywords}`.toLowerCase().includes(q))
      : index.filter((e) => ['p-dashboard', 'p-schedule', 'p-assignments', 'p-finance', 'c-is301', 'c-is305', 's-letter', 'news-1'].includes(e.id))
    const grouped = groupOrder
      .map((g) => ({ group: g, items: list.filter((e) => e.group === g).slice(0, q ? 5 : 4) }))
      .filter((g) => g.items.length)
    return { grouped, flat: grouped.flatMap((g) => g.items) }
  }, [index, query])

  useEffect(() => setActive(0), [query])
  useEffect(() => {
    listRef.current?.querySelector(`[data-index="${active}"]`)?.scrollIntoView({ block: 'nearest' })
  }, [active])

  if (!open) return null

  const go = (entry?: Entry) => {
    if (!entry) return
    close()
    navigate(entry.to)
  }

  const optionId = (i: number) => `${listId}-opt-${i}`
  let running = -1

  return (
    <div className="fixed inset-0 z-[75] flex items-start justify-center px-3 pt-[8vh] sm:px-6 sm:pt-[12vh]">
      <div aria-hidden className="absolute inset-0 animate-fade-in bg-one-hero/40 backdrop-blur-[2px]" onClick={close} />
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-label={t('search.label')}
        className="relative w-full max-w-xl animate-rise-in overflow-hidden rounded-2xl border border-one-line bg-one-elev shadow-overlay"
      >
        <div className="flex items-center gap-3 border-b border-one-line px-4">
          <Search size={18} aria-hidden className="shrink-0 text-one-subtle" />
          <input
            data-autofocus
            role="combobox"
            aria-expanded="true"
            aria-controls={listId}
            aria-activedescendant={results.flat.length ? optionId(active) : undefined}
            aria-autocomplete="list"
            aria-label={t('search.label')}
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'ArrowDown') {
                e.preventDefault()
                setActive((a) => Math.min(a + 1, results.flat.length - 1))
              } else if (e.key === 'ArrowUp') {
                e.preventDefault()
                setActive((a) => Math.max(a - 1, 0))
              } else if (e.key === 'Enter') {
                e.preventDefault()
                go(results.flat[active])
              }
            }}
            placeholder={t('search.placeholder')}
            className="h-14 w-full bg-transparent text-[15px] text-one-fg placeholder:text-one-subtle focus:outline-none"
          />
          <Kbd>Esc</Kbd>
        </div>

        <ul ref={listRef} id={listId} role="listbox" aria-label={t('search.label')} className="max-h-[min(60vh,440px)] overflow-y-auto p-2">
          {results.flat.length === 0 && (
            <li className="px-4 py-10 text-center">
              <p className="text-[14px] font-medium text-one-fg">{t('states.emptySearch', { query })}</p>
              <p className="mt-1 text-[13px] text-one-subtle">{t('states.emptySearchBody')}</p>
            </li>
          )}
          {results.grouped.map((g) => (
            <li key={g.group} role="presentation">
              <p className="t-caption px-2.5 pb-1 pt-2.5 text-one-subtle" aria-hidden>
                {query ? t(`search.groups.${g.group}`) : g.group === 'page' ? t('search.suggestions') : t(`search.groups.${g.group}`)}
              </p>
              <ul role="group" aria-label={t(`search.groups.${g.group}`)}>
                {g.items.map((item) => {
                  running += 1
                  const i = running
                  const selected = i === active
                  return (
                    <li
                      key={item.id}
                      id={optionId(i)}
                      data-index={i}
                      role="option"
                      aria-selected={selected}
                      onMouseMove={() => setActive(i)}
                      onClick={() => go(item)}
                      className={cn('flex cursor-pointer items-center gap-3 rounded-xl px-2.5 py-2', selected && 'bg-one-sunken')}
                    >
                      <span className={cn('flex h-8 w-8 shrink-0 items-center justify-center rounded-lg', selected ? 'bg-one-royal/10 text-one-royal' : 'bg-one-sunken text-one-subtle')}>
                        <item.icon size={16} aria-hidden />
                      </span>
                      <span className="min-w-0 flex-1">
                        <span className="block truncate text-[14px] text-one-fg">{item.title}</span>
                        {item.subtitle && <span className="block truncate text-[12px] text-one-subtle">{item.subtitle}</span>}
                      </span>
                      {selected && <CornerDownLeft size={14} aria-hidden className="text-one-subtle" />}
                    </li>
                  )
                })}
              </ul>
            </li>
          ))}
        </ul>

        <div className="hidden items-center gap-4 border-t border-one-line px-4 py-2.5 text-[12px] text-one-subtle sm:flex">
          <span className="flex items-center gap-1.5">
            <Kbd>↑</Kbd>
            <Kbd>↓</Kbd> {t('search.hintNavigate')}
          </span>
          <span className="flex items-center gap-1.5">
            <Kbd>↵</Kbd> {t('search.hintSelect')}
          </span>
          <span className="flex items-center gap-1.5">
            <Kbd>Esc</Kbd> {t('search.hintClose')}
          </span>
        </div>
      </div>
    </div>
  )
}
