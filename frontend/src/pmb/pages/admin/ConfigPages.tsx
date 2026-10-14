import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Lock, Pencil, Plus, Trash2 } from 'lucide-react'
import { pmbAdmin } from '@/pmb/api/admin'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminList } from '@/pmb/components/AdminList'
import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { Tabs } from '@/pmb/components/Tabs'
import { AdminPage, Can, P, invalidateLookups, options, useLookups, type Lookups } from '@/pmb/components/admin'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Select } from '@/components/ui/Select'
import { Modal } from '@/components/ui/Modal'
import { DataTable } from '@/components/ui/DataTable'
import { date, dateTime, money } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'

interface Period { id: string; code: string; name: string; status: string; starts_on: string; ends_on: string; description: string | null; entry_academic_term_id: string; waves_count?: number; entry_term?: { academic_year: string; semester: string } | null; has_submitted_applications?: boolean }
interface Wave { id: string; code: string; name: string; sequence: number; status: string; is_ods: boolean; registration_opens_at: string; registration_closes_at: string; payment_deadline_at: string | null; form_lock_at: string | null; exam_starts_on: string | null; exam_ends_on: string | null; announcement_at: string; re_registration_opens_at: string; re_registration_closes_at: string; wave_paths_count?: number }
interface WavePath { id: string; admission_path_id: string; registration_fee: string; payment_timing: string; max_choices: number; allow_cross_system_choice: boolean; form_template_id: string; selection_scheme_id: string | null; quota_mode: string; bulk_verifiable: boolean; is_open: boolean; public_notes: string | null; admission_path: { name: string; code: string } | null; form_template: { code: string; version: number; status: string } | null; selection_scheme: { code: string; version: number; status: string } | null; applications_count?: number; document_requirements_count?: number }
interface Quota { id: string; admission_path_id: string; study_program_id: string; study_system_id: string; capacity: number; waitlist_capacity: number; min_final_score: string | null; accepted_count: number; is_offered: boolean; lock_version: number; study_program: { name: string } | null; study_system: { name: string } | null; admission_path: { name: string } | null }
interface Fee { id: string; study_program_id: string; study_system_id: string; admission_path_id: string | null; component_code: string; name: string; amount: string; is_mandatory_for_enrollment: boolean; installable: boolean; installment_plan: Array<{ percent: number; due_days: number }> | null; is_published: boolean; sort_order: number; study_program: { name: string } | null; study_system: { name: string } | null; admission_path: { name: string } | null }

const PERIOD_FIELDS = (l: Lookups | null): FieldDef[] => [
  { name: 'code', label: 'Kode', required: true, placeholder: 'PMB2027' },
  { name: 'name', label: 'Nama', required: true },
  { name: 'entry_academic_term_id', label: 'Tahun akademik masuk', type: 'select', required: true, options: options(l?.academic_terms, (t) => `${t.academic_year} ${t.semester}`) },
  { name: 'starts_on', label: 'Mulai', type: 'date', required: true },
  { name: 'ends_on', label: 'Selesai', type: 'date', required: true },
  { name: 'description', label: 'Deskripsi', type: 'textarea' },
]

/** ADM-10 Periode PMB. */
export function PeriodsPage() {
  const lookups = useLookups()
  const navigate = useNavigate()
  const [creating, setCreating] = useState(false)

  return (
    <AdminPage title="Periode PMB" trail={[{ label: 'Konfigurasi' }]} actions={<Can permission={P.CONFIG_MANAGE}><Button leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Periode baru</Button></Can>}>
      <AdminList<Period> path="periods" rowKey={(r) => r.id} itemLabel="periode"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: ['draft', 'published', 'closed', 'archived'].map((v) => ({ value: v, label: v })) }]}
        columns={[
          { header: 'Periode', cell: (r) => <Link className="font-medium text-primary hover:underline" to={to(PMB.admin.period, { id: r.id })}>{r.name}<span className="block text-xs text-ink-tertiary">{r.code}</span></Link> },
          { header: 'Tahun masuk', cell: (r) => (r.entry_term ? `${r.entry_term.academic_year} ${r.entry_term.semester}` : '—') },
          { header: 'Rentang', cell: (r) => `${date(r.starts_on)} – ${date(r.ends_on)}` },
          { header: 'Gelombang', cell: (r) => r.waves_count ?? 0 },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
        ]} />
      <FormDialog open={creating} title="Periode baru" fields={PERIOD_FIELDS(lookups)} onClose={() => setCreating(false)} onSubmit={async (v) => {
        const created = await pmbAdmin.post<Period>('periods', v)
        invalidateLookups()
        navigate(to(PMB.admin.period, { id: created.id }))
      }} />
    </AdminPage>
  )
}

type PeriodTab = 'waves' | 'quotas' | 'fees'

/** ADM-10..15 Detail periode: gelombang & jalur, kuota, biaya; publikasi dengan daftar periksa. */
export function PeriodDetailPage() {
  const { id = '' } = useParams()
  const navigate = useNavigate()
  const lookups = useLookups()
  const period = useLoad(() => pmbAdmin.get<Period>(`periods/${id}`), [id])
  const action = useAction()
  const [tab, setTab] = useState<PeriodTab>('waves')
  const [editing, setEditing] = useState(false)
  const [checklist, setChecklist] = useState<{ ready: boolean; missing: string[] } | null>(null)
  const p = period.data

  if (!p) return <p className="text-sm text-ink-secondary">{period.error?.message ?? 'Memuat...'}</p>

  const openChecklist = async () => setChecklist(await pmbAdmin.get<{ ready: boolean; missing: string[] }>(`periods/${id}/publish-checklist`))

  return (
    <AdminPage title={p.name} description={`${p.code} · ${date(p.starts_on)} – ${date(p.ends_on)}`} trail={[{ label: 'Periode', path: PMB.admin.periods }, { label: p.code }]}
      actions={
        <div className="flex flex-wrap gap-2">
          <StatusBadge status={p.status} />
          <Can permission={P.CONFIG_MANAGE}>
            {p.status !== 'archived' && <Button size="sm" variant="outline" leftIcon={<Pencil className="size-4" />} onClick={() => setEditing(true)}>Ubah</Button>}
            {p.status === 'draft' && <Button size="sm" variant="ghost" onClick={() => window.confirm('Hapus periode draf ini?') && action.run(() => pmbAdmin.del(`periods/${id}`)).then((r) => r !== undefined && navigate(PMB.admin.periods))}>Hapus</Button>}
            {p.status === 'published' && <Button size="sm" variant="outline" onClick={() => window.confirm('Tutup periode? Pendaftaran baru tidak dapat dibuat.') && action.run(() => pmbAdmin.post(`periods/${id}/close`), 'Periode ditutup.').then(period.reload)}>Tutup</Button>}
            {p.status === 'closed' && <Button size="sm" variant="outline" onClick={() => action.run(() => pmbAdmin.post(`periods/${id}/archive`), 'Periode diarsipkan.').then(period.reload)}>Arsipkan</Button>}
          </Can>
          <Can permission={P.CONFIG_PUBLISH}>{p.status === 'draft' && <Button size="sm" onClick={openChecklist}>Publikasikan</Button>}</Can>
        </div>
      }>
      <Feedback state={action} />
      {p.has_submitted_applications && <Alert variant="info"><Lock className="mr-1 inline size-4" />Sudah ada pendaftar — perubahan jadwal/kuota/biaya wajib disertai alasan dan tercatat di audit.</Alert>}
      <Tabs<PeriodTab> active={tab} onChange={setTab} tabs={[{ key: 'waves', label: 'Gelombang & jalur' }, { key: 'quotas', label: 'Kuota' }, { key: 'fees', label: 'Biaya' }]} />
      {tab === 'waves' && <WavesTab periodId={id} lookups={lookups} locked={Boolean(p.has_submitted_applications)} />}
      {tab === 'quotas' && <QuotasTab periodId={id} lookups={lookups} locked={Boolean(p.has_submitted_applications)} />}
      {tab === 'fees' && <FeesTab periodId={id} lookups={lookups} locked={Boolean(p.has_submitted_applications)} />}

      <FormDialog open={editing} title="Ubah periode" fields={[...PERIOD_FIELDS(lookups), ...(p.has_submitted_applications ? [{ name: 'reason', label: 'Alasan perubahan', type: 'textarea', required: true } as FieldDef] : [])]} initial={p as unknown as Record<string, unknown>} onClose={() => setEditing(false)} onSubmit={async (v) => { await pmbAdmin.patch(`periods/${id}`, v); period.reload() }} />
      <Modal open={checklist !== null} onClose={() => setChecklist(null)} title="Daftar periksa publikasi">
        {checklist?.ready ? <Alert variant="success">Konfigurasi lengkap. Publikasi membuka jadwal gelombang ke publik.</Alert> : <Alert variant="warning" title="Belum lengkap"><ul className="list-disc pl-5">{checklist?.missing.map((m) => <li key={m}>{m}</li>)}</ul></Alert>}
        <div className="mt-4 flex justify-end gap-2">
          <Button variant="outline" onClick={() => setChecklist(null)}>Tutup</Button>
          <Button disabled={!checklist?.ready} isLoading={action.busy} onClick={() => action.run(() => pmbAdmin.post(`periods/${id}/publish`), 'Periode dipublikasikan.').then(() => { setChecklist(null); period.reload(); invalidateLookups() })}>Publikasikan</Button>
        </div>
      </Modal>
    </AdminPage>
  )
}

const WAVE_FIELDS: FieldDef[] = [
  { name: 'code', label: 'Kode', required: true, placeholder: 'G1' },
  { name: 'name', label: 'Nama', required: true },
  { name: 'sequence', label: 'Urutan', type: 'number', required: true },
  { name: 'registration_opens_at', label: 'Pendaftaran dibuka', type: 'datetime', required: true },
  { name: 'registration_closes_at', label: 'Pendaftaran ditutup', type: 'datetime', required: true },
  { name: 'payment_deadline_at', label: 'Batas pembayaran', type: 'datetime' },
  { name: 'form_lock_at', label: 'Batas perbaikan formulir', type: 'datetime' },
  { name: 'exam_starts_on', label: 'Ujian mulai', type: 'date' },
  { name: 'exam_ends_on', label: 'Ujian selesai', type: 'date' },
  { name: 'announcement_at', label: 'Pengumuman', type: 'datetime', required: true },
  { name: 're_registration_opens_at', label: 'Daftar ulang dibuka', type: 'datetime', required: true },
  { name: 're_registration_closes_at', label: 'Daftar ulang ditutup', type: 'datetime', required: true },
  { name: 'is_ods', label: 'Gelombang ODS (jadwal ujian fleksibel)', type: 'checkbox' },
]

function WavesTab({ periodId, lookups, locked }: { periodId: string; lookups: Lookups | null; locked: boolean }) {
  const waves = useLoad(() => pmbAdmin.get<Wave[]>(`periods/${periodId}/waves`), [periodId])
  const action = useAction()
  const [editing, setEditing] = useState<Wave | 'new' | null>(null)
  const [extending, setExtending] = useState<Wave | null>(null)
  const [selected, setSelected] = useState<Wave | null>(null)
  const reasonField: FieldDef[] = locked ? [{ name: 'reason', label: 'Alasan perubahan', type: 'textarea', required: true }] : []

  return (
    <div className="flex flex-col gap-4">
      <Feedback state={action} />
      <Card title="Gelombang" noPadding actions={<Can permission={P.CONFIG_MANAGE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Gelombang</Button></Can>}>
        <DataTable rowKey={(w) => w.id} data={waves.data ?? []} emptyMessage={waves.loading ? 'Memuat...' : 'Belum ada gelombang.'} columns={[
          { header: 'Gelombang', cell: (w) => <button type="button" className="text-left font-medium text-primary" onClick={() => setSelected(w)}>{w.name}<span className="block text-xs text-ink-tertiary">{w.code}{w.is_ods ? ' · ODS' : ''}</span></button> },
          { header: 'Pendaftaran', cell: (w) => `${dateTime(w.registration_opens_at)} – ${dateTime(w.registration_closes_at)}` },
          { header: 'Pengumuman', cell: (w) => dateTime(w.announcement_at) },
          { header: 'Jalur', cell: (w) => w.wave_paths_count ?? 0 },
          { header: 'Status', cell: (w) => <StatusBadge status={w.status} /> },
          { header: '', cell: (w) => (
            <Can permission={P.CONFIG_MANAGE}>
              <div className="flex gap-1">
                <Button size="sm" variant="ghost" onClick={() => setEditing(w)}>Ubah</Button>
                {['open', 'closed'].includes(w.status) && <Button size="sm" variant="ghost" onClick={() => setExtending(w)}>Perpanjang</Button>}
                <Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus gelombang?') && action.run(() => pmbAdmin.del(`waves/${w.id}`), 'Gelombang dihapus.').then(waves.reload)}><Trash2 className="size-4" /></Button>
              </div>
            </Can>
          ) },
        ]} />
      </Card>
      {selected && <WavePathsCard wave={selected} lookups={lookups} locked={locked} />}
      <FormDialog open={editing !== null} wide title={editing === 'new' ? 'Gelombang baru' : 'Ubah gelombang'} fields={[...WAVE_FIELDS, ...(editing !== 'new' ? reasonField : [])]} initial={editing && editing !== 'new' ? (editing as unknown as Record<string, unknown>) : { sequence: (waves.data?.length ?? 0) + 1 }} onClose={() => setEditing(null)} onSubmit={async (v) => {
        if (editing === 'new') await pmbAdmin.post(`periods/${periodId}/waves`, v)
        else if (editing) await pmbAdmin.patch(`waves/${editing.id}`, v)
        waves.reload()
      }} />
      <ReasonDialog open={extending !== null} title="Perpanjang pendaftaran" extraFields={[{ name: 'registration_closes_at', label: 'Tanggal tutup baru', type: 'datetime', required: true }]} onClose={() => setExtending(null)} onConfirm={async (v) => { if (extending) await pmbAdmin.patch(`waves/${extending.id}/extend`, v); waves.reload() }} />
    </div>
  )
}

function WavePathsCard({ wave, lookups, locked }: { wave: Wave; lookups: Lookups | null; locked: boolean }) {
  const paths = useLoad(() => pmbAdmin.get<WavePath[]>(`waves/${wave.id}/paths`), [wave.id])
  const action = useAction()
  const [editing, setEditing] = useState<WavePath | 'new' | null>(null)
  const [toggling, setToggling] = useState<WavePath | null>(null)
  const [requirements, setRequirements] = useState<WavePath | null>(null)
  const fields: FieldDef[] = [
    ...(editing === 'new' ? [{ name: 'admission_path_id', label: 'Jalur', type: 'select', required: true, options: options(lookups?.admission_paths.filter((x) => x.is_active), (x) => x.name) } as FieldDef] : []),
    { name: 'registration_fee', label: 'Biaya pendaftaran (Rp)', type: 'number', required: true },
    { name: 'payment_timing', label: 'Waktu pembayaran', type: 'select', required: true, options: [{ value: 'after_submit', label: 'Setelah kirim formulir' }, { value: 'before_submit', label: 'Sebelum kirim formulir' }] },
    { name: 'max_choices', label: 'Maksimal pilihan prodi', type: 'number', required: true },
    { name: 'allow_cross_system_choice', label: 'Boleh beda sistem kuliah antarpilihan', type: 'checkbox' },
    { name: 'form_template_id', label: 'Template formulir (terpublikasi)', type: 'select', required: true, options: options(lookups?.form_templates.filter((t) => t.status === 'published'), (t) => `${t.code} v${t.version}`) },
    { name: 'selection_scheme_id', label: 'Skema seleksi (terkunci)', type: 'select', options: options(lookups?.selection_schemes.filter((s) => s.status === 'locked'), (s) => `${s.code} v${s.version}`) },
    { name: 'quota_mode', label: 'Mode kuota', type: 'select', required: true, options: [{ value: 'period', label: 'Kuota periode' }, { value: 'wave_cap', label: 'Batas per gelombang' }] },
    { name: 'bulk_verifiable', label: 'Boleh verifikasi massal', type: 'checkbox' },
    { name: 'is_open', label: 'Dibuka', type: 'checkbox' },
    { name: 'public_notes', label: 'Catatan publik', type: 'textarea' },
    ...(locked && editing !== 'new' ? [{ name: 'reason', label: 'Alasan perubahan', type: 'textarea', required: true } as FieldDef] : []),
  ]

  return (
    <Card title={`Jalur pada ${wave.name}`} noPadding actions={<Can permission={P.CONFIG_MANAGE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Buka jalur</Button></Can>}>
      <div className="px-4 pt-3"><Feedback state={action} /></div>
      <DataTable rowKey={(r) => r.id} data={paths.data ?? []} emptyMessage="Belum ada jalur pada gelombang ini." columns={[
        { header: 'Jalur', cell: (r) => r.admission_path?.name },
        { header: 'Biaya', cell: (r) => `${money(r.registration_fee)} · ${r.payment_timing === 'before_submit' ? 'sebelum kirim' : 'setelah kirim'}` },
        { header: 'Template', cell: (r) => (r.form_template ? `${r.form_template.code} v${r.form_template.version}` : '—') },
        { header: 'Skema', cell: (r) => (r.selection_scheme ? `${r.selection_scheme.code} v${r.selection_scheme.version}` : <Badge variant="warning">Belum ada</Badge>) },
        { header: 'Dokumen', cell: (r) => <button type="button" className="text-primary" onClick={() => setRequirements(r)}>{r.document_requirements_count ?? 0} persyaratan</button> },
        { header: 'Pendaftar', cell: (r) => r.applications_count ?? 0 },
        { header: 'Status', cell: (r) => <Badge variant={r.is_open ? 'success' : 'neutral'}>{r.is_open ? 'Dibuka' : 'Ditutup'}</Badge> },
        { header: '', cell: (r) => (
          <Can permission={P.CONFIG_MANAGE}>
            <div className="flex gap-1">
              <Button size="sm" variant="ghost" onClick={() => setEditing(r)}>Ubah</Button>
              <Button size="sm" variant="ghost" onClick={() => setToggling(r)}>{r.is_open ? 'Tutup' : 'Buka'}</Button>
              <Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus jalur dari gelombang?') && action.run(() => pmbAdmin.del(`wave-paths/${r.id}`), 'Dihapus.').then(paths.reload)}><Trash2 className="size-4" /></Button>
            </div>
          </Can>
        ) },
      ]} />
      <FormDialog open={editing !== null} wide title={editing === 'new' ? 'Buka jalur pada gelombang' : 'Ubah gelombang-jalur'} fields={fields} initial={editing && editing !== 'new' ? (editing as unknown as Record<string, unknown>) : { payment_timing: 'after_submit', max_choices: 2, quota_mode: 'period', is_open: true, registration_fee: 0 }} onClose={() => setEditing(null)} onSubmit={async (v) => {
        if (editing === 'new') await pmbAdmin.post(`waves/${wave.id}/paths`, v)
        else if (editing) await pmbAdmin.patch(`wave-paths/${editing.id}`, v)
        paths.reload()
      }} />
      <ReasonDialog open={toggling !== null} title={toggling?.is_open ? 'Tutup pendaftaran jalur' : 'Buka pendaftaran jalur'} onClose={() => setToggling(null)} onConfirm={async (v) => { if (toggling) await pmbAdmin.patch(`wave-paths/${toggling.id}/toggle-open`, { ...v, is_open: !toggling.is_open }); paths.reload() }} />
      {requirements && <RequirementsEditor wavePath={requirements} lookups={lookups} locked={locked} onClose={() => { setRequirements(null); paths.reload() }} />}
    </Card>
  )
}

interface Requirement { document_type_id: string; stage: string; is_mandatory: boolean; instructions: string | null; condition: Record<string, unknown> | null; sort_order: number }

/** ADM-14 Persyaratan dokumen per gelombang-jalur (wajib/opsional, tahap, instruksi, kondisi). */
function RequirementsEditor({ wavePath, lookups, locked, onClose }: { wavePath: WavePath; lookups: Lookups | null; locked: boolean; onClose: () => void }) {
  const [rows, setRows] = useState<Requirement[]>([])
  const [reason, setReason] = useState('')
  const action = useAction()

  useEffect(() => {
    pmbAdmin.get<Requirement[]>(`wave-paths/${wavePath.id}/document-requirements`).then((r) => setRows(r.map((x) => ({ document_type_id: x.document_type_id, stage: x.stage, is_mandatory: x.is_mandatory, instructions: x.instructions, condition: x.condition, sort_order: x.sort_order }))))
  }, [wavePath.id])

  const update = (i: number, patch: Partial<Requirement>) => setRows(rows.map((r, j) => (j === i ? { ...r, ...patch } : r)))

  return (
    <Modal open onClose={onClose} title={`Persyaratan dokumen — ${wavePath.admission_path?.name}`} className="max-w-4xl">
      <Feedback state={action} />
      <table className="mt-2 w-full text-sm">
        <thead><tr className="text-left text-xs text-ink-tertiary"><th className="p-1">Jenis dokumen</th><th className="p-1">Tahap</th><th className="p-1">Wajib</th><th className="p-1">Instruksi</th><th /></tr></thead>
        <tbody>
          {rows.map((r, i) => (
            <tr key={i}>
              <td className="p-1"><Select options={options(lookups?.document_types, (d) => d.name)} value={r.document_type_id} onChange={(e) => update(i, { document_type_id: e.target.value })} /></td>
              <td className="p-1"><Select options={[{ value: 'application', label: 'Pendaftaran' }, { value: 're_registration', label: 'Daftar ulang' }]} value={r.stage} onChange={(e) => update(i, { stage: e.target.value })} /></td>
              <td className="p-1 text-center"><input type="checkbox" checked={r.is_mandatory} onChange={(e) => update(i, { is_mandatory: e.target.checked })} /></td>
              <td className="p-1"><input className="w-full rounded border border-border bg-surface px-2 py-1" value={r.instructions ?? ''} onChange={(e) => update(i, { instructions: e.target.value })} /></td>
              <td><button type="button" aria-label="Hapus" onClick={() => setRows(rows.filter((_, j) => j !== i))}><Trash2 className="size-4 text-ink-tertiary" /></button></td>
            </tr>
          ))}
        </tbody>
      </table>
      <Button size="sm" variant="outline" className="mt-2" leftIcon={<Plus className="size-4" />} onClick={() => setRows([...rows, { document_type_id: lookups?.document_types[0]?.id ?? '', stage: 'application', is_mandatory: true, instructions: null, condition: null, sort_order: rows.length }])}>Tambah baris</Button>
      {locked && <textarea className="mt-3 w-full rounded border border-border bg-surface p-2 text-sm" placeholder="Alasan perubahan (wajib setelah ada pendaftar)" value={reason} onChange={(e) => setReason(e.target.value)} />}
      <div className="mt-4 flex justify-end gap-2">
        <Button variant="outline" onClick={onClose}>Tutup</Button>
        <Can permission={P.CONFIG_MANAGE}><Button isLoading={action.busy} onClick={() => action.run(() => pmbAdmin.put(`wave-paths/${wavePath.id}/document-requirements/bulk`, { rows: rows.map((r, i) => ({ ...r, sort_order: i })), reason: reason || null }), 'Persyaratan disimpan.')}>Simpan</Button></Can>
      </div>
    </Modal>
  )
}

/** ADM-13 Kuota: tabel editable + salin dari periode lain. */
function QuotasTab({ periodId, lookups, locked }: { periodId: string; lookups: Lookups | null; locked: boolean }) {
  const quotas = useLoad(() => pmbAdmin.get<Quota[]>(`periods/${periodId}/quotas`), [periodId])
  const action = useAction()
  const [rows, setRows] = useState<Array<Partial<Quota> & { key: string }>>([])
  const [reason, setReason] = useState('')
  const [copyFrom, setCopyFrom] = useState('')

  useEffect(() => {
    setRows((quotas.data ?? []).map((q) => ({ ...q, key: q.id })))
  }, [quotas.data])

  const update = (key: string, patch: Partial<Quota>) => setRows(rows.map((r) => (r.key === key ? { ...r, ...patch } : r)))
  const save = () => action.run(() => pmbAdmin.put(`periods/${periodId}/quotas/bulk`, {
    rows: rows.filter((r) => r.admission_path_id && r.study_program_id && r.study_system_id).map((r) => ({ admission_path_id: r.admission_path_id, study_program_id: r.study_program_id, study_system_id: r.study_system_id, capacity: Number(r.capacity ?? 0), waitlist_capacity: Number(r.waitlist_capacity ?? 0), min_final_score: r.min_final_score === '' || r.min_final_score === null || r.min_final_score === undefined ? null : Number(r.min_final_score), is_offered: Boolean(r.is_offered), lock_version: r.lock_version })),
    reason: reason || null,
  }), 'Kuota disimpan.').then(quotas.reload)

  const cell = 'w-20 rounded border border-border bg-surface px-1 py-0.5'

  return (
    <Card title="Kuota per prodi × jalur × sistem kuliah">
      <Feedback state={action} />
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead><tr className="text-left text-xs text-ink-tertiary"><th className="p-1">Jalur</th><th className="p-1">Program studi</th><th className="p-1">Sistem</th><th className="p-1">Kapasitas</th><th className="p-1">Cadangan</th><th className="p-1">Ambang skor</th><th className="p-1">Diterima</th><th className="p-1">Ditawarkan</th></tr></thead>
          <tbody>
            {rows.map((r) => (
              <tr key={r.key} className="border-t border-border">
                <td className="p-1">{r.id ? r.admission_path?.name : <Select options={options(lookups?.admission_paths, (x) => x.name)} placeholder="Jalur" value={r.admission_path_id ?? ''} onChange={(e) => update(r.key, { admission_path_id: e.target.value })} />}</td>
                <td className="p-1">{r.id ? r.study_program?.name : <Select options={options(lookups?.study_programs, (x) => `${x.name} (${x.degree_level})`)} placeholder="Prodi" value={r.study_program_id ?? ''} onChange={(e) => update(r.key, { study_program_id: e.target.value })} />}</td>
                <td className="p-1">{r.id ? r.study_system?.name : <Select options={options(lookups?.study_systems, (x) => x.name)} placeholder="Sistem" value={r.study_system_id ?? ''} onChange={(e) => update(r.key, { study_system_id: e.target.value })} />}</td>
                <td className="p-1"><input type="number" min={0} className={cell} value={r.capacity ?? 0} onChange={(e) => update(r.key, { capacity: Number(e.target.value) })} /></td>
                <td className="p-1"><input type="number" min={0} className={cell} value={r.waitlist_capacity ?? 0} onChange={(e) => update(r.key, { waitlist_capacity: Number(e.target.value) })} /></td>
                <td className="p-1"><input type="number" min={0} max={100} step="0.01" className={cell} value={r.min_final_score ?? ''} onChange={(e) => update(r.key, { min_final_score: e.target.value })} /></td>
                <td className="p-1">{r.accepted_count ?? 0}</td>
                <td className="p-1"><input type="checkbox" checked={Boolean(r.is_offered)} onChange={(e) => update(r.key, { is_offered: e.target.checked })} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Can permission={P.CONFIG_MANAGE}>
        <div className="mt-3 flex flex-wrap items-end gap-2">
          <Button size="sm" variant="outline" leftIcon={<Plus className="size-4" />} onClick={() => setRows([...rows, { key: `new-${rows.length}-${Date.now()}`, capacity: 0, waitlist_capacity: 0, is_offered: true }])}>Tambah baris</Button>
          <Select options={options(lookups?.periods.filter((p) => p.id !== periodId), (p) => p.name)} placeholder="Salin dari periode..." value={copyFrom} onChange={(e) => setCopyFrom(e.target.value)} />
          <Button size="sm" variant="outline" disabled={!copyFrom} onClick={() => action.run(() => pmbAdmin.post<{ copied: number }>(`periods/${periodId}/quotas/copy-from/${copyFrom}`), (r) => `${r.copied} kuota disalin.`).then(quotas.reload)}>Salin</Button>
        </div>
        {locked && <textarea className="mt-3 w-full rounded border border-border bg-surface p-2 text-sm" placeholder="Alasan perubahan (wajib setelah gelombang dibuka)" value={reason} onChange={(e) => setReason(e.target.value)} />}
        <Button className="mt-3" isLoading={action.busy} onClick={save}>Simpan kuota</Button>
      </Can>
    </Card>
  )
}

/** ADM-15 Biaya per prodi × sistem × jalur dengan rencana cicilan. */
function FeesTab({ periodId, lookups, locked }: { periodId: string; lookups: Lookups | null; locked: boolean }) {
  const fees = useLoad(() => pmbAdmin.get<Fee[]>(`periods/${periodId}/fee-components`), [periodId])
  const action = useAction()
  const [editing, setEditing] = useState<Fee | 'new' | null>(null)
  const [copyFrom, setCopyFrom] = useState('')

  return (
    <Card title="Komponen biaya" noPadding actions={<Can permission={P.FEES_MANAGE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Komponen</Button></Can>}>
      <div className="px-4 pt-3"><Feedback state={action} /></div>
      <DataTable rowKey={(r) => r.id} data={fees.data ?? []} emptyMessage="Belum ada komponen biaya." columns={[
        { header: 'Prodi', cell: (r) => r.study_program?.name }, { header: 'Sistem', cell: (r) => r.study_system?.name }, { header: 'Jalur', cell: (r) => r.admission_path?.name ?? 'Semua' },
        { header: 'Komponen', cell: (r) => `${r.name} (${r.component_code})` }, { header: 'Nominal', cell: (r) => money(r.amount) },
        { header: 'Cicilan', cell: (r) => (r.installable && r.installment_plan ? r.installment_plan.map((t) => `${t.percent}%/${t.due_days}h`).join(' + ') : '—') },
        { header: 'Publik', cell: (r) => (r.is_published ? 'Ya' : 'Tidak') },
        { header: '', cell: (r) => <Can permission={P.FEES_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => setEditing(r)}>Ubah</Button><Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus komponen?') && action.run(() => pmbAdmin.del(`fee-components/${r.id}`), 'Dihapus.').then(fees.reload)}><Trash2 className="size-4" /></Button></div></Can> },
      ]} />
      <Can permission={P.FEES_MANAGE}>
        <div className="flex items-end gap-2 p-3">
          <Select options={options(lookups?.periods.filter((p) => p.id !== periodId), (p) => p.name)} placeholder="Salin dari periode..." value={copyFrom} onChange={(e) => setCopyFrom(e.target.value)} />
          <Button size="sm" variant="outline" disabled={!copyFrom} onClick={() => action.run(() => pmbAdmin.post<{ copied: number }>(`periods/${periodId}/fee-components/copy-from/${copyFrom}`), (r) => `${r.copied} komponen disalin.`).then(fees.reload)}>Salin</Button>
        </div>
      </Can>
      <FormDialog open={editing !== null} wide title="Komponen biaya" fields={[
        { name: 'study_program_id', label: 'Program studi', type: 'select', required: true, options: options(lookups?.study_programs, (x) => x.name) },
        { name: 'study_system_id', label: 'Sistem kuliah', type: 'select', required: true, options: options(lookups?.study_systems, (x) => x.name) },
        { name: 'admission_path_id', label: 'Khusus jalur (kosong = semua)', type: 'select', options: options(lookups?.admission_paths, (x) => x.name) },
        { name: 'component_code', label: 'Kode komponen', required: true, placeholder: 'SPP' },
        { name: 'name', label: 'Nama', required: true },
        { name: 'amount', label: 'Nominal (Rp)', type: 'number', required: true },
        { name: 'is_mandatory_for_enrollment', label: 'Wajib untuk daftar ulang', type: 'checkbox' },
        { name: 'installable', label: 'Dapat dicicil', type: 'checkbox' },
        { name: 'installment_plan', label: 'Rencana cicilan (JSON: [{"percent":50,"due_days":0},{"percent":50,"due_days":30}])', type: 'json', visible: (v) => Boolean(v.installable) },
        { name: 'is_published', label: 'Tampilkan di website', type: 'checkbox' },
        { name: 'sort_order', label: 'Urutan', type: 'number' },
        ...(locked ? [{ name: 'reason', label: 'Alasan perubahan', type: 'textarea', required: true } as FieldDef] : []),
      ]} initial={editing && editing !== 'new' ? (editing as unknown as Record<string, unknown>) : { is_mandatory_for_enrollment: true, is_published: true }} onClose={() => setEditing(null)} onSubmit={async (v) => {
        const { reason, ...row } = v
        await pmbAdmin.put(`periods/${periodId}/fee-components/bulk`, { rows: [{ ...row, installable: Boolean(row.installable), is_mandatory_for_enrollment: Boolean(row.is_mandatory_for_enrollment), is_published: Boolean(row.is_published) }], reason })
        fees.reload()
      }} />
    </Card>
  )
}

type MasterTab = 'paths' | 'systems' | 'documents'

/** ADM-11/14 Master: jalur masuk, sistem kuliah, katalog jenis dokumen. */
export function MasterDataPage() {
  const [tab, setTab] = useState<MasterTab>('paths')
  const [reload, setReload] = useState(0)
  const [editing, setEditing] = useState<Record<string, unknown> | 'new' | null>(null)
  const config: Record<MasterTab, { path: string; title: string; fields: FieldDef[]; columns: Array<{ header: string; cell: (r: Record<string, unknown>) => React.ReactNode }> }> = {
    paths: {
      path: 'admission-paths', title: 'Jalur masuk',
      fields: [
        { name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true },
        { name: 'category', label: 'Kategori', type: 'select', required: true, options: ['regular', 'achievement', 'kip_affirmation', 'transfer', 'postgraduate', 'rpl', 'scholarship', 'partnership', 'international', 'other'].map((v) => ({ value: v, label: v })) },
        { name: 'description', label: 'Deskripsi', type: 'textarea' },
        { name: 'eligible_degree_levels', label: 'Jenjang yang dibuka', type: 'multiselect', options: ['D3', 'D4', 'S1', 'S2', 'S3', 'Profesi'].map((v) => ({ value: v, label: v })) },
        { name: 'requires_exam', label: 'Berujian', type: 'checkbox' }, { name: 'requires_previous_institution', label: 'Wajib asal perguruan tinggi', type: 'checkbox' }, { name: 'is_active', label: 'Aktif', type: 'checkbox' },
      ],
      columns: [{ header: 'Kode', cell: (r) => String(r.code) }, { header: 'Nama', cell: (r) => String(r.name) }, { header: 'Kategori', cell: (r) => String(r.category) }, { header: 'Ujian', cell: (r) => (r.requires_exam ? 'Ya' : 'Tidak') }, { header: 'Status', cell: (r) => <Badge variant={r.is_active ? 'success' : 'neutral'}>{r.is_active ? 'Aktif' : 'Nonaktif'}</Badge> }],
    },
    systems: {
      path: 'study-systems', title: 'Sistem kuliah',
      fields: [{ name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true }, { name: 'schedule_note', label: 'Keterangan jadwal' }, { name: 'is_active', label: 'Aktif', type: 'checkbox' }],
      columns: [{ header: 'Kode', cell: (r) => String(r.code) }, { header: 'Nama', cell: (r) => String(r.name) }, { header: 'Jadwal', cell: (r) => String(r.schedule_note ?? '—') }, { header: 'Status', cell: (r) => <Badge variant={r.is_active ? 'success' : 'neutral'}>{r.is_active ? 'Aktif' : 'Nonaktif'}</Badge> }],
    },
    documents: {
      path: 'document-types', title: 'Jenis dokumen',
      fields: [
        { name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true }, { name: 'description', label: 'Deskripsi', type: 'textarea' },
        { name: 'allowed_mime_types', label: 'Format', type: 'multiselect', required: true, options: [{ value: 'image/jpeg', label: 'JPG' }, { value: 'image/png', label: 'PNG' }, { value: 'application/pdf', label: 'PDF' }] },
        { name: 'max_size_kb', label: 'Ukuran maks (KB)', type: 'number', required: true }, { name: 'allows_multiple', label: 'Boleh beberapa berkas', type: 'checkbox' },
        { name: 'requires_original_check', label: 'Perlu cek dokumen asli saat daftar ulang', type: 'checkbox' }, { name: 'is_sensitive', label: 'Dokumen sensitif (akses tercatat)', type: 'checkbox' }, { name: 'retention_class', label: 'Kelas retensi' },
      ],
      columns: [{ header: 'Kode', cell: (r) => String(r.code) }, { header: 'Nama', cell: (r) => String(r.name) }, { header: 'Format', cell: (r) => ((r.allowed_mime_types as string[]) ?? []).map((m) => m.split('/')[1]).join(', ') }, { header: 'Maks', cell: (r) => `${r.max_size_kb} KB` }, { header: 'Sensitif', cell: (r) => (r.is_sensitive ? 'Ya' : 'Tidak') }],
    },
  }
  const current = config[tab]
  const rows = useLoad(() => pmbAdmin.get<Array<Record<string, unknown>>>(current.path), [tab, reload])
  const action = useAction()

  return (
    <AdminPage title="Master Data PMB" trail={[{ label: 'Konfigurasi' }]}>
      <Tabs<MasterTab> active={tab} onChange={setTab} tabs={[{ key: 'paths', label: 'Jalur masuk' }, { key: 'systems', label: 'Sistem kuliah' }, { key: 'documents', label: 'Jenis dokumen' }]} />
      <Feedback state={action} />
      <Card noPadding title={current.title} actions={<Can permission={P.CONFIG_MANAGE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Tambah</Button></Can>}>
        <DataTable rowKey={(r) => String(r.id)} data={rows.data ?? []} emptyMessage={rows.loading ? 'Memuat...' : 'Belum ada data.'} columns={[...current.columns, { header: '', cell: (r) => <Can permission={P.CONFIG_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => setEditing(r)}>Ubah</Button><Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus data ini? Bila sudah dipakai, nonaktifkan saja.') && action.run(() => pmbAdmin.del(`${current.path}/${r.id}`), 'Dihapus.').then(() => { setReload(reload + 1); invalidateLookups() })}><Trash2 className="size-4" /></Button></div></Can> }]} />
      </Card>
      <FormDialog open={editing !== null} title={current.title} fields={current.fields} initial={editing && editing !== 'new' ? editing : { is_active: true, max_size_kb: 2048, allowed_mime_types: ['application/pdf'] }} onClose={() => setEditing(null)} onSubmit={async (v) => {
        if (editing === 'new') await pmbAdmin.post(current.path, v)
        else if (editing) await pmbAdmin.patch(`${current.path}/${editing.id}`, v)
        setReload(reload + 1)
        invalidateLookups()
      }} />
    </AdminPage>
  )
}

