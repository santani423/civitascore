import { Link } from 'react-router-dom'
import { ArrowRight, Quote } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { useFilterParams } from '../../hooks/useFilterParams'
import { ALUMNI_STORIES, STORY_THEMES, findAlumnus } from '../../data/alumni'
import { LANDING_PATHS } from '../../paths'
import { storyPath } from '../../lib/alumniPaths'
import { Breadcrumbs } from '../../components/layout/Breadcrumbs'
import { buttonClass } from '../../components/ui/buttonClass'
import { Reveal } from '../../components/ui/Primitives'
import { ChipGroup } from '../../components/student-affairs/Filters'
import { EmptyResults } from '../../components/student-affairs/CardGrid'
import { Portrait } from '../../components/alumni/Portrait'
import { AlumniDemoNotice } from '../../components/alumni/Shared'
import { StoryByline, StoryCard } from '../../components/alumni/Stories'

const FILTER_KEYS = ['tema'] as const

/** /komunitas-alumni/kisah — laid out like a university magazine rather than a list: masthead, cover story, then the issue. */
export function StoriesPage() {
  const { m, intl } = useLandingI18n()
  const s = m.alumni.stories
  useDocumentMeta({ title: fill(m.meta.pageTitle, { page: s.metaTitle }), description: s.metaDescription, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.alumniStories })

  const filters = useFilterParams(FILTER_KEYS, { tema: STORY_THEMES })
  const { tema } = filters.values
  const [cover, ...rest] = ALUMNI_STORIES
  const coverPerson = findAlumnus(cover.alumnus)!
  const themed = tema ? ALUMNI_STORIES.filter((story) => story.theme === tema) : rest
  const quoted = ALUMNI_STORIES[1]
  const quotedPerson = findAlumnus(quoted.alumnus)!
  const edition = new Intl.DateTimeFormat(intl, { month: 'long', year: 'numeric' }).format(new Date())

  return (
    <>
      <div className="lp-warm">
        <div className="lp-container pb-10 pt-5 lg:pt-7">
          <Breadcrumbs items={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.alumni, to: LANDING_PATHS.alumni }, { label: m.nav.alumniStories }]} />
          <header className="mt-6 border-b-2 border-lp-fg pb-5">
            <div className="flex flex-wrap items-end justify-between gap-x-6 gap-y-2 border-b border-lp-strong pb-3 text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-lp-muted">
              <span>{s.masthead}</span>
              <span>{fill(s.edition, { date: edition })}</span>
            </div>
            <h1 className="lp-serif mt-4 text-[3rem] leading-none text-lp-fg sm:text-[5rem] lg:text-[6.5rem]">{s.title}</h1>
            <p className="lp-lead mt-4 max-w-2xl text-lp-muted">{s.lead}</p>
          </header>

          <Reveal className="mt-10">
            <article className="group relative grid items-center gap-8 lg:grid-cols-12 lg:gap-12">
              <Portrait person={coverPerson} className="aspect-[4/3.4] rounded-lp-xl shadow-lp-lg lg:col-span-6" imageClassName="transition-transform duration-700 ease-out group-hover:scale-[1.02]">
                <span className="absolute left-4 top-4 rounded-full bg-lp-brand px-3 py-1.5 text-[0.75rem] font-semibold uppercase tracking-[0.1em] text-lp-onbrand">{s.coverStory}</span>
              </Portrait>
              <div className="lg:col-span-6">
                <p className="lp-eyebrow text-lp-brand-text">{s.themes[cover.theme]}</p>
                <h2 lang="id" className="lp-serif mt-3 text-[2.125rem] leading-[1.12] text-lp-fg sm:text-[3rem]">
                  <Link to={storyPath(cover.slug)} className="after:absolute after:inset-0 after:content-[''] group-hover:text-lp-brand-text">
                    {cover.headline}
                  </Link>
                </h2>
                <p lang="id" className="lp-lead mt-4 text-lp-muted">
                  {cover.dek}
                </p>
                <StoryByline story={cover} className="mt-5" />
                <span aria-hidden className={buttonClass('primary', 'md', 'mt-6')}>
                  {s.read}
                  <ArrowRight size={16} className="transition-transform duration-200 group-hover:translate-x-1" />
                </span>
              </div>
            </article>
          </Reveal>
        </div>
      </div>

      <section aria-labelledby="stories-issue" className="lp-section">
        <div className="lp-container">
          <AlumniDemoNotice className="mb-10" />
          <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <h2 id="stories-issue" className="lp-serif text-[2rem] leading-tight text-lp-fg sm:text-[2.5rem]">
              {s.inThisIssue}
            </h2>
            <div className="min-w-0 lg:max-w-[46rem]">
              <ChipGroup
                label={s.themeLabel}
                value={tema}
                onChange={(v) => filters.set('tema', v)}
                options={[{ value: '', label: m.affairs.filters.all }, ...STORY_THEMES.map((theme) => ({ value: theme, label: s.themes[theme] }))]}
              />
            </div>
          </div>

          <div className="mt-8 border-t border-lp-strong pt-10">
            {themed.length === 0 ? (
              <EmptyResults noun={s.noun} onReset={() => filters.reset()} />
            ) : (
              <ul className="grid gap-x-8 gap-y-12 md:grid-cols-2 lg:grid-cols-3">
                {themed.map((story) => (
                  <li key={story.slug}>
                    <StoryCard story={story} />
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      </section>

      <section aria-labelledby="stories-quote" className="lp-on-band relative isolate overflow-hidden bg-lp-band">
        <div aria-hidden data-lp-decor className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
        <div className="lp-container grid items-center gap-8 py-14 sm:py-20 lg:grid-cols-12">
          <Reveal className="mx-auto w-40 sm:w-52 lg:col-span-3 lg:w-full">
            <Portrait person={quotedPerson} className="aspect-[4/5] rounded-b-lp-lg rounded-t-full ring-1 ring-lp-band-line" />
          </Reveal>
          <Reveal delay={60} className="lg:col-span-9">
            <Quote aria-hidden size={36} className="text-lp-accent" />
            <h2 id="stories-quote" className="sr-only">
              {s.quoteTitle}
            </h2>
            <blockquote lang="id" className="lp-serif mt-3 text-[1.625rem] leading-[1.25] text-lp-band-fg sm:text-[2.5rem]">
              {quoted.quote}
            </blockquote>
            <p className="mt-5 text-[0.9375rem] text-lp-band-muted">
              <span lang="id" className="font-semibold text-lp-band-fg">
                {quotedPerson.name}
              </span>{' '}
              · <span lang="id">{quotedPerson.position}</span>, <span lang="id">{quotedPerson.company}</span>
            </p>
            <Link to={storyPath(quoted.slug)} className="group mt-5 inline-flex min-h-11 items-center gap-2 text-[0.9063rem] font-semibold text-lp-accent">
              {s.read}
              <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
            </Link>
          </Reveal>
        </div>
      </section>
    </>
  )
}
