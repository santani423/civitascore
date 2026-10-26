import { useState, type ReactNode } from 'react'
import { FileClock, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import type { Faculty } from '../../data/faculties'
import { CoverArt } from '../student-affairs/Covers'
import { Reveal, SectionHeader } from '../ui/Primitives'
import { FACULTY_STYLE } from './styles'

interface FacultySectionProps {
  id: string
  eyebrow: string
  title: ReactNode
  description?: ReactNode
  /** Alternating backgrounds down the page. */
  tone?: 'canvas' | 'warm'
  /** Shown next to the heading, e.g. a "see all" link. */
  action?: ReactNode
  children: ReactNode
}

/** One section of a faculty page: eyebrow + h2 (+ lead), then its content. */
export function FacultySection({ id, eyebrow, title, description, tone = 'canvas', action, children }: FacultySectionProps) {
  return (
    // scroll-mt clears the sticky section nav (the navbar is covered by scroll-padding-top).
    <section id={id} aria-labelledby={`${id}-title`} className={cn('lp-section scroll-mt-16', tone === 'warm' ? 'lp-warm' : 'bg-lp-canvas')}>
      <div className="lp-container">
        <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <SectionHeader id={`${id}-title`} eyebrow={eyebrow} title={title} description={description} />
          {action && <Reveal className="shrink-0">{action}</Reveal>}
        </div>
        <div className="mt-10">{children}</div>
      </div>
    </section>
  )
}

/**
 * Official information that hasn't been supplied yet. Always says so
 * plainly ("Menunggu informasi resmi") instead of showing invented content.
 */
export function PendingInfo({ title, body, icon: Icon = FileClock, children, className }: { title: string; body?: string; icon?: LucideIcon; children?: ReactNode; className?: string }) {
  const { m } = useLandingI18n()
  return (
    <div className={cn('flex flex-col gap-5 rounded-lp-lg border border-dashed border-lp-strong bg-lp-surface/80 p-6 sm:flex-row sm:items-start sm:p-7', className)}>
      <span aria-hidden className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-lp-gold/15 text-lp-gold-text">
        <Icon size={22} />
      </span>
      <div className="min-w-0 flex-1">
        <p className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-gold-text">{m.faculty.pending.label}</p>
        <p className="lp-h4 mt-1.5 text-lp-fg">{title}</p>
        {body && <p className="lp-small mt-1.5 max-w-2xl text-lp-muted">{body}</p>}
        {children && <div className="mt-5 flex flex-wrap gap-2">{children}</div>}
      </div>
    </div>
  )
}

/** The faculty's official cover photo when one is set (falling back if it fails to load), otherwise its field motif. */
export function FacultyCover({ faculty, size = 'card', priority, className }: { faculty: Faculty; size?: 'card' | 'hero'; priority?: boolean; className?: string }) {
  const { m } = useLandingI18n()
  const [failed, setFailed] = useState(false)
  if (faculty.coverImage && !failed) {
    return (
      <div className={cn('relative overflow-hidden bg-lp-sunken', className)}>
        <img
          src={faculty.coverImage}
          alt={fill(m.faculty.coverAlt, { name: faculty.name })}
          loading={priority ? 'eager' : 'lazy'}
          fetchPriority={priority ? 'high' : undefined}
          decoding="async"
          onError={() => setFailed(true)}
          className="absolute inset-0 h-full w-full object-cover"
        />
      </div>
    )
  }
  return <CoverArt style={FACULTY_STYLE[faculty.slug]} size={size} className={className} />
}

/** Nothing to show in a sample-data section (not missing official data): a plain note with somewhere useful to go. */
export function EmptyNote({ text, children }: { text: string; children?: ReactNode }) {
  return (
    <div className="flex flex-col gap-4 rounded-lp-lg border border-dashed border-lp-strong bg-lp-surface/80 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
      <p className="lp-small text-lp-muted">{text}</p>
      {children}
    </div>
  )
}
