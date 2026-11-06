import { useState } from 'react'
import { Loader2 } from 'lucide-react'
import { Alert } from '@/components/ui/Alert'
import { startGoogleSignIn, type GoogleIntent, type GoogleRegisterDraft } from '@/pmb/lib/googleAuth'
import type { NormalizedApiError } from '@/services/api'

/** Logo "G" resmi Google (multiwarna) — jangan diubah warna/proporsinya (brand guideline). */
function GoogleLogo() {
  return (
    <svg aria-hidden viewBox="0 0 48 48" className="size-[18px] shrink-0">
      <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" />
      <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z" />
      <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z" />
      <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" />
    </svg>
  )
}

/** Teks resmi tombol Google versi Bahasa Indonesia ("Sign up with" / "Continue with"). */
const LABELS: Record<GoogleIntent, string> = {
  register: 'Daftar dengan Google',
  login: 'Lanjutkan dengan Google',
}

/** Pemisah "ATAU" antara formulir email/sandi dan tombol Google. */
export function OrDivider() {
  return (
    <div role="separator" aria-label="atau" className="my-1 flex items-center gap-3 text-xs font-semibold tracking-[0.12em] text-lp-muted">
      <span className="h-px flex-1 bg-lp-line" />
      ATAU
      <span className="h-px flex-1 bg-lp-line" />
    </div>
  )
}

/**
 * Tombol "Daftar/Lanjutkan dengan Google" mengikuti Google Identity branding
 * guidelines: latar putih + garis #747775 + teks #1F1F1F (tema terang), latar
 * #131314 + garis #8E918F + teks #E3E3E3 (tema gelap), Roboto Medium 14px,
 * tinggi 40px, logo di kiri dengan jarak 10px.
 */
export function GoogleSignInButton({ intent, next, draft, disabled }: { intent: GoogleIntent; next?: string | null; draft?: () => GoogleRegisterDraft; disabled?: boolean }) {
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState<string | null>(null)

  async function start() {
    setBusy(true)
    setError(null)

    try {
      await startGoogleSignIn(intent, { next, draft: draft?.() })
      // Halaman sedang berpindah ke Google — biarkan status memuat tetap tampil.
    } catch (err) {
      const e = err as Partial<NormalizedApiError> & { message?: string }
      setError(e.status === 429 ? 'Terlalu banyak percobaan. Coba lagi beberapa saat lagi.' : (e.message ?? 'Tidak dapat menghubungi Google. Silakan coba lagi.'))
      setBusy(false)
    }
  }

  return (
    <div className="flex flex-col gap-3">
      {error && <Alert variant="danger">{error}</Alert>}
      <button
        type="button"
        onClick={start}
        disabled={disabled || busy}
        aria-busy={busy}
        style={{ fontFamily: "Roboto, 'Google Sans', Arial, sans-serif" }}
        className="inline-flex h-10 w-full items-center justify-center gap-2.5 rounded-full border border-[#747775] bg-white px-3 text-sm font-medium text-[#1F1F1F] transition-colors hover:bg-[#F7F8F8] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#4285F4] focus-visible:ring-offset-2 active:bg-[#EEEFEF] disabled:cursor-not-allowed disabled:opacity-60 dark:border-[#8E918F] dark:bg-[#131314] dark:text-[#E3E3E3] dark:hover:bg-[#1F1F20] dark:active:bg-[#2A2A2B]"
      >
        <GoogleLogo />
        <span>{busy ? 'Menghubungkan ke Google…' : LABELS[intent]}</span>
        {busy && <Loader2 aria-hidden className="size-4 animate-spin" />}
      </button>
    </div>
  )
}
