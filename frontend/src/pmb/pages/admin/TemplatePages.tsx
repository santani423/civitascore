import { useEffect, useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Plus, Trash2 } from 'lucide-react'
import { pmbAdmin } from '@/pmb/api/admin'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { AdminPage, Can, P, invalidateLookups } from '@/pmb/components/admin'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { DataTable } from '@/components/ui/DataTable'
import { PMB, to } from '@/pmb/routes'

interface Template { id: string; code: string; name: string; version: number; status: string; fields_count?: number; core_field_rules: Record<string, string> | null }
interface Field { id: string; section: string; key: string; label: string; help_text: string | null; placeholder: string | null; type: string; options: Array<{ value: string; label: string }> | null; is_required: boolean; validation: Record<string, unknown> | null; visibility_rule: Record<string, unknown> | null; is_sensitive: boolean; is_reportable: boolean; stage: string; sort_order: number }
interface TemplateDetail extends Template { fields: Field[]; core_fields: Record<string, Record<string, string>>; field_types: string[]; in_use: boolean }

/** ADM-16 Template formulir: daftar versi. */
export function FormTemplatesPage() {
  const navigate = useNavigate()
  const list = useLoad(() => pmbAdmin.get<Template[]>('form-templates'), [])
  const [creating, setCreating] = useState(false)

  return (
    <AdminPage title="Template Formulir" trail={[{ label: 'Konfigurasi' }]} actions={<Can permission={P.CONFIG_MANAGE}><Button leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Template baru</Button></Can>}>
      <Card noPadding>
        <DataTable rowKey={(r) => r.id} data={list.data ?? []} emptyMessage={list.loading ? 'Memuat...' : 'Belum ada template.'} columns={[
          { header: 'Template', cell: (r) => <Link className="font-medium text-primary" to={to(PMB.admin.form, { id: r.id })}>{r.name}<span className="block text-xs text-ink-tertiary">{r.code} v{r.version}</span></Link> },
          { header: 'Field tambahan', cell: (r) => r.fields_count ?? 0 },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
        ]} />
      </Card>
      <FormDialog open={creating} title="Template baru" fields={[{ name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true }]} onClose={() => setCreating(false)} onSubmit={async (v) => { const t = await pmbAdmin.post<Template>('form-templates', v); invalidateLookups(); navigate(to(PMB.admin.form, { id: t.id })) }} />
    </AdminPage>
  )
}

const FIELD_FORM = (types: string[]): FieldDef[] => [
  { name: 'stage', label: 'Tahap', type: 'select', required: true, options: [{ value: 'application', label: 'Pendaftaran' }, { value: 're_registration', label: 'Daftar ulang' }] },
  { name: 'section', label: 'Seksi', required: true, placeholder: 'supporting' },
  { name: 'key', label: 'Kunci (huruf kecil_garis_bawah)', required: true },
  { name: 'label', label: 'Label', required: true },
  { name: 'type', label: 'Tipe', type: 'select', required: true, options: types.map((t) => ({ value: t, label: t })) },
  { name: 'options', label: 'Opsi (JSON: [{"value":"a","label":"A"}])', type: 'json', visible: (v) => ['select', 'multiselect', 'radio'].includes(String(v.type)) },
  { name: 'help_text', label: 'Bantuan' },
  { name: 'placeholder', label: 'Placeholder' },
  { name: 'is_required', label: 'Wajib', type: 'checkbox' },
  { name: 'validation', label: 'Validasi (JSON, mis. {"max": 100})', type: 'json' },
  { name: 'visibility_rule', label: 'Aturan tampil (JSON, mis. {"field":"path_category","op":"eq","value":"kip_affirmation"})', type: 'json' },
  { name: 'is_sensitive', label: 'Data sensitif (dienkripsi, tersamar)', type: 'checkbox' },
  { name: 'is_reportable', label: 'Muncul di laporan', type: 'checkbox' },
  { name: 'sort_order', label: 'Urutan', type: 'number' },
]

/** ADM-16 Editor template: aturan field inti, field tambahan, pratinjau, publikasi/versi. */
export function FormTemplateDetailPage() {
  const { id = '' } = useParams()
  const navigate = useNavigate()
  const t = useLoad(() => pmbAdmin.get<TemplateDetail>(`form-templates/${id}`), [id])
  const action = useAction()
  const [editing, setEditing] = useState<Field | 'new' | null>(null)
  const [rules, setRules] = useState<Record<string, string>>({})
  const [preview, setPreview] = useState<unknown>(null)

  useEffect(() => setRules(t.data?.core_field_rules ?? {}), [t.data])

  if (!t.data) return <p className="text-sm text-ink-secondary">{t.error?.message ?? 'Memuat...'}</p>

  const d = t.data
  const draft = d.status === 'draft'

  return (
    <AdminPage title={d.name} description={`${d.code} versi ${d.version}`} trail={[{ label: 'Template formulir', path: PMB.admin.forms }, { label: `${d.code} v${d.version}` }]}
      actions={
        <div className="flex flex-wrap gap-2">
          <StatusBadge status={d.status} />
          <Button size="sm" variant="outline" onClick={() => action.run(async () => setPreview(await pmbAdmin.get(`form-templates/${id}/preview`)))}>Pratinjau skema</Button>
          {draft && <Can permission={P.CONFIG_PUBLISH}><Button size="sm" onClick={() => window.confirm('Publikasikan? Versi terpublikasi tidak dapat diubah lagi.') && action.run(() => pmbAdmin.post(`form-templates/${id}/publish`), 'Template dipublikasikan.').then(() => { t.reload(); invalidateLookups() })}>Publikasikan</Button></Can>}
          {!draft && <Can permission={P.CONFIG_MANAGE}><Button size="sm" variant="outline" onClick={() => action.run(() => pmbAdmin.post<Template>(`form-templates/${id}/new-version`)).then((nv) => nv && navigate(to(PMB.admin.form, { id: nv.id })))}>Buat versi baru</Button></Can>}
          {d.status === 'published' && <Can permission={P.CONFIG_MANAGE}><Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post(`form-templates/${id}/retire`), 'Template dipensiunkan.').then(t.reload)}>Pensiunkan</Button></Can>}
        </div>
      }>
      <Feedback state={action} />
      {!draft && <Alert variant="info">Versi {d.status === 'published' ? 'terpublikasi' : 'pensiun'} bersifat tetap. Buat versi baru untuk mengubah.</Alert>}
      <Card title="Aturan field inti" description="Default dari sistem; ubah menjadi wajib/opsional/disembunyikan sesuai kebijakan jalur.">
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          {Object.entries(d.core_fields).flatMap(([section, fields]) => Object.entries(fields).map(([key, def]) => (
            <Select key={key} label={`${section} · ${key}`} disabled={!draft} options={[{ value: 'required', label: 'Wajib' }, { value: 'optional', label: 'Opsional' }, { value: 'hidden', label: 'Disembunyikan' }]} placeholder={`Default (${def})`} value={rules[key] ?? ''} onChange={(e) => setRules({ ...rules, [key]: e.target.value })} />
          )))}
        </div>
        {draft && <Button className="mt-4" onClick={() => action.run(() => pmbAdmin.patch(`form-templates/${id}`, { core_field_rules: Object.fromEntries(Object.entries(rules).filter(([, v]) => v)) }), 'Aturan disimpan.').then(t.reload)}>Simpan aturan</Button>}
      </Card>
      <Card title="Field tambahan" noPadding actions={draft && <Can permission={P.CONFIG_MANAGE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Field</Button></Can>}>
        <DataTable rowKey={(f) => f.id} data={d.fields} emptyMessage="Belum ada field tambahan." columns={[
          { header: 'Tahap / seksi', cell: (f) => `${f.stage} · ${f.section}` },
          { header: 'Field', cell: (f) => <span>{f.label}<span className="block text-xs text-ink-tertiary">{f.key}</span></span> },
          { header: 'Tipe', cell: (f) => f.type },
          { header: 'Atribut', cell: (f) => <div className="flex gap-1">{f.is_required && <Badge variant="warning">wajib</Badge>}{f.is_sensitive && <Badge variant="danger">sensitif</Badge>}{f.visibility_rule && <Badge variant="info">bersyarat</Badge>}</div> },
          { header: '', cell: (f) => draft && <Can permission={P.CONFIG_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => setEditing(f)}>Ubah</Button><Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => action.run(() => pmbAdmin.del(`form-templates/${id}/fields/${f.id}`), 'Field dihapus.').then(t.reload)}><Trash2 className="size-4" /></Button></div></Can> },
        ]} />
      </Card>
      {preview !== null && <Card title="Pratinjau skema (seperti yang diterima portal)"><Textarea readOnly rows={14} className="font-mono text-xs" value={JSON.stringify(preview, null, 2)} /></Card>}
      <FormDialog open={editing !== null} wide title="Field formulir" fields={FIELD_FORM(d.field_types)} initial={editing && editing !== 'new' ? (editing as unknown as Record<string, unknown>) : { stage: 'application', section: 'supporting', type: 'text' }} onClose={() => setEditing(null)} onSubmit={async (v) => {
        if (editing === 'new') await pmbAdmin.post(`form-templates/${id}/fields`, v)
        else if (editing) await pmbAdmin.patch(`form-templates/${id}/fields/${editing.id}`, v)
        t.reload()
      }} />
    </AdminPage>
  )
}

interface Scheme { id: string; code: string; name: string; version: number; status: string; passing_score: string | null; allocation_method: string; tie_break_rules: Record<string, unknown> | null; rounding_scale: number; components_count?: number }
interface Component { id: string; code: string; name: string; source: string; score_type: string; raw_min: string | null; raw_max: string | null; weight: string; minimum_normalized: string | null; is_mandatory: boolean; tie_break_priority: number | null; normalization: string; rubric: Record<string, unknown> | null; config: Record<string, unknown> | null; sort_order: number }
interface SchemeDetail extends Scheme { components: Component[]; lock_errors: string[]; in_use: boolean; enums: Record<string, string[]> }

/** ADM-17 Skema seleksi: daftar versi. */
export function SchemesPage() {
  const navigate = useNavigate()
  const list = useLoad(() => pmbAdmin.get<Scheme[]>('selection-schemes'), [])
  const [creating, setCreating] = useState(false)

  return (
    <AdminPage title="Skema Seleksi" trail={[{ label: 'Konfigurasi' }]} actions={<Can permission={P.SELECTION_MANAGE}><Button leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Skema baru</Button></Can>}>
      <Card noPadding>
        <DataTable rowKey={(r) => r.id} data={list.data ?? []} emptyMessage={list.loading ? 'Memuat...' : 'Belum ada skema.'} columns={[
          { header: 'Skema', cell: (r) => <Link className="font-medium text-primary" to={to(PMB.admin.scheme, { id: r.id })}>{r.name}<span className="block text-xs text-ink-tertiary">{r.code} v{r.version}</span></Link> },
          { header: 'Ambang', cell: (r) => r.passing_score ?? '—' },
          { header: 'Alokasi', cell: (r) => (r.allocation_method === 'score_priority' ? 'Skor lalu prioritas' : 'Pilihan pertama saja') },
          { header: 'Komponen', cell: (r) => r.components_count ?? 0 },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
        ]} />
      </Card>
      <FormDialog open={creating} title="Skema baru" fields={[{ name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true }, { name: 'passing_score', label: 'Ambang skor akhir (0–100)', type: 'number' }]} onClose={() => setCreating(false)} onSubmit={async (v) => { const s = await pmbAdmin.post<Scheme>('selection-schemes', v); invalidateLookups(); navigate(to(PMB.admin.scheme, { id: s.id })) }} />
    </AdminPage>
  )
}

const SIM_EXAMPLE = JSON.stringify({
  quotas: { TI: { capacity: 1, waitlist_capacity: 1 }, SI: { capacity: 2, waitlist_capacity: 0 } },
  applicants: [
    { id: 'A', scores: { RAPOR: 85 }, choices: [{ quota: 'TI' }, { quota: 'SI' }] },
    { id: 'B', scores: { RAPOR: 80 }, choices: [{ quota: 'TI' }] },
  ],
}, null, 2)

/** ADM-17 Editor skema: komponen & bobot, kunci, versi, simulasi. */
export function SchemeDetailPage() {
  const { id = '' } = useParams()
  const navigate = useNavigate()
  const s = useLoad(() => pmbAdmin.get<SchemeDetail>(`selection-schemes/${id}`), [id])
  const action = useAction()
  const [editing, setEditing] = useState<Component | 'new' | null>(null)
  const [settings, setSettings] = useState(false)
  const [sim, setSim] = useState(SIM_EXAMPLE)
  const [simResult, setSimResult] = useState<unknown>(null)

  if (!s.data) return <p className="text-sm text-ink-secondary">{s.error?.message ?? 'Memuat...'}</p>

  const d = s.data
  const draft = d.status === 'draft'
  const totalWeight = d.components.reduce((sum, c) => sum + Number(c.weight), 0)

  const runSimulation = () => action.run(async () => {
    const input = JSON.parse(sim) as { quotas: Record<string, unknown>; applicants: unknown[] }
    setSimResult(await pmbAdmin.post(`selection-schemes/${id}/simulate`, input))
  })

  return (
    <AdminPage title={d.name} description={`${d.code} versi ${d.version}`} trail={[{ label: 'Skema seleksi', path: PMB.admin.schemes }, { label: `${d.code} v${d.version}` }]}
      actions={
        <div className="flex flex-wrap gap-2">
          <StatusBadge status={d.status} />
          {draft && <Can permission={P.SELECTION_MANAGE}><Button size="sm" variant="outline" onClick={() => setSettings(true)}>Pengaturan</Button></Can>}
          {draft && <Can permission={P.SELECTION_LOCK}><Button size="sm" disabled={d.lock_errors.length > 0} onClick={() => window.confirm('Kunci skema? Skema terkunci dipakai untuk perhitungan dan tidak dapat diubah.') && action.run(() => pmbAdmin.post(`selection-schemes/${id}/lock`), 'Skema dikunci.').then(() => { s.reload(); invalidateLookups() })}>Kunci skema</Button></Can>}
          {!draft && <Can permission={P.SELECTION_MANAGE}><Button size="sm" variant="outline" onClick={() => action.run(() => pmbAdmin.post<Scheme>(`selection-schemes/${id}/new-version`)).then((nv) => nv && navigate(to(PMB.admin.scheme, { id: nv.id })))}>Buat versi baru</Button></Can>}
        </div>
      }>
      <Feedback state={action} />
      {draft && d.lock_errors.length > 0 && <Alert variant="warning" title="Belum dapat dikunci"><ul className="list-disc pl-5">{d.lock_errors.map((e) => <li key={e}>{e}</li>)}</ul></Alert>}
      <Card title="Komponen" description={`Total bobot ${totalWeight} — skor akhir = Σ(bobot × nilai ternormalisasi) / Σ bobot. Ambang: ${d.passing_score ?? '—'}.`} noPadding actions={draft && <Can permission={P.SELECTION_MANAGE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Komponen</Button></Can>}>
        <DataTable rowKey={(c) => c.id} data={d.components} emptyMessage="Belum ada komponen." columns={[
          { header: 'Komponen', cell: (c) => <span>{c.name}<span className="block text-xs text-ink-tertiary">{c.code} · {c.source}</span></span> },
          { header: 'Tipe', cell: (c) => (c.score_type === 'pass_fail' ? 'Lulus/tidak' : `${c.raw_min}–${c.raw_max}`) },
          { header: 'Bobot', cell: (c) => `${Number(c.weight)} (${totalWeight ? Math.round((Number(c.weight) / totalWeight) * 1000) / 10 : 0}%)` },
          { header: 'Minimum', cell: (c) => c.minimum_normalized ?? '—' },
          { header: 'Tie-break', cell: (c) => c.tie_break_priority ?? '—' },
          { header: 'Wajib', cell: (c) => (c.is_mandatory ? 'Ya' : 'Tidak') },
          { header: '', cell: (c) => draft && <Can permission={P.SELECTION_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => setEditing(c)}>Ubah</Button><Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => action.run(() => pmbAdmin.del(`selection-schemes/${id}/components/${c.id}`), 'Dihapus.').then(s.reload)}><Trash2 className="size-4" /></Button></div></Can> },
        ]} />
      </Card>
      <Card title="Simulasi" description="Uji skema dengan data contoh (tidak disimpan). Nilai per kode komponen; kuota per kode bebas.">
        <Textarea rows={10} className="font-mono text-xs" value={sim} onChange={(e) => setSim(e.target.value)} />
        <Can permission={P.SELECTION_MANAGE}><Button className="mt-3" variant="outline" onClick={runSimulation}>Jalankan simulasi</Button></Can>
        {simResult !== null && <Textarea readOnly rows={14} className="mt-3 font-mono text-xs" value={JSON.stringify(simResult, null, 2)} />}
      </Card>
      <FormDialog open={editing !== null} wide title="Komponen seleksi" fields={[
        { name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true },
        { name: 'source', label: 'Sumber nilai', type: 'select', required: true, options: (d.enums.sources ?? []).map((v) => ({ value: v, label: v })) },
        { name: 'score_type', label: 'Tipe nilai', type: 'select', required: true, options: [{ value: 'numeric', label: 'Angka' }, { value: 'pass_fail', label: 'Lulus/tidak' }] },
        { name: 'raw_min', label: 'Nilai mentah minimum', type: 'number', visible: (v) => v.score_type !== 'pass_fail' },
        { name: 'raw_max', label: 'Nilai mentah maksimum', type: 'number', visible: (v) => v.score_type !== 'pass_fail' },
        { name: 'weight', label: 'Bobot', type: 'number', required: true },
        { name: 'minimum_normalized', label: 'Nilai minimum ternormalisasi (gugur bila di bawah)', type: 'number' },
        { name: 'is_mandatory', label: 'Wajib dinilai', type: 'checkbox' },
        { name: 'tie_break_priority', label: 'Prioritas tie-break (1 = pertama)', type: 'number' },
        { name: 'normalization', label: 'Normalisasi', type: 'select', options: [{ value: 'minmax', label: 'Min-max (0–100)' }, { value: 'zscore_session', label: 'Z-score antarsesi' }] },
        { name: 'rubric', label: 'Rubrik wawancara/portofolio (JSON {"criteria":[{"key":"komunikasi","label":"Komunikasi","weight":50,"max":100}]})', type: 'json' },
        { name: 'config', label: 'Konfigurasi (JSON, mis. {"semesters":[1,2,3,4,5]})', type: 'json' },
        { name: 'sort_order', label: 'Urutan', type: 'number' },
      ]} initial={editing && editing !== 'new' ? (editing as unknown as Record<string, unknown>) : { score_type: 'numeric', raw_min: 0, raw_max: 100, weight: 1, normalization: 'minmax', is_mandatory: true }} onClose={() => setEditing(null)} onSubmit={async (v) => {
        if (editing === 'new') await pmbAdmin.post(`selection-schemes/${id}/components`, v)
        else if (editing) await pmbAdmin.patch(`selection-schemes/${id}/components/${editing.id}`, v)
        s.reload()
      }} />
      <FormDialog open={settings} title="Pengaturan skema" fields={[
        { name: 'name', label: 'Nama', required: true },
        { name: 'passing_score', label: 'Ambang skor akhir (0–100)', type: 'number' },
        { name: 'allocation_method', label: 'Metode alokasi', type: 'select', options: [{ value: 'score_priority', label: 'Skor lalu prioritas pilihan' }, { value: 'first_choice_only', label: 'Pilihan pertama saja' }] },
        { name: 'rounding_scale', label: 'Desimal pembulatan', type: 'number' },
        { name: 'tie_break_rules', label: 'Aturan tie-break (JSON, mis. {"use_verified_at":true})', type: 'json' },
      ]} initial={d as unknown as Record<string, unknown>} onClose={() => setSettings(false)} onSubmit={async (v) => { await pmbAdmin.patch(`selection-schemes/${id}`, v); s.reload() }} />
    </AdminPage>
  )
}
