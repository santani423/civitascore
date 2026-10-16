import { useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import { Download, FilePlus2, Plus, Trash2 } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Modal } from '@/components/ui/Modal'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { Checkbox } from '@/components/ui/Checkbox'
import { DataTable } from '@/components/ui/DataTable'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, describeError, useAction } from '@/pmb/components/useAction'
import { FormDialog } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { KeyValue } from '@/pmb/components/KeyValue'
import { Tabs } from '@/pmb/components/Tabs'
import { date, dateTime, money } from '@/pmb/lib/format'
import { usePermission } from '@/hooks/usePermission'
import { keuAdmin } from '@/keu/api'
import { Can, Figure, KeuBadge, KeuList, KeuPage, StudentPicker } from '@/keu/components'
import { P } from '@/keu/permissions'
import { opts, useKeuLookups } from '@/keu/lookups'
import { KEU, to } from '@/keu/routes'
import type { Adjustment, Invoice, InvoiceDetail, StudentRef } from '@/keu/types'
import { invoiceColumns } from '@/keu/columns'
import type { NormalizedApiError } from '@/services/api'

const MANUAL_GROUPS = ['registration', 'credit', 'incidental', 'short_term', 'graduation', 'opening_balance']

/** Daftar tagihan (F-03) — filter, pencarian, ekspor, tagihan manual. */
export function InvoicesPage() {
  const lookups = useKeuLookups()
  // Tautan lama dari Dashboard/detail mahasiswa: ?status=unpaid,partial & ?student_id=...
  const [searchParams] = useSearchParams()
  const [initialFilters] = useState(() => Object.fromEntries(['status', 'student_id'].flatMap((k) => searchParams.get(k) ? [[`filter[${k}]`, searchParams.get(k) as string]] : [])))
  const [reload, setReload] = useState(0)
  const [creating, setCreating] = useState(false)
  const [exporting, setExporting] = useState(false)
  const action = useAction()

  return (
    <KeuPage title="Tagihan Mahasiswa" description="Semua tagihan terbit, draf, dan riwayat statusnya. Tagihan terbit hanya dapat diubah lewat penyesuaian." trail={[{ label: 'Tagihan' }]}
      actions={<>
        <Can permission={P.INVOICES_EXPORT}><Button variant="outline" leftIcon={<Download className="size-4" />} onClick={() => setExporting(true)}>Ekspor CSV</Button></Can>
        <Can permission={P.INVOICES_CREATE}><Button leftIcon={<FilePlus2 className="size-4" />} onClick={() => setCreating(true)}>Tagihan manual</Button></Can>
      </>}>
      <Feedback state={action} />
      <KeuList<Invoice> path="/invoices" reloadKey={reload} rowKey={(r) => r.id} itemLabel="tagihan" initialFilters={initialFilters}
        filters={[
          { name: 'filter[student_id]', label: 'ID mahasiswa', type: 'text' },
          { name: 'filter[status]', label: 'Status', type: 'select', options: [{ value: 'unpaid,partial', label: 'Belum lunas (semua)' }, ...(lookups?.enums.invoice_status ?? [])] },
          { name: 'filter[bill_group]', label: 'Kelompok', type: 'select', options: lookups?.enums.bill_group ?? [] },
          { name: 'filter[academic_term_id]', label: 'Semester', type: 'select', options: opts(lookups?.academic_terms, (t) => t.label) },
          { name: 'study_program_id', label: 'Prodi', type: 'select', options: opts(lookups?.study_programs, (p) => p.name) },
          { name: 'overdue', label: 'Menunggak', type: 'select', options: [{ value: '1', label: 'Ya' }] },
          { name: 'due_from', label: 'Jatuh tempo dari', type: 'date' },
          { name: 'due_to', label: 'sampai', type: 'date' },
        ]}
        columns={invoiceColumns()} />
      <ManualInvoiceDialog open={creating} onClose={() => setCreating(false)} onSaved={(message) => { setReload((r) => r + 1); action.run(async () => message, (m) => m) }} />
      <ReasonDialog open={exporting} title="Ekspor daftar tagihan" description="Ekspor data keuangan dicatat di log audit beserta alasannya." confirmLabel="Unduh CSV" onClose={() => setExporting(false)}
        extraFields={[
          { name: 'status', label: 'Status (opsional)', type: 'select', options: lookups?.enums.invoice_status ?? [] },
          { name: 'academic_term_id', label: 'Semester (opsional)', type: 'select', options: opts(lookups?.academic_terms, (t) => t.label) },
        ]}
        onConfirm={(v) => keuAdmin.download('invoices-export', v as Record<string, string>, 'tagihan.csv')} />
    </KeuPage>
  )
}

interface Line { fee_component_id: string; description: string; amount: string }

function ManualInvoiceDialog({ open, onClose, onSaved }: { open: boolean; onClose: () => void; onSaved: (message: string) => void }) {
  const lookups = useKeuLookups()
  const [student, setStudent] = useState<StudentRef | null>(null)
  const [form, setForm] = useState({ academic_term_id: '', bill_group: 'incidental', due_date: '', period: '', notes: '', draft: false })
  const [lines, setLines] = useState<Line[]>([{ fee_component_id: '', description: '', amount: '' }])
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)
  const total = lines.reduce((sum, l) => sum + (Number(l.amount) || 0), 0)

  async function submit() {
    setBusy(true)
    setError(null)

    try {
      const { message } = await keuAdmin.post('invoices', {
        ...form, student_id: student?.id, academic_term_id: form.academic_term_id || null, period: form.period || null, notes: form.notes || null,
        lines: lines.map((l) => ({ fee_component_id: l.fee_component_id || null, description: l.description, amount: l.amount })),
      })
      onSaved(message)
      onClose()
      setStudent(null)
      setLines([{ fee_component_id: '', description: '', amount: '' }])
    } catch (err) {
      setError(describeError(err as NormalizedApiError))
    } finally {
      setBusy(false)
    }
  }

  return (
    <Modal open={open} onClose={onClose} title="Tagihan manual / insidental" className="max-w-3xl">
      <div className="flex flex-col gap-4">
        <p className="text-sm text-ink-secondary">Untuk biaya insidental (mis. cetak ulang kartu, praktikum susulan). Tagihan rutin semester diterbitkan lewat Penerbitan Massal agar tarif diambil dari tabel tarif.</p>
        {error && <Alert variant="danger">{error}</Alert>}
        <StudentPicker value={student} onChange={setStudent} />
        <div className="grid gap-3 sm:grid-cols-2">
          <Select label="Semester" placeholder="Tanpa semester" options={opts(lookups?.academic_terms, (t) => t.label)} value={form.academic_term_id} onChange={(e) => setForm({ ...form, academic_term_id: e.target.value })} />
          <Select label="Kelompok tagihan *" options={(lookups?.enums.bill_group ?? []).filter((o) => MANUAL_GROUPS.includes(o.value))} value={form.bill_group} onChange={(e) => setForm({ ...form, bill_group: e.target.value })} />
          <Input label="Jatuh tempo *" type="date" value={form.due_date} onChange={(e) => setForm({ ...form, due_date: e.target.value })} />
          <Input label="Periode (opsional)" placeholder="Default: label semester" value={form.period} onChange={(e) => setForm({ ...form, period: e.target.value })} />
        </div>
        <div className="flex flex-col gap-2">
          <p className="text-sm font-medium text-ink-primary">Rincian biaya *</p>
          {lines.map((line, index) => (
            <div key={index} className="grid gap-2 sm:grid-cols-[1fr_2fr_1fr_auto] sm:items-end">
              <Select label="Komponen" placeholder="(bebas)" options={opts(lookups?.fee_components.filter((c) => c.is_active), (c) => `${c.code} — ${c.name}`)} value={line.fee_component_id} onChange={(e) => setLines(lines.map((l, i) => i === index ? { ...l, fee_component_id: e.target.value } : l))} />
              <Input label="Uraian" value={line.description} onChange={(e) => setLines(lines.map((l, i) => i === index ? { ...l, description: e.target.value } : l))} />
              <Input label="Nominal (Rp)" type="number" min={1} value={line.amount} onChange={(e) => setLines(lines.map((l, i) => i === index ? { ...l, amount: e.target.value } : l))} />
              <Button variant="ghost" aria-label="Hapus baris" disabled={lines.length === 1} onClick={() => setLines(lines.filter((_, i) => i !== index))}><Trash2 className="size-4" /></Button>
            </div>
          ))}
          <div className="flex items-center justify-between">
            <Button variant="outline" size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setLines([...lines, { fee_component_id: '', description: '', amount: '' }])}>Tambah baris</Button>
            <span className="text-sm">Total: <strong>{money(total)}</strong></span>
          </div>
        </div>
        <Textarea label="Catatan internal" rows={2} value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
        <Checkbox label="Simpan sebagai draf (belum terlihat mahasiswa)" checked={form.draft} onChange={(e) => setForm({ ...form, draft: e.target.checked })} />
        <div className="flex justify-end gap-2">
          <Button variant="outline" onClick={onClose}>Batal</Button>
          <Button isLoading={busy} disabled={!student} onClick={submit}>{form.draft ? 'Simpan draf' : 'Terbitkan'}</Button>
        </div>
      </div>
    </Modal>
  )
}

type DetailTab = 'items' | 'payments' | 'installments' | 'adjustments'

/** Detail tagihan + aksi: terbitkan draf, penyesuaian, cicilan, voucher, deposit. */
export function InvoiceDetailPage() {
  const { id = '' } = useParams()
  const navigate = useNavigate()
  const lookups = useKeuLookups()
  const { data, error, reload } = useLoad(() => keuAdmin.get<InvoiceDetail>(`/invoices/${id}`), [id])
  const action = useAction()
  const [tab, setTab] = useState<DetailTab>('items')
  const [dialog, setDialog] = useState<'adjust' | 'installment' | 'voucher' | 'deposit' | 'cancelSchedule' | null>(null)
  const canAdjust = usePermission(P.ADJ_CREATE)
  const run = (fn: () => Promise<{ message: string }>) => action.run(fn, (r) => r.message).then((r) => { if (r) reload() })

  if (error) return <KeuPage title="Tagihan" trail={[{ label: 'Tagihan', path: KEU.admin.invoices }]}><Alert variant="danger">{describeError(error)}</Alert></KeuPage>
  if (!data) return <p className="text-sm text-ink-secondary">Memuat...</p>
  const open = data.status === 'unpaid' || data.status === 'partial'

  return (
    <KeuPage title={`Tagihan ${data.invoice_number ?? ''}`} description={`${data.bill_group_label} · ${data.period}`} trail={[{ label: 'Tagihan', path: KEU.admin.invoices }, { label: data.invoice_number ?? data.id }]}
      actions={<div className="flex flex-wrap gap-2">
        {data.status === 'draft' && <Can permission={P.INVOICES_CREATE}><Button onClick={() => run(() => keuAdmin.post(`invoices/${id}/issue`))}>Terbitkan</Button></Can>}
        {data.status === 'draft' && <Can permission={P.INVOICES_UPDATE}><Button variant="outline" onClick={() => action.run(() => keuAdmin.send('delete', `invoices/${id}`), 'Draf dibatalkan.').then((r) => r && navigate(KEU.admin.invoices))}>Hapus draf</Button></Can>}
        {canAdjust && !['draft', 'cancelled', 'waived'].includes(data.status) && <Button variant="outline" onClick={() => setDialog('adjust')}>Ajukan penyesuaian</Button>}
        {open && <Can permission={P.INST_CREATE}><Button variant="outline" onClick={() => setDialog('installment')}>Cicilan</Button></Can>}
        {data.status === 'unpaid' && <Can permission={P.VOUCHER_UPDATE}><Button variant="outline" onClick={() => setDialog('voucher')}>Pakai voucher</Button></Can>}
        {open && <Can permission={P.DEPOSIT_UPDATE}><Button variant="outline" onClick={() => setDialog('deposit')}>Bayar dari deposit</Button></Can>}
      </div>}>
      <Feedback state={action} />
      <Card>
        <div className="grid gap-4 sm:grid-cols-5">
          <Figure label="Bruto" value={money(data.gross_amount)} />
          <Figure label="Potongan" value={money(data.discount_amount)} />
          <Figure label="Penyesuaian" value={money(data.adjustment_amount)} />
          <Figure label="Dibayar" value={money(data.paid_amount)} tone="success" />
          <Figure label="Sisa" value={money(data.outstanding)} tone={Number(data.outstanding) > 0 ? 'danger' : undefined} />
        </div>
        <div className="mt-4 border-t border-border pt-4">
          <KeyValue columns={3} items={[
            ['Mahasiswa', data.student ? <Link key="mahasiswa" className="text-primary" to={to(KEU.admin.student, { id: data.student.id })}>{data.student.nim} — {data.student.name}</Link> : '—'],
            ['Status', <span key="status" className="flex gap-1"><KeuBadge status={data.status} label={data.status_label} />{data.is_overdue && <KeuBadge status="unpaid" overdue />}</span>],
            ['Jatuh tempo', date(data.due_date)],
            ['Diterbitkan', dateTime(data.issued_at)],
            ['Lunas', dateTime(data.settled_at)],
            ['Menunggak sejak', date(data.overdue_since)],
            ['Sumber', data.source_type ?? 'KEU'],
            ['Tagihan induk', data.parent_invoice_id ? <Link key="tagihan-induk" className="text-primary" to={to(KEU.admin.invoice, { id: data.parent_invoice_id })}>Lihat</Link> : '—'],
            ['Alasan pembatalan', data.cancel_reason],
          ]} />
          {data.notes && <p className="mt-3 text-sm text-ink-secondary">Catatan: {data.notes}</p>}
        </div>
      </Card>

      <Tabs<DetailTab> active={tab} onChange={setTab} tabs={[
        { key: 'items', label: 'Rincian' }, { key: 'payments', label: 'Pembayaran', badge: data.payments.length },
        { key: 'installments', label: 'Cicilan', badge: data.installments.length }, { key: 'adjustments', label: 'Penyesuaian', badge: data.adjustments.length },
      ]} />

      {tab === 'items' && (
        <Card noPadding>
          <DataTable rowKey={(r) => r.id} data={data.items} emptyMessage="Tagihan lama tanpa rincian (nominal tunggal)." columns={[
            { header: 'Jenis', cell: (r) => r.line_type_label },
            { header: 'Uraian', cell: (r) => <span className="whitespace-normal">{r.description}</span> },
            { header: 'Qty', cell: (r) => Number(r.quantity) },
            { header: 'Satuan', cell: (r) => money(r.unit_amount) },
            { header: 'Jumlah', cell: (r) => <span className={Number(r.amount) < 0 ? 'text-primary' : undefined}>{money(r.amount)}</span> },
          ]} />
          {data.penalty_invoices.length > 0 && (
            <div className="border-t border-border p-4 text-sm">Tagihan denda terkait: {data.penalty_invoices.map((p) => <Link key={p.id} className="mr-2 text-primary" to={to(KEU.admin.invoice, { id: p.id })}>{p.invoice_number} ({money(p.amount)})</Link>)}</div>
          )}
        </Card>
      )}
      {tab === 'payments' && (
        <Card noPadding>
          <DataTable rowKey={(r) => r.id} data={data.payments} emptyMessage="Belum ada pembayaran." columns={[
            { header: 'Tanggal', cell: (r) => date(r.paid_at) },
            { header: 'Jenis', cell: (r) => r.allocation_type === 'credit_out' ? 'Kelebihan ke deposit' : r.allocation_type === 'deposit_usage' ? 'Dari deposit' : 'Pembayaran' },
            { header: 'Metode', cell: (r) => r.method ?? '—' },
            { header: 'Referensi', cell: (r) => r.payment_transaction_id ? <Link className="text-primary" to={to(KEU.admin.transaction, { id: r.payment_transaction_id })}>{r.reference}</Link> : r.reference ?? '—' },
            { header: 'Nominal', cell: (r) => money(r.amount) },
            { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status === 'reversed' ? `Dibalik: ${r.reversal_reason ?? ''}` : 'Tercatat'} /> },
          ]} />
        </Card>
      )}
      {tab === 'installments' && (
        <Card noPadding actions={data.installments.length > 0 ? <Can permission={P.INST_APPROVE}><Button size="sm" variant="outline" onClick={() => setDialog('cancelSchedule')}>Batalkan jadwal</Button></Can> : undefined} title="Jadwal cicilan aktif">
          <DataTable rowKey={(r) => r.id} data={data.installments} emptyMessage="Tagihan ini tidak dicicil." columns={[
            { header: 'Termin', cell: (r) => `Ke-${r.seq}` },
            { header: 'Jatuh tempo', cell: (r) => date(r.due_date) },
            { header: 'Nominal', cell: (r) => money(r.amount) },
            { header: 'Dibayar', cell: (r) => money(r.paid_amount) },
            { header: 'Sisa', cell: (r) => money(r.remaining) },
            { header: 'Status', cell: (r) => <KeuBadge status={r.is_overdue ? 'unpaid' : r.status} label={r.status_label} /> },
          ]} />
        </Card>
      )}
      {tab === 'adjustments' && <AdjustmentTable rows={data.adjustments} onChanged={reload} />}

      <FormDialog open={dialog === 'adjust'} title="Ajukan penyesuaian tagihan" description="Penyesuaian berlaku setelah disetujui petugas lain. Penurunan atas tagihan yang sudah dibayar memindahkan kelebihan ke deposit mahasiswa." onClose={() => setDialog(null)}
        fields={[
          { name: 'type', label: 'Jenis', type: 'select', required: true, options: (lookups?.enums.adjustment_type ?? []).filter((o) => ['increase', 'decrease', 'waiver', 'penalty_waiver', 'void'].includes(o.value)) },
          { name: 'amount', label: 'Nominal (Rp)', type: 'number', visible: (v) => v.type !== 'void', hint: 'Untuk pembatalan (void) nominal = seluruh tagihan.' },
          { name: 'reason_code', label: 'Kode alasan', required: true, placeholder: 'mis. KOREKSI_TARIF' },
          { name: 'reason_note', label: 'Penjelasan', type: 'textarea', required: true },
        ]}
        onSubmit={(v) => run(() => keuAdmin.post(`invoices/${id}/adjustments`, v))} />
      <InstallmentRequestDialog open={dialog === 'installment'} invoice={data} onClose={() => setDialog(null)} onDone={(m) => { reload(); action.run(async () => m, (x) => x) }} />
      <FormDialog open={dialog === 'voucher'} title="Pakai voucher untuk mahasiswa" onClose={() => setDialog(null)} fields={[{ name: 'code', label: 'Kode voucher', required: true }]}
        onSubmit={(v) => run(() => keuAdmin.post(`invoices/${id}/voucher`, v))} />
      <FormDialog open={dialog === 'deposit'} title="Bayar dari saldo deposit" description="Kosongkan nominal untuk memakai saldo sebanyak mungkin (maksimal sisa tagihan)." onClose={() => setDialog(null)}
        fields={[
          { name: 'amount', label: 'Nominal (Rp)', type: 'number' },
          { name: 'installment_id', label: 'Termin (opsional)', type: 'select', options: data.installments.filter((i) => Number(i.remaining) > 0).map((i) => ({ value: i.id, label: `Termin ${i.seq} — sisa ${money(i.remaining)}` })) },
        ]}
        onSubmit={(v) => run(() => keuAdmin.post('deposits/apply', { ...v, invoice_id: id }))} />
      <ReasonDialog open={dialog === 'cancelSchedule'} title="Batalkan jadwal cicilan" description="Tagihan kembali ke jatuh tempo semula; pembayaran termin yang sudah masuk tetap tercatat." onClose={() => setDialog(null)}
        onConfirm={(v) => run(() => keuAdmin.post(`invoices/${id}/installments/cancel`, v))} />
    </KeuPage>
  )
}

function InstallmentRequestDialog({ open, invoice, onClose, onDone }: { open: boolean; invoice: InvoiceDetail; onClose: () => void; onDone: (message: string) => void }) {
  const lookups = useKeuLookups()
  const plans = (lookups?.installment_plans ?? []).filter((p) => p.is_active && p.bill_groups.includes(invoice.bill_group))
  const hasSchedule = invoice.installments.length > 0

  return (
    <FormDialog open={open} title={hasSchedule ? 'Penjadwalan ulang cicilan' : 'Pengajuan cicilan'} onClose={onClose}
      description="Skema standar dengan persetujuan otomatis langsung membuat jadwal. Termin khusus (persentase per termin, total 100%) perlu persetujuan petugas lain."
      fields={[
        { name: 'installment_plan_id', label: 'Skema', type: 'select', options: plans.map((p) => ({ value: p.id, label: `${p.name} (${p.terms.length} termin)` })), visible: () => !hasSchedule },
        { name: 'terms', label: 'Termin khusus (JSON)', type: 'json', hint: 'Contoh: [{"percent":50,"due_date":"2026-11-30"},{"percent":50,"due_date":"2026-12-30"}]. Kosongkan bila memakai skema.' },
        { name: 'reason', label: 'Alasan', type: 'textarea' },
      ]}
      onSubmit={async (v) => {
        const { message } = await keuAdmin.post(`invoices/${invoice.id}/installments`, { ...v, kind: hasSchedule ? 'reschedule' : 'new' })
        onDone(message)
      }} />
  )
}

/** Tabel penyesuaian + aksi setuju/tolak (pengaju ≠ penyetuju ditegakkan server). */
export function AdjustmentTable({ rows, onChanged }: { rows: Adjustment[]; onChanged: () => void }) {
  const action = useAction()
  const [rejecting, setRejecting] = useState<Adjustment | null>(null)
  const canApprove = usePermission(P.ADJ_APPROVE)
  const canCreate = usePermission(P.ADJ_CREATE)
  const post = (path: string) => action.run(() => keuAdmin.post(path), (r) => r.message).then((r) => r && onChanged())

  return (
    <Card noPadding>
      <div className="p-3"><Feedback state={action} /></div>
      <DataTable rowKey={(r) => r.id} data={rows} emptyMessage="Belum ada penyesuaian." columns={adjustmentColumns(canApprove, (a) => post(`adjustments/${a.id}/approve`), setRejecting, canCreate ? (a) => post(`adjustments/${a.id}/cancel`) : undefined)} />
      <ReasonDialog open={rejecting !== null} title="Tolak penyesuaian" reasonName="note" onClose={() => setRejecting(null)}
        onConfirm={(v) => action.run(() => keuAdmin.post(`adjustments/${rejecting?.id}/reject`, v), (r) => r.message).then((r) => r && onChanged())} />
    </Card>
  )
}

function adjustmentColumns(canApprove: boolean, approve: (a: Adjustment) => void, reject: (a: Adjustment) => void, cancel?: (a: Adjustment) => void) {
  return [
    { header: 'Nomor', cell: (r: Adjustment) => <span>{r.adjustment_number}<span className="block text-xs text-ink-tertiary">{dateTime(r.created_at)}</span></span> },
    { header: 'Tagihan', cell: (r: Adjustment) => <Link className="text-primary" to={to(KEU.admin.invoice, { id: r.invoice_id })}>{r.invoice_number ?? 'Lihat'}<span className="block text-xs text-ink-tertiary">{r.student?.nim} {r.student?.name}</span></Link> },
    { header: 'Jenis', cell: (r: Adjustment) => r.type_label },
    { header: 'Nominal', cell: (r: Adjustment) => money(r.amount) },
    { header: 'Alasan', cell: (r: Adjustment) => <span className="whitespace-normal text-xs">{r.reason_code}: {r.reason_note}</span> },
    { header: 'Status', cell: (r: Adjustment) => <span><KeuBadge status={r.status} label={r.status_label} /><span className="block text-xs text-ink-tertiary">oleh {r.requested_by ?? 'sistem'}{r.decided_by ? ` · diputus ${r.decided_by}` : ''}</span></span> },
    { header: 'Aksi', cell: (r: Adjustment) => r.status === 'pending_approval' ? (
      <div className="flex gap-1">
        {canApprove && <><Button size="sm" onClick={() => approve(r)}>Setujui</Button><Button size="sm" variant="outline" onClick={() => reject(r)}>Tolak</Button></>}
        {/* Hanya pengaju yang dapat membatalkan — ditegakkan server. */}
        {cancel && <Button size="sm" variant="ghost" onClick={() => cancel(r)}>Batalkan</Button>}
      </div>
    ) : null },
  ]
}

/** Antrean penyesuaian tagihan (maker-checker). */
export function AdjustmentsPage() {
  const [reload, setReload] = useState(0)
  const action = useAction()
  const [rejecting, setRejecting] = useState<Adjustment | null>(null)
  const canApprove = usePermission(P.ADJ_APPROVE)
  const canCreate = usePermission(P.ADJ_CREATE)
  const done = (r: unknown) => r && setReload((x) => x + 1)

  return (
    <KeuPage title="Penyesuaian Tagihan" description="Kenaikan, penurunan, pembebasan, dan pembatalan tagihan terbit. Setiap penyesuaian wajib beralasan dan disetujui petugas lain." trail={[{ label: 'Penyesuaian' }]}>
      <Feedback state={action} />
      <KeuList<Adjustment> path="adjustments" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="penyesuaian" baseParams={{}}
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [
          { value: 'pending_approval', label: 'Menunggu persetujuan' }, { value: 'applied', label: 'Diterapkan' }, { value: 'rejected', label: 'Ditolak' }, { value: 'cancelled', label: 'Dibatalkan' },
        ] }]}
        columns={adjustmentColumns(canApprove, (a) => action.run(() => keuAdmin.post(`adjustments/${a.id}/approve`), (r) => r.message).then(done), setRejecting,
          canCreate ? (a) => action.run(() => keuAdmin.post(`adjustments/${a.id}/cancel`), (r) => r.message).then(done) : undefined)} />
      <ReasonDialog open={rejecting !== null} title="Tolak penyesuaian" reasonName="note" onClose={() => setRejecting(null)}
        onConfirm={(v) => action.run(() => keuAdmin.post(`adjustments/${rejecting?.id}/reject`, v), (r) => r.message).then(done)} />
    </KeuPage>
  )
}

interface Run {
  id: string; run_number: string; academic_term: string | null; bill_group: string; bill_group_label: string; due_date: string; status: string; status_label: string
  totals: { students: number; ready: number; skipped: number; gross: string; discount: string; net: string; skip_reasons: Record<string, number> } | null
  posted_totals: { invoices: number; skipped: number; failed: number; net: string } | null; rejection_reason: string | null
  created_by: string | null; created_by_id: string | null; approved_by: string | null; created_at: string | null; posted_at: string | null
}

const ITEM_LABELS: Record<string, string> = { ready: 'Siap terbit', skipped: 'Dilewati', posted: 'Terbit', failed: 'Gagal' }

const SKIP_LABELS: Record<string, string> = {
  NO_RATE_TABLE: 'Belum ada tabel tarif berlaku', NO_RATE: 'Tarif tidak ditemukan', ALREADY_BILLED: 'Sudah ditagihkan', UKT_MISSING: 'Golongan UKT belum ditetapkan',
  ZERO_AMOUNT: 'Nominal nol', INELIGIBLE_STATUS: 'Status mahasiswa tidak eligible',
}

/** Penerbitan tagihan massal: simulasi → ajukan → setujui (petugas lain) → terbit. */
export function BillingRunsPage() {
  const lookups = useKeuLookups()
  const navigate = useNavigate()
  const [creating, setCreating] = useState(false)

  return (
    <KeuPage title="Penerbitan Tagihan Massal" description="Hitung tagihan semester dari tabel tarif untuk banyak mahasiswa sekaligus. Hasil simulasi wajib diperiksa dan disetujui petugas lain sebelum terbit." trail={[{ label: 'Penerbitan' }]}
      actions={<Can permission={P.RUNS_CREATE}><Button leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Simulasi baru</Button></Can>}>
      <KeuList<Run> path="billing-runs" search={false} rowKey={(r) => r.id} itemLabel="penerbitan"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [
          { value: 'simulated', label: 'Simulasi' }, { value: 'pending_approval', label: 'Menunggu persetujuan' }, { value: 'posted', label: 'Diterbitkan' }, { value: 'rejected', label: 'Ditolak' }, { value: 'cancelled', label: 'Dibatalkan' },
        ] }]}
        columns={[
          { header: 'Nomor', cell: (r) => <Link className="text-primary" to={to(KEU.admin.billingRun, { id: r.id })}>{r.run_number}<span className="block text-xs text-ink-tertiary">{dateTime(r.created_at)}</span></Link> },
          { header: 'Semester', cell: (r) => <span>{r.academic_term}<span className="block text-xs text-ink-tertiary">{r.bill_group_label}</span></span> },
          { header: 'Mahasiswa', cell: (r) => r.totals ? `${r.totals.ready} siap · ${r.totals.skipped} dilewati` : '—' },
          { header: 'Neto', cell: (r) => money(r.posted_totals?.net ?? r.totals?.net) },
          { header: 'Jatuh tempo', cell: (r) => date(r.due_date) },
          { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status_label} /> },
        ]} />
      <FormDialog open={creating} title="Simulasi penerbitan tagihan" wide onClose={() => setCreating(false)}
        description="Simulasi tidak membuat tagihan. Mahasiswa yang sudah ditagih komponen yang sama pada semester ini otomatis dilewati (tidak ada tagihan ganda)."
        fields={[
          { name: 'academic_term_id', label: 'Semester', type: 'select', required: true, options: opts(lookups?.academic_terms, (t) => t.label) },
          { name: 'bill_group', label: 'Kelompok tagihan', type: 'select', required: true, options: (lookups?.enums.bill_group ?? []).filter((o) => ['registration', 'incidental', 'graduation'].includes(o.value)) },
          { name: 'fee_component_ids', label: 'Komponen biaya', type: 'multiselect', required: true, options: opts(lookups?.fee_components.filter((c) => c.is_active), (c) => `${c.code} — ${c.name}`) },
          { name: 'due_date', label: 'Jatuh tempo', type: 'date', required: true },
          { name: 'study_program_ids', label: 'Batasi program studi (opsional)', type: 'multiselect', options: opts(lookups?.study_programs, (p) => p.name) },
          { name: 'admission_years', label: 'Batasi angkatan (opsional, JSON)', type: 'json', hint: 'Contoh: [2024, 2025]' },
        ]}
        onSubmit={async (v) => {
          const { data } = await keuAdmin.post<Run>('billing-runs', { ...v, study_program_ids: (v.study_program_ids as string[] | undefined)?.length ? v.study_program_ids : null })
          navigate(to(KEU.admin.billingRun, { id: data.id }))
        }} />
    </KeuPage>
  )
}

interface RunItem { id: string; student: StudentRef | null; status: string; skip_reason: string | null; error_message: string | null; gross: string; discount: string; net: string; invoice_id: string | null; lines: Array<{ type: string; description: string; amount: string }> }

export function BillingRunDetailPage() {
  const { id = '' } = useParams()
  const [status, setStatus] = useState('')
  const { data, error, reload } = useLoad(() => keuAdmin.get<Run & { items: RunItem[] }>(`billing-runs/${id}`, { status }), [id, status])
  const action = useAction()
  const [rejecting, setRejecting] = useState(false)
  const run = (fn: () => Promise<{ message: string }>) => action.run(fn, (r) => r.message).then((r) => { if (r) reload() })

  if (error) return <KeuPage title="Penerbitan" trail={[{ label: 'Penerbitan', path: KEU.admin.billingRuns }]}><Alert variant="danger">{describeError(error)}</Alert></KeuPage>
  if (!data) return <p className="text-sm text-ink-secondary">Memuat...</p>
  const t = data.totals

  return (
    <KeuPage title={`Penerbitan ${data.run_number}`} description={`${data.academic_term ?? ''} · ${data.bill_group_label} · jatuh tempo ${date(data.due_date)}`} trail={[{ label: 'Penerbitan', path: KEU.admin.billingRuns }, { label: data.run_number }]}
      actions={<div className="flex gap-2">
        {data.status === 'simulated' && <Can permission={P.RUNS_CREATE}><Button onClick={() => run(() => keuAdmin.post(`billing-runs/${id}/submit`))}>Ajukan persetujuan</Button></Can>}
        {data.status === 'pending_approval' && <Can permission={P.RUNS_APPROVE}><Button onClick={() => run(() => keuAdmin.post(`billing-runs/${id}/approve`))}>Setujui & terbitkan</Button><Button variant="outline" onClick={() => setRejecting(true)}>Tolak</Button></Can>}
        {['simulated', 'pending_approval'].includes(data.status) && <Can permission={P.RUNS_CREATE}><Button variant="ghost" onClick={() => run(() => keuAdmin.post(`billing-runs/${id}/cancel`))}>Batalkan</Button></Can>}
      </div>}>
      <Feedback state={action} />
      <Card>
        <div className="grid gap-4 sm:grid-cols-6">
          <Figure label="Status" value={<KeuBadge status={data.status} label={data.status_label} />} />
          <Figure label="Mahasiswa dihitung" value={t?.students ?? 0} />
          <Figure label="Siap terbit" value={t?.ready ?? 0} tone="success" />
          <Figure label="Dilewati" value={t?.skipped ?? 0} tone={t?.skipped ? 'warning' : undefined} />
          <Figure label="Total bruto" value={money(t?.gross)} />
          <Figure label="Total neto" value={money(t?.net)} />
        </div>
        {t && Object.keys(t.skip_reasons ?? {}).length > 0 && (
          <p className="mt-3 text-sm text-ink-secondary">Alasan dilewati: {Object.entries(t.skip_reasons).map(([k, n]) => `${SKIP_LABELS[k] ?? k} (${n})`).join(', ')}</p>
        )}
        {data.posted_totals && <Alert variant="success" className="mt-3">Terbit {data.posted_totals.invoices} tagihan senilai {money(data.posted_totals.net)} · dilewati saat posting {data.posted_totals.skipped} · gagal {data.posted_totals.failed}. Disetujui oleh {data.approved_by}.</Alert>}
        {data.rejection_reason && <Alert variant="danger" className="mt-3">Ditolak: {data.rejection_reason}</Alert>}
        <p className="mt-3 text-xs text-ink-tertiary">Dibuat oleh {data.created_by} pada {dateTime(data.created_at)}. Pembuat tidak dapat menyetujui penerbitannya sendiri.</p>
      </Card>
      <Card noPadding title="Rincian per mahasiswa (maks. 1.000 baris)" actions={<Select options={[{ value: 'ready', label: 'Siap' }, { value: 'skipped', label: 'Dilewati' }, { value: 'posted', label: 'Terbit' }, { value: 'failed', label: 'Gagal' }]} placeholder="Semua" value={status} onChange={(e) => setStatus(e.target.value)} />}>
        <DataTable rowKey={(r) => r.id} data={data.items} emptyMessage="Tidak ada baris." columns={[
          { header: 'Mahasiswa', cell: (r) => <span>{r.student?.nim}<span className="block text-xs text-ink-tertiary">{r.student?.name}</span></span> },
          { header: 'Rincian', cell: (r) => <span className="whitespace-normal text-xs">{r.lines?.map((l) => `${l.description}: ${money(l.amount)}`).join('; ') || '—'}</span> },
          { header: 'Neto', cell: (r) => money(r.net) },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={ITEM_LABELS[r.status] ?? r.status} />{(r.skip_reason || r.error_message) && <span className="block text-xs text-ink-tertiary">{SKIP_LABELS[r.skip_reason ?? ''] ?? r.error_message}</span>}</span> },
          { header: 'Tagihan', cell: (r) => r.invoice_id ? <Link className="text-primary" to={to(KEU.admin.invoice, { id: r.invoice_id })}>Lihat</Link> : '—' },
        ]} />
      </Card>
      <ReasonDialog open={rejecting} title="Tolak penerbitan" onClose={() => setRejecting(false)} onConfirm={(v) => run(() => keuAdmin.post(`billing-runs/${id}/reject`, v))} />
    </KeuPage>
  )
}
