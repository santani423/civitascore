import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { CalendarClock, Download, MapPin, MonitorPlay } from 'lucide-react'
import { pmbApplicant, type CbtAttempt, type ExamParticipation } from '@/pmb/api/applicant'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { FormDialog, toFormData } from '@/pmb/components/FormDialog'
import { InvoicePanel } from '@/pmb/components/InvoicePanel'
import { DocumentsPanel } from '@/pmb/components/DocumentsPanel'
import { GuardianManager } from '@/pmb/components/ProfileManagers'
import { RichText } from '@/pmb/components/Section'
import { KeyValue } from '@/pmb/components/KeyValue'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Checkbox } from '@/components/ui/Checkbox'
import { PageHeader } from '@/components/ui/PageHeader'
import { EmptyState } from '@/components/ui/EmptyState'
import { DECISION_LABELS, statusLabel } from '@/pmb/lib/labels'
import { countdown, date, dateTime } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'
import { cn } from '@/utils/cn'

/** POR-07 Ujian & kartu peserta. */
export function ExamsPage() {
  const { id = '' } = useParams()
  const data = useLoad(() => pmbApplicant.exams(id), [id])
  const action = useAction()
  const [reschedule, setReschedule] = useState<ExamParticipation | null>(null)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Ujian & Kartu Peserta" breadcrumb={[{ label: 'Beranda', path: PMB.portal.home }, { label: 'Ujian' }]} />
      <Feedback state={action} />
      {data.error && <Alert variant="danger">{data.error.message}</Alert>}
      {data.data && !data.data.requires_exam && <Alert variant="info">Jalur ini tidak memerlukan ujian tulis; seleksi berdasarkan berkas.</Alert>}
      {data.data?.requires_exam && data.data.participations.length === 0 && <Card><EmptyState icon={CalendarClock} title="Jadwal ujian belum terbit" description={data.data.exam_window.starts_on ? `Perkiraan ujian ${date(data.data.exam_window.starts_on)} – ${date(data.data.exam_window.ends_on)}.` : 'Jadwal akan diumumkan setelah verifikasi selesai.'} /></Card>}
      {(data.data?.participations ?? []).map((p) => (
        <Card key={p.id} title={p.title} description={`No. peserta ${p.participant_number}`} actions={<StatusBadge status={p.attendance_status} />}>
          <KeyValue items={[
            ['Sesi', `${p.session.code} · ${dateTime(p.session.starts_at)} – ${dateTime(p.session.ends_at)}`],
            ['Mode', p.mode === 'cbt' ? 'Computer Based Test (daring)' : p.mode === 'interview' ? 'Wawancara' : p.mode === 'online_proctored' ? 'Daring terawasi' : 'Luring di lokasi'],
            ['Lokasi', p.location ? <span className="flex items-start gap-1"><MapPin className="mt-0.5 size-4" />{p.location.venue} — {p.location.room}{p.location.address ? `, ${p.location.address}` : ''}</span> : p.session.online_url ? <a className="text-primary" href={p.session.online_url} target="_blank" rel="noopener">Tautan ujian daring</a> : 'Daring'],
          ]} />
          {p.instructions && <p className="mt-3 whitespace-pre-line text-sm text-ink-secondary">{p.instructions}</p>}
          <div className="mt-4 flex flex-wrap gap-2">
            <Button variant="outline" leftIcon={<Download className="size-4" />} disabled={!p.card_available} onClick={() => action.run(async () => window.open((await pmbApplicant.examCard(p.id)).url, '_blank', 'noopener'))}>{p.card_available ? 'Unduh kartu peserta' : 'Kartu sedang disiapkan'}</Button>
            {p.cbt && !p.cbt.submitted && <Link to={to(PMB.portal.cbt, { participantId: p.id })}><Button leftIcon={<MonitorPlay className="size-4" />}>{p.cbt.started ? 'Lanjutkan ujian' : 'Mulai ujian CBT'}</Button></Link>}
            {p.cbt?.submitted && <StatusBadge status="submitted" label="Jawaban CBT terkirim" />}
            {p.attendance_status === 'registered' && <Button variant="ghost" onClick={() => setReschedule(p)}>Ajukan jadwal ulang</Button>}
          </div>
        </Card>
      ))}
      <FormDialog
        open={reschedule !== null}
        title="Ajukan jadwal ulang"
        description="Sertakan alasan kuat (sakit, bentrok jadwal resmi) dan bukti pendukung bila ada. Permintaan ditinjau panitia."
        fields={[{ name: 'reason', label: 'Alasan', type: 'textarea', required: true }, { name: 'attachment', label: 'Bukti (opsional)', type: 'file', accept: 'image/jpeg,image/png,application/pdf' }]}
        onClose={() => setReschedule(null)}
        onSubmit={async (values) => {
          if (reschedule) await pmbApplicant.reschedule(reschedule.id, toFormData(values))
          data.reload()
        }}
      />
    </div>
  )
}

/** Pengerjaan CBT layar penuh: simpan otomatis, hitung mundur server, deteksi pindah tab. */
export function CbtPage() {
  const { participantId = '' } = useParams()
  const navigate = useNavigate()
  const [attempt, setAttempt] = useState<CbtAttempt | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [index, setIndex] = useState(0)
  const [now, setNow] = useState(Date.now())
  const [offset, setOffset] = useState(0)
  const [saving, setSaving] = useState<string | null>(null)
  const submittedRef = useRef(false)

  useEffect(() => {
    pmbApplicant.cbtStart(participantId).then((a) => {
      setAttempt(a)
      setOffset(new Date(a.server_time).getTime() - Date.now())
    }).catch((e: { message: string }) => setError(e.message))
  }, [participantId])

  const submit = useCallback(async () => {
    if (submittedRef.current) return
    submittedRef.current = true
    await pmbApplicant.cbtSubmit(participantId).catch(() => undefined)
    navigate(-1)
  }, [participantId, navigate])

  useEffect(() => {
    const timer = window.setInterval(() => setNow(Date.now() + offset), 1000)

    return () => window.clearInterval(timer)
  }, [offset])

  useEffect(() => {
    if (attempt && !attempt.submitted_at && new Date(attempt.deadline_at).getTime() <= now) void submit()
  }, [attempt, now, submit])

  useEffect(() => {
    const onHide = () => document.visibilityState === 'hidden' && pmbApplicant.cbtViolation(participantId).catch(() => undefined)
    document.addEventListener('visibilitychange', onHide)

    return () => document.removeEventListener('visibilitychange', onHide)
  }, [participantId])

  if (error) return <div className="mx-auto max-w-xl p-6"><Alert variant="danger">{error}</Alert><Button className="mt-4" variant="outline" onClick={() => navigate(-1)}>Kembali</Button></div>
  if (!attempt) return <p className="p-6 text-sm text-ink-secondary">Menyiapkan ujian...</p>

  const q = attempt.questions[index]
  const answered = attempt.questions.filter((x) => x.answer).length

  const answer = async (option: string) => {
    setAttempt({ ...attempt, questions: attempt.questions.map((x) => (x.id === q.id ? { ...x, answer: option } : x)) })
    setSaving(q.id)

    try {
      await pmbApplicant.cbtAnswer(participantId, q.id, option)
    } catch (e) {
      setError((e as { message: string }).message)
    } finally {
      setSaving(null)
    }
  }

  return (
    <div className="min-h-screen bg-background">
      <header className="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-surface px-4 py-3">
        <span className="text-sm font-medium">Soal {index + 1} / {attempt.questions.length} · terjawab {answered}</span>
        <span className="font-mono text-lg font-semibold text-primary">{countdown(attempt.deadline_at, now)}</span>
      </header>
      <div className="mx-auto grid max-w-5xl gap-6 p-4 md:grid-cols-4">
        <Card className="md:col-span-3">
          <p className="whitespace-pre-line text-ink-primary">{q.question}</p>
          <div className="mt-4 flex flex-col gap-2">
            {q.options.map((o) => (
              <button key={o.key} type="button" onClick={() => answer(o.key)} className={cn('rounded-lg border px-4 py-3 text-left text-sm', q.answer === o.key ? 'border-primary bg-primary-50 text-primary-800 dark:bg-primary-950' : 'border-border hover:bg-surface-hover')}>
                <span className="mr-2 font-semibold">{o.key}.</span>{o.text}
              </button>
            ))}
          </div>
          <p className="mt-2 h-4 text-xs text-ink-tertiary">{saving === q.id ? 'Menyimpan...' : q.answer ? 'Tersimpan' : ''}</p>
          <div className="mt-4 flex justify-between">
            <Button variant="outline" disabled={index === 0} onClick={() => setIndex(index - 1)}>Sebelumnya</Button>
            {index < attempt.questions.length - 1 ? <Button onClick={() => setIndex(index + 1)}>Berikutnya</Button> : <Button variant="danger" onClick={() => window.confirm(`Kirim jawaban? ${attempt.questions.length - answered} soal belum dijawab.`) && submit()}>Kirim jawaban</Button>}
          </div>
        </Card>
        <Card title="Navigasi soal">
          <div className="grid grid-cols-5 gap-1">
            {attempt.questions.map((x, i) => <button key={x.id} type="button" onClick={() => setIndex(i)} className={cn('rounded border py-1 text-xs', i === index ? 'border-primary' : 'border-border', x.answer && 'bg-primary text-white')}>{i + 1}</button>)}
          </div>
          <p className="mt-3 text-xs text-ink-tertiary">Jangan berpindah tab/aplikasi — tercatat sebagai pelanggaran.</p>
        </Card>
      </div>
    </div>
  )
}

/** POR-08 Hasil seleksi & LoA (404 identik sebelum terbit). */
export function ResultPage() {
  const { id = '' } = useParams()
  const result = useLoad(() => pmbApplicant.result(id), [id])
  const action = useAction()
  const r = result.data

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Hasil Seleksi" breadcrumb={[{ label: 'Beranda', path: PMB.portal.home }, { label: 'Hasil' }]} />
      <Feedback state={action} />
      {result.error?.status === 404 && <Card><EmptyState icon={CalendarClock} title="Hasil seleksi belum tersedia" description="Hasil diumumkan sesuai jadwal gelombang. Anda akan menerima notifikasi." /></Card>}
      {r && (
        <Card>
          <div className={cn('rounded-xl p-6 text-center', r.decision === 'accepted' ? 'bg-primary-50 dark:bg-primary-950' : r.decision === 'waitlisted' ? 'bg-amber-50 dark:bg-amber-950' : 'bg-surface-hover')}>
            <p className="text-sm text-ink-secondary">Keputusan</p>
            <p className="mt-1 text-2xl font-bold text-ink-primary">{DECISION_LABELS[r.decision] ?? r.decision}</p>
            {r.program && <p className="mt-1 text-ink-primary">{r.program} · {r.study_system}</p>}
            {r.waitlisted && <p className="mt-2 text-sm text-ink-secondary">Anda berada pada daftar cadangan. Kami akan menghubungi Anda bila kursi tersedia.</p>}
            {r.scholarship_award?.percent ? <p className="mt-2 text-sm text-primary">Beasiswa jalur masuk: potongan {r.scholarship_award.percent}%</p> : null}
          </div>
          {r.corrected && <Alert variant="info" className="mt-4" title="Hasil telah dikoreksi">{r.correction_note}</Alert>}
          <div className="mt-4 flex flex-wrap gap-2">
            {r.letter_available && <Button leftIcon={<Download className="size-4" />} onClick={() => action.run(async () => window.open((await pmbApplicant.letter(id)).url, '_blank', 'noopener'))}>Unduh surat penerimaan</Button>}
            {r.re_registration && <Link to={to(PMB.portal.reRegistration, { id })}><Button variant="outline">Daftar ulang (batas {dateTime(r.re_registration.deadline_at)})</Button></Link>}
          </div>
          <p className="mt-3 text-xs text-ink-tertiary">Diumumkan {dateTime(r.published_at)}</p>
        </Card>
      )}
    </div>
  )
}

const PDDIKTI_FIELDS: Array<[string, string, Array<[string, string]>?]> = [
  ['religion', 'Agama', [['islam', 'Islam'], ['kristen', 'Kristen'], ['katolik', 'Katolik'], ['hindu', 'Hindu'], ['buddha', 'Buddha'], ['konghucu', 'Konghucu'], ['lainnya', 'Lainnya']]],
  ['mother_name', 'Nama ibu kandung'],
  ['nationality', 'Kewarganegaraan (kode 2 huruf)'],
  ['address_line', 'Alamat'], ['village', 'Kelurahan/Desa'], ['district', 'Kecamatan'], ['city', 'Kabupaten/Kota'], ['province', 'Provinsi'], ['region_code', 'Kode wilayah (Kemendagri)'],
  ['residence_type', 'Jenis tinggal', [['orang_tua', 'Bersama orang tua'], ['wali', 'Wali'], ['kos', 'Kos'], ['asrama', 'Asrama'], ['panti', 'Panti asuhan'], ['lainnya', 'Lainnya']]],
  ['transportation', 'Alat transportasi', [['jalan_kaki', 'Jalan kaki'], ['sepeda', 'Sepeda'], ['motor', 'Sepeda motor'], ['mobil', 'Mobil'], ['angkutan_umum', 'Angkutan umum'], ['ojek', 'Ojek'], ['lainnya', 'Lainnya']]],
  ['almamater_size', 'Ukuran jas almamater', [['S', 'S'], ['M', 'M'], ['L', 'L'], ['XL', 'XL'], ['XXL', 'XXL'], ['XXXL', 'XXXL']]],
]

const CHECK_LABELS: Record<string, string> = {
  OFFER_ACCEPTED: 'Tawaran diterima', PDDIKTI_DATA_COMPLETE: 'Data PDDikti lengkap', GUARDIAN_DATA_COMPLETE: 'Data orang tua lengkap', FINAL_DOCUMENTS_APPROVED: 'Dokumen daftar ulang disetujui',
  HEALTH_CLEARED: 'Surat sehat', PAYMENT_MINIMUM_MET: 'Pembayaran minimum', ORIGINAL_DOCUMENTS_CHECKED: 'Pemeriksaan dokumen asli', NO_OPEN_DUPLICATE_FLAG: 'Tidak ada indikasi duplikat',
}

/** POR-09 Daftar ulang: terima/tolak tawaran, data PDDikti, dokumen, pembayaran, kirim, bukti registrasi. */
export function ReRegistrationPage() {
  const { id = '' } = useParams()
  const rr = useLoad(() => pmbApplicant.reRegistration(id), [id])
  const action = useAction()
  const [agreed, setAgreed] = useState<string[]>([])
  const [declining, setDeclining] = useState(false)
  const [form, setForm] = useState<Record<string, string | boolean>>({})

  useEffect(() => {
    if (rr.data) setForm(Object.fromEntries(Object.entries(rr.data.data).map(([k, v]) => [k, v === null ? '' : v])))
  }, [rr.data])

  if (rr.error) return <Alert variant="danger">{rr.error.status === 404 ? 'Daftar ulang belum tersedia untuk pendaftaran ini.' : rr.error.message}</Alert>
  if (!rr.data) return <p className="text-sm text-ink-secondary">Memuat...</p>

  const d = rr.data
  const awaiting = d.status === 'awaiting_confirmation'
  const active = ['confirmed', 'in_progress', 'revision_requested'].includes(d.status)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Daftar Ulang" description={`${d.program ?? ''} · ${d.study_system ?? ''}`} breadcrumb={[{ label: 'Beranda', path: PMB.portal.home }, { label: 'Daftar ulang' }]} />
      <Feedback state={action} />
      <Card>
        <KeyValue columns={3} items={[['Status', statusLabel(d.status)], ['Batas daftar ulang', dateTime(d.deadline_at)], ['NIM', d.conversion?.nim ?? '—']]} />
      </Card>

      {awaiting && (
        <Card title="Konfirmasi tawaran" description="Terima tawaran untuk melanjutkan daftar ulang. Menerima tawaran ini menarik pendaftaran Anda yang lain pada periode yang sama.">
          {d.declarations.map((decl) => (
            <div key={decl.id} className="mb-3 rounded-lg border border-border p-3"><p className="text-sm font-medium">{decl.title}</p><div className="max-h-40 overflow-y-auto text-sm"><RichText html={decl.body} /></div><Checkbox className="mt-2" label="Saya menyetujui" checked={agreed.includes(decl.id)} onChange={(e) => setAgreed(e.target.checked ? [...agreed, decl.id] : agreed.filter((x) => x !== decl.id))} /></div>
          ))}
          <div className="flex flex-wrap gap-2">
            <Button disabled={agreed.length !== d.declarations.length} isLoading={action.busy} onClick={() => action.run(() => pmbApplicant.acceptOffer(id, agreed, d.lock_version), 'Tawaran diterima.').then(rr.reload)}>Terima tawaran</Button>
            <Button variant="ghost" onClick={() => setDeclining(true)}>Tolak tawaran</Button>
          </div>
        </Card>
      )}

      {(active || d.status === 'submitted' || d.status === 'approved' || d.status === 'enrolled') && (
        <>
          <Card title="Kelengkapan">
            <ul className="grid gap-1 text-sm sm:grid-cols-2">
              {Object.entries(d.checks).map(([code, check]) => <li key={code} className="flex items-center justify-between gap-2 rounded border border-border px-3 py-1.5"><span>{CHECK_LABELS[code] ?? code}</span><StatusBadge status={check.status} /></li>)}
            </ul>
          </Card>
          {d.invoice && <InvoicePanel invoice={d.invoice} allowInstallment onChanged={rr.reload} />}
          <Card title="Data lanjutan (PDDikti)">
            <div className="grid gap-3 sm:grid-cols-2">
              {PDDIKTI_FIELDS.map(([key, label, options]) => {
                const disabled = !active || (d.status === 'revision_requested' && !(d.revision_targets?.fields ?? []).includes(key))

                return options
                  ? <Select key={key} label={label} disabled={disabled} options={options.map(([value, l]) => ({ value, label: l }))} placeholder="Pilih" value={String(form[key] ?? '')} onChange={(e) => setForm({ ...form, [key]: e.target.value })} />
                  : <Input key={key} label={label} disabled={disabled} value={String(form[key] ?? '')} onChange={(e) => setForm({ ...form, [key]: e.target.value })} />
              })}
              <Checkbox label="Penerima KPS/KKS" disabled={!active} checked={Boolean(form.kps_recipient)} onChange={(e) => setForm({ ...form, kps_recipient: e.target.checked })} />
            </div>
            {active && <Button className="mt-4" isLoading={action.busy} onClick={() => action.run(() => pmbApplicant.saveReRegistrationData(id, Object.fromEntries(Object.entries(form).map(([k, v]) => [k, v === '' ? null : v])), d.lock_version), 'Data disimpan.').then(rr.reload)}>Simpan data</Button>}
          </Card>
          {active && <Card title="Orang tua / wali"><GuardianManager /></Card>}
          <DocumentsPanel applicationId={id} stage="re_registration" editable={active} onChanged={rr.reload} />
          {active && (
            <Card>
              <p className="mb-3 text-sm text-ink-secondary">Pastikan semua kelengkapan otomatis terpenuhi lalu kirim untuk divalidasi petugas.</p>
              <Button isLoading={action.busy} onClick={() => action.run(() => pmbApplicant.submitReRegistration(id, d.lock_version), 'Daftar ulang dikirim untuk divalidasi.').then(rr.reload)}>Kirim daftar ulang</Button>
            </Card>
          )}
        </>
      )}

      {d.status === 'enrolled' && (
        <Card title="Selamat! Anda resmi menjadi mahasiswa">
          <p className="text-sm text-ink-secondary">NIM Anda: <strong>{d.conversion?.nim}</strong>. Informasi akun SIAKAD dikirim ke email Anda.</p>
          <Button className="mt-3" leftIcon={<Download className="size-4" />} onClick={() => action.run(async () => window.open((await pmbApplicant.registrationProof(id)).url, '_blank', 'noopener'))}>Unduh bukti registrasi</Button>
          {d.orientation && <div className="mt-4"><p className="font-medium">{d.orientation.title}</p><RichText html={d.orientation.body} /></div>}
        </Card>
      )}
      {['declined', 'expired', 'cancelled'].includes(d.status) && <Alert variant="warning">Daftar ulang {statusLabel(d.status).toLowerCase()}. Hubungi panitia bila ada kekeliruan.</Alert>}

      <FormDialog open={declining} title="Tolak tawaran" description="Kursi Anda akan diberikan kepada pendaftar cadangan. Penolakan dapat dibatalkan panitia maksimal 24 jam." fields={[{ name: 'reason', label: 'Alasan (opsional)', type: 'textarea' }]} submitLabel="Tolak tawaran" onClose={() => setDeclining(false)} onSubmit={async (values) => { await pmbApplicant.declineOffer(id, String(values.reason ?? '')); rr.reload() }} />
    </div>
  )
}
