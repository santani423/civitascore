import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import { useLandingI18n } from '../i18n'
import { useDocumentMeta } from '../hooks/useDocumentSetup'
import { LANDING_PATHS } from '../paths'
import { HeroSection } from '../components/sections/HeroSection'
import { ValueProposition } from '../components/sections/ValueProposition'
import { PlatformOverview } from '../components/sections/PlatformOverview'
import { ModuleShowcase } from '../components/sections/ModuleShowcase'
import { InteractiveProductDemo } from '../components/sections/InteractiveProductDemo'
import { UserExperienceSection } from '../components/sections/UserExperienceSection'
import { BenefitsSection } from '../components/sections/BenefitsSection'
import { SecuritySection } from '../components/sections/SecuritySection'
import { AboutSection, ImplementationTimeline } from '../components/sections/AboutSection'
import { FAQSection } from '../components/sections/FAQSection'
import { FinalCTA } from '../components/sections/FinalCTA'

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
      <ValueProposition />
      <PlatformOverview />
      <ModuleShowcase />
      <InteractiveProductDemo />
      <UserExperienceSection />
      <BenefitsSection />
      <SecuritySection />
      <AboutSection />
      <ImplementationTimeline />
      <FAQSection />
      <FinalCTA />
    </>
  )
}
