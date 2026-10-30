import { Link, useLocation } from 'react-router-dom'
import { ArrowRight, CalendarDays, Clock, Info, MapPin, Tag, UsersRound, UserRoundCheck } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { backToList } from '../../hooks/useFilterParams'
import { activityStatus, findActivity, organizerOf, relatedActivities, type ResolvedActivity } from '../../data/student-affairs'
import { PILLARS } from '../../data/pillars'
import { LANDING_PATHS, ORG_LIST_PATHS, STUDENT_LIFE_IDS, detailPath } from '../../paths'
import { formatActivityDate, formatClock } from '../../lib/affairsFormat'
import { Breadcrumbs } from '../../components/layout/Breadcrumbs'
import { SectionLink } from '../../components/layout/SectionLink'
import { buttonClass } from '../../components/ui/buttonClass'
import { Reveal } from '../../components/ui/Primitives'
import { CategoryBadge, DemoBadge, StatusBadge } from '../../components/student-affairs/Badges'
import { CoverArt } from '../../components/student-affairs/Covers'
import { ActivityCard } from '../../components/student-affairs/Cards'
import { BackLink, BulletList, DetailSection, InfoItem, InfoList, NotFoundState, Paragraphs, RelatedSection } from '../../components/student-affairs/Detail'
import { PhotoGallery } from '../../components/student-affairs/PhotoGallery'
import { ACTIVITY_STYLE } from '../../components/student-affairs/styles'

/** /kegiatan/:slug — one template for every sample activity. */
export function ActivityDetailPage({ slug }: { slug: string }) {
  const { m } = useLandingI18n()
  const activity = findActivity(slug)
  const nf = m.affairs.notFound

  useDocumentMeta({
    title: fill(m.meta.pageTitle, { page: activity ? activity.title : nf.activity.title }),
    description: activity ? activity.excerpt : nf.activity.body,
    ogLocale: m.meta.ogLocale,
    path: detailPath(LANDING_PATHS.activities, slug),
    robots: activity ? undefined : 'noindex',
  })

  if (!activity) {
    return (
      <NotFoundState
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.activities, to: LANDING_PATHS.activities }, { label: nf.activity.title }]}
        title={nf.activity.title}
        body={nf.activity.body}
        backTo={LANDING_PATHS.activities}
        backLabel={m.affairs.activity.back}
      />
    )
  }
  return <ActivityDetail activity={activity} />
}

function ActivityDetail({ activity }: { activity: ResolvedActivity }) {
  const { m, intl, locale } = useLandingI18n()
  const { state } = useLocation()
  const a = m.affairs
  const t = a.activity
  const organizer = organizerOf(activity)
  const organizerPath = organizer ? detailPath(ORG_LIST_PATHS[organizer.kind], organizer.slug) : null
  const backTo = backToList(LANDING_PATHS.activities, state)
  const pillars = PILLARS.filter((p) => activity.pillars.includes(p.key))
  const gallery = activity.photos?.slice(1) ?? []
  const time = fill(t.timeRange, { start: formatClock(activity.start.time, locale), end: formatClock(activity.end.time, locale) })
  const related = relatedActivities(activity)

  return (
    <article aria-labelledby="activity-title">
      <header className="relative overflow-hidden border-b border-lp-line bg-lp-surface pb-12 pt-24 sm:pb-14 sm:pt-28 lg:pt-32">
        <div aria-hidden className="lp-grid-bg pointer-events-none absolute inset-x-0 top-0 h-96" />
        <div className="lp-container relative">
          <Breadcrumbs
            items={[
              { label: m.nav.home, to: LANDING_PATHS.home },
              { label: m.nav.activities, to: backTo },
              { label: activity.title, lang: 'id' },
            ]}
          />
          <div className="mt-8 grid gap-10 lg:grid-cols-12 lg:items-center lg:gap-12">
            <Reveal className="lg:col-span-7">
              <div className="flex flex-wrap items-center gap-2">
                <CategoryBadge label={a.categories.activity[activity.category]} className="bg-lp-brand-soft text-lp-brand-text ring-transparent" />
                <StatusBadge status={activityStatus(activity)} />
                <DemoBadge />
              </div>
              <h1 id="activity-title" lang="id" className="lp-h2 mt-5 text-lp-fg sm:text-[2.5rem]">
                {activity.title}
              </h1>
              <p lang="id" className="lp-lead mt-5 max-w-2xl text-lp-muted">
                {activity.excerpt}
              </p>
              <p className="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-[0.9375rem] font-medium text-lp-fg">
                <span className="inline-flex items-center gap-2">
                  <CalendarDays size={17} aria-hidden className="text-lp-brand-text" />
                  <time dateTime={activity.startsAt.toISOString()}>{formatActivityDate(activity, intl, 'long')}</time>
                </span>
                <span className="inline-flex items-center gap-2">
                  <Clock size={17} aria-hidden className="text-lp-brand-text" />
                  {time}
                </span>
              </p>
            </Reveal>
            <Reveal delay={80} className="lg:col-span-5">
              <CoverArt
                style={ACTIVITY_STYLE[activity.category]}
                photo={activity.photos?.[0]}
                size="hero"
                priority
                sizes="(min-width: 1024px) 40vw, 100vw"
                className="aspect-[16/10] rounded-lp-xl border border-lp-line shadow-lp-lg"
              />
            </Reveal>
          </div>
        </div>
      </header>

      <div className="lp-container grid gap-12 py-14 sm:py-16 lg:grid-cols-12 lg:gap-14 lg:py-20">
        <div className="space-y-14 lg:col-span-8">
          <DetailSection id="activity-about" title={t.about}>
            <Paragraphs items={activity.description} />
          </DetailSection>

          <DetailSection id="activity-goals" title={t.goalsTitle}>
            <div className="grid gap-6 sm:grid-cols-2">
              <div className="rounded-lp-lg border border-lp-line bg-lp-surface p-5 sm:p-6">
                <h3 className="lp-h4 text-lp-fg">{t.objectives}</h3>
                <BulletList items={activity.objectives} className="mt-4" />
              </div>
              <div className="rounded-lp-lg border border-lp-line bg-lp-surface p-5 sm:p-6">
                <h3 className="lp-h4 text-lp-fg">{t.benefits}</h3>
                <BulletList items={activity.benefits} className="mt-4" />
              </div>
            </div>
          </DetailSection>

          {activity.schedule && activity.schedule.length > 0 && (
            <DetailSection id="activity-schedule" title={t.schedule}>
              <ol className="relative space-y-1 border-l-2 border-lp-line pl-6">
                {activity.schedule.map((item, i) => (
                  <li key={`${item.day ?? ''}-${item.time}-${i}`} className="relative py-2.5">
                    <span aria-hidden className="absolute -left-[1.95rem] top-4 h-3.5 w-3.5 rounded-full border-[3px] border-lp-canvas bg-lp-brand" />
                    <p className="lp-tabular text-[0.8125rem] font-semibold text-lp-brand-text">
                      {item.day && (
                        <span lang="id" className="mr-2 text-lp-subtle">
                          {item.day}
                        </span>
                      )}
                      {fill(t.clock, { time: formatClock(item.time, locale) })}
                    </p>
                    <p lang="id" className="mt-0.5 text-[0.9375rem] font-medium text-lp-fg">
                      {item.title}
                    </p>
                    {item.detail && (
                      <p lang="id" className="lp-small mt-0.5 text-lp-muted">
                        {item.detail}
                      </p>
                    )}
                  </li>
                ))}
              </ol>
            </DetailSection>
          )}

          {gallery.length > 0 && (
            <DetailSection id="activity-gallery" title={t.gallery}>
              <PhotoGallery photos={gallery} caption={t.galleryNote} />
            </DetailSection>
          )}

          <BackLink to={backTo}>{t.back}</BackLink>
        </div>

        <aside aria-labelledby="activity-info" className="lg:col-span-4">
          <div className="rounded-lp-xl border border-lp-line bg-lp-surface p-6 shadow-lp-md lg:sticky lg:top-[calc(var(--lp-nav-h)+1.5rem)]">
            <h2 id="activity-info" className="lp-h4 text-lp-fg">
              {t.details}
            </h2>
            <InfoList className="mt-5">
              <InfoItem icon={CalendarDays} label={t.date}>
                <time dateTime={activity.startsAt.toISOString()}>{formatActivityDate(activity, intl, 'long')}</time>
              </InfoItem>
              <InfoItem icon={Clock} label={t.time}>
                {time}
              </InfoItem>
              <InfoItem icon={MapPin} label={t.venue}>
                <span lang="id">{activity.venue}</span>
              </InfoItem>
              {organizer && organizerPath && (
                <InfoItem icon={UsersRound} label={t.organizer}>
                  <Link to={organizerPath} lang="id" className="font-medium text-lp-fg underline decoration-lp-strong underline-offset-[0.2em] transition-colors hover:text-lp-brand-text">
                    {organizer.name}
                  </Link>
                </InfoItem>
              )}
              <InfoItem icon={UserRoundCheck} label={t.audience}>
                <span lang="id">{activity.audience}</span>
              </InfoItem>
              <InfoItem icon={Tag} label={t.pillars}>
                <ul className="mt-1 flex flex-wrap gap-1.5">
                  {pillars.map((pillar) => (
                    <li key={pillar.id}>
                      <SectionLink
                        section={pillar.id}
                        path={LANDING_PATHS.studentLife}
                        className="inline-flex min-h-9 items-center rounded-full bg-lp-brand-soft px-3 text-[0.8125rem] font-semibold text-lp-brand-text transition-colors hover:bg-lp-brand hover:text-lp-onbrand"
                      >
                        <span lang="id">{pillar.title}</span>
                      </SectionLink>
                    </li>
                  ))}
                </ul>
              </InfoItem>
            </InfoList>

            {organizer && organizerPath && (
              <Link to={organizerPath} className={buttonClass('primary', 'md', 'group mt-6 w-full')}>
                {t.organizerCta}
                <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
              </Link>
            )}
            <p className="mt-5 flex items-start gap-2.5 rounded-lp-sm bg-lp-sunken px-3.5 py-3 text-[0.8125rem] leading-relaxed text-lp-muted">
              <Info size={16} aria-hidden className="mt-0.5 shrink-0 text-lp-gold-text" />
              <span>
                {t.infoNote}{' '}
                <SectionLink section={STUDENT_LIFE_IDS.contact} path={LANDING_PATHS.studentLife} className="font-semibold text-lp-brand-text underline underline-offset-2">
                  {a.demo.contact}
                </SectionLink>
              </span>
            </p>
          </div>
        </aside>
      </div>

      {related.length > 0 && (
        <RelatedSection titleId="activity-related" title={t.related} allTo={LANDING_PATHS.activities} allLabel={t.relatedAll}>
          <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
            {related.map((item) => (
              <li key={item.slug} className="flex">
                <ActivityCard activity={item} />
              </li>
            ))}
          </ul>
        </RelatedSection>
      )}
    </article>
  )
}
