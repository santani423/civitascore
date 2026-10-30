import { create } from 'zustand'
import { persist } from 'zustand/middleware'

/** Favorites of each list are kept apart by prefix, e.g. `alumni:<slug>`. */
export type FavoriteId = `${'alumni' | 'event' | 'opportunity'}:${string}`

interface AlumniState {
  favorites: FavoriteId[]
  /** Alumni slugs with a (simulated) connection request. */
  connections: string[]
  /** Event slug → the ticket code shown on the (simulated) confirmation. */
  registrations: Record<string, string>
  /** Opportunity slugs with a (simulated) application. */
  applications: string[]
  toggleFavorite: (id: FavoriteId) => void
  toggleConnection: (slug: string) => void
  register: (slug: string) => string
  apply: (slug: string) => void
}

/**
 * What a backend would own once the alumni module exists: nothing here leaves
 * the browser. Persisted so the demo stays continuous across reloads.
 */
export const useAlumniStore = create<AlumniState>()(
  persist(
    (set, get) => ({
      favorites: [],
      connections: [],
      registrations: {},
      applications: [],
      toggleFavorite: (id) => set((s) => ({ favorites: s.favorites.includes(id) ? s.favorites.filter((x) => x !== id) : [...s.favorites, id] })),
      toggleConnection: (slug) => set((s) => ({ connections: s.connections.includes(slug) ? s.connections.filter((x) => x !== slug) : [...s.connections, slug] })),
      register: (slug) => {
        const existing = get().registrations[slug]
        if (existing) return existing
        const code = `OAJ-${Date.now().toString(36).slice(-5).toUpperCase()}`
        set((s) => ({ registrations: { ...s.registrations, [slug]: code } }))
        return code
      },
      apply: (slug) => set((s) => ({ applications: s.applications.includes(slug) ? s.applications : [...s.applications, slug] })),
    }),
    { name: 'civitas-one-atma-alumni' },
  ),
)
