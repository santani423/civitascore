import { ArrowRight, Users } from 'lucide-react'
import { useLandingI18n } from '../i18n'
import { CAPABILITY_STATUS, type ModuleDef } from '../data/catalog'
import { useLandingUi } from '../store/ui'
import { Dialog } from './ui/Overlay'
import { Button } from './ui/Button'
import { StatusPill } from './ui/Primitives'

export function ModuleDialog({ module, onClose }: { module: ModuleDef | null; onClose: () => void }) {
  const { m } = useLandingI18n()
  const openRequest = useLandingUi((s) => s.openRequest)
  if (!module) return null
  const copy = m.modules.items[module.key]
  const Icon = module.icon

  return (
    <Dialog
      open
      onClose={onClose}
      title={copy.name}
      description={<StatusPill status={module.status} className="mt-1" />}
      icon={
        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lp bg-lp-brand/10 text-lp-brand-text">
          <Icon size={21} aria-hidden />
        </span>
      }
    >
      <p className="lp-body text-lp-muted">{copy.detail}</p>

      <h3 className="mt-7 text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{m.modules.featuresTitle}</h3>
      <ul className="mt-3 divide-y divide-lp-line rounded-lp border border-lp-line">
        {module.capabilities.map((cap) => (
          <li key={cap} className="flex items-center justify-between gap-4 px-4 py-3">
            <span className="text-[14.5px] text-lp-fg">{m.capabilities[cap]}</span>
            <StatusPill status={CAPABILITY_STATUS[cap]} compact />
          </li>
        ))}
      </ul>

      <div className="mt-6 flex items-start gap-3 rounded-lp bg-lp-sunken px-4 py-3.5">
        <Users size={17} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
        <div>
          <p className="text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{m.modules.usersTitle}</p>
          <p className="mt-1 text-[14.5px] text-lp-fg">{copy.users}</p>
        </div>
      </div>

      <p className="mt-5 text-[13px] text-lp-subtle">{m.modules.dialogNote}</p>

      <div className="mt-6 flex justify-end">
        <Button
          onClick={() => {
            onClose()
            openRequest('demo', [module.category])
          }}
        >
          {m.modules.dialogCta}
          <ArrowRight size={16} aria-hidden />
        </Button>
      </div>
    </Dialog>
  )
}
