import { useEffect, useMemo, useState } from 'react'
import { Bar, BarChart, CartesianGrid, Legend, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { ClipboardList, Clock, Flag, Hourglass, Timer, Users } from 'lucide-react'
import { useLoad } from '@/pmb/components/useLoad'
import { AdminPage, useLookups } from '@/pmb/components/admin'
import { cleanParams, usePeriodOptions, useTypeOptions } from '@/pmb/lib/talent'
import { StatCard } from '@/components/ui/StatCard'
import { Card } from '@/components/ui/Card'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { EmptyState } from '@/components/ui/EmptyState'
import { Button } from '@/components/ui/Button'
import { Link } from 'react-router-dom'
import { PMB } from '@/pmb/routes'
import { talentApi, SESSION_STATUS_LABEL, type SessionStatus, type VersionRow } from '@/pmb/api/talent'

const STATUS_OPTIONS = (Object.keys(SESSION_STATUS_LABEL) as SessionStatus[]).map((value) => ({ value, label: SESSION_STATUS_LABEL[value] }))

/** Dashboard One Atma Talent: angka dan grafik dari API aktual dengan filter periode, jenis, versi, status, prodi, dan mode. */
export function TalentDashboardPage() {
  const [filters, setFilters] = useState({ period_id: '', type_id: '', version_id: '', status: '', study_program_id: '', mode: '' })
  const periods = usePeriodOptions()
  const { types, options: typeOptions } = useTypeOptions()
  const lookups = useLookups()
  const [versions, setVersions] = useState<VersionRow[]>([])
  const params = useMemo(() => cleanParams(filters), [filters])
  const dash = useLoad(() => talentApi.dashboard(params), [JSON.stringify(params)])

  useEffect(() => {
    if (!filters.type_id) {
      setVersions([])

      return
    }

    talentApi.versions(filters.type_id).then(setVersions).catch(() => setVersions([]))
  }, [filters.type_id])

  const set = (key: keyof typeof filters) => (e: React.ChangeEvent<HTMLSelectElement>) => setFilters((f) => ({ ...f, [key]: e.target.value, ...(key === 'type_id' ? { version_id: '' } : {}) }))
  const data = dash.data
  const empty = data !== null && data.totals.participants === 0 && data.totals.assessments === 0

  return (
    <AdminPage title="One Atma Talent" description="Pantau asesmen minat dan bakat peserta PMB: penyelesaian, review, dan hasil yang sudah dipublikasikan.">
      <Card>
        <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
          <Select label="Periode PMB" value={filters.period_id} onChange={set('period_id')} options={periods} placeholder="Semua periode" />
          <Select label="Jenis asesmen" value={filters.type_id} onChange={set('type_id')} options={typeOptions} placeholder="Semua jenis" />
          <Select label="Versi" value={filters.version_id} onChange={set('version_id')} disabled={!filters.type_id} options={versions.map((v) => ({ value: v.id, label: `Versi ${v.version_no} · ${v.title}` }))} placeholder={filters.type_id ? 'Semua versi' : 'Pilih jenis dahulu'} />
          <Select label="Status sesi" value={filters.status} onChange={set('status')} options={STATUS_OPTIONS} placeholder="Semua status" />
          <Select label="Program studi" value={filters.study_program_id} onChange={set('study_program_id')} options={(lookups?.study_programs ?? []).map((p) => ({ value: p.id, label: `${p.code} · ${p.name}` }))} placeholder="Semua prodi" />
          <Select label="Mode pelaksanaan" value={filters.mode} onChange={set('mode')} options={[{ value: 'online', label: 'Daring (mandiri)' }]} placeholder="Semua mode" />
        </div>
        {types.length === 0 && !dash.loading && (
          <p className="mt-3 text-xs text-ink-tertiary">Belum ada jenis asesmen. Buat jenis dan versi di menu Asesmen & Versi.</p>
        )}
      </Card>

      {dash.error && <Alert variant="danger">{dash.error.message}</Alert>}
      {dash.loading && !data && <p className="text-sm text-ink-secondary">Memuat ringkasan...</p>}

      {empty && (
        <Card>
          <EmptyState icon={ClipboardList} title="Belum ada asesmen" description="Buat jenis asesmen minat, susun versi instrumen, lalu terbitkan agar peserta dapat mengerjakannya." action={<Link to={PMB.admin.talentAssessments}><Button>Buka Asesmen & Versi</Button></Link>} />
        </Card>
      )}

      {data && !empty && (
        <>
          <div className="grid grid-cols-2 gap-3 xl:grid-cols-5">
            <StatCard label="Total asesmen" value={String(data.totals.assessments)} icon={ClipboardList} caption={`${data.totals.published_versions} versi terbit`} />
            <StatCard label="Total peserta" value={String(data.totals.participants)} icon={Users} caption="Pendaftaran dengan sesi" />
            <StatCard label="Belum dimulai" value={String(data.totals.not_started)} icon={Hourglass} caption="Pendaftaran terkirim" />
            <StatCard label="Sedang berlangsung" value={String(data.totals.in_progress)} icon={Timer} />
            <StatCard label="Sudah dikirim" value={String(data.totals.submitted)} icon={Clock} />
            <StatCard label="Menunggu scoring" value={String(data.totals.pending_scoring)} icon={Hourglass} caption="Seharusnya nol" />
            <StatCard label="Perlu review" value={String(data.totals.needs_review)} icon={Flag} />
            <StatCard label="Sudah dipublikasikan" value={String(data.totals.published)} icon={ClipboardList} />
            <StatCard label="Sesi kedaluwarsa" value={String(data.totals.expired)} icon={Clock} caption={`${data.totals.invalidated} diinvalidasi`} />
            <StatCard label="Tingkat selesai" value={data.totals.participants ? `${Math.round(((data.totals.submitted + data.totals.published + data.totals.expired) / data.totals.participants) * 100)}%` : '—'} icon={Users} caption="Terkirim atau berakhir" />
          </div>

          <div className="grid gap-5 xl:grid-cols-2">
            <Card title="Distribusi status sesi">
              <ResponsiveContainer width="100%" height={240}>
                <BarChart data={(Object.keys(data.status_distribution) as SessionStatus[]).map((k) => ({ name: SESSION_STATUS_LABEL[k], total: data.status_distribution[k] }))}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
                  <XAxis dataKey="name" tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }} />
                  <YAxis allowDecimals={false} tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }} />
                  <Tooltip />
                  <Bar dataKey="total" name="Sesi" fill="var(--color-accent)" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </Card>

            <Card title="Tren 30 hari terakhir" description="Sesi dimulai dan dikirim per hari.">
              <ResponsiveContainer width="100%" height={240}>
                <LineChart data={data.completion_trend.map((d) => ({ ...d, date: d.date.slice(5) }))}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
                  <XAxis dataKey="date" tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }} interval="preserveStartEnd" />
                  <YAxis allowDecimals={false} tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }} />
                  <Tooltip />
                  <Legend />
                  <Line type="monotone" dataKey="started" name="Dimulai" stroke="var(--color-text-secondary)" dot={false} />
                  <Line type="monotone" dataKey="submitted" name="Dikirim" stroke="var(--color-accent)" strokeWidth={2} dot={false} />
                </LineChart>
              </ResponsiveContainer>
            </Card>

            <Card title="Peserta per periode PMB">
              {data.participants_by_period.length === 0
                ? <EmptyState title="Belum ada peserta" description="Peserta muncul setelah memulai asesmen." />
                : (
                  <ResponsiveContainer width="100%" height={240}>
                    <BarChart data={data.participants_by_period.map((p) => ({ name: p.period_name, total: p.participants }))}>
                      <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
                      <XAxis dataKey="name" tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }} />
                      <YAxis allowDecimals={false} tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }} />
                      <Tooltip />
                      <Bar dataKey="total" name="Peserta" fill="var(--color-accent)" radius={[4, 4, 0, 0]} />
                    </BarChart>
                  </ResponsiveContainer>
                )}
            </Card>

            <Card title="Distribusi hasil" description="Dimensi teratas (Holland Code) dari hasil yang sudah dipublikasikan. Bukan diagnosis.">
              {data.result_distribution === null
                ? <EmptyState title="Tidak tersedia" description="Anda tidak memiliki izin melihat hasil asesmen." />
                : data.result_distribution.suppressed
                  ? <Alert variant="info">Distribusi hasil ditampilkan setelah minimal {data.result_distribution.min_published} hasil dipublikasikan, agar peserta tidak dapat dikenali dari angka kecil. Saat ini: {data.result_distribution.published_total}.</Alert>
                  : (
                    <ResponsiveContainer width="100%" height={240}>
                      <BarChart data={data.result_distribution.items.map((i) => ({ name: `${i.code} · ${i.name}`, total: i.total }))}>
                        <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
                        <XAxis dataKey="name" tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }} />
                        <YAxis allowDecimals={false} tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }} />
                        <Tooltip />
                        <Bar dataKey="total" name="Peserta" fill="var(--color-accent)" radius={[4, 4, 0, 0]} />
                      </BarChart>
                    </ResponsiveContainer>
                  )}
            </Card>
          </div>
        </>
      )}
    </AdminPage>
  )
}
