import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import { useLandingI18n } from '../i18n'
import { useDocumentMeta } from '../hooks/useDocumentSetup'
import { LANDING_PATHS } from '../paths'
import { HeroSection } from '../components/sections/HeroSection'
import { AudienceStrip } from '../components/sections/AudienceStrip'
import { FeaturesSection } from '../components/sections/FeaturesSection'
import { IntegrationSection } from '../components/sections/IntegrationSection'
import { ServicesSection } from '../components/sections/ServicesSection'
import { AcademicSection } from '../components/sections/AcademicSection'
import { InfoSection } from '../components/sections/InfoSection'
import { TuitionSection } from '../components/sections/TuitionSection'
import { FinalCTA } from '../components/sections/FinalCTA'

/**
 * One Atma Jaya home: hero → audience strip → about (advantages + integration)
 * → services → academic cycle → admissions news & access guide → postgraduate
 * tuition → CTA. The order matches the navbar (Beranda, Tentang Kami, Layanan,
 * Akademik, Informasi — which also covers the tuition section).
 */
export function HomePage() {
  const { m } = useLandingI18n()
  const { hash } = useLocation()
  useDocumentMeta({ title: m.meta.title, description: m.meta.description, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.home })

  // Arriving at /#section (shared link, or a link from the legal pages): jump there once rendered.
  useEffect(() => {
    if (!hash) return
    const frame = window.requestAnimationFrame(() => document.getElementById(decodeURIComponent(hash.slice(1)))?.scrollIntoView({ block: 'start' }))
    return () => window.cancelAnimationFrame(frame)
  }, [hash])

  return (
    <>
      <HeroSection />
      <AudienceStrip />
      <FeaturesSection />
      <IntegrationSection />
      <ServicesSection />
      <AcademicSection />
      <InfoSection />
      <TuitionSection />
      <FinalCTA />
    </>
  )
}
