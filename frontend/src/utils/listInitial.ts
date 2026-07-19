/**
 * Picks only the allowed filter keys out of a URL's search params — used by
 * List pages to seed usePaginatedList's initialFilter when opened via a
 * dashboard card/chart link (e.g. /mahasiswa?status=active), so the page
 * arrives already filtered to the exact set the card counted.
 */
export function pickFilterParams(searchParams: URLSearchParams, keys: string[]): Record<string, string> {
  const filter: Record<string, string> = {}

  for (const key of keys) {
    const value = searchParams.get(key)
    if (value) filter[key] = value
  }

  return filter
}
