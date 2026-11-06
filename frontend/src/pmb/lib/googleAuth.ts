import { pmbPublic } from '@/pmb/api/public'
import { useSite } from '@/pmb/components/SiteContext'

/**
 * Google Sign-In portal pendaftar — sisi SPA.
 *
 * Sebelum diarahkan ke Google, tab ini membuat `verifier` acak (disimpan di
 * sessionStorage, hanya tab ini) dan mengirim SHA-256-nya (`client_challenge`)
 * ke backend. Setelah Google → backend → /pmb/masuk/google#ticket=…, tiket hanya
 * bisa ditukar dengan verifier tersebut: tautan tiket/callback milik orang lain
 * tidak bisa membuat Anda masuk ke akun mereka (login CSRF).
 * Client secret Google tidak pernah ada di frontend.
 */
const FLOW_KEY = 'civitasone-pmb-google-flow'
const FLOW_MAX_AGE_MS = 30 * 60 * 1000
const GOOGLE_AUTH_ORIGIN = 'https://accounts.google.com'

export type GoogleIntent = 'login' | 'register'

/** Isian halaman Daftar yang dibawa ke langkah "lengkapi pendaftaran". */
export interface GoogleRegisterDraft {
  phone?: string
  info_source?: string
  consents?: { privacy_policy: boolean; terms: boolean; marketing_optional: boolean }
}

export interface GoogleFlow {
  verifier: string
  intent: GoogleIntent
  next: string | null
  draft?: GoogleRegisterDraft
  startedAt: number
}

function base64url(bytes: Uint8Array): string {
  let binary = ''
  bytes.forEach((b) => {
    binary += String.fromCharCode(b)
  })

  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
}

async function challengeFor(verifier: string): Promise<string> {
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier))

  return base64url(new Uint8Array(digest))
}

/** true bila backend mengaktifkan Google Sign-In (GOOGLE_CLIENT_ID/SECRET terisi) — tombol disembunyikan bila tidak. */
export function useGoogleSignInEnabled(): boolean {
  return useSite()?.auth?.google === true
}

/** Hanya rute portal internal yang boleh menjadi tujuan setelah masuk (anti open redirect). */
export function safeNext(next: string | null | undefined): string | null {
  if (!next || !next.startsWith('/pmb/portal') || next.startsWith('//') || next.includes('\\')) return null

  return next
}

export function readGoogleFlow(): GoogleFlow | null {
  try {
    const raw = window.sessionStorage.getItem(FLOW_KEY)
    if (!raw) return null
    const flow = JSON.parse(raw) as GoogleFlow

    return typeof flow.verifier === 'string' && Date.now() - flow.startedAt < FLOW_MAX_AGE_MS ? flow : null
  } catch {
    return null
  }
}

export function clearGoogleFlow(): void {
  try {
    window.sessionStorage.removeItem(FLOW_KEY)
  } catch {
    // sessionStorage diblokir — tidak ada yang perlu dihapus.
  }
}

/** Meminta URL Google dari backend lalu meninggalkan halaman ini. Melempar error bila gagal. */
export async function startGoogleSignIn(intent: GoogleIntent, options: { next?: string | null; draft?: GoogleRegisterDraft } = {}): Promise<void> {
  if (!window.crypto?.subtle) {
    throw new Error('Browser ini tidak mendukung masuk dengan Google secara aman. Gunakan browser terbaru atau email dan kata sandi.')
  }

  const bytes = new Uint8Array(32)
  crypto.getRandomValues(bytes)
  const verifier = base64url(bytes)
  const { authorization_url } = await pmbPublic.googleRedirect(await challengeFor(verifier))

  if (!authorization_url.startsWith(`${GOOGLE_AUTH_ORIGIN}/`)) {
    throw new Error('Alamat masuk Google tidak valid.')
  }

  const flow: GoogleFlow = { verifier, intent, next: safeNext(options.next), draft: options.draft, startedAt: Date.now() }

  try {
    window.sessionStorage.setItem(FLOW_KEY, JSON.stringify(flow))
  } catch {
    throw new Error('Penyimpanan browser diblokir (mode privat?). Izinkan penyimpanan situs atau gunakan email dan kata sandi.')
  }

  window.location.assign(authorization_url)
}

/** Kode `error` dari callback backend → pesan untuk pendaftar. */
export const GOOGLE_ERROR_MESSAGES: Record<string, string> = {
  cancelled: 'Proses masuk dengan Google dibatalkan. Anda dapat mencoba lagi atau menggunakan email dan kata sandi.',
  state_invalid: 'Sesi masuk dengan Google sudah kedaluwarsa atau tidak valid. Silakan ulangi dari halaman masuk.',
  google_error: 'Google tidak dapat menyelesaikan proses masuk. Silakan coba lagi.',
  google_failed: 'Verifikasi akun Google gagal. Silakan coba lagi beberapa saat lagi.',
  email_unverified: 'Email akun Google Anda belum terverifikasi oleh Google. Verifikasi email tersebut di akun Google, atau daftar dengan email dan kata sandi.',
  account_inactive: 'Akun PMB Anda tidak aktif. Hubungi panitia PMB.',
  account_conflict: 'Email ini sudah terhubung dengan akun Google lain. Masuk dengan email dan kata sandi, atau hubungi panitia PMB.',
}
