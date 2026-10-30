import { LANDING_PATHS, detailPath } from '../paths'

export const alumnusPath = (slug: string) => detailPath(LANDING_PATHS.alumniDirectory, slug)
export const storyPath = (slug: string) => detailPath(LANDING_PATHS.alumniStories, slug)
/** The events page with this event's dialog open. */
export const eventPath = (slug: string) => `${LANDING_PATHS.alumniEvents}?acara=${slug}`
/** The careers page with this opportunity's dialog open. */
export const opportunityPath = (slug: string) => `${LANDING_PATHS.alumniCareers}?peluang=${slug}`
