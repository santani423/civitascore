import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ArrowDown, ArrowUp, CheckCircle2, CircleAlert, Trash2 } from 'lucide-react'
import { pmbApplicant } from '@/pmb/api/applicant'
import type { ApplicationDetail, FormFieldSchema } from '@/pmb/api/types'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { AchievementManager, EducationManager, GuardianManager, IdentityForm } from '@/pmb/components/ProfileManagers'
import { DocumentsPanel } from '@/pmb/components/DocumentsPanel'
import { InvoicePanel } from '@/pmb/components/InvoicePanel'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { RichText } from '@/pmb/components/Section'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { Checkbox } from '@/components/ui/Checkbox'
import { PageHeader } from '@/components/ui/PageHeader'
import { dateTime, money } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'
import { cn } from '@/utils/cn'

type Step = 'identity' | 'education' | 'choices' | 'supporting' | 'documents' | 'payment' | 'declarations'

const STEP_LABELS: Record<Step, string> = {
  identity: 'Data diri', education: 'Pendidikan', choices: 'Pilihan prodi', supporting: 'Data pendukung', documents: 'Dokumen', payment: 'Pembayaran', declarations: 'Pernyataan & kirim',
}

const INFO_SOURCES = [['website', 'Website kampus'], ['social_media', 'Media sosial'], ['school_visit', 'Kunjungan ke sekolah'], ['expo', 'Pameran pendidikan'], ['friend_family', 'Teman / keluarga'], ['alumni', 'Alumni'], ['maukuliah', 'Portal MauKuliah'], ['search_engine', 'Mesin pencari'], ['other', 'Lainnya']].map(([value, label]) => ({ value, label }))

/** POR-04 Formulir wizard (+ POR-05 dokumen, POR-06 pembayaran). */
export function ApplicationPage() {
  const { id = '' } = useParams()
  const navigate = useNavigate()
  const app = useLoad(() => pmbApplicant.application(id), [id])
  const notes = useLoad(() => pmbApplicant.notes(id), [id])
  const action = useAction()
  const [step, setStep] = useState<Step>('identity')
  const [withdrawing, setWithdrawing] = useState(false)

  if (app.error) return <Alert variant="danger">{app.error.status === 404 ? 'Pendaftaran tidak ditemukan.' : app.error.message}</Alert>
  if (!app.data) return <p className="text-sm text-ink-secondary">Memuat...</p>

  const a = app.data
  const editable = a.form_status === 'draft' || a.form_status === 'revision_requested'
  const completeness = Object.fromEntries(a.completeness.steps.map((s) => [s.key, s]))
  const reload = () => app.reload()
  const revisionNotes = (notes.data?.notes ?? []).filter((n) => n.context === 'verification')

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title={`${a.path?.name ?? ''} — ${a.wave?.name ?? ''}`}
        description={`${a.period?.name ?? ''}${a.registration_number ? ` · No. pendaftaran ${a.registration_number}` : ''}`}
        breadcrumb={[{ label: 'Beranda', path: PMB.portal.home }, { label: 'Formulir' }]}
        actions={
          <div className="flex flex-wrap gap-2">
            {a.form_status === 'draft' && <Button variant="ghost" leftIcon={<Trash2 className="size-4" />} onClick={() => window.confirm('Hapus draf pendaftaran ini?') && action.run(() => pmbApplicant.discard(id)).then((r) => r !== undefined && navigate(PMB.portal.home))}>Hapus draf</Button>}
            {['submitted', 'revision_requested', 'locked'].includes(a.form_status) && !a.has_published_result && <Button variant="outline" onClick={() => setWithdrawing(true)}>Tarik pendaftaran</Button>}
          </div>
        }
      />
      <Feedback state={action} />
      <div className="flex flex-wrap gap-2"><Badge variant="info">{a.current_stage_label}</Badge><StatusBadge status={a.form_status} /><StatusBadge status={a.verification_status} /></div>
      {a.form_status === 'revision_requested' && (
        <Alert variant="warning" title={`Perbaiki sebelum ${dateTime(a.revision_due_at)}`}>
          {revisionNotes.map((n, i) => <p key={i}>{n.body}</p>)}
          {a.revision_targets?.documents && a.revision_targets.documents.length > 0 && <p className="mt-1">Dokumen: {a.revision_targets.documents.join(', ')}</p>}
          {a.revision_targets?.fields && a.revision_targets.fields.length > 0 && <p>Data: {a.revision_targets.fields.join(', ')}</p>}
          <p className="mt-1">Setelah diperbaiki, kirim ulang formulir pada langkah terakhir.</p>
        </Alert>
      )}
      {a.verification_status === 'rejected' && <Alert variant="danger" title="Tidak lolos administrasi">{(notes.data?.notes ?? []).map((n, i) => <p key={i}>{n.body}</p>)}</Alert>}
      {a.has_published_result && <Alert variant="success">Hasil seleksi sudah terbit. <Link className="underline" to={to(PMB.portal.result, { id })}>Lihat hasil</Link></Alert>}

      <div className="grid gap-5 lg:grid-cols-4">
        <nav className="flex gap-1 overflow-x-auto lg:flex-col">
          {(Object.keys(STEP_LABELS) as Step[]).map((key) => {
            const complete = key === 'declarations' ? a.form_status !== 'draft' : completeness[key]?.complete

            return (
              <button key={key} type="button" onClick={() => setStep(key)} className={cn('flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-left text-sm', step === key ? 'bg-primary-100 text-primary-700' : 'text-ink-secondary hover:bg-surface-hover')}>
                {complete ? <CheckCircle2 className="size-4 text-primary" /> : <CircleAlert className="size-4 text-amber-500" />}{STEP_LABELS[key]}
              </button>
            )
          })}
        </nav>
        <div className="lg:col-span-3">
          {completeness[step] && !completeness[step].complete && completeness[step].missing.length > 0 && (
            <Alert variant="warning" className="mb-4" title="Belum lengkap">
              <ul className="list-disc pl-5">{completeness[step].missing.map((m, i) => <li key={i}>{m.message ?? m.label ?? m.name ?? (m.semester ? `Nilai rapor semester ${m.semester}` : m.field)}</li>)}</ul>
            </Alert>
          )}
          {step === 'identity' && <Card title="Data diri"><IdentityForm requirements={a.schema.core.identity} editable={a.editable_fields} onSave={async (data) => { await pmbApplicant.saveSection(id, 'identity', data, a.lock_version); reload() }} /></Card>}
          {step === 'education' && <Card title="Riwayat pendidikan" description="Tambah riwayat lalu pilih yang dipakai untuk pendaftaran ini."><EducationManager selectedId={a.primary_education_id} onSelect={(eduId) => action.run(() => pmbApplicant.saveSection(id, 'education', { primary_education_id: eduId }, a.lock_version), 'Pendidikan dipilih.').then(reload)} /></Card>}
          {step === 'choices' && <ChoicesStep app={a} editable={editable} onSaved={reload} />}
          {step === 'supporting' && <SupportingStep app={a} editable={editable} onSaved={reload} />}
          {step === 'documents' && <DocumentsPanel applicationId={id} editable={editable} onChanged={reload} />}
          {step === 'payment' && <PaymentStep app={a} onChanged={reload} />}
          {step === 'declarations' && <SubmitStep app={a} onSubmitted={reload} />}
        </div>
      </div>

      <ReasonDialog open={withdrawing} title="Tarik pendaftaran" description="Pendaftaran yang ditarik tidak dapat diaktifkan kembali. Biaya pendaftaran tidak dikembalikan kecuali sesuai ketentuan." reasonLabel="Alasan (opsional)" confirmLabel="Tarik pendaftaran" onClose={() => setWithdrawing(false)} onConfirm={async (values) => { await pmbApplicant.withdraw(id, String(values.reason ?? '')); reload() }} />
    </div>
  )
}

function ChoicesStep({ app, editable, onSaved }: { app: ApplicationDetail; editable: boolean; onSaved: () => void }) {
  const max = Number(app.config.max_choices ?? 1)
  const [choices, setChoices] = useState(app.choices.map((c) => `${c.study_program_id}|${c.study_system_id}`))
  const action = useAction()
  const options = app.offered_choices.map((o) => ({ value: `${o.study_program_id}|${o.study_system_id}`, label: `${o.study_program} (${o.degree_level}) — ${o.study_system}` }))
  const canEdit = editable && (app.editable_fields === null || app.editable_fields.includes('choices'))

  const move = (i: number, dir: -1 | 1) => {
    const next = [...choices]
    ;[next[i], next[i + dir]] = [next[i + dir], next[i]]
    setChoices(next)
  }

  const save = () => action.run(() => pmbApplicant.saveChoices(app.id, choices.filter(Boolean).map((key, i) => { const [p, s] = key.split('|'); return { priority: i + 1, study_program_id: p, study_system_id: s } }), app.lock_version), 'Pilihan disimpan.').then(onSaved)

  return (
    <Card title="Pilihan program studi" description={`Pilih maksimal ${max} program studi sesuai urutan prioritas.`}>
      <div className="flex flex-col gap-3">
        <Feedback state={action} />
        {options.length === 0 && <Alert variant="warning">Belum ada program studi yang ditawarkan pada jalur ini.</Alert>}
        {choices.map((choice, i) => (
          <div key={i} className="flex items-end gap-2">
            <Select label={`Pilihan ${i + 1}`} disabled={!canEdit} options={options.filter((o) => o.value === choice || !choices.includes(o.value))} placeholder="Pilih program studi" value={choice} onChange={(e) => setChoices(choices.map((c, j) => (j === i ? e.target.value : c)))} />
            {canEdit && <>
              <Button size="sm" variant="ghost" disabled={i === 0} aria-label="Naik" onClick={() => move(i, -1)}><ArrowUp className="size-4" /></Button>
              <Button size="sm" variant="ghost" disabled={i === choices.length - 1} aria-label="Turun" onClick={() => move(i, 1)}><ArrowDown className="size-4" /></Button>
              <Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => setChoices(choices.filter((_, j) => j !== i))}><Trash2 className="size-4" /></Button>
            </>}
          </div>
        ))}
        {canEdit && choices.length < max && <div><Button variant="outline" onClick={() => setChoices([...choices, ''])}>Tambah pilihan</Button></div>}
        {canEdit && <div><Button onClick={save} isLoading={action.busy} disabled={choices.filter(Boolean).length === 0}>Simpan pilihan</Button></div>}
      </div>
    </Card>
  )
}

function SupportingStep({ app, editable, onSaved }: { app: ApplicationDetail; editable: boolean; onSaved: () => void }) {
  const s = app.supporting as Record<string, unknown>
  const [values, setValues] = useState<Record<string, string | boolean>>({
    info_source: String(s.info_source ?? ''), info_source_detail: String(s.info_source_detail ?? ''), referral_code: String(s.referral_code ?? ''),
    kip_number: '', kip_registration_number: String(s.kip_registration_number ?? ''), scholarship_interest: Boolean(s.scholarship_interest), scholarship_program_code: String(s.scholarship_program_code ?? ''),
  })
  const [answers, setAnswers] = useState<Record<string, unknown>>(app.answers)
  const action = useAction()
  const category = app.path?.category
  const allowed = (name: string) => editable && (app.editable_fields === null || app.editable_fields.includes(name))

  useEffect(() => setAnswers(app.answers), [app.answers])

  const save = () => action.run(async () => {
    const data = Object.fromEntries(Object.entries(values).filter(([k, v]) => allowed(k) && !(k === 'kip_number' && v === '')).map(([k, v]) => [k, v === '' ? null : v]))
    const result = await pmbApplicant.saveSection(app.id, 'supporting', data, app.lock_version)
    const fieldKeys = app.schema.fields.map((f) => f.key).filter(allowed)

    if (fieldKeys.length > 0) {
      await pmbApplicant.saveAnswers(app.id, Object.fromEntries(fieldKeys.filter((k) => answers[k] !== '••••').map((k) => [k, answers[k] ?? null])), result.lock_version)
    }
  }, 'Data pendukung disimpan.').then(onSaved)

  const sections = [...new Set(app.schema.fields.map((f) => f.section))]

  return (
    <div className="flex flex-col gap-4">
      <Feedback state={action} />
      <Card title="Orang tua / wali / kontak darurat"><GuardianManager /></Card>
      {category === 'achievement' && <Card title="Prestasi"><AchievementManager /></Card>}
      <Card title="Data pendukung">
        <div className="grid gap-3 sm:grid-cols-2">
          <Select label="Sumber informasi PMB *" disabled={!allowed('info_source')} options={INFO_SOURCES} placeholder="Pilih" value={String(values.info_source)} onChange={(e) => setValues({ ...values, info_source: e.target.value })} />
          <Input label="Detail sumber informasi" disabled={!allowed('info_source_detail')} value={String(values.info_source_detail)} onChange={(e) => setValues({ ...values, info_source_detail: e.target.value })} />
          <Input label="Kode referal (opsional)" disabled={!allowed('referral_code')} value={String(values.referral_code)} onChange={(e) => setValues({ ...values, referral_code: e.target.value })} />
          {(category === 'kip_affirmation' || Boolean(s.kip_number_filled)) && <>
            <Input label="Nomor KIP" disabled={!allowed('kip_number')} placeholder={s.kip_number_filled ? 'Sudah diisi (isi ulang untuk mengubah)' : ''} value={String(values.kip_number)} onChange={(e) => setValues({ ...values, kip_number: e.target.value })} />
            <Input label="Nomor pendaftaran KIP Kuliah" disabled={!allowed('kip_registration_number')} value={String(values.kip_registration_number)} onChange={(e) => setValues({ ...values, kip_registration_number: e.target.value })} />
          </>}
          <Checkbox label="Berminat mengikuti program beasiswa" disabled={!allowed('scholarship_interest')} checked={Boolean(values.scholarship_interest)} onChange={(e) => setValues({ ...values, scholarship_interest: e.target.checked })} />
        </div>
      </Card>
      {sections.map((section) => (
        <Card key={section} title={`Isian tambahan — ${section}`}>
          <div className="grid gap-3 sm:grid-cols-2">
            {app.schema.fields.filter((f) => f.section === section).map((f) => <DynamicField key={f.id} field={f} value={answers[f.key]} disabled={!allowed(f.key)} onChange={(v) => setAnswers({ ...answers, [f.key]: v })} />)}
          </div>
        </Card>
      ))}
      {editable && <div><Button onClick={save} isLoading={action.busy}>Simpan data pendukung</Button></div>}
    </div>
  )
}

/** Field formulir dinamis (FR-CFG-08) sesuai tipe template. */
function DynamicField({ field, value, disabled, onChange }: { field: FormFieldSchema; value: unknown; disabled: boolean; onChange: (v: unknown) => void }) {
  const label = `${field.label}${field.is_required ? ' *' : ''}`
  const options = field.options ?? []

  switch (field.type) {
    case 'textarea':
      return <Textarea label={label} hint={field.help_text ?? undefined} disabled={disabled} value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} />
    case 'select':
    case 'radio':
      return <Select label={label} hint={field.help_text ?? undefined} disabled={disabled} options={options} placeholder="Pilih" value={String(value ?? '')} onChange={(e) => onChange(e.target.value)} />
    case 'multiselect': {
      const list = Array.isArray(value) ? (value as string[]) : []

      return (
        <fieldset className="sm:col-span-2"><legend className="mb-1 text-sm font-medium">{label}</legend>
          <div className="grid grid-cols-2 gap-1">{options.map((o) => <Checkbox key={o.value} label={o.label} disabled={disabled} checked={list.includes(o.value)} onChange={(e) => onChange(e.target.checked ? [...list, o.value] : list.filter((x) => x !== o.value))} />)}</div>
        </fieldset>
      )
    }
    case 'checkbox':
      return <Checkbox label={label} disabled={disabled} checked={value === true || value === '1' || value === 'true'} onChange={(e) => onChange(e.target.checked)} />
    default:
      return <Input label={label} hint={field.help_text ?? undefined} placeholder={field.placeholder ?? undefined} disabled={disabled} type={field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : field.type === 'email' ? 'email' : field.type === 'url' ? 'url' : 'text'} value={field.is_sensitive && value === '••••' ? '' : String(value ?? '')} onChange={(e) => onChange(e.target.value)} />
  }
}

function PaymentStep({ app, onChanged }: { app: ApplicationDetail; onChanged: () => void }) {
  const invoices = useLoad(() => pmbApplicant.invoices(app.id), [app.id, app.lock_version])
  const fee = Number(app.registration_fee ?? 0)
  const registration = (invoices.data ?? []).filter((i) => i.type === 'registration_fee')

  return (
    <div className="flex flex-col gap-4">
      {fee === 0 && <Alert variant="success">Jalur ini tidak memungut biaya pendaftaran.</Alert>}
      {fee > 0 && registration.length === 0 && !invoices.loading && (
        <Alert variant="info">{app.payment_timing === 'after_submit' ? `Tagihan biaya pendaftaran (${money(app.registration_fee)}) dibuat setelah formulir dikirim.` : 'Tagihan sedang disiapkan. Muat ulang halaman ini.'}</Alert>
      )}
      {registration.map((invoice) => <InvoicePanel key={invoice.id} invoice={invoice} onChanged={() => { invoices.reload(); onChanged() }} />)}
    </div>
  )
}

function SubmitStep({ app, onSubmitted }: { app: ApplicationDetail; onSubmitted: () => void }) {
  const [agreed, setAgreed] = useState<string[]>([])
  const action = useAction()
  const canSubmit = (app.form_status === 'draft' || app.form_status === 'revision_requested') && app.completeness.can_submit && agreed.length === app.declarations.length

  if (app.form_status !== 'draft' && app.form_status !== 'revision_requested') {
    return <Card title="Formulir terkirim"><p className="text-sm text-ink-secondary">Formulir dikirim pada {dateTime(app.submitted_at)}. Nomor pendaftaran: <strong>{app.registration_number}</strong>.</p></Card>
  }

  return (
    <Card title="Pernyataan & kirim formulir">
      <div className="flex flex-col gap-4">
        <Feedback state={action} />
        {!app.completeness.can_submit && <Alert variant="warning">Masih ada langkah yang belum lengkap (lihat tanda pada menu langkah).</Alert>}
        {app.declarations.map((d) => (
          <div key={d.id} className="rounded-lg border border-border p-3">
            <p className="text-sm font-medium text-ink-primary">{d.title} <span className="text-xs text-ink-tertiary">v{d.version}</span></p>
            <div className="max-h-40 overflow-y-auto text-sm"><RichText html={d.body} /></div>
            <Checkbox className="mt-2" label="Saya menyatakan data yang saya isi benar dan menyetujui pernyataan di atas" checked={agreed.includes(d.id)} onChange={(e) => setAgreed(e.target.checked ? [...agreed, d.id] : agreed.filter((x) => x !== d.id))} />
          </div>
        ))}
        {app.declarations.length === 0 && <p className="text-sm text-ink-secondary">Dengan mengirim, Anda menyatakan seluruh data yang diisi benar.</p>}
        <p className="text-xs text-ink-tertiary">Setelah dikirim, data identitas terkunci dan hanya dapat diubah bila panitia meminta perbaikan.</p>
        <div><Button disabled={!canSubmit} isLoading={action.busy} onClick={() => action.run(() => pmbApplicant.submit(app.id, agreed, app.lock_version), (r) => `Formulir terkirim. Nomor pendaftaran Anda: ${r.registration_number}`).then(onSubmitted)}>Kirim formulir</Button></div>
      </div>
    </Card>
  )
}
