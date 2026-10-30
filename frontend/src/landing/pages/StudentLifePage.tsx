import { fill, useLandingI18n } from '../i18n'
import { useDocumentMeta } from '../hooks/useDocumentSetup'
import { useScrollToHash } from '../hooks/useScroll'
import { PILLARS } from '../data/pillars'
import { LANDING_PATHS } from '../paths'
import { StudentLifeHero } from '../components/student-life/StudentLifeHero'
import { PillarNav } from '../components/student-life/PillarNav'
import { PillarSection } from '../components/student-life/PillarSection'
import { ActivitiesSection } from '../components/student-life/ActivitiesSection'
import { StudentLifeCTA } from '../components/student-life/StudentLifeCTA'

/**
 * 5 Pilar Student Life Journey: hero → sticky pillar nav → one section per
 * pillar → activities & BKK contact → closing CTA. Official text and images
 * come from data/pillars.ts; activity links from lib/activities.ts.
 */
export function StudentLifePage() {
  const { m } = useLandingI18n()
  const s = m.studentLife
  useDocumentMeta({
    title: fill(m.meta.pageTitle, { page: s.metaTitle }),
    description: s.metaDescription,
    ogLocale: m.meta.ogLocale,
    path: LANDING_PATHS.studentLife,
  })
  useScrollToHash()

  return (
    <>
      <StudentLifeHero />
      {/* The pillar nav sticks only within this wrapper, i.e. while the pillars are on screen. */}
      <div>
        <PillarNav />
        {PILLARS.map((pillar, i) => (
          <PillarSection key={pillar.id} pillar={pillar} index={i} />
        ))}
      </div>
      <ActivitiesSection />
      <StudentLifeCTA />
    </>
  )
}
