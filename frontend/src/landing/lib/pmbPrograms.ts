import { useEffect, useState } from 'react'
import type { PublicProgram } from '@/pmb/api/public'
import { resolveCampusCode } from '@/pmb/lib/tenant'
import { API_BASE_URL } from '@/services/apiBase'

/**
 * Study programs from the existing public PMB endpoint
 * (GET /pmb/public/programs — what the PMB website's "Program Studi" page
 * shows). Same approach as lib/pmbNews.ts: plain fetch, unchanged endpoint,
 * tenant header and envelope. Fetched once per visit and shared, so moving
 * from the faculty directory to a faculty page doesn't request it again.
 */
const TIMEOUT_MS = 10_000

export type StudyProgram = Pick<PublicProgram, 'slug' | 'name' | 'degree_level' | 'faculty' | 'summary' | 'accreditation'>

export type ProgramsState = { status: 'loading' | 'error' } | { status: 'ready'; items: StudyProgram[] }

let request: Promise<StudyProgram[]> | null = null

async function fetchPrograms(): Promise<StudyProgram[]> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  const campus = resolveCampusCode()
  if (campus) headers['X-University-Code'] = campus

  const controller = new AbortController()
  const timer = window.setTimeout(() => controller.abort(), TIMEOUT_MS)
  try {
    const response = await fetch(`${API_BASE_URL}/pmb/public/programs`, { headers, signal: controller.signal })
    if (!response.ok) throw new Error(`HTTP ${response.status}`)
    const body = (await response.json()) as { data?: unknown }
    const items = Array.isArray(body.data) ? (body.data as StudyProgram[]) : []
    return items.filter((p) => typeof p?.slug === 'string' && p.slug && typeof p.name === 'string' && p.name)
  } finally {
    window.clearTimeout(timer)
  }
}

function loadPrograms(): Promise<StudyProgram[]> {
  // A failed request is forgotten, so the next page that needs it tries again.
  request ??= fetchPrograms().catch((error: unknown) => {
    request = null
    throw error
  })
  return request
}

export function usePmbPrograms(): ProgramsState {
  const [state, setState] = useState<ProgramsState>({ status: 'loading' })

  useEffect(() => {
    let cancelled = false
    loadPrograms()
      .then((items) => !cancelled && setState({ status: 'ready', items }))
      .catch(() => !cancelled && setState({ status: 'error' }))
    return () => {
      cancelled = true
    }
  }, [])

  return state
}
