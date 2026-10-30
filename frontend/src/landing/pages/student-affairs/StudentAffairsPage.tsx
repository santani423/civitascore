import { matchPath } from 'react-router-dom'
import { LANDING_PATHS } from '../../paths'
import { ActivitiesPage } from './ActivitiesPage'
import { ActivityDetailPage } from './ActivityDetailPage'
import { OrgDirectoryPage } from './OrgDirectoryPage'
import { OrgDetailPage } from './OrgDetailPage'

/**
 * Kegiatan Mahasiswa, Ormawa and UKM (lists + detail pages), loaded as one
 * lazy chunk by LandingApp so the home page doesn't carry them. `path` is
 * already normalized (no trailing slash).
 */
export default function StudentAffairsPage({ path }: { path: string }) {
  if (path === LANDING_PATHS.activities) return <ActivitiesPage />
  if (path === LANDING_PATHS.ormawa) return <OrgDirectoryPage kind="ormawa" />
  if (path === LANDING_PATHS.ukm) return <OrgDirectoryPage kind="ukm" />

  const slug = (pattern: string) => matchPath(`${pattern}/:slug`, path)?.params.slug
  const activity = slug(LANDING_PATHS.activities)
  if (activity !== undefined) return <ActivityDetailPage key={activity} slug={activity} />
  const ormawa = slug(LANDING_PATHS.ormawa)
  if (ormawa !== undefined) return <OrgDetailPage key={`ormawa-${ormawa}`} kind="ormawa" slug={ormawa} />
  const ukm = slug(LANDING_PATHS.ukm)
  if (ukm !== undefined) return <OrgDetailPage key={`ukm-${ukm}`} kind="ukm" slug={ukm} />
  return null
}
