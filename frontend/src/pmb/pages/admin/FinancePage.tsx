import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Banknote, FileWarning, Receipt, RotateCcw, Wallet } from 'lucide-react'
import { pmbAdmin } from '@/pmb/api/admin'
import { adminClient } from '@/pmb/api/http'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminList } from '@/pmb/components/AdminList'
import { FormDialog, toFormData } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { Tabs } from '@/pmb/components/Tabs'
import { KeyValue } from '@/pmb/components/KeyValue'
import { AdminPage, Can, P, WavePathPicker, options, useLookups } from '@/pmb/components/admin'
import { usePermission } from '@/hooks/usePermission'
import { StatCard } from '@/components/ui/StatCard'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { DataTable } from '@/components/ui/DataTable'
import type { Invoice, Transaction } from '@/pmb/api/types'
import { date, dateTime, money } from '@/pmb/lib/format'
import { statusLabel } from '@/pmb/lib/labels'
import { PMB, to } from '@/pmb/routes'

type FTab = 'invoices' | 'transactions' | 'proofs' | 'reconciliation' | 'refunds'
type InvoiceRow = Invoice & { application: { id: string; registration_number: string | null; name: string | null; email: string | null }; resolution_note: string | null }
type InvoiceDetail = InvoiceRow & { refunds: RefundRow[]; transactions: Array<Transaction & { idempotency_key?: string }> }
interface RefundRow { id: string; status: string; amount: string; reason_code: string; reason_note: string; beneficiary_bank: string | null; beneficiary_account_masked: string | null; beneficiary_name: string | null; transaction_id: string; invoice_number: string | null; applicant: string | null; requested_by: string | null; approval_request_id: string | null; completed_at: string | null; created_at: string | null }

const REFUND_CODES = ['DUPLICATE_PAYMENT', 'PERIOD_CANCELLED', 'WITHDRAWAL', 'LATE_PAYMENT', 'OVERPAYMENT', 'POLICY', 'OTHER']
const PROOF_REJECT = ['NOT_FOUND_IN_STATEMENT', 'AMOUNT_MISMATCH', 'UNREADABLE', 'DUPLICATE_PROOF', 'OTHER']

/** ADM-03/40..45 Keuangan PMB. */
export function FinancePage() {
  const lookups = useLookups()
  const [period, setPeriod] = useState('')
  const stats = useLoad(() => pmbAdmin.get<{ paid_registration: number; unpaid_registration: number; revenue_registration: string; revenue_initial_tuition: string; today_transactions: number; today_amount: string; pending_manual_proofs: number; needs_resolution: number; refunds_pending: number }>('finance/stats', { period_id: period }), [period])
  const canVerify = usePermission(P.PAYMENTS_VERIFY)
  const canReconcile = usePermission(P.PAYMENTS_RECONCILE)
  const canRefund = usePermission(P.REFUNDS_REQUEST)
  const [tab, setTab] = useState<FTab>('invoices')
  const s = stats.data

  return (
    <AdminPage title="Keuangan PMB" trail={[{ label: 'Keuangan' }]} actions={<Select options={options(lookups?.periods, (p) => p.name)} placeholder="Semua periode" value={period} onChange={(e) => setPeriod(e.target.value)} />}>
      {s && (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
          <StatCard label="Pendapatan pendaftaran" value={money(s.revenue_registration)} icon={Wallet} caption={`${s.paid_registration} lunas · ${s.unpaid_registration} belum`} />
          <StatCard label="Pendapatan biaya awal" value={money(s.revenue_initial_tuition)} icon={Banknote} />
          <StatCard label="Hari ini" value={money(s.today_amount)} icon={Receipt} caption={`${s.today_transactions} transaksi`} />
          <StatCard label="Bukti manual menunggu" value={String(s.pending_manual_proofs)} icon={FileWarning} caption={`${s.needs_resolution} perlu resolusi`} />
          <StatCard label="Refund berjalan" value={String(s.refunds_pending)} icon={RotateCcw} />
        </div>
      )}
      <Tabs<FTab> active={tab} onChange={setTab} tabs={[
        { key: 'invoices', label: 'Tagihan' }, { key: 'transactions', label: 'Transaksi' },
        ...(canVerify ? [{ key: 'proofs' as FTab, label: 'Bukti manual', badge: s?.pending_manual_proofs }] : []),
        ...(canReconcile ? [{ key: 'reconciliation' as FTab, label: 'Rekonsiliasi' }] : []),
        ...(canRefund ? [{ key: 'refunds' as FTab, label: 'Refund' }] : []),
      ]} />
      {tab === 'invoices' && <InvoicesTab period={period} onChanged={stats.reload} />}
      {tab === 'transactions' && <TransactionsTab />}
      {tab === 'proofs' && <ProofsTab onChanged={stats.reload} />}
      {tab === 'reconciliation' && <ReconciliationTab />}
      {tab === 'refunds' && <RefundsTab />}
    </AdminPage>
  )
}

function InvoicesTab({ period, onChanged }: { period: string; onChanged: () => void }) {
  const [reload, setReload] = useState(0)
  const [detailId, setDetailId] = useState<string | null>(null)

  return (
    <>
      <AdminList<InvoiceRow> path="invoices" reloadKey={reload} baseParams={{ period_id: period }} rowKey={(r) => r.id} itemLabel="tagihan"
        filters={[
          { name: 'type', label: 'Jenis', type: 'select', options: [{ value: 'registration_fee', label: 'Biaya pendaftaran' }, { value: 'initial_tuition', label: 'Biaya awal' }] },
          { name: 'status', label: 'Status', type: 'select', options: ['unpaid', 'pending', 'partially_paid', 'paid', 'expired', 'cancelled', 'refund_pending', 'refunded'].map((v) => ({ value: v, label: statusLabel(v) })) },
          { name: 'needs_resolution', label: 'Perlu resolusi', type: 'select', options: [{ value: '1', label: 'Ya' }] },
        ]}
        columns={[
          { header: 'Tagihan', cell: (r) => <button type="button" className="text-left text-primary" onClick={() => setDetailId(r.id)}>{r.invoice_number}<span className="block text-xs text-ink-tertiary">{statusLabel(r.type)}</span></button> },
          { header: 'Pendaftar', cell: (r) => <Link className="text-primary" to={to(PMB.admin.application, { id: r.application.id })}>{r.application.name}<span className="block text-xs text-ink-tertiary">{r.application.registration_number}</span></Link> },
          { header: 'Nominal', cell: (r) => money(r.amount_net) }, { header: 'Dibayar', cell: (r) => money(r.amount_paid) },
          { header: 'Tenggat', cell: (r) => dateTime(r.due_at) },
          { header: 'Status', cell: (r) => <span className="flex gap-1"><StatusBadge status={r.status} />{r.needs_resolution && <Badge variant="danger">Resolusi</Badge>}</span> },
        ]} />
      {detailId && <InvoiceDetailModal id={detailId} onClose={() => { setDetailId(null); setReload(reload + 1); onChanged() }} />}
    </>
  )
}

function InvoiceDetailModal({ id, onClose }: { id: string; onClose: () => void }) {
  const inv = useLoad(() => pmbAdmin.get<InvoiceDetail>(`invoices/${id}`), [id])
  const action = useAction()
  const [dialog, setDialog] = useState<null | 'cashier' | 'resolve'>(null)
  const i = inv.data

  return (
    <Modal open onClose={onClose} title={i ? `Tagihan ${i.invoice_number}` : 'Tagihan'} className="max-w-3xl">
      <Feedback state={action} />
      {i && (
        <div className="flex flex-col gap-4">
          <KeyValue columns={3} items={[['Pendaftar', i.application.name], ['Jenis', statusLabel(i.type)], ['Status', statusLabel(i.status)], ['Total', money(i.amount_net)], ['Dibayar', money(i.amount_paid)], ['Sisa', money(i.outstanding)], ['Tenggat', dateTime(i.due_at)], ['Lunas', dateTime(i.paid_at)], ['Catatan resolusi', i.resolution_note]]} />
          <DataTable rowKey={(r) => r.description + r.amount} data={i.items} columns={[{ header: 'Item', cell: (r) => r.description }, { header: 'Nominal', cell: (r) => money(r.amount) }, { header: 'Jatuh tempo', cell: (r) => date(r.due_at) }]} />
          <DataTable rowKey={(t) => t.id} data={i.transactions} emptyMessage="Belum ada transaksi." columns={[
            { header: 'Kanal', cell: (t) => `${t.channel} (${t.provider})` }, { header: 'Nominal', cell: (t) => money(t.amount) }, { header: 'Ref', cell: (t) => <span className="font-mono text-xs">{t.provider_reference ?? '—'}</span> },
            { header: 'Status', cell: (t) => <StatusBadge status={t.status} /> }, { header: 'Dibayar', cell: (t) => dateTime(t.paid_at) },
            { header: '', cell: (t) => <Can permission={P.PAYMENTS_RECONCILE}>{t.status === 'pending' && t.provider !== 'manual' && t.provider !== 'cashier' && <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post(`transactions/${t.id}/recheck`), 'Status diperbarui dari penyedia.').then(inv.reload)}>Cek ulang</Button>}</Can> },
          ]} />
          {i.refunds.length > 0 && <p className="text-sm">Refund: {i.refunds.map((r) => `${money(r.amount)} (${statusLabel(r.status)})`).join(', ')}</p>}
          <div className="flex flex-wrap gap-2">
            <Can permission={P.PAYMENTS_VERIFY}>{['unpaid', 'partially_paid', 'expired'].includes(i.status) && <Button size="sm" onClick={() => setDialog('cashier')}>Catat pembayaran loket</Button>}</Can>
            <Can permission={P.PAYMENTS_RECONCILE}>{i.needs_resolution && <Button size="sm" variant="outline" onClick={() => setDialog('resolve')}>Resolusi pembayaran terlambat</Button>}</Can>
          </div>
        </div>
      )}
      <FormDialog open={dialog === 'cashier'} title="Pembayaran loket" fields={[{ name: 'amount', label: 'Nominal diterima (Rp)', type: 'number', required: true }, { name: 'receipt_number', label: 'Nomor kuitansi', required: true }, { name: 'paid_at', label: 'Waktu bayar', type: 'datetime', required: true }]} initial={{ amount: i?.outstanding, paid_at: new Date().toISOString() }} onClose={() => setDialog(null)} onSubmit={async (v) => { await pmbAdmin.post(`invoices/${id}/cashier-payments`, v, { idempotent: true }); inv.reload() }} />
      <ReasonDialog open={dialog === 'resolve'} title="Resolusi pembayaran terlambat (BR-18)" reasonName="note" reasonLabel="Catatan" extraFields={[{ name: 'resolution', label: 'Keputusan', type: 'select', required: true, options: [{ value: 'accept_and_reopen', label: 'Terima & buka kembali aplikasi' }, { value: 'refund', label: 'Kembalikan dana (refund)' }] }]} onClose={() => setDialog(null)} onConfirm={async (v) => { await pmbAdmin.post(`invoices/${id}/resolve`, v); inv.reload() }} />
    </Modal>
  )
}

function TransactionsTab() {
  const [callbacksFor, setCallbacksFor] = useState<string | null>(null)
  const callbacks = useLoad(() => (callbacksFor ? pmbAdmin.get<Array<{ id: string; provider_event_id: string; signature_valid: boolean; received_at: string; processed_at: string | null; processing_result: string | null; error_message: string | null; attempts: number }>>(`transactions/${callbacksFor}/callbacks`) : Promise.resolve([])), [callbacksFor])

  return (
    <>
      <AdminList<Transaction & { invoice: { id: string; number: string | null; type: string | null }; applicant: string | null; registration_number: string | null; recheck_attempts: number }> path="transactions" rowKey={(r) => r.id} itemLabel="transaksi"
        filters={[
          { name: 'status', label: 'Status', type: 'select', options: ['created', 'pending', 'success', 'failed', 'expired', 'cancelled', 'refunded'].map((v) => ({ value: v, label: statusLabel(v) })) },
          { name: 'provider', label: 'Penyedia', type: 'select', options: ['manual', 'cashier', 'midtrans'].map((v) => ({ value: v, label: v })) },
          { name: 'date_from', label: 'Dari', type: 'date' }, { name: 'date_to', label: 'Sampai', type: 'date' },
        ]}
        columns={[
          { header: 'Transaksi', cell: (r) => <span className="font-mono text-xs">{r.provider_reference ?? r.id}</span> },
          { header: 'Tagihan', cell: (r) => <span>{r.invoice.number}<span className="block text-xs text-ink-tertiary">{r.applicant}</span></span> },
          { header: 'Kanal', cell: (r) => `${r.channel} · ${r.provider}` }, { header: 'Nominal', cell: (r) => money(r.amount) },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> }, { header: 'Dibuat', cell: (r) => dateTime(r.created_at) }, { header: 'Dibayar', cell: (r) => dateTime(r.paid_at) },
          { header: '', cell: (r) => r.provider !== 'manual' && r.provider !== 'cashier' && <Button size="sm" variant="ghost" onClick={() => setCallbacksFor(r.id)}>Callback</Button> },
        ]} />
      <Modal open={callbacksFor !== null} onClose={() => setCallbacksFor(null)} title="Callback penyedia" className="max-w-3xl">
        <DataTable rowKey={(c) => c.id} data={callbacks.data ?? []} emptyMessage="Belum ada callback." columns={[
          { header: 'Diterima', cell: (c) => dateTime(c.received_at) }, { header: 'Tanda tangan', cell: (c) => (c.signature_valid ? 'Valid' : <Badge variant="danger">Tidak valid</Badge>) },
          { header: 'Hasil', cell: (c) => c.processing_result ?? 'menunggu' }, { header: 'Percobaan', cell: (c) => c.attempts }, { header: 'Galat', cell: (c) => c.error_message ?? '—' },
        ]} />
      </Modal>
    </>
  )
}

function ProofsTab({ onChanged }: { onChanged: () => void }) {
  const [reload, setReload] = useState(0)
  const [deciding, setDeciding] = useState<{ id: string; expected_amount: string; transferred_amount: string } | null>(null)
  const action = useAction()

  return (
    <>
      <Feedback state={action} />
      <AdminList<{ id: string; status: string; sender_bank: string; sender_name: string; transfer_date: string; transferred_amount: string; expected_amount: string; unique_code: number | null; duplicate_warning: boolean; invoice_number: string | null; registration_number: string | null; applicant: string | null; reason_code: string | null; reviewer: string | null; created_at: string | null }>
        path="manual-proofs" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="bukti"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'submitted', label: 'Menunggu' }, { value: 'approved', label: 'Disetujui' }, { value: 'rejected', label: 'Ditolak' }] }]}
        columns={[
          { header: 'Pendaftar', cell: (r) => <span>{r.applicant}<span className="block text-xs text-ink-tertiary">{r.registration_number} · {r.invoice_number}</span></span> },
          { header: 'Pengirim', cell: (r) => `${r.sender_name} (${r.sender_bank})` }, { header: 'Tgl transfer', cell: (r) => date(r.transfer_date) },
          { header: 'Nominal', cell: (r) => <span className={r.transferred_amount !== r.expected_amount ? 'text-danger' : undefined}>{money(r.transferred_amount)}<span className="block text-xs text-ink-tertiary">tagihan {money(r.expected_amount)}{r.unique_code ? ` · kode ${r.unique_code}` : ''}</span></span> },
          { header: 'Status', cell: (r) => <span className="flex gap-1"><StatusBadge status={r.status} />{r.duplicate_warning && <Badge variant="danger">Mirip bukti lain</Badge>}</span> },
          { header: '', cell: (r) => (
            <div className="flex gap-1">
              <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.open(`manual-proofs/${r.id}/file`))}>Lihat bukti</Button>
              {r.status === 'submitted' && <Button size="sm" onClick={() => setDeciding(r)}>Putuskan</Button>}
            </div>
          ) },
        ]} />
      <FormDialog open={deciding !== null} title="Verifikasi bukti transfer" description={deciding ? `Cocokkan dengan mutasi rekening: ${money(deciding.transferred_amount)} (tagihan ${money(deciding.expected_amount)}).` : undefined} fields={[
        { name: 'approve', label: 'Keputusan', type: 'select', required: true, options: [{ value: '1', label: 'Setujui — dana diterima' }, { value: '0', label: 'Tolak' }] },
        { name: 'reason_code', label: 'Alasan penolakan', type: 'select', options: PROOF_REJECT.map((c) => ({ value: c, label: c })), visible: (v) => v.approve === '0' },
        { name: 'note', label: 'Catatan', type: 'textarea' },
      ]} onClose={() => setDeciding(null)} onSubmit={async (v) => {
        if (deciding) await pmbAdmin.post(`manual-proofs/${deciding.id}/decision`, { ...v, approve: v.approve === '1' }, { idempotent: true })
        setReload(reload + 1)
        onChanged()
      }} />
    </>
  )
}

function ReconciliationTab() {
  const [reload, setReload] = useState(0)
  const [creating, setCreating] = useState(false)
  const [batchId, setBatchId] = useState<string | null>(null)

  return (
    <>
      <AdminList<{ id: string; provider: string; statement_date: string; status: string; total_items: number; matched_items: number; closed_at: string | null }> path="reconciliation-batches" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="batch"
        toolbar={<Button size="sm" onClick={() => setCreating(true)}>Batch baru</Button>}
        columns={[
          { header: 'Tanggal mutasi', cell: (r) => <button type="button" className="text-primary" onClick={() => setBatchId(r.id)}>{date(r.statement_date)}</button> },
          { header: 'Penyedia', cell: (r) => r.provider }, { header: 'Cocok', cell: (r) => `${r.matched_items} / ${r.total_items}` },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
        ]} />
      <FormDialog open={creating} title="Batch rekonsiliasi" description="Gateway: settlement ditarik otomatis. Transfer manual: unggah mutasi bank setelah batch dibuat." fields={[{ name: 'provider', label: 'Penyedia', type: 'select', required: true, options: [{ value: 'manual', label: 'Transfer bank (mutasi)' }, { value: 'midtrans', label: 'Midtrans' }] }, { name: 'statement_date', label: 'Tanggal', type: 'date', required: true }]} onClose={() => setCreating(false)} onSubmit={async (v) => { const b = await pmbAdmin.post<{ id: string }>('reconciliation-batches', v); setReload(reload + 1); setBatchId(b.id) }} />
      {batchId && <BatchModal id={batchId} onClose={() => { setBatchId(null); setReload(reload + 1) }} />}
    </>
  )
}

function BatchModal({ id, onClose }: { id: string; onClose: () => void }) {
  const batch = useLoad(() => pmbAdmin.get<{ id: string; provider: string; statement_date: string; status: string; items: Array<{ id: string; external_reference: string | null; external_amount: string | null; external_date: string | null; external_description: string | null; result: string; resolution: string | null; resolution_note: string | null; transaction: { amount: string; status: string; provider_reference: string | null } | null }>; resolutions: string[] }>(`reconciliation-batches/${id}`), [id])
  const action = useAction()
  const [resolving, setResolving] = useState<string | null>(null)
  const b = batch.data

  const upload = async (file: File | undefined) => {
    if (!file) return
    const form = new FormData()
    form.append('file', file)
    await action.run(() => adminClient.post(`/pmb/admin/reconciliation-batches/${id}/import`, form), 'Mutasi diimpor & dicocokkan.')
    batch.reload()
  }

  return (
    <Modal open onClose={onClose} title={b ? `Rekonsiliasi ${b.provider} · ${date(b.statement_date)}` : 'Rekonsiliasi'} className="max-w-5xl">
      <Feedback state={action} />
      {b && (
        <>
          {b.status !== 'closed' && (
            <div className="mb-3 flex flex-wrap items-center gap-2">
              {b.provider === 'manual' && <label className="cursor-pointer rounded-lg border border-border-strong px-3 py-1.5 text-sm">Impor mutasi (CSV/XLSX: tanggal, keterangan, nominal, referensi)<input type="file" className="hidden" accept=".csv,.xlsx" onChange={(e) => upload(e.target.files?.[0])} /></label>}
              <Button size="sm" variant="outline" onClick={() => window.confirm('Tutup batch? Item terbuka harus sudah diselesaikan.') && action.run(() => pmbAdmin.post(`reconciliation-batches/${id}/close`), 'Batch ditutup.').then(batch.reload)}>Tutup batch</Button>
            </div>
          )}
          <DataTable rowKey={(r) => r.id} data={b.items} emptyMessage="Belum ada item." columns={[
            { header: 'Referensi', cell: (r) => <span className="font-mono text-xs">{r.external_reference ?? r.transaction?.provider_reference ?? '—'}</span> },
            { header: 'Eksternal', cell: (r) => `${money(r.external_amount)} · ${date(r.external_date)}` },
            { header: 'Sistem', cell: (r) => (r.transaction ? `${money(r.transaction.amount)} (${statusLabel(r.transaction.status)})` : '—') },
            { header: 'Hasil', cell: (r) => <Badge variant={r.result === 'matched' ? 'success' : 'warning'}>{r.result}</Badge> },
            { header: 'Resolusi', cell: (r) => r.resolution ?? (r.result !== 'matched' && b.status !== 'closed' ? <Button size="sm" variant="ghost" onClick={() => setResolving(r.id)}>Selesaikan</Button> : '—') },
          ]} />
        </>
      )}
      <ReasonDialog open={resolving !== null} title="Selesaikan item" reasonName="note" reasonLabel="Catatan" extraFields={[{ name: 'resolution', label: 'Resolusi', type: 'select', required: true, options: (b?.resolutions ?? []).map((r) => ({ value: r, label: r })) }]} onClose={() => setResolving(null)} onConfirm={async (v) => { if (resolving) await pmbAdmin.patch(`reconciliation-items/${resolving}`, v); batch.reload() }} />
    </Modal>
  )
}

function RefundsTab() {
  const [reload, setReload] = useState(0)
  const [requesting, setRequesting] = useState(false)
  const [completing, setCompleting] = useState<RefundRow | null>(null)
  const [submittingDraft, setSubmittingDraft] = useState<RefundRow | null>(null)
  const [bulk, setBulk] = useState(false)
  const [bulkTarget, setBulkTarget] = useState<{ period?: string; wave?: string; wavePath?: string }>({})
  const [bulkReason, setBulkReason] = useState('')
  const bulkAction = useAction()
  const refundFields = [
    { name: 'amount', label: 'Nominal (Rp)', type: 'number' as const, required: true },
    { name: 'reason_code', label: 'Alasan', type: 'select' as const, required: true, options: REFUND_CODES.map((c) => ({ value: c, label: c })) },
    { name: 'reason_note', label: 'Keterangan', type: 'textarea' as const, required: true },
    { name: 'beneficiary_bank', label: 'Bank penerima', required: true }, { name: 'beneficiary_account', label: 'Nomor rekening', required: true }, { name: 'beneficiary_name', label: 'Nama pemilik rekening', required: true },
  ]

  return (
    <>
      <Alert variant="info">Refund disetujui oleh pejabat keuangan lain lewat menu Persetujuan; pengaju tidak dapat menyetujui sendiri.</Alert>
      <AdminList<RefundRow> path="refunds" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="refund"
        toolbar={<><Button size="sm" onClick={() => setRequesting(true)}>Ajukan refund</Button><Button size="sm" variant="outline" onClick={() => setBulk(true)}>Refund massal (jalur dibatalkan)</Button></>}
        filters={[{ name: 'status', label: 'Status', type: 'select', options: ['draft', 'pending_approval', 'approved', 'processing', 'completed', 'rejected', 'failed'].map((v) => ({ value: v, label: statusLabel(v) })) }]}
        columns={[
          { header: 'Pendaftar', cell: (r) => <span>{r.applicant}<span className="block text-xs text-ink-tertiary">{r.invoice_number}</span></span> },
          { header: 'Nominal', cell: (r) => money(r.amount) }, { header: 'Alasan', cell: (r) => r.reason_code },
          { header: 'Rekening', cell: (r) => `${r.beneficiary_bank ?? ''} ${r.beneficiary_account_masked ?? ''}` },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
          { header: '', cell: (r) => (
            <div className="flex gap-1">
              {r.approval_request_id && <Link className="text-sm text-primary" to={`/persetujuan/${r.approval_request_id}`}>Persetujuan</Link>}
              {r.status === 'draft' && <Button size="sm" variant="ghost" onClick={() => setSubmittingDraft(r)}>Lengkapi & ajukan</Button>}
              {['approved', 'processing'].includes(r.status) && <Button size="sm" variant="ghost" onClick={() => setCompleting(r)}>Catat selesai</Button>}
            </div>
          ) },
        ]} />
      <FormDialog open={requesting} title="Ajukan refund" fields={[{ name: 'transaction_id', label: 'ID transaksi (sukses)', required: true, hint: 'Salin dari tab Transaksi atau detail tagihan.' }, ...refundFields]} onClose={() => setRequesting(false)} onSubmit={async (v) => { await pmbAdmin.post('refunds', v, { idempotent: true }); setReload(reload + 1) }} />
      <FormDialog open={submittingDraft !== null} title="Lengkapi refund" fields={refundFields} initial={submittingDraft ? { amount: submittingDraft.amount, reason_code: submittingDraft.reason_code, reason_note: submittingDraft.reason_note } : undefined} onClose={() => setSubmittingDraft(null)} onSubmit={async (v) => { if (submittingDraft) await pmbAdmin.post(`refunds/${submittingDraft.id}/submit`, v); setReload(reload + 1) }} />
      <Modal open={bulk} onClose={() => setBulk(false)} title="Refund massal gelombang-jalur" className="max-w-2xl">
        <div className="flex flex-col gap-3">
          <Feedback state={bulkAction} />
          <p className="text-sm text-ink-secondary">Dipakai bila kampus membatalkan jalur pada gelombang tertentu: seluruh pembayaran biaya pendaftaran sukses diajukan refund dalam satu persetujuan.</p>
          <WavePathPicker value={bulkTarget} onChange={setBulkTarget} />
          <Textarea label="Alasan" value={bulkReason} onChange={(e) => setBulkReason(e.target.value)} />
          <div className="flex justify-end"><Button disabled={!bulkTarget.wavePath || bulkReason.trim().length < 10} isLoading={bulkAction.busy} onClick={() => bulkAction.run(() => pmbAdmin.post(`wave-paths/${bulkTarget.wavePath}/bulk-refunds`, { reason: bulkReason }), 'Refund massal diajukan untuk persetujuan.').then(() => setReload(reload + 1))}>Ajukan</Button></div>
        </div>
      </Modal>
      <FormDialog open={completing !== null} title="Catat refund selesai" fields={[{ name: 'proof', label: 'Bukti transfer', type: 'file', accept: 'image/jpeg,image/png,application/pdf' }, { name: 'completed_at', label: 'Waktu transfer', type: 'datetime' }]} onClose={() => setCompleting(null)} onSubmit={async (v) => { if (completing) await adminClient.post(`/pmb/admin/refunds/${completing.id}/complete`, toFormData(v)); setReload(reload + 1) }} />
    </>
  )
}
