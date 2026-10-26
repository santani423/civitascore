import { Link } from 'react-router-dom'
import { ArrowRight, CalendarDays, ChevronRight, ClipboardCheck, KeyRound, LogIn, MessageSquare, Newspaper, QrCode, UserPlus } from 'lucide-react'
import { ROUTES } from '@/constants/routes'
import { PMB, to } from '@/pmb/routes'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { useInView } from '../../hooks/useInView'
import { usePmbNews, type NewsItem } from '../../lib/pmbNews'
import { PHOTOS } from '../../data/photos'
import { SECTION_IDS } from '../../paths'
import { useLandingUi } from '../../store/ui'
import { buttonClass } from '../ui/buttonClass'
import { Reveal, SectionHeader } from '../ui/Primitives'

const NEWS_LIMIT = 4

/**
 * Section F. News comes live from the PMB website's public endpoint and is
 * labelled as admissions news; when there is none (or the API can't be
 * reached) the section says so and points to the PMB website — it never
 * shows placeholder headlines.
 */
export function InfoSection() {
  const { m } = useLandingI18n()
  // Fetch only once the visitor scrolls near the section (one request per visit).
  const [ref, near] = useInView<HTMLDivElement>({ threshold: 0, rootMargin: '0px 0px 600px 0px' })
  const news = usePmbNews(near, NEWS_LIMIT)

  return (
    <section id={SECTION_IDS.info} aria-labelledby="info-title" className="lp-section bg-lp-canvas">
      <div ref={ref} className="lp-container">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeader id="info-title" eyebrow={m.info.eyebrow} title={m.info.title} description={m.info.description} />
          <Reveal className="shrink-0">
            <Link to={PMB.news} className="group inline-flex min-h-11 items-center gap-2 text-[0.9063rem] font-semibold text-lp-brand-text">
              {m.info.viewAll}
              <ArrowRight size={16} aria-hidden className="transition-transform group-hover:translate-x-0.5" />
            </Link>
          </Reveal>
        </div>

        <div className="mt-10" aria-busy={news.status === 'loading' || news.status === 'idle'}>
          {news.status === 'ready' && news.items.length > 0 ? (
            <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
              {news.items.map((item, i) => (
                <li key={item.id} className="flex">
                  <Reveal delay={i * 60} className="flex w-full">
                    <NewsCard item={item} />
                  </Reveal>
                </li>
              ))}
            </ul>
          ) : news.status === 'ready' || news.status === 'error' ? (
            <NewsFallback reason={news.status === 'error' ? 'error' : 'empty'} />
          ) : (
            <NewsSkeleton label={m.info.loading} />
          )}
        </div>

        <AccessGuide />
      </div>
    </section>
  )
}

function NewsCard({ item }: { item: NewsItem }) {
  const { m, fmt } = useLandingI18n()
  const date = item.published_at ? new Date(item.published_at) : null
  const href = to(PMB.newsDetail, { slug: item.slug })

  return (
    <article className="group relative flex w-full flex-col overflow-hidden rounded-lp border border-lp-line bg-lp-surface shadow-lp-sm transition-[box-shadow,transform,border-color] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-lg">
      <div aria-hidden className="relative flex h-28 items-end overflow-hidden bg-gradient-to-br from-lp-brand to-lp-accent px-5 pb-4">
        <span className="absolute -right-6 -top-10 h-32 w-32 rounded-full border-[16px] border-white/15" />
        <Newspaper size={30} className="relative text-white/90" />
      </div>
      <div className="flex flex-1 flex-col p-5">
        <p className="flex flex-wrap items-center gap-x-3 gap-y-1 text-[0.7813rem]">
          {date && !Number.isNaN(date.getTime()) && (
            <time dateTime={item.published_at ?? undefined} className="inline-flex items-center gap-1.5 text-lp-subtle">
              <CalendarDays size={13} aria-hidden />
              {fmt.date(date)}
            </time>
          )}
          <span className="font-semibold text-lp-brand-text">{item.category || m.info.sourceBadge}</span>
        </p>
        <h3 className="lp-h4 mt-3 line-clamp-3 text-lp-fg">
          {/* Stretched link: the whole card is clickable, the heading stays the accessible name. */}
          <Link to={href} className="after:absolute after:inset-0 after:content-[''] focus-visible:outline-none">
            {item.title}
          </Link>
        </h3>
        {item.excerpt && <p className="lp-small mt-2 line-clamp-3 text-lp-muted">{item.excerpt}</p>}
        <span aria-hidden className="mt-auto inline-flex items-center gap-1.5 pt-5 text-[0.8438rem] font-semibold text-lp-brand-text">
          {m.info.readMore}
          <ArrowRight size={15} className="transition-transform group-hover:translate-x-0.5" />
        </span>
      </div>
      {/* Visible focus ring for the stretched link. */}
      <span aria-hidden className="pointer-events-none absolute inset-0 rounded-lp ring-lp-ring group-has-[:focus-visible]:ring-2" />
    </article>
  )
}

function NewsSkeleton({ label }: { label: string }) {
  return (
    <div>
      <span className="sr-only">{label}</span>
      <ul aria-hidden className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        {Array.from({ length: NEWS_LIMIT }, (_, i) => (
          <li key={i} className={cn('overflow-hidden rounded-lp border border-lp-line bg-lp-surface', i > 1 && 'hidden lg:block', i === 1 && 'hidden sm:block')}>
            <div className="h-28 animate-pulse bg-lp-sunken" />
            <div className="space-y-3 p-5">
              <div className="h-3 w-24 animate-pulse rounded bg-lp-sunken" />
              <div className="h-4 w-full animate-pulse rounded bg-lp-sunken" />
              <div className="h-4 w-3/4 animate-pulse rounded bg-lp-sunken" />
              <div className="h-3 w-1/2 animate-pulse rounded bg-lp-sunken" />
            </div>
          </li>
        ))}
      </ul>
    </div>
  )
}

function NewsFallback({ reason }: { reason: 'empty' | 'error' }) {
  const { m } = useLandingI18n()
  return (
    <div className="flex flex-col gap-5 rounded-lp border border-dashed border-lp-strong bg-lp-surface p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
      <div className="flex items-start gap-4">
        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lp bg-lp-brand-soft text-lp-brand-text">
          <Newspaper size={20} aria-hidden />
        </span>
        <div>
          <p className="lp-h4 text-lp-fg">{reason === 'error' ? m.info.errorTitle : m.info.emptyTitle}</p>
          <p className="lp-small mt-1 max-w-xl text-lp-muted">{m.info.fallbackBody}</p>
        </div>
      </div>
      <Link to={PMB.home} className={buttonClass('secondary', 'md', 'shrink-0')}>
        {m.info.fallbackCta}
        <ArrowRight size={16} aria-hidden />
      </Link>
    </div>
  )
}

/** "Kehidupan kampus"-style band from the reference: photo, copy, and a list of real entry points. */
function AccessGuide() {
  const { m } = useLandingI18n()
  const openContact = useLandingUi((s) => s.openContact)
  const photo = PHOTOS.grotto
  const g = m.info.guide
  const rowClass =
    'group flex w-full items-center gap-3.5 rounded-lp-sm px-3 py-3 text-left text-[0.9063rem] font-medium text-lp-fg transition-colors hover:bg-lp-brand-soft hover:text-lp-brand-text'
  const iconClass = 'flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-lp-brand/25 text-lp-brand-text'

  const links = [
    { key: 'signIn', icon: LogIn, to: ROUTES.login },
    { key: 'forgot', icon: KeyRound, to: ROUTES.forgotPassword },
    { key: 'register', icon: UserPlus, to: PMB.register },
    { key: 'status', icon: ClipboardCheck, to: PMB.statusCheck },
  ] as const

  return (
    <Reveal className="mt-16 lg:mt-20">
      <div className="grid overflow-hidden rounded-lp-xl border border-lp-line bg-lp-surface shadow-lp-md lg:grid-cols-12">
        <div className="relative h-56 sm:h-72 lg:col-span-4 lg:h-auto">
          <img
            src={photo.src}
            srcSet={photo.srcSet}
            sizes="(min-width: 1024px) 33vw, 100vw"
            width={photo.width}
            height={photo.height}
            alt={m.photos.grotto}
            loading="lazy"
            decoding="async"
            className="absolute inset-0 h-full w-full object-cover"
          />
        </div>
        <div className="flex flex-col justify-center p-6 sm:p-8 lg:col-span-4 lg:p-10">
          <p className="lp-eyebrow text-lp-brand-text">{g.eyebrow}</p>
          <h3 className="lp-h3 mt-3 text-[1.5rem] text-lp-fg">{g.title}</h3>
          <p className="lp-body mt-4 text-lp-muted">{g.body}</p>
          <p className="mt-5 flex items-start gap-2.5 rounded-lp-sm bg-lp-sunken px-3.5 py-3 text-[0.8438rem] leading-relaxed text-lp-muted">
            <QrCode size={17} aria-hidden className="mt-0.5 shrink-0 text-lp-brand-text" />
            {g.examNote}
          </p>
        </div>
        <div className="border-t border-lp-line p-4 sm:p-6 lg:col-span-4 lg:border-l lg:border-t-0 lg:p-8">
          <ul className="space-y-1">
            {links.map(({ key, icon: Icon, to: path }) => (
              <li key={key}>
                <Link to={path} className={rowClass}>
                  <span aria-hidden className={iconClass}>
                    <Icon size={16} />
                  </span>
                  <span className="flex-1">{g.links[key]}</span>
                  <ChevronRight size={16} aria-hidden className="text-lp-subtle transition-transform group-hover:translate-x-0.5 group-hover:text-lp-brand-text" />
                </Link>
              </li>
            ))}
            <li>
              <button type="button" onClick={openContact} className={rowClass}>
                <span aria-hidden className={iconClass}>
                  <MessageSquare size={16} />
                </span>
                <span className="flex-1">{g.links.contact}</span>
                <ChevronRight size={16} aria-hidden className="text-lp-subtle transition-transform group-hover:translate-x-0.5 group-hover:text-lp-brand-text" />
              </button>
            </li>
          </ul>
        </div>
      </div>
    </Reveal>
  )
}
