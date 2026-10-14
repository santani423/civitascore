import { adminClient, idempotencyKey } from '@/pmb/api/http'
import type { Paginated } from '@/pmb/api/types'

/**
 * Klien admin PMB (`/pmb/admin/*`) di atas apiClient staf. Endpoint daftar
 * memakai parameter datar (status, search, period_id, ...) dan meta
 * paginasi standar.
 */
const BASE = '/pmb/admin'

export type Params = Record<string, string | number | boolean | undefined | null>

function clean(params?: Params): Record<string, string | number | boolean> | undefined {
  if (!params) return undefined

  return Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '')) as Record<string, string | number | boolean>
}

export const pmbAdmin = {
  async get<T>(path: string, params?: Params): Promise<T> {
    return (await adminClient.get<{ data: T }>(`${BASE}/${path}`, { params: clean(params) })).data.data
  },
  async list<T>(path: string, params?: Params): Promise<Paginated<T>> {
    const { data } = await adminClient.get<{ data: T[]; meta: Paginated<T>['meta'] }>(`${BASE}/${path}`, { params: clean(params) })

    return { data: data.data, meta: data.meta }
  },
  async post<T = unknown>(path: string, body?: unknown, options: { idempotent?: boolean } = {}): Promise<T> {
    const headers = options.idempotent ? { 'Idempotency-Key': idempotencyKey() } : undefined

    return (await adminClient.post<{ data: T }>(`${BASE}/${path}`, body, { headers })).data.data
  },
  async put<T = unknown>(path: string, body?: unknown): Promise<T> {
    return (await adminClient.put<{ data: T }>(`${BASE}/${path}`, body)).data.data
  },
  async patch<T = unknown>(path: string, body?: unknown): Promise<T> {
    return (await adminClient.patch<{ data: T }>(`${BASE}/${path}`, body)).data.data
  },
  async del<T = unknown>(path: string): Promise<T> {
    return (await adminClient.delete<{ data: T }>(`${BASE}/${path}`)).data.data
  },
  /** Membuka URL bertanda tangan (unduhan) dari endpoint yang mengembalikan {url}. */
  async open(path: string, params?: Params): Promise<void> {
    const { url } = await pmbAdmin.get<{ url: string }>(path, params)
    window.open(url, '_blank', 'noopener')
  },
}

/** Approval engine bersama (/approval-request-steps) — dipakai halaman approval PMB. */
export const approvalSteps = {
  approve: (stepId: string, comment?: string) => adminClient.post(`/approval-request-steps/${stepId}/approve`, { comment }),
  reject: (stepId: string, comment: string) => adminClient.post(`/approval-request-steps/${stepId}/reject`, { comment }),
  returnToRequester: (stepId: string, comment: string) => adminClient.post(`/approval-request-steps/${stepId}/return`, { comment }),
}
