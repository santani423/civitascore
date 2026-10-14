import { useState } from 'react'
import { Link } from 'react-router-dom'
import { CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { BadgeCheck, CircleDollarSign, FileCheck2, GraduationCap, UserPlus, Users } from 'lucide-react'
import { pmbAdmin } from '@/pmb/api/admin'
import { useLoad } from '@/pmb/components/useLoad'
import { AdminPage, Can, P, useLookups, options } from '@/pmb/components/admin'
import { StatCard } from '@/components/ui/StatCard'
import { Card } from '@/components/ui/Card'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { DataTable } from '@/components/ui/DataTable'
import { money } from '@/pmb/lib/format'
import { statusLabel } from '@/pmb/lib/labels'
import { PMB } from '@/pmb/routes'

interface Summary {
  kpi_01_accounts: number
  kpi_02_verified_accounts: number
  kpi_03_applications: number
  kpi_04_submitted: number
  kpi_06_incomplete: number
  kpi_07_documents_pending: number
  kpi_08_registration_payment: { by_status: Record<string, number>; paid_percent: number | null }
  kpi_09_selection_participants: number
  kpi_10_decisions: Record<string, number>
  kpi_11_re_registration: Record<string, number>
  kpi_12_conversion: Record<string, number | null>
  kpi_13_quota: Array<{ quota_id: string; program: string; path: string; system: string; capacity: number; accepted: number; enrolled: number; attainment: number | null }>
  kpi_14_revenue: { total: string; by_type: Record<string, string>; by_channel: Record<string, string>; refunded: string; initial_tuition_receivable: string }
  kpi_17_info_sources: Array<{ info_source: string; submitted: number; enrolled: number; conversion: number }>
  kpi_18_verification_sla: { median_hours: number | null; p90_hours: number | null; sample: number }
  kpi_19_pddikti_completeness: { percent: number | null; enrolled: number; complete: number }
  funnel: Array<{ stage: string; value: number }>
}

const FUNNEL: Record<string, string> = { accounts: 'Akun', submitted: 'Formulir terkirim', paid: 'Bayar pendaftaran', verified: 'Terverifikasi', accepted: 'Diterima', enrolled: 'Resmi mahasiswa' }

/** ADM-01/06 Dashboard PMB: KPI, funnel, tren harian, capaian kuota (ber-scope). */
export function DashboardPage() {
  const lookups = useLookups()
  const [period, setPeriod] = useState('')
  const summary = useLoad(() => pmbAdmin.get<Summary>('dashboard/summary', { period_id: period }), [period])
  const today = new Date()
  const from = new Date(today.getTime() - 29 * 86_400_000).toISOString().slice(0, 10)
  const trend = useLoad(() => pmbAdmin.get<Record<string, Array<{ date: string; value: number }>>>('dashboard/trend', { period_id: period, from, to: today.toISOString().slice(0, 10) }), [period])
  const s = summary.data
  const chart = (trend.data?.submitted ?? []).map((point, i) => ({ date: point.date.slice(5), Akun: trend.data?.accounts?.[i]?.value ?? 0, Terkirim: point.value, Bayar: trend.data?.paid?.[i]?.value ?? 0, Mahasiswa: trend.data?.enrolled?.[i]?.value ?? 0 }))

  return (
    <AdminPage title="Dashboard PMB" description="Ringkasan penerimaan mahasiswa baru." actions={<Select options={options(lookups?.periods, (p) => p.name)} placeholder="Semua periode" value={period} onChange={(e) => setPeriod(e.target.value)} />}>
      {summary.error && <Alert variant="danger">{summary.error.message}</Alert>}
      {s && (
        <>
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <StatCard label="Akun pendaftar" value={String(s.kpi_01_accounts)} icon={UserPlus} caption={`${s.kpi_02_verified_accounts} terverifikasi`} />
            <StatCard label="Formulir terkirim" value={String(s.kpi_04_submitted)} icon={FileCheck2} caption={`${s.kpi_06_incomplete} draf belum dikirim`} />
            <StatCard label="Bayar pendaftaran" value={`${s.kpi_08_registration_payment.paid_percent ?? 0}%`} icon={CircleDollarSign} caption={`${s.kpi_08_registration_payment.by_status.paid ?? 0} lunas`} />
            <StatCard label="Peserta seleksi" value={String(s.kpi_09_selection_participants)} icon={Users} caption={`${s.kpi_07_documents_pending} dokumen menunggu tinjauan`} />
            <StatCard label="Diterima" value={String(s.kpi_10_decisions.accepted ?? 0)} icon={BadgeCheck} caption={`${s.kpi_10_decisions.waitlisted ?? 0} cadangan · ${s.kpi_10_decisions.rejected ?? 0} tidak diterima`} />
            <StatCard label="Resmi mahasiswa" value={String(s.kpi_11_re_registration.enrolled ?? 0)} icon={GraduationCap} caption={`Yield ${s.kpi_12_conversion.yield ?? 0}%`} />
          </div>
          <div className="grid gap-4 lg:grid-cols-2">
            <Card title="Funnel">
              <div className="space-y-2">
                {s.funnel.map((f) => {
                  const max = Math.max(1, ...s.funnel.map((x) => x.value))

                  return <div key={f.stage}><div className="flex justify-between text-sm"><span>{FUNNEL[f.stage] ?? f.stage}</span><span className="font-medium">{f.value}</span></div><div className="h-2 rounded bg-surface-hover"><div className="h-2 rounded bg-primary" style={{ width: `${(f.value / max) * 100}%` }} /></div></div>
                })}
              </div>
            </Card>
            <Card title="Tren 30 hari">
              <div className="h-64">
                <ResponsiveContainer>
                  <LineChart data={chart}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="date" fontSize={11} />
                    <YAxis allowDecimals={false} fontSize={11} />
                    <Tooltip />
                    <Legend />
                    <Line type="monotone" dataKey="Akun" stroke="#94a3b8" dot={false} />
                    <Line type="monotone" dataKey="Terkirim" stroke="#0f766e" dot={false} />
                    <Line type="monotone" dataKey="Bayar" stroke="#d97706" dot={false} />
                    <Line type="monotone" dataKey="Mahasiswa" stroke="#2563eb" dot={false} />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </Card>
          </div>
          <Card title="Capaian kuota" noPadding>
            <DataTable rowKey={(r) => r.quota_id} data={s.kpi_13_quota} emptyMessage="Belum ada kuota." columns={[
              { header: 'Prodi', cell: (r) => r.program }, { header: 'Jalur', cell: (r) => r.path }, { header: 'Sistem', cell: (r) => r.system },
              { header: 'Kapasitas', cell: (r) => r.capacity }, { header: 'Diterima aktif', cell: (r) => r.accepted }, { header: 'Mahasiswa', cell: (r) => r.enrolled },
              { header: 'Capaian', cell: (r) => (r.attainment === null ? '—' : `${r.attainment}%`) },
            ]} />
          </Card>
          <div className="grid gap-4 lg:grid-cols-3">
            <Can permission={P.PAYMENTS_READ}>
              <Card title="Pendapatan PMB">
                <p className="text-2xl font-semibold">{money(s.kpi_14_revenue.total)}</p>
                <ul className="mt-2 space-y-1 text-sm text-ink-secondary">
                  {Object.entries(s.kpi_14_revenue.by_type).map(([k, v]) => <li key={k}>{statusLabel(k)}: {money(v)}</li>)}
                  <li>Piutang biaya awal: {money(s.kpi_14_revenue.initial_tuition_receivable)}</li>
                  <li>Refund: {money(s.kpi_14_revenue.refunded)}</li>
                </ul>
              </Card>
            </Can>
            <Card title="Verifikasi (SLA)">
              <p className="text-sm">Median: <strong>{s.kpi_18_verification_sla.median_hours ?? '—'} jam</strong></p>
              <p className="text-sm">P90: <strong>{s.kpi_18_verification_sla.p90_hours ?? '—'} jam</strong> ({s.kpi_18_verification_sla.sample} aplikasi)</p>
              <Link to={PMB.admin.verification} className="mt-2 inline-block text-sm text-primary">Buka antrean verifikasi</Link>
            </Card>
            <Card title="Sumber informasi">
              <ul className="space-y-1 text-sm">{s.kpi_17_info_sources.slice(0, 6).map((r) => <li key={r.info_source} className="flex justify-between"><span>{r.info_source}</span><span>{r.submitted} ({r.conversion}%)</span></li>)}</ul>
              <p className="mt-2 text-xs text-ink-tertiary">Kelengkapan PDDikti: {s.kpi_19_pddikti_completeness.percent ?? 0}%</p>
            </Card>
          </div>
        </>
      )}
    </AdminPage>
  )
}
