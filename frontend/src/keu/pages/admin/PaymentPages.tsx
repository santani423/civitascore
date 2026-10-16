import { useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'
import { FileText, Lock, Printer, Unlock } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { DataTable } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, describeError, useAction } from '@/pmb/components/useAction'
import { FormDialog } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { KeyValue } from '@/pmb/components/KeyValue'
import { date, dateTime, money } from '@/pmb/lib/format'
import { downloadBlob } from '@/services/studentPortalService'
import { usePermission } from '@/hooks/usePermission'
import { useAuthStore } from '@/stores/authStore'
import { keuAdmin, uploadFile } from '@/keu/api'
import { Can, Figure, KeuBadge, KeuList, KeuPage, StudentPicker } from '@/keu/components'
import { P } from '@/keu/permissions'
import { useKeuLookups } from '@/keu/lookups'
import { KEU, to } from '@/keu/routes'
import type { Invoice, ProofRow, StudentRef, Transaction } from '@/keu/types'
import { transactionColumns } from '@/keu/columns'
import type { NormalizedApiError } from '@/services/api'

const RECON_OPTIONS = [
  { value: 'unreconciled', label: 'Belum direkonsiliasi' }, { value: 'pending_deposit', label: 'Menunggu setoran' }, { value: 'matched', label: 'Cocok' },
  { value: 'exception', label: 'Pengecualian' }, { value: 'resolved', label: 'Diselesaikan' }, { value: 'not_required', label: 'Tidak perlu' },
]

/** Seluruh transaksi uang masuk: tunai, transfer manual, online, deposit. */
export function TransactionsPage() {
  const lookups = useKeuLookups()
  // Tautan grafik penerimaan Dashboard: ?date_from=&date_to= (tanggal bayar efektif).
  const [searchParams] = useSearchParams()
  const [initialFilters] = useState(() => Object.fromEntries(['date_from', 'date_to', 'status', 'student_id'].flatMap((k) => searchParams.get(k) ? [[k, searchParams.get(k) as string]] : [])))

  return (
    <KeuPage title="Transaksi Pembayaran" description="Uang masuk dari semua kanal. Status transaksi, status tagihan, dan status rekonsiliasi dicatat terpisah." trail={[{ label: 'Pembayaran' }]}>
      <KeuList<Transaction> path="transactions" rowKey={(r) => r.id} itemLabel="transaksi" initialFilters={initialFilters}
        filters={[
          { name: 'status', label: 'Status', type: 'select', options: lookups?.enums.transaction_status ?? [] },
          { name: 'method', label: 'Metode', type: 'select', options: lookups?.enums.payment_method ?? [] },
          { name: 'reconciliation_status', label: 'Rekonsiliasi', type: 'select', options: RECON_OPTIONS },
          { name: 'needs_resolution', label: 'Perlu resolusi', type: 'select', options: [{ value: '1', label: 'Ya' }] },
          { name: 'date_from', label: 'Dibayar dari', type: 'date' },
          { name: 'date_to', label: 'sampai', type: 'date' },
        ]}
        columns={transactionColumns()} />
    </KeuPage>
  )
}

export function TransactionDetailPage() {
  const { id = '' } = useParams()
  const { data, error, reload } = useLoad(() => keuAdmin.get<Transaction>(`transactions/${id}`), [id])
  const action = useAction()
  const [reversing, setReversing] = useState(false)
  const run = (fn: () => Promise<{ message: string }>) => action.run(fn, (r) => r.message).then((r) => { if (r) reload() })

  if (error) return <KeuPage title="Transaksi" trail={[{ label: 'Pembayaran', path: KEU.admin.transactions }]}><Alert variant="danger">{describeError(error)}</Alert></KeuPage>
  if (!data) return <p className="text-sm text-ink-secondary">Memuat...</p>

  return (
    <KeuPage title={`Transaksi ${data.transaction_number}`} trail={[{ label: 'Pembayaran', path: KEU.admin.transactions }, { label: data.transaction_number }]}
      actions={<div className="flex flex-wrap gap-2">
        {data.receipt_number && <Button variant="outline" leftIcon={<Printer className="size-4" />} onClick={() => action.run(() => keuAdmin.openPdf(`transactions/${id}/receipt`))}>Kuitansi</Button>}
        {data.method === 'online' && data.status === 'pending' && <Can permission={P.TX_UPDATE}><Button variant="outline" onClick={() => run(() => keuAdmin.post(`transactions/${id}/recheck`))}>Cek status ke penyedia</Button></Can>}
        {['created', 'pending'].includes(data.status) && <Can permission={P.TX_UPDATE}><Button variant="outline" onClick={() => run(() => keuAdmin.post(`transactions/${id}/cancel`))}>Batalkan</Button></Can>}
        {data.status === 'success' && <Can permission={P.TX_APPROVE}><Button variant="danger" onClick={() => setReversing(true)}>Balik transaksi</Button></Can>}
      </div>}>
      <Feedback state={action} />
      {data.needs_resolution && <Alert variant="warning">Transaksi ini perlu resolusi: dana diterima setelah transaksi kedaluwarsa/dibatalkan atau tidak teralokasi ke tagihan. Dana yang tidak teralokasi sudah dikreditkan ke deposit mahasiswa.</Alert>}
      <Card>
        <KeyValue columns={3} items={[
          ['Mahasiswa', data.student ? <Link key="mahasiswa" className="text-primary" to={to(KEU.admin.student, { id: data.student.id })}>{data.student.nim} — {data.student.name}</Link> : '—'],
          ['Penyetor', data.payer_name],
          ['Metode / kanal', `${data.method_label}${data.channel ? ` · ${data.channel.name}` : ''}`],
          ['Nominal', money(data.amount)],
          ['Biaya kanal', money(data.channel_fee)],
          ['Status', <KeuBadge key="status" status={data.status} label={data.status_label} />],
          ['Nomor kuitansi', data.receipt_number],
          ['Waktu bayar efektif', dateTime(data.paid_at)],
          ['Rekonsiliasi', <KeuBadge key="rekonsiliasi" status={data.reconciliation_status} label={data.reconciliation_label} />],
          ['Target', data.target_type === 'deposit' ? 'Titipan/deposit' : data.target_type === 'installment' ? 'Termin cicilan' : 'Tagihan'],
          ['Kode unik', data.unique_code],
          ['Nomor VA', data.va_number],
          ['Dicatat oleh', data.recorded_by],
          ['Diverifikasi oleh', data.verified_by],
          ['Kedaluwarsa', dateTime(data.expires_at)],
          ['Alasan gagal', data.failure_reason],
          ['Alasan pembalikan', data.reversal_reason],
        ]} />
      </Card>
      {data.proof && (
        <Card title="Bukti transfer" actions={data.proof.file_upload_id ? <Button size="sm" variant="outline" leftIcon={<FileText className="size-4" />} onClick={() => action.run(() => downloadBlob(`/file-uploads/${data.proof!.file_upload_id}/download`, `bukti-${data.transaction_number}`))}>Unduh bukti</Button> : undefined}>
          <KeyValue columns={3} items={[
            ['Bank pengirim', data.proof.sender_bank], ['Nama pengirim', data.proof.sender_name], ['Tanggal transfer', date(data.proof.transfer_date)],
            ['Nominal di bukti', money(data.proof.transferred_amount)], ['Status', <KeuBadge key="status" status={data.proof.status} label={data.proof.status_label} />], ['Catatan', data.proof.note],
          ]} />
          {data.proof.duplicate_warning && <Alert variant="warning" className="mt-3">Berkas bukti identik dengan bukti pada transaksi lain (kemungkinan bukti ganda).</Alert>}
        </Card>
      )}
      <Card title="Alokasi ke tagihan" noPadding>
        <DataTable rowKey={(r) => r.id} data={data.allocations ?? []} emptyMessage="Belum ada alokasi (transaksi belum berhasil atau seluruhnya masuk deposit)." columns={[
          { header: 'Tagihan', cell: (r) => <Link className="text-primary" to={to(KEU.admin.invoice, { id: r.invoice_id })}>Lihat tagihan</Link> },
          { header: 'Termin', cell: (r) => r.invoice_installment_id ? 'Ya' : '—' },
          { header: 'Nominal', cell: (r) => money(r.amount) },
          { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status === 'reversed' ? 'Dibalik' : 'Tercatat'} /> },
        ]} />
      </Card>
      <ReasonDialog open={reversing} title="Balik transaksi" description="Alokasi dibatalkan, tagihan dihitung ulang, dan kelebihan yang sempat masuk deposit ditarik kembali. Pencatat/verifikator transaksi tidak dapat membaliknya sendiri." confirmLabel="Balik transaksi"
        onClose={() => setReversing(false)} onConfirm={(v) => run(() => keuAdmin.post(`transactions/${id}/reverse`, v))} />
    </KeuPage>
  )
}

/** Verifikasi bukti transfer manual (FIFO). */
export function VerificationPage() {
  const lookups = useKeuLookups()
  const [status, setStatus] = useState('submitted')
  const [reload, setReload] = useState(0)
  const [approving, setApproving] = useState<ProofRow | null>(null)
  const [rejecting, setRejecting] = useState<ProofRow | null>(null)
  const canApprove = usePermission(P.TX_APPROVE)
  const action = useAction()
  const done = (r: unknown) => r && setReload((x) => x + 1)

  return (
    <KeuPage title="Verifikasi Transfer Manual" description="Cocokkan bukti dengan mutasi rekening kampus. Tanggal transfer pada bukti menjadi tanggal bayar efektif (denda tidak dihitung dari tanggal verifikasi)." trail={[{ label: 'Verifikasi transfer' }]}
      actions={<Select options={[{ value: 'submitted', label: 'Menunggu verifikasi' }, { value: 'approved', label: 'Disetujui' }, { value: 'rejected', label: 'Ditolak' }]} value={status} onChange={(e) => setStatus(e.target.value)} />}>
      <Feedback state={action} />
      <KeuList<ProofRow> path="payment-proofs" baseParams={{ status }} search={false} reloadKey={reload} rowKey={(r) => r.proof_id} itemLabel="bukti" emptyMessage="Tidak ada bukti pada status ini."
        columns={[
          { header: 'Transaksi', cell: (r) => <Link className="text-primary" to={to(KEU.admin.transaction, { id: r.id })}>{r.transaction_number}<span className="block text-xs text-ink-tertiary">dikirim {dateTime(r.submitted_at)}</span></Link> },
          { header: 'Mahasiswa', cell: (r) => <span>{r.student?.nim}<span className="block text-xs text-ink-tertiary">{r.student?.name}</span></span> },
          { header: 'Instruksi', cell: (r) => <span>{money(r.amount)}{r.unique_code ? <span className="block text-xs text-ink-tertiary">kode unik {r.unique_code}</span> : null}</span> },
          { header: 'Bukti', cell: (r) => r.proof ? <span>{money(r.proof.transferred_amount)}<span className="block text-xs text-ink-tertiary">{r.proof.sender_bank} · {r.proof.sender_name} · {date(r.proof.transfer_date)}</span></span> : '—' },
          { header: 'Catatan', cell: (r) => <span className="flex flex-col gap-0.5">
            {r.proof?.duplicate_warning && <Badge variant="danger">Bukti ganda?</Badge>}
            {r.proof && Number(r.proof.transferred_amount) !== Number(r.amount) && <Badge variant="warning">Nominal beda</Badge>}
            {status !== 'submitted' && <span className="text-xs text-ink-tertiary">{r.reviewed_by} · {dateTime(r.reviewed_at)}</span>}
          </span> },
          { header: 'Aksi', cell: (r) => (
            <div className="flex gap-1">
              {r.proof?.file_upload_id && <Button size="sm" variant="ghost" aria-label="Unduh bukti" onClick={() => action.run(() => downloadBlob(`/file-uploads/${r.proof!.file_upload_id}/download`, `bukti-${r.transaction_number}`))}><FileText className="size-4" /></Button>}
              {status === 'submitted' && canApprove && <><Button size="sm" onClick={() => setApproving(r)}>Setujui</Button><Button size="sm" variant="outline" onClick={() => setRejecting(r)}>Tolak</Button></>}
            </div>
          ) },
        ]} />
      <FormDialog open={approving !== null} title="Setujui bukti transfer" onClose={() => setApproving(null)}
        description={approving ? `Pastikan dana ${money(approving.proof?.transferred_amount)} benar masuk di mutasi rekening. Bila dana yang masuk berbeda, isi nominal aktual — kekurangan tetap menjadi sisa tagihan, kelebihan masuk deposit.` : undefined}
        fields={[{ name: 'amount', label: 'Nominal aktual diterima (opsional)', type: 'number' }, { name: 'note', label: 'Catatan', type: 'textarea' }]}
        onSubmit={(v) => action.run(() => keuAdmin.post(`payment-proofs/${approving?.proof_id}/approve`, v), (r) => r.message).then(done)} />
      <FormDialog open={rejecting !== null} title="Tolak bukti transfer" description="Mahasiswa menerima notifikasi berisi alasan dan dapat mengunggah ulang bukti." onClose={() => setRejecting(null)}
        fields={[{ name: 'reason_code', label: 'Alasan', type: 'select', required: true, options: lookups?.enums.proof_reject_reasons ?? [] }, { name: 'note', label: 'Keterangan untuk mahasiswa', type: 'textarea' }]}
        onSubmit={(v) => action.run(() => keuAdmin.post(`payment-proofs/${rejecting?.proof_id}/reject`, v), (r) => r.message).then(done)} />
    </KeuPage>
  )
}

interface Session { id: string; session_number: string; cashier: string | null; cashier_id: string; opened_at: string | null; closed_at: string | null; opening_float: string; system_total: string; counted_total: string | null; difference: string | null; deposit_reference: string | null; status: string; status_label: string; verified_by: string | null; note: string | null; is_open: boolean }
interface Account { student: StudentRef; summary: { outstanding: string; arrears: string; deposit_available: string }; invoices: Invoice[] }

/** Loket kasir: sesi kas, pembayaran tunai, tutup sesi & setoran. */
export function CashierPage() {
  const [reload, setReload] = useState(0)
  const sessions = useLoad(() => keuAdmin.list<Session>('cashier-sessions', { status: 'open' }), [reload])
  const action = useAction()
  const [opening, setOpening] = useState(false)
  const [closing, setClosing] = useState<Session | null>(null)
  const [depositing, setDepositing] = useState<Session | null>(null)
  const canVerify = usePermission(P.CASH_APPROVE)
  const canRecord = usePermission(P.TX_CREATE)
  const userId = useAuthStore((state) => state.session?.user.id)
  // Penyetuju melihat sesi semua kasir — sesi "milik saya" dicocokkan dengan akun yang login.
  const mine = sessions.data?.data.find((s) => s.is_open && s.cashier_id === userId) ?? null
  const refresh = () => setReload((x) => x + 1)

  return (
    <KeuPage title="Loket Kasir" description="Pembayaran tunai hanya dapat dicatat dalam sesi kas yang terbuka. Setiap pembayaran langsung menghasilkan kuitansi bernomor." trail={[{ label: 'Kasir' }]}
      actions={mine ? <Button variant="outline" leftIcon={<Lock className="size-4" />} onClick={() => setClosing(mine)}>Tutup sesi {mine.session_number}</Button> : <Can permission={P.CASH_CREATE}><Button leftIcon={<Unlock className="size-4" />} onClick={() => setOpening(true)}>Buka sesi kas</Button></Can>}>
      <Feedback state={action} />
      {mine ? (
        <Card>
          <div className="grid gap-4 sm:grid-cols-4">
            <Figure label="Sesi" value={mine.session_number} />
            <Figure label="Dibuka" value={dateTime(mine.opened_at)} />
            <Figure label="Kas awal" value={money(mine.opening_float)} />
            <Figure label="Penerimaan tunai (sistem)" value={money(mine.system_total)} tone="success" />
          </div>
        </Card>
      ) : (
        <Alert variant="info">Belum ada sesi kas terbuka atas nama Anda. Buka sesi untuk mulai menerima pembayaran tunai.</Alert>
      )}
      {mine && canRecord && <CashPayment session={mine} onRecorded={refresh} />}

      <KeuList<Session> path="cashier-sessions" search={false} reloadKey={reload} rowKey={(r) => r.id} itemLabel="sesi"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'open', label: 'Terbuka' }, { value: 'closed', label: 'Ditutup' }, { value: 'verified', label: 'Terverifikasi' }] }]}
        columns={[
          { header: 'Sesi', cell: (r) => <span>{r.session_number}<span className="block text-xs text-ink-tertiary">{r.cashier}</span></span> },
          { header: 'Dibuka / ditutup', cell: (r) => <span className="text-xs">{dateTime(r.opened_at)}<br />{dateTime(r.closed_at)}</span> },
          { header: 'Sistem', cell: (r) => money(r.system_total) },
          { header: 'Hitung fisik', cell: (r) => money(r.counted_total) },
          { header: 'Selisih', cell: (r) => r.difference === null ? '—' : <span className={Number(r.difference) !== 0 ? 'text-danger' : undefined}>{money(r.difference)}</span> },
          { header: 'Setoran', cell: (r) => r.deposit_reference ?? '—' },
          { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status_label} /> },
          { header: 'Aksi', cell: (r) => (
            <div className="flex gap-1">
              {r.status === 'closed' && !r.deposit_reference && <Button size="sm" variant="outline" onClick={() => setDepositing(r)}>Catat setoran</Button>}
              {r.status === 'closed' && r.deposit_reference && canVerify && <Button size="sm" onClick={() => action.run(() => keuAdmin.post(`cashier-sessions/${r.id}/verify`), (x) => x.message).then((x) => x && refresh())}>Verifikasi setoran</Button>}
              <Link className="text-sm text-primary" to={`${KEU.admin.transactions}`}>Transaksi</Link>
            </div>
          ) },
        ]} />

      <FormDialog open={opening} title="Buka sesi kas" onClose={() => setOpening(false)} fields={[{ name: 'opening_float', label: 'Kas awal / uang kembalian (Rp)', type: 'number', required: true }]}
        onSubmit={(v) => action.run(() => keuAdmin.post('cashier-sessions', v), (r) => r.message).then((r) => r && refresh())} />
      <FormDialog open={closing !== null} title="Tutup sesi kas" onClose={() => setClosing(null)}
        description={closing ? `Seharusnya ada ${money(Number(closing.opening_float) + Number(closing.system_total))} (kas awal + penerimaan, dikurangi transaksi yang dibalik). Selisih dicatat tanpa mengubah transaksi.` : undefined}
        fields={[{ name: 'counted_total', label: 'Hasil hitung fisik (Rp)', type: 'number', required: true }, { name: 'note', label: 'Catatan (wajib bila ada selisih)', type: 'textarea' }]}
        onSubmit={(v) => action.run(() => keuAdmin.post(`cashier-sessions/${closing?.id}/close`, v), (r) => r.message).then((r) => r && refresh())} />
      <FormDialog open={depositing !== null} title="Catat setoran bank" onClose={() => setDepositing(null)}
        fields={[{ name: 'deposit_reference', label: 'Nomor referensi setoran', required: true }, { name: 'file', label: 'Bukti setoran (PDF/JPG/PNG, opsional)', type: 'file', accept: '.pdf,.jpg,.jpeg,.png' }]}
        onSubmit={async (v) => {
          const fileId = v.file instanceof File ? await uploadFile(v.file) : null
          await action.run(() => keuAdmin.post(`cashier-sessions/${depositing?.id}/deposit`, { deposit_reference: v.deposit_reference, deposit_file_id: fileId }), (r) => r.message).then((r) => r && refresh())
        }} />
    </KeuPage>
  )
}

function CashPayment({ session, onRecorded }: { session: Session; onRecorded: () => void }) {
  const [student, setStudent] = useState<StudentRef | null>(null)
  const account = useLoad(() => (student ? keuAdmin.get<Account>(`students/${student.id}`) : Promise.resolve(null)), [student?.id])
  const [target, setTarget] = useState('')
  const [amount, setAmount] = useState('')
  const [payer, setPayer] = useState('')
  const [busy, setBusy] = useState(false)
  const [result, setResult] = useState<{ variant: 'success' | 'danger'; text: string; tx?: Transaction } | null>(null)
  const openInvoices = (account.data?.invoices ?? []).filter((i) => i.status === 'unpaid' || i.status === 'partial')
  const targets = openInvoices.flatMap((inv) => [
    { value: `invoice:${inv.id}`, label: `${inv.invoice_number} · ${inv.bill_group_label} ${inv.period} — sisa ${money(inv.outstanding)}`, outstanding: inv.outstanding },
    ...(inv.installments ?? []).filter((t) => Number(t.remaining) > 0).map((t) => ({ value: `installment:${t.id}`, label: `   ↳ Termin ${t.seq} (${date(t.due_date)}) — sisa ${money(t.remaining)}`, outstanding: t.remaining })),
  ])

  async function submit() {
    const [type, id] = target.split(':')
    setBusy(true)
    setResult(null)

    try {
      const { data, message } = await keuAdmin.post<Transaction>(`cashier-sessions/${session.id}/payments`, { student_id: student?.id, target_type: type, target_id: id ?? null, amount, payer_name: payer || null })
      setResult({ variant: 'success', text: message, tx: data })
      setAmount('')
      setTarget('')
      account.reload()
      onRecorded()
    } catch (err) {
      setResult({ variant: 'danger', text: describeError(err as NormalizedApiError) })
    } finally {
      setBusy(false)
    }
  }

  return (
    <Card title="Terima pembayaran tunai">
      <div className="flex flex-col gap-4">
        <StudentPicker value={student} onChange={(s) => { setStudent(s); setTarget(''); setResult(null) }} />
        {account.data && (
          <div className="grid gap-3 sm:grid-cols-3">
            <Figure label="Total sisa tagihan" value={money(account.data.summary.outstanding)} tone={Number(account.data.summary.outstanding) > 0 ? 'danger' : undefined} />
            <Figure label="Tunggakan" value={money(account.data.summary.arrears)} />
            <Figure label="Saldo deposit" value={money(account.data.summary.deposit_available)} />
          </div>
        )}
        {student && (
          <div className="grid gap-3 sm:grid-cols-3">
            <Select label="Bayar untuk *" placeholder="Pilih tagihan/termin" options={[...targets.map(({ value, label }) => ({ value, label })), { value: 'deposit:', label: 'Titipan (masuk deposit)' }]} value={target}
              onChange={(e) => { setTarget(e.target.value); setAmount(targets.find((t) => t.value === e.target.value)?.outstanding ?? '') }} />
            <Input label="Nominal diterima (Rp) *" type="number" min={1} value={amount} onChange={(e) => setAmount(e.target.value)} hint="Boleh sebagian. Kelebihan otomatis masuk deposit." />
            <Input label="Nama penyetor" placeholder="Bila bukan mahasiswa" value={payer} onChange={(e) => setPayer(e.target.value)} />
          </div>
        )}
        {result && <Alert variant={result.variant}>{result.text}{result.tx && <Button size="sm" variant="outline" className="ml-3" leftIcon={<Printer className="size-4" />} onClick={() => keuAdmin.openPdf(`transactions/${result.tx!.id}/receipt`)}>Cetak kuitansi</Button>}</Alert>}
        <div className="flex justify-end"><Button isLoading={busy} disabled={!student || !target || !amount} onClick={submit}>Catat pembayaran</Button></div>
      </div>
    </Card>
  )
}
