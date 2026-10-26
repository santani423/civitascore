import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { ArrowUpRight, Globe, Mail, MapPin, MapPinned, MessageSquare, Phone, Share2 } from 'lucide-react'
import { PMB } from '@/pmb/routes'
import { useLandingI18n } from '../../i18n'
import type { Faculty } from '../../data/faculties'
import { LANDING_PATHS, STUDENT_LIFE_IDS } from '../../paths'
import { SectionLink } from '../layout/SectionLink'
import { buttonClass } from '../ui/buttonClass'
import { InfoItem, InfoList } from '../student-affairs/Detail'
import { FacultySection, PendingInfo } from './FacultySection'
import { FACULTY_SECTION_IDS } from './styles'

const linkClass = 'font-medium text-lp-fg underline decoration-lp-strong underline-offset-[0.2em] transition-colors hover:text-lp-brand-text'

function External({ href, children, newTab }: { href: string; children: ReactNode; newTab: string }) {
  return (
    <a href={href} target="_blank" rel="noopener noreferrer" className={`${linkClass} inline-flex items-center gap-1`}>
      {children}
      <ArrowUpRight size={14} aria-hidden />
      <span className="sr-only"> {newTab}</span>
    </a>
  )
}

/**
 * "Kontak dan Lokasi Fakultas": only channels that exist in the data are
 * rendered. Until the faculty supplies them, it points to the official
 * channels that do exist (PMB committee, BKK).
 */
export function FacultyContact({ faculty, tone }: { faculty: Faculty; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const c = m.faculty.contact
  const newTab = m.studentLife.contact.newTab
  const contact = faculty.contact ?? {}
  const social = contact.socialLinks ?? []
  const hasAny = Boolean(contact.address || contact.email || contact.phone || contact.website || contact.mapUrl || social.length)

  return (
    <FacultySection id={FACULTY_SECTION_IDS.contact} tone={tone} eyebrow={c.eyebrow} title={c.title}>
      {hasAny ? (
        <div className="max-w-2xl rounded-lp-xl border border-lp-line bg-lp-surface p-6 shadow-lp-md sm:p-8">
          <InfoList>
            {contact.address && (
              <InfoItem icon={MapPin} label={c.address}>
                <address lang="id" className="not-italic">
                  {contact.address}
                </address>
                {contact.mapUrl && (
                  <span className="mt-1 block">
                    <External href={contact.mapUrl} newTab={newTab}>
                      <MapPinned size={14} aria-hidden />
                      {c.map}
                    </External>
                  </span>
                )}
              </InfoItem>
            )}
            {contact.email && (
              <InfoItem icon={Mail} label={c.email}>
                <a href={`mailto:${contact.email}`} className={`${linkClass} break-all`}>
                  {contact.email}
                </a>
              </InfoItem>
            )}
            {contact.phone && (
              <InfoItem icon={Phone} label={c.phone}>
                <a href={`tel:${contact.phone.replace(/[^\d+]/g, '')}`} className={linkClass}>
                  {contact.phone}
                </a>
              </InfoItem>
            )}
            {contact.website && (
              <InfoItem icon={Globe} label={c.website}>
                <External href={contact.website} newTab={newTab}>
                  {contact.website.replace(/^https?:\/\//, '')}
                </External>
              </InfoItem>
            )}
            {social.length > 0 && (
              <InfoItem icon={Share2} label={c.social}>
                <ul className="flex flex-wrap gap-x-4 gap-y-1">
                  {social.map((link) => (
                    <li key={link.url}>
                      <External href={link.url} newTab={newTab}>
                        {link.label}
                      </External>
                    </li>
                  ))}
                </ul>
              </InfoItem>
            )}
          </InfoList>
        </div>
      ) : (
        <PendingInfo icon={MessageSquare} title={c.pendingTitle} body={c.pendingBody}>
          <Link to={PMB.contact} className={buttonClass('secondary', 'sm')}>
            {c.admissions}
          </Link>
          <SectionLink section={STUDENT_LIFE_IDS.contact} path={LANDING_PATHS.studentLife} className={buttonClass('secondary', 'sm')}>
            {c.studentAffairs}
          </SectionLink>
        </PendingInfo>
      )}
    </FacultySection>
  )
}
