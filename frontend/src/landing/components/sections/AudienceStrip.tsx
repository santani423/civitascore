import { BadgeCheck, BookUser, Fingerprint, GraduationCap, History, Landmark, MonitorSmartphone, Settings2, ShieldCheck, UsersRound, type LucideIcon } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { Reveal } from '../ui/Primitives'

type RoleKey = keyof Messages['audience']['roles']
type TraitKey = keyof Messages['audience']['traits']

/** Roles that sign in through /login (seeded role templates); applicants use the separate PMB account. */
const ROLES: Array<{ key: RoleKey; icon: LucideIcon }> = [
  { key: 'students', icon: GraduationCap },
  { key: 'lecturers', icon: BookUser },
  { key: 'staff', icon: UsersRound },
  { key: 'leaders', icon: Landmark },
  { key: 'admins', icon: Settings2 },
]

const TRAITS: Array<{ key: TraitKey; icon: LucideIcon }> = [
  { key: 'signIn', icon: Fingerprint },
  { key: 'roles', icon: ShieldCheck },
  { key: 'audit', icon: History },
  { key: 'devices', icon: MonitorSmartphone },
]

/**
 * Sits on the hero's bottom edge, where the university website shows
 * accreditation logos and statistics. Those figures aren't verified for
 * this page, so it lists who the system serves and how it protects access.
 */
export function AudienceStrip() {
  const { m } = useLandingI18n()
  const a = m.audience

  return (
    <div className="relative z-10 -mt-14 sm:-mt-16">
      <div className="lp-container">
        <Reveal>
          <div className="grid gap-8 rounded-lp-lg border border-lp-line bg-lp-surface p-6 shadow-lp-lg sm:p-8 lg:grid-cols-12 lg:gap-0">
            <div className="lg:col-span-6 lg:pr-10">
              <h2 className="flex items-center gap-2 text-[0.9375rem] font-semibold text-lp-fg">
                <BadgeCheck size={18} aria-hidden className="text-lp-brand-text" />
                {a.title}
              </h2>
              <ul aria-label={a.label} className="mt-5 grid grid-cols-2 gap-3 min-[480px]:grid-cols-3 sm:grid-cols-5">
                {ROLES.map(({ key, icon: Icon }) => (
                  <li key={key} className="flex flex-col items-center gap-2 rounded-lp border border-lp-line px-2 py-3.5 text-center">
                    <span className="flex h-10 w-10 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
                      <Icon size={19} aria-hidden />
                    </span>
                    <span className="text-[0.7813rem] font-semibold leading-tight text-lp-fg">{a.roles[key]}</span>
                  </li>
                ))}
              </ul>
            </div>
            <div className="border-t border-lp-line pt-8 lg:col-span-6 lg:border-l lg:border-t-0 lg:pl-10 lg:pt-0">
              <h2 className="text-[0.9375rem] font-semibold text-lp-fg">{a.traitsTitle}</h2>
              <ul className="mt-5 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                {TRAITS.map(({ key, icon: Icon }) => (
                  <li key={key} className="flex items-center gap-3">
                    <Icon size={22} strokeWidth={1.7} aria-hidden className="shrink-0 text-lp-brand-text" />
                    <span className="text-[0.875rem] font-medium text-lp-muted">{a.traits[key]}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </Reveal>
      </div>
    </div>
  )
}
