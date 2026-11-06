import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { pmbPublic, type GoogleAuthenticated } from '@/pmb/api/public'
import { GoogleSignInButton, OrDivider } from '@/pmb/components/GoogleSignInButton'
import { clearGoogleFlow, GOOGLE_ERROR_MESSAGES, readGoogleFlow, safeNext, useGoogleSignInEnabled, type GoogleFlow } from '@/pmb/lib/googleAuth'
import { useApplicantStore } from '@/pmb/stores/applicantStore'
import { useSite } from '@/pmb/components/SiteContext'
import { Captcha } from '@/pmb/components/Captcha'
import { describeError } from '@/pmb/components/useAction'
import { cardClass, linkClass } from '@/pmb/components/siteClass'
import { Input } from '@/components/ui/Input'
import { PasswordInput } from '@/components/ui/PasswordInput'
import { Checkbox } from '@/components/ui/Checkbox'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Select } from '@/components/ui/Select'
import { PMB, to } from '@/pmb/routes'
import type { NormalizedApiError } from '@/services/api'
import { fitFileToLimit } from '@/utils/compressFile'

const INFO_SOURCES = [
  ['website', 'Website kampus'], ['social_media', 'Media sosial'], ['school_visit', 'Kunjungan ke sekolah'], ['expo', 'Pameran pendidikan'],
  ['friend_family', 'Teman / keluarga'], ['alumni', 'Alumni'], ['maukuliah', 'Portal MauKuliah'], ['search_engine', 'Mesin pencari'], ['other', 'Lainnya'],
].map(([value, label]) => ({ value, label }))

const STEPS = ['Buat akun dan verifikasi email', 'Isi formulir dan unggah dokumen', 'Bayar biaya pendaftaran dan ikuti seleksi', 'Lihat hasil dan lakukan daftar ulang']

/** Formulir akun di kanan; di layar lebar didampingi panel charcoal alur pendaftaran (gaya hero landing). */
function AuthCard({ title, description, children, footer }: { title: string; description?: string; children: ReactNode; footer?: ReactNode }) {
  return (
    <div className="relative isolate">
      <div aria-hidden className="lp-grid-bg pointer-events-none absolute inset-x-0 top-0 -z-10 h-96" />
      <div className="lp-container grid max-w-5xl items-start gap-8 py-10 sm:py-14 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1fr)] lg:gap-10">
        <aside aria-label="Alur pendaftaran" className="lp-on-band relative isolate hidden overflow-hidden rounded-lp-xl bg-lp-band p-10 text-lp-band-fg shadow-lp-lg lg:sticky lg:top-24 lg:block">
          <div aria-hidden className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
          <div aria-hidden className="lp-sweep-br absolute -bottom-px right-0 -z-10 h-24 w-40 bg-lp-accent" />
          <p className="lp-eyebrow text-lp-accent">Portal Pendaftar</p>
          <p className="lp-h2 mt-3 text-lp-band-fg">Satu akun untuk seluruh tahap pendaftaran</p>
          <ol className="mt-8 space-y-5 pb-10">
            {STEPS.map((step, i) => (
              <li key={step} className="flex items-start gap-4">
                <span aria-hidden className="flex size-8 shrink-0 items-center justify-center rounded-full border border-lp-band-fg/30 font-display text-[0.875rem] font-bold text-lp-accent">{i + 1}</span>
                <span className="pt-1 text-[0.9375rem] text-lp-band-muted">{step}</span>
              </li>
            ))}
          </ol>
        </aside>
        <div className="mx-auto w-full max-w-md lg:max-w-none">
          <div className={cardClass(false, 'p-6 shadow-lp-lg sm:p-8')}>
            <p className="lp-eyebrow text-lp-brand-text">Portal Pendaftar</p>
            <h1 className="lp-h3 mt-2 text-[1.5rem] text-lp-fg">{title}</h1>
            {description && <p className="lp-small mt-2 text-lp-muted">{description}</p>}
            <div className="mt-6">{children}</div>
          </div>
          {footer && <div className="mt-5 text-center text-sm text-lp-muted">{footer}</div>}
        </div>
      </div>
    </div>
  )
}

function fieldErrors(err: NormalizedApiError): Record<string, string> {
  return Object.fromEntries(Object.entries(err.errors ?? {}).filter(([, v]) => Array.isArray(v)).map(([k, v]) => [k, (v as string[])[0]]))
}

function passwordStrength(value: string): { label: string; score: number } {
  let score = 0
  if (value.length >= 8) score++
  if (value.length >= 12) score++
  if (/[a-z]/i.test(value) && /\d/.test(value)) score++
  if (/[^a-z0-9]/i.test(value)) score++

  return { score, label: ['Sangat lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'][score] }
}

/** AUT-01 Daftar akun. Respons identik walau email sudah terdaftar (FR-ACC-01). */
export function RegisterPage() {
  const site = useSite()
  const [values, setValues] = useState({ name: '', email: '', phone: '', password: '', password_confirmation: '', info_source: '' })
  const [consents, setConsents] = useState({ privacy_policy: false, terms: false, marketing_optional: false })
  const [captcha, setCaptcha] = useState<string | null>(null)
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [formError, setFormError] = useState<string | null>(null)
  const [done, setDone] = useState(false)
  const [busy, setBusy] = useState(false)
  const googleEnabled = useGoogleSignInEnabled()
  const strength = passwordStrength(values.password)

  async function submit(event: FormEvent) {
    event.preventDefault()
    setErrors({})
    setFormError(null)

    if (values.password !== values.password_confirmation) {
      setErrors({ password_confirmation: 'Konfirmasi kata sandi tidak sama.' })

      return
    }

    setBusy(true)

    try {
      await pmbPublic.register({ ...values, phone: values.phone || null, info_source: values.info_source || null, consents, captcha_token: captcha })
      setDone(true)
    } catch (err) {
      const e = err as NormalizedApiError
      setErrors(fieldErrors(e))
      if (!e.errors || Object.keys(fieldErrors(e)).length === 0) setFormError(e.status === 429 ? 'Terlalu banyak percobaan. Coba lagi nanti.' : e.message)
    } finally {
      setBusy(false)
    }
  }

  if (done) {
    return (
      <AuthCard title="Cek email Anda" footer={<Link to={PMB.login} className={linkClass}>Kembali ke halaman masuk</Link>}>
        <Alert variant="success">Jika data valid, tautan verifikasi telah dikirim ke <strong>{values.email}</strong>. Buka email dan klik tautan untuk mengaktifkan akun (berlaku 24 jam).</Alert>
      </AuthCard>
    )
  }

  const set = (key: keyof typeof values) => (e: { target: { value: string } }) => setValues({ ...values, [key]: e.target.value })

  return (
    <AuthCard title="Daftar Akun PMB" description="Buat akun untuk mengisi formulir pendaftaran." footer={<>Sudah punya akun? <Link to={PMB.login} className={linkClass}>Masuk</Link></>}>
      <form onSubmit={submit} className="flex flex-col gap-4">
        {formError && <Alert variant="danger">{formError}</Alert>}
        <Input label="Nama lengkap (sesuai ijazah)" required minLength={3} value={values.name} onChange={set('name')} error={errors.name} />
        <Input label="Email" type="email" required value={values.email} onChange={set('email')} error={errors.email} />
        <Input label="Nomor HP (WhatsApp)" type="tel" value={values.phone} onChange={set('phone')} error={errors.phone} hint="Contoh: 081234567890" />
        <div>
          <PasswordInput label="Kata sandi" required value={values.password} onChange={set('password')} error={errors.password} hint="Minimal 8 karakter, berisi huruf dan angka." />
          {values.password && <div className="mt-1 flex items-center gap-2 text-xs text-ink-secondary"><div className="h-1.5 flex-1 rounded bg-surface-hover"><div className="h-1.5 rounded bg-primary" style={{ width: `${(strength.score / 4) * 100}%` }} /></div>{strength.label}</div>}
        </div>
        <PasswordInput label="Ulangi kata sandi" required value={values.password_confirmation} onChange={set('password_confirmation')} error={errors.password_confirmation} />
        <Select label="Dari mana Anda mengetahui PMB kami?" placeholder="Pilih (opsional)" options={INFO_SOURCES} value={values.info_source} onChange={set('info_source')} />
        <div className="space-y-2 text-sm">
          <Checkbox checked={consents.privacy_policy} onChange={(e) => setConsents({ ...consents, privacy_policy: e.target.checked })} label="Saya menyetujui Kebijakan Privasi" />
          {site?.legal.privacy_policy && <Link to={to(PMB.legal, { type: 'privacy_policy' })} target="_blank" className={`${linkClass} ml-6 text-xs`}>Baca Kebijakan Privasi</Link>}
          <Checkbox checked={consents.terms} onChange={(e) => setConsents({ ...consents, terms: e.target.checked })} label="Saya menyetujui Ketentuan Pendaftaran" />
          {site?.legal.terms && <Link to={to(PMB.legal, { type: 'terms' })} target="_blank" className={`${linkClass} ml-6 text-xs`}>Baca Ketentuan</Link>}
          <Checkbox checked={consents.marketing_optional} onChange={(e) => setConsents({ ...consents, marketing_optional: e.target.checked })} label="Saya bersedia menerima informasi promosi (opsional)" />
          {(errors['consents.privacy_policy'] || errors['consents.terms']) && <p className="text-xs text-danger">Kebijakan privasi dan ketentuan wajib disetujui.</p>}
        </div>
        <Captcha onToken={setCaptcha} />
        <Button type="submit" isLoading={busy} disabled={!consents.privacy_policy || !consents.terms}>Daftar</Button>
      </form>
      {googleEnabled && (
        <div className="mt-4 flex flex-col gap-4">
          <OrDivider />
          {/* Isian yang sudah diketik (HP, sumber info, persetujuan) dibawa ke langkah "lengkapi pendaftaran". */}
          <GoogleSignInButton intent="register" draft={() => ({ phone: values.phone, info_source: values.info_source, consents })} />
          <p className="text-center text-xs text-lp-muted">Email akun Google langsung terverifikasi. Anda tetap diminta melengkapi nama sesuai ijazah dan menyetujui ketentuan.</p>
        </div>
      )}
    </AuthCard>
  )
}

/** AUT-03 Masuk (email atau nomor HP). */
export function LoginPage() {
  const navigate = useNavigate()
  const [params] = useSearchParams()
  const setSession = useApplicantStore((s) => s.setSession)
  const [login, setLogin] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const googleEnabled = useGoogleSignInEnabled()

  async function submit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError(null)

    try {
      const result = await pmbPublic.login({ login, password, device_name: navigator.userAgent.slice(0, 90) })
      setSession(result.token, result.expires_at, result.account)
      navigate(params.get('next') ?? PMB.portal.home, { replace: true })
    } catch (err) {
      const e = err as NormalizedApiError
      setError(e.status === 429 ? 'Terlalu banyak percobaan masuk. Coba lagi beberapa saat lagi.' : describeError(e))
    } finally {
      setBusy(false)
    }
  }

  return (
    <AuthCard title="Masuk Portal PMB" footer={<>Belum punya akun? <Link to={PMB.register} className={linkClass}>Daftar</Link></>}>
      <form onSubmit={submit} className="flex flex-col gap-4">
        {error && <Alert variant="danger">{error}</Alert>}
        <Input label="Email atau nomor HP" required value={login} onChange={(e) => setLogin(e.target.value)} autoComplete="username" />
        <PasswordInput label="Kata sandi" required value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="current-password" />
        <Button type="submit" isLoading={busy}>Masuk</Button>
        <div className="flex justify-between text-sm">
          <Link to={PMB.forgot} className={linkClass}>Lupa kata sandi?</Link>
          <Link to={PMB.recovery} className="text-lp-muted underline-offset-4 hover:text-lp-brand-text hover:underline">Tidak bisa akses email?</Link>
        </div>
      </form>
      {googleEnabled && (
        <div className="mt-4 flex flex-col gap-4">
          <OrDivider />
          <GoogleSignInButton intent="login" next={params.get('next')} />
        </div>
      )}
    </AuthCard>
  )
}

/**
 * Tujuan redirect backend setelah Google: /pmb/masuk/google#ticket=… atau #error=….
 * Tiket ditukar bersama verifier tab ini, lalu: langsung masuk, atau minta sandi
 * akun lama (email sudah terdaftar), atau lengkapi pendaftaran (akun baru).
 */
export function GoogleCallbackPage() {
  const navigate = useNavigate()
  const setSession = useApplicantStore((s) => s.setSession)
  const started = useRef(false)
  const [phase, setPhase] = useState<'loading' | 'error' | 'link' | 'register'>('loading')
  const [error, setError] = useState<string | null>(null)
  const [flow, setFlow] = useState<GoogleFlow | null>(null)
  const [ticket, setTicket] = useState('')
  const [identity, setIdentity] = useState<{ email: string; name: string | null; avatar_url?: string | null } | null>(null)

  const finish = (result: GoogleAuthenticated, next: string | null) => {
    clearGoogleFlow()
    setSession(result.token, result.expires_at, result.account)
    navigate(safeNext(next) ?? PMB.portal.home, { replace: true })
  }

  const fail = (message: string) => {
    clearGoogleFlow()
    setError(message)
    setPhase('error')
  }

  useEffect(() => {
    // StrictMode menjalankan efek dua kali; tiket login hanya boleh ditukar sekali.
    if (started.current) return
    started.current = true

    const fragment = new URLSearchParams(window.location.hash.slice(1))
    // Hapus tiket dari bilah alamat & riwayat browser.
    window.history.replaceState(window.history.state, '', window.location.pathname + window.location.search)
    const errorCode = fragment.get('error')
    const ticketValue = fragment.get('ticket')
    const stored = readGoogleFlow()

    if (errorCode) {
      fail(GOOGLE_ERROR_MESSAGES[errorCode] ?? GOOGLE_ERROR_MESSAGES.google_error)

      return
    }

    if (!ticketValue || !stored) {
      fail('Sesi masuk dengan Google tidak ditemukan atau sudah kedaluwarsa. Ulangi dari halaman Masuk di tab browser yang sama.')

      return
    }

    setFlow(stored)
    setTicket(ticketValue)
    pmbPublic.googleSession({ ticket: ticketValue, client_verifier: stored.verifier, device_name: navigator.userAgent.slice(0, 90) })
      .then((result) => {
        if (result.status === 'authenticated') {
          finish(result, stored.next)
        } else {
          setIdentity(result)
          setPhase(result.status === 'link_required' ? 'link' : 'register')
        }
      })
      .catch((err: NormalizedApiError) => fail(err.status === 429 ? 'Terlalu banyak percobaan. Coba lagi beberapa saat lagi.' : describeError(err)))
    // eslint-disable-next-line react-hooks/exhaustive-deps -- sekali saat halaman dibuka
  }, [])

  if (phase === 'loading') {
    return (
      <AuthCard title="Masuk dengan Google">
        <p role="status" className="flex items-center gap-2 text-sm text-lp-muted"><span aria-hidden className="size-4 animate-spin rounded-full border-2 border-lp-line border-t-lp-brand" />Memverifikasi akun Google Anda…</p>
      </AuthCard>
    )
  }

  if (phase === 'error' || !flow || !identity) {
    return (
      <AuthCard title="Masuk dengan Google tidak berhasil" footer={<Link to={PMB.login} className={linkClass}>Kembali ke halaman masuk</Link>}>
        <div className="flex flex-col gap-4">
          <Alert variant="danger">{error ?? GOOGLE_ERROR_MESSAGES.google_error}</Alert>
          <GoogleSignInButton intent="login" />
          <p className="text-center text-sm text-lp-muted">atau <Link to={PMB.login} className={linkClass}>masuk dengan email dan kata sandi</Link></p>
        </div>
      </AuthCard>
    )
  }

  const body = { ticket, client_verifier: flow.verifier, device_name: navigator.userAgent.slice(0, 90) }

  return phase === 'link'
    ? <GoogleLinkForm email={identity.email} onLink={(password) => pmbPublic.googleLink({ ...body, password }).then((r) => finish(r, flow.next))} onExpired={fail} />
    : <GoogleRegisterForm identity={identity} flow={flow} onRegister={(data) => pmbPublic.googleRegister({ ...body, ...data }).then((r) => finish(r, flow.next))} onExpired={fail} />
}

/** Kode error yang berarti tiket tidak bisa dipakai lagi → kembali ke awal alur. */
const GOOGLE_RESTART_CODES = ['GOOGLE_SESSION_INVALID', 'GOOGLE_LINK_UNAVAILABLE', 'ACCOUNT_EXISTS', 'ACCOUNT_INACTIVE']

function errorCode(err: NormalizedApiError): string | undefined {
  const code = (err.errors as Record<string, unknown> | undefined)?.code

  return typeof code === 'string' ? code : undefined
}

/** Email Google sama dengan akun PMB lama: buktikan kepemilikan dengan sandi akun lama. */
function GoogleLinkForm({ email, onLink, onExpired }: { email: string; onLink: (password: string) => Promise<void>; onExpired: (message: string) => void }) {
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function submit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError(null)

    try {
      await onLink(password)
    } catch (err) {
      const e = err as NormalizedApiError
      const code = errorCode(e)

      if (code && GOOGLE_RESTART_CODES.includes(code)) {
        onExpired(e.message)

        return
      }

      setError(code === 'INVALID_CREDENTIALS' ? 'Kata sandi salah.' : e.status === 429 ? 'Terlalu banyak percobaan. Coba lagi beberapa saat lagi.' : describeError(e))
      setBusy(false)
    }
  }

  return (
    <AuthCard title="Hubungkan akun Google" description="Email ini sudah terdaftar sebagai akun PMB." footer={<Link to={PMB.login} className={linkClass}>Batal</Link>}>
      <form onSubmit={submit} className="flex flex-col gap-4">
        <Alert variant="info">
          Akun PMB dengan email <strong>{email}</strong> sudah ada. Demi keamanan, masukkan kata sandi akun tersebut satu kali untuk menghubungkannya dengan Google. Setelah itu Anda dapat masuk dengan Google maupun email dan kata sandi.
        </Alert>
        {error && <Alert variant="danger">{error}</Alert>}
        <PasswordInput label="Kata sandi akun PMB" required value={password} onChange={(e) => setPassword(e.target.value)} autoComplete="current-password" />
        <Button type="submit" isLoading={busy}>Hubungkan dan masuk</Button>
        <div className="flex justify-between text-sm">
          <Link to={PMB.forgot} className={linkClass}>Lupa kata sandi?</Link>
        </div>
      </form>
    </AuthCard>
  )
}

/** Akun baru dari Google: nama sesuai ijazah, HP opsional, dan persetujuan wajib (FR-ACC-01). */
function GoogleRegisterForm({ identity, flow, onRegister, onExpired }: {
  identity: { email: string; name: string | null; avatar_url?: string | null }
  flow: GoogleFlow
  onRegister: (data: { name: string; phone: string | null; info_source: string | null; consents: Record<string, boolean> }) => Promise<void>
  onExpired: (message: string) => void
}) {
  const site = useSite()
  const draft = flow.draft
  const [values, setValues] = useState({ name: identity.name ?? '', phone: draft?.phone ?? '', info_source: draft?.info_source ?? '' })
  const [consents, setConsents] = useState(draft?.consents ?? { privacy_policy: false, terms: false, marketing_optional: false })
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [formError, setFormError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const set = (key: keyof typeof values) => (e: { target: { value: string } }) => setValues({ ...values, [key]: e.target.value })

  async function submit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setErrors({})
    setFormError(null)

    try {
      await onRegister({ name: values.name, phone: values.phone || null, info_source: values.info_source || null, consents })
    } catch (err) {
      const e = err as NormalizedApiError
      const code = errorCode(e)

      if (code && GOOGLE_RESTART_CODES.includes(code)) {
        onExpired(e.message)

        return
      }

      const fields = fieldErrors(e)
      setErrors(fields)
      if (Object.keys(fields).length === 0) setFormError(e.status === 429 ? 'Terlalu banyak percobaan. Coba lagi beberapa saat lagi.' : describeError(e))
      setBusy(false)
    }
  }

  return (
    <AuthCard title="Lengkapi Pendaftaran" description="Satu langkah lagi untuk membuat akun PMB dengan Google." footer={<Link to={PMB.login} className={linkClass}>Batal</Link>}>
      <form onSubmit={submit} className="flex flex-col gap-4">
        <div className="flex items-center gap-3 rounded-lp-lg border border-lp-line bg-lp-sunken p-3">
          {identity.avatar_url
            ? <img src={identity.avatar_url} alt="" referrerPolicy="no-referrer" className="size-10 shrink-0 rounded-full" />
            : <span aria-hidden className="flex size-10 shrink-0 items-center justify-center rounded-full bg-lp-brand-soft font-semibold text-lp-brand-text">{identity.email.charAt(0).toUpperCase()}</span>}
          <div className="min-w-0">
            <p className="truncate text-sm font-medium text-lp-fg">{identity.email}</p>
            <p className="text-xs text-lp-muted">Akun Google · email terverifikasi</p>
          </div>
        </div>
        {formError && <Alert variant="danger">{formError}</Alert>}
        <Input label="Nama lengkap (sesuai ijazah)" required minLength={3} value={values.name} onChange={set('name')} error={errors.name} hint="Periksa kembali: nama di akun Google bisa berbeda dengan nama di ijazah." />
        <Input label="Nomor HP (WhatsApp)" type="tel" value={values.phone} onChange={set('phone')} error={errors.phone} hint="Opsional. Contoh: 081234567890" />
        <Select label="Dari mana Anda mengetahui PMB kami?" placeholder="Pilih (opsional)" options={INFO_SOURCES} value={values.info_source} onChange={set('info_source')} />
        <div className="space-y-2 text-sm">
          <Checkbox checked={consents.privacy_policy} onChange={(e) => setConsents({ ...consents, privacy_policy: e.target.checked })} label="Saya menyetujui Kebijakan Privasi" />
          {site?.legal.privacy_policy && <Link to={to(PMB.legal, { type: 'privacy_policy' })} target="_blank" className={`${linkClass} ml-6 text-xs`}>Baca Kebijakan Privasi</Link>}
          <Checkbox checked={consents.terms} onChange={(e) => setConsents({ ...consents, terms: e.target.checked })} label="Saya menyetujui Ketentuan Pendaftaran" />
          {site?.legal.terms && <Link to={to(PMB.legal, { type: 'terms' })} target="_blank" className={`${linkClass} ml-6 text-xs`}>Baca Ketentuan</Link>}
          <Checkbox checked={consents.marketing_optional} onChange={(e) => setConsents({ ...consents, marketing_optional: e.target.checked })} label="Saya bersedia menerima informasi promosi (opsional)" />
          {(errors['consents.privacy_policy'] || errors['consents.terms']) && <p className="text-xs text-danger">Kebijakan privasi dan ketentuan wajib disetujui.</p>}
        </div>
        <Button type="submit" isLoading={busy} disabled={!consents.privacy_policy || !consents.terms}>Buat akun dan masuk</Button>
      </form>
    </AuthCard>
  )
}

/** AUT-02 Verifikasi email dari tautan (juga konfirmasi ganti email: type=email). */
export function VerifyPage() {
  const [params] = useSearchParams()
  const token = params.get('token') ?? ''
  const isEmailChange = params.get('type') === 'email'
  const [state, setState] = useState<'loading' | 'ok' | 'error'>('loading')
  const [message, setMessage] = useState('')

  useEffect(() => {
    if (!token) {
      setState('error')
      setMessage('Tautan verifikasi tidak lengkap.')

      return
    }

    const call = isEmailChange ? pmbPublic.confirmEmailChange(token) : pmbPublic.verifyEmail(token)
    call.then(() => setState('ok')).catch((err: NormalizedApiError) => {
      setState('error')
      setMessage(err.message)
    })
  }, [token, isEmailChange])

  return (
    <AuthCard title={isEmailChange ? 'Konfirmasi Email Baru' : 'Verifikasi Akun'}>
      {state === 'loading' && <p className="text-sm text-ink-secondary">Memverifikasi...</p>}
      {state === 'ok' && <><Alert variant="success">{isEmailChange ? 'Email baru telah dikonfirmasi.' : 'Akun Anda aktif. Silakan masuk untuk memulai pendaftaran.'}</Alert><Link to={PMB.login} className={`${linkClass} mt-4 inline-block text-sm`}>Masuk</Link></>}
      {state === 'error' && <><Alert variant="danger">{message || 'Tautan tidak valid atau kedaluwarsa.'}</Alert><p className="mt-3 text-sm text-ink-secondary">Masuk ke portal untuk meminta tautan verifikasi baru.</p></>}
    </AuthCard>
  )
}

/** AUT-04 Lupa kata sandi (respons generik). */
export function ForgotPasswordPage() {
  const [login, setLogin] = useState('')
  const [captcha, setCaptcha] = useState<string | null>(null)
  const [done, setDone] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function submit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError(null)

    try {
      await pmbPublic.forgotPassword(login, captcha)
      setDone(true)
    } catch (err) {
      setError(describeError(err as NormalizedApiError))
    } finally {
      setBusy(false)
    }
  }

  return (
    <AuthCard title="Lupa Kata Sandi" footer={<Link to={PMB.login} className={linkClass}>Kembali ke halaman masuk</Link>}>
      {done ? (
        <Alert variant="success">Jika akun terdaftar, tautan atau kode pengaturan ulang telah dikirim.</Alert>
      ) : (
        <form onSubmit={submit} className="flex flex-col gap-4">
          {error && <Alert variant="danger">{error}</Alert>}
          <Input label="Email atau nomor HP terdaftar" required value={login} onChange={(e) => setLogin(e.target.value)} />
          <Captcha onToken={setCaptcha} />
          <Button type="submit" isLoading={busy}>Kirim</Button>
          <Link to={`${PMB.resetPassword}?mode=otp`} className={`${linkClass} text-center text-sm`}>Saya menerima kode OTP lewat SMS</Link>
        </form>
      )}
    </AuthCard>
  )
}

/** AUT-04 Atur kata sandi baru dari tautan email (token) atau kode OTP SMS. */
export function ResetPasswordPage() {
  const [params] = useSearchParams()
  const token = params.get('token')
  const otpMode = params.get('mode') === 'otp' || !token
  const [values, setValues] = useState({ destination: '', code: '', password: '', password_confirmation: '' })
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [error, setError] = useState<string | null>(null)
  const [done, setDone] = useState(false)
  const [busy, setBusy] = useState(false)

  async function submit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError(null)
    setErrors({})

    try {
      if (otpMode) {
        await pmbPublic.verifyOtp({ purpose: 'reset_password', destination: values.destination, code: values.code, password: values.password, password_confirmation: values.password_confirmation })
      } else {
        await pmbPublic.resetPassword({ token, password: values.password, password_confirmation: values.password_confirmation, verify: params.get('verify') === '1' })
      }

      setDone(true)
    } catch (err) {
      const e = err as NormalizedApiError
      setErrors(fieldErrors(e))
      setError(describeError(e))
    } finally {
      setBusy(false)
    }
  }

  return (
    <AuthCard title="Atur Kata Sandi Baru">
      {done ? (
        <><Alert variant="success">Kata sandi berhasil diatur. Silakan masuk.</Alert><Link to={PMB.login} className={`${linkClass} mt-4 inline-block text-sm`}>Masuk</Link></>
      ) : (
        <form onSubmit={submit} className="flex flex-col gap-4">
          {error && <Alert variant="danger">{error}</Alert>}
          {otpMode && <Input label="Nomor HP terdaftar" required value={values.destination} onChange={(e) => setValues({ ...values, destination: e.target.value })} error={errors.destination} />}
          {otpMode && <Input label="Kode OTP (6 digit)" required inputMode="numeric" maxLength={6} value={values.code} onChange={(e) => setValues({ ...values, code: e.target.value.replace(/\D/g, '') })} error={errors.code} />}
          <PasswordInput label="Kata sandi baru" required value={values.password} onChange={(e) => setValues({ ...values, password: e.target.value })} error={errors.password} />
          <PasswordInput label="Ulangi kata sandi baru" required value={values.password_confirmation} onChange={(e) => setValues({ ...values, password_confirmation: e.target.value })} />
          <Button type="submit" isLoading={busy}>Simpan</Button>
        </form>
      )}
    </AuthCard>
  )
}

/** AUT-05 Pemulihan akun (kehilangan akses email/HP) — diverifikasi manual panitia. */
export function RecoveryPage() {
  const [values, setValues] = useState({ full_name: '', birth_date: '', identity_number: '', new_email: '' })
  const [file, setFile] = useState<File | null>(null)
  const [captcha, setCaptcha] = useState<string | null>(null)
  const [done, setDone] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function submit(event: FormEvent) {
    event.preventDefault()
    if (!file) return
    setBusy(true)
    setError(null)

    try {
      const form = new FormData()
      Object.entries(values).forEach(([k, v]) => form.append(k, v))
      // Batas backend 2 MB; foto KTP yang lebih besar dikompres otomatis.
      form.append('identity_document', await fitFileToLimit(file, 2048 * 1024, { accept: 'image/jpeg,image/png,application/pdf' }))
      if (captcha) form.append('captcha_token', captcha)
      await pmbPublic.recoveryRequest(form)
      setDone(true)
    } catch (err) {
      setError(describeError(err as NormalizedApiError))
    } finally {
      setBusy(false)
    }
  }

  return (
    <AuthCard title="Pemulihan Akun" description="Ajukan bila Anda tidak dapat lagi mengakses email/nomor HP terdaftar.">
      {done ? (
        <Alert variant="success">Permintaan diterima dan akan diproses panitia maksimal 2 hari kerja. Hasilnya dikirim ke email baru Anda.</Alert>
      ) : (
        <form onSubmit={submit} className="flex flex-col gap-4">
          {error && <Alert variant="danger">{error}</Alert>}
          <Input label="Nama lengkap" required value={values.full_name} onChange={(e) => setValues({ ...values, full_name: e.target.value })} />
          <Input label="Tanggal lahir" type="date" required value={values.birth_date} onChange={(e) => setValues({ ...values, birth_date: e.target.value })} />
          <Input label="NIK / nomor paspor" required value={values.identity_number} onChange={(e) => setValues({ ...values, identity_number: e.target.value })} />
          <Input label="Email baru" type="email" required value={values.new_email} onChange={(e) => setValues({ ...values, new_email: e.target.value })} />
          <Input label="Foto KTP / identitas" type="file" accept="image/jpeg,image/png,application/pdf" required onChange={(e) => setFile(e.target.files?.[0] ?? null)} />
          <Captcha onToken={setCaptcha} />
          <Button type="submit" isLoading={busy}>Ajukan</Button>
        </form>
      )}
    </AuthCard>
  )
}
