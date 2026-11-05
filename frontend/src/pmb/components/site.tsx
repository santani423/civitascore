import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, CheckCircle2, ChevronDown, Circle, CircleDot } from 'lucide-react'
import type { SiteInfo } from '@/pmb/api/public'
import { RichText } from '@/pmb/components/Section'
import { cn } from '@/utils/cn'

/**
 * Logo kampus + "PMB <nama singkat>" + nama lengkap — susunan Wordmark landing,
 * dengan identitas tenant. Dekoratif: tautan pembungkusnya yang memberi nama aksesibel.
 */
export function SiteMark({ site, label = 'PMB', tone = 'default', size = 'md' }: { site: SiteInfo | null; label?: string; tone?: 'default' | 'band'; size?: 'md' | 'lg' }) {
  const band = tone === 'band'
  const name = site?.university.short_name || site?.university.name || ''
  const logo = site?.university.logo_url

  return (
    <span aria-hidden className="inline-flex min-w-0 items-center gap-3">
      {logo ? (
        <img src={logo} alt="" className={cn('w-auto shrink-0 object-contain', size === 'lg' ? 'h-14' : 'h-11', band && 'rounded-lp-sm bg-white p-1')} />
      ) : (
        <span className={cn('flex shrink-0 items-center justify-center rounded-lp-sm bg-lp-brand font-display font-extrabold uppercase text-lp-onbrand', size === 'lg' ? 'size-14 text-lg' : 'size-11 text-[0.9375rem]')}>
          {(name || label).slice(0, 2)}
        </span>
      )}
      <span className="flex min-w-0 flex-col">
        <span className={cn('font-display font-extrabold uppercase leading-[1.1] tracking-[-0.01em] sm:whitespace-nowrap', size === 'lg' ? 'text-[1.3125rem]' : 'text-[1.0625rem]', band ? 'text-lp-band-fg' : 'text-lp-fg')}>
          <span className={band ? 'text-lp-accent' : 'text-lp-brand-text'}>{label}</span> {name}
        </span>
        <span className={cn('mt-1 truncate font-medium', size === 'lg' ? 'text-[0.7813rem]' : 'text-[0.6875rem]', band ? 'text-lp-band-muted' : 'text-lp-subtle')}>
          {site?.university.name ?? 'Penerimaan Mahasiswa Baru'}
        </span>
      </span>
    </span>
  )
}

/** Eyebrow + judul + pengantar (SectionHeader landing); `action` di kanan, mis. tautan "Semua jalur". */
export function SectionHeading({ id, eyebrow, title, description, action }: { id: string; eyebrow: string; title: ReactNode; description?: ReactNode; action?: ReactNode }) {
  return (
    <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div className="max-w-2xl">
        <p className="lp-eyebrow text-lp-brand-text">{eyebrow}</p>
        <h2 id={id} className="lp-h2 mt-3 text-lp-fg">{title}</h2>
        {description && <p className="lp-lead mt-4 text-lp-muted">{description}</p>}
      </div>
      {action}
    </div>
  )
}

export function MoreLink({ to, children }: { to: string; children: ReactNode }) {
  return (
    <Link to={to} className="group inline-flex min-h-11 shrink-0 items-center gap-2 text-[0.9063rem] font-semibold text-lp-brand-text">
      {children}
      <ArrowRight size={16} aria-hidden className="transition-transform group-hover:translate-x-0.5" />
    </Link>
  )
}

const WAVE_STATUS = {
  open: { label: 'Dibuka', icon: CheckCircle2, tone: 'bg-lp-ok/10 text-lp-ok ring-lp-ok/20' },
  scheduled: { label: 'Segera', icon: CircleDot, tone: 'bg-lp-gold/10 text-lp-gold-text ring-lp-gold/25' },
  closed: { label: 'Ditutup', icon: Circle, tone: 'bg-lp-sunken text-lp-muted ring-lp-strong' },
} as const

/** Status gelombang — selalu teks + bentuk, tidak bergantung warna (StatusPill landing). */
export function WaveStatus({ status }: { status: string }) {
  const s = WAVE_STATUS[status === 'open' || status === 'scheduled' ? status : 'closed']

  return (
    <span className={cn('inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[0.75rem] font-medium ring-1 ring-inset', s.tone)}>
      <s.icon size={14} aria-hidden className="shrink-0" />
      {s.label}
    </span>
  )
}

/** Garis oranye di bawah menu header yang aktif (NavUnderline landing). */
export function NavUnderline({ active }: { active: boolean }) {
  return (
    <span
      aria-hidden
      className={cn('absolute inset-x-2.5 -bottom-px h-[3px] rounded-t-full bg-lp-brand transition-[opacity,transform] duration-300', active ? 'scale-x-100 opacity-100' : 'scale-x-50 opacity-0')}
    />
  )
}

/** Satu butir FAQ sebagai akordeon <details>. */
export function FaqItem({ title, body }: { title: string; body: string | null | undefined }) {
  return (
    <details className="group rounded-lp border border-lp-line bg-lp-surface transition-colors open:border-lp-brand/30 open:shadow-lp-sm">
      <summary className="flex min-h-12 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 text-[0.9375rem] font-semibold text-lp-fg marker:hidden hover:text-lp-brand-text [&::-webkit-details-marker]:hidden">
        {title}
        <ChevronDown size={18} aria-hidden className="shrink-0 text-lp-subtle transition-transform duration-200 group-open:rotate-180 group-open:text-lp-brand-text" />
      </summary>
      <div className="border-t border-lp-line px-4 pb-4 text-lp-muted">
        <RichText html={body} />
      </div>
    </details>
  )
}

/** Chip netral untuk jalur · biaya, jenjang, dsb. */
export function Chip({ children, className }: { children: ReactNode; className?: string }) {
  return <span className={cn('inline-flex items-center rounded-full bg-lp-sunken px-2.5 py-1 text-[0.75rem] font-medium text-lp-muted ring-1 ring-inset ring-lp-line', className)}>{children}</span>
}
