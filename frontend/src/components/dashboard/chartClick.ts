/**
 * Recharts click handlers receive slightly different shapes per chart type
 * (Bar/Pie pass an enriched props object with the original datum under
 * `payload`; chart-level onClick passes `activePayload` entries). This
 * normalizes both back to the plain datum the dashboard passed in.
 */
export function datumOf<T>(entry: unknown): T | null {
  if (!entry || typeof entry !== 'object') return null

  const withPayload = entry as { payload?: T }
  return (withPayload.payload ?? (entry as T)) as T
}

/** Chart-level onClick (Area/Line) — ambil datum titik aktif dari state Recharts. */
export function activeDatumOf<T>(state: unknown): T | null {
  const typed = state as { activePayload?: Array<{ payload?: T }> } | null
  return typed?.activePayload?.[0]?.payload ?? null
}
