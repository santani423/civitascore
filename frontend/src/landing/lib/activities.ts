import { landingConfig } from '../config'
import type { Pillar } from '../data/pillars'
import { fill } from '../i18n'

export interface ActivityTarget {
  href: string
  /** Another site (plain <a>) rather than a route of this app (<Link>, so auth guards still apply). */
  external: boolean
  /** True only when the destination really is narrowed to the given pillar. */
  filtered: boolean
}

function target(href: string, filtered: boolean): ActivityTarget {
  return { href, external: !href.startsWith('/'), filtered }
}

/**
 * Where the "Lihat Kegiatan" buttons go (VITE_LANDING_ACTIVITIES_* in .env).
 * With a pillar: the filtered list when configured, otherwise the unfiltered
 * list (`filtered: false`, so the label must not promise a filter).
 * `null` means no activity page exists yet — callers show that instead of a dead button.
 */
export function activityTarget(pillar?: Pillar): ActivityTarget | null {
  const { activitiesUrl, activitiesPillarUrl } = landingConfig
  if (pillar && activitiesPillarUrl) {
    return target(fill(activitiesPillarUrl, { pillar: encodeURIComponent(pillar.id), number: pillar.number }), true)
  }
  return activitiesUrl ? target(activitiesUrl, false) : null
}
