import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { PolarAngleAxis, PolarGrid, PolarRadiusAxis, Radar, RadarChart, ResponsiveContainer, Tooltip } from 'recharts'
import { CheckCircle2, ChevronLeft, ChevronRight, ClipboardList, Send, Timer, WifiOff } from 'lucide-react'
import { pmbApplicant, type AssessmentCatalogItem, type AssessmentSession, type AssessmentSessionStatus } from '@/pmb/api/applicant'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { EmptyState } from '@/components/ui/EmptyState'
import { Modal } from '@/components/ui/Modal'
import { PageHeader } from '@/components/ui/PageHeader'
import { dateTime } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'
import { cn } from '@/utils/cn'
import type { NormalizedApiError } from '@/services/api'

/** Pilihan Likert 1–5 (docs/one-atma-talent/01 §3, skala Likert). Angka di luar 1–5 ditampilkan apa adanya. */
const LIKERT_LABELS: Record<number, string> = {
  1: 'Sangat tidak sesuai',
  2: 'Tidak sesuai',
  3: 'Netral',
  4: 'Sesuai',
  5: 'Sangat sesuai',
}

const SESSION_BADGE: Record<AssessmentSessionStatus, { label: string; variant: BadgeVariant }> = {
  in_progress: { label: 'Sedang dikerjakan', variant: 'warning' },
  submitted: { label: 'Menunggu hasil', variant: 'info' },
  expired: { label: 'Waktu habis', variant: 'neutral' },
  published: { label: 'Hasil tersedia', variant: 'success' },
  invalidated: { label: 'Dibatalkan panitia', variant: 'danger' },
}

function formatRemaining(ms: number): string {
  const seconds = Math.ceil(ms / 1000)
  const minutes = Math.floor(seconds / 60)

  return `${String(minutes).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`
}

/** Daftar asesmen minat & bakat untuk satu pendaftaran (POR-07 turunan, One Atma Talent). */
export function AssessmentListPage() {
  const { id = '' } = useParams()
  const catalog = useLoad(() => pmbApplicant.assessments(id), [id])
  const items = catalog.data?.assessments ?? []

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Asesmen minat & bakat"
        description="Asesmen ini membantu Anda mengenali minat dan kecenderungan belajar. Hasilnya bersifat eksploratif dan bukan keputusan penerimaan."
        actions={<Link to={to(PMB.portal.application, { id })}><Button variant="outline">Kembali ke pendaftaran</Button></Link>}
      />
      {catalog.loading && <p className="text-sm text-ink-secondary">Memuat asesmen...</p>}
      {catalog.error && <Alert variant="danger">{catalog.error.message}</Alert>}
      {catalog.data && items.length === 0 && (
        <Card>
          <EmptyState icon={ClipboardList} title="Belum ada asesmen" description="Belum ada asesmen yang dibuka untuk pendaftaran ini." />
        </Card>
      )}
      {items.map((item) => <AssessmentCard key={item.id} item={item} applicationId={id} />)}
    </div>
  )
}

function AssessmentCard({ item, applicationId }: { item: AssessmentCatalogItem; applicationId: string }) {
  const session = item.session
  const badge = session ? SESSION_BADGE[session.status] : null
  // Persetujuan belum diberikan bukan halangan: halaman mulai yang meminta persetujuan itu.
  const canOpen = !session && (item.can_start || item.blocked_reason === 'CONSENT_REQUIRED')

  return (
    <Card title={item.title} description={item.type.name} actions={badge && <Badge variant={badge.variant}>{badge.label}</Badge>}>
      <dl className="grid gap-3 text-sm sm:grid-cols-3">
        <div>
          <dt className="text-ink-tertiary">Durasi</dt>
          <dd className="text-ink-primary">{item.duration_minutes ? `${item.duration_minutes} menit` : 'Tidak dibatasi'}</dd>
        </div>
        <div>
          <dt className="text-ink-tertiary">Dibuka</dt>
          <dd className="text-ink-primary">{item.available_from ? dateTime(item.available_from) : 'Sekarang'}</dd>
        </div>
        <div>
          <dt className="text-ink-tertiary">Ditutup</dt>
          <dd className="text-ink-primary">{item.available_until ? dateTime(item.available_until) : 'Tidak ditentukan'}</dd>
        </div>
      </dl>

      {session && session.status === 'in_progress' && (
        <p className="mt-4 text-sm text-ink-secondary">{session.answered_count} dari {session.total_count} pertanyaan sudah dijawab.</p>
      )}
      {!session && !canOpen && item.blocked_message && (
        <div className="mt-4"><Alert variant="warning">{item.blocked_message}</Alert></div>
      )}

      <div className="mt-4 flex flex-wrap gap-2">
        {session?.status === 'in_progress' && (
          <Link to={to(PMB.portal.assessmentSession, { sessionId: session.id })}><Button>Lanjutkan</Button></Link>
        )}
        {session?.status === 'published' && (
          <Link to={to(PMB.portal.assessmentResult, { sessionId: session.id })}><Button>Lihat hasil</Button></Link>
        )}
        {canOpen && (
          <Link to={to(PMB.portal.assessment, { id: applicationId, versionId: item.id })}><Button>Mulai asesmen</Button></Link>
        )}
        {session && session.status !== 'in_progress' && session.status !== 'published' && (
          <p className="text-sm text-ink-secondary">Hasil akan tersedia setelah panitia mempublikasikannya.</p>
        )}
      </div>
    </Card>
  )
}

/** Instruksi, persetujuan pemrosesan data, lalu mulai atau lanjutkan sesi (prompt bagian 5.B). */
export function AssessmentStartPage() {
  const { id = '', versionId = '' } = useParams()
  const navigate = useNavigate()
  const catalog = useLoad(() => pmbApplicant.assessments(id), [id])
  const action = useAction()
  const [agreed, setAgreed] = useState(false)
  const item = catalog.data?.assessments.find((a) => a.id === versionId)

  if (catalog.loading) return <p className="text-sm text-ink-secondary">Memuat asesmen...</p>
  if (catalog.error) return <Alert variant="danger">{catalog.error.message}</Alert>
  if (!item) {
    return (
      <Card>
        <EmptyState icon={ClipboardList} title="Asesmen tidak ditemukan" description="Asesmen ini tidak tersedia untuk pendaftaran Anda." action={<Link to={to(PMB.portal.assessments, { id })}><Button variant="outline">Kembali</Button></Link>} />
      </Card>
    )
  }

  const resumable = item.session?.status === 'in_progress'
  const blockedMessage = !resumable && item.blocked_reason !== 'CONSENT_REQUIRED' ? item.blocked_message : null

  if (item.session && !resumable) {
    return (
      <div className="flex flex-col gap-5">
        <PageHeader title={item.title} />
        <Card>
          <EmptyState icon={CheckCircle2} title="Asesmen sudah dikerjakan" description="Anda hanya dapat mengerjakan asesmen ini sekali." action={<Link to={to(PMB.portal.assessments, { id })}><Button variant="outline">Kembali ke daftar asesmen</Button></Link>} />
        </Card>
      </div>
    )
  }

  const start = async () => {
    const session = await action.run(async (): Promise<AssessmentSession> => {
      if (!resumable) await pmbApplicant.assessmentConsent(id)

      return pmbApplicant.assessmentStart(id, versionId)
    })

    if (session) navigate(to(PMB.portal.assessmentSession, { sessionId: session.id }))
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title={item.title} description={item.type.name} actions={<Link to={to(PMB.portal.assessments, { id })}><Button variant="outline">Kembali</Button></Link>} />
      <Feedback state={action} />
      <Card title="Sebelum memulai">
        <div className="flex flex-col gap-4 text-sm text-ink-secondary">
          <p>{item.instructions ?? 'Pilih jawaban yang paling sesuai dengan diri Anda. Tidak ada jawaban yang benar atau salah.'}</p>
          <ul className="list-disc space-y-1 pl-5">
            <li>Durasi: {item.duration_minutes ? `${item.duration_minutes} menit sejak Anda memulai.` : 'tidak dibatasi waktu.'}</li>
            <li>Jawaban disimpan otomatis. Jika koneksi terputus, jawaban akan dikirim ulang saat koneksi kembali.</li>
            <li>Asesmen hanya dapat dikirim sekali. Setelah dikirim, jawaban tidak dapat diubah.</li>
            <li>Hasil bersifat eksploratif. Hasil tidak digunakan sebagai keputusan penerimaan dan bukan diagnosis psikologis.</li>
          </ul>
        </div>
      </Card>

      {!resumable && (
        <Card title="Persetujuan pemrosesan data">
          <label className="flex items-start gap-3 text-sm text-ink-primary">
            <input type="checkbox" className="mt-1 size-4 accent-primary" checked={agreed} onChange={(e) => setAgreed(e.target.checked)} />
            <span>Saya setuju jawaban dan hasil asesmen ini diproses oleh universitas untuk keperluan eksplorasi minat dan bakat saya, sesuai kebijakan privasi PMB.</span>
          </label>
        </Card>
      )}

      {blockedMessage && <Alert variant="warning">{blockedMessage}</Alert>}

      <div>
        <Button disabled={(!resumable && !agreed) || blockedMessage !== null} isLoading={action.busy} onClick={() => void start()}>
          {resumable ? 'Lanjutkan asesmen' : 'Mulai asesmen'}
        </Button>
      </div>
    </div>
  )
}

/**
 * Antarmuka pengerjaan layar penuh (di luar PortalLayout, seperti CBT). Jawaban disimpan
 * per batch tiap 2 detik; galat jaringan dicoba ulang, dan 409 memuat ulang status sesi.
 */
export function AssessmentSessionPage() {
  const { sessionId = '' } = useParams()
  const navigate = useNavigate()
  const [session, setSession] = useState<AssessmentSession | null>(null)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [values, setValues] = useState<Record<string, number>>({})
  const [index, setIndex] = useState(0)
  const [saveState, setSaveState] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle')
  const [saveMessage, setSaveMessage] = useState<string | null>(null)
  const [confirmOpen, setConfirmOpen] = useState(false)
  const [submitting, setSubmitting] = useState(false)
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [online, setOnline] = useState(() => navigator.onLine)
  const [now, setNow] = useState(() => Date.now())
  const [offset, setOffset] = useState(0)
  const pendingRef = useRef(new Map<string, number>())
  const flushingRef = useRef(false)
  const autoSubmittedRef = useRef(false)

  const applySession = useCallback((next: AssessmentSession) => {
    setSession(next)
    setValues(Object.fromEntries(next.items.filter((item) => item.value !== null).map((item) => [item.id, item.value as number])))
    setOffset(new Date(next.server_time).getTime() - Date.now())
  }, [])

  useEffect(() => {
    pmbApplicant.assessmentSession(sessionId).then(applySession).catch((e: NormalizedApiError) => setLoadError(e.message))
  }, [sessionId, applySession])

  useEffect(() => {
    const up = () => setOnline(true)
    const down = () => setOnline(false)
    window.addEventListener('online', up)
    window.addEventListener('offline', down)

    return () => {
      window.removeEventListener('online', up)
      window.removeEventListener('offline', down)
    }
  }, [])

  useEffect(() => {
    const timer = window.setInterval(() => setNow(Date.now() + offset), 1000)

    return () => window.clearInterval(timer)
  }, [offset])

  const flush = useCallback(async () => {
    if (flushingRef.current || pendingRef.current.size === 0) return

    flushingRef.current = true
    const batch = [...pendingRef.current].map(([item_id, value]) => ({ item_id, value }))
    setSaveState('saving')

    try {
      await pmbApplicant.assessmentSave(sessionId, batch)

      for (const { item_id, value } of batch) {
        if (pendingRef.current.get(item_id) === value) pendingRef.current.delete(item_id)
      }

      setSaveMessage(null)
      setSaveState(pendingRef.current.size === 0 ? 'saved' : 'saving')
    } catch (err) {
      const e = err as NormalizedApiError

      if (e.status === 409) {
        // Sesi sudah ditutup (waktu habis atau sudah dikirim): tampilkan keadaan terbaru dari server.
        pendingRef.current.clear()
        setSaveMessage(e.message)
        setSaveState('idle')
        pmbApplicant.assessmentSession(sessionId).then(applySession).catch(() => undefined)
      } else {
        setSaveState('error')
        setSaveMessage('Jawaban belum tersimpan. Kami akan mencoba lagi secara otomatis.')
      }
    } finally {
      flushingRef.current = false
    }
  }, [sessionId, applySession])

  useEffect(() => {
    const timer = window.setInterval(() => void flush(), 2000)

    return () => window.clearInterval(timer)
  }, [flush])

  const finish = useCallback(async () => {
    setSubmitting(true)
    setSubmitError(null)

    try {
      await flush()

      if (pendingRef.current.size > 0) {
        setSubmitError('Masih ada jawaban yang belum tersimpan. Periksa koneksi, lalu coba lagi.')

        return
      }

      applySession(await pmbApplicant.assessmentSubmit(sessionId))
      setConfirmOpen(false)
    } catch (err) {
      setSubmitError((err as NormalizedApiError).message)
    } finally {
      setSubmitting(false)
    }
  }, [flush, sessionId, applySession])

  const deadline = session?.deadline_at ? new Date(session.deadline_at).getTime() : null
  const isOpen = session?.status === 'in_progress'

  useEffect(() => {
    if (isOpen && deadline !== null && now >= deadline && !autoSubmittedRef.current) {
      autoSubmittedRef.current = true
      void finish()
    }
  }, [isOpen, deadline, now, finish])

  const answer = useCallback((itemId: string, value: number) => {
    setValues((prev) => ({ ...prev, [itemId]: value }))
    pendingRef.current.set(itemId, value)
    setSaveState('saving')
    void flush()
  }, [flush])

  const items = session?.items ?? []
  const answeredCount = items.filter((item) => values[item.id] !== undefined).length
  const unanswered = items.length - answeredCount
  const current = items[index]
  const scale = session?.scale ?? { min: 1, max: 5 }
  const scaleValues = Array.from({ length: scale.max - scale.min + 1 }, (_, i) => scale.min + i)

  useEffect(() => {
    if (!isOpen || !current) return

    const onKey = (e: KeyboardEvent) => {
      const value = Number(e.key)

      if (Number.isInteger(value) && value >= scale.min && value <= scale.max) answer(current.id, value)
    }

    window.addEventListener('keydown', onKey)

    return () => window.removeEventListener('keydown', onKey)
  }, [isOpen, current, scale.min, scale.max, answer])

  if (loadError) {
    return (
      <div className="mx-auto flex max-w-xl flex-col gap-4 p-6">
        <Alert variant="danger">{loadError}</Alert>
        <Button variant="outline" onClick={() => navigate(PMB.portal.home)}>Kembali ke beranda</Button>
      </div>
    )
  }

  if (!session) return <p className="p-6 text-sm text-ink-secondary">Menyiapkan asesmen...</p>

  if (!isOpen) {
    const expired = session.status === 'expired'
    const invalidated = session.status === 'invalidated'

    return (
      <div className="mx-auto flex max-w-xl flex-col gap-4 p-6">
        <Card>
          <EmptyState
            icon={CheckCircle2}
            title={invalidated ? 'Asesmen dibatalkan panitia' : expired ? 'Waktu pengerjaan telah habis' : 'Asesmen sudah dikirim'}
            description={invalidated ? 'Hasil asesmen ini tidak akan diproses. Hubungi panitia PMB jika Anda merasa ini kekeliruan.' : expired ? 'Jawaban yang sudah lengkap tetap dinilai. Jika belum lengkap, hasil tidak dihitung.' : 'Hasil akan tersedia setelah panitia mempublikasikannya.'}
            action={session.status === 'published'
              ? <Link to={to(PMB.portal.assessmentResult, { sessionId })}><Button>Lihat hasil</Button></Link>
              : <Link to={PMB.portal.home}><Button variant="outline">Kembali ke beranda</Button></Link>}
          />
        </Card>
      </div>
    )
  }

  return (
    <div className="mx-auto flex min-h-dvh max-w-3xl flex-col gap-4 p-4 sm:p-6">
      <header className="flex flex-wrap items-end justify-between gap-3">
        <div>
          <p className="text-xs text-ink-tertiary">{session.version.title}</p>
          <h1 className="text-lg font-semibold text-ink-primary">Pertanyaan {index + 1} dari {items.length}</h1>
        </div>
        <div className="flex items-center gap-3 text-sm">
          {deadline !== null && (
            <span className="inline-flex items-center gap-1 font-medium tabular-nums text-ink-primary" aria-label={`Sisa waktu ${formatRemaining(Math.max(0, deadline - now))}`}>
              <Timer className="size-4" aria-hidden />
              {formatRemaining(Math.max(0, deadline - now))}
            </span>
          )}
          {saveState === 'saving' && <span className="text-ink-tertiary">Menyimpan...</span>}
          {saveState === 'saved' && <span className="inline-flex items-center gap-1 text-primary-700"><CheckCircle2 className="size-4" aria-hidden />Tersimpan</span>}
        </div>
      </header>

      <div role="progressbar" aria-label="Progres pengerjaan" aria-valuemin={0} aria-valuemax={items.length} aria-valuenow={answeredCount} className="h-2 overflow-hidden rounded-full bg-surface-hover">
        <div className="h-full bg-primary transition-all" style={{ width: `${items.length ? (answeredCount / items.length) * 100 : 0}%` }} />
      </div>

      {!online && (
        <Alert variant="warning" title="Koneksi terputus">
          <span className="inline-flex items-center gap-2"><WifiOff className="size-4" aria-hidden />Jawaban akan dikirim ulang saat koneksi kembali. Jangan tutup halaman ini.</span>
        </Alert>
      )}
      {saveState === 'error' && saveMessage && <Alert variant="warning">{saveMessage}</Alert>}
      {saveState === 'idle' && saveMessage && <Alert variant="info">{saveMessage}</Alert>}

      {current && (
        <Card>
          <fieldset>
            <legend className="text-base font-medium leading-relaxed text-ink-primary">{current.stem}</legend>
            <div role="radiogroup" aria-label="Pilihan jawaban" className="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-5">
              {scaleValues.map((value) => {
                const selected = values[current.id] === value

                return (
                  <label
                    key={value}
                    className={cn(
                      'flex cursor-pointer flex-col items-center gap-1 rounded-lg border px-3 py-3 text-center text-sm transition focus-within:ring-2 focus-within:ring-primary',
                      selected ? 'border-primary bg-primary-100 text-primary-700' : 'border-border text-ink-secondary hover:bg-surface-hover',
                    )}
                  >
                    <input
                      type="radio"
                      name={`answer-${current.id}`}
                      value={value}
                      checked={selected}
                      onChange={() => answer(current.id, value)}
                      className="sr-only"
                    />
                    <span className="text-lg font-semibold tabular-nums">{value}</span>
                    <span>{LIKERT_LABELS[value] ?? String(value)}</span>
                  </label>
                )
              })}
            </div>
          </fieldset>
        </Card>
      )}

      <nav aria-label="Daftar pertanyaan" className="flex flex-wrap gap-2">
        {items.map((item, i) => {
          const done = values[item.id] !== undefined

          return (
            <button
              key={item.id}
              type="button"
              onClick={() => setIndex(i)}
              aria-label={`Pertanyaan ${i + 1}, ${done ? 'sudah dijawab' : 'belum dijawab'}`}
              aria-current={i === index ? 'step' : undefined}
              className={cn(
                'size-9 rounded-md border text-sm font-medium tabular-nums transition',
                i === index && 'ring-2 ring-primary',
                done ? 'border-primary bg-primary text-white' : 'border-border text-ink-secondary hover:bg-surface-hover',
              )}
            >
              {i + 1}
            </button>
          )
        })}
      </nav>

      <div className="flex flex-wrap items-center justify-between gap-3">
        <Button variant="outline" leftIcon={<ChevronLeft className="size-4" />} disabled={index === 0} onClick={() => setIndex((i) => Math.max(0, i - 1))}>
          Sebelumnya
        </Button>
        {index < items.length - 1 && (
          <Button rightIcon={<ChevronRight className="size-4" />} onClick={() => setIndex((i) => Math.min(items.length - 1, i + 1))}>
            Selanjutnya
          </Button>
        )}
        <Button variant={index === items.length - 1 ? 'primary' : 'outline'} leftIcon={<Send className="size-4" />} onClick={() => setConfirmOpen(true)}>
          Kirim asesmen
        </Button>
      </div>

      <Modal open={confirmOpen} onClose={() => setConfirmOpen(false)} title="Kirim asesmen?">
        <div className="flex flex-col gap-4 text-sm text-ink-secondary">
          <p>
            {unanswered > 0
              ? `Masih ada ${unanswered} pertanyaan yang belum dijawab. Kirim hanya setelah semua pertanyaan dijawab.`
              : 'Setelah dikirim, jawaban tidak dapat diubah. Hasil akan tersedia setelah dipublikasikan.'}
          </p>
          {submitError && <Alert variant="danger">{submitError}</Alert>}
          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setConfirmOpen(false)}>Periksa lagi</Button>
            <Button isLoading={submitting} disabled={unanswered > 0} onClick={() => void finish()}>Kirim sekarang</Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}

/** Hasil per dimensi setelah dipublikasikan. Sebelum itu peserta hanya melihat keterangan belum tersedia. */
export function AssessmentResultPage() {
  const { sessionId = '' } = useParams()
  const result = useLoad(() => pmbApplicant.assessmentResult(sessionId), [sessionId])

  if (result.loading) return <p className="text-sm text-ink-secondary">Memuat hasil...</p>

  if (result.error) {
    return (
      <div className="flex flex-col gap-5">
        <PageHeader title="Hasil asesmen" />
        {result.error.status === 409
          ? <Card><EmptyState icon={ClipboardList} title="Hasil belum dipublikasikan" description="Hasil akan tersedia setelah panitia PMB mempublikasikannya." /></Card>
          : <Alert variant="danger">{result.error.message}</Alert>}
      </div>
    )
  }

  const data = result.data
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Hasil asesmen minat" description={data.assessment.title} />

      <Card>
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p className="text-sm text-ink-tertiary">Holland Code</p>
            <p className="text-4xl font-bold tracking-widest text-primary-700 tabular-nums">{data.holland_code ?? '—'}</p>
          </div>
          <div className="flex flex-wrap gap-2">
            {data.dominant.map((d) => <Badge key={d.code} variant="primary">{d.code} · {d.name}</Badge>)}
          </div>
        </div>
      </Card>

      <div className="grid gap-5 lg:grid-cols-2">
        <Card title="Profil minat">
          <ResponsiveContainer width="100%" height={280}>
            <RadarChart data={data.dimensions.map((d) => ({ dimensi: d.code, nilai: d.percent_score }))} outerRadius="75%">
              <PolarGrid stroke="var(--color-border)" />
              <PolarAngleAxis dataKey="dimensi" tick={{ fill: 'var(--color-text-secondary)', fontSize: 12 }} />
              <PolarRadiusAxis domain={[0, 100]} tick={false} axisLine={false} />
              <Radar dataKey="nilai" stroke="var(--color-accent)" fill="var(--color-accent)" fillOpacity={0.25} />
              <Tooltip formatter={(value) => [`${value}%`, 'Posisi']} />
            </RadarChart>
          </ResponsiveContainer>
        </Card>

        <Card title="Skor per dimensi" description="Posisi relatif terhadap rentang skala jawaban. Bukan skor terstandar.">
          <ol className="flex flex-col gap-4">
            {data.dimensions.map((d) => (
              <li key={d.code} className="flex flex-col gap-1">
                <div className="flex items-center justify-between text-sm">
                  <span className="font-medium text-ink-primary">{d.rank}. {d.name} ({d.code})</span>
                  <span className="tabular-nums text-ink-tertiary">rata-rata {d.mean_score}</span>
                </div>
                <div className="h-2 overflow-hidden rounded-full bg-surface-hover" role="img" aria-label={`${d.name}: ${d.percent_score} persen`}>
                  <div className="h-full bg-primary" style={{ width: `${d.percent_score}%` }} />
                </div>
              </li>
            ))}
          </ol>
        </Card>
      </div>

      <Card title="Interpretasi">
        <ul className="flex flex-col gap-4 text-sm">
          {data.dimensions.map((d) => (
            <li key={d.code}>
              <p className="font-medium text-ink-primary">{d.name} ({d.code})</p>
              <p className="text-ink-secondary">{d.interpretation ?? d.description ?? 'Keterangan untuk dimensi ini belum tersedia.'}</p>
            </li>
          ))}
        </ul>
      </Card>

      <Alert variant="info">{data.disclaimer}</Alert>
      <div><Link to={PMB.portal.home}><Button variant="outline">Kembali ke beranda</Button></Link></div>
    </div>
  )
}
