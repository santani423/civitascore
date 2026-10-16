import { useState } from 'react'
import { Link } from 'react-router-dom'
import { FileText, Plus, Play } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { DataTable } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, describeError, useAction } from '@/pmb/components/useAction'
import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { Tabs } from '@/pmb/components/Tabs'
import { date, dateTime, money } from '@/pmb/lib/format'
import { downloadBlob } from '@/services/studentPortalService'
import { usePermission } from '@/hooks/usePermission'
import { keuAdmin, uploadFile } from '@/keu/api'
import { Can, KeuBadge, KeuList, KeuPage, StudentPicker } from '@/keu/components'
import { P } from '@/keu/permissions'
import { invalidateKeuLookups, opts, useKeuLookups } from '@/keu/lookups'
import { KEU, to } from '@/keu/routes'
import type { Award, Invoice, StudentRef } from '@/keu/types'

const BILL_GROUP_FIELD = (options: Array<{ value: string; label: string }>): FieldDef => ({ name: 'bill_groups', label: 'Berlaku untuk kelompok tagihan', type: 'multiselect', options: options.filter((o) => !['penalty', 'admission'].includes(o.value)) })

// ---------------------------------------------------------------- Beasiswa, potongan, voucher

type AidTab = 'awards' | 'policies' | 'vouchers'

/** Beasiswa & potongan (F-05) dan voucher (F-06). */
export function AidPage() {
  const canAid = usePermission(P.AID_READ)
  const canVoucher = usePermission(P.VOUCHER_READ)
  const [tab, setTab] = useState<AidTab>(canAid ? 'awards' : 'vouchers')

  return (
    <KeuPage title="Beasiswa, Potongan & Voucher" description="Penetapan beasiswa/potongan per mahasiswa (perlu persetujuan) dan voucher kode untuk tagihan yang belum dibayar." trail={[{ label: 'Potongan' }]}>
      <Tabs<AidTab> active={tab} onChange={setTab} tabs={[
        ...(canAid ? [{ key: 'awards' as AidTab, label: 'Penetapan mahasiswa' }, { key: 'policies' as AidTab, label: 'Jenis beasiswa/potongan' }] : []),
        ...(canVoucher ? [{ key: 'vouchers' as AidTab, label: 'Voucher' }] : []),
      ]} />
      {tab === 'awards' && canAid && <Awards />}
      {tab === 'policies' && canAid && <Policies />}
      {tab === 'vouchers' && canVoucher && <Vouchers />}
    </KeuPage>
  )
}

interface Policy { id: string; code: string; name: string; kind: string; kind_label: string; funder_name: string | null; scholarship: string | null; calc_type: string; calc_type_label: string; value: string; max_amount: string | null; fee_component_ids: string[] | null; priority: number; stackable: boolean; description: string | null; is_active: boolean; active_awards: number }

function Policies() {
  const lookups = useKeuLookups()
  const { data, error, reload } = useLoad(() => keuAdmin.get<Policy[]>('discount-policies'))
  const [editing, setEditing] = useState<Partial<Policy> | null>(null)

  return (
    <Card noPadding title="Jenis beasiswa & potongan" actions={<Can permission={P.AID_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing({ is_active: true, priority: 5, stackable: false, calc_type: 'percent' })}>Jenis</Button></Can>}>
      {error && <Alert variant="danger" className="m-3">{describeError(error)}</Alert>}
      <DataTable rowKey={(r) => r.id} data={data ?? []} emptyMessage="Belum ada jenis beasiswa/potongan." columns={[
        { header: 'Nama', cell: (r) => <span>{r.name}<span className="block text-xs text-ink-tertiary">{r.code} · {r.kind_label}{r.funder_name ? ` · ${r.funder_name}` : ''}</span></span> },
        { header: 'Besaran', cell: (r) => r.calc_type === 'percent' ? `${Number(r.value)}%` : r.calc_type === 'full' ? 'Penuh' : money(r.value) },
        { header: 'Maks.', cell: (r) => money(r.max_amount) },
        { header: 'Prioritas', cell: (r) => <span>{r.priority}{r.stackable && <Badge variant="info" className="ml-1">Dapat digabung</Badge>}</span> },
        { header: 'Penerima aktif', cell: (r) => r.active_awards },
        { header: 'Status', cell: (r) => <KeuBadge status={r.is_active ? 'active' : 'inactive'} label={r.is_active ? 'Aktif' : 'Nonaktif'} /> },
        { header: 'Aksi', cell: (r) => <Can permission={P.AID_UPDATE}><Button size="sm" variant="outline" onClick={() => setEditing(r)}>Ubah</Button></Can> },
      ]} />
      <FormDialog open={editing !== null} title={editing?.id ? 'Ubah jenis' : 'Jenis beasiswa/potongan baru'} initial={editing ?? {}} wide onClose={() => setEditing(null)}
        description="Perubahan hanya berlaku untuk penetapan & tagihan baru. Potongan yang tidak dapat digabung: hanya potongan terbesar yang dipakai."
        fields={[
          { name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true },
          { name: 'kind', label: 'Jenis', type: 'select', required: true, options: lookups?.enums.aid_kind ?? [] },
          { name: 'funder_name', label: 'Pemberi dana (opsional)' },
          { name: 'calc_type', label: 'Cara hitung', type: 'select', required: true, options: lookups?.enums.aid_calc_type ?? [] },
          { name: 'value', label: 'Nilai (% atau Rp)', type: 'number', visible: (v) => v.calc_type !== 'full' },
          { name: 'max_amount', label: 'Batas maksimal per komponen biaya (Rp)', type: 'number', hint: 'Nominal dan batas berlaku untuk setiap komponen yang dipotong.' },
          { name: 'fee_component_ids', label: 'Komponen yang dipotong (kosong = semua yang boleh dipotong)', type: 'multiselect', options: opts(lookups?.fee_components, (c) => c.code) },
          { name: 'priority', label: 'Prioritas (1 = pertama)', type: 'number', required: true },
          { name: 'stackable', label: 'Dapat digabung dengan potongan lain', type: 'checkbox' },
          { name: 'is_active', label: 'Aktif', type: 'checkbox' },
          { name: 'description', label: 'Keterangan', type: 'textarea' },
        ]}
        onSubmit={async (v) => {
          await (editing?.id ? keuAdmin.put(`discount-policies/${editing.id}`, v) : keuAdmin.post('discount-policies', v))
          invalidateKeuLookups()
          reload()
        }} />
    </Card>
  )
}

function Awards() {
  const lookups = useKeuLookups()
  const action = useAction()
  const [reload, setReload] = useState(0)
  const [creating, setCreating] = useState(false)
  const [student, setStudent] = useState<StudentRef | null>(null)
  const [deciding, setDeciding] = useState<{ award: Award; mode: 'reject' | 'revoke' } | null>(null)
  const [applying, setApplying] = useState<Award | null>(null)
  const canApprove = usePermission(P.AID_APPROVE)
  const done = (r: unknown) => r && setReload((x) => x + 1)
  const invoices = useLoad(() => (applying?.student ? keuAdmin.list<Invoice>('/invoices', { 'filter[student_id]': applying.student.id, per_page: 50 }) : Promise.resolve(null)), [applying?.id])

  return (
    <>
      <Feedback state={action} />
      <KeuList<Award> path="aid-awards" reloadKey={reload} rowKey={(r) => r.id} itemLabel="penetapan"
        toolbar={<Can permission={P.AID_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Penetapan</Button></Can>}
        filters={[
          { name: 'status', label: 'Status', type: 'select', options: [{ value: 'pending_approval', label: 'Menunggu persetujuan' }, { value: 'active', label: 'Aktif' }, { value: 'rejected', label: 'Ditolak' }, { value: 'revoked', label: 'Dicabut' }] },
          { name: 'discount_policy_id', label: 'Jenis', type: 'select', options: opts(lookups?.discount_policies, (p) => p.name) },
        ]}
        columns={[
          { header: 'Mahasiswa', cell: (r) => r.student ? <Link className="text-primary" to={to(KEU.admin.student, { id: r.student.id })}>{r.student.nim}<span className="block text-xs text-ink-tertiary">{r.student.name}</span></Link> : '—' },
          { header: 'Jenis', cell: (r) => <span>{r.policy?.name}<span className="block text-xs text-ink-tertiary">{r.policy?.kind_label}</span></span> },
          { header: 'Besaran', cell: (r) => r.calc_type === 'percent' ? `${Number(r.value)}%` : r.calc_type === 'full' ? 'Penuh' : money(r.value) },
          { header: 'Masa berlaku', cell: (r) => `${r.start_term ?? '—'} s.d. ${r.end_term ?? 'seterusnya'}` },
          { header: 'Dasar', cell: (r) => <span className="whitespace-normal text-xs">{r.basis}</span> },
          { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status_label} /> },
          { header: 'Aksi', cell: (r) => <div className="flex gap-1">
            {r.status === 'pending_approval' && canApprove && <><Button size="sm" onClick={() => action.run(() => keuAdmin.post(`aid-awards/${r.id}/approve`), (x) => x.message).then(done)}>Setujui</Button><Button size="sm" variant="outline" onClick={() => setDeciding({ award: r, mode: 'reject' })}>Tolak</Button></>}
            {r.status === 'active' && <Can permission={P.ADJ_CREATE}><Button size="sm" variant="outline" onClick={() => setApplying(r)}>Terapkan ke tagihan</Button></Can>}
            {r.status === 'active' && canApprove && <Button size="sm" variant="ghost" onClick={() => setDeciding({ award: r, mode: 'revoke' })}>Cabut</Button>}
          </div> },
        ]} />
      <FormDialog open={creating} title="Penetapan beasiswa/potongan" wide onClose={() => { setCreating(false); setStudent(null) }} prepend={<StudentPicker value={student} onChange={setStudent} />}
        description="Penetapan berlaku setelah disetujui petugas lain dan otomatis dipotongkan pada tagihan berikutnya. Untuk tagihan yang sudah terbit, gunakan “Terapkan ke tagihan”."
        fields={[
          { name: 'discount_policy_id', label: 'Jenis', type: 'select', required: true, options: opts(lookups?.discount_policies.filter((p) => p.is_active), (p) => p.name) },
          { name: 'calc_type', label: 'Cara hitung (kosong = ikut jenis)', type: 'select', options: lookups?.enums.aid_calc_type ?? [] },
          { name: 'value', label: 'Nilai (kosong = ikut jenis)', type: 'number' },
          { name: 'max_amount', label: 'Batas maksimal (Rp)', type: 'number' },
          { name: 'start_term_id', label: 'Mulai semester', type: 'select', options: opts(lookups?.academic_terms, (t) => t.label) },
          { name: 'end_term_id', label: 'Sampai semester (kosong = seterusnya)', type: 'select', options: opts(lookups?.academic_terms, (t) => t.label) },
          { name: 'basis', label: 'Dasar penetapan (SK/nomor surat)', type: 'textarea', required: true },
          { name: 'evidence', label: 'Dokumen pendukung (PDF/JPG/PNG)', type: 'file', accept: '.pdf,.jpg,.jpeg,.png' },
        ]}
        onSubmit={async (v) => {
          if (!student) throw { status: 422, message: 'Pilih mahasiswa terlebih dahulu.' }
          const { evidence, ...rest } = v
          const evidenceId = evidence instanceof File ? await uploadFile(evidence) : null
          await keuAdmin.post('aid-awards', { ...rest, student_id: student.id, evidence_file_id: evidenceId })
          setStudent(null)
          setReload((x) => x + 1)
        }} />
      <ReasonDialog open={deciding !== null} title={deciding?.mode === 'revoke' ? 'Cabut penetapan' : 'Tolak penetapan'} reasonName={deciding?.mode === 'revoke' ? 'reason' : 'note'}
        description={deciding?.mode === 'revoke' ? 'Pencabutan berlaku untuk tagihan berikutnya; potongan pada tagihan yang sudah terbit tidak berubah otomatis.' : undefined}
        onClose={() => setDeciding(null)} onConfirm={(v) => action.run(() => keuAdmin.post(`aid-awards/${deciding?.award.id}/${deciding?.mode}`, v), (x) => x.message).then(done)} />
      <FormDialog open={applying !== null} title="Terapkan potongan ke tagihan terbit" onClose={() => setApplying(null)}
        description="Membuat penyesuaian penurunan yang menunggu persetujuan. Bila tagihan sudah dibayar, kelebihan otomatis masuk deposit setelah disetujui."
        fields={[{ name: 'invoice_id', label: 'Tagihan', type: 'select', required: true, options: (invoices.data?.data ?? []).filter((i) => !['cancelled', 'draft', 'waived'].includes(i.status)).map((i) => ({ value: i.id, label: `${i.invoice_number} · ${i.period} · ${money(i.amount)}` })) }]}
        onSubmit={(v) => action.run(() => keuAdmin.post(`aid-awards/${applying?.id}/apply`, v), (x) => x.message).then(done)} />
    </>
  )
}

interface VoucherRow { id: string; code: string; name: string; discount_type: string; discount_type_label: string; discount_value: string; max_discount: string | null; min_invoice_amount: string | null; bill_groups: string[] | null; scope: Record<string, unknown> | null; quota_total: number | null; quota_used: number; per_student_limit: number; valid_from: string | null; valid_until: string | null; status: string; state_label: string }

function Vouchers() {
  const lookups = useKeuLookups()
  const [reload, setReload] = useState(0)
  const [editing, setEditing] = useState<Partial<VoucherRow> | null>(null)
  const [viewing, setViewing] = useState<VoucherRow | null>(null)
  const redemptions = useLoad(() => (viewing ? keuAdmin.get<Array<{ id: string; student: StudentRef | null; invoice_number: string | null; discount_amount: string; status: string; status_label: string; created_at: string | null }>>(`vouchers/${viewing.id}/redemptions`) : Promise.resolve([])), [viewing?.id])
  const action = useAction()

  return (
    <>
      <Feedback state={action} />
      <KeuList<VoucherRow> path="vouchers" reloadKey={reload} rowKey={(r) => r.id} itemLabel="voucher"
        toolbar={<Can permission={P.VOUCHER_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing({ status: 'active', per_student_limit: 1, discount_type: 'percent' })}>Voucher</Button></Can>}
        columns={[
          { header: 'Kode', cell: (r) => <span className="font-mono">{r.code}<span className="block font-sans text-xs text-ink-tertiary">{r.name}</span></span> },
          { header: 'Potongan', cell: (r) => <span>{r.discount_type === 'percent' ? `${Number(r.discount_value)}%` : r.discount_type === 'full' ? 'Penuh' : money(r.discount_value)}{r.max_discount && <span className="block text-xs text-ink-tertiary">maks. {money(r.max_discount)}</span>}</span> },
          { header: 'Kuota', cell: (r) => `${r.quota_used} / ${r.quota_total ?? '∞'}` },
          { header: 'Masa berlaku', cell: (r) => <span className="text-xs">{dateTime(r.valid_from)}<br />{dateTime(r.valid_until)}</span> },
          { header: 'Status', cell: (r) => <KeuBadge status={r.state_label === 'Aktif' ? 'active' : 'inactive'} label={r.state_label} /> },
          { header: 'Aksi', cell: (r) => <div className="flex gap-1">
            <Button size="sm" variant="ghost" onClick={() => setViewing(r)}>Pemakaian</Button>
            <Can permission={P.VOUCHER_UPDATE}><Button size="sm" variant="outline" onClick={() => setEditing(r)}>Ubah</Button></Can>
          </div> },
        ]} />
      <FormDialog open={editing !== null} title={editing?.id ? 'Ubah voucher' : 'Voucher baru'} initial={editing ?? {}} wide onClose={() => setEditing(null)}
        description={editing?.quota_used ? 'Voucher sudah dipakai: hanya nama, status, masa berlaku, dan kuota yang dapat diubah.' : 'Voucher hanya dapat dipakai pada tagihan berstatus belum dibayar, sebelum jatuh tempo, dan tanpa transaksi berjalan.'}
        fields={[
          { name: 'code', label: 'Kode (huruf/angka/-)', required: true }, { name: 'name', label: 'Nama', required: true },
          { name: 'discount_type', label: 'Cara hitung', type: 'select', required: true, options: lookups?.enums.aid_calc_type ?? [] },
          { name: 'discount_value', label: 'Nilai (% atau Rp)', type: 'number', visible: (v) => v.discount_type !== 'full' },
          { name: 'max_discount', label: 'Potongan maksimal (Rp)', type: 'number' },
          { name: 'min_invoice_amount', label: 'Minimal nilai tagihan (Rp)', type: 'number' },
          BILL_GROUP_FIELD(lookups?.enums.bill_group ?? []),
          { name: 'scope', label: 'Batasan penerima (JSON, opsional)', type: 'json', hint: '{"study_program_ids":["…"],"admission_years":[2026],"admission_path_codes":["KIP"]}' },
          { name: 'quota_total', label: 'Kuota total (kosong = tak terbatas)', type: 'number' },
          { name: 'per_student_limit', label: 'Batas per mahasiswa', type: 'number', required: true },
          { name: 'valid_from', label: 'Berlaku mulai', type: 'datetime', required: true },
          { name: 'valid_until', label: 'Berlaku sampai', type: 'datetime', required: true },
          { name: 'status', label: 'Status', type: 'select', required: true, options: [{ value: 'active', label: 'Aktif' }, { value: 'paused', label: 'Dijeda' }, { value: 'archived', label: 'Diarsipkan' }] },
        ]}
        onSubmit={async (v) => {
          await (editing?.id ? keuAdmin.put(`vouchers/${editing.id}`, v) : keuAdmin.post('vouchers', v))
          setReload((x) => x + 1)
        }} />
      {viewing && (
        <Card title={`Pemakaian voucher ${viewing.code}`} actions={<Button size="sm" variant="ghost" onClick={() => setViewing(null)}>Tutup</Button>} noPadding>
          <DataTable rowKey={(r) => r.id} data={redemptions.data ?? []} emptyMessage="Belum dipakai." columns={[
            { header: 'Mahasiswa', cell: (r) => `${r.student?.nim ?? ''} ${r.student?.name ?? ''}` },
            { header: 'Tagihan', cell: (r) => r.invoice_number },
            { header: 'Potongan', cell: (r) => money(r.discount_amount) },
            { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status_label} /> },
            { header: 'Waktu', cell: (r) => dateTime(r.created_at) },
            { header: 'Aksi', cell: (r) => r.status === 'applied' ? <Can permission={P.VOUCHER_UPDATE}><Button size="sm" variant="ghost" onClick={() => action.run(() => keuAdmin.post(`voucher-redemptions/${r.id}/reverse`), (x) => x.message).then((x) => x && redemptions.reload())}>Batalkan</Button></Can> : null },
          ]} />
        </Card>
      )}
    </>
  )
}

// ---------------------------------------------------------------- Cicilan

type InstTab = 'requests' | 'plans'

export function InstallmentsPage() {
  const [tab, setTab] = useState<InstTab>('requests')

  return (
    <KeuPage title="Cicilan" description="Skema cicilan standar dan pengajuan cicilan/penjadwalan ulang. Jumlah termin selalu sama dengan sisa tagihan." trail={[{ label: 'Cicilan' }]}>
      <Tabs<InstTab> active={tab} onChange={setTab} tabs={[{ key: 'requests', label: 'Pengajuan' }, { key: 'plans', label: 'Skema cicilan' }]} />
      {tab === 'requests' ? <InstallmentRequests /> : <Plans />}
    </KeuPage>
  )
}

interface InstRequest { id: string; student: StudentRef | null; invoice: Invoice; plan: string | null; terms: Array<{ percent: number; due_date?: string; due_offset_days?: number }>; kind: string; status: string; status_label: string; plan_status: string | null; reason: string | null; decided_by: string | null; decision_note: string | null; created_at: string | null }

function InstallmentRequests() {
  const [reload, setReload] = useState(0)
  const [rejecting, setRejecting] = useState<InstRequest | null>(null)
  const canApprove = usePermission(P.INST_APPROVE)
  const action = useAction()
  const done = (r: unknown) => r && setReload((x) => x + 1)

  return (
    <>
      <Feedback state={action} />
      <KeuList<InstRequest> path="installment-requests" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="pengajuan"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'submitted', label: 'Menunggu' }, { value: 'approved', label: 'Disetujui' }, { value: 'rejected', label: 'Ditolak' }] }]}
        columns={[
          { header: 'Mahasiswa', cell: (r) => <span>{r.student?.nim}<span className="block text-xs text-ink-tertiary">{r.student?.name}</span></span> },
          { header: 'Tagihan', cell: (r) => <Link className="text-primary" to={to(KEU.admin.invoice, { id: r.invoice.id })}>{r.invoice.invoice_number}<span className="block text-xs text-ink-tertiary">sisa {money(r.invoice.outstanding)}</span></Link> },
          { header: 'Skema', cell: (r) => <span>{r.plan ?? 'Khusus'}{r.kind === 'reschedule' && <Badge variant="info" className="ml-1">Jadwal ulang</Badge>}<span className="block text-xs text-ink-tertiary">{r.terms.map((t) => `${t.percent}%`).join(' / ')}</span></span> },
          { header: 'Alasan', cell: (r) => <span className="whitespace-normal text-xs">{r.reason ?? '—'}</span> },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{r.decision_note && <span className="block text-xs text-ink-tertiary">{r.decision_note}</span>}</span> },
          { header: 'Aksi', cell: (r) => r.status === 'submitted' && canApprove ? <div className="flex gap-1">
            <Button size="sm" onClick={() => action.run(() => keuAdmin.post(`installment-requests/${r.id}/approve`), (x) => x.message).then(done)}>Setujui</Button>
            <Button size="sm" variant="outline" onClick={() => setRejecting(r)}>Tolak</Button>
          </div> : null },
        ]} />
      <ReasonDialog open={rejecting !== null} title="Tolak pengajuan cicilan" reasonName="note" onClose={() => setRejecting(null)}
        onConfirm={(v) => action.run(() => keuAdmin.post(`installment-requests/${rejecting?.id}/reject`, v), (x) => x.message).then(done)} />
    </>
  )
}

interface Plan { id: string; code: string; name: string; bill_groups: string[]; terms: Array<{ percent: number; due_date?: string; due_offset_days?: number }>; min_first_payment: string | null; auto_approve: boolean; require_no_arrears: boolean; is_active: boolean }

function Plans() {
  const lookups = useKeuLookups()
  const { data, error, reload } = useLoad(() => keuAdmin.get<Plan[]>('installment-plans'))
  const [editing, setEditing] = useState<Partial<Plan> | null>(null)

  return (
    <Card noPadding title="Skema cicilan" actions={<Can permission={P.INST_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing({ is_active: true, auto_approve: true, require_no_arrears: true, bill_groups: ['registration'], terms: [{ percent: 50, due_offset_days: 14 }, { percent: 50, due_offset_days: 60 }] })}>Skema</Button></Can>}>
      {error && <Alert variant="danger" className="m-3">{describeError(error)}</Alert>}
      <DataTable rowKey={(r) => r.id} data={data ?? []} emptyMessage="Belum ada skema cicilan." columns={[
        { header: 'Skema', cell: (r) => <span>{r.name}<span className="block text-xs text-ink-tertiary">{r.code}</span></span> },
        { header: 'Termin', cell: (r) => r.terms.map((t) => `${t.percent}%${t.due_offset_days ? ` (+${t.due_offset_days} hr)` : t.due_date ? ` (${date(t.due_date)})` : ''}`).join(' / ') },
        { header: 'Kelompok', cell: (r) => r.bill_groups.map((g) => lookups?.enums.bill_group.find((o) => o.value === g)?.label ?? g).join(', ') },
        { header: 'Ketentuan', cell: (r) => <span className="flex flex-wrap gap-1">{r.auto_approve && <Badge variant="info">Otomatis</Badge>}{r.require_no_arrears && <Badge variant="warning">Tanpa tunggakan lain</Badge>}</span> },
        { header: 'Status', cell: (r) => <KeuBadge status={r.is_active ? 'active' : 'inactive'} label={r.is_active ? 'Aktif' : 'Nonaktif'} /> },
        { header: 'Aksi', cell: (r) => <Can permission={P.INST_UPDATE}><Button size="sm" variant="outline" onClick={() => setEditing(r)}>Ubah</Button></Can> },
      ]} />
      <FormDialog open={editing !== null} title={editing?.id ? 'Ubah skema' : 'Skema baru'} initial={editing ?? {}} wide onClose={() => setEditing(null)}
        description="Perubahan skema tidak mengubah jadwal yang sudah berjalan."
        fields={[
          { name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true },
          BILL_GROUP_FIELD(lookups?.enums.bill_group ?? []),
          { name: 'terms', label: 'Termin (JSON)', type: 'json', required: true, hint: 'Persentase total 100. Jatuh tempo: due_offset_days (hari sejak tagihan terbit) atau due_date.' },
          { name: 'min_first_payment', label: 'Pembayaran pertama minimal (Rp)', type: 'number' },
          { name: 'auto_approve', label: 'Disetujui otomatis bila memakai skema ini', type: 'checkbox' },
          { name: 'require_no_arrears', label: 'Syarat: tidak ada tunggakan pada tagihan lain', type: 'checkbox' },
          { name: 'is_active', label: 'Aktif', type: 'checkbox' },
        ]}
        onSubmit={async (v) => {
          await (editing?.id ? keuAdmin.put(`installment-plans/${editing.id}`, v) : keuAdmin.post('installment-plans', v))
          invalidateKeuLookups()
          reload()
        }} />
    </Card>
  )
}

// ---------------------------------------------------------------- Denda

type PenTab = 'accruals' | 'rules'

export function PenaltiesPage() {
  const [tab, setTab] = useState<PenTab>('accruals')

  return (
    <KeuPage title="Denda Keterlambatan" description="Denda dihitung harian atas pokok yang terlambat (setelah tenggang), tidak atas denda. Saat pokok lunas, denda ditagihkan sebagai tagihan denda terpisah dengan tanggal bayar efektif." trail={[{ label: 'Denda' }]}>
      <Tabs<PenTab> active={tab} onChange={setTab} tabs={[{ key: 'accruals', label: 'Denda berjalan' }, { key: 'rules', label: 'Aturan denda' }]} />
      {tab === 'accruals' ? <Accruals /> : <PenaltyRules />}
    </KeuPage>
  )
}

interface Accrual { id: string; invoice: Invoice; rule: string | null; installment_seq: number | null; overdue_from: string | null; days: number; amount: string; status: string; status_label: string; penalty_invoice_id: string | null; penalty_invoice_number: string | null; last_calculated_on: string | null }

function Accruals() {
  const [reload, setReload] = useState(0)
  const [waiving, setWaiving] = useState<Accrual | null>(null)
  const action = useAction()
  const done = (r: unknown) => r && setReload((x) => x + 1)

  return (
    <>
      <Feedback state={action} />
      <KeuList<Accrual> path="penalty-accruals" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="denda"
        toolbar={<Can permission={P.PEN_UPDATE}><Button size="sm" variant="outline" leftIcon={<Play className="size-4" />} onClick={() => action.run(() => keuAdmin.post<{ updated: number }>('penalty-accruals/run'), (x) => `${x.message} (${x.data.updated} target diperbarui)`).then(done)}>Hitung sekarang</Button></Can>}
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'accruing', label: 'Berjalan' }, { value: 'crystallized', label: 'Ditagihkan' }, { value: 'waived', label: 'Dihapus' }, { value: 'stopped', label: 'Berhenti' }] }]}
        columns={[
          { header: 'Tagihan pokok', cell: (r) => <Link className="text-primary" to={to(KEU.admin.invoice, { id: r.invoice.id })}>{r.invoice.invoice_number}{r.installment_seq ? ` · termin ${r.installment_seq}` : ''}<span className="block text-xs text-ink-tertiary">{r.invoice.student?.nim} {r.invoice.student?.name}</span></Link> },
          { header: 'Aturan', cell: (r) => r.rule },
          { header: 'Terlambat sejak', cell: (r) => date(r.overdue_from) },
          { header: 'Hari', cell: (r) => r.days },
          { header: 'Denda', cell: (r) => money(r.amount) },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{r.penalty_invoice_id && <Link className="block text-xs text-primary" to={to(KEU.admin.invoice, { id: r.penalty_invoice_id })}>{r.penalty_invoice_number}</Link>}</span> },
          { header: 'Aksi', cell: (r) => r.status === 'accruing' ? <div className="flex gap-1">
            <Can permission={P.PEN_UPDATE}><Button size="sm" variant="outline" onClick={() => action.run(() => keuAdmin.post(`penalty-accruals/${r.id}/crystallize`), (x) => x.message).then(done)}>Tagihkan</Button></Can>
            <Can permission={P.PEN_APPROVE}><Button size="sm" variant="ghost" onClick={() => setWaiving(r)}>Hapus</Button></Can>
          </div> : null },
        ]} />
      <ReasonDialog open={waiving !== null} title="Hapus denda berjalan" description="Denda yang sudah ditagihkan dihapus lewat penyesuaian “Pembebasan Denda” pada tagihan dendanya." onClose={() => setWaiving(null)}
        onConfirm={(v) => action.run(() => keuAdmin.post(`penalty-accruals/${waiving?.id}/waive`, v), (x) => x.message).then(done)} />
    </>
  )
}

interface PenaltyRule { id: string; code: string; name: string; bill_groups: string[]; grace_days: number; calc_type: string; calc_type_label: string; period_unit: string; period_unit_label: string; rate_percent: string | null; flat_amount: string | null; cap_amount: string | null; cap_percent: string | null; max_periods: number | null; effective_from: string | null; effective_until: string | null; status: string; status_label: string; decree_reference: string | null }

function PenaltyRules() {
  const lookups = useKeuLookups()
  const { data, error, reload } = useLoad(() => keuAdmin.get<PenaltyRule[]>('penalty-rules'))
  const [editing, setEditing] = useState<Partial<PenaltyRule> | null>(null)
  const action = useAction()
  const run = (fn: () => Promise<{ message: string }>) => action.run(fn, (x) => x.message).then((x) => x && reload())

  return (
    <Card noPadding title="Aturan denda" actions={<Can permission={P.PEN_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing({ grace_days: 0, calc_type: 'percent', period_unit: 'day', bill_groups: ['registration'] })}>Aturan</Button></Can>}>
      <div className="p-3"><Feedback state={action} />{error && <Alert variant="danger">{describeError(error)}</Alert>}</div>
      <DataTable rowKey={(r) => r.id} data={data ?? []} emptyMessage="Belum ada aturan denda — tidak ada denda yang dihitung." columns={[
        { header: 'Aturan', cell: (r) => <span>{r.name}<span className="block text-xs text-ink-tertiary">{r.code}{r.decree_reference ? ` · ${r.decree_reference}` : ''}</span></span> },
        { header: 'Rumus', cell: (r) => <span className="text-xs">{r.calc_type === 'percent' ? `${Number(r.rate_percent)}%` : money(r.flat_amount)} per {r.period_unit_label.toLowerCase()} · tenggang {r.grace_days} hari{r.cap_amount ? ` · maks. ${money(r.cap_amount)}` : ''}{r.cap_percent ? ` · maks. ${Number(r.cap_percent)}% pokok` : ''}</span> },
        { header: 'Berlaku', cell: (r) => `${date(r.effective_from)}${r.effective_until ? ` – ${date(r.effective_until)}` : ''}` },
        { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status_label} /> },
        { header: 'Aksi', cell: (r) => <div className="flex gap-1">
          {r.status === 'draft' && <Can permission={P.PEN_UPDATE}><Button size="sm" variant="outline" onClick={() => setEditing(r)}>Ubah</Button><Button size="sm" onClick={() => run(() => keuAdmin.post(`penalty-rules/${r.id}/submit`))}>Ajukan</Button></Can>}
          {r.status === 'pending_approval' && <Can permission={P.PEN_APPROVE}><Button size="sm" onClick={() => run(() => keuAdmin.post(`penalty-rules/${r.id}/approve`))}>Setujui</Button></Can>}
          {r.status === 'active' && <Can permission={P.PEN_APPROVE}><Button size="sm" variant="ghost" onClick={() => run(() => keuAdmin.post(`penalty-rules/${r.id}/deactivate`))}>Nonaktifkan</Button></Can>}
        </div> },
      ]} />
      <FormDialog open={editing !== null} title={editing?.id ? 'Ubah aturan denda' : 'Aturan denda baru'} initial={editing ?? {}} wide onClose={() => setEditing(null)}
        description="Aturan baru berstatus draf dan aktif setelah disetujui petugas lain."
        fields={[
          { name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true },
          BILL_GROUP_FIELD(lookups?.enums.bill_group ?? []),
          { name: 'grace_days', label: 'Tenggang (hari)', type: 'number', required: true },
          { name: 'calc_type', label: 'Cara hitung', type: 'select', required: true, options: lookups?.enums.penalty_calc_type ?? [] },
          { name: 'period_unit', label: 'Satuan periode', type: 'select', required: true, options: lookups?.enums.penalty_period_unit ?? [] },
          { name: 'rate_percent', label: 'Persentase per periode (%)', type: 'number', visible: (v) => v.calc_type === 'percent' },
          { name: 'flat_amount', label: 'Nominal per periode (Rp)', type: 'number', visible: (v) => v.calc_type === 'flat' },
          { name: 'cap_amount', label: 'Batas maksimal (Rp)', type: 'number' },
          { name: 'cap_percent', label: 'Batas maksimal (% pokok)', type: 'number' },
          { name: 'max_periods', label: 'Jumlah periode maksimal', type: 'number' },
          { name: 'effective_from', label: 'Berlaku mulai', type: 'date', required: true },
          { name: 'effective_until', label: 'Berlaku sampai', type: 'date' },
          { name: 'decree_reference', label: 'Dasar (nomor SK)' },
        ]}
        onSubmit={async (v) => {
          await (editing?.id ? keuAdmin.put(`penalty-rules/${editing.id}`, v) : keuAdmin.post('penalty-rules', v))
          reload()
        }} />
    </Card>
  )
}

// ---------------------------------------------------------------- UKT

type UktTab = 'assignments' | 'requests' | 'groups'

export function UktPage() {
  const [tab, setTab] = useState<UktTab>('assignments')

  return (
    <KeuPage title="UKT" description="Golongan Uang Kuliah Tunggal dan penetapannya per mahasiswa. Tarif per golongan diatur pada tabel tarif (komponen kategori UKT)." trail={[{ label: 'UKT' }]}>
      <Alert variant="info">Instrumen penilaian kemampuan ekonomi (skoring otomatis) belum tersedia. Penetapan golongan dilakukan petugas berdasarkan SK/hasil verifikasi.</Alert>
      <Tabs<UktTab> active={tab} onChange={setTab} tabs={[{ key: 'assignments', label: 'Penetapan' }, { key: 'requests', label: 'Pengajuan peninjauan' }, { key: 'groups', label: 'Golongan' }]} />
      {tab === 'assignments' && <UktAssignments />}
      {tab === 'requests' && <UktRequests />}
      {tab === 'groups' && <UktGroups />}
    </KeuPage>
  )
}

function UktGroups() {
  const { data, error, reload } = useLoad(() => keuAdmin.get<Array<{ id: string; code: string; name: string; level: number; description: string | null; is_active: boolean; assignments_count: number }>>('ukt-groups'))
  const [editing, setEditing] = useState<Record<string, unknown> | null>(null)

  return (
    <Card noPadding title="Golongan UKT" actions={<Can permission={P.UKT_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing({ is_active: true })}>Golongan</Button></Can>}>
      {error && <Alert variant="danger" className="m-3">{describeError(error)}</Alert>}
      <DataTable rowKey={(r) => r.id} data={data ?? []} emptyMessage="Belum ada golongan UKT." columns={[
        { header: 'Golongan', cell: (r) => <span>{r.name}<span className="block text-xs text-ink-tertiary">{r.code} · tingkat {r.level}</span></span> },
        { header: 'Mahasiswa aktif', cell: (r) => r.assignments_count },
        { header: 'Status', cell: (r) => <KeuBadge status={r.is_active ? 'active' : 'inactive'} label={r.is_active ? 'Aktif' : 'Nonaktif'} /> },
        { header: 'Aksi', cell: (r) => <Can permission={P.UKT_UPDATE}><Button size="sm" variant="outline" onClick={() => setEditing(r)}>Ubah</Button></Can> },
      ]} />
      <FormDialog open={editing !== null} title={editing?.id ? 'Ubah golongan' : 'Golongan baru'} initial={editing ?? {}} onClose={() => setEditing(null)}
        fields={[{ name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true }, { name: 'level', label: 'Tingkat (1 = terendah)', type: 'number', required: true }, { name: 'description', label: 'Keterangan', type: 'textarea' }, { name: 'is_active', label: 'Aktif', type: 'checkbox' }]}
        onSubmit={async (v) => {
          await (editing?.id ? keuAdmin.put(`ukt-groups/${String(editing.id)}`, v) : keuAdmin.post('ukt-groups', v))
          invalidateKeuLookups()
          reload()
        }} />
    </Card>
  )
}

function UktAssignments() {
  const lookups = useKeuLookups()
  const [reload, setReload] = useState(0)
  const [assigning, setAssigning] = useState(false)
  const [students, setStudents] = useState<StudentRef[]>([])
  const action = useAction()

  return (
    <>
      <Feedback state={action} />
      <KeuList<{ id: string; student: StudentRef | null; group: { name: string; code: string } | null; effective_from: string | null; basis: string; decree_number: string | null; status: string; assigned_by: string | null; created_at: string | null }>
        path="ukt-assignments" reloadKey={reload} rowKey={(r) => r.id} itemLabel="penetapan"
        toolbar={<Can permission={P.UKT_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setAssigning(true)}>Tetapkan golongan</Button></Can>}
        filters={[{ name: 'ukt_group_id', label: 'Golongan', type: 'select', options: opts(lookups?.ukt_groups, (g) => g.name) }, { name: 'include_history', label: 'Riwayat', type: 'select', options: [{ value: '1', label: 'Tampilkan riwayat' }] }]}
        columns={[
          { header: 'Mahasiswa', cell: (r) => r.student ? <Link className="text-primary" to={to(KEU.admin.student, { id: r.student.id })}>{r.student.nim}<span className="block text-xs text-ink-tertiary">{r.student.name}</span></Link> : '—' },
          { header: 'Golongan', cell: (r) => r.group?.name },
          { header: 'Berlaku', cell: (r) => r.effective_from ?? 'Segera' },
          { header: 'Dasar', cell: (r) => <span className="whitespace-normal text-xs">{r.decree_number ? `${r.decree_number} — ` : ''}{r.basis}</span> },
          { header: 'Status', cell: (r) => <KeuBadge status={r.status === 'active' ? 'active' : 'superseded'} label={r.status === 'active' ? 'Berlaku' : 'Riwayat'} /> },
          { header: 'Oleh', cell: (r) => <span className="text-xs">{r.assigned_by}<br />{dateTime(r.created_at)}</span> },
        ]} />
      <FormDialog open={assigning} title="Tetapkan golongan UKT" onClose={() => { setAssigning(false); setStudents([]) }}
        description="Golongan baru berlaku untuk tagihan berikutnya; tagihan yang sudah terbit tidak berubah."
        prepend={<div className="flex flex-col gap-2">
          <StudentPicker value={null} label="Tambah mahasiswa" onChange={(s) => s && setStudents((list) => list.some((x) => x.id === s.id) ? list : [...list, s])} />
          <div className="flex flex-wrap gap-1">{students.length === 0 ? <span className="text-xs text-ink-tertiary">Belum ada mahasiswa dipilih.</span> : students.map((s) => (
            <button key={s.id} type="button" className="rounded-full bg-surface-hover px-2 py-0.5 text-xs" onClick={() => setStudents((list) => list.filter((x) => x.id !== s.id))}>{s.nim} ✕</button>
          ))}</div>
        </div>}
        fields={[
          { name: 'ukt_group_id', label: 'Golongan', type: 'select', required: true, options: opts(lookups?.ukt_groups.filter((g) => g.is_active), (g) => g.name) },
          { name: 'effective_term_id', label: 'Berlaku mulai semester', type: 'select', options: opts(lookups?.academic_terms, (t) => t.label) },
          { name: 'decree_number', label: 'Nomor SK' },
          { name: 'basis', label: 'Dasar penetapan', type: 'textarea', required: true },
        ]}
        onSubmit={async (v) => {
          if (students.length === 0) throw { status: 422, message: 'Pilih minimal satu mahasiswa.' }
          await action.run(() => keuAdmin.post('ukt-assignments', { ...v, student_ids: students.map((s) => s.id) }), (x) => x.message)
          setStudents([])
          setReload((x) => x + 1)
        }} />
    </>
  )
}

function UktRequests() {
  const lookups = useKeuLookups()
  const [reload, setReload] = useState(0)
  const [deciding, setDeciding] = useState<{ id: string; approve: boolean } | null>(null)
  const canApprove = usePermission(P.UKT_APPROVE)
  const action = useAction()

  return (
    <>
      <Feedback state={action} />
      <KeuList<{ id: string; student: StudentRef | null; current_group: string | null; requested_group: string | null; decided_group: string | null; reason: string; evidence_file_id: string | null; status: string; status_label: string; decision_note: string | null; decided_by: string | null; created_at: string | null }>
        path="ukt-requests" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="pengajuan"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'submitted', label: 'Menunggu' }, { value: 'approved', label: 'Disetujui' }, { value: 'rejected', label: 'Ditolak' }] }]}
        columns={[
          { header: 'Mahasiswa', cell: (r) => <span>{r.student?.nim}<span className="block text-xs text-ink-tertiary">{r.student?.name}</span></span> },
          { header: 'Golongan', cell: (r) => <span className="text-xs">Saat ini: {r.current_group ?? '—'}<br />Diminta: {r.requested_group ?? '—'}{r.decided_group ? <><br />Diputuskan: {r.decided_group}</> : null}</span> },
          { header: 'Alasan', cell: (r) => <span className="whitespace-normal text-xs">{r.reason}</span> },
          { header: 'Bukti', cell: (r) => r.evidence_file_id ? <Button size="sm" variant="ghost" onClick={() => action.run(() => downloadBlob(`/file-uploads/${r.evidence_file_id}/download`, 'bukti-ukt'))}><FileText className="size-4" /></Button> : '—' },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{r.decision_note && <span className="block text-xs text-ink-tertiary">{r.decision_note}</span>}</span> },
          { header: 'Aksi', cell: (r) => r.status === 'submitted' && canApprove ? <div className="flex gap-1"><Button size="sm" onClick={() => setDeciding({ id: r.id, approve: true })}>Setujui</Button><Button size="sm" variant="outline" onClick={() => setDeciding({ id: r.id, approve: false })}>Tolak</Button></div> : null },
        ]} />
      <FormDialog open={deciding !== null} title={deciding?.approve ? 'Setujui peninjauan UKT' : 'Tolak peninjauan UKT'} onClose={() => setDeciding(null)}
        fields={[
          ...(deciding?.approve ? [
            { name: 'ukt_group_id', label: 'Golongan baru', type: 'select' as const, required: true, options: opts(lookups?.ukt_groups.filter((g) => g.is_active), (g) => g.name) },
            { name: 'effective_term_id', label: 'Berlaku mulai semester', type: 'select' as const, options: opts(lookups?.academic_terms, (t) => t.label) },
          ] : []),
          { name: 'note', label: 'Catatan keputusan', type: 'textarea', required: true },
        ]}
        onSubmit={(v) => action.run(() => keuAdmin.post(`ukt-requests/${deciding?.id}/decide`, { ...v, approve: deciding?.approve }), (x) => x.message).then((x) => x && setReload((n) => n + 1))} />
    </>
  )
}
