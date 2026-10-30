import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { ArrowLeft, ArrowRight, Check, SearchX, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { Breadcrumbs, type Crumb } from '../layout/Breadcrumbs'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'

/** A titled block of a detail page's main column. */
export function DetailSection({ id, title, children, className }: { id: string; title: ReactNode; children: ReactNode; className?: string }) {
  return (
    <section aria-labelledby={id} className={className}>
      <h2 id={id} className="lp-h3 text-[1.375rem] text-lp-fg sm:text-[1.5rem]">
        {title}
      </h2>
      <div className="mt-5">{children}</div>
    </section>
  )
}

export function Paragraphs({ items }: { items: string[] }) {
  return (
    <div lang="id" className="lp-body max-w-[68ch] space-y-4 text-lp-muted">
      {items.map((text) => (
        <p key={text}>{text}</p>
      ))}
    </div>
  )
}

export function BulletList({ items, className }: { items: string[]; className?: string }) {
  return (
    <ul lang="id" className={cn('space-y-3', className)}>
      {items.map((text) => (
        <li key={text} className="flex items-start gap-3 text-[0.9375rem] leading-relaxed text-lp-muted">
          <span aria-hidden className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
            <Check size={13} strokeWidth={2.5} />
          </span>
          {text}
        </li>
      ))}
    </ul>
  )
}

/** Labelled facts (date, venue, …) with an icon each. */
export function InfoList({ children, className }: { children: ReactNode; className?: string }) {
  return <dl className={cn('divide-y divide-lp-line', className)}>{children}</dl>
}

export function InfoItem({ icon: Icon, label, children }: { icon: LucideIcon; label: string; children: ReactNode }) {
  return (
    <div className="relative py-4 pl-12 first:pt-0 [&:first-child>dt>span]:top-0">
      <dt className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle">
        <span aria-hidden className="absolute left-0 top-4 flex h-9 w-9 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
          <Icon size={17} />
        </span>
        {label}
      </dt>
      <dd className="mt-1 text-[0.9375rem] leading-relaxed text-lp-fg">{children}</dd>
    </div>
  )
}

export function BackLink({ to, children, className }: { to: string; children: ReactNode; className?: string }) {
  return (
    <Link to={to} className={cn('group inline-flex min-h-11 items-center gap-2 text-[0.9063rem] font-semibold text-lp-brand-text', className)}>
      <ArrowLeft size={17} aria-hidden className="transition-transform duration-200 group-hover:-translate-x-1" />
      {children}
    </Link>
  )
}

/** "Kegiatan Lainnya" / "UKM Lainnya" band below a detail page. */
export function RelatedSection({ titleId, title, allTo, allLabel, children }: { titleId: string; title: string; allTo: string; allLabel: string; children: ReactNode }) {
  return (
    <section aria-labelledby={titleId} className="lp-section lp-warm">
      <div className="lp-container">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <h2 id={titleId} className="lp-h2 text-lp-fg">
            {title}
          </h2>
          <Link to={allTo} className="group inline-flex min-h-11 items-center gap-2 text-[0.9063rem] font-semibold text-lp-brand-text">
            {allLabel}
            <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
          </Link>
        </div>
        <div className="mt-8">{children}</div>
      </div>
    </section>
  )
}

/** Unknown slug: says so inside the landing layout and points back to the list. */
export function NotFoundState({ crumbs, title, body, backTo, backLabel }: { crumbs: Crumb[]; title: string; body: string; backTo: string; backLabel: string }) {
  return (
    <section aria-labelledby="not-found-title" className="relative overflow-hidden pb-24 pt-28 sm:pb-32 sm:pt-36">
      <div aria-hidden className="lp-grid-bg pointer-events-none absolute inset-x-0 top-0 h-96" />
      <div className="lp-container relative max-w-3xl">
        <Breadcrumbs items={crumbs} />
        <Reveal className="mt-10">
          <span className="flex h-14 w-14 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
            <SearchX size={26} aria-hidden />
          </span>
          <h1 id="not-found-title" className="lp-h2 mt-6 text-lp-fg">
            {title}
          </h1>
          <p className="lp-lead mt-4 text-lp-muted">{body}</p>
          <Link to={backTo} className={buttonClass('primary', 'lg', 'group mt-8')}>
            <ArrowLeft size={18} aria-hidden className="transition-transform duration-200 group-hover:-translate-x-1" />
            {backLabel}
          </Link>
        </Reveal>
      </div>
    </section>
  )
}
