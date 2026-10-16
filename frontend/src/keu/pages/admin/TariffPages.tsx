import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Calculator, Copy, Plus } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { DataTable } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Modal } from '@/components/ui/Modal'
import { Select } from '@/components/ui/Select'
import { Checkbox } from '@/components/ui/Checkbox'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, describeError, useAction } from '@/pmb/components/useAction'
import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { Tabs } from '@/pmb/components/Tabs'
import { dateTime, money } from '@/pmb/lib/format'
import { usePermission } from '@/hooks/usePermission'
import { keuAdmin } from '@/keu/api'
import { Can, Figure, KeuBadge, KeuPage, StudentPicker } from '@/keu/components'
import { P } from '@/keu/permissions'
import { invalidateKeuLookups, opts, useKeuLookups } from '@/keu/lookups'
import { KEU, to } from '@/keu/routes'
import type { StudentRef } from '@/keu/types'

interface Component { id: string; code: string; name: string; category: string; category_label: string; billing_frequency: string; is_mandatory: boolean; installable: boolean; discountable: boolean; refundable: boolean; counts_for_clearance: boolean; pddikti_category: string | null; gl_account_code: string | null; description: string | null; is_active: boolean }
interface RateTable { id: string; code: string; name: string; academic_year: string; version: number; status: string; status_label: string; rates_count: number; effective_term: string | null; notes: string | null; rejection_reason: string | null; published_at: string | null; created_by: string | null; submitted_by: string | null }
interface Rate { id: string; fee_component_id: string; component: string | null; study_program_id: string | null; study_program: string | null; ukt_group_id: string | null; ukt_group: string | null; admission_year_from: number | null; admission_year_to: number | null; admission_path_code: string | null; study_system_code: string | null; semester_level_from: number | null; semester_level_to: number | null; calc_basis: string; calc_basis_label: string; amount: string; min_credits: number | null; max_credits: number | null; specificity_score: number }

const FREQUENCIES = [{ value: 'once', label: 'Sekali' }, { value: 'per_term', label: 'Per semester' }, { value: 'per_year', label: 'Per tahun' }, { value: 'on_demand', label: 'Sesuai kebutuhan' }]

type Tab = 'tables' | 'components'

/** Komponen biaya & tabel tarif berversi (F-01/F-02). */
export function TariffsPage() {
  const [tab, setTab] = useState<Tab>('tables')
  const canComponents = usePermission(P.COMPONENTS_READ)
  const canRates = usePermission(P.RATES_READ)

  return (
    <KeuPage title="Tarif & Komponen Biaya" description="Tarif berlaku per tahun akademik dan berversi. Tabel yang sudah disetujui tidak dapat diubah; perubahan dibuat sebagai versi baru sehingga tagihan lama tetap memakai tarif lamanya." trail={[{ label: 'Tarif' }]}>
      <Tabs<Tab> active={tab} onChange={setTab} tabs={[...(canRates ? [{ key: 'tables' as Tab, label: 'Tabel tarif' }] : []), ...(canComponents ? [{ key: 'components' as Tab, label: 'Komponen biaya' }] : [])]} />
      {tab === 'tables' && canRates && <RateTables />}
      {(tab === 'components' || !canRates) && canComponents && <Components />}
    </KeuPage>
  )
}

function Components() {
  const lookups = useKeuLookups()
  const { data, error, reload } = useLoad(() => keuAdmin.get<Component[]>('fee-components'))
  const action = useAction()
  const [editing, setEditing] = useState<Partial<Component> | null>(null)
  const fields: FieldDef[] = [
    { name: 'code', label: 'Kode', required: true, hint: 'Huruf/angka/garis, mis. BPP' },
    { name: 'name', label: 'Nama', required: true },
    { name: 'category', label: 'Kategori', type: 'select', required: true, options: lookups?.enums.fee_category ?? [] },
    { name: 'billing_frequency', label: 'Frekuensi', type: 'select', required: true, options: FREQUENCIES },
    { name: 'pddikti_category', label: 'Kategori pelaporan PDDikti', type: 'select', options: [{ value: 'entry_fee', label: 'Biaya masuk' }, { value: 'term_fee', label: 'Biaya per semester' }, { value: 'none', label: 'Tidak dilaporkan' }] },
    { name: 'gl_account_code', label: 'Kode akun (opsional)' },
    { name: 'description', label: 'Keterangan', type: 'textarea' },
    { name: 'is_mandatory', label: 'Wajib', type: 'checkbox' },
    { name: 'installable', label: 'Boleh dicicil', type: 'checkbox' },
    { name: 'discountable', label: 'Boleh mendapat potongan/beasiswa', type: 'checkbox' },
    { name: 'refundable', label: 'Dapat dikembalikan', type: 'checkbox' },
    { name: 'counts_for_clearance', label: 'Dihitung untuk cekal', type: 'checkbox' },
    { name: 'is_active', label: 'Aktif', type: 'checkbox' },
  ]

  return (
    <Card noPadding title="Komponen biaya" actions={<Can permission={P.COMPONENTS_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing({ is_active: true, is_mandatory: true, installable: true, discountable: true, counts_for_clearance: true, billing_frequency: 'per_term' })}>Komponen</Button></Can>}>
      <div className="p-3"><Feedback state={action} />{error && <Alert variant="danger">{describeError(error)}</Alert>}</div>
      <DataTable rowKey={(r) => r.id} data={data ?? []} emptyMessage="Belum ada komponen. Jalankan `php artisan keu:install` untuk komponen bawaan atau tambah manual." columns={[
        { header: 'Kode', cell: (r) => <span className="font-mono">{r.code}</span> },
        { header: 'Nama', cell: (r) => <span>{r.name}<span className="block text-xs text-ink-tertiary">{r.category_label} · {FREQUENCIES.find((f) => f.value === r.billing_frequency)?.label}</span></span> },
        { header: 'Sifat', cell: (r) => <span className="flex flex-wrap gap-1">{r.is_mandatory && <Badge>Wajib</Badge>}{r.installable && <Badge variant="info">Cicil</Badge>}{r.discountable && <Badge variant="info">Potongan</Badge>}{r.counts_for_clearance && <Badge variant="warning">Cekal</Badge>}</span> },
        { header: 'Status', cell: (r) => <KeuBadge status={r.is_active ? 'active' : 'inactive'} label={r.is_active ? 'Aktif' : 'Nonaktif'} /> },
        { header: 'Aksi', cell: (r) => <div className="flex gap-1">
          <Can permission={P.COMPONENTS_UPDATE}><Button size="sm" variant="outline" onClick={() => setEditing(r)}>Ubah</Button></Can>
          <Can permission={P.COMPONENTS_DELETE}><Button size="sm" variant="ghost" onClick={() => action.run(() => keuAdmin.send('delete', `fee-components/${r.id}`), (x) => x.message).then((x) => { if (x) { invalidateKeuLookups(); reload() } })}>Hapus</Button></Can>
        </div> },
      ]} />
      <FormDialog open={editing !== null} title={editing?.id ? 'Ubah komponen' : 'Komponen baru'} initial={editing ?? {}} fields={fields} onClose={() => setEditing(null)}
        onSubmit={async (v) => {
          await (editing?.id ? keuAdmin.put(`fee-components/${editing.id}`, v) : keuAdmin.post('fee-components', v))
          invalidateKeuLookups()
          reload()
        }} />
    </Card>
  )
}

function RateTables() {
  const navigate = useNavigate()
  const lookups = useKeuLookups()
  const { data, error } = useLoad(() => keuAdmin.get<RateTable[]>('fee-rate-tables'))
  const [creating, setCreating] = useState<{ copy_from_id?: string } | null>(null)

  return (
    <Card noPadding title="Tabel tarif" actions={<Can permission={P.RATES_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setCreating({})}>Tabel baru</Button></Can>}>
      {error && <Alert variant="danger" className="m-3">{describeError(error)}</Alert>}
      <DataTable rowKey={(r) => r.id} data={data ?? []} emptyMessage="Belum ada tabel tarif." columns={[
        { header: 'Tabel', cell: (r) => <Link className="text-primary" to={to(KEU.admin.rateTable, { id: r.id })}>{r.name}<span className="block text-xs text-ink-tertiary">{r.code} · versi {r.version}</span></Link> },
        { header: 'Tahun akademik', cell: (r) => r.academic_year },
        { header: 'Baris tarif', cell: (r) => r.rates_count },
        { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status_label} /> },
        { header: 'Berlaku sejak', cell: (r) => dateTime(r.published_at) },
        { header: 'Aksi', cell: (r) => <Can permission={P.RATES_CREATE}><Button size="sm" variant="ghost" leftIcon={<Copy className="size-4" />} onClick={() => setCreating({ copy_from_id: r.id })}>Salin jadi versi baru</Button></Can> },
      ]} />
      <FormDialog open={creating !== null} title={creating?.copy_from_id ? 'Salin tabel tarif' : 'Tabel tarif baru'} initial={creating ?? {}} onClose={() => setCreating(null)}
        description={creating?.copy_from_id ? 'Semua baris tarif disalin ke draf baru. Isi kenaikan (%) untuk menyesuaikan nominal sekaligus (dibulatkan ke ratusan).' : 'Tabel baru berstatus draf. Kode yang sama dengan tabel lama menjadi versi berikutnya.'}
        fields={[
          { name: 'code', label: 'Kode', required: true },
          { name: 'name', label: 'Nama', required: true },
          { name: 'academic_year', label: 'Tahun akademik', required: true, placeholder: '2026/2027' },
          { name: 'effective_term_id', label: 'Mulai berlaku semester', type: 'select', options: opts(lookups?.academic_terms, (t) => t.label) },
          { name: 'increase_percent', label: 'Kenaikan (%)', type: 'number', visible: () => Boolean(creating?.copy_from_id) },
          { name: 'notes', label: 'Catatan / dasar SK', type: 'textarea' },
        ]}
        onSubmit={async (v) => {
          const { data: table } = await keuAdmin.post<RateTable>('fee-rate-tables', { ...v, copy_from_id: creating?.copy_from_id ?? null })
          navigate(to(KEU.admin.rateTable, { id: table.id }))
        }} />
    </Card>
  )
}

export function RateTableDetailPage() {
  const { id = '' } = useParams()
  const lookups = useKeuLookups()
  const { data, error, reload } = useLoad(() => keuAdmin.get<RateTable & { rates: Rate[]; conflicts: Array<{ component: string; rate_ids: string[] }> }>(`fee-rate-tables/${id}`), [id])
  const action = useAction()
  const [editing, setEditing] = useState<Partial<Rate> | null>(null)
  const [rejecting, setRejecting] = useState(false)
  const [previewing, setPreviewing] = useState(false)
  const run = (fn: () => Promise<{ message: string }>) => action.run(fn, (r) => r.message).then((r) => { if (r) reload() })

  if (error) return <KeuPage title="Tabel tarif" trail={[{ label: 'Tarif', path: KEU.admin.tariffs }]}><Alert variant="danger">{describeError(error)}</Alert></KeuPage>
  if (!data) return <p className="text-sm text-ink-secondary">Memuat...</p>
  const draft = data.status === 'draft'
  const conflictIds = new Set(data.conflicts.flatMap((c) => c.rate_ids))
  const rateFields: FieldDef[] = [
    { name: 'fee_component_id', label: 'Komponen', type: 'select', required: true, options: opts(lookups?.fee_components, (c) => `${c.code} — ${c.name}`) },
    { name: 'calc_basis', label: 'Dasar hitung', type: 'select', required: true, options: lookups?.enums.calc_basis ?? [] },
    { name: 'amount', label: 'Nominal (Rp) — per SKS bila dasar hitung per SKS', type: 'number', required: true },
    { name: 'study_program_id', label: 'Program studi (kosong = semua)', type: 'select', options: opts(lookups?.study_programs, (p) => p.name) },
    { name: 'ukt_group_id', label: 'Golongan UKT (kosong = semua)', type: 'select', options: opts(lookups?.ukt_groups, (g) => `${g.code} — ${g.name}`) },
    { name: 'admission_year_from', label: 'Angkatan dari', type: 'number' },
    { name: 'admission_year_to', label: 'Angkatan sampai', type: 'number' },
    { name: 'semester_level_from', label: 'Semester ke- dari', type: 'number' },
    { name: 'semester_level_to', label: 'Semester ke- sampai', type: 'number' },
    { name: 'admission_path_code', label: 'Kode jalur masuk (opsional)' },
    { name: 'study_system_code', label: 'Kode kelas/sistem kuliah (opsional)' },
    { name: 'min_credits', label: 'SKS minimum ditagih', type: 'number', visible: (v) => v.calc_basis === 'per_credit' },
    { name: 'max_credits', label: 'SKS maksimum ditagih', type: 'number', visible: (v) => v.calc_basis === 'per_credit' },
  ]

  return (
    <KeuPage title={data.name} description={`${data.code} · versi ${data.version} · TA ${data.academic_year}`} trail={[{ label: 'Tarif', path: KEU.admin.tariffs }, { label: `${data.code} v${data.version}` }]}
      actions={<div className="flex flex-wrap gap-2">
        <Button variant="outline" leftIcon={<Calculator className="size-4" />} onClick={() => setPreviewing(true)}>Pratinjau tagihan</Button>
        {draft && <Can permission={P.RATES_UPDATE}><Button onClick={() => run(() => keuAdmin.post(`fee-rate-tables/${id}/submit`))}>Ajukan persetujuan</Button></Can>}
        {data.status === 'pending_approval' && <Can permission={P.RATES_APPROVE}><Button onClick={() => run(() => keuAdmin.post(`fee-rate-tables/${id}/approve`))}>Setujui & berlakukan</Button><Button variant="outline" onClick={() => setRejecting(true)}>Kembalikan</Button></Can>}
      </div>}>
      <Feedback state={action} />
      <Card>
        <div className="grid gap-4 sm:grid-cols-4">
          <Figure label="Status" value={<KeuBadge status={data.status} label={data.status_label} />} />
          <Figure label="Baris tarif" value={data.rates.length} />
          <Figure label="Konflik" value={data.conflicts.length} tone={data.conflicts.length ? 'danger' : undefined} />
          <Figure label="Berlaku sejak" value={dateTime(data.published_at)} />
        </div>
        {data.rejection_reason && <Alert variant="warning" className="mt-3">Dikembalikan: {data.rejection_reason}</Alert>}
        {data.conflicts.length > 0 && <Alert variant="danger" className="mt-3">Ada {data.conflicts.length} pasangan baris yang bertabrakan (dimensi sama, rentang angkatan/semester beririsan): {data.conflicts.map((c) => c.component).join(', ')}. Tabel tidak dapat diajukan sebelum diperbaiki.</Alert>}
        {!draft && <p className="mt-3 text-xs text-ink-tertiary">Tabel yang sudah diajukan/berlaku tidak dapat diubah. Gunakan “Salin jadi versi baru” di daftar tabel tarif.</p>}
      </Card>
      <Card noPadding title="Baris tarif" actions={draft ? <Can permission={P.RATES_UPDATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing({ calc_basis: 'flat' })}>Baris tarif</Button></Can> : undefined}>
        <DataTable rowKey={(r) => r.id} data={data.rates} emptyMessage="Belum ada baris tarif." columns={[
          { header: 'Komponen', cell: (r) => <span>{r.component}{conflictIds.has(r.id) && <Badge variant="danger" className="ml-1">Konflik</Badge>}</span> },
          { header: 'Berlaku untuk', cell: (r) => <span className="whitespace-normal text-xs">{[
            r.study_program ?? 'Semua prodi', r.ukt_group && `UKT ${r.ukt_group}`,
            (r.admission_year_from || r.admission_year_to) && `Angkatan ${r.admission_year_from ?? '…'}–${r.admission_year_to ?? '…'}`,
            (r.semester_level_from || r.semester_level_to) && `Semester ${r.semester_level_from ?? '…'}–${r.semester_level_to ?? '…'}`,
            r.admission_path_code && `Jalur ${r.admission_path_code}`, r.study_system_code && `Kelas ${r.study_system_code}`,
          ].filter(Boolean).join(' · ')}</span> },
          { header: 'Dasar', cell: (r) => r.calc_basis_label },
          { header: 'Nominal', cell: (r) => money(r.amount) },
          { header: 'Spesifisitas', cell: (r) => r.specificity_score },
          { header: 'Aksi', cell: (r) => draft ? <Can permission={P.RATES_UPDATE}><div className="flex gap-1">
            <Button size="sm" variant="outline" onClick={() => setEditing(r)}>Ubah</Button>
            <Button size="sm" variant="ghost" onClick={() => run(() => keuAdmin.send('delete', `fee-rates/${r.id}`))}>Hapus</Button>
          </div></Can> : null },
        ]} />
      </Card>
      <FormDialog open={editing !== null} title={editing?.id ? 'Ubah baris tarif' : 'Baris tarif baru'} initial={editing ?? {}} fields={rateFields} wide onClose={() => setEditing(null)}
        description="Bila beberapa baris cocok untuk seorang mahasiswa, baris paling spesifik yang dipakai (prodi > golongan UKT > jalur > kelas > semester > angkatan)."
        onSubmit={async (v) => {
          await (editing?.id ? keuAdmin.put(`fee-rates/${editing.id}`, v) : keuAdmin.post(`fee-rate-tables/${id}/rates`, v))
          reload()
        }} />
      <ReasonDialog open={rejecting} title="Kembalikan ke draf" onClose={() => setRejecting(false)} onConfirm={(v) => run(() => keuAdmin.post(`fee-rate-tables/${id}/reject`, v))} />
      {previewing && <TariffPreview onClose={() => setPreviewing(false)} />}
    </KeuPage>
  )
}

interface Preview { lines: Array<{ type: string; type_label: string; description: string; amount: string }>; gross: string; discount: string; net: string; skip: string | null; skip_detail: string | null; semester_level: number }

/** Pratinjau perhitungan tagihan satu mahasiswa dengan tabel tarif yang BERLAKU (bukan draf), tanpa membuat tagihan. */
function TariffPreview({ onClose }: { onClose: () => void }) {
  const lookups = useKeuLookups()
  const [student, setStudent] = useState<StudentRef | null>(null)
  const [termId, setTermId] = useState('')
  const [componentIds, setComponentIds] = useState<string[]>([])
  const [result, setResult] = useState<Preview | null>(null)
  const action = useAction()

  return (
    <Modal open onClose={onClose} title="Pratinjau tagihan mahasiswa" className="max-w-3xl">
      <div className="flex flex-col gap-4">
        <p className="text-sm text-ink-secondary">Memakai tabel tarif yang berlaku, golongan UKT, serta beasiswa/potongan aktif mahasiswa.</p>
        <StudentPicker value={student} onChange={setStudent} />
        <Select label="Semester" placeholder="Pilih..." options={opts(lookups?.academic_terms, (t) => t.label)} value={termId} onChange={(e) => setTermId(e.target.value)} />
        <fieldset className="grid grid-cols-1 gap-1 sm:grid-cols-2">
          <legend className="mb-1 text-sm font-medium text-ink-primary">Komponen</legend>
          {(lookups?.fee_components ?? []).filter((c) => c.is_active).map((c) => (
            <Checkbox key={c.id} label={`${c.code} — ${c.name}`} checked={componentIds.includes(c.id)}
              onChange={(e) => setComponentIds(e.target.checked ? [...componentIds, c.id] : componentIds.filter((x) => x !== c.id))} />
          ))}
        </fieldset>
        <Feedback state={action} />
        <div className="flex justify-end gap-2">
          <Button variant="outline" onClick={onClose}>Tutup</Button>
          <Button isLoading={action.busy} disabled={!student || !termId || componentIds.length === 0}
            onClick={() => action.run(() => keuAdmin.post<Preview>('tariff-preview', { student_id: student?.id, academic_term_id: termId, fee_component_ids: componentIds })).then((r) => r && setResult(r.data))}>Hitung</Button>
        </div>
        {result && (result.skip ? <Alert variant="warning">Tidak dapat dihitung: {result.skip_detail}</Alert> : (
          <div className="rounded-lg border border-border">
            <DataTable rowKey={(r) => `${r.type}-${r.description}`} data={result.lines} columns={[
              { header: 'Jenis', cell: (r) => r.type_label }, { header: 'Uraian', cell: (r) => r.description }, { header: 'Jumlah', cell: (r) => money(r.amount) },
            ]} />
            <p className="border-t border-border p-3 text-right text-sm">Semester ke-{result.semester_level} · bruto {money(result.gross)} · potongan {money(result.discount)} · <strong>neto {money(result.net)}</strong></p>
          </div>
        ))}
      </div>
    </Modal>
  )
}
