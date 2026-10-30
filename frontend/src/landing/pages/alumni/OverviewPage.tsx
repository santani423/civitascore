import { Link, useNavigate } from 'react-router-dom'
import { ArrowRight, Briefcase, CalendarClock, Earth, HandCoins, HeartHandshake, Quote, Rocket, School, UserRoundCheck, UsersRound, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { useCountdown } from '../../hooks/useAlumniDemo'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { ALUMNI_STATS, ALUMNI_STORIES, CONTRIBUTION_PROGRAMS, COUNTRY_BREAKDOWN, INDUSTRY_BREAKDOWN, RESOLVED_OPPORTUNITIES, SPOTLIGHT_ALUMNI, findAlumnus, upcomingEvents } from '../../data/alumni'
import { PHOTOS } from '../../data/photos'
import { LANDING_PATHS } from '../../paths'
import { formatEventDate, formatRupiahBillions, shareOf } from '../../lib/alumniFormat'
import { eventPath, storyPath } from '../../lib/alumniPaths'
import { Breadcrumbs } from '../../components/layout/Breadcrumbs'
import { buttonClass } from '../../components/ui/buttonClass'
import { CtaBanner } from '../../components/ui/CtaBanner'
import { Reveal } from '../../components/ui/Primitives'
import { BarList } from '../../components/alumni/Charts'
import { EventCard, EventRow } from '../../components/alumni/Events'
import { OpportunityRow } from '../../components/alumni/Opportunities'
import { Portrait } from '../../components/alumni/Portrait'
import { ProfileCard } from '../../components/alumni/ProfileCard'
import { AlumniDemoNotice, CountUp, PreviewHeader } from '../../components/alumni/Shared'

const HERO_PEOPLE = ['theresia-anindya-putri', 'gregorius-bima-santoso', 'maria-clarissa-wibisono', 'stefanus-aditya-nugroho', 'aulia-rahmawati'].map((slug) => findAlumnus(slug)!)
/** Staggered like people standing together; widths are shares of the row. */
const HERO_OFFSETS = ['translate-y-3', '-translate-y-1', 'translate-y-1', '-translate-y-2', 'translate-y-4']

/** The real campus photo with the community standing in front of it, and the next event on top. */
function HeroVisual() {
  const { m, intl } = useLandingI18n()
  const photo = PHOTOS.campusTower
  const countdown = useCountdown()
  const next = upcomingEvents()[0]

  return (
    <div className="relative pb-10">
      <figure className="relative aspect-[5/4] overflow-hidden rounded-lp-xl border border-lp-band-line bg-lp-band-elev shadow-lp-xl">
        <img
          src={photo.src}
          srcSet={photo.srcSet}
          sizes="(min-width: 1024px) 45vw, 100vw"
          width={photo.width}
          height={photo.height}
          alt={m.photos.campusTower}
          fetchPriority="high"
          decoding="async"
          className="h-full w-full object-cover object-[50%_22%]"
        />
        <div aria-hidden className="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-lp-band/85 to-transparent" />
      </figure>

      <div aria-hidden className="absolute inset-x-[4%] bottom-0 flex items-end justify-center gap-[2.5%]">
        {HERO_PEOPLE.map((person, i) => (
          <Portrait key={person.slug} person={person} className={cn('aspect-[3/4] w-[18%] rounded-b-lp rounded-t-full shadow-lp-lg ring-[3px] ring-lp-band sm:ring-4', HERO_OFFSETS[i])} />
        ))}
      </div>

      <Link
        to={eventPath(next.slug)}
        className="group absolute left-3 top-3 flex max-w-[calc(100%-1.5rem)] items-center gap-3 rounded-lp bg-lp-surface/95 p-3 pr-4 shadow-lp-lg backdrop-blur transition-transform duration-200 hover:-translate-y-0.5 sm:left-4 sm:top-4 sm:max-w-[22rem]"
      >
        <span aria-hidden className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lp-sm bg-lp-brand-soft text-lp-brand-text">
          <CalendarClock size={19} />
        </span>
        <span className="min-w-0">
          <span className="block text-[0.6875rem] font-semibold uppercase tracking-[0.1em] text-lp-brand-text">
            {m.alumni.overview.nextEvent} · {countdown(next.startsAt)}
          </span>
          <span lang="id" className="mt-0.5 block truncate text-[0.875rem] font-semibold text-lp-fg">
            {next.title}
          </span>
          <span className="mt-0.5 block truncate text-[0.75rem] text-lp-muted">{formatEventDate(next, intl)}</span>
        </span>
      </Link>
    </div>
  )
}

function Hero() {
  const { m, fmt } = useLandingI18n()
  const o = m.alumni.overview
  return (
    <section aria-labelledby="alumni-title" className="lp-on-band relative isolate overflow-hidden bg-lp-band">
      <div aria-hidden data-lp-decor className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
      <div aria-hidden data-lp-decor className="pointer-events-none absolute -left-40 top-1/3 -z-10 h-[28rem] w-[28rem] rounded-full bg-lp-accent/[0.1] blur-3xl" />
      <div className="lp-container grid gap-10 pb-24 pt-5 sm:pb-28 lg:grid-cols-12 lg:items-center lg:gap-14 lg:pb-32 lg:pt-7">
        <div className="lg:col-span-6">
          <Breadcrumbs tone="band" items={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.alumni }]} />
          <Reveal>
            <p className="lp-eyebrow mt-8 text-lp-accent">{o.eyebrow}</p>
            <h1 id="alumni-title" className="lp-display mt-4 text-lp-band-fg">
              {o.titleLead} <span className="text-lp-accent">{o.titleAccent}</span>
            </h1>
            <p className="lp-lead mt-6 max-w-xl text-lp-band-muted">{o.lead}</p>
            <div className="mt-9 flex flex-col gap-3 min-[420px]:flex-row min-[420px]:flex-wrap">
              <Link to={LANDING_PATHS.alumniDirectory} className={buttonClass('primary', 'lg', 'group')}>
                {o.primaryCta}
                <ArrowRight size={18} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
              </Link>
              <Link to={LANDING_PATHS.alumniEvents} className={buttonClass('bandOutline', 'lg')}>
                {o.secondaryCta}
              </Link>
            </div>
            <p className="mt-7 text-[0.875rem] text-lp-band-muted">{fill(o.joined, { count: fmt.number(ALUMNI_STATS.registered) })}</p>
          </Reveal>
        </div>
        <Reveal delay={80} className="lg:col-span-6">
          <HeroVisual />
        </Reveal>
      </div>
    </section>
  )
}

function StatTile({ icon: Icon, label, hint, value, className }: { icon: LucideIcon; label: string; hint: string; value: number; className?: string }) {
  const { fmt } = useLandingI18n()
  return (
    <div className={cn('flex flex-col bg-lp-surface p-4 sm:p-5', className)}>
      <span aria-hidden className="flex h-9 w-9 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
        <Icon size={17} />
      </span>
      <dd className="order-2 mt-3 font-display text-[1.75rem] font-extrabold leading-none tracking-tight text-lp-fg sm:text-[2rem]">
        <CountUp value={value} format={(n) => fmt.number(n)} />
      </dd>
      <dt className="order-3 mt-2 text-[0.875rem] font-semibold leading-snug text-lp-fg">{label}</dt>
      <p className="order-4 mt-1 text-[0.8125rem] leading-snug text-lp-muted">{hint}</p>
    </div>
  )
}

function Stats() {
  const { m, fmt } = useLandingI18n()
  const s = m.alumni.overview.stats
  const { total, registered, employed, entrepreneurs, countries } = ALUMNI_STATS
  const pct = (part: number, whole: number) => fmt.percent(shareOf(part, whole), 0)
  return (
    <section aria-label={m.alumni.overview.statsTitle} className="relative z-10 -mt-14 sm:-mt-16">
      <div className="lp-container">
        <Reveal>
          <dl className="grid grid-cols-2 gap-px overflow-hidden rounded-lp-xl border border-lp-line bg-lp-line shadow-lp-lg md:grid-cols-5">
            <StatTile icon={UsersRound} label={s.total} hint={s.totalHint} value={total} />
            <StatTile icon={UserRoundCheck} label={s.registered} hint={fill(s.registeredHint, { pct: pct(registered, total) })} value={registered} />
            <StatTile icon={Briefcase} label={s.employed} hint={fill(s.employedHint, { pct: pct(employed, registered) })} value={employed} />
            <StatTile icon={Rocket} label={s.entrepreneurs} hint={fill(s.entrepreneursHint, { pct: pct(entrepreneurs, registered) })} value={entrepreneurs} />
            <StatTile icon={Earth} label={s.countries} hint={s.countriesHint} value={countries} className="col-span-2 md:col-span-1" />
          </dl>
        </Reveal>
      </div>
    </section>
  )
}

function Spotlight() {
  const { m } = useLandingI18n()
  const o = m.alumni.overview
  return (
    <section aria-labelledby="alumni-spotlight" className="lp-section pb-0">
      <div className="lp-container">
        <PreviewHeader id="alumni-spotlight" eyebrow={o.spotlightEyebrow} title={o.spotlightTitle} lead={o.spotlightLead} to={LANDING_PATHS.alumniDirectory} linkLabel={o.spotlightAll} />
        <ul className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {SPOTLIGHT_ALUMNI.map((person) => (
            <li key={person.slug} className="flex">
              <ProfileCard person={person} />
            </li>
          ))}
        </ul>
      </div>
    </section>
  )
}

function UpcomingEvents() {
  const { m } = useLandingI18n()
  const navigate = useNavigate()
  const o = m.alumni.overview
  const [featured, ...rest] = upcomingEvents()
  return (
    <section aria-labelledby="alumni-events" className="lp-section pb-0">
      <div className="lp-container">
        <PreviewHeader id="alumni-events" eyebrow={o.eventsEyebrow} title={o.eventsTitle} to={LANDING_PATHS.alumniEvents} linkLabel={o.eventsAll} />
        <div className="mt-8 grid gap-5 lg:grid-cols-12 lg:gap-6">
          <div className="flex lg:col-span-5">
            <EventCard event={featured} featured onOpen={() => navigate(eventPath(featured.slug))} />
          </div>
          <ul className="space-y-3 lg:col-span-7 lg:space-y-4">
            {rest.slice(0, 4).map((event) => (
              <li key={event.slug}>
                <EventRow event={event} />
              </li>
            ))}
          </ul>
        </div>
      </div>
    </section>
  )
}

function Reach() {
  const { m, fmt } = useLandingI18n()
  const o = m.alumni.overview
  const working = ALUMNI_STATS.employed + ALUMNI_STATS.entrepreneurs
  return (
    <section aria-labelledby="alumni-reach" className="lp-section lp-warm mt-16 sm:mt-20 lg:mt-24">
      <div className="lp-container grid gap-10 lg:grid-cols-12 lg:gap-12">
        <Reveal className="lg:col-span-4">
          <p className="lp-eyebrow text-lp-brand-text">{o.reachEyebrow}</p>
          <h2 id="alumni-reach" className="lp-h2 mt-3 text-lp-fg">
            {o.reachTitle}
          </h2>
          <p className="lp-body mt-4 text-lp-muted">{o.reachLead}</p>
          <p className="mt-8 font-display text-[3.5rem] font-extrabold leading-none tracking-tight text-lp-fg">{fmt.percent(shareOf(working, ALUMNI_STATS.registered), 0)}</p>
          <p className="lp-small mt-2 max-w-xs text-lp-muted">{o.reachFigure}</p>
        </Reveal>
        <div className="grid gap-5 md:grid-cols-2 lg:col-span-8">
          <BarList title={o.industryTitle} caption={o.industrySub} format={(n) => fmt.number(n)} data={INDUSTRY_BREAKDOWN.map((row) => ({ label: m.alumni.industries[row.key], value: row.value }))} />
          <BarList title={o.countryTitle} caption={o.countrySub} lang="id" format={(n) => fmt.number(n)} data={COUNTRY_BREAKDOWN.map((row) => ({ label: row.country, value: row.value }))} />
        </div>
      </div>
    </section>
  )
}

function StoryFeature() {
  const { m } = useLandingI18n()
  const o = m.alumni.overview
  const story = ALUMNI_STORIES[0]
  const person = findAlumnus(story.alumnus)!
  return (
    <section aria-labelledby="alumni-story" className="lp-on-band relative isolate overflow-hidden bg-lp-band">
      <div aria-hidden data-lp-decor className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
      <div className="lp-container grid items-center gap-10 py-16 sm:py-20 lg:grid-cols-12 lg:gap-14 lg:py-24">
        <Reveal className="mx-auto w-full max-w-[17rem] lg:col-span-4 lg:max-w-none">
          <Portrait person={person} className="aspect-[4/5] rounded-b-lp-lg rounded-t-full shadow-lp-xl ring-1 ring-lp-band-line" />
        </Reveal>
        <Reveal delay={80} className="lg:col-span-8">
          <p className="lp-eyebrow text-lp-accent">{o.storyEyebrow}</p>
          <Quote aria-hidden size={38} className="mt-5 text-lp-accent" />
          <h2 id="alumni-story" lang="id" className="lp-serif mt-3 text-[1.75rem] leading-[1.2] text-lp-band-fg sm:text-[2.5rem]">
            {story.quote}
          </h2>
          <p className="mt-6 text-[0.9375rem] text-lp-band-muted">
            <span lang="id" className="font-semibold text-lp-band-fg">
              {person.name}
            </span>{' '}
            · <span lang="id">{person.position}</span>
          </p>
          <div className="mt-8 flex flex-col gap-3 min-[420px]:flex-row min-[420px]:flex-wrap">
            <Link to={storyPath(story.slug)} className={buttonClass('primary', 'md', 'group')}>
              {o.storyCta}
              <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
            </Link>
            <Link to={LANDING_PATHS.alumniStories} className={buttonClass('bandOutline', 'md')}>
              {o.storyAll}
            </Link>
          </div>
        </Reveal>
      </div>
    </section>
  )
}

function CareersAndGiving() {
  const { m, fmt, intl } = useLandingI18n()
  const o = m.alumni.overview
  const figure = (key: string) => CONTRIBUTION_PROGRAMS.find((p) => p.key === key)!.value
  const tiles: Array<{ icon: LucideIcon; value: string; label: string }> = [
    { icon: HandCoins, value: formatRupiahBillions(figure('donation'), intl), label: m.alumni.contributions.programs.donation },
    { icon: School, value: fmt.number(figure('scholarship')), label: o.contribScholars },
    { icon: HeartHandshake, value: fmt.number(figure('mentoring')), label: o.contribMentors },
  ]
  return (
    <section aria-label={o.careerEyebrow} className="lp-section">
      <div className="lp-container grid gap-12 lg:grid-cols-12 lg:gap-14">
        <div className="lg:col-span-7">
          <PreviewHeader id="alumni-careers" eyebrow={o.careerEyebrow} title={o.careerTitle} to={LANDING_PATHS.alumniCareers} linkLabel={o.careerAll} />
          <ul className="mt-5">
            {RESOLVED_OPPORTUNITIES.slice(0, 4).map((item) => (
              <li key={item.slug}>
                <OpportunityRow item={item} />
              </li>
            ))}
          </ul>
        </div>
        <div className="lg:col-span-5">
          <Reveal className="rounded-lp-xl border border-lp-line bg-lp-surface p-6 shadow-lp-md sm:p-8">
            <p className="lp-eyebrow text-lp-brand-text">{o.contribEyebrow}</p>
            <h2 className="lp-h3 mt-3 text-lp-fg">{o.contribTitle}</h2>
            <p className="lp-small mt-2 text-lp-muted">{o.contribLead}</p>
            <ul className="mt-6 divide-y divide-lp-line">
              {tiles.map((tile) => (
                <li key={tile.label} className="flex items-center gap-4 py-3.5">
                  <span aria-hidden className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
                    <tile.icon size={18} />
                  </span>
                  <span className="min-w-0 flex-1 text-[0.875rem] text-lp-muted">{tile.label}</span>
                  <span className="font-display text-[1.25rem] font-extrabold text-lp-fg">{tile.value}</span>
                </li>
              ))}
            </ul>
            <Link to={LANDING_PATHS.alumniContributions} className={buttonClass('secondary', 'md', 'group mt-6 w-full')}>
              {o.contribAll}
              <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
            </Link>
          </Reveal>
        </div>
      </div>
    </section>
  )
}

/** /komunitas-alumni — the hero, the five headline figures, and a preview of every other page in the section. */
export function OverviewPage() {
  const { m } = useLandingI18n()
  const o = m.alumni.overview
  useDocumentMeta({ title: fill(m.meta.pageTitle, { page: o.metaTitle }), description: o.metaDescription, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.alumni })

  return (
    <>
      <Hero />
      <Stats />
      <div className="lp-container mt-6">
        <AlumniDemoNotice />
      </div>
      <Spotlight />
      <UpcomingEvents />
      <Reach />
      <StoryFeature />
      <CareersAndGiving />
      <CtaBanner titleId="alumni-cta" title={o.ctaTitle} description={o.ctaBody}>
        <Link to={LANDING_PATHS.alumniContributions} className={buttonClass('band', 'lg')}>
          {o.ctaPrimary}
        </Link>
        <Link to={LANDING_PATHS.alumniDirectory} className={buttonClass('bandOutline', 'lg')}>
          {o.ctaSecondary}
        </Link>
      </CtaBanner>
    </>
  )
}
