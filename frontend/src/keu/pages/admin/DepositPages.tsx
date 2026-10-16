import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Eye, Plus } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { DataTable } from '@/components/ui/DataTable'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, describeError, useAction } from '@/pmb/components/useAction'
import { FormDialog } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { Tabs } from '@/pmb/components/Tabs'
import { date, dateTime, money } from '@/pmb/lib/format'
import { usePermission } from '@/hooks/usePermission'
import { keuAdmin, uploadFile } from '@/keu/api'
import { Can, Figure, KeuBadge, KeuList, KeuPage, StudentPicker } from '@/keu/components'
import { P } from '@/keu/permissions'
import { useKeuLookups } from '@/keu/lookups'
import { KEU, to } from '@/keu/routes'
import { invoiceColumns, transactionColumns } from '@/keu/columns'
import type { Award, Clearance, Invoice, Refund, StatementEntry, StudentRef, Transaction } from '@/keu/types'

const RELATIONS: Record<string, string> = { self: 'mahasiswa', parent: 'orang tua/wali', other: 'pihak lain' }

type DepTab = 'accounts' | 'refunds'

/** Saldo deposit (F-11) & pengembalian dana (F-13). Refund selalu dibayar dari saldo deposit. */
export function DepositsPage() {
  const canDeposit = usePermission(P.DEPOSIT_READ)
  const canRefund = usePermission(P.REFUND_READ)
  const [tab, setTab] = useState<DepTab>(canDeposit ? 'accounts' : 'refunds')

  return (
    <KeuPage title="Deposit & Pengembalian Dana" description="Kelebihan bayar, pembayaran ganda, dan koreksi tagihan menjadi saldo deposit mahasiswa. Saldo dapat dipakai untuk tagihan berikutnya atau dikembalikan lewat pengajuan refund." trail={[{ label: 'Deposit' }]}>
      <Tabs<DepTab> active={tab} onChange={setTab} tabs={[...(canDeposit ? [{ key: 'accounts' as DepTab, label: 'Saldo deposit' }] : []), ...(canRefund ? [{ key: 'refunds' as DepTab, label: 'Pengembalian dana' }] : [])]} />
      {tab === 'accounts' && canDeposit && (
        <KeuList<{ id: string; student: StudentRef | null; balance: string; held_amount: string; available: string; updated_at: string | null }> path="deposits" rowKey={(r) => r.id} itemLabel="akun"
          filters={[{ name: 'include_empty', label: 'Saldo nol', type: 'select', options: [{ value: '1', label: 'Tampilkan' }] }]}
          columns={[
            { header: 'Mahasiswa', cell: (r) => r.student ? <Link className="text-primary" to={to(KEU.admin.student, { id: r.student.id })}>{r.student.nim}<span className="block text-xs text-ink-tertiary">{r.student.name} · {r.student.study_program}</span></Link> : '—' },
            { header: 'Saldo', cell: (r) => money(r.balance) },
            { header: 'Ditahan (refund)', cell: (r) => money(r.held_amount) },
            { header: 'Tersedia', cell: (r) => <strong>{money(r.available)}</strong> },
            { header: 'Mutasi terakhir', cell: (r) => dateTime(r.updated_at) },
          ]} />
      )}
      {tab === 'refunds' && canRefund && <Refunds />}
    </KeuPage>
  )
}

function Refunds() {
  const lookups = useKeuLookups()
  const [reload, setReload] = useState(0)
  const [creating, setCreating] = useState(false)
  const [student, setStudent] = useState<StudentRef | null>(null)
  const [deciding, setDeciding] = useState<{ refund: Refund; mode: 'reject' | 'complete' | 'fail' } | null>(null)
  const [revealed, setRevealed] = useState<Record<string, string>>({})
  const canApprove = usePermission(P.REFUND_APPROVE)
  const canFinalize = usePermission(P.REFUND_FINALIZE)
  const action = useAction()
  const done = (r: unknown) => r && setReload((x) => x + 1)

  return (
    <>
      <Feedback state={action} />
      <KeuList<Refund> path="refunds" search={false} reloadKey={reload} rowKey={(r) => r.id} itemLabel="pengajuan"
        toolbar={<Can permission={P.REFUND_CREATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Ajukan refund</Button></Can>}
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [
          { value: 'pending_approval', label: 'Menunggu persetujuan' }, { value: 'approved', label: 'Disetujui, menunggu transfer' }, { value: 'completed', label: 'Selesai' },
          { value: 'rejected', label: 'Ditolak' }, { value: 'failed', label: 'Gagal' }, { value: 'cancelled', label: 'Dibatalkan' },
        ] }]}
        columns={[
          { header: 'Nomor', cell: (r) => <span>{r.refund_number}<span className="block text-xs text-ink-tertiary">{dateTime(r.created_at)}</span></span> },
          { header: 'Mahasiswa', cell: (r) => <span>{r.student?.nim}<span className="block text-xs text-ink-tertiary">{r.student?.name}</span></span> },
          { header: 'Nominal', cell: (r) => money(r.amount) },
          { header: 'Alasan', cell: (r) => <span className="whitespace-normal text-xs">{lookups?.enums.refund_reasons.find((o) => o.value === r.reason_code)?.label ?? r.reason_code}: {r.reason_note}</span> },
          { header: 'Rekening tujuan', cell: (r) => <span className="text-xs">{r.beneficiary_bank} · {revealed[r.id] ?? r.beneficiary_account}<br />{r.beneficiary_name} ({RELATIONS[r.beneficiary_relation] ?? r.beneficiary_relation})
            {(canApprove || canFinalize) && !revealed[r.id] && <Button size="sm" variant="ghost" aria-label="Tampilkan nomor rekening" onClick={() => action.run(() => keuAdmin.get<Refund>(`refunds/${r.id}`, { reveal: 1 })).then((x) => x && setRevealed((m) => ({ ...m, [r.id]: x.beneficiary_account })))}><Eye className="size-3.5" /></Button>}
          </span> },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{(r.decision_note || r.failure_reason || r.execution_reference) && <span className="block max-w-xs whitespace-normal text-xs text-ink-tertiary">{r.execution_reference ?? r.failure_reason ?? r.decision_note}</span>}</span> },
          { header: 'Aksi', cell: (r) => <div className="flex flex-wrap gap-1">
            {r.status === 'pending_approval' && canApprove && <><Button size="sm" onClick={() => action.run(() => keuAdmin.post(`refunds/${r.id}/approve`), (x) => x.message).then(done)}>Setujui</Button><Button size="sm" variant="outline" onClick={() => setDeciding({ refund: r, mode: 'reject' })}>Tolak</Button></>}
            {r.status === 'pending_approval' && <Can permission={P.REFUND_CREATE}><Button size="sm" variant="ghost" onClick={() => action.run(() => keuAdmin.post(`refunds/${r.id}/cancel`), (x) => x.message).then(done)}>Batalkan</Button></Can>}
            {r.status === 'approved' && canFinalize && <><Button size="sm" onClick={() => setDeciding({ refund: r, mode: 'complete' })}>Catat transfer</Button><Button size="sm" variant="outline" onClick={() => setDeciding({ refund: r, mode: 'fail' })}>Gagal</Button></>}
          </div> },
        ]} />
      <FormDialog open={creating} title="Ajukan pengembalian dana" wide onClose={() => { setCreating(false); setStudent(null) }} prepend={<StudentPicker value={student} onChange={setStudent} />}
        description="Dana diambil dari saldo deposit tersedia dan langsung ditahan sampai diputuskan. Transfer dilakukan di luar sistem (internet banking kampus) lalu dicatat di sini."
        fields={[
          { name: 'amount', label: 'Nominal (Rp)', type: 'number', required: true },
          { name: 'reason_code', label: 'Alasan', type: 'select', required: true, options: lookups?.enums.refund_reasons ?? [] },
          { name: 'reason_note', label: 'Penjelasan', type: 'textarea', required: true },
          { name: 'beneficiary_bank', label: 'Bank tujuan', required: true },
          { name: 'beneficiary_account', label: 'Nomor rekening (6–20 digit)', required: true },
          { name: 'beneficiary_name', label: 'Nama pemilik rekening', required: true },
          { name: 'beneficiary_relation', label: 'Hubungan pemilik rekening', type: 'select', required: true, options: [{ value: 'self', label: 'Mahasiswa sendiri' }, { value: 'parent', label: 'Orang tua/wali' }, { value: 'other', label: 'Pihak lain (perlu surat kuasa)' }] },
          { name: 'power_of_attorney', label: 'Surat kuasa sudah diterima & diperiksa', type: 'checkbox', visible: (v) => v.beneficiary_relation === 'other' },
        ]}
        onSubmit={async (v) => {
          if (!student) throw { status: 422, message: 'Pilih mahasiswa terlebih dahulu.' }
          await keuAdmin.post('refunds', { ...v, student_id: student.id })
          setStudent(null)
          setReload((x) => x + 1)
        }} />
      <ReasonDialog open={deciding?.mode === 'reject'} title="Tolak pengembalian dana" reasonName="note" description="Saldo yang ditahan dilepas kembali ke deposit." onClose={() => setDeciding(null)}
        onConfirm={(v) => action.run(() => keuAdmin.post(`refunds/${deciding?.refund.id}/reject`, v), (x) => x.message).then(done)} />
      <ReasonDialog open={deciding?.mode === 'fail'} title="Transfer refund gagal" description="Dana dikembalikan ke saldo deposit mahasiswa." onClose={() => setDeciding(null)}
        onConfirm={(v) => action.run(() => keuAdmin.post(`refunds/${deciding?.refund.id}/fail`, v), (x) => x.message).then(done)} />
      <FormDialog open={deciding?.mode === 'complete'} title="Catat transfer refund" onClose={() => setDeciding(null)}
        fields={[{ name: 'execution_reference', label: 'Nomor referensi transfer', required: true }, { name: 'proof', label: 'Bukti transfer (PDF/JPG/PNG)', type: 'file', accept: '.pdf,.jpg,.jpeg,.png' }]}
        onSubmit={async (v) => {
          const proofId = v.proof instanceof File ? await uploadFile(v.proof) : null
          await action.run(() => keuAdmin.post(`refunds/${deciding?.refund.id}/complete`, { execution_reference: v.execution_reference, proof_file_id: proofId }), (x) => x.message).then(done)
        }} />
    </>
  )
}

interface StudentAccount {
  student: StudentRef
  summary: { outstanding: string; arrears: string; deposit_available: string }
  clearance: Clearance
  invoices: Invoice[]
  transactions: Transaction[]
  awards: Award[]
  ukt: Array<{ group: string | null; status: string; basis: string; created_at: string | null }>
  notes: Array<{ id: string; channel: string; note: string; promise_amount: string | null; promise_date: string | null; created_by: string | null; created_at: string | null }>
}

type AccTab = 'invoices' | 'transactions' | 'statement' | 'deposit' | 'aid' | 'notes'

const NOTE_CHANNELS = [{ value: 'phone', label: 'Telepon' }, { value: 'whatsapp', label: 'WhatsApp' }, { value: 'email', label: 'Email' }, { value: 'visit', label: 'Tatap muka' }, { value: 'letter', label: 'Surat' }, { value: 'other', label: 'Lainnya' }]

/** Profil keuangan satu mahasiswa: piutang, transaksi, kartu piutang, deposit, beasiswa, catatan penagihan. */
export function StudentAccountPage() {
  const { id = '' } = useParams()
  const { data, error, reload } = useLoad(() => keuAdmin.get<StudentAccount>(`students/${id}`), [id])
  const [tab, setTab] = useState<AccTab>('invoices')
  const statement = useLoad(() => (tab === 'statement' ? keuAdmin.get<{ entries: StatementEntry[]; balance: string }>(`students/${id}/statement`) : Promise.resolve(null)), [id, tab])
  const ledger = useLoad(() => (tab === 'deposit' ? keuAdmin.get<{ balance: string; held_amount: string; entries: Array<{ id: string; entry_type_label: string; amount: string; balance_after: string; held_after: string; note: string | null; created_by: string | null; created_at: string | null }> }>(`deposits/${id}`) : Promise.resolve(null)), [id, tab])
  const [noting, setNoting] = useState(false)
  const canDeposit = usePermission(P.DEPOSIT_READ)

  if (error) return <KeuPage title="Profil keuangan" trail={[{ label: 'Mahasiswa' }]}><Alert variant="danger">{describeError(error)}</Alert></KeuPage>
  if (!data) return <p className="text-sm text-ink-secondary">Memuat...</p>
  const s = data.student

  return (
    <KeuPage title={`${s.nim} — ${s.name}`} description={`${s.study_program ?? '—'} · Angkatan ${s.admission_year ?? '—'} · ${s.status_label ?? s.status}`} trail={[{ label: 'Profil keuangan mahasiswa' }]}
      actions={<Can permission={P.INVOICES_UPDATE}><Button variant="outline" onClick={() => setNoting(true)}>Catatan penagihan</Button></Can>}>
      <Card>
        <div className="grid gap-4 sm:grid-cols-5">
          <Figure label="Sisa tagihan" value={money(data.summary.outstanding)} tone={Number(data.summary.outstanding) > 0 ? 'danger' : 'success'} />
          <Figure label="Tunggakan" value={money(data.summary.arrears)} tone={Number(data.summary.arrears) > 0 ? 'danger' : undefined} />
          <Figure label="Deposit tersedia" value={money(data.summary.deposit_available)} />
          <Figure label="Denda berjalan" value={money(data.clearance.accruing_penalty)} />
          <Figure label="Bebas tanggungan" value={data.clearance.clear ? 'Ya' : 'Belum'} tone={data.clearance.clear ? 'success' : 'warning'} />
        </div>
        {data.clearance.pending_verification > 0 && <Alert variant="info" className="mt-3">{data.clearance.pending_verification} bukti transfer menunggu verifikasi.</Alert>}
      </Card>
      <Tabs<AccTab> active={tab} onChange={setTab} tabs={[
        { key: 'invoices', label: 'Tagihan', badge: data.invoices.length }, { key: 'transactions', label: 'Transaksi', badge: data.transactions.length },
        { key: 'statement', label: 'Kartu piutang' }, ...(canDeposit ? [{ key: 'deposit' as AccTab, label: 'Mutasi deposit' }] : []),
        { key: 'aid', label: 'Beasiswa & UKT' }, { key: 'notes', label: 'Catatan penagihan', badge: data.notes.length },
      ]} />
      {tab === 'invoices' && <Card noPadding><DataTable rowKey={(r) => r.id} data={data.invoices} emptyMessage="Belum ada tagihan." columns={invoiceColumns().filter((c) => c.header !== 'Mahasiswa')} /></Card>}
      {tab === 'transactions' && <Card noPadding><DataTable rowKey={(r) => r.id} data={data.transactions} emptyMessage="Belum ada transaksi." columns={transactionColumns().filter((c) => c.header !== 'Mahasiswa')} /></Card>}
      {tab === 'statement' && (
        <Card noPadding title={`Saldo akhir: ${money(statement.data?.balance)}`}>
          <DataTable rowKey={(r) => `${r.date}-${r.type}-${r.amount}-${r.balance}`} data={statement.data?.entries ?? []} emptyMessage={statement.loading ? 'Memuat...' : 'Belum ada mutasi.'} columns={[
            { header: 'Tanggal', cell: (r) => dateTime(r.date) }, { header: 'Tagihan', cell: (r) => r.invoice_number }, { header: 'Jenis', cell: (r) => r.type },
            { header: 'Uraian', cell: (r) => <span className="whitespace-normal text-xs">{r.description}</span> },
            { header: 'Debit/Kredit', cell: (r) => <span className={Number(r.amount) < 0 ? 'text-primary' : undefined}>{money(r.amount)}</span> }, { header: 'Saldo', cell: (r) => money(r.balance) },
          ]} />
        </Card>
      )}
      {tab === 'deposit' && (
        <Card noPadding title={`Saldo ${money(ledger.data?.balance)} · ditahan ${money(ledger.data?.held_amount)}`}>
          <DataTable rowKey={(r) => r.id} data={ledger.data?.entries ?? []} emptyMessage={ledger.loading ? 'Memuat...' : 'Belum ada mutasi deposit.'} columns={[
            { header: 'Waktu', cell: (r) => dateTime(r.created_at) }, { header: 'Jenis', cell: (r) => r.entry_type_label }, { header: 'Mutasi', cell: (r) => money(r.amount) },
            { header: 'Saldo', cell: (r) => money(r.balance_after) }, { header: 'Ditahan', cell: (r) => money(r.held_after) }, { header: 'Keterangan', cell: (r) => <span className="text-xs">{r.note} {r.created_by && `· ${r.created_by}`}</span> },
          ]} />
        </Card>
      )}
      {tab === 'aid' && (
        <div className="grid gap-4 lg:grid-cols-2">
          <Card noPadding title="Beasiswa & potongan">
            <DataTable rowKey={(r) => r.id} data={data.awards} emptyMessage="Tidak ada." columns={[
              { header: 'Jenis', cell: (r) => r.policy?.name }, { header: 'Besaran', cell: (r) => r.calc_type === 'percent' ? `${Number(r.value)}%` : money(r.value) },
              { header: 'Berlaku', cell: (r) => `${r.start_term ?? '—'} s.d. ${r.end_term ?? '…'}` }, { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status_label} /> },
            ]} />
          </Card>
          <Card noPadding title="Golongan UKT">
            <DataTable rowKey={(r) => `${r.created_at}-${r.group}`} data={data.ukt} emptyMessage="Belum ditetapkan." columns={[
              { header: 'Golongan', cell: (r) => r.group }, { header: 'Dasar', cell: (r) => <span className="whitespace-normal text-xs">{r.basis}</span> },
              { header: 'Status', cell: (r) => r.status === 'active' ? 'Berlaku' : 'Riwayat' }, { header: 'Tanggal', cell: (r) => date(r.created_at) },
            ]} />
          </Card>
        </div>
      )}
      {tab === 'notes' && (
        <Card>
          {data.notes.length === 0 ? <p className="text-sm text-ink-secondary">Belum ada catatan penagihan.</p> : (
            <ul className="divide-y divide-border">
              {data.notes.map((n) => (
                <li key={n.id} className="py-3 text-sm">
                  <p className="text-xs text-ink-tertiary">{dateTime(n.created_at)} · {NOTE_CHANNELS.find((c) => c.value === n.channel)?.label} · {n.created_by}</p>
                  <p className="mt-1">{n.note}</p>
                  {n.promise_amount && <p className="mt-1 text-xs">Janji bayar {money(n.promise_amount)} pada {date(n.promise_date)}</p>}
                </li>
              ))}
            </ul>
          )}
        </Card>
      )}
      <FormDialog open={noting} title="Catatan penagihan" onClose={() => setNoting(false)}
        fields={[
          { name: 'channel', label: 'Media kontak', type: 'select', required: true, options: NOTE_CHANNELS },
          { name: 'note', label: 'Catatan', type: 'textarea', required: true },
          { name: 'promise_amount', label: 'Janji bayar (Rp)', type: 'number' },
          { name: 'promise_date', label: 'Tanggal janji bayar', type: 'date' },
        ]}
        onSubmit={async (v) => { await keuAdmin.post(`students/${id}/collection-notes`, v); reload() }} />
    </KeuPage>
  )
}
