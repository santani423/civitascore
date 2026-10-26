import { matchPath } from 'react-router-dom'
import { LANDING_PATHS } from '../../paths'
import { FacultyDirectoryPage } from './FacultyDirectoryPage'
import { FacultyDetailPage } from './FacultyDetailPage'

/** /fakultas and /fakultas/:slug, loaded as one lazy chunk by LandingApp. `path` is normalized. */
export default function FacultyPages({ path }: { path: string }) {
  if (path === LANDING_PATHS.faculties) return <FacultyDirectoryPage />
  const slug = matchPath(`${LANDING_PATHS.faculties}/:slug`, path)?.params.slug
  return slug !== undefined ? <FacultyDetailPage key={slug} slug={slug} /> : null
}
