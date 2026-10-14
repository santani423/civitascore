import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { CheckCircle2, Circle, Download, FileText, Plus, Send } from 'lucide-react'
import { pmbApplicant, type ChatThreadSummary } from '@/pmb/api/applicant'
import { usePortal } from '@/pmb/components/PortalContext'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { FormDialog, toFormData } from '@/pmb/components/FormDialog'
import { useApplicantStore } from '@/pmb/stores/applicantStore'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Input } from '@/components/ui/Input'
import { Textarea } from '@/components/ui/Textarea'
import { PasswordInput } from '@/components/ui/PasswordInput'
import { Checkbox } from '@/components/ui/Checkbox'
import { EmptyState } from '@/components/ui/EmptyState'
import { PageHeader } from '@/components/ui/PageHeader'
import { dateTime, money } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'
import { cn } from '@/utils/cn'
import type { ApplicationSummary } from '@/pmb/api/types'

const STAGES: Array<[string, string]> = [
  ['draft', 'Isi formulir'], ['awaiting_payment', 'Pembayaran'], ['verification', 'Verifikasi'], ['selection', 'Seleksi'], ['result', 'Pengumuman'], ['re_registration', 'Daftar ulang'], ['enrolled', 'Mahasiswa'],
]

/** Posisi linimasa per tahap ApplicationStageResolver (backend). */
const STAGE_POSITION: Record<string, number> = {
  draft: 0, revision_needed: 0, void: 0, awaiting_payment: 1, under_verification: 2, admin_rejected: 2, withdrawn: 2,
  in_selection: 3, result_available: 4, re_registration: 5, re_registration_closed: 5, enrolled: 6,
}

function stageIndex(stage: string): number {
  return STAGE_POSITION[stage] ?? 0
}

/** POR-01 Dashboard pendaftar: status akun, kartu per pendaftaran + langkah berikutnya. */
export function PortalHomePage() {
  const { me, reloadMe } = usePortal()
  const apps = useLoad(() => pmbApplicant.applications(), [])
  const action = useAction()
  const [otp, setOtp] = useState('')

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title={`Halo, ${me?.account.name ?? ''}`} description="Pantau pendaftaran dan langkah berikutnya di sini." actions={<Link to={PMB.portal.newApplication}><Button leftIcon={<Plus className="size-4" />}>Daftar baru</Button></Link>} />
      <Feedback state={action} />
      {me && !me.account.verified && (
        <Alert variant="warning" title="Akun belum terverifikasi">
          <p>Verifikasi email Anda sebelum membuat pendaftaran.</p>
          <div className="mt-2 flex flex-wrap items-end gap-2">
            <Button size="sm" variant="outline" isLoading={action.busy} onClick={() => action.run(() => pmbApplicant.resendVerification(), 'Tautan verifikasi dikirim ulang ke email Anda.')}>Kirim ulang email verifikasi</Button>
            {me.account.phone && (
              <>
                <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbApplicant.sendPhoneOtp(), 'Kode OTP dikirim ke nomor HP Anda.')}>Verifikasi lewat SMS</Button>
                <Input placeholder="Kode OTP" value={otp} maxLength={6} onChange={(e) => setOtp(e.target.value.replace(/\D/g, ''))} />
                <Button size="sm" disabled={otp.length !== 6} onClick={() => action.run(() => pmbApplicant.verifyPhoneOtp(otp), 'Akun terverifikasi.').then(reloadMe)}>Verifikasi</Button>
              </>
            )}
          </div>
        </Alert>
      )}
      {apps.error && <Alert variant="danger">{apps.error.message}</Alert>}
      {apps.data?.length === 0 && (
        <Card><EmptyState icon={FileText} title="Belum ada pendaftaran" description="Pilih gelombang dan jalur yang sedang dibuka untuk mulai mendaftar." action={<Link to={PMB.portal.newApplication}><Button>Mulai daftar</Button></Link>} /></Card>
      )}
      {(apps.data ?? []).map((app) => <ApplicationCard key={app.id} app={app} />)}
    </div>
  )
}

function ApplicationCard({ app }: { app: ApplicationSummary }) {
  const current = stageIndex(app.current_stage)
  const links: Array<[string, string]> = [[to(PMB.portal.application, { id: app.id }), app.form_status === 'draft' || app.form_status === 'revision_requested' ? 'Lanjutkan formulir' : 'Lihat formulir']]
  if (app.verification_status === 'verified') links.push([to(PMB.portal.exams, { id: app.id }), 'Ujian & kartu peserta'])
  if (app.has_published_result) links.push([to(PMB.portal.result, { id: app.id }), 'Hasil seleksi'])
  if (app.re_registration_status) links.push([to(PMB.portal.reRegistration, { id: app.id }), 'Daftar ulang'])

  return (
    <Card title={`${app.path?.name ?? 'Pendaftaran'} · ${app.wave?.name ?? ''}`} description={`${app.period?.name ?? ''}${app.registration_number ? ` · No. ${app.registration_number}` : ''}`} actions={<Badge variant="info">{app.current_stage_label}</Badge>}>
      <ol className="mb-4 flex flex-wrap gap-x-4 gap-y-2">
        {STAGES.map(([key, label], i) => (
          <li key={key} className={cn('flex items-center gap-1.5 text-xs', i <= current ? 'text-primary' : 'text-ink-tertiary')}>
            {i < current ? <CheckCircle2 className="size-4" /> : <Circle className={cn('size-4', i === current && 'fill-primary-100')} />}{label}
          </li>
        ))}
      </ol>
      <div className="mb-3 flex flex-wrap gap-2 text-xs">
        <StatusBadge status={app.form_status} />
        <StatusBadge status={app.registration_payment_status} label={`Bayar: ${app.registration_payment_status === 'paid' ? 'lunas' : app.registration_payment_status === 'not_required' ? 'tidak perlu' : 'belum'}`} />
        <StatusBadge status={app.verification_status} />
        {app.first_choice && <Badge>{app.first_choice}</Badge>}
      </div>
      {app.form_status === 'revision_requested' && app.revision_due_at && <Alert variant="warning" className="mb-3">Perbaiki data/dokumen sebelum {dateTime(app.revision_due_at)}.</Alert>}
      {app.form_status === 'draft' && app.wave?.registration_closes_at && <p className="mb-3 text-xs text-ink-secondary">Kirim formulir sebelum {dateTime(app.wave.registration_closes_at)}.</p>}
      <div className="flex flex-wrap gap-2">{links.map(([href, label]) => <Link key={href} to={href}><Button size="sm" variant="outline">{label}</Button></Link>)}</div>
    </Card>
  )
}

/** POR-03 Pilih gelombang & jalur (dengan kelayakan BR-04). */
export function NewApplicationPage() {
  const navigate = useNavigate()
  const { me } = usePortal()
  const paths = useLoad(() => pmbApplicant.openWavePaths(), [])
  const action = useAction()

  const create = async (wavePathId: string) => {
    const app = await action.run(() => pmbApplicant.createApplication(wavePathId))
    if (app) navigate(to(PMB.portal.application, { id: app.id }))
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Pilih Gelombang & Jalur" description="Hanya jalur yang sedang dibuka yang ditampilkan." />
      <Feedback state={action} />
      {me && !me.account.verified && <Alert variant="warning">Verifikasi akun Anda terlebih dahulu (lihat Beranda).</Alert>}
      {paths.data?.length === 0 && <Card><EmptyState title="Belum ada pendaftaran yang dibuka" description="Pantau jadwal di website PMB." /></Card>}
      <div className="grid gap-4 md:grid-cols-2">
        {(paths.data ?? []).map((p) => (
          <Card key={p.wave_path_id} title={`${p.path.name} — ${p.wave.name}`} description={p.period.name}>
            <p className="text-sm text-ink-secondary">{p.path.description}</p>
            <ul className="mt-3 space-y-1 text-sm">
              <li>Biaya pendaftaran: <strong>{money(p.fee)}</strong> ({p.payment_timing === 'before_submit' ? 'dibayar sebelum kirim formulir' : 'dibayar setelah kirim formulir'})</li>
              <li>Pilihan prodi: maksimal {p.max_choices}</li>
              <li>Ditutup: {dateTime(p.closes_at)}</li>
            </ul>
            {p.public_notes && <p className="mt-2 whitespace-pre-line text-xs text-ink-tertiary">{p.public_notes}</p>}
            {!p.eligible && <Alert variant="warning" className="mt-3">{p.ineligible_reason}</Alert>}
            <Button className="mt-4" disabled={!p.eligible || !me?.account.verified} isLoading={action.busy} onClick={() => create(p.wave_path_id)}>Daftar jalur ini</Button>
          </Card>
        ))}
      </div>
    </div>
  )
}

/** POR-11 Notifikasi in-app. */
export function NotificationsPage() {
  const { reloadMe } = usePortal()
  const [page, setPage] = useState(1)
  const list = useLoad(() => pmbApplicant.notifications(page), [page])

  const read = async (id: string) => {
    await pmbApplicant.readNotification(id)
    list.reload()
    reloadMe()
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Notifikasi" />
      <Card noPadding>
        {list.data?.data.length === 0 && <EmptyState title="Belum ada notifikasi" />}
        <ul className="divide-y divide-border">
          {(list.data?.data ?? []).map((n) => (
            <li key={n.id} className={cn('flex items-start justify-between gap-3 p-4', !n.read_at && 'bg-primary-50/40 dark:bg-primary-950/20')}>
              <div>
                <p className="text-sm font-medium text-ink-primary">{n.title}</p>
                <p className="text-sm text-ink-secondary">{n.message}</p>
                <p className="mt-1 text-xs text-ink-tertiary">{dateTime(n.created_at)}</p>
              </div>
              {!n.read_at && <Button size="sm" variant="ghost" onClick={() => read(n.id)}>Tandai dibaca</Button>}
            </li>
          ))}
        </ul>
        {list.data && list.data.meta.last_page > 1 && (
          <div className="flex justify-between border-t border-border p-3 text-sm">
            <Button size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</Button>
            <span>Halaman {page} / {list.data.meta.last_page}</span>
            <Button size="sm" variant="outline" disabled={page >= list.data.meta.last_page} onClick={() => setPage(page + 1)}>Berikutnya</Button>
          </div>
        )}
      </Card>
    </div>
  )
}

/** POR-10 Pesan (quick chat) dengan panitia. */
export function MessagesPage() {
  const threads = useLoad(() => pmbApplicant.threads(), [])
  const [active, setActive] = useState<ChatThreadSummary | null>(null)
  const [creating, setCreating] = useState(false)
  const apps = useLoad(() => pmbApplicant.applications(), [])

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Pesan" description="Tanyakan kendala pendaftaran kepada panitia." actions={<Button leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Pesan baru</Button>} />
      <Alert variant="info">Panitia PMB TIDAK PERNAH meminta kata sandi atau kode OTP Anda.</Alert>
      <div className="grid gap-4 md:grid-cols-3">
        <Card noPadding className="md:col-span-1">
          {threads.data?.length === 0 && <EmptyState title="Belum ada percakapan" description="Ada pertanyaan? Mulai percakapan." />}
          <ul className="divide-y divide-border">
            {(threads.data ?? []).map((t) => (
              <li key={t.id}>
                <button type="button" onClick={() => setActive(t)} className={cn('w-full p-3 text-left hover:bg-surface-hover', active?.id === t.id && 'bg-surface-hover')}>
                  <div className="flex items-center justify-between gap-2"><span className="truncate text-sm font-medium text-ink-primary">{t.subject}</span>{t.unread > 0 && <Badge variant="danger">{t.unread}</Badge>}</div>
                  <p className="text-xs text-ink-tertiary">{dateTime(t.last_message_at)} · <StatusBadge status={t.status} /></p>
                </button>
              </li>
            ))}
          </ul>
        </Card>
        <div className="md:col-span-2">{active ? <ThreadView threadId={active.id} onChanged={threads.reload} /> : <Card><EmptyState title="Pilih percakapan" /></Card>}</div>
      </div>
      <FormDialog
        open={creating}
        title="Pesan baru"
        onClose={() => setCreating(false)}
        fields={[
          { name: 'subject', label: 'Subjek', required: true },
          { name: 'application_id', label: 'Terkait pendaftaran', type: 'select', options: (apps.data ?? []).map((a) => ({ value: a.id, label: `${a.path?.name} · ${a.registration_number ?? 'draf'}` })) },
          { name: 'message', label: 'Pesan', type: 'textarea', required: true },
          { name: 'attachment', label: 'Lampiran (opsional)', type: 'file', accept: 'image/jpeg,image/png,application/pdf' },
        ]}
        onSubmit={async (values) => {
          const thread = await pmbApplicant.openThread(toFormData(values))
          threads.reload()
          setActive({ id: thread.id, subject: String(values.subject), status: 'open', application_id: null, last_message_at: null, unread: 0 })
        }}
      />
    </div>
  )
}

function ThreadView({ threadId, onChanged }: { threadId: string; onChanged: () => void }) {
  const data = useLoad(() => pmbApplicant.messages(threadId), [threadId])
  const [body, setBody] = useState('')
  const [file, setFile] = useState<File | null>(null)
  const action = useAction()

  const send = async () => {
    const form = new FormData()
    form.append('body', body)
    if (file) form.append('attachment', file)
    const ok = await action.run(() => pmbApplicant.reply(threadId, form))

    if (ok !== undefined) {
      setBody('')
      setFile(null)
      data.reload()
      onChanged()
    }
  }

  return (
    <Card title={data.data?.thread.subject}>
      <div className="flex max-h-[55vh] flex-col gap-2 overflow-y-auto">
        {(data.data?.messages ?? []).map((m) => (
          <div key={m.id} className={cn('max-w-[80%] rounded-lg px-3 py-2 text-sm', m.sender_type === 'applicant' ? 'self-end bg-primary text-white' : m.sender_type === 'system' ? 'self-center bg-amber-50 text-amber-800' : 'self-start bg-surface-hover text-ink-primary')}>
            <p className="whitespace-pre-line">{m.body}</p>
            {m.attachment_url && <a href={m.attachment_url} target="_blank" rel="noopener" className="mt-1 inline-flex items-center gap-1 text-xs underline"><Download className="size-3" />Lampiran</a>}
            <p className="mt-1 text-[10px] opacity-70">{dateTime(m.created_at)}</p>
          </div>
        ))}
      </div>
      {data.data?.thread.status !== 'closed' ? (
        <div className="mt-3 flex flex-col gap-2">
          <Feedback state={action} />
          <Textarea rows={3} value={body} onChange={(e) => setBody(e.target.value)} placeholder="Tulis pesan..." />
          <div className="flex items-center justify-between gap-2">
            <input type="file" accept="image/jpeg,image/png,application/pdf" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="text-xs" />
            <Button leftIcon={<Send className="size-4" />} disabled={!body.trim()} isLoading={action.busy} onClick={send}>Kirim</Button>
          </div>
        </div>
      ) : <p className="mt-3 text-sm text-ink-secondary">Percakapan ditutup.</p>}
    </Card>
  )
}

/** POR-12 Akun & keamanan. */
export function AccountPage() {
  const { me, reloadMe } = usePortal()
  const clear = useApplicantStore((s) => s.clear)
  const sessions = useLoad(() => pmbApplicant.sessions(), [])
  const action = useAction()
  const [profile, setProfile] = useState<{ name: string; phone: string } | null>(null)
  const [pw, setPw] = useState({ current_password: '', password: '', password_confirmation: '' })
  const [emailChange, setEmailChange] = useState({ new_email: '', password: '' })
  const [deactivatePw, setDeactivatePw] = useState('')
  const current = profile ?? { name: me?.account.name ?? '', phone: me?.account.phone ?? '' }

  const exportData = async () => {
    const data = await action.run(() => pmbApplicant.dataExport(), 'Data Anda diunduh.')
    if (!data) return
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = 'data-pmb-saya.json'
    a.click()
    URL.revokeObjectURL(a.href)
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Akun & Keamanan" />
      <Feedback state={action} />
      <div className="grid gap-4 lg:grid-cols-2">
        <Card title="Data akun">
          <div className="flex flex-col gap-3">
            <Input label="Nama" value={current.name} onChange={(e) => setProfile({ ...current, name: e.target.value })} />
            <Input label="Nomor HP" value={current.phone} onChange={(e) => setProfile({ ...current, phone: e.target.value })} hint="Mengganti nomor HP membutuhkan verifikasi ulang." />
            <p className="text-sm text-ink-secondary">Email: {me?.account.email}</p>
            <Button onClick={() => action.run(() => pmbApplicant.updateMe({ name: current.name, phone: current.phone || null }), 'Akun diperbarui.').then(reloadMe)}>Simpan</Button>
            <Checkbox label="Bersedia menerima informasi promosi" checked={me?.marketing_consent ?? false} onChange={(e) => action.run(() => pmbApplicant.marketingConsent(e.target.checked), 'Preferensi disimpan.').then(reloadMe)} />
          </div>
        </Card>
        <Card title="Ubah kata sandi">
          <div className="flex flex-col gap-3">
            <PasswordInput label="Kata sandi saat ini" value={pw.current_password} onChange={(e) => setPw({ ...pw, current_password: e.target.value })} />
            <PasswordInput label="Kata sandi baru" value={pw.password} onChange={(e) => setPw({ ...pw, password: e.target.value })} />
            <PasswordInput label="Ulangi kata sandi baru" value={pw.password_confirmation} onChange={(e) => setPw({ ...pw, password_confirmation: e.target.value })} />
            <Button onClick={() => action.run(() => pmbApplicant.changePassword(pw), 'Kata sandi diubah.').then(() => setPw({ current_password: '', password: '', password_confirmation: '' }))}>Ubah kata sandi</Button>
          </div>
        </Card>
        <Card title="Ganti email">
          <div className="flex flex-col gap-3">
            <Input label="Email baru" type="email" value={emailChange.new_email} onChange={(e) => setEmailChange({ ...emailChange, new_email: e.target.value })} />
            <PasswordInput label="Kata sandi" value={emailChange.password} onChange={(e) => setEmailChange({ ...emailChange, password: e.target.value })} />
            <Button variant="outline" onClick={() => action.run(() => pmbApplicant.requestEmailChange(emailChange), 'Tautan konfirmasi dikirim ke email baru.')}>Kirim tautan konfirmasi</Button>
          </div>
        </Card>
        <Card title="Sesi aktif" actions={<Button size="sm" variant="outline" onClick={() => action.run(() => pmbApplicant.logoutAll()).then(() => clear())}>Keluar dari semua perangkat</Button>}>
          <ul className="divide-y divide-border text-sm">
            {(sessions.data ?? []).map((s) => (
              <li key={String(s.id)} className="flex items-center justify-between gap-2 py-2">
                <span className="min-w-0 truncate">{s.device_name}<span className="block text-xs text-ink-tertiary">Terakhir dipakai {dateTime(s.last_used_at)}</span></span>
                {s.current ? <Badge variant="success">Perangkat ini</Badge> : <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbApplicant.revokeSession(s.id), 'Sesi dicabut.').then(sessions.reload)}>Cabut</Button>}
              </li>
            ))}
          </ul>
        </Card>
        <Card title="Data pribadi saya">
          <p className="mb-3 text-sm text-ink-secondary">Unduh salinan seluruh data pribadi Anda (UU PDP).</p>
          <Button variant="outline" leftIcon={<Download className="size-4" />} onClick={exportData}>Unduh data saya</Button>
        </Card>
        <Card title="Nonaktifkan akun">
          <p className="mb-3 text-sm text-ink-secondary">Tidak dapat dilakukan selama masih ada pendaftaran yang berjalan.</p>
          <div className="flex items-end gap-2">
            <PasswordInput label="Kata sandi" value={deactivatePw} onChange={(e) => setDeactivatePw(e.target.value)} />
            <Button variant="danger" disabled={!deactivatePw} onClick={() => { if (window.confirm('Nonaktifkan akun? Anda tidak dapat masuk lagi.')) action.run(() => pmbApplicant.deactivate(deactivatePw)).then((r) => r !== undefined && clear()) }}>Nonaktifkan</Button>
          </div>
        </Card>
      </div>
    </div>
  )
}
