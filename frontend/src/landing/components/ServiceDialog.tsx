import { Link } from 'react-router-dom'
import { ArrowRight, KeyRound, Users } from 'lucide-react'
import { useLandingI18n } from '../i18n'
import { useAppEntry } from '../hooks/useAppEntry'
import { CAPABILITY_STATUS, type ServiceDef } from '../data/catalog'
import { Dialog } from './ui/Overlay'
import { buttonClass } from './ui/buttonClass'
import { StatusPill } from './ui/Primitives'

/** Detail of one service card: every capability with its own honest status, who uses it, and how to get in. */
export function ServiceDialog({ service, onClose }: { service: ServiceDef | null; onClose: () => void }) {
  const { m } = useLandingI18n()
  const entry = useAppEntry()
  if (!service) return null
  const copy = m.services.items[service.key]
  const d = m.services.dialog
  const Icon = service.icon
  const pmb = service.access.kind === 'pmb'

  return (
    <Dialog
      open
      onClose={onClose}
      title={copy.name}
      description={<StatusPill status={service.status} className="mt-1" />}
      icon={
        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lp bg-lp-brand-soft text-lp-brand-text">
          <Icon size={21} aria-hidden />
        </span>
      }
    >
      <p className="lp-body text-lp-muted">{copy.summary}</p>

      <h3 className="mt-7 text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{d.capabilities}</h3>
      <ul className="mt-3 divide-y divide-lp-line rounded-lp border border-lp-line">
        {service.capabilities.map((cap) => (
          <li key={cap} className="flex items-center justify-between gap-4 px-4 py-3">
            <span className="text-[14.5px] text-lp-fg">{m.capabilities[cap]}</span>
            <StatusPill status={CAPABILITY_STATUS[cap]} compact />
          </li>
        ))}
      </ul>

      <div className="mt-6 grid gap-3 sm:grid-cols-2">
        <div className="flex items-start gap-3 rounded-lp bg-lp-sunken px-4 py-3.5">
          <Users size={17} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
          <div>
            <p className="text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{d.users}</p>
            <p className="mt-1 text-[14px] text-lp-fg">{copy.users}</p>
          </div>
        </div>
        <div className="flex items-start gap-3 rounded-lp bg-lp-sunken px-4 py-3.5">
          <KeyRound size={17} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
          <div>
            <p className="text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{d.access}</p>
            <p className="mt-1 text-[14px] text-lp-fg">{pmb ? d.accessPmb : d.accessLogin}</p>
          </div>
        </div>
      </div>

      <p className="mt-5 text-[13px] text-lp-subtle">{d.note}</p>

      <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        {pmb && (
          <Link to={entry.to} className={buttonClass('secondary')}>
            {entry.label}
          </Link>
        )}
        <Link to={service.access.kind === 'pmb' ? service.access.to : entry.to} className={buttonClass('primary')}>
          {pmb ? m.services.accessPmb : entry.authenticated ? m.nav.openDashboard : m.services.accessLogin}
          <ArrowRight size={16} aria-hidden />
        </Link>
      </div>
    </Dialog>
  )
}
