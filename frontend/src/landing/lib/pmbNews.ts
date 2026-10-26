import { useEffect, useState } from 'react'
import type { PublicContent } from '@/pmb/api/public'
import { resolveCampusCode } from '@/pmb/lib/tenant'

/**
 * Admissions news from the existing public PMB endpoint
 * (GET /pmb/public/contents?type=news — the same request the PMB website
 * makes through pmb/api/public.ts). Uses plain fetch so the landing bundle
 * doesn't pull in axios and the app's API client; the endpoint, tenant
 * header and response envelope are unchanged.
 */
const API_BASE = (import.meta.env.VITE_API_BASE_URL as string | undefined) ?? 'http://localhost:8000/api/v1'
const TIMEOUT_MS = 10_000

export type NewsItem = Pick<PublicContent, 'id' | 'slug' | 'title' | 'category' | 'excerpt' | 'published_at'>

export type NewsState = { status: 'idle' | 'loading' | 'error' } | { status: 'ready'; items: NewsItem[] }

async function fetchPmbNews(signal: AbortSignal): Promise<NewsItem[]> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  const campus = resolveCampusCode()
  if (campus) headers['X-University-Code'] = campus

  const response = await fetch(`${API_BASE}/pmb/public/contents?type=news`, { headers, signal })
  if (!response.ok) throw new Error(`HTTP ${response.status}`)
  const body = (await response.json()) as { data?: unknown }
  return Array.isArray(body.data) ? (body.data as NewsItem[]) : []
}

const time = (item: NewsItem) => (item.published_at ? Date.parse(item.published_at) || 0 : 0)

/** Latest `limit` items, newest first (the API orders by sort_order first). Starts once `enabled` turns true. */
export function usePmbNews(enabled: boolean, limit: number): NewsState {
  const [state, setState] = useState<NewsState>({ status: 'idle' })

  useEffect(() => {
    if (!enabled) return
    // `cancelled` = unmounted / re-run; a timeout abort still counts as an error.
    let cancelled = false
    const controller = new AbortController()
    const timer = window.setTimeout(() => controller.abort(), TIMEOUT_MS)
    setState({ status: 'loading' })
    fetchPmbNews(controller.signal)
      .then((items) => {
        if (cancelled) return
        const latest = items.filter((item) => item.slug && item.title).sort((a, b) => time(b) - time(a)).slice(0, limit)
        setState({ status: 'ready', items: latest })
      })
      .catch(() => {
        if (!cancelled) setState({ status: 'error' })
      })
      .finally(() => window.clearTimeout(timer))
    return () => {
      cancelled = true
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [enabled, limit])

  return state
}
