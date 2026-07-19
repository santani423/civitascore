/**
 * Builds a List page URL from a base path + filter params, dropping empty
 * values — used by dashboard summary cards and chart click-throughs so the
 * destination page opens pre-filtered to exactly the same set the card/bar
 * represents.
 */
export function buildDashboardLink(path: string, params: Record<string, string | number | null | undefined> = {}): string {
  const query = new URLSearchParams()

  for (const [key, value] of Object.entries(params)) {
    if (value === null || value === undefined || value === '') continue
    query.set(key, String(value))
  }

  const queryString = query.toString()
  return queryString ? `${path}?${queryString}` : path
}
