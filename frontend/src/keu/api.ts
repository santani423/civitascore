import { apiClient } from '@/services/api'
import type { Paginated } from '@/keu/types'

/**
 * Klien KEU di atas apiClient staf (token, X-University-ID, normalisasi
 * error). `keuAdmin` → `/finance/*` (petugas), `keuStudent` →
 * `/student/finance/*` (mahasiswa; backend selalu meresolusi data milik
 * akun yang login).
 */
export type Params = Record<string, string | number | boolean | undefined | null>

function clean(params?: Params): Record<string, string | number | boolean> | undefined {
  if (!params) return undefined

  return Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '')) as Record<string, string | number | boolean>
}

/** Kunci idempotensi untuk aksi pembayaran (header Idempotency-Key). */
export function idempotencyKey(): string {
  return typeof crypto !== 'undefined' && 'randomUUID' in crypto ? crypto.randomUUID() : `${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function client(base: string) {
  // Path berawalan `/` dipakai apa adanya (mis. `/invoices` — endpoint pra-KEU), selain itu di bawah `base`.
  const url = (path: string) => (path.startsWith('/') ? path : `${base}/${path}`)

  return {
    async get<T>(path: string, params?: Params): Promise<T> {
      return (await apiClient.get<{ data: T }>(url(path), { params: clean(params) })).data.data
    },
    async list<T>(path: string, params?: Params): Promise<Paginated<T>> {
      const { data } = await apiClient.get<{ data: T[]; meta: Paginated<T>['meta'] }>(url(path), { params: clean(params) })

      return { data: data.data, meta: data.meta }
    },
    /** Mengembalikan `data` + `message` backend supaya pesan sukses tampil apa adanya. */
    async send<T = unknown>(method: 'post' | 'put' | 'delete', path: string, body?: unknown, headers?: Record<string, string>): Promise<{ data: T; message: string }> {
      const response = method === 'delete'
        ? await apiClient.delete<{ data: T; message: string }>(url(path), { headers })
        : await apiClient[method]<{ data: T; message: string }>(url(path), body, { headers })

      return { data: response.data.data, message: response.data.message }
    },
    async post<T = unknown>(path: string, body?: unknown, headers?: Record<string, string>): Promise<{ data: T; message: string }> {
      return this.send<T>('post', path, body, headers)
    },
    async put<T = unknown>(path: string, body?: unknown): Promise<{ data: T; message: string }> {
      return this.send<T>('put', path, body)
    },
    /** Unduh CSV/PDF sebagai blob lalu picu unduhan browser. */
    async download(path: string, params: Params, filename: string): Promise<void> {
      const response = await apiClient.get(url(path), { params: clean(params), responseType: 'blob' })
      const objectUrl = URL.createObjectURL(response.data as Blob)
      const anchor = document.createElement('a')
      anchor.href = objectUrl
      anchor.download = filename
      document.body.appendChild(anchor)
      anchor.click()
      anchor.remove()
      URL.revokeObjectURL(objectUrl)
    },
    /** Buka PDF (kuitansi) di tab baru. */
    async openPdf(path: string): Promise<void> {
      const response = await apiClient.get(url(path), { responseType: 'blob' })
      const objectUrl = URL.createObjectURL(response.data as Blob)
      window.open(objectUrl, '_blank', 'noopener')
      setTimeout(() => URL.revokeObjectURL(objectUrl), 60_000)
    },
  }
}

export const keuAdmin = client('/finance')
export const keuStudent = client('/student/finance')

/** Unggah berkas lewat FileManagement (POST /file-uploads) lalu kembalikan id-nya. */
export async function uploadFile(file: File): Promise<string> {
  const form = new FormData()
  form.append('file', file)
  form.append('is_public', 'false')
  const response = await apiClient.post<{ data: { id: string } }>('/file-uploads', form, { headers: { 'Content-Type': 'multipart/form-data' } })

  return response.data.data.id
}
