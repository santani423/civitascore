import { matchPath } from 'react-router-dom'
import { LANDING_PATHS } from '../../paths'
import { AlumniNav } from '../../components/alumni/AlumniNav'
import { OverviewPage } from './OverviewPage'
import { DirectoryPage } from './DirectoryPage'
import { ProfilePage } from './ProfilePage'
import { EventsPage } from './EventsPage'
import { CareersPage } from './CareersPage'
import { StoriesPage } from './StoriesPage'
import { StoryDetailPage } from './StoryDetailPage'
import { ContributionsPage } from './ContributionsPage'
import '../../styles/alumni.css'

function route(path: string) {
  switch (path) {
    case LANDING_PATHS.alumni:
      return <OverviewPage />
    case LANDING_PATHS.alumniDirectory:
      return <DirectoryPage />
    case LANDING_PATHS.alumniEvents:
      return <EventsPage />
    case LANDING_PATHS.alumniCareers:
      return <CareersPage />
    case LANDING_PATHS.alumniStories:
      return <StoriesPage />
    case LANDING_PATHS.alumniContributions:
      return <ContributionsPage />
  }
  const slug = (pattern: string) => matchPath(`${pattern}/:slug`, path)?.params.slug
  const person = slug(LANDING_PATHS.alumniDirectory)
  if (person !== undefined) return <ProfilePage key={person} slug={person} />
  const story = slug(LANDING_PATHS.alumniStories)
  if (story !== undefined) return <StoryDetailPage key={story} slug={story} />
  return null
}

/**
 * Komunitas Alumni, loaded as one lazy chunk by LandingApp. UI only: every
 * page runs on the sample data in data/alumni and frontend state, and none of
 * them calls the API. `path` is already normalized (no trailing slash).
 */
export default function AlumniPages({ path }: { path: string }) {
  return (
    <div className="pt-[var(--lp-nav-h)]">
      <AlumniNav />
      {route(path)}
    </div>
  )
}
