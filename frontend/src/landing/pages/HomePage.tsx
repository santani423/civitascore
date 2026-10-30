import { useLandingI18n } from '../i18n'
import { useDocumentMeta } from '../hooks/useDocumentSetup'
import { useScrollToHash } from '../hooks/useScroll'
import { LANDING_PATHS } from '../paths'
import { HeroSection } from '../components/sections/HeroSection'
import { AudienceStrip } from '../components/sections/AudienceStrip'
import { FeaturesSection } from '../components/sections/FeaturesSection'
import { IntegrationSection } from '../components/sections/IntegrationSection'
import { ServicesSection } from '../components/sections/ServicesSection'
import { AcademicSection } from '../components/sections/AcademicSection'
import { StudentLifeTeaser } from '../components/sections/StudentLifeTeaser'
import { InfoSection } from '../components/sections/InfoSection'
import { TuitionSection } from '../components/sections/TuitionSection'
import { FinalCTA } from '../components/sections/FinalCTA'
 
export function HomePage() {
  const { m } = useLandingI18n()
  useDocumentMeta({ title: m.meta.title, description: m.meta.description, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.home })
  useScrollToHash()

  return (
    <>
      <HeroSection />
      <AudienceStrip />
      <FeaturesSection />
      <IntegrationSection />
      <ServicesSection />
      <AcademicSection />
      <StudentLifeTeaser />
      <InfoSection />
      <TuitionSection />
      <FinalCTA />
    </>
  )
}
