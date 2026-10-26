/**
 * Landing page contact & site configuration — set in frontend/.env
 * (see .env.example). Every value is optional: when a contact channel is
 * missing the page says so honestly instead of showing a made-up address.
 */
function clean(value: unknown): string | null {
  const text = typeof value === 'string' ? value.trim() : ''
  return text === '' ? null : text
}

/** An absolute http(s) URL or a same-site path ("/…"); anything else is ignored. */
function link(value: unknown): string | null {
  const text = clean(value)
  if (!text) return null
  if (text.startsWith('/')) return text.startsWith('//') ? null : text
  try {
    return ['http:', 'https:'].includes(new URL(text).protocol) ? text : null
  } catch {
    return null
  }
}

const email = clean(import.meta.env.VITE_LANDING_CONTACT_EMAIL)
const activitiesPillarUrl = link(import.meta.env.VITE_LANDING_ACTIVITIES_PILLAR_URL)

export const landingConfig = {
  /**
   * Student activities list, for "Jelajahi Kegiatan Mahasiswa" on /student-life-journey.
   * One Atma Jaya has no activity module yet, so this stays empty until one exists.
   */
  activitiesUrl: link(import.meta.env.VITE_LANDING_ACTIVITIES_URL),
  /**
   * The same list filtered to one pillar: must contain `{pillar}` (pillar slug,
   * see data/pillars.ts) or `{number}` (1–5) — a URL without either can't filter, so it's ignored.
   */
  activitiesPillarUrl: activitiesPillarUrl && /\{(pillar|number)\}/.test(activitiesPillarUrl) ? activitiesPillarUrl : null,
  /** Receives demo requests and contact messages (opened as a pre-filled email draft). */
  contactEmail: email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? email : null,
  /** Optional phone number shown in the footer, e.g. "+62 21 0000 0000". */
  contactPhone: clean(import.meta.env.VITE_LANDING_CONTACT_PHONE),
  /** Production origin, e.g. "https://one.example.ac.id" — enables canonical/og:url tags. */
  siteUrl: clean(import.meta.env.VITE_LANDING_SITE_URL)?.replace(/\/+$/, '') ?? null,
}
