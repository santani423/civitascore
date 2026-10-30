import { Link } from 'react-router-dom'
import { ArrowRight } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { findAlumnus, readingMinutes, type AlumniStory } from '../../data/alumni'
import { storyPath } from '../../lib/alumniPaths'
import { Portrait } from './Portrait'

/** Name, cohort and reading time, the same under every headline. */
export function StoryByline({ story, className }: { story: AlumniStory; className?: string }) {
  const { m } = useLandingI18n()
  const s = m.alumni.stories
  const person = findAlumnus(story.alumnus)
  if (!person) return null
  return (
    <p className={cn('text-[0.875rem] text-lp-muted', className)}>
      <span lang="id" className="font-semibold text-lp-fg">
        {person.name}
      </span>{' '}
      · {fill(s.cohort, { year: person.gradYear })} · {fill(s.readTime, { n: readingMinutes(story.body) })}
    </p>
  )
}

/** Editorial card: a tall portrait, the theme as kicker, a serif headline and the standfirst. */
export function StoryCard({ story, size = 'md' }: { story: AlumniStory; size?: 'md' | 'lg' }) {
  const { m } = useLandingI18n()
  const person = findAlumnus(story.alumnus)
  if (!person) return null
  const large = size === 'lg'
  return (
    <article className="group relative flex h-full flex-col">
      <Portrait person={person} className={cn('rounded-lp-lg', large ? 'aspect-[4/3.6]' : 'aspect-[4/3]')} imageClassName="transition-transform duration-500 ease-out group-hover:scale-[1.03]" />
      <p className="lp-eyebrow mt-4 text-lp-brand-text">{m.alumni.stories.themes[story.theme]}</p>
      <h3 lang="id" className={cn('lp-serif mt-2 leading-[1.2] text-lp-fg', large ? 'text-[1.75rem] sm:text-[2.125rem]' : 'text-[1.375rem]')}>
        <Link to={storyPath(story.slug)} className="after:absolute after:inset-0 after:content-[''] group-hover:text-lp-brand-text">
          {story.headline}
        </Link>
      </h3>
      <p lang="id" className={cn('mt-2 text-lp-muted', large ? 'lp-body' : 'lp-small line-clamp-3')}>
        {story.dek}
      </p>
      <p className="mt-3 text-[0.8125rem] text-lp-muted">
        <span lang="id" className="font-semibold text-lp-fg">
          {person.name}
        </span>{' '}
        · <span lang="id">{person.company}</span>
      </p>
      <span aria-hidden className="mt-3 inline-flex items-center gap-1.5 text-[0.875rem] font-semibold text-lp-brand-text">
        {m.alumni.stories.read}
        <ArrowRight size={15} className="transition-transform duration-200 group-hover:translate-x-1" />
      </span>
    </article>
  )
}
