import { Fragment } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, Building2, GraduationCap, Quote, UserRound } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { ALUMNI_STORIES, findAlumnus, findStory, type AlumniStory, type Alumnus } from '../../data/alumni'
import { LANDING_PATHS } from '../../paths'
import { alumnusPath, storyPath } from '../../lib/alumniPaths'
import { Breadcrumbs } from '../../components/layout/Breadcrumbs'
import { buttonClass } from '../../components/ui/buttonClass'
import { Reveal } from '../../components/ui/Primitives'
import { DemoBadge } from '../../components/student-affairs/Badges'
import { BackLink, BulletList, InfoItem, InfoList, NotFoundState, RelatedSection } from '../../components/student-affairs/Detail'
import { Portrait } from '../../components/alumni/Portrait'
import { StoryByline, StoryCard } from '../../components/alumni/Stories'

/** /komunitas-alumni/kisah/:slug — one article template for every sample story. */
export function StoryDetailPage({ slug }: { slug: string }) {
  const { m } = useLandingI18n()
  const s = m.alumni.stories
  const story = findStory(slug)
  const person = story ? findAlumnus(story.alumnus) : undefined

  useDocumentMeta({
    title: fill(m.meta.pageTitle, { page: story ? story.headline : s.notFoundTitle }),
    description: story ? story.dek : s.notFoundBody,
    ogLocale: m.meta.ogLocale,
    path: storyPath(slug),
    robots: story ? undefined : 'noindex',
  })

  if (!story || !person) {
    return (
      <NotFoundState
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.alumniStories, to: LANDING_PATHS.alumniStories }, { label: s.notFoundTitle }]}
        title={s.notFoundTitle}
        body={s.notFoundBody}
        backTo={LANDING_PATHS.alumniStories}
        backLabel={s.back}
      />
    )
  }
  return <Story story={story} person={person} />
}

function PullQuote({ text }: { text: string }) {
  return (
    <figure className="my-10 border-y-2 border-lp-fg py-7">
      <Quote aria-hidden size={28} className="text-lp-brand-text" />
      <blockquote lang="id" className="lp-serif mt-2 text-[1.625rem] leading-[1.25] text-lp-fg sm:text-[2rem]">
        {text}
      </blockquote>
    </figure>
  )
}

function Story({ story, person }: { story: AlumniStory; person: Alumnus }) {
  const { m } = useLandingI18n()
  const s = m.alumni.stories
  const more = ALUMNI_STORIES.filter((other) => other.slug !== story.slug).slice(0, 3)

  return (
    <article aria-labelledby="story-title">
      <header className="lp-warm">
        <div className="lp-container pb-12 pt-5 lg:pb-16 lg:pt-7">
          <Breadcrumbs items={[{ label: m.nav.alumni, to: LANDING_PATHS.alumni }, { label: m.nav.alumniStories, to: LANDING_PATHS.alumniStories }, { label: story.headline, lang: 'id' }]} />
          <div className="mt-8 grid items-center gap-8 lg:grid-cols-12 lg:gap-12">
            <Reveal className="lg:col-span-8">
              <div className="flex flex-wrap items-center gap-2">
                <p className="lp-eyebrow text-lp-brand-text">{s.themes[story.theme]}</p>
                <DemoBadge />
              </div>
              <h1 id="story-title" lang="id" className="lp-serif mt-4 text-[2.25rem] leading-[1.08] text-lp-fg sm:text-[3.5rem]">
                {story.headline}
              </h1>
              <p lang="id" className="lp-lead mt-5 text-lp-muted">
                {story.dek}
              </p>
              <StoryByline story={story} className="mt-6" />
            </Reveal>
            <Reveal delay={80} className="mx-auto w-48 sm:w-60 lg:col-span-4 lg:w-full">
              <Portrait person={person} className="aspect-[4/5] rounded-b-lp-lg rounded-t-full shadow-lp-xl" />
            </Reveal>
          </div>
        </div>
      </header>

      <div className="lp-container grid gap-12 py-12 sm:py-14 lg:grid-cols-12 lg:gap-14 lg:py-16">
        <div className="min-w-0 lg:col-span-8">
          <div lang="id" className="lp-serif max-w-[66ch] text-[1.125rem] leading-[1.75] text-lp-fg sm:text-[1.1875rem]">
            {story.body.map((paragraph, i) => (
              <Fragment key={paragraph}>
                <p className={i === 0 ? 'lp-dropcap' : 'mt-5'}>{paragraph}</p>
                {i === story.quoteAfter && <PullQuote text={story.quote} />}
              </Fragment>
            ))}
          </div>

          <section aria-labelledby="story-milestones" className="mt-12 max-w-[66ch]">
            <h2 id="story-milestones" className="lp-h3 text-[1.375rem] text-lp-fg">
              {s.journey}
            </h2>
            <ol className="relative mt-5 space-y-1 border-l-2 border-lp-line pl-6">
              {story.milestones.map((item) => (
                <li key={item.year} className="relative py-2.5">
                  <span aria-hidden className="absolute -left-[1.95rem] top-[0.95rem] h-3.5 w-3.5 rounded-full border-[3px] border-lp-canvas bg-lp-brand" />
                  <p className="lp-tabular text-[0.8125rem] font-semibold text-lp-brand-text">{item.year}</p>
                  <p lang="id" className="mt-0.5 text-[0.9375rem] text-lp-fg">
                    {item.title}
                  </p>
                </li>
              ))}
            </ol>
          </section>

          <BackLink to={LANDING_PATHS.alumniStories} className="mt-10">
            {s.back}
          </BackLink>
        </div>

        <aside aria-labelledby="story-person" className="lg:col-span-4">
          <div className="rounded-lp-xl border border-lp-line bg-lp-surface p-6 shadow-lp-md lg:sticky lg:top-[calc(var(--lp-nav-h)+4.75rem)]">
            <h2 id="story-person" className="lp-h4 text-lp-fg">
              {s.aboutPerson}
            </h2>
            <InfoList className="mt-5">
              <InfoItem icon={UserRound} label={s.name}>
                <span lang="id">{person.name}</span>
              </InfoItem>
              <InfoItem icon={GraduationCap} label={s.cohortLabel}>
                {fill(s.cohort, { year: person.gradYear })}
              </InfoItem>
              <InfoItem icon={Building2} label={s.role}>
                <span lang="id">
                  {person.position}, {person.company}
                </span>
              </InfoItem>
            </InfoList>
            <h3 className="lp-h4 mt-6 border-t border-lp-line pt-6 text-lp-fg">{s.achievements}</h3>
            <BulletList items={story.achievements} className="mt-4" />
            <Link to={alumnusPath(person.slug)} className={buttonClass('primary', 'md', 'group mt-6 w-full')}>
              {s.viewProfile}
              <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
            </Link>
          </div>
        </aside>
      </div>

      <RelatedSection titleId="story-more" title={s.more} allTo={LANDING_PATHS.alumniStories} allLabel={s.allStories}>
        <ul className="grid gap-x-8 gap-y-12 md:grid-cols-2 lg:grid-cols-3">
          {more.map((other) => (
            <li key={other.slug}>
              <StoryCard story={other} />
            </li>
          ))}
        </ul>
      </RelatedSection>
    </article>
  )
}
