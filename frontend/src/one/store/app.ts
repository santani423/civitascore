import { create } from 'zustand'
import { persist } from 'zustand/middleware'

/**
 * Frontend-only interaction state (what a backend would normally own).
 * Persisted so the demo feels continuous across reloads.
 */
interface ServiceRequest {
  id: string
  service: string
  note: string
  createdAt: string
  status: 'pending' | 'inReview' | 'approved'
}

interface AppState {
  readNotifications: string[]
  unreadOverrides: string[]
  bookmarks: string[]
  favorites: string[]
  submitted: Record<string, string>
  registered: string[]
  joined: string[]
  applied: string[]
  paid: string[]
  borrowed: string[]
  requests: ServiceRequest[]
  posts: Record<string, Array<{ id: string; body: string; at: string }>>

  toggle: (list: 'bookmarks' | 'favorites' | 'registered' | 'joined' | 'applied' | 'borrowed', id: string) => void
  add: (list: 'registered' | 'joined' | 'applied' | 'paid' | 'borrowed', id: string) => void
  markRead: (id: string, read: boolean) => void
  markAllRead: (ids: string[]) => void
  submit: (assignmentId: string) => void
  addRequest: (service: string, note: string) => void
  addPost: (courseId: string, body: string) => void
}

const addUnique = (list: string[], id: string) => (list.includes(id) ? list : [...list, id])

export const useApp = create<AppState>()(
  persist(
    (set) => ({
      readNotifications: [],
      unreadOverrides: [],
      bookmarks: ['news-2'],
      favorites: ['academic', 'schedule', 'attendance', 'finance', 'library', 'career', 'organizations'],
      submitted: {},
      registered: [],
      joined: ['org-3'],
      applied: [],
      paid: [],
      borrowed: [],
      requests: [
        { id: 'req-1', service: 'letter', note: 'Internship application at PT Nusantara Digital', createdAt: '2026-09-28', status: 'approved' },
        { id: 'req-2', service: 'it', note: 'Cannot connect to eduroam on laptop', createdAt: '2026-10-03', status: 'inReview' },
      ],
      posts: {},

      toggle: (list, id) =>
        set((s) => ({ [list]: s[list].includes(id) ? s[list].filter((x) => x !== id) : [...s[list], id] }) as Partial<AppState>),
      add: (list, id) => set((s) => ({ [list]: addUnique(s[list], id) }) as Partial<AppState>),
      markRead: (id, read) =>
        set((s) => ({
          readNotifications: read ? addUnique(s.readNotifications, id) : s.readNotifications.filter((x) => x !== id),
          unreadOverrides: read ? s.unreadOverrides.filter((x) => x !== id) : addUnique(s.unreadOverrides, id),
        })),
      markAllRead: (ids) =>
        set((s) => ({ readNotifications: Array.from(new Set([...s.readNotifications, ...ids])), unreadOverrides: [] })),
      submit: (assignmentId) => set((s) => ({ submitted: { ...s.submitted, [assignmentId]: new Date().toISOString() } })),
      addRequest: (service, note) =>
        set((s) => ({
          requests: [
            { id: `req-${Date.now()}`, service, note, createdAt: new Date().toISOString().slice(0, 10), status: 'pending' },
            ...s.requests,
          ],
        })),
      addPost: (courseId, body) =>
        set((s) => ({
          posts: {
            ...s.posts,
            [courseId]: [{ id: `p-${Date.now()}`, body, at: new Date().toISOString() }, ...(s.posts[courseId] ?? [])],
          },
        })),
    }),
    { name: 'civitas-one-state' },
  ),
)
