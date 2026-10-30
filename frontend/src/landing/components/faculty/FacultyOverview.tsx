import { BookOpenText, Compass, History, Lightbulb, Microscope, Sprout, type LucideIcon } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import type { Faculty } from '../../data/faculties'
import { Paragraphs } from '../student-affairs/Detail'
import { FacultySection } from './FacultySection'
import { FACULTY_SECTION_IDS } from './styles'

type TopicKey = 'history' | 'identity' | 'expertise' | 'learning' | 'research' | 'studentDevelopment'

const TOPICS: Array<{ key: TopicKey; icon: LucideIcon }> = [
  { key: 'history', icon: History },
  { key: 'identity', icon: Compass },
  { key: 'expertise', icon: Microscope },
  { key: 'learning', icon: BookOpenText },
  { key: 'research', icon: Lightbulb },
  { key: 'studentDevelopment', icon: Sprout },
]

function topicContent(faculty: Faculty, key: TopicKey): string[] | null {
  const value = key === 'history' ? faculty.history : key === 'expertise' ? faculty.expertise : faculty[key]
  if (Array.isArray(value)) return value.length > 0 ? value : null
  return value ? [value] : null
}

/**
 * "Tentang Fakultas": the official overview, then one card per topic
 * (history, academic identity, expertise, learning, research, student
 * development). A topic without official text says so instead of being guessed.
 */
export function FacultyOverview({ faculty, tone }: { faculty: Faculty; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const a = m.faculty.about

  return (
    <FacultySection id={FACULTY_SECTION_IDS.about} tone={tone} eyebrow={a.eyebrow} title={fill(a.title, { name: faculty.name })} description={faculty.overview ? undefined : a.lead}>
      {faculty.overview && faculty.overview.length > 0 && (
        <div className="mb-10">
          <Paragraphs items={faculty.overview} />
        </div>
      )}
      <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {TOPICS.map(({ key, icon: Icon }) => {
          const content = topicContent(faculty, key)
          return (
            <li key={key} className={content ? 'rounded-lp-lg border border-lp-line bg-lp-surface p-6 shadow-lp-sm' : 'rounded-lp-lg border border-dashed border-lp-strong bg-lp-surface/70 p-6'}>
              <span aria-hidden className="flex h-11 w-11 items-center justify-center rounded-lp-sm bg-lp-brand-soft text-lp-brand-text">
                <Icon size={20} />
              </span>
              <h3 className="lp-h4 mt-4 text-lp-fg">{a.topics[key]}</h3>
              {content ? (
                key === 'expertise' ? (
                  <ul lang="id" className="mt-3 flex flex-wrap gap-1.5">
                    {content.map((item) => (
                      <li key={item} className="rounded-full bg-lp-sunken px-2.5 py-1 text-[0.8125rem] font-medium text-lp-muted">
                        {item}
                      </li>
                    ))}
                  </ul>
                ) : (
                  <div lang="id" className="lp-small mt-2 space-y-2 text-lp-muted">
                    {content.map((p) => (
                      <p key={p}>{p}</p>
                    ))}
                  </div>
                )
              ) : (
                <p className="mt-2 inline-flex items-center gap-1.5 text-[0.8125rem] font-medium text-lp-gold-text">{m.faculty.pending.label}</p>
              )}
            </li>
          )
        })}
      </ul>
    </FacultySection>
  )
}
