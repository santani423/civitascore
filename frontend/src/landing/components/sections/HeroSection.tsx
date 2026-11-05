import { Link } from 'react-router-dom'
import { ArrowRight, GraduationCap, MapPin } from 'lucide-react'
import { PMB } from '@/pmb/routes'
import { useLandingI18n } from '../../i18n'
import { PHOTOS } from '../../data/photos'
import { SECTION_IDS } from '../../paths'
import { buttonClass } from '../ui/buttonClass'
import { SectionLink } from '../layout/SectionLink'
import { useAppEntry } from '../../hooks/useAppEntry'

/**
 * Charcoal hero with the Semanggi campus tower. The photo is a normal block
 * on phones (text overlaps its faded bottom edge) and an absolutely placed
 * right-hand panel from `md` up (fading into the text side).
 */
export function HeroSection() {
  const { m } = useLandingI18n()
  const entry = useAppEntry()
  const photo = PHOTOS.campusTower

  return (
    <section id={SECTION_IDS.home} aria-labelledby="hero-title" className="lp-on-band relative isolate overflow-hidden bg-lp-band pt-16 lg:pt-[4.5rem]">
      <div className="relative h-72 sm:h-96 md:absolute md:inset-0 md:h-auto md:w-full">
        <img
          src={photo.src}
          srcSet={photo.srcSet}
          sizes="(min-width: 768px) 60vw, 100vw"
          width={photo.width}
          height={photo.height}
          alt={m.photos.campusTower}
          fetchPriority="high"
          decoding="async"
          className="lp-hero-photo h-full w-full object-cover object-[50%_22%]"
        />
        <p className="absolute bottom-4 right-4 hidden items-center gap-1.5 rounded-full bg-lp-band/70 px-3 py-1.5 text-[0.75rem] font-medium text-lp-band-fg backdrop-blur md:inline-flex">
          <MapPin size={13} aria-hidden className="text-lp-accent" />
          {m.hero.photoCaption}
        </p>
      </div>
      {/* Orange sweep in the corner, after the university website's hero. */}
      <div aria-hidden data-lp-decor className="lp-sweep-br absolute -bottom-px right-0 hidden h-24 w-48 bg-lp-accent md:block lg:h-32 lg:w-64" />

      <div className="lp-container relative -mt-20 pb-24 sm:-mt-24 md:mt-0 md:flex md:min-h-[34rem] md:items-center md:py-24 lg:min-h-[38rem] lg:pb-32">
        <div className="max-w-xl lg:max-w-[38rem]">
          <p className="lp-eyebrow text-lp-accent">{m.hero.eyebrow}</p>
          <h1 id="hero-title" className="lp-display mt-4 text-lp-band-fg">
            {m.hero.titleLead} <span className="text-lp-accent">{m.hero.titleAccent}</span>
          </h1>
          <p className="lp-lead mt-6 max-w-lg text-lp-band-muted">{m.hero.subtitle}</p>
          <div className="mt-9 flex flex-col gap-3 min-[420px]:flex-row min-[420px]:flex-wrap">
            <Link to={entry.to} className={buttonClass('primary', 'lg')}>
              {entry.authenticated ? m.nav.openDashboard : m.hero.primaryCta}
              <ArrowRight size={18} aria-hidden />
            </Link>
            <SectionLink section={SECTION_IDS.services} className={buttonClass('bandOutline', 'lg')}>
              {m.hero.secondaryCta}
            </SectionLink>
          </div>
          {/* Applicants don't sign in here: they register on the PMB website with their own account. */}
          <p className="mt-6 flex flex-wrap items-center gap-x-2 text-[0.9063rem] text-lp-band-muted">
            <GraduationCap size={17} aria-hidden className="shrink-0 text-lp-accent" />
            {m.hero.admissionsLead}
            <Link
              to={PMB.home}
              className="group inline-flex min-h-11 items-center gap-1.5 rounded-lp-sm font-semibold text-lp-band-fg underline decoration-lp-accent decoration-2 underline-offset-4 transition-colors hover:decoration-lp-band-fg"
            >
              {m.hero.admissionsCta}
              <ArrowRight size={15} aria-hidden className="transition-transform group-hover:translate-x-0.5" />
            </Link>
          </p>
          <SectionLink
            section={SECTION_IDS.about}
            className="mt-10 hidden min-h-11 items-center gap-3 rounded-lp-sm text-[0.8125rem] font-medium text-lp-band-muted transition-colors hover:text-lp-band-fg md:inline-flex"
          >
            <span aria-hidden className="flex h-9 w-6 items-start justify-center rounded-full border-2 border-lp-band-fg/50 pt-1.5">
              <span className="lp-scroll-cue h-2 w-1 rounded-full bg-lp-accent" />
            </span>
            {m.hero.scrollCue}
          </SectionLink>
        </div>
      </div>
    </section>
  )
}
