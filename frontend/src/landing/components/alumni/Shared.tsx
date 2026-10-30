import { useEffect, useState, type ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, BadgeCheck, Briefcase, FlaskConical, GraduationCap, Heart, HeartHandshake, Info, Landmark, Rocket, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { useInView } from '../../hooks/useInView'
import { prefersReducedMotion } from '../../hooks/useScroll'
import { useAlumniStore, type FavoriteId } from '../../store/alumni'
import type { Company, ProfessionalStatus } from '../../data/alumni'
import { Breadcrumbs, type Crumb } from '../layout/Breadcrumbs'
import { Reveal } from '../ui/Primitives'

/* ------------------------------- Demo notice ------------------------------ */

/** States plainly that everything in the section is demonstration content. */
export function AlumniDemoNotice({ className }: { className?: string }) {
  const { m } = useLandingI18n()
  const d = m.alumni.demo
  return (
    <div role="note" className={cn('flex items-start gap-3 rounded-lp-lg border border-lp-gold/40 bg-lp-gold/[0.07] p-4 text-[0.875rem] leading-relaxed text-lp-fg sm:p-5', className)}>
      <Info size={18} aria-hidden className="mt-0.5 shrink-0 text-lp-gold-text" />
      <p>
        <strong className="font-semibold">{d.title}</strong> {d.body}
      </p>
    </div>
  )
}

/* ------------------------------- Page header ------------------------------ */

interface AlumniPageHeroProps {
  titleId: string
  crumbs: Crumb[]
  eyebrow: string
  titleLead: string
  titleAccent: string
  lead: string
  /** Right-hand column, from `lg` up it sits beside the copy. */
  aside?: ReactNode
  children?: ReactNode
}

/** Compact charcoal header of the section's inner pages; it runs on from the Alumni bar above it. */
export function AlumniPageHero({ titleId, crumbs, eyebrow, titleLead, titleAccent, lead, aside, children }: AlumniPageHeroProps) {
  return (
    <section aria-labelledby={titleId} className="lp-on-band relative isolate overflow-hidden bg-lp-band">
      <div aria-hidden data-lp-decor className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
      <div aria-hidden data-lp-decor className="pointer-events-none absolute -right-40 -top-56 -z-10 h-[30rem] w-[30rem] rounded-full bg-lp-accent/[0.14] blur-3xl" />
      <div className="lp-container grid gap-8 pb-12 pt-5 sm:pb-14 lg:grid-cols-12 lg:items-end lg:gap-12 lg:pb-16 lg:pt-7">
        <div className={aside ? 'lg:col-span-7' : 'lg:col-span-9'}>
          <Breadcrumbs tone="band" items={crumbs} />
          <Reveal>
            <p className="lp-eyebrow mt-5 text-lp-accent">{eyebrow}</p>
            <h1 id={titleId} className="lp-h2 mt-3 text-lp-band-fg sm:text-[2.75rem] sm:leading-[1.1]">
              {titleLead} <span className="text-lp-accent">{titleAccent}</span>
            </h1>
            <p className="lp-lead mt-4 max-w-2xl text-lp-band-muted">{lead}</p>
            {children}
          </Reveal>
        </div>
        {aside && (
          <Reveal delay={80} className="lg:col-span-5">
            {aside}
          </Reveal>
        )}
      </div>
    </section>
  )
}

/** Eyebrow + title + a "see all" link, for the overview page's preview sections. */
export function PreviewHeader({ id, eyebrow, title, lead, to, linkLabel, tone = 'default' }: { id: string; eyebrow: string; title: string; lead?: string; to?: string; linkLabel?: string; tone?: 'default' | 'band' }) {
  const band = tone === 'band'
  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <Reveal className="max-w-2xl">
        <p className={cn('lp-eyebrow', band ? 'text-lp-accent' : 'text-lp-brand-text')}>{eyebrow}</p>
        <h2 id={id} className={cn('lp-h2 mt-3', band ? 'text-lp-band-fg' : 'text-lp-fg')}>
          {title}
        </h2>
        {lead && <p className={cn('lp-body mt-3', band ? 'text-lp-band-muted' : 'text-lp-muted')}>{lead}</p>}
      </Reveal>
      {to && linkLabel && (
        <Link to={to} className={cn('group inline-flex min-h-11 shrink-0 items-center gap-2 text-[0.9063rem] font-semibold', band ? 'text-lp-accent' : 'text-lp-brand-text')}>
          {linkLabel}
          <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
        </Link>
      )}
    </div>
  )
}

/* -------------------------------- Favorites ------------------------------- */

/** Heart toggle. Sits above a card's stretched link. */
export function FavoriteButton({ id, name, className }: { id: FavoriteId; name: string; className?: string }) {
  const { m } = useLandingI18n()
  const on = useAlumniStore((s) => s.favorites.includes(id))
  const toggle = useAlumniStore((s) => s.toggleFavorite)
  return (
    <button
      type="button"
      aria-pressed={on}
      aria-label={fill(m.alumni.favorite, { name })}
      onClick={() => toggle(id)}
      className={cn(
        'relative z-10 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-lp-surface/95 text-lp-muted shadow-lp-sm ring-1 ring-lp-line transition-[color,transform] duration-200 hover:text-lp-brand-text active:scale-90',
        on && 'text-lp-brand-text',
        className,
      )}
    >
      <Heart size={18} aria-hidden className={cn('transition-transform duration-200', on && 'scale-110 fill-current')} />
    </button>
  )
}

/* ------------------------------ Status badges ----------------------------- */

const pill = 'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[0.75rem] font-semibold leading-none ring-1 ring-inset'

const STATUS_ICON: Record<ProfessionalStatus, LucideIcon> = {
  employed: Briefcase,
  entrepreneur: Rocket,
  academic: FlaskConical,
  public: Landmark,
  study: GraduationCap,
}

/** Icon + text, so it never depends on color alone. */
export function ProfessionalBadge({ status, className }: { status: ProfessionalStatus; className?: string }) {
  const { m } = useLandingI18n()
  const Icon = STATUS_ICON[status]
  return (
    <span className={cn(pill, 'bg-lp-brand-soft text-lp-brand-text ring-transparent', className)}>
      <Icon size={13} aria-hidden className="shrink-0" />
      {m.alumni.status[status]}
    </span>
  )
}

export function MentorBadge({ className }: { className?: string }) {
  const { m } = useLandingI18n()
  return (
    <span className={cn(pill, 'bg-lp-surface text-lp-gold-text ring-lp-gold/40', className)}>
      <HeartHandshake size={13} aria-hidden className="shrink-0" />
      {m.alumni.mentor}
    </span>
  )
}

export function VerifiedPill({ label, className }: { label: string; className?: string }) {
  return (
    <span className={cn(pill, 'bg-lp-surface text-lp-ok ring-lp-ok/30', className)}>
      <BadgeCheck size={13} aria-hidden className="shrink-0" />
      {label}
    </span>
  )
}

/* ------------------------------ Company logo ------------------------------ */

const LOGO_TONES = ['bg-lp-band text-lp-accent', 'bg-lp-brand text-white', 'bg-lp-gold text-white', 'bg-lp-band-elev text-lp-gold', 'bg-lp-brand-soft text-lp-brand-text', 'bg-lp-sunken text-lp-gold-text ring-1 ring-inset ring-lp-line']

/** Sample companies have no logo, so a monogram is drawn. Decorative: the name is always printed next to it. */
export function CompanyLogo({ company, className }: { company: Pick<Company, 'slug' | 'short'>; className?: string }) {
  const hash = Array.from(company.slug).reduce((sum, ch) => sum + ch.charCodeAt(0), 0)
  return (
    <span aria-hidden className={cn('flex h-12 w-12 shrink-0 items-center justify-center rounded-lp font-display text-[0.875rem] font-extrabold tracking-tight', LOGO_TONES[hash % LOGO_TONES.length], className)}>
      {company.short}
    </span>
  )
}

/* -------------------------------- Count-up -------------------------------- */

/** Animates to `value` once scrolled into view. Screen readers get the final figure only. */
export function CountUp({ value, format }: { value: number; format: (n: number) => string }) {
  const [ref, inView] = useInView<HTMLSpanElement>()
  const [progress, setProgress] = useState(0)

  useEffect(() => {
    if (!inView) return
    if (prefersReducedMotion()) {
      setProgress(1)
      return
    }
    let frame = 0
    const start = performance.now()
    const tick = (now: number) => {
      const t = Math.min(1, (now - start) / 900)
      setProgress(1 - (1 - t) ** 3)
      if (t < 1) frame = requestAnimationFrame(tick)
    }
    frame = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(frame)
  }, [inView])

  return (
    <span ref={ref}>
      <span aria-hidden>{format(Math.round(value * progress))}</span>
      <span className="sr-only">{format(value)}</span>
    </span>
  )
}

/* ------------------------------ Skeleton bits ----------------------------- */

export function Skeleton({ className }: { className?: string }) {
  return (
    <div aria-hidden className={cn('relative overflow-hidden rounded-lp-sm bg-lp-sunken', className)}>
      <div className="absolute inset-0 -translate-x-full animate-shimmer bg-gradient-to-r from-transparent via-lp-line/70 to-transparent" />
    </div>
  )
}

export function SkeletonFrame({ children, className }: { children: ReactNode; className?: string }) {
  const { m } = useLandingI18n()
  return (
    <div role="status" aria-live="polite" className={className}>
      <span className="sr-only">{m.alumni.loading}</span>
      {children}
    </div>
  )
}
