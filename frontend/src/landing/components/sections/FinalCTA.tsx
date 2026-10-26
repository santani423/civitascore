import { Link } from 'react-router-dom'
import { ArrowRight, MessageSquare } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { useLandingUi } from '../../store/ui'
import { Button } from '../ui/Button'
import { buttonClass } from '../ui/buttonClass'
import { CtaBanner } from '../ui/CtaBanner'
import { useAppEntry } from '../../hooks/useAppEntry'

export function FinalCTA() {
  const { m } = useLandingI18n()
  const openContact = useLandingUi((s) => s.openContact)
  const entry = useAppEntry()

  return (
    <CtaBanner titleId="cta-title" title={m.cta.title} description={m.cta.description}>
      <Link to={entry.to} className={buttonClass('band', 'lg')}>
        {entry.authenticated ? m.nav.openDashboard : m.cta.primary}
        <ArrowRight size={18} aria-hidden />
      </Link>
      <Button variant="bandOutline" size="lg" onClick={openContact}>
        <MessageSquare size={17} aria-hidden />
        {m.cta.secondary}
      </Button>
    </CtaBanner>
  )
}
