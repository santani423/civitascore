/**
 * Landing page contact & site configuration — set in frontend/.env
 * (see .env.example). Every value is optional: when a contact channel is
 * missing the page says so honestly instead of showing a made-up address.
 */
function clean(value: unknown): string | null {
  const text = typeof value === 'string' ? value.trim() : ''
  return text === '' ? null : text
}

const email = clean(import.meta.env.VITE_LANDING_CONTACT_EMAIL)

export const landingConfig = {
  /** Receives demo requests and contact messages (opened as a pre-filled email draft). */
  contactEmail: email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? email : null,
  /** Optional phone number shown in the footer, e.g. "+62 21 0000 0000". */
  contactPhone: clean(import.meta.env.VITE_LANDING_CONTACT_PHONE),
  /** Production origin, e.g. "https://one.example.ac.id" — enables canonical/og:url tags. */
  siteUrl: clean(import.meta.env.VITE_LANDING_SITE_URL)?.replace(/\/+$/, '') ?? null,
}
