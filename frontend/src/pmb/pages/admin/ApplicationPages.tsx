import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Eye, EyeOff, Plus } from 'lucide-react'
import { pmbAdmin } from '@/pmb/api/admin'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminList } from '@/pmb/components/AdminList'
import { FormDialog } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { Tabs } from '@/pmb/components/Tabs'
import { KeyValue } from '@/pmb/components/KeyValue'
import { AdminPage, Can, P, options, useLookups } from '@/pmb/components/admin'
import { usePermission } from '@/hooks/usePermission'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { date, dateTime, money } from '@/pmb/lib/format'
import { statusLabel } from '@/pmb/lib/labels'
import { PMB, to } from '@/pmb/routes'

interface Row { id: string; registration_number: string | null; applicant: { id: string; name: string; email: string; phone: string | null }; path: string | null; wave: string | null; first_choice: string | null; form_status: string; verification_status: string; selection_status: string; registration_payment_status: string; reviewer: string | null; submitted_at: string | null }

const STATUS_OPTIONS = (values: string[]) => values.map((v) => ({ value: v, label: statusLabel(v) }))

/** ADM-30 Daftar pendaftar (ber-scope prodi). */
export function ApplicationsPage() {
  const lookups = useLookups()
  const [assisted, setAssisted] = useState(false)
  const [wavePaths, setWavePaths] = useState<Array<{ value: string; label: string }>>([])
  const navigate = useNavigate()

  const openAssisted = async () => {
    setAssisted(true)
    const periods = (lookups?.periods ?? []).filter((p) => p.status === 'published')
    const rows: Array<{ value: string; label: string }> = []

    for (const p of periods) {
      const waves = await pmbAdmin.get<Array<{ id: string; name: string; status: string }>>(`periods/${p.id}/waves`)

      for (const w of waves.filter((x) => x.status === 'open')) {
        const wps = await pmbAdmin.get<Array<{ id: string; admission_path: { name: string } | null }>>(`waves/${w.id}/paths`)
        rows.push(...wps.map((wp) => ({ value: wp.id, label: `${p.name} · ${w.name} · ${wp.admission_path?.name}` })))
      }
    }

    setWavePaths(rows)
  }

  return (
    <AdminPage title="Pendaftar" trail={[{ label: 'Pendaftar' }]} actions={<Can permission={P.APPLICATIONS_ASSISTED}><Button leftIcon={<Plus className="size-4" />} onClick={openAssisted}>Pendaftaran loket</Button></Can>}>
      <AdminList<Row> path="applications" rowKey={(r) => r.id} itemLabel="pendaftar"
        filters={[
          { name: 'period_id', label: 'Periode', type: 'select', options: options(lookups?.periods, (p) => p.name) },
          { name: 'admission_path_id', label: 'Jalur', type: 'select', options: options(lookups?.admission_paths, (p) => p.name) },
          { name: 'study_program_id', label: 'Prodi', type: 'select', options: options(lookups?.study_programs, (p) => p.name) },
          { name: 'form_status', label: 'Formulir', type: 'select', options: STATUS_OPTIONS(['draft', 'submitted', 'revision_requested', 'locked', 'withdrawn', 'void']) },
          { name: 'verification_status', label: 'Verifikasi', type: 'select', options: STATUS_OPTIONS(['not_started', 'in_review', 'revision_requested', 'verified', 'rejected']) },
          { name: 'registration_payment_status', label: 'Pembayaran', type: 'select', options: STATUS_OPTIONS(['unpaid', 'pending', 'paid', 'not_required', 'expired']) },
        ]}
        columns={[
          { header: 'Pendaftar', cell: (r) => <Link to={to(PMB.admin.application, { id: r.id })} className="font-medium text-primary hover:underline">{r.applicant.name}<span className="block text-xs text-ink-tertiary">{r.registration_number ?? 'draf'} · {r.applicant.email}</span></Link> },
          { header: 'Jalur', cell: (r) => <span>{r.path}<span className="block text-xs text-ink-tertiary">{r.wave}</span></span> },
          { header: 'Pilihan 1', cell: (r) => r.first_choice ?? '—' },
          { header: 'Formulir', cell: (r) => <StatusBadge status={r.form_status} /> },
          { header: 'Bayar', cell: (r) => <StatusBadge status={r.registration_payment_status} /> },
          { header: 'Verifikasi', cell: (r) => <span><StatusBadge status={r.verification_status} />{r.reviewer && <span className="block text-xs text-ink-tertiary">{r.reviewer}</span>}</span> },
          { header: 'Dikirim', cell: (r) => dateTime(r.submitted_at) },
        ]} />
      <FormDialog open={assisted} title="Pendaftaran loket" description="Akun dibuat atas nama pendaftar; tautan aktivasi & pengaturan sandi dikirim ke email pendaftar." fields={[
        { name: 'name', label: 'Nama lengkap', required: true }, { name: 'email', label: 'Email', type: 'email', required: true }, { name: 'phone', label: 'Nomor HP' },
        { name: 'wave_path_id', label: 'Langsung buat draf pada jalur (opsional)', type: 'select', options: wavePaths },
        { name: 'info_source', label: 'Sumber informasi', type: 'select', options: ['website', 'social_media', 'school_visit', 'expo', 'friend_family', 'alumni', 'maukuliah', 'search_engine', 'other'].map((v) => ({ value: v, label: v })) },
      ]} onClose={() => setAssisted(false)} onSubmit={async (v) => {
        const result = await pmbAdmin.post<{ application_id: string | null }>('assisted-applications', v, { idempotent: true })
        if (result.application_id) navigate(to(PMB.admin.application, { id: result.application_id }))
      }} />
    </AdminPage>
  )
}

interface Detail {
  id: string; registration_number: string | null; lock_version: number
  statuses: { form: string; verification: string; selection: string; registration_payment: string; current_stage: string }
  account: { id: string; name: string; email: string; phone: string | null; status: string; email_verified_at: string | null; assisted: boolean }
  profile: Record<string, unknown> | null
  educations: Array<Record<string, unknown> & { report_scores: Array<{ semester: number; subject_code: string; score: string }> }>
  guardians: Array<Record<string, unknown>>
  achievements: Array<Record<string, unknown>>
  period: { name: string } | null; wave: { name: string } | null; path: { name: string; category: string } | null
  choices: Array<{ priority: number; study_program_id: string; study_program: string; study_system_id: string; study_system: string }>
  answers: Array<{ field_id: string; label: string | null; section: string | null; value: unknown }>
  application_data: Record<string, unknown> & { revision_targets: Record<string, string[]> | null; declarations: unknown }
  invoices: Array<{ id: string; type: string; invoice_number: string; amount_net: string; amount_paid: string; status: string; due_at: string | null; needs_resolution: boolean }>
  assignment: { reviewer_id: string; reviewer: string | null; claimed_until: string | null; is_mine: boolean } | null
  unmet_for_verification: string[]
  scores: Array<{ id: string; component: string | null; component_code: string | null; raw_score: string | null; passed: boolean | null; source: string; locked: boolean }>
  exams: Array<{ participant_number: string; session: string | null; starts_at: string | null; attendance_status: string }>
  decision: { id: string; decision: string; program: string | null; publication_status: string; version: number; scholarship_award: Record<string, unknown> | null } | null
  re_registration: { id: string; status: string } | null
  conversion: { id: string; status: string; nim: string | null } | null
  duplicate_flags: Array<{ id: string; type: string; similarity_score: string | null; status: string }>
  can: { reveal: boolean; correct: boolean; verify: boolean; override: boolean }
}

interface DocRow { id: string; type: string | null; type_code: string | null; stage: string; slot: number; is_mandatory: boolean; status: string; locked: boolean; requires_original_check: boolean; is_sensitive: boolean; current_version_id: string | null; versions: Array<{ id: string; version_no: number; mime_type: string | null; size: number | null; scan_status: string | null; uploaded_by_type: string; created_at: string | null; reviews: Array<{ decision: string; reason_code: string | null; note_for_applicant: string | null; internal_note: string | null; created_at: string | null }> }> }

type DetailTab = 'data' | 'documents' | 'timeline' | 'notes'

/** ADM-31/32 Detail pendaftar & verifikasi: data (tersamar), dokumen per versi, keputusan, linimasa, catatan. */
export function ApplicationDetailPage() {
  const { id = '' } = useParams()
  const app = useLoad(() => pmbAdmin.get<Detail>(`applications/${id}`), [id])
  const docs = useLoad(() => pmbAdmin.get<{ documents: DocRow[]; notes: Array<{ id: string; context: string; visibility: string; body: string; author: string; created_at: string | null }>; reason_codes: { document: string[]; application_rejection: string[] } }>(`applications/${id}/documents`), [id])
  const timeline = useLoad(() => pmbAdmin.get<Array<{ id: string; lifecycle: string; from: string | null; to: string; actor: string | null; reason_code: string | null; reason_note: string | null; created_at: string | null }>>(`applications/${id}/timeline`), [id])
  const action = useAction()
  const canVerify = usePermission(P.VERIFICATION_DECIDE)
  const [tab, setTab] = useState<DetailTab>('data')
  const [dialog, setDialog] = useState<null | 'revision' | 'reject' | 'reopen' | 'conflict' | 'correct' | 'suspend' | 'choices'>(null)
  const [revealed, setRevealed] = useState<Record<string, string>>({})
  const [revealField, setRevealField] = useState<string | null>(null)
  const [review, setReview] = useState<DocRow | null>(null)
  const [note, setNote] = useState({ body: '', visibility: 'internal' })
  const lookups = useLookups()

  if (!app.data) return <p className="text-sm text-ink-secondary">{app.error?.message ?? 'Memuat...'}</p>

  const a = app.data
  const reload = () => { app.reload(); docs.reload(); timeline.reload() }
  const claimedByMe = a.assignment?.is_mine
  const decidable = canVerify && claimedByMe && ['submitted'].includes(a.statuses.form) && a.statuses.verification === 'in_review'
  const sensitive = (field: string, masked: unknown) => (
    <span className="inline-flex items-center gap-1">{revealed[field] ?? String(masked ?? '—')}
      {a.can.reveal && Boolean(masked) && !revealed[field] && <button type="button" aria-label="Buka" title="Buka (tercatat)" onClick={() => setRevealField(field)}><Eye className="size-3.5 text-ink-tertiary" /></button>}
      {revealed[field] && <button type="button" aria-label="Sembunyikan" onClick={() => setRevealed({ ...revealed, [field]: undefined as unknown as string })}><EyeOff className="size-3.5 text-ink-tertiary" /></button>}
    </span>
  )
  const p = a.profile ?? {}

  return (
    <AdminPage title={a.account.name} description={`${a.registration_number ?? 'Draf'} · ${a.path?.name ?? ''} · ${a.wave?.name ?? ''} · ${a.period?.name ?? ''}`} trail={[{ label: 'Pendaftar', path: PMB.admin.applications }, { label: a.registration_number ?? 'Draf' }]}
      actions={
        <div className="flex flex-wrap gap-2">
          {canVerify && !a.assignment && a.statuses.form === 'submitted' && ['not_started', 'in_review'].includes(a.statuses.verification) && <Button size="sm" onClick={() => action.run(() => pmbAdmin.post(`applications/${id}/claim`), 'Aplikasi diklaim.').then(reload)}>Klaim untuk diperiksa</Button>}
          {claimedByMe && <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post(`applications/${id}/release`), 'Klaim dilepas.').then(reload)}>Lepas klaim</Button>}
          {decidable && <>
            <Button size="sm" disabled={a.unmet_for_verification.length > 0} onClick={() => action.run(() => pmbAdmin.post(`applications/${id}/verify`, { lock_version: a.lock_version }), 'Aplikasi terverifikasi.').then(reload)}>Verifikasi</Button>
            <Button size="sm" variant="outline" onClick={() => setDialog('revision')}>Minta perbaikan</Button>
            <Button size="sm" variant="danger" onClick={() => setDialog('reject')}>Tolak</Button>
          </>}
          {canVerify && <Button size="sm" variant="ghost" onClick={() => setDialog('conflict')}>Konflik kepentingan</Button>}
          {a.can.override && ['verified', 'rejected'].includes(a.statuses.verification) && <Button size="sm" variant="ghost" onClick={() => setDialog('reopen')}>Buka kembali</Button>}
        </div>
      }>
      <Feedback state={action} />
      <div className="flex flex-wrap gap-2">
        <StatusBadge status={a.statuses.form} /><StatusBadge status={a.statuses.registration_payment} /><StatusBadge status={a.statuses.verification} /><StatusBadge status={a.statuses.selection} />
        {a.assignment && <Badge variant="info">Diperiksa: {a.assignment.reviewer}{a.assignment.claimed_until ? ` s.d. ${dateTime(a.assignment.claimed_until)}` : ''}</Badge>}
        {a.duplicate_flags.length > 0 && <Badge variant="danger">Indikasi duplikat ({a.duplicate_flags.length})</Badge>}
      </div>
      {decidable && a.unmet_for_verification.length > 0 && <Alert variant="warning" title="Belum dapat diverifikasi">{a.unmet_for_verification.join(', ')}</Alert>}
      <Tabs<DetailTab> active={tab} onChange={setTab} tabs={[{ key: 'data', label: 'Data' }, { key: 'documents', label: 'Dokumen', badge: docs.data?.documents.filter((d) => ['uploaded', 'in_review'].includes(d.status)).length }, { key: 'timeline', label: 'Linimasa' }, { key: 'notes', label: 'Catatan' }]} />

      {tab === 'data' && (
        <div className="grid gap-4 lg:grid-cols-2">
          <Card title="Akun" actions={<Can permission={P.APPLICATIONS_CORRECT}><Button size="sm" variant="ghost" onClick={() => setDialog('suspend')}>{a.account.status === 'suspended' ? 'Cabut pembekuan' : 'Bekukan akun'}</Button></Can>}>
            <KeyValue items={[['Email', a.account.email], ['HP', a.account.phone], ['Status', statusLabel(a.account.status)], ['Email terverifikasi', dateTime(a.account.email_verified_at)], ['Dibuat loket', a.account.assisted ? 'Ya' : 'Tidak']]} />
          </Card>
          <Card title="Identitas" actions={a.can.correct && <Button size="sm" variant="ghost" onClick={() => setDialog('correct')}>Koreksi</Button>}>
            <KeyValue items={[
              ['Nama', String(p.full_name ?? '—')], ['NIK', sensitive('nik', p.nik_masked)], ['Paspor', sensitive('passport_number', p.passport_number_masked)],
              ['Tempat, tgl lahir', `${p.birth_place ?? ''}, ${date(p.birth_date as string)}`], ['Jenis kelamin', String(p.gender ?? '—')], ['Agama', String(p.religion ?? '—')],
              ['Nama ibu', sensitive('mother_name', p.mother_name_masked)], ['Alamat', `${p.address_line ?? ''} ${p.city ?? ''} ${p.province ?? ''}`],
              ['Kebutuhan khusus', p.has_special_needs ? sensitive('special_needs_note', 'Ada') : 'Tidak ada'],
            ]} />
          </Card>
          <Card title="Pilihan prodi" actions={a.can.correct && <Button size="sm" variant="ghost" onClick={() => setDialog('choices')}>Ubah</Button>}>
            <ol className="list-decimal pl-5 text-sm">{a.choices.map((c) => <li key={c.priority}>{c.study_program} — {c.study_system}</li>)}</ol>
          </Card>
          <Card title="Pendidikan">
            {a.educations.map((e) => (
              <div key={String(e.id)} className="mb-2 text-sm"><p className="font-medium">{String(e.institution_name)} {e.is_primary ? <Badge variant="success">utama</Badge> : null}</p><p className="text-xs text-ink-secondary">{String(e.level)} · {String(e.major ?? '—')} · {String(e.graduation_year ?? '—')} · NISN {String(e.nisn ?? '—')}</p>
                {e.report_scores.length > 0 && <p className="text-xs text-ink-tertiary">Rata-rata rapor: {(e.report_scores.reduce((s, x) => s + Number(x.score), 0) / e.report_scores.length).toFixed(2)}</p>}</div>
            ))}
          </Card>
          <Card title="Orang tua / wali">{a.guardians.map((g) => <p key={String(g.id)} className="text-sm">{String(g.full_name)} ({String(g.relationship)}) · {String(g.phone ?? '—')}</p>)}</Card>
          <Card title="Data pendukung & isian tambahan">
            <KeyValue items={[['Sumber info', String(a.application_data.info_source ?? '—')], ['KIP', sensitive('kip_number', a.application_data.kip_number_masked)], ['Minat beasiswa', a.application_data.scholarship_interest ? 'Ya' : 'Tidak'], ...a.answers.map((x) => [x.label ?? x.field_id, Array.isArray(x.value) ? x.value.join(', ') : String(x.value ?? '—')] as [string, string])]} />
          </Card>
          <Card title="Pembayaran">{a.invoices.map((i) => <div key={i.id} className="flex justify-between text-sm"><span>{i.invoice_number} · {statusLabel(i.type)}</span><span>{money(i.amount_paid)} / {money(i.amount_net)} <StatusBadge status={i.status} /></span></div>)}</Card>
          <Card title="Seleksi & hasil">
            {a.scores.map((s) => <p key={s.id} className="text-sm">{s.component}: {s.raw_score ?? (s.passed === null ? '—' : s.passed ? 'Lulus' : 'Tidak')} {s.locked && <Badge>terkunci</Badge>}</p>)}
            {a.exams.map((x) => <p key={x.participant_number} className="text-xs text-ink-secondary">Ujian {x.participant_number} · {x.session} · {statusLabel(x.attendance_status)}</p>)}
            {a.decision && <p className="mt-2 text-sm">Keputusan v{a.decision.version}: <strong>{statusLabel(a.decision.decision)}</strong> {a.decision.program} ({statusLabel(a.decision.publication_status)})</p>}
            {a.re_registration && <Link className="text-sm text-primary" to={to(PMB.admin.reRegistration, { id: a.re_registration.id })}>Daftar ulang: {statusLabel(a.re_registration.status)}</Link>}
            {a.conversion && <p className="text-sm">NIM: {a.conversion.nim ?? '—'} ({statusLabel(a.conversion.status)})</p>}
          </Card>
        </div>
      )}

      {tab === 'documents' && (
        <Card noPadding>
          <div className="divide-y divide-border">
            {(docs.data?.documents ?? []).map((d) => {
              const current = d.versions.find((v) => v.id === d.current_version_id)

              return (
                <div key={d.id} className="flex flex-wrap items-start justify-between gap-3 p-4">
                  <div className="min-w-0">
                    <p className="text-sm font-medium">{d.type}{d.slot > 1 ? ` #${d.slot}` : ''} {d.is_mandatory && <span className="text-danger">*</span>} {d.is_sensitive && <Badge variant="danger">sensitif</Badge>} <span className="text-xs text-ink-tertiary">({d.stage === 'application' ? 'pendaftaran' : 'daftar ulang'})</span></p>
                    <p className="text-xs text-ink-secondary">{d.versions.length} versi{current ? ` · terbaru v${current.version_no}, ${dateTime(current.created_at)}, pemindaian ${current.scan_status}` : ''}</p>
                    {d.versions.flatMap((v) => v.reviews.map((r, i) => <p key={`${v.id}-${i}`} className="text-xs text-ink-tertiary">v{v.version_no}: {statusLabel(r.decision)} {r.reason_code ?? ''} {r.note_for_applicant ?? ''} {r.internal_note ? `(internal: ${r.internal_note})` : ''}</p>))}
                  </div>
                  <div className="flex flex-wrap items-center gap-2">
                    <StatusBadge status={d.status} />
                    {d.versions.map((v) => <Can key={v.id} permission={P.DOCUMENTS_DOWNLOAD}><Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.open(`document-versions/${v.id}/download`, { purpose: 'verifikasi dokumen' }))}>Lihat v{v.version_no}</Button></Can>)}
                    {current && ['uploaded', 'in_review'].includes(d.status) && (decidable || (d.stage === 're_registration')) && <Button size="sm" onClick={() => setReview(d)}>Tinjau</Button>}
                    {a.can.override && d.status === 'approved' && <Button size="sm" variant="ghost" onClick={() => setReview({ ...d, status: 'reopen' })}>Buka kembali</Button>}
                  </div>
                </div>
              )
            })}
          </div>
        </Card>
      )}

      {tab === 'timeline' && (
        <Card>
          <ol className="relative space-y-3 border-l border-border pl-4">
            {(timeline.data ?? []).map((h) => <li key={h.id}><p className="text-sm"><Badge>{h.lifecycle}</Badge> {h.from ? `${statusLabel(h.from)} → ` : ''}<strong>{statusLabel(h.to)}</strong></p><p className="text-xs text-ink-tertiary">{dateTime(h.created_at)} · {h.actor}{h.reason_code ? ` · ${h.reason_code}` : ''}{h.reason_note ? ` · ${h.reason_note}` : ''}</p></li>)}
          </ol>
        </Card>
      )}

      {tab === 'notes' && (
        <Card>
          <div className="mb-4 flex flex-col gap-2">
            <Textarea label="Catatan baru" value={note.body} onChange={(e) => setNote({ ...note, body: e.target.value })} />
            <div className="flex gap-2"><Select options={[{ value: 'internal', label: 'Internal (staf)' }, { value: 'applicant', label: 'Terlihat pendaftar' }]} value={note.visibility} onChange={(e) => setNote({ ...note, visibility: e.target.value })} /><Button disabled={!note.body.trim()} onClick={() => action.run(() => pmbAdmin.post(`applications/${id}/notes`, note), 'Catatan disimpan.').then(() => { setNote({ body: '', visibility: 'internal' }); docs.reload() })}>Simpan</Button></div>
          </div>
          <ul className="divide-y divide-border">{(docs.data?.notes ?? []).map((n) => <li key={n.id} className="py-2 text-sm"><p className="whitespace-pre-line">{n.body}</p><p className="text-xs text-ink-tertiary">{n.author} · {dateTime(n.created_at)} · {n.visibility === 'applicant' ? 'terlihat pendaftar' : 'internal'} · {n.context}</p></li>)}</ul>
        </Card>
      )}

      <FormDialog open={review !== null} title={review?.status === 'reopen' ? `Buka kembali ${review?.type}` : `Tinjau ${review?.type ?? ''}`} fields={[
        ...(review?.status === 'reopen' ? [] : [{ name: 'decision', label: 'Keputusan', type: 'select' as const, required: true, options: [{ value: 'approved', label: 'Setujui' }, { value: 'revision_requested', label: 'Minta perbaikan' }, { value: 'rejected', label: 'Tolak' }] }]),
        { name: 'reason_code', label: 'Kode alasan', type: 'select', options: (docs.data?.reason_codes.document ?? []).map((c) => ({ value: c, label: c })), visible: (v) => review?.status === 'reopen' || v.decision !== 'approved' },
        { name: 'note_for_applicant', label: 'Catatan untuk pendaftar', type: 'textarea', visible: (v) => review?.status === 'reopen' || v.decision !== 'approved' },
        { name: 'internal_note', label: 'Catatan internal', type: 'textarea' },
      ]} initial={{ decision: 'approved' }} onClose={() => setReview(null)} onSubmit={async (v) => {
        if (!review) return
        await pmbAdmin.post(`application-documents/${review.id}/reviews`, review.status === 'reopen' ? { ...v, decision: 'reopened' } : { ...v, version_id: review.current_version_id })
        reload()
      }} />
      <FormDialog open={dialog === 'revision'} title="Minta perbaikan" fields={[
        { name: 'documents', label: 'Dokumen yang perlu diperbaiki', type: 'multiselect', options: (docs.data?.documents ?? []).filter((d) => d.stage === 'application').map((d) => ({ value: d.type_code ?? d.id, label: d.type ?? d.id })) },
        { name: 'fields', label: 'Data yang perlu diperbaiki', type: 'multiselect', options: ['full_name', 'nik', 'birth_place', 'birth_date', 'address_line', 'choices', 'info_source', 'primary_education_id'].map((f) => ({ value: f, label: f })) },
        { name: 'note_for_applicant', label: 'Catatan untuk pendaftar', type: 'textarea', required: true },
        { name: 'due_at', label: 'Batas perbaikan (kosong = default)', type: 'datetime' },
      ]} onClose={() => setDialog(null)} onSubmit={async (v) => { await pmbAdmin.post(`applications/${id}/request-revision`, { targets: { documents: v.documents, fields: v.fields }, note_for_applicant: v.note_for_applicant, due_at: v.due_at, lock_version: a.lock_version }); reload() }} />
      <FormDialog open={dialog === 'reject'} title="Tolak administrasi" fields={[
        { name: 'reason_code', label: 'Kode alasan', type: 'select', required: true, options: (docs.data?.reason_codes.application_rejection ?? []).map((c) => ({ value: c, label: c })) },
        { name: 'note_for_applicant', label: 'Catatan untuk pendaftar', type: 'textarea', required: true }, { name: 'internal_note', label: 'Catatan internal', type: 'textarea' },
      ]} onClose={() => setDialog(null)} onSubmit={async (v) => { await pmbAdmin.post(`applications/${id}/reject`, { ...v, lock_version: a.lock_version }); reload() }} />
      <ReasonDialog open={dialog === 'reopen'} title="Buka kembali keputusan verifikasi" onClose={() => setDialog(null)} onConfirm={async (v) => { await pmbAdmin.post(`applications/${id}/reopen`, v); reload() }} />
      <ReasonDialog open={dialog === 'conflict'} title="Deklarasi konflik kepentingan" description="Anda tidak akan dapat memeriksa pendaftar ini." onClose={() => setDialog(null)} onConfirm={async (v) => { await pmbAdmin.post(`applications/${id}/conflict-declarations`, v); reload() }} />
      <ReasonDialog open={dialog === 'suspend'} title={a.account.status === 'suspended' ? 'Cabut pembekuan akun' : 'Bekukan akun pendaftar'} onClose={() => setDialog(null)} onConfirm={async (v) => { await pmbAdmin.post(`applicant-accounts/${a.account.id}/suspension`, { ...v, suspended: a.account.status !== 'suspended' }); reload() }} />
      <ReasonDialog open={dialog === 'correct'} title="Koreksi data identitas" description="Hanya isi field yang dikoreksi. Koreksi nama/NIK setelah terverifikasi membuka ulang verifikasi KTP." extraFields={[
        { name: 'full_name', label: 'Nama lengkap' }, { name: 'nik', label: 'NIK' }, { name: 'birth_place', label: 'Tempat lahir' }, { name: 'birth_date', label: 'Tanggal lahir', type: 'date' },
        { name: 'gender', label: 'Jenis kelamin', type: 'select', options: [{ value: 'male', label: 'Laki-laki' }, { value: 'female', label: 'Perempuan' }] }, { name: 'mother_name', label: 'Nama ibu' },
      ]} onClose={() => setDialog(null)} onConfirm={async (v) => {
        const { reason, ...rest } = v
        const changes = Object.fromEntries(Object.entries(rest).filter(([, x]) => x !== null && x !== '' && x !== undefined))
        await pmbAdmin.patch(`applications/${id}/corrections`, { changes, reason })
        reload()
      }} />
      <ReasonDialog open={dialog === 'choices'} title="Ubah pilihan prodi" extraFields={[1, 2].map((n) => ({ name: `choice_${n}`, label: `Pilihan ${n}`, type: 'select' as const, options: (lookups?.study_programs ?? []).flatMap((sp) => (lookups?.study_systems ?? []).map((ss) => ({ value: `${sp.id}|${ss.id}`, label: `${sp.name} — ${ss.name}` }))) }))} onClose={() => setDialog(null)} onConfirm={async (v) => {
        const choices = [v.choice_1, v.choice_2].filter(Boolean).map((x) => { const [study_program_id, study_system_id] = String(x).split('|'); return { study_program_id, study_system_id } })
        await pmbAdmin.put(`applications/${id}/choices`, { choices, lock_version: a.lock_version, reason: v.reason })
        reload()
      }} />
      <ReasonDialog open={revealField !== null} title="Buka data sensitif" description="Akses dicatat (pengguna, waktu, tujuan) dan dapat diaudit." reasonName="purpose" reasonLabel="Tujuan akses" confirmLabel="Buka" onClose={() => setRevealField(null)} onConfirm={async (v) => {
        const r = await pmbAdmin.post<{ field: string; value: string }>(`applications/${id}/reveal`, { field: revealField, purpose: v.purpose })
        setRevealed({ ...revealed, [r.field]: r.value ?? '—' })
      }} />
    </AdminPage>
  )
}
