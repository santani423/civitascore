import { Link, useLocation } from 'react-router-dom'
import { ArrowRight, ArrowUpRight, Building2, CalendarDays, Clock, MapPin, Medal, Quote, Target } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { backToList } from '../../hooks/useFilterParams'
import { activitiesOf, findOrg, relatedOrgs, type Org, type OrgKind } from '../../data/student-affairs'
import { facultyName } from '../../data/faculties'
import { LANDING_PATHS, ORG_LIST_PATHS, STUDENT_LIFE_IDS, detailPath } from '../../paths'
import { Breadcrumbs } from '../../components/layout/Breadcrumbs'
import { SectionLink } from '../../components/layout/SectionLink'
import { buttonClass } from '../../components/ui/buttonClass'
import { Reveal } from '../../components/ui/Primitives'
import { CategoryBadge, DemoBadge } from '../../components/student-affairs/Badges'
import { CoverArt, OrgLogo } from '../../components/student-affairs/Covers'
import { ActivityCard, OrgCard } from '../../components/student-affairs/Cards'
import { BackLink, BulletList, DetailSection, InfoItem, InfoList, NotFoundState, Paragraphs, RelatedSection } from '../../components/student-affairs/Detail'
import { PhotoGallery } from '../../components/student-affairs/PhotoGallery'
import { orgStyle } from '../../components/student-affairs/styles'

/** /daftar-ormawa/:slug and /daftar-ukm/:slug — one profile template for every sample organization. */
export function OrgDetailPage({ kind, slug }: { kind: OrgKind; slug: string }) {
  const { m } = useLandingI18n()
  const org = findOrg(kind, slug)
  const nf = m.affairs.notFound[kind]
  const listPath = ORG_LIST_PATHS[kind]

  useDocumentMeta({
    title: fill(m.meta.pageTitle, { page: org ? org.name : nf.title }),
    description: org ? org.excerpt : nf.body,
    ogLocale: m.meta.ogLocale,
    path: detailPath(listPath, slug),
    robots: org ? undefined : 'noindex',
  })

  if (!org) {
    return (
      <NotFoundState
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav[kind], to: listPath }, { label: nf.title }]}
        title={nf.title}
        body={nf.body}
        backTo={listPath}
        backLabel={m.affairs[kind].back}
      />
    )
  }
  return <OrgProfile org={org} />
}

function OrgProfile({ org }: { org: Org }) {
  const { m } = useLandingI18n()
  const { state } = useLocation()
  const a = m.affairs
  const o = a.org
  const copy = a[org.kind]
  const listPath = ORG_LIST_PATHS[org.kind]
  const backTo = backToList(listPath, state)
  const style = orgStyle(org)
  const category = org.kind === 'ormawa' ? a.categories.ormawa[org.category] : a.categories.ukm[org.category]
  const faculty = org.kind === 'ormawa' && org.faculty ? facultyName(org.faculty) : null
  const facultyPath = org.kind === 'ormawa' && org.faculty ? detailPath(LANDING_PATHS.faculties, org.faculty) : null
  const activities = activitiesOf(org)
  const gallery = org.photos?.slice(1) ?? []
  const related = relatedOrgs(org)
  const CategoryIcon = style.icon

  return (
    <article aria-labelledby="org-title">
      <header className="relative overflow-hidden border-b border-lp-line bg-lp-surface pb-12 pt-24 sm:pb-14 sm:pt-28 lg:pt-32">
        <div aria-hidden className="lp-grid-bg pointer-events-none absolute inset-x-0 top-0 h-96" />
        <div className="lp-container relative">
          <Breadcrumbs
            items={[
              { label: m.nav.home, to: LANDING_PATHS.home },
              { label: m.nav[org.kind], to: backTo },
              { label: org.name, lang: 'id' },
            ]}
          />
          <Reveal className="mt-6">
            <CoverArt style={style} photo={org.photos?.[0]} size="hero" priority sizes="(min-width: 1216px) 1152px, 100vw" className="h-40 rounded-lp-xl border border-lp-line sm:h-52 lg:h-60" />
            <div className="relative -mt-12 px-4 sm:-mt-14 sm:px-8">
              <OrgLogo org={org} size="lg" className="ring-4 ring-lp-surface" />
            </div>
            <div className="mt-5 sm:px-8">
              <div className="flex flex-wrap items-center gap-2">
                <CategoryBadge label={category} className="bg-lp-brand-soft text-lp-brand-text ring-transparent" />
                {org.kind === 'ormawa' && <CategoryBadge label={faculty ?? o.universityLevel} lang={faculty ? 'id' : undefined} />}
                <DemoBadge />
              </div>
              <h1 id="org-title" lang="id" className="lp-h2 mt-4 text-lp-fg sm:text-[2.5rem]">
                {org.name}
              </h1>
              <p lang="id" className="lp-lead mt-4 max-w-3xl text-lp-muted">
                {org.excerpt}
              </p>
            </div>
          </Reveal>
        </div>
      </header>

      <div className="lp-container grid gap-12 py-14 sm:py-16 lg:grid-cols-12 lg:gap-14 lg:py-20">
        <div className="space-y-14 lg:col-span-8">
          <DetailSection id="org-profile" title={o.profile}>
            <Paragraphs items={org.description} />
          </DetailSection>

          {(org.vision || (org.mission && org.mission.length > 0)) && (
            <DetailSection id="org-vision" title={o.visionMission}>
              <div className="grid gap-5 md:grid-cols-5">
                {org.vision && (
                  <figure className="relative overflow-hidden rounded-lp-lg bg-lp-band p-6 text-lp-band-fg md:col-span-2">
                    <Quote size={28} aria-hidden className="text-lp-accent" />
                    <figcaption className="mt-3 text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-band-muted">{o.vision}</figcaption>
                    <blockquote lang="id" className="mt-2 font-display text-[1.125rem] font-semibold leading-snug">
                      {org.vision}
                    </blockquote>
                  </figure>
                )}
                {org.mission && org.mission.length > 0 && (
                  <div className={`rounded-lp-lg border border-lp-line bg-lp-surface p-6 ${org.vision ? 'md:col-span-3' : 'md:col-span-5'}`}>
                    <h3 className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{o.mission}</h3>
                    <BulletList items={org.mission} className="mt-4" />
                  </div>
                )}
              </div>
            </DetailSection>
          )}

          <DetailSection id="org-programs" title={o.programs}>
            <ul className="grid gap-4 sm:grid-cols-2">
              {org.programs.map((program) => (
                <li key={program.title} className="rounded-lp-lg border border-lp-line bg-lp-surface p-5 sm:p-6">
                  <span aria-hidden className="flex h-10 w-10 items-center justify-center rounded-lp-sm bg-lp-brand-soft text-lp-brand-text">
                    <CategoryIcon size={19} />
                  </span>
                  <h3 lang="id" className="lp-h4 mt-4 text-lp-fg">
                    {program.title}
                  </h3>
                  <p lang="id" className="lp-small mt-1.5 text-lp-muted">
                    {program.description}
                  </p>
                </li>
              ))}
            </ul>
          </DetailSection>

          {org.kind === 'ukm' && org.training && org.training.length > 0 && (
            <DetailSection id="org-training" title={o.training}>
              <ul className="divide-y divide-lp-line overflow-hidden rounded-lp-lg border border-lp-line bg-lp-surface">
                {org.training.map((session) => (
                  <li key={`${session.day}-${session.time}`} lang="id" className="grid gap-1 px-5 py-4 sm:grid-cols-[8rem_9rem_minmax(0,1fr)] sm:items-center sm:gap-4">
                    <span className="inline-flex items-center gap-2 font-semibold text-lp-fg">
                      <CalendarDays size={16} aria-hidden className="text-lp-brand-text" />
                      {session.day}
                    </span>
                    <span className="lp-tabular inline-flex items-center gap-2 text-[0.9375rem] text-lp-muted">
                      <Clock size={16} aria-hidden className="text-lp-subtle" />
                      {session.time}
                    </span>
                    <span className="inline-flex items-start gap-2 text-[0.9375rem] text-lp-muted">
                      <MapPin size={16} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
                      {session.place}
                    </span>
                  </li>
                ))}
              </ul>
              <p className="lp-small mt-3 text-lp-subtle">{o.trainingNote}</p>
            </DetailSection>
          )}

          {org.achievements && org.achievements.length > 0 && (
            <DetailSection id="org-achievements" title={o.achievements}>
              <ul className="space-y-3">
                {org.achievements.map((item) => (
                  <li key={item.title} className="flex items-start gap-4 rounded-lp-lg border border-lp-line bg-lp-surface p-4 sm:p-5">
                    <span aria-hidden className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-lp-gold/15 text-lp-gold-text">
                      <Medal size={19} />
                    </span>
                    <span>
                      <span className="lp-tabular block text-[0.8125rem] font-semibold text-lp-brand-text">{item.year}</span>
                      <span lang="id" className="mt-0.5 block text-[0.9375rem] font-medium text-lp-fg">
                        {item.title}
                      </span>
                    </span>
                  </li>
                ))}
              </ul>
            </DetailSection>
          )}

          <DetailSection id="org-activities" title={fill(o.activities, { name: org.name })}>
            {activities.length > 0 ? (
              <ul className="grid gap-5 sm:grid-cols-2">
                {activities.map((activity) => (
                  <li key={activity.slug} className="flex">
                    <ActivityCard activity={activity} />
                  </li>
                ))}
              </ul>
            ) : (
              <div className="flex flex-col gap-4 rounded-lp-lg border border-dashed border-lp-strong bg-lp-surface p-5 sm:flex-row sm:items-center sm:justify-between">
                <p className="lp-small text-lp-muted">{fill(o.noActivities, { name: org.name })}</p>
                <Link to={LANDING_PATHS.activities} className={buttonClass('secondary', 'sm', 'group shrink-0')}>
                  {o.allActivities}
                  <ArrowRight size={15} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
                </Link>
              </div>
            )}
          </DetailSection>

          {gallery.length > 0 && (
            <DetailSection id="org-gallery" title={o.gallery}>
              <PhotoGallery photos={gallery} />
            </DetailSection>
          )}

          <BackLink to={backTo}>{copy.back}</BackLink>
        </div>

        <aside aria-labelledby="org-summary" className="lg:col-span-4">
          <div className="rounded-lp-xl border border-lp-line bg-lp-surface p-6 shadow-lp-md lg:sticky lg:top-[calc(var(--lp-nav-h)+1.5rem)]">
            <h2 id="org-summary" className="lp-h4 text-lp-fg">
              {o.summary}
            </h2>
            <InfoList className="mt-5">
              <InfoItem icon={CategoryIcon} label={o.category}>
                {category}
              </InfoItem>
              {org.kind === 'ormawa' && (
                <InfoItem icon={Building2} label={o.affiliation}>
                  {faculty && facultyPath ? (
                    <Link to={facultyPath} lang="id" className="font-medium text-lp-fg underline decoration-lp-strong underline-offset-[0.2em] transition-colors hover:text-lp-brand-text">
                      {faculty}
                    </Link>
                  ) : (
                    o.universityLevel
                  )}
                </InfoItem>
              )}
              <InfoItem icon={Target} label={o.focus}>
                <ul className="mt-1 flex flex-wrap gap-1.5">
                  {org.focus.map((tag) => (
                    <li key={tag} lang="id" className="rounded-full bg-lp-sunken px-2.5 py-1 text-[0.8125rem] font-medium text-lp-muted">
                      {tag}
                    </li>
                  ))}
                </ul>
              </InfoItem>
            </InfoList>

            {org.socialLinks && org.socialLinks.length > 0 && (
              <div className="mt-6 border-t border-lp-line pt-5">
                <h3 className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{o.social}</h3>
                <ul className="mt-3 flex flex-wrap gap-2">
                  {org.socialLinks.map((link) => (
                    <li key={link.href}>
                      <a
                        href={link.href}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="group inline-flex min-h-11 items-center gap-1.5 rounded-full border border-lp-strong px-4 text-[0.875rem] font-semibold text-lp-fg transition-colors hover:border-lp-brand/40 hover:text-lp-brand-text"
                      >
                        {link.label}
                        <ArrowUpRight size={15} aria-hidden />
                        <span className="sr-only"> {m.studentLife.contact.newTab}</span>
                      </a>
                    </li>
                  ))}
                </ul>
              </div>
            )}

            <div className="mt-6 rounded-lp bg-lp-sunken p-4">
              <p className="text-[0.875rem] font-semibold text-lp-fg">{o.contactTitle}</p>
              <p className="lp-small mt-1 text-lp-muted">{o.contactBody}</p>
              <SectionLink section={STUDENT_LIFE_IDS.contact} path={LANDING_PATHS.studentLife} className={buttonClass('secondary', 'sm', 'mt-4 w-full')}>
                {o.contactCta}
              </SectionLink>
            </div>
          </div>
        </aside>
      </div>

      {related.length > 0 && (
        <RelatedSection titleId="org-related" title={copy.related} allTo={listPath} allLabel={copy.relatedAll}>
          <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
            {related.map((item) => (
              <li key={item.slug} className="flex">
                <OrgCard org={item} />
              </li>
            ))}
          </ul>
        </RelatedSection>
      )}
    </article>
  )
}
