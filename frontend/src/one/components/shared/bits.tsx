import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import {
  Atom,
  Bookmark,
  BookmarkCheck,
  CalendarDays,
  Clock,
  Globe,
  GraduationCap,
  Landmark,
  MapPin,
  Megaphone,
  Mic,
  Palette,
  Trophy,
  Briefcase,
  Users,
  Dumbbell,
  Presentation,
  Wrench,
  type LucideIcon,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../../i18n'
import { useApp } from '../../store/app'
import { daysUntil } from '../../lib/dates'
import type { Article, CampusEvent, EventCategory, NewsCategory } from '../../data/campus'
import { Badge } from '../ui/Badge'
import { Card } from '../ui/Card'
import { Cover, MetaItem } from '../ui/Display'
import type { UiTone } from '../../lib/tones'

export const newsIcon: Record<NewsCategory, LucideIcon> = {
  campus: Landmark,
  academic: GraduationCap,
  research: Atom,
  student: Users,
  international: Globe,
  career: Briefcase,
  events: Megaphone,
  achievement: Trophy,
}

export const eventIcon: Record<EventCategory, LucideIcon> = {
  seminar: Mic,
  workshop: Wrench,
  competition: Trophy,
  cultural: Palette,
  sports: Dumbbell,
  career: Presentation,
}

/** Relative due/countdown label, tone escalates as the date approaches. */
export function useDue() {
  const { t } = useI18n()
  return (iso: string): { label: string; tone: UiTone } => {
    const d = daysUntil(iso)
    if (d < 0) return { label: t('common.overdueBy', { count: -d }), tone: 'bad' }
    if (d === 0) return { label: t('common.today'), tone: 'bad' }
    if (d === 1) return { label: t('common.tomorrow'), tone: 'warn' }
    if (d <= 3) return { label: t('common.inDays', { count: d }), tone: 'warn' }
    return { label: t('common.inDays', { count: d }), tone: 'neutral' }
  }
}

export function SectionTitle({ title, action, id, className }: { title: ReactNode; action?: ReactNode; id?: string; className?: string }) {
  return (
    <div className={cn('mb-4 flex items-end justify-between gap-4', className)}>
      <h2 id={id} className="t-h2 text-one-fg">
        {title}
      </h2>
      {action}
    </div>
  )
}

export function BookmarkButton({ id, className, inverse }: { id: string; className?: string; inverse?: boolean }) {
  const { t } = useI18n()
  const on = useApp((s) => s.bookmarks.includes(id))
  const toggle = useApp((s) => s.toggle)
  const Icon = on ? BookmarkCheck : Bookmark
  return (
    <button
      type="button"
      aria-pressed={on}
      aria-label={on ? t('common.removeBookmark') : t('common.bookmark')}
      title={on ? t('common.removeBookmark') : t('common.bookmark')}
      onClick={(e) => {
        e.preventDefault()
        e.stopPropagation()
        toggle('bookmarks', id)
      }}
      className={cn(
        'flex h-9 w-9 items-center justify-center rounded-xl transition-colors',
        inverse ? 'bg-black/25 text-white backdrop-blur hover:bg-black/40' : 'text-one-subtle hover:bg-one-sunken hover:text-one-fg',
        on && !inverse && 'text-one-royal',
        className,
      )}
    >
      <Icon size={17} aria-hidden fill={on ? 'currentColor' : 'none'} />
    </button>
  )
}

export function NewsCard({ article, layout = 'card' }: { article: Article; layout?: 'card' | 'row' }) {
  const { t, fmt } = useI18n()
  const Icon = newsIcon[article.category]
  if (layout === 'row') {
    return (
      <Link to={`/one/news/${article.id}`} className="group flex gap-4 rounded-2xl p-1">
        <Cover tone={article.tone} icon={Icon} className="h-20 w-28 shrink-0 rounded-xl sm:h-24 sm:w-32" />
        <div className="min-w-0 py-0.5">
          <p className="t-caption text-one-royal">{t(`newsCategory.${article.category}`)}</p>
          <h3 className="mt-1 line-clamp-2 text-[14.5px] font-semibold leading-snug text-one-fg group-hover:text-one-royal">{article.title}</h3>
          <p className="mt-1 text-[12.5px] text-one-subtle">{fmt.date(article.date)}</p>
        </div>
      </Link>
    )
  }
  return (
    <Card as="article" interactive className="group relative flex flex-col overflow-hidden">
      <Cover tone={article.tone} icon={Icon} className="aspect-[16/9]">
        <span className="absolute left-3 top-3">
          <Badge tone="neutral" className="bg-white/90 text-one-ink ring-0">
            {t(`newsCategory.${article.category}`)}
          </Badge>
        </span>
        <span className="absolute right-3 top-3">
          <BookmarkButton id={article.id} inverse />
        </span>
      </Cover>
      <div className="flex flex-1 flex-col p-5">
        <h3 className="line-clamp-2 text-[16px] font-semibold leading-snug text-one-fg">
          <Link to={`/one/news/${article.id}`} className="after:absolute after:inset-0 group-hover:text-one-royal">
            {article.title}
          </Link>
        </h3>
        <p className="t-small mt-2 line-clamp-2 text-one-muted">{article.excerpt}</p>
        <div className="mt-auto flex items-center gap-3 pt-4 text-[12.5px] text-one-subtle">
          <span>{fmt.date(article.date)}</span>
          <span aria-hidden>·</span>
          <span>{t('news.readTime', { count: article.readTime })}</span>
        </div>
      </div>
    </Card>
  )
}

export function EventCard({ event, onView }: { event: CampusEvent; onView: () => void }) {
  const { t, fmt } = useI18n()
  const registered = useApp((s) => s.registered.includes(event.id))
  const d = new Date(event.date)
  return (
    <Card as="article" interactive className="flex flex-col overflow-hidden">
      <Cover tone={event.tone} icon={eventIcon[event.category]} className="aspect-[16/9]">
        <div className="absolute left-3 top-3 flex flex-col items-center rounded-xl bg-white/95 px-2.5 py-1.5 text-center shadow-sm">
          <span className="text-[10.5px] font-bold uppercase tracking-wider text-one-ink/60">{fmt.date(d, { month: 'short' })}</span>
          <span className="font-display text-[20px] font-bold leading-none text-one-ink">{d.getDate()}</span>
        </div>
        <span className="absolute right-3 top-3">
          <BookmarkButton id={event.id} inverse />
        </span>
      </Cover>
      <div className="flex flex-1 flex-col p-5">
        <div className="flex items-center gap-2">
          <Badge tone="teal">{t(`events.categories.${event.category}`)}</Badge>
          {registered && <Badge tone="ok" dot>{t('common.registered')}</Badge>}
        </div>
        <h3 className="mt-3 line-clamp-2 text-[16px] font-semibold leading-snug text-one-fg">{event.title}</h3>
        <div className="mt-3 space-y-1.5">
          <MetaItem icon={CalendarDays}>
            {fmt.date(d, { weekday: 'short', day: 'numeric', month: 'short' })} · {fmt.time(d)}
          </MetaItem>
          <MetaItem icon={MapPin}>{event.location}</MetaItem>
          <MetaItem icon={Users}>{event.organizer}</MetaItem>
        </div>
        <div className="mt-auto pt-5">
          <button
            type="button"
            onClick={onView}
            className="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-one-line text-[14px] font-medium text-one-fg transition-colors hover:border-one-line2 hover:bg-one-sunken"
          >
            {t('events.viewEvent')}
          </button>
        </div>
      </div>
    </Card>
  )
}

export function TimeMeta({ iso }: { iso: string }) {
  const { fmt } = useI18n()
  return <MetaItem icon={Clock}>{fmt.time(iso)}</MetaItem>
}
