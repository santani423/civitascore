import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { QRCodeSVG } from 'qrcode.react'
import { AlertTriangle, CalendarClock, Landmark, Printer, ShieldAlert, Wallet } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { DataTable } from '@/components/ui/DataTable'
import { StatCard } from '@/components/ui/StatCard'
import { ListPagination } from '@/components/ui/ListPagination'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, describeError, useAction } from '@/pmb/components/useAction'
import { FormDialog } from '@/pmb/components/FormDialog'
import { Tabs } from '@/pmb/components/Tabs'
import { KeyValue } from '@/pmb/components/KeyValue'
import { date, dateTime, money } from '@/pmb/lib/format'
import { ROUTES } from '@/constants/routes'
import { idempotencyKey, keuStudent, uploadFile } from '@/keu/api'
import { Figure, KeuBadge } from '@/keu/components'
import { KEU, to } from '@/keu/routes'
import type { Award, Block, Clearance, Invoice, InvoiceDetail, Paginated, Refund, StatementEntry, StudentRef, Transaction } from '@/keu/types'

interface Summary {
  student: StudentRef
  outstanding: string
  arrears: string
  open_invoices: number
  next_due: { date: string; amount: string; invoice_id: string; installment_seq: number | null } | null
  deposit_available: string
  pending_verification: number
  services: Array<{ service: string; label: string; blocked: boolean }>
  clearance: Clearance
  blocks: Block[]
}

const SERVICES: Record<string, string> = { krs: 'Pengisian KRS', exam: 'Ujian', view_grades: 'Melihat nilai', transcript: 'Transkrip', yudisium: 'Yudisium', graduation: 'Wisuda' }
const REFUND_REASONS = [
  { value: 'OVERPAYMENT', label: 'Kelebihan bayar' }, { value: 'DUPLICATE_PAYMENT', label: 'Pembayaran ganda' }, { value: 'CANCELLED_INVOICE', label: 'Tagihan dibatalkan/dikoreksi' },
  { value: 'WITHDRAWAL', label: 'Mengundurkan diri' }, { value: 'LEAVE', label: 'Cuti akademik' }, { value: 'GRADUATED_BALANCE', label: 'Sisa saldo setelah lulus/keluar' }, { value: 'OTHER', label: 'Lainnya' },
]

function PortalPage({ title, description, trail = [], children, actions }: { title: string; description?: string; trail?: Array<{ label: string; path?: string }>; children: React.ReactNode; actions?: React.ReactNode }) {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader title={title} description={description} actions={actions} breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Keuangan', path: KEU.portal.home }, ...trail]} />
      {children}
    </div>
  )
}

type Tab = 'invoices' | 'transactions' | 'deposit' | 'statement' | 'ukt' | 'awards'

/** Keuangan Saya (F-16): ringkasan, tagihan, riwayat bayar, deposit/refund, UKT, beasiswa, kartu piutang. */
export function PortalFinancePage() {
  const { data, error, reload } = useLoad(() => keuStudent.get<Summary>('summary'))
  const [tab, setTab] = useState<Tab>('invoices')
  const [dispensing, setDispensing] = useState<Block | null>(null)
  const action = useAction()

  return (
    <PortalPage title="Keuangan" description="Tagihan, pembayaran, dan status kewajiban keuangan Anda.">
      <Feedback state={action} />
      {error && <Alert variant="danger">{describeError(error)}</Alert>}
      {data && (
        <>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Sisa tagihan" value={money(data.outstanding)} icon={Wallet} caption={`${data.open_invoices} tagihan belum lunas`} />
            <StatCard label="Tunggakan" value={money(data.arrears)} icon={AlertTriangle} caption="Lewat jatuh tempo & masa tenggang" />
            <StatCard label="Jatuh tempo berikutnya" value={data.next_due ? date(data.next_due.date) : '—'} icon={CalendarClock} caption={data.next_due ? `${money(data.next_due.amount)}${data.next_due.installment_seq ? ` · termin ${data.next_due.installment_seq}` : ''}` : 'Tidak ada'} />
            <StatCard label="Saldo deposit" value={money(data.deposit_available)} icon={Landmark} caption="Dapat dipakai untuk tagihan" />
          </div>
          {data.pending_verification > 0 && <Alert variant="info">{data.pending_verification} bukti transfer sedang diverifikasi Bagian Keuangan. Status tagihan berubah setelah bukti disetujui.</Alert>}
          {data.blocks.length > 0 && (
            <Card title={<span className="flex items-center gap-2 text-danger"><ShieldAlert className="size-4" />Cekal keuangan</span>}>
              <div className="flex flex-col gap-3">
                {data.blocks.map((b) => (
                  <div key={b.id} className="rounded-lg border border-border p-3 text-sm">
                    <p className="font-medium">{b.services.map((s) => SERVICES[s] ?? s).join(', ')} dibatasi</p>
                    <p className="mt-1 text-ink-secondary">{b.reason}</p>
                    {b.shortfall && <p className="mt-1">Kekurangan: <strong>{money(b.shortfall)}</strong></p>}
                    {(b.exemptions ?? []).filter((e) => ['pending', 'active'].includes(e.status)).map((e) => (
                      <p key={e.id} className="mt-1 text-xs">Dispensasi {SERVICES[e.service] ?? e.service}: <KeuBadge status={e.status} label={e.status_label} /> {e.status === 'active' && `sampai ${dateTime(e.valid_until)}`}</p>
                    ))}
                    <Button size="sm" variant="outline" className="mt-2" onClick={() => setDispensing(b)}>Ajukan dispensasi</Button>
                  </div>
                ))}
              </div>
            </Card>
          )}
          <div className="flex flex-wrap gap-2 text-sm">
            {data.services.map((s) => <span key={s.service} className="rounded-full border border-border px-3 py-1">{s.label}: {s.blocked ? <span className="font-medium text-danger">diblokir</span> : <span className="text-primary">dapat diakses</span>}</span>)}
            <span className="rounded-full border border-border px-3 py-1">Bebas tanggungan keuangan: {data.clearance.clear ? <span className="text-primary">Ya</span> : <span className="text-danger">Belum</span>}</span>
          </div>
        </>
      )}
      <Tabs<Tab> active={tab} onChange={setTab} tabs={[
        { key: 'invoices', label: 'Tagihan' }, { key: 'transactions', label: 'Riwayat pembayaran' }, { key: 'deposit', label: 'Deposit & refund' },
        { key: 'statement', label: 'Kartu piutang' }, { key: 'ukt', label: 'UKT' }, { key: 'awards', label: 'Beasiswa/potongan' },
      ]} />
      {tab === 'invoices' && <MyInvoices />}
      {tab === 'transactions' && <MyTransactions onChanged={reload} />}
      {tab === 'deposit' && <MyDeposit onChanged={reload} />}
      {tab === 'statement' && <MyStatement />}
      {tab === 'ukt' && <MyUkt />}
      {tab === 'awards' && <MyAwards />}
      <FormDialog open={dispensing !== null} title="Ajukan dispensasi cekal" onClose={() => setDispensing(null)}
        description="Dispensasi memberi akses sementara ke layanan yang diblokir sambil Anda menyelesaikan pembayaran. Pengajuan diputuskan Bagian Keuangan."
        fields={[
          { name: 'service', label: 'Layanan', type: 'select', required: true, options: (dispensing?.services ?? []).map((s) => ({ value: s, label: SERVICES[s] ?? s })) },
          { name: 'valid_until', label: 'Dibutuhkan sampai', type: 'datetime', required: true },
          { name: 'reason', label: 'Alasan (minimal 20 karakter)', type: 'textarea', required: true },
          { name: 'promise_amount', label: 'Janji bayar (Rp, opsional)', type: 'number' },
          { name: 'promise_date', label: 'Tanggal janji bayar', type: 'date' },
        ]}
        onSubmit={(v) => action.run(() => keuStudent.post(`blocks/${dispensing?.id}/dispensations`, v), (x) => x.message).then((x) => x && reload())} />
    </PortalPage>
  )
}

function usePaged<T>(path: string, deps: unknown[] = []) {
  const [page, setPage] = useState(1)
  const state = useLoad(() => keuStudent.list<T>(path, { page }), [page, ...deps])

  return { ...state, page, setPage, result: state.data as Paginated<T> | null }
}

function MyInvoices() {
  const { result, loading, error, page, setPage } = usePaged<Invoice>('invoices')

  return (
    <Card noPadding>
      {error && <Alert variant="danger" className="m-3">{describeError(error)}</Alert>}
      <DataTable rowKey={(r) => r.id} data={result?.data ?? []} emptyMessage={loading ? 'Memuat...' : 'Belum ada tagihan.'} columns={[
        { header: 'Tagihan', cell: (r) => <Link className="text-primary" to={to(KEU.portal.invoice, { id: r.id })}>{r.invoice_number ?? r.period}<span className="block text-xs text-ink-tertiary">{r.bill_group_label} · {r.period}</span></Link> },
        { header: 'Jumlah', cell: (r) => money(r.amount) },
        { header: 'Dibayar', cell: (r) => money(r.paid_amount) },
        { header: 'Sisa', cell: (r) => money(r.outstanding) },
        { header: 'Jatuh tempo', cell: (r) => date(r.due_date) },
        { header: 'Status', cell: (r) => <span className="flex gap-1"><KeuBadge status={r.status} label={r.status_label} />{r.is_overdue && <KeuBadge status="unpaid" overdue />}</span> },
        { header: '', cell: (r) => ['unpaid', 'partial'].includes(r.status) ? <Link to={to(KEU.portal.invoice, { id: r.id })}><Button size="sm">Bayar</Button></Link> : <Link className="text-sm text-primary" to={to(KEU.portal.invoice, { id: r.id })}>Detail</Link> },
      ]} />
      <ListPagination meta={result?.meta ?? null} page={page} onPageChange={setPage} itemLabel="tagihan" />
    </Card>
  )
}

function MyTransactions({ onChanged }: { onChanged: () => void }) {
  const [reload, setReload] = useState(0)
  const { result, loading, error, page, setPage } = usePaged<Transaction>('transactions', [reload])
  const [uploading, setUploading] = useState<Transaction | null>(null)
  const action = useAction()
  const changed = (r: unknown) => { if (r) { setReload((x) => x + 1); onChanged() } }

  return (
    <Card noPadding>
      <div className="p-3"><Feedback state={action} />{error && <Alert variant="danger">{describeError(error)}</Alert>}</div>
      <DataTable rowKey={(r) => r.id} data={result?.data ?? []} emptyMessage={loading ? 'Memuat...' : 'Belum ada transaksi.'} columns={transactionRows(action, setUploading, changed)} />
      <ListPagination meta={result?.meta ?? null} page={page} onPageChange={setPage} itemLabel="transaksi" />
      <ProofDialog tx={uploading} onClose={() => setUploading(null)} onDone={(m) => { changed(true); action.run(async () => m, (x) => x) }} />
    </Card>
  )
}

function transactionRows(action: ReturnType<typeof useAction>, upload: (t: Transaction) => void, changed: (r: unknown) => void) {
  return [
    { header: 'Transaksi', cell: (r: Transaction) => <span>{r.receipt_number ?? r.transaction_number}<span className="block text-xs text-ink-tertiary">{dateTime(r.paid_at ?? r.created_at)}</span></span> },
    { header: 'Metode', cell: (r: Transaction) => r.channel?.name ?? r.method_label },
    { header: 'Nominal', cell: (r: Transaction) => money(r.amount) },
    { header: 'Status', cell: (r: Transaction) => <span><KeuBadge status={r.status} label={r.status_label} />{r.failure_reason && <span className="block max-w-xs whitespace-normal text-xs text-danger">{r.failure_reason}{r.proof?.note ? ` — ${r.proof.note}` : ''}</span>}</span> },
    { header: 'Aksi', cell: (r: Transaction) => <div className="flex flex-wrap gap-1">
      {r.receipt_number && <Button size="sm" variant="outline" leftIcon={<Printer className="size-3.5" />} onClick={() => action.run(() => keuStudent.openPdf(`transactions/${r.id}/receipt`))}>Kuitansi</Button>}
      {r.method === 'manual_transfer' && ['pending', 'failed'].includes(r.status) && <Button size="sm" onClick={() => upload(r)}>{r.status === 'failed' ? 'Unggah ulang bukti' : 'Unggah bukti'}</Button>}
      {r.method === 'online' && r.status === 'pending' && <Button size="sm" variant="outline" onClick={() => action.run(() => keuStudent.post(`transactions/${r.id}/refresh`), 'Status diperbarui dari penyedia.').then(changed)}>Cek status</Button>}
      {['created', 'pending'].includes(r.status) && <Button size="sm" variant="ghost" onClick={() => action.run(() => keuStudent.post(`transactions/${r.id}/cancel`), (x) => x.message).then(changed)}>Batalkan</Button>}
    </div> },
  ]
}

function ProofDialog({ tx, onClose, onDone }: { tx: Transaction | null; onClose: () => void; onDone: (message: string) => void }) {
  return (
    <FormDialog open={tx !== null} title="Unggah bukti transfer" onClose={onClose}
      description={tx ? `Transfer tepat ${money(tx.amount)}${tx.unique_code ? ` (sudah termasuk kode unik ${tx.unique_code})` : ''}. Bukti berupa JPG/PNG/PDF maksimal 2 MB.` : undefined}
      fields={[
        { name: 'file', label: 'Berkas bukti', type: 'file', required: true, accept: '.jpg,.jpeg,.png,.pdf' },
        { name: 'sender_bank', label: 'Bank pengirim', required: true },
        { name: 'sender_name', label: 'Nama pemilik rekening pengirim', required: true },
        { name: 'transfer_date', label: 'Tanggal transfer', type: 'date', required: true },
        { name: 'transferred_amount', label: 'Nominal yang ditransfer (Rp)', type: 'number', required: true },
      ]}
      onSubmit={async (v) => {
        if (!(v.file instanceof File)) throw { status: 422, message: 'Pilih berkas bukti transfer.' }
        if (v.file.size > 2 * 1024 * 1024) throw { status: 422, message: 'Ukuran berkas maksimal 2 MB.' }
        const fileId = await uploadFile(v.file)
        const { file: _file, ...rest } = v
        const { message } = await keuStudent.post(`transactions/${tx?.id}/proof`, { ...rest, file_upload_id: fileId })
        onDone(message)
      }} />
  )
}

function MyDeposit({ onChanged }: { onChanged: () => void }) {
  const { data, error, reload } = useLoad(() => keuStudent.get<{ balance: string; held_amount: string; available: string; entries: Array<{ id: string; type_label: string; amount: string; balance_after: string; note: string | null; created_at: string | null }>; refunds: Refund[] }>('deposit'))
  const [requesting, setRequesting] = useState(false)
  const action = useAction()

  return (
    <div className="flex flex-col gap-4">
      <Feedback state={action} />
      {error && <Alert variant="danger">{describeError(error)}</Alert>}
      <Card actions={Number(data?.available ?? 0) > 0 ? <Button size="sm" variant="outline" onClick={() => setRequesting(true)}>Ajukan pengembalian dana</Button> : undefined}>
        <div className="grid gap-4 sm:grid-cols-3">
          <Figure label="Saldo" value={money(data?.balance)} />
          <Figure label="Ditahan (pengajuan refund)" value={money(data?.held_amount)} />
          <Figure label="Tersedia" value={money(data?.available)} tone="success" />
        </div>
        <p className="mt-3 text-xs text-ink-tertiary">Saldo deposit berasal dari kelebihan bayar atau koreksi tagihan, dan dapat dipakai untuk membayar tagihan dari halaman detail tagihan.</p>
      </Card>
      <Card noPadding title="Mutasi deposit">
        <DataTable rowKey={(r) => r.id} data={data?.entries ?? []} emptyMessage="Belum ada mutasi." columns={[
          { header: 'Waktu', cell: (r) => dateTime(r.created_at) }, { header: 'Jenis', cell: (r) => r.type_label }, { header: 'Mutasi', cell: (r) => money(r.amount) }, { header: 'Saldo', cell: (r) => money(r.balance_after) }, { header: 'Keterangan', cell: (r) => r.note ?? '—' },
        ]} />
      </Card>
      <Card noPadding title="Pengajuan pengembalian dana">
        <DataTable rowKey={(r) => r.id} data={data?.refunds ?? []} emptyMessage="Belum ada pengajuan." columns={[
          { header: 'Nomor', cell: (r) => r.refund_number }, { header: 'Nominal', cell: (r) => money(r.amount) },
          { header: 'Rekening', cell: (r) => `${r.beneficiary_bank} ${r.beneficiary_account}` },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{(r.decision_note || r.failure_reason) && <span className="block text-xs text-ink-tertiary">{r.failure_reason ?? r.decision_note}</span>}</span> },
          { header: 'Diajukan', cell: (r) => dateTime(r.created_at) },
        ]} />
      </Card>
      <FormDialog open={requesting} title="Ajukan pengembalian dana" onClose={() => setRequesting(false)}
        description={`Saldo tersedia ${money(data?.available)}. Dana ditahan selama pengajuan diproses dan ditransfer ke rekening Anda atau orang tua/wali.`}
        fields={[
          { name: 'amount', label: 'Nominal (Rp)', type: 'number', required: true },
          { name: 'reason_code', label: 'Alasan', type: 'select', required: true, options: REFUND_REASONS },
          { name: 'reason_note', label: 'Penjelasan', type: 'textarea', required: true },
          { name: 'beneficiary_bank', label: 'Bank', required: true },
          { name: 'beneficiary_account', label: 'Nomor rekening', required: true },
          { name: 'beneficiary_name', label: 'Nama pemilik rekening', required: true },
          { name: 'beneficiary_relation', label: 'Pemilik rekening', type: 'select', required: true, options: [{ value: 'self', label: 'Saya sendiri' }, { value: 'parent', label: 'Orang tua/wali' }] },
        ]}
        onSubmit={(v) => action.run(() => keuStudent.post('refunds', v), (x) => x.message).then((x) => { if (x) { reload(); onChanged() } })} />
    </div>
  )
}

function MyStatement() {
  const { data, loading, error } = useLoad(() => keuStudent.get<{ entries: StatementEntry[]; balance: string }>('statement'))

  return (
    <Card noPadding title={`Saldo kewajiban: ${money(data?.balance)}`}>
      {error && <Alert variant="danger" className="m-3">{describeError(error)}</Alert>}
      <DataTable rowKey={(r) => `${r.date}-${r.type}-${r.amount}-${r.balance}`} data={data?.entries ?? []} emptyMessage={loading ? 'Memuat...' : 'Belum ada mutasi.'} columns={[
        { header: 'Tanggal', cell: (r) => dateTime(r.date) }, { header: 'Tagihan', cell: (r) => r.invoice_number ?? '—' }, { header: 'Jenis', cell: (r) => r.type },
        { header: 'Uraian', cell: (r) => <span className="whitespace-normal text-xs">{r.description}</span> }, { header: 'Jumlah', cell: (r) => money(r.amount) }, { header: 'Saldo', cell: (r) => money(r.balance) },
      ]} />
    </Card>
  )
}

function MyUkt() {
  const { data, error, reload } = useLoad(() => keuStudent.get<{ current: { group: { id: string; code: string; name: string } | null; basis: string; decree_number: string | null; effective_from: string | null } | null; groups: Array<{ id: string; code: string; name: string }>; requests: Array<{ id: string; requested_group: string | null; decided_group: string | null; reason: string; status: string; status_label: string; decision_note: string | null; created_at: string | null }> }>('ukt'))
  const [requesting, setRequesting] = useState(false)
  const action = useAction()

  return (
    <div className="flex flex-col gap-4">
      <Feedback state={action} />
      {error && <Alert variant="danger">{describeError(error)}</Alert>}
      <Card actions={<Button size="sm" variant="outline" onClick={() => setRequesting(true)}>Ajukan peninjauan UKT</Button>}>
        {data?.current ? <KeyValue columns={3} items={[['Golongan UKT', data.current.group?.name], ['Berlaku mulai', data.current.effective_from ?? '—'], ['Dasar', `${data.current.decree_number ? `${data.current.decree_number} — ` : ''}${data.current.basis}`]]} />
          : <p className="text-sm text-ink-secondary">Golongan UKT Anda belum ditetapkan (atau kampus tidak memakai skema UKT).</p>}
      </Card>
      <Card noPadding title="Riwayat pengajuan">
        <DataTable rowKey={(r) => r.id} data={data?.requests ?? []} emptyMessage="Belum ada pengajuan." columns={[
          { header: 'Diajukan', cell: (r) => dateTime(r.created_at) }, { header: 'Golongan diminta', cell: (r) => r.requested_group ?? '—' },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{r.decided_group && <span className="block text-xs">Ditetapkan: {r.decided_group}</span>}{r.decision_note && <span className="block text-xs text-ink-tertiary">{r.decision_note}</span>}</span> },
        ]} />
      </Card>
      <FormDialog open={requesting} title="Ajukan peninjauan UKT" onClose={() => setRequesting(false)} description="Lampirkan bukti perubahan kemampuan ekonomi (mis. surat keterangan penghasilan). Keputusan berlaku untuk tagihan berikutnya."
        fields={[
          { name: 'requested_group_id', label: 'Golongan yang diharapkan (opsional)', type: 'select', options: (data?.groups ?? []).map((g) => ({ value: g.id, label: g.name })) },
          { name: 'reason', label: 'Alasan (minimal 20 karakter)', type: 'textarea', required: true },
          { name: 'evidence', label: 'Bukti pendukung (PDF/JPG/PNG, maks. 5 MB)', type: 'file', required: true, accept: '.pdf,.jpg,.jpeg,.png' },
        ]}
        onSubmit={async (v) => {
          if (!(v.evidence instanceof File)) throw { status: 422, message: 'Lampirkan bukti pendukung.' }
          const fileId = await uploadFile(v.evidence)
          await action.run(() => keuStudent.post('ukt-requests', { requested_group_id: v.requested_group_id, reason: v.reason, evidence_file_id: fileId }), (x) => x.message).then((x) => x && reload())
        }} />
    </div>
  )
}

function MyAwards() {
  const { data, loading, error } = useLoad(() => keuStudent.get<Award[]>('awards'))

  return (
    <Card noPadding>
      {error && <Alert variant="danger" className="m-3">{describeError(error)}</Alert>}
      <DataTable rowKey={(r) => r.id} data={data ?? []} emptyMessage={loading ? 'Memuat...' : 'Anda tidak memiliki beasiswa/potongan aktif.'} columns={[
        { header: 'Jenis', cell: (r) => <span>{r.policy?.name}<span className="block text-xs text-ink-tertiary">{r.policy?.kind_label}</span></span> },
        { header: 'Besaran', cell: (r) => r.calc_type === 'percent' ? `${Number(r.value)}%` : r.calc_type === 'full' ? 'Penuh' : money(r.value) },
        { header: 'Berlaku', cell: (r) => `${r.start_term ?? '—'} s.d. ${r.end_term ?? 'seterusnya'}` },
        { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{r.revoke_reason && <span className="block text-xs text-ink-tertiary">{r.revoke_reason}</span>}</span> },
      ]} />
    </Card>
  )
}

interface StudentInvoice extends InvoiceDetail { pending_transactions: Transaction[] }
interface Channels { online: Array<{ id: string; code: string; name: string; method: string; fee_amount: string; fee_bearer: string }>; manual_transfer: { enabled: boolean; bank_accounts: Array<{ bank: string; account_number: string; account_name: string }>; unique_code: boolean } }

/** Detail tagihan mahasiswa + pembayaran (online bila tersedia, transfer manual, deposit), voucher, cicilan. */
export function PortalInvoicePage() {
  const { id = '' } = useParams()
  const { data, error, reload } = useLoad(() => keuStudent.get<StudentInvoice>(`invoices/${id}`), [id])
  const channels = useLoad(() => keuStudent.get<Channels>('channels'))
  const summary = useLoad(() => keuStudent.get<{ deposit_available: string }>('summary'), [data?.paid_amount])
  const plans = useLoad(() => keuStudent.get<Array<{ id: string; code: string; name: string; terms: Array<{ percent: number }>; auto_approve: boolean }>>(`invoices/${id}/installment-plans`), [id])
  const action = useAction()
  const [target, setTarget] = useState('invoice')
  const [dialog, setDialog] = useState<'voucher' | 'installment' | 'deposit' | null>(null)
  const [uploading, setUploading] = useState<Transaction | null>(null)
  const run = (fn: () => Promise<{ message: string }>) => action.run(fn, (r) => r.message).then((r) => { if (r) reload() })

  if (error) return <PortalPage title="Tagihan" trail={[{ label: 'Tagihan' }]}><Alert variant="danger">{describeError(error)}</Alert></PortalPage>
  if (!data) return <p className="text-sm text-ink-secondary">Memuat...</p>
  const open = ['unpaid', 'partial'].includes(data.status)
  const unpaidTerms = data.installments.filter((i) => Number(i.remaining) > 0)
  const [targetType, targetId] = target === 'invoice' ? ['invoice', data.id] : ['installment', target]
  const deposit = Number(summary.data?.deposit_available ?? 0)

  return (
    <PortalPage title={`Tagihan ${data.invoice_number ?? data.period}`} description={`${data.bill_group_label} · ${data.period}`} trail={[{ label: data.invoice_number ?? 'Tagihan' }]}>
      <Feedback state={action} />
      <Card>
        <div className="grid gap-4 sm:grid-cols-4">
          <Figure label="Jumlah tagihan" value={money(data.amount)} />
          <Figure label="Dibayar" value={money(data.paid_amount)} tone="success" />
          <Figure label="Sisa" value={money(data.outstanding)} tone={Number(data.outstanding) > 0 ? 'danger' : undefined} />
          <Figure label="Jatuh tempo" value={date(data.due_date)} tone={data.is_overdue ? 'danger' : undefined} />
        </div>
        <div className="mt-3 flex gap-2"><KeuBadge status={data.status} label={data.status_label} />{data.is_overdue && <KeuBadge status="unpaid" overdue />}</div>
        {data.cancel_reason && <Alert variant="info" className="mt-3">Tagihan dibatalkan: {data.cancel_reason}</Alert>}
      </Card>

      {data.pending_transactions.length > 0 && (
        <Card title="Pembayaran yang sedang berjalan">
          <div className="flex flex-col gap-4">
            {data.pending_transactions.map((t) => <PaymentInstruction key={t.id} tx={t} onUpload={() => setUploading(t)} onCancel={() => run(() => keuStudent.post(`transactions/${t.id}/cancel`))} onRefresh={() => run(async () => ({ ...(await keuStudent.post(`transactions/${t.id}/refresh`)), message: 'Status diperbarui dari penyedia.' }))} />)}
          </div>
        </Card>
      )}

      {open && (
        <Card title="Bayar tagihan">
          <div className="flex flex-col gap-4">
            {unpaidTerms.length > 0 && (
              <div className="flex flex-wrap gap-2">
                <span className="text-sm text-ink-secondary">Bayar untuk:</span>
                <button type="button" className={`rounded-full border px-3 py-1 text-sm ${target === 'invoice' ? 'border-primary text-primary' : 'border-border'}`} onClick={() => setTarget('invoice')}>Seluruh sisa ({money(data.outstanding)})</button>
                {unpaidTerms.map((t) => <button key={t.id} type="button" className={`rounded-full border px-3 py-1 text-sm ${target === t.id ? 'border-primary text-primary' : 'border-border'}`} onClick={() => setTarget(t.id)}>Termin {t.seq} · {money(t.remaining)} · {date(t.due_date)}</button>)}
              </div>
            )}
            <div className="grid gap-3 md:grid-cols-2">
              <div className="rounded-lg border border-border p-4">
                <p className="font-medium">Pembayaran online</p>
                {(channels.data?.online ?? []).length === 0 ? (
                  <p className="mt-1 text-sm text-ink-secondary">Pembayaran online (virtual account/QRIS) belum tersedia di kampus Anda. Silakan transfer manual atau bayar tunai di loket Bagian Keuangan.</p>
                ) : (
                  <div className="mt-2 flex flex-wrap gap-2">
                    {channels.data!.online.map((c) => (
                      <Button key={c.id} size="sm" variant="outline" isLoading={action.busy} onClick={() => run(() => keuStudent.post('payments/online', { payment_channel_id: c.id, target_type: targetType, target_id: targetId }, { 'Idempotency-Key': idempotencyKey() }))}>
                        {c.name}{Number(c.fee_amount) > 0 && c.fee_bearer === 'student' ? ` (+${money(c.fee_amount)})` : ''}
                      </Button>
                    ))}
                  </div>
                )}
              </div>
              <div className="rounded-lg border border-border p-4">
                <p className="font-medium">Transfer manual ke rekening kampus</p>
                <p className="mt-1 text-sm text-ink-secondary">Sistem membuat nominal transfer{channels.data?.manual_transfer.unique_code ? ' dengan kode unik' : ''}. Setelah transfer, unggah bukti untuk diverifikasi Bagian Keuangan.</p>
                <Button size="sm" className="mt-2" isLoading={action.busy} onClick={() => run(() => keuStudent.post('payments/manual', { target_type: targetType, target_id: targetId }))}>Buat instruksi transfer</Button>
              </div>
            </div>
            <div className="flex flex-wrap gap-2">
              {deposit > 0 && <Button size="sm" variant="outline" onClick={() => setDialog('deposit')}>Pakai saldo deposit ({money(deposit)})</Button>}
              {data.status === 'unpaid' && <Button size="sm" variant="outline" onClick={() => setDialog('voucher')}>Pakai kode voucher</Button>}
              {unpaidTerms.length === 0 && (plans.data ?? []).length > 0 && <Button size="sm" variant="outline" onClick={() => setDialog('installment')}>Ajukan cicilan</Button>}
            </div>
          </div>
        </Card>
      )}

      <Card noPadding title="Rincian">
        <DataTable rowKey={(r) => r.id} data={data.items} emptyMessage="Tagihan tanpa rincian." columns={[
          { header: 'Uraian', cell: (r) => <span className="whitespace-normal">{r.description}</span> }, { header: 'Jenis', cell: (r) => r.line_type_label }, { header: 'Jumlah', cell: (r) => money(r.amount) },
        ]} />
      </Card>
      {data.installments.length > 0 && (
        <Card noPadding title="Jadwal cicilan">
          <DataTable rowKey={(r) => r.id} data={data.installments} columns={[
            { header: 'Termin', cell: (r) => r.seq }, { header: 'Jatuh tempo', cell: (r) => date(r.due_date) }, { header: 'Nominal', cell: (r) => money(r.amount) },
            { header: 'Sisa', cell: (r) => money(r.remaining) }, { header: 'Status', cell: (r) => <KeuBadge status={r.is_overdue ? 'unpaid' : r.status} label={r.status_label} /> },
          ]} />
        </Card>
      )}
      {data.payments.length > 0 && (
        <Card noPadding title="Pembayaran tercatat">
          <DataTable rowKey={(r) => r.id} data={data.payments} columns={[
            { header: 'Tanggal', cell: (r) => date(r.paid_at) }, { header: 'Referensi', cell: (r) => r.reference ?? '—' }, { header: 'Nominal', cell: (r) => money(r.amount) },
            { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status === 'reversed' ? 'Dibatalkan' : 'Tercatat'} /> },
          ]} />
        </Card>
      )}
      {data.penalty_invoices.length > 0 && <Alert variant="warning">Pembayaran terlambat menimbulkan denda: {data.penalty_invoices.map((p) => <Link key={p.id} className="ml-1 text-primary" to={to(KEU.portal.invoice, { id: p.id })}>{p.invoice_number} ({money(p.amount)})</Link>)}</Alert>}

      <FormDialog open={dialog === 'voucher'} title="Pakai kode voucher" onClose={() => setDialog(null)} description="Voucher hanya untuk tagihan yang belum dibayar sama sekali dan belum lewat jatuh tempo. Batalkan dahulu instruksi pembayaran yang sedang berjalan."
        fields={[{ name: 'code', label: 'Kode voucher', required: true }]} onSubmit={(v) => run(() => keuStudent.post(`invoices/${id}/voucher`, v))} />
      <FormDialog open={dialog === 'deposit'} title="Bayar dengan saldo deposit" onClose={() => setDialog(null)} description={`Saldo tersedia ${money(deposit)}. Kosongkan nominal untuk memakai sebanyak mungkin.`}
        fields={[{ name: 'amount', label: 'Nominal (Rp, opsional)', type: 'number' }]}
        onSubmit={(v) => run(() => keuStudent.post(`invoices/${id}/deposit`, { ...v, installment_id: targetType === 'installment' ? targetId : null }))} />
      <FormDialog open={dialog === 'installment'} title="Ajukan cicilan" onClose={() => setDialog(null)} description="Skema dengan persetujuan otomatis langsung membuat jadwal termin."
        fields={[
          { name: 'installment_plan_id', label: 'Skema', type: 'select', required: true, options: (plans.data ?? []).map((p) => ({ value: p.id, label: `${p.name} — ${p.terms.map((t) => `${t.percent}%`).join('/')}${p.auto_approve ? '' : ' (perlu persetujuan)'}` })) },
          { name: 'reason', label: 'Alasan (opsional)', type: 'textarea' },
        ]}
        onSubmit={(v) => run(() => keuStudent.post(`invoices/${id}/installments`, v))} />
      <ProofDialog tx={uploading} onClose={() => setUploading(null)} onDone={(m) => { reload(); action.run(async () => m, (x) => x) }} />
    </PortalPage>
  )
}

function PaymentInstruction({ tx, onUpload, onCancel, onRefresh }: { tx: Transaction; onUpload: () => void; onCancel: () => void; onRefresh: () => void }) {
  const banks = tx.instructions?.bank_accounts ?? []

  return (
    <div className="rounded-lg border border-border p-4 text-sm">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <p className="font-medium">{tx.method_label}{tx.channel ? ` · ${tx.channel.name}` : ''} — {money(tx.amount)}</p>
        <KeuBadge status={tx.status} label={tx.status_label} />
      </div>
      {tx.method === 'manual_transfer' && tx.status === 'pending' && (
        <div className="mt-2 flex flex-col gap-1">
          <p>Transfer <strong>tepat {money(tx.amount)}</strong>{tx.unique_code ? ` (termasuk kode unik ${tx.unique_code})` : ''} ke salah satu rekening:</p>
          {banks.length === 0 ? <p className="text-danger">Rekening penerimaan belum diatur kampus — hubungi Bagian Keuangan.</p> : banks.map((b) => <p key={b.account_number} className="font-mono">{b.bank} {b.account_number} a.n. {b.account_name}</p>)}
          <p className="text-xs text-ink-tertiary">Batas unggah bukti: {dateTime(tx.expires_at)}</p>
        </div>
      )}
      {tx.method === 'manual_transfer' && tx.status === 'awaiting_verification' && <p className="mt-2 text-ink-secondary">Bukti sudah dikirim dan sedang diverifikasi.</p>}
      {tx.method === 'online' && (
        <div className="mt-2 flex flex-col gap-1">
          {tx.va_number && <p>Nomor virtual account: <strong className="font-mono">{tx.va_number}</strong></p>}
          {tx.qr_string && <QRCodeSVG value={tx.qr_string} size={160} />}
          {tx.redirect_url && <a className="text-primary" href={tx.redirect_url} target="_blank" rel="noopener noreferrer">Lanjutkan pembayaran di halaman penyedia</a>}
          {(tx.instructions?.steps ?? []).map((s) => <div key={s.title}><p className="font-medium">{s.title}</p><ol className="ml-5 list-decimal">{s.steps.map((x) => <li key={x}>{x}</li>)}</ol></div>)}
          <p className="text-xs text-ink-tertiary">Berlaku sampai {dateTime(tx.expires_at)}. Status pembayaran dikonfirmasi oleh penyedia; halaman ini dapat dicek ulang.</p>
        </div>
      )}
      <div className="mt-3 flex flex-wrap gap-2">
        {tx.method === 'manual_transfer' && tx.status === 'pending' && <Button size="sm" onClick={onUpload}>Unggah bukti transfer</Button>}
        {tx.method === 'online' && tx.status === 'pending' && <Button size="sm" variant="outline" onClick={onRefresh}>Cek status</Button>}
        {['created', 'pending'].includes(tx.status) && <Button size="sm" variant="ghost" onClick={onCancel}>Batalkan</Button>}
      </div>
    </div>
  )
}
