import { ArrowRight, Mail, MessageCircle } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { BKK_CONTACT } from '../../data/pillars'
import { activityTarget } from '../../lib/activities'
import { buttonClass } from '../ui/buttonClass'
import { CtaBanner } from '../ui/CtaBanner'
import { ActivityLink } from './ActivityLink'

/** "Temukan ruang untuk bertumbuh." — to the activity page once configured; until then, to BKK directly. */
export function StudentLifeCTA() {
  const { m } = useLandingI18n()
  const c = m.studentLife.cta
  const target = activityTarget()

  return (
    <CtaBanner titleId="slj-cta-title" title={c.title} description={c.description} className="pt-16 sm:pt-20 lg:pt-24">
      {target ? (
        <ActivityLink target={target} className={buttonClass('band', 'lg', 'group')}>
          {c.primary}
          <ArrowRight size={18} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
        </ActivityLink>
      ) : (
        <>
          <a href={BKK_CONTACT.whatsapp.href} target="_blank" rel="noopener noreferrer" className={buttonClass('band', 'lg')}>
            <MessageCircle size={18} aria-hidden />
            {c.whatsapp}
            <span className="sr-only"> {m.studentLife.contact.newTab}</span>
          </a>
          <a href={`mailto:${BKK_CONTACT.email}`} className={buttonClass('bandOutline', 'lg')}>
            <Mail size={17} aria-hidden />
            {c.email}
          </a>
        </>
      )}
    </CtaBanner>
  )
}
