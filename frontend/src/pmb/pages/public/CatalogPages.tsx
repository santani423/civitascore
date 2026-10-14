import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Download, FileText } from 'lucide-react'
import { pmbPublic, type PublicPeriod } from '@/pmb/api/public'
import { useLoad } from '@/pmb/components/useLoad'
import { RichText, Section } from '@/pmb/components/Section'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { DataTable } from '@/components/ui/DataTable'
import { KeyValue } from '@/pmb/components/KeyValue'
import { date, dateTime, money } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'

const QUOTA_LABEL: Record<string, string> = { tersedia: 'Kuota tersedia', terbatas: 'Kuota terbatas', penuh: 'Kuota penuh' }

/** PUB-02 Daftar prodi. */
export function ProgramsPage() {
  const { data, loading, error } = useLoad(() => pmbPublic.programs(), [])
  const [search, setSearch] = useState('')
  const [level, setLevel] = useState('')
  const [faculty, setFaculty] = useState('')
  const levels = [...new Set((data ?? []).map((p) => p.degree_level))].filter(Boolean)
  const faculties = [...new Set((data ?? []).map((p) => p.faculty ?? ''))].filter(Boolean)
  const shown = (data ?? []).filter((p) => (!search || p.name.toLowerCase().includes(search.toLowerCase())) && (!level || p.degree_level === level) && (!faculty || p.faculty === faculty))

  return (
    <Section title="Program Studi" description="Jelajahi program studi yang membuka pendaftaran.">
      <div className="mb-5 flex flex-wrap gap-3">
        <Input label="Cari" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nama prodi..." />
        <Select label="Jenjang" placeholder="Semua" options={levels.map((l) => ({ value: l, label: l }))} value={level} onChange={(e) => setLevel(e.target.value)} />
        <Select label="Fakultas" placeholder="Semua" options={faculties.map((f) => ({ value: f, label: f }))} value={faculty} onChange={(e) => setFaculty(e.target.value)} />
      </div>
      {error && <Alert variant="danger">{error.message}</Alert>}
      {!loading && shown.length === 0 && <p className="text-sm text-ink-secondary">Tidak ada program studi yang cocok.</p>}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {shown.map((p) => (
          <Link key={p.slug} to={to(PMB.program, { slug: p.slug })}>
            <Card className="h-full transition-shadow hover:shadow-md">
              <p className="font-semibold text-ink-primary">{p.name}</p>
              <p className="text-xs text-ink-tertiary">{p.degree_level} · {p.faculty}</p>
              {p.accreditation && <Badge variant="info" className="mt-2">Akreditasi {p.accreditation}</Badge>}
              <p className="mt-2 line-clamp-3 text-sm text-ink-secondary">{p.summary}</p>
              <p className="mt-2 text-xs text-ink-tertiary">{p.study_systems.join(', ')}</p>
            </Card>
          </Link>
        ))}
      </div>
    </Section>
  )
}

/** PUB-03 Detail prodi. */
export function ProgramDetailPage() {
  const { slug = '' } = useParams()
  const { data: p, loading, error } = useLoad(() => pmbPublic.program(slug), [slug])

  if (error?.status === 404) return <Section title="Program studi tidak ditemukan"><Link to={PMB.programs} className="text-primary">Kembali ke daftar prodi</Link></Section>
  if (loading || !p) return <Section><p className="text-sm text-ink-secondary">Memuat...</p></Section>

  return (
    <Section title={p.name} description={`${p.degree_level} · ${p.faculty ?? ''}`} actions={<Link to={PMB.register} className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white">Daftar ke prodi ini</Link>}>
      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-4 lg:col-span-2">
          {p.cover_url && <img src={p.cover_url} alt="" className="w-full rounded-xl object-cover" />}
          <Card><p className="text-sm text-ink-secondary">{p.summary}</p><RichText html={p.description} /></Card>
          {p.career_prospects && <Card title="Prospek Karier"><p className="whitespace-pre-line text-sm text-ink-secondary">{p.career_prospects}</p></Card>}
          <Card title="Biaya" noPadding>
            <DataTable rowKey={(r) => r.code + r.study_system + (r.path ?? '')} data={p.fees} emptyMessage="Rincian biaya belum dipublikasikan." columns={[
              { header: 'Komponen', cell: (r) => r.component },
              { header: 'Sistem', cell: (r) => r.study_system },
              { header: 'Jalur', cell: (r) => r.path ?? 'Semua' },
              { header: 'Nominal', cell: (r) => money(r.amount) },
              { header: 'Cicilan', cell: (r) => (r.installable ? 'Bisa dicicil' : '—') },
            ]} />
          </Card>
        </div>
        <div className="space-y-4">
          <Card title="Ringkasan">
            <KeyValue columns={1} items={[
              ['Akreditasi', p.accreditation_label ? `${p.accreditation_label}${p.accreditation_valid_until ? ` (s.d. ${date(p.accreditation_valid_until)})` : ''}` : null],
              ['Gelar', p.degree_title],
              ['Lama studi', p.study_duration],
            ]} />
            {p.brochure_url && <a href={p.brochure_url} target="_blank" rel="noopener" className="mt-3 inline-flex items-center gap-2 text-sm text-primary"><Download className="size-4" />Unduh brosur</a>}
          </Card>
          <Card title="Jalur yang membuka">
            <ul className="space-y-1 text-sm">
              {p.paths.map((x) => <li key={x.path_code + x.study_system} className="flex justify-between gap-2"><Link className="text-primary" to={to(PMB.path, { code: x.path_code.toLowerCase() })}>{x.path} · {x.study_system}</Link>{x.quota_status && <Badge variant={x.quota_status === 'penuh' ? 'danger' : x.quota_status === 'terbatas' ? 'warning' : 'success'}>{QUOTA_LABEL[x.quota_status]}</Badge>}</li>)}
              {p.paths.length === 0 && <li className="text-ink-secondary">Belum ada jalur yang membuka prodi ini.</li>}
            </ul>
          </Card>
        </div>
      </div>
    </Section>
  )
}

/** PUB-04 Daftar jalur. */
export function PathsPage() {
  const { data, loading } = useLoad(() => pmbPublic.paths(), [])

  return (
    <Section title="Jalur Masuk" description="Pilih jalur yang sesuai dengan latar belakang Anda.">
      {loading && <p className="text-sm text-ink-secondary">Memuat...</p>}
      <div className="grid gap-4 sm:grid-cols-2">
        {(data ?? []).map((p) => (
          <Link key={p.code} to={to(PMB.path, { code: p.code.toLowerCase() })}>
            <Card className="h-full transition-shadow hover:shadow-md">
              <p className="font-semibold text-ink-primary">{p.name}</p>
              <p className="mt-1 text-sm text-ink-secondary">{p.description}</p>
              <div className="mt-3 flex flex-wrap gap-1">
                {p.open_waves.map((w) => <Badge key={w.wave} variant="success">{w.wave} · {money(w.fee)} · tutup {date(w.closes_at)}</Badge>)}
                {p.open_waves.length === 0 && <Badge>Belum dibuka</Badge>}
              </div>
            </Card>
          </Link>
        ))}
      </div>
    </Section>
  )
}

/** PUB-04 Detail jalur: gelombang, persyaratan dokumen, komponen seleksi, biaya. */
export function PathDetailPage() {
  const { code = '' } = useParams()
  const { data: p, error, loading } = useLoad(() => pmbPublic.path(code), [code])

  if (error?.status === 404) return <Section title="Jalur tidak ditemukan"><Link to={PMB.paths} className="text-primary">Kembali</Link></Section>
  if (loading || !p) return <Section><p className="text-sm text-ink-secondary">Memuat...</p></Section>

  return (
    <Section title={`Jalur ${p.name}`} description={p.description ?? undefined} actions={<Link to={PMB.register} className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-white">Daftar</Link>}>
      <p className="mb-4 text-sm text-ink-secondary">{p.requires_exam ? 'Jalur ini mensyaratkan ujian/tes seleksi.' : 'Seleksi berdasarkan berkas (tanpa ujian tulis).'} {p.eligible_degree_levels.length > 0 && `Jenjang: ${p.eligible_degree_levels.join(', ')}.`}</p>
      <div className="space-y-4">
        {p.waves.map((w) => (
          <Card key={w.wave} title={w.wave} description={`${dateTime(w.opens_at)} – ${dateTime(w.closes_at)} · Pengumuman ${date(w.announcement_at)}`} actions={<Badge variant={w.status === 'open' ? 'success' : 'neutral'}>{w.status === 'open' ? 'Dibuka' : w.status}</Badge>}>
            <KeyValue items={[['Biaya pendaftaran', money(w.fee)], ['Maksimal pilihan prodi', String(w.max_choices)], ['Komponen seleksi', w.selection_components.join(', ') || '—']]} />
            {w.public_notes && <p className="mt-3 whitespace-pre-line text-sm text-ink-secondary">{w.public_notes}</p>}
            <h3 className="mb-2 mt-4 text-sm font-semibold text-ink-primary">Persyaratan dokumen</h3>
            <ul className="space-y-1 text-sm">
              {w.documents.map((d) => (
                <li key={d.name + d.stage} className="flex items-start gap-2 text-ink-secondary">
                  <FileText className="mt-0.5 size-4 shrink-0" />
                  <span><span className="text-ink-primary">{d.name}</span> {d.mandatory ? '(wajib)' : '(opsional)'} · {d.stage === 'application' ? 'saat mendaftar' : 'saat daftar ulang'} · {d.formats.map((f) => f.split('/')[1]?.toUpperCase()).join('/')} maks {Math.round(d.max_size_kb / 1024 * 10) / 10} MB{d.instructions ? ` — ${d.instructions}` : ''}</span>
                </li>
              ))}
            </ul>
          </Card>
        ))}
        {p.waves.length === 0 && <p className="text-sm text-ink-secondary">Belum ada gelombang untuk jalur ini.</p>}
      </div>
    </Section>
  )
}

/** PUB-05 Biaya: pilih prodi & sistem kuliah → rincian komponen. */
export function FeesPage() {
  const { data, loading } = useLoad(() => pmbPublic.fees(), [])
  const [program, setProgram] = useState('')
  const [system, setSystem] = useState('')
  const programs = useMemo(() => [...new Map((data ?? []).map((f) => [f.study_program_id, f.program])).entries()], [data])
  const systems = useMemo(() => [...new Map((data ?? []).map((f) => [f.study_system_id, f.study_system])).entries()], [data])
  const rows = (data ?? []).filter((f) => (!program || f.study_program_id === program) && (!system || f.study_system_id === system))
  const total = rows.filter((r) => !r.path).reduce((sum, r) => sum + Number(r.amount), 0)

  return (
    <Section title="Biaya Kuliah" description="Rincian biaya awal per program studi dan sistem kuliah.">
      <div className="mb-4 flex flex-wrap gap-3">
        <Select label="Program studi" placeholder="Semua" options={programs.map(([value, label]) => ({ value, label }))} value={program} onChange={(e) => setProgram(e.target.value)} />
        <Select label="Sistem kuliah" placeholder="Semua" options={systems.map(([value, label]) => ({ value, label }))} value={system} onChange={(e) => setSystem(e.target.value)} />
      </div>
      <Card noPadding>
        <DataTable rowKey={(r) => `${r.study_program_id}-${r.study_system_id}-${r.code}-${r.path ?? ''}`} data={rows} emptyMessage={loading ? 'Memuat...' : 'Belum ada biaya yang dipublikasikan.'} columns={[
          { header: 'Program studi', cell: (r) => r.program },
          { header: 'Sistem', cell: (r) => r.study_system },
          { header: 'Jalur', cell: (r) => r.path ?? 'Semua jalur' },
          { header: 'Komponen', cell: (r) => r.component },
          { header: 'Nominal', cell: (r) => money(r.amount) },
          { header: 'Cicilan', cell: (r) => (r.installable && r.installment_plan ? r.installment_plan.map((t) => `${t.percent}%`).join(' + ') : '—') },
        ]} />
      </Card>
      {program && system && <p className="mt-3 text-sm font-medium text-ink-primary">Total biaya awal (semua jalur): {money(total)}</p>}
      <p className="mt-2 text-xs text-ink-tertiary">Potongan beasiswa jalur/KIP diterapkan pada tagihan setelah pengumuman.</p>
    </Section>
  )
}

function icsFor(periods: PublicPeriod[]): string {
  const stamp = new Date().toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z'
  const fmt = (iso: string) => new Date(iso).toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z'
  const events = periods.flatMap((p) => p.waves.flatMap((w) => [
    ['Pendaftaran dibuka', w.registration_opens_at], ['Pendaftaran ditutup', w.registration_closes_at], ['Pengumuman', w.announcement_at], ['Daftar ulang dibuka', w.re_registration_opens_at], ['Daftar ulang ditutup', w.re_registration_closes_at],
  ].filter(([, at]) => at).map(([label, at]) => `BEGIN:VEVENT\r\nUID:${w.id}-${label}@pmb\r\nDTSTAMP:${stamp}\r\nDTSTART:${fmt(at as string)}\r\nSUMMARY:${label} — ${w.name} (${p.name})\r\nEND:VEVENT`)))

  return `BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//CivitasOne//PMB//ID\r\n${events.join('\r\n')}\r\nEND:VCALENDAR`
}

/** PUB-06 Jadwal: linimasa tahapan per gelombang + unduh .ics. */
export function SchedulePage() {
  const { data, loading } = useLoad(() => pmbPublic.periods(), [])

  const download = () => {
    const blob = new Blob([icsFor(data ?? [])], { type: 'text/calendar' })
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = 'jadwal-pmb.ics'
    a.click()
    URL.revokeObjectURL(a.href)
  }

  return (
    <Section title="Jadwal" actions={data && data.length > 0 ? <Button variant="outline" onClick={download} leftIcon={<Download className="size-4" />}>Tambahkan ke kalender</Button> : undefined}>
      {loading && <p className="text-sm text-ink-secondary">Memuat...</p>}
      {data?.length === 0 && <p className="text-sm text-ink-secondary">Jadwal belum diumumkan.</p>}
      {(data ?? []).map((period) => (
        <div key={period.id} className="mb-6">
          <h2 className="mb-3 text-lg font-semibold text-ink-primary">{period.name}</h2>
          <div className="grid gap-4 md:grid-cols-2">
            {period.waves.map((w) => (
              <Card key={w.id} title={w.name}>
                <ol className="relative space-y-3 border-l border-border pl-4 text-sm">
                  {[
                    ['Pendaftaran', `${dateTime(w.registration_opens_at)} – ${dateTime(w.registration_closes_at)}`],
                    ['Batas pembayaran', dateTime(w.payment_deadline_at)],
                    ['Ujian', w.exam_starts_on ? `${date(w.exam_starts_on)} – ${date(w.exam_ends_on)}` : (w.is_ods ? 'Sesuai jadwal ODS' : 'Tanpa ujian')],
                    ['Pengumuman', dateTime(w.announcement_at)],
                    ['Daftar ulang', `${dateTime(w.re_registration_opens_at)} – ${dateTime(w.re_registration_closes_at)}`],
                  ].map(([label, value]) => (
                    <li key={label}><span className="absolute -left-1.5 mt-1.5 size-3 rounded-full bg-primary" /><p className="font-medium text-ink-primary">{label}</p><p className="text-ink-secondary">{value}</p></li>
                  ))}
                </ol>
              </Card>
            ))}
          </div>
        </div>
      ))}
    </Section>
  )
}
