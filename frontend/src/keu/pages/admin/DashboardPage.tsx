import { useState } from 'react'
import { Link } from 'react-router-dom'
import { AlertTriangle, Banknote, Clock, FileCheck2, Landmark, Percent, ShieldAlert, Wallet } from 'lucide-react'
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { StatCard } from '@/components/ui/StatCard'
import { Card } from '@/components/ui/Card'
import { Alert } from '@/components/ui/Alert'
import { Select } from '@/components/ui/Select'
import { Input } from '@/components/ui/Input'
import { DataTable } from '@/components/ui/DataTable'
import { useLoad } from '@/pmb/components/useLoad'
import { describeError } from '@/pmb/components/useAction'
import { Tabs } from '@/pmb/components/Tabs'
import { money, date, dateTime } from '@/pmb/lib/format'
import { keuAdmin } from '@/keu/api'
import { KeuPage } from '@/keu/components'
import { opts, useKeuLookups } from '@/keu/lookups'
import { KEU, to } from '@/keu/routes'

interface Breakdown { label: string; invoices: number; billed: string; paid: string; outstanding: string; arrears: string }
interface Dashboard {
  filters: Record<string, string>
  summary: {
    billed: string; gross: string; discount: string; paid_on_invoices: string; received: string; received_count: number; receivable: string; arrears: string
    overdue_invoices: number; due_soon_count: number; due_soon_amount: string; collection_rate: number | null; deposit_balance: string; pending_proofs: number
    stale_online: number; reconciliation_exceptions: number; refunds_pending: number; blocked_students: number; pending_approvals: number
  }
  by_faculty: Breakdown[]; by_program: Breakdown[]; by_cohort: Breakdown[]; by_student_status: Breakdown[]
  receipts_trend: Array<{ date: string; amount: string; count: number }>
  aging: Array<{ bucket: string; count: number; amount: string }>
  recent_transactions: Array<{ id: string; transaction_number: string; receipt_number: string | null; amount: string; method: string; paid_at: string; nim: string; student_name: string }>
  follow_up: Array<{ id: string; invoice_number: string; period: string; due_date: string; overdue_since: string | null; outstanding: string; student_id: string; nim: string; student_name: string }>
}

type Dim = 'by_program' | 'by_faculty' | 'by_cohort' | 'by_student_status'

/** Dashboard Keuangan (F-14): angka dari transaksi & tagihan nyata, difilter. */
export function DashboardPage() {
  const lookups = useKeuLookups()
  const [filters, setFilters] = useState<Record<string, string>>({})
  const [dim, setDim] = useState<Dim>('by_program')
  const { data, loading, error } = useLoad(() => keuAdmin.get<Dashboard>('dashboard', filters), [JSON.stringify(filters)])
  const s = data?.summary
  const set = (key: string, value: string) => setFilters((f) => ({ ...f, [key]: value }))

  return (
    <KeuPage title="Dashboard Keuangan" description="Ringkasan tagihan, penerimaan, piutang, dan pekerjaan yang menunggu tindak lanjut.">
      <Card>
        <div className="flex flex-wrap items-end gap-3">
          <Select label="Semester" placeholder="Semua" options={opts(lookups?.academic_terms, (t) => t.label)} value={filters.academic_term_id ?? ''} onChange={(e) => set('academic_term_id', e.target.value)} />
          <Select label="Fakultas" placeholder="Semua" options={opts(lookups?.faculties, (f) => f.name)} value={filters.faculty_id ?? ''} onChange={(e) => set('faculty_id', e.target.value)} />
          <Select label="Program studi" placeholder="Semua" options={opts(lookups?.study_programs, (p) => p.name)} value={filters.study_program_id ?? ''} onChange={(e) => set('study_program_id', e.target.value)} />
          <Input label="Angkatan" type="number" value={filters.admission_year ?? ''} onChange={(e) => set('admission_year', e.target.value)} />
          <Input label="Penerimaan dari" type="date" value={filters.date_from ?? ''} onChange={(e) => set('date_from', e.target.value)} />
          <Input label="sampai" type="date" value={filters.date_to ?? ''} onChange={(e) => set('date_to', e.target.value)} />
        </div>
      </Card>

      {error && <Alert variant="danger">{describeError(error)}</Alert>}
      {loading && !data && <p className="text-sm text-ink-secondary">Memuat...</p>}

      {s && data && (
        <>
          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Total tagihan (neto)" value={money(s.billed)} icon={Landmark} caption={`Bruto ${money(s.gross)} · potongan ${money(s.discount)}`} />
            <StatCard label="Penerimaan periode" value={money(s.received)} icon={Banknote} caption={`${s.received_count} transaksi ${date(data.filters.date_from)} – ${date(data.filters.date_to)}`} />
            <StatCard label="Piutang berjalan" value={money(s.receivable)} icon={Wallet} caption={`Jatuh tempo ≤ 7 hari: ${s.due_soon_count} (${money(s.due_soon_amount)})`} />
            <StatCard label="Tunggakan" value={money(s.arrears)} icon={AlertTriangle} caption={`${s.overdue_invoices} tagihan lewat tenggang`} />
            <StatCard label="Tingkat kolektibilitas" value={s.collection_rate === null ? '—' : `${s.collection_rate}%`} icon={Percent} caption="Dibayar ÷ ditagihkan" />
            <StatCard label="Saldo deposit mahasiswa" value={money(s.deposit_balance)} icon={Wallet} />
            <StatCard label="Mahasiswa tercekal (keuangan)" value={String(s.blocked_students)} icon={ShieldAlert} />
            <StatCard label="Menunggu persetujuan" value={String(s.pending_approvals)} icon={Clock} caption="Penyesuaian, penerbitan, beasiswa, cicilan" />
          </div>

          <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <WorkItem to={KEU.admin.verification} label="Bukti transfer menunggu verifikasi" value={s.pending_proofs} />
            <WorkItem to={KEU.admin.transactions} label="Transaksi online pending > 24 jam" value={s.stale_online} />
            <WorkItem to={KEU.admin.reconciliations} label="Pengecualian rekonsiliasi terbuka" value={s.reconciliation_exceptions} />
            <WorkItem to={KEU.admin.deposits} label="Pengembalian dana berjalan" value={s.refunds_pending} />
          </div>

          <div className="grid gap-4 lg:grid-cols-2">
            <Card title="Tren penerimaan">
              {data.receipts_trend.length === 0 ? <p className="text-sm text-ink-secondary">Belum ada penerimaan pada rentang ini.</p> : (
                <div className="h-64">
                  <ResponsiveContainer width="100%" height="100%">
                    <BarChart data={data.receipts_trend.map((r) => ({ ...r, value: Number(r.amount) }))}>
                      <CartesianGrid strokeDasharray="3 3" vertical={false} />
                      <XAxis dataKey="date" fontSize={11} />
                      <YAxis fontSize={11} tickFormatter={(v: number) => `${Math.round(v / 1_000_000)} jt`} />
                      <Tooltip formatter={(v) => money(Number(v))} />
                      <Bar dataKey="value" name="Penerimaan" fill="currentColor" className="text-primary" />
                    </BarChart>
                  </ResponsiveContainer>
                </div>
              )}
            </Card>
            <Card title="Umur piutang (aging)" noPadding>
              <DataTable rowKey={(r) => r.bucket} data={data.aging} columns={[
                { header: 'Kelompok', cell: (r) => r.bucket },
                { header: 'Tagihan', cell: (r) => r.count },
                { header: 'Sisa', cell: (r) => money(r.amount) },
              ]} />
            </Card>
          </div>

          <Card noPadding title="Rincian per dimensi">
            <div className="px-4 pt-2">
              <Tabs<Dim> active={dim} onChange={setDim} tabs={[
                { key: 'by_program', label: 'Program studi' }, { key: 'by_faculty', label: 'Fakultas' }, { key: 'by_cohort', label: 'Angkatan' }, { key: 'by_student_status', label: 'Status mahasiswa' },
              ]} />
            </div>
            <DataTable rowKey={(r) => r.label} data={data[dim]} emptyMessage="Belum ada tagihan." columns={[
              { header: 'Kelompok', cell: (r) => r.label },
              { header: 'Tagihan', cell: (r) => r.invoices },
              { header: 'Ditagihkan', cell: (r) => money(r.billed) },
              { header: 'Dibayar', cell: (r) => money(r.paid) },
              { header: 'Piutang', cell: (r) => money(r.outstanding) },
              { header: 'Tunggakan', cell: (r) => <span className={Number(r.arrears) > 0 ? 'text-danger' : undefined}>{money(r.arrears)}</span> },
            ]} />
          </Card>

          <div className="grid gap-4 lg:grid-cols-2">
            <Card title="Penerimaan terbaru" noPadding>
              <DataTable rowKey={(r) => r.id} data={data.recent_transactions} emptyMessage="Belum ada penerimaan." columns={[
                { header: 'Kuitansi', cell: (r) => <Link className="text-primary" to={to(KEU.admin.transaction, { id: r.id })}>{r.receipt_number ?? r.transaction_number}</Link> },
                { header: 'Mahasiswa', cell: (r) => <span>{r.nim}<span className="block text-xs text-ink-tertiary">{r.student_name}</span></span> },
                { header: 'Nominal', cell: (r) => money(r.amount) },
                { header: 'Waktu', cell: (r) => dateTime(r.paid_at) },
              ]} />
            </Card>
            <Card title="Prioritas penagihan (tunggakan terbesar)" noPadding>
              <DataTable rowKey={(r) => r.id} data={data.follow_up} emptyMessage="Tidak ada tunggakan." columns={[
                { header: 'Mahasiswa', cell: (r) => <Link className="text-primary" to={to(KEU.admin.student, { id: r.student_id })}>{r.nim}<span className="block text-xs text-ink-tertiary">{r.student_name}</span></Link> },
                { header: 'Tagihan', cell: (r) => <Link className="text-primary" to={to(KEU.admin.invoice, { id: r.id })}>{r.invoice_number}</Link> },
                { header: 'Sisa', cell: (r) => money(r.outstanding) },
                { header: 'Menunggak sejak', cell: (r) => date(r.overdue_since) },
              ]} />
            </Card>
          </div>
        </>
      )}
    </KeuPage>
  )
}

function WorkItem({ to: path, label, value }: { to: string; label: string; value: number }) {
  return (
    <Link to={path} className="flex items-center justify-between rounded-xl border border-border bg-surface p-4 shadow-card hover:shadow-popover">
      <span className="flex items-center gap-2 text-sm text-ink-secondary"><FileCheck2 className="size-4" aria-hidden />{label}</span>
      <span className={`text-lg font-semibold ${value > 0 ? 'text-amber-600' : 'text-ink-tertiary'}`}>{value}</span>
    </Link>
  )
}
