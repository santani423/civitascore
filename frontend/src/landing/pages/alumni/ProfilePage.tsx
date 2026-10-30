import { useState, type FormEvent } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { ArrowRight, BookOpenText, Building2, CalendarCheck, CalendarDays, Check, GraduationCap, Globe, Layers, MapPin, Send, Trophy, UserPlus } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { backToList } from '../../hooks/useFilterParams'
import { findAlumnus, relatedAlumni, storyOf, type Alumnus } from '../../data/alumni'
import { facultyName } from '../../data/faculties'
import { PHOTOS } from '../../data/photos'
import { LANDING_PATHS, detailPath } from '../../paths'
import { alumnusPath, storyPath } from '../../lib/alumniPaths'
import { useAlumniStore } from '../../store/alumni'
import { useLandingUi } from '../../store/ui'
import { Breadcrumbs } from '../../components/layout/Breadcrumbs'
import { Button } from '../../components/ui/Button'
import { buttonClass } from '../../components/ui/buttonClass'
import { Dialog } from '../../components/ui/Overlay'
import { Reveal } from '../../components/ui/Primitives'
import { DemoBadge } from '../../components/student-affairs/Badges'
import { BackLink, DetailSection, InfoItem, InfoList, NotFoundState, Paragraphs, RelatedSection } from '../../components/student-affairs/Detail'
import { TextField } from '../../components/alumni/FormFields'
import { Portrait } from '../../components/alumni/Portrait'
import { ProfileCard } from '../../components/alumni/ProfileCard'
import { FavoriteButton, MentorBadge, ProfessionalBadge } from '../../components/alumni/Shared'

/** /komunitas-alumni/direktori/:slug — one template for every sample alumnus. */
export function ProfilePage({ slug }: { slug: string }) {
  const { m } = useLandingI18n()
  const person = findAlumnus(slug)
  const p = m.alumni.profile

  useDocumentMeta({
    title: fill(m.meta.pageTitle, { page: person ? person.name : p.notFoundTitle }),
    description: person ? person.bio[0] : p.notFoundBody,
    ogLocale: m.meta.ogLocale,
    path: alumnusPath(slug),
    robots: person ? undefined : 'noindex',
  })

  if (!person) {
    return (
      <NotFoundState
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.alumniDirectory, to: LANDING_PATHS.alumniDirectory }, { label: p.notFoundTitle }]}
        title={p.notFoundTitle}
        body={p.notFoundBody}
        backTo={LANDING_PATHS.alumniDirectory}
        backLabel={p.back}
      />
    )
  }
  return <Profile person={person} />
}

/** "Hubungi Alumni": a visual form only; nothing is sent anywhere. */
function ContactDialog({ person, open, onClose }: { person: Alumnus; open: boolean; onClose: () => void }) {
  const { m } = useLandingI18n()
  const p = m.alumni.profile
  const toast = useLandingUi((s) => s.toast)
  const [message, setMessage] = useState('')
  const [error, setError] = useState<string>()

  const submit = (e: FormEvent) => {
    e.preventDefault()
    if (!message.trim()) {
      setError(p.contactError)
      return
    }
    setError(undefined)
    setMessage('')
    onClose()
    toast(fill(p.contactToast, { name: person.name }), 'info')
  }

  return (
    <Dialog open={open} onClose={onClose} title={fill(p.contactTitle, { name: person.name })} description={p.contactNote} icon={<Portrait person={person} className="h-12 w-12 shrink-0 rounded-full" />}>
      <form onSubmit={submit} noValidate>
        <TextField label={p.contactMessage} value={message} onChange={setMessage} error={error} placeholder={p.contactPlaceholder} multiline />
        <div className="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <Button variant="secondary" onClick={onClose}>
            {m.common.close}
          </Button>
          <Button type="submit">
            <Send size={16} aria-hidden />
            {p.contactSend}
          </Button>
        </div>
      </form>
    </Dialog>
  )
}

function Timeline({ items }: { items: Array<{ key: string; title: string; org: string; period: string; note?: string }> }) {
  return (
    <ol className="relative space-y-1 border-l-2 border-lp-line pl-6">
      {items.map((item) => (
        <li key={item.key} className="relative py-3">
          <span aria-hidden className="absolute -left-[1.95rem] top-[1.15rem] h-3.5 w-3.5 rounded-full border-[3px] border-lp-canvas bg-lp-brand" />
          <p lang="id" className="text-[1rem] font-semibold leading-snug text-lp-fg">
            {item.title}
          </p>
          <p lang="id" className="mt-0.5 text-[0.9375rem] text-lp-muted">
            {item.org}
          </p>
          <p lang="id" className="lp-tabular mt-1 text-[0.8125rem] font-medium text-lp-subtle">
            {item.period}
          </p>
          {item.note && (
            <p lang="id" className="lp-small mt-1.5 text-lp-muted">
              {item.note}
            </p>
          )}
        </li>
      ))}
    </ol>
  )
}

function Profile({ person }: { person: Alumnus }) {
  const { m } = useLandingI18n()
  const { state } = useLocation()
  const p = m.alumni.profile
  const connected = useAlumniStore((s) => s.connections.includes(person.slug))
  const toggleConnection = useAlumniStore((s) => s.toggleConnection)
  const toast = useLandingUi((s) => s.toast)
  const [contactOpen, setContactOpen] = useState(false)
  const backTo = backToList(LANDING_PATHS.alumniDirectory, state)
  const faculty = facultyName(person.faculty)
  const story = storyOf(person)
  const related = relatedAlumni(person)
  const cover = PHOTOS.campusFacade

  const placeholderLink = () => toast(p.linkToast, 'info')

  return (
    <article aria-labelledby="profile-title">
      <header>
        <div className="lp-on-band relative isolate overflow-hidden bg-lp-band">
          <img
            src={cover.src}
            srcSet={cover.srcSet}
            sizes="100vw"
            width={cover.width}
            height={cover.height}
            alt=""
            decoding="async"
            className="absolute inset-0 -z-10 h-full w-full object-cover object-[50%_30%] opacity-30"
          />
          <div aria-hidden className="absolute inset-0 -z-10 bg-gradient-to-r from-lp-band via-lp-band/85 to-lp-band/40" />
          <div className="lp-container pb-28 pt-5 sm:pb-24 lg:pb-20 lg:pt-7">
            <Breadcrumbs tone="band" items={[{ label: m.nav.alumni, to: LANDING_PATHS.alumni }, { label: m.nav.alumniDirectory, to: backTo }, { label: person.name, lang: 'id' }]} />
          </div>
        </div>

        <div className="border-b border-lp-line bg-lp-surface">
          <div className="lp-container flex flex-col gap-5 pb-8 sm:flex-row sm:items-end sm:gap-8">
            <Reveal className="-mt-20 w-40 shrink-0 sm:w-52 lg:-mt-16">
              <Portrait person={person} className="aspect-[4/5] rounded-b-lp-lg rounded-t-full shadow-lp-xl ring-4 ring-lp-surface" />
            </Reveal>
            <Reveal delay={60} className="min-w-0 flex-1 sm:pt-6">
              <div className="flex flex-wrap items-center gap-2">
                <ProfessionalBadge status={person.status} />
                {person.mentor && <MentorBadge />}
                <DemoBadge />
              </div>
              <h1 id="profile-title" lang="id" className="lp-h2 mt-3 text-lp-fg sm:text-[2.25rem]">
                {person.name}
              </h1>
              <p lang="id" className="mt-1 text-[0.9375rem] text-lp-subtle">
                {person.degree}
              </p>
              <p lang="id" className="mt-3 text-[1.0625rem] font-semibold text-lp-fg">
                {person.position}
              </p>
              <p className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-[0.9375rem] text-lp-muted">
                <span lang="id" className="inline-flex items-center gap-1.5">
                  <Building2 size={15} aria-hidden className="text-lp-subtle" />
                  {person.company}
                </span>
                <span lang="id" className="inline-flex items-center gap-1.5">
                  <MapPin size={15} aria-hidden className="text-lp-subtle" />
                  {person.location}
                </span>
              </p>
            </Reveal>
            <Reveal delay={120} className="flex flex-wrap items-center gap-2 sm:shrink-0 sm:flex-col sm:items-stretch lg:flex-row lg:items-center">
              <Button className="flex-1 sm:flex-none" onClick={() => setContactOpen(true)}>
                <Send size={16} aria-hidden />
                {p.contact}
              </Button>
              <Button
                variant="secondary"
                className="flex-1 sm:flex-none"
                aria-pressed={connected}
                onClick={() => {
                  toggleConnection(person.slug)
                  toast(fill(connected ? p.disconnectedToast : p.connectedToast, { name: person.name }), connected ? 'info' : 'success')
                }}
              >
                {connected ? <Check size={16} aria-hidden className="text-lp-ok" /> : <UserPlus size={16} aria-hidden />}
                {connected ? p.connected : p.connect}
              </Button>
              <FavoriteButton id={`alumni:${person.slug}`} name={person.name} className="shadow-none" />
            </Reveal>
          </div>
        </div>
      </header>

      <div className="lp-container grid gap-12 py-12 sm:py-14 lg:grid-cols-12 lg:gap-14 lg:py-16">
        <div className="min-w-0 space-y-12 lg:col-span-8">
          <DetailSection id="profile-about" title={p.about}>
            <Paragraphs items={person.bio} />
          </DetailSection>

          <DetailSection id="profile-experience" title={p.experience}>
            <Timeline items={person.experience.map((item) => ({ key: `${item.role}-${item.period}`, title: item.role, org: item.company, period: item.period, note: item.note }))} />
          </DetailSection>

          <DetailSection id="profile-education" title={p.education}>
            <Timeline items={person.education.map((item) => ({ key: `${item.degree}-${item.period}`, title: item.degree, org: item.institution, period: item.period }))} />
          </DetailSection>

          <DetailSection id="profile-achievements" title={p.achievements}>
            <ul className="grid gap-3 sm:grid-cols-2">
              {person.achievements.map((item) => (
                <li key={item.title} className="flex gap-3 rounded-lp-lg border border-lp-line bg-lp-surface p-4">
                  <span aria-hidden className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
                    <Trophy size={18} />
                  </span>
                  <span className="min-w-0">
                    <span className="lp-tabular block text-[0.8125rem] font-semibold text-lp-brand-text">{item.year}</span>
                    <span lang="id" className="mt-0.5 block text-[0.9375rem] leading-snug text-lp-fg">
                      {item.title}
                    </span>
                  </span>
                </li>
              ))}
            </ul>
          </DetailSection>

          {story && (
            <Link
              to={storyPath(story.slug)}
              className="lp-on-band group block overflow-hidden rounded-lp-xl bg-lp-band p-6 text-lp-band-fg transition-shadow duration-300 hover:shadow-lp-lg sm:p-8"
            >
              <span className="lp-eyebrow inline-flex items-center gap-2 text-lp-accent">
                <BookOpenText size={15} aria-hidden />
                {p.storyLabel}
              </span>
              <span lang="id" className="lp-serif mt-3 block text-[1.5rem] leading-snug sm:text-[1.75rem]">
                {story.headline}
              </span>
              <span className="mt-4 inline-flex items-center gap-2 text-[0.9063rem] font-semibold text-lp-accent">
                {p.storyCta}
                <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
              </span>
            </Link>
          )}

          <BackLink to={backTo}>{p.back}</BackLink>
        </div>

        <aside aria-labelledby="profile-info" className="lg:col-span-4">
          <div className="rounded-lp-xl border border-lp-line bg-lp-surface p-6 shadow-lp-md lg:sticky lg:top-[calc(var(--lp-nav-h)+4.75rem)]">
            <h2 id="profile-info" className="lp-h4 text-lp-fg">
              {p.academic}
            </h2>
            <InfoList className="mt-5">
              <InfoItem icon={GraduationCap} label={p.faculty}>
                <Link to={detailPath(LANDING_PATHS.faculties, person.faculty)} lang="id" className="font-medium text-lp-fg underline decoration-lp-strong underline-offset-[0.2em] transition-colors hover:text-lp-brand-text">
                  {faculty}
                </Link>
              </InfoItem>
              <InfoItem icon={Layers} label={p.program}>
                <span lang="id">{person.program}</span>
              </InfoItem>
              <InfoItem icon={CalendarDays} label={p.entryYear}>
                {person.entryYear}
              </InfoItem>
              <InfoItem icon={CalendarCheck} label={p.gradYear}>
                {person.gradYear}
              </InfoItem>
            </InfoList>

            <h2 className="lp-h4 mt-6 border-t border-lp-line pt-6 text-lp-fg">{p.links}</h2>
            <ul className="mt-3 space-y-1">
              <li>
                <button type="button" onClick={placeholderLink} className="group flex min-h-11 w-full items-center gap-3 rounded-lp-sm px-2 py-1.5 text-left transition-colors hover:bg-lp-sunken">
                  <span aria-hidden className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lp-sm bg-lp-band font-display text-[0.8125rem] font-extrabold text-lp-band-fg">
                    in
                  </span>
                  <span className="min-w-0">
                    <span className="block text-[0.75rem] text-lp-subtle">LinkedIn</span>
                    <span className="block truncate text-[0.875rem] font-medium text-lp-fg group-hover:text-lp-brand-text">{person.linkedin}</span>
                  </span>
                </button>
              </li>
              {person.website && (
                <li>
                  <button type="button" onClick={placeholderLink} className="group flex min-h-11 w-full items-center gap-3 rounded-lp-sm px-2 py-1.5 text-left transition-colors hover:bg-lp-sunken">
                    <span aria-hidden className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lp-sm bg-lp-brand-soft text-lp-brand-text">
                      <Globe size={16} />
                    </span>
                    <span className="min-w-0">
                      <span className="block text-[0.75rem] text-lp-subtle">{p.website}</span>
                      <span className="block truncate text-[0.875rem] font-medium text-lp-fg group-hover:text-lp-brand-text">{person.website}</span>
                    </span>
                  </button>
                </li>
              )}
            </ul>
            <Link to={LANDING_PATHS.alumniDirectory} className={buttonClass('ghost', 'sm', 'mt-4 w-full')}>
              {p.browseAll}
            </Link>
          </div>
        </aside>
      </div>

      <RelatedSection titleId="profile-related" title={p.related} allTo={LANDING_PATHS.alumniDirectory} allLabel={p.browseAll}>
        <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
          {related.map((other) => (
            <li key={other.slug} className="flex">
              <ProfileCard person={other} />
            </li>
          ))}
        </ul>
      </RelatedSection>

      <ContactDialog person={person} open={contactOpen} onClose={() => setContactOpen(false)} />
    </article>
  )
}
