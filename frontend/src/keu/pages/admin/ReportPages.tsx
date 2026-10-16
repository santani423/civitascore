import { useState } from 'react'
import { Download, Plus } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Select } from '@/components/ui/Select'
import { Input } from '@/components/ui/Input'
import { DataTable } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Textarea } from '@/components/ui/Textarea'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, describeError, useAction } from '@/pmb/components/useAction'
import { FormDialog } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { money } from '@/pmb/lib/format'
import { usePermission } from '@/hooks/usePermission'
import { keuAdmin, type Params } from '@/keu/api'
import { Can, KeuBadge, KeuPage } from '@/keu/components'
import { P } from '@/keu/permissions'
import { opts, useKeuLookups } from '@/keu/lookups'
import type { Report } from '@/keu/types'

const MONEY_KEYS = /amount|outstanding|arrears|paid|billed|gross|discount|net|balance|held|penalty|deposit|value|remaining|available|fee|total_/i

/** Label ringkasan laporan (kunci dari FinanceReportService). */
const SUMMARY_LABELS: Record<string, string> = {
  total_gross: 'Total bruto', total_discount: 'Total potongan', total_adjustment: 'Total penyesuaian', total_net: 'Total neto', total_paid: 'Total dibayar',
  total_outstanding: 'Total piutang', total_arrears: 'Total tunggakan', total_amount: 'Total nominal', total_channel_fee: 'Total biaya kanal', total_total: 'Total keringanan',
  total_balance: 'Total saldo', total_held: 'Total ditahan', total_available: 'Total tersedia', total_remaining: 'Total sisa', total_total_amount: 'Total mutasi',
  count: 'Jumlah data', students: 'Jumlah mahasiswa', accounts: 'Jumlah akun', by_method: 'Per metode', by_officer: 'Per petugas',
}

function cell(key: string, value: unknown): string {
  if (value === null || value === undefined || value === '') return '—'
  if (typeof value === 'object') return JSON.stringify(value)
  if (MONEY_KEYS.test(key) && /^-?\d+(\.\d+)?$/.test(String(value))) return money(String(value))

  return String(value)
}

/** Laporan keuangan dengan filter & ekspor CSV (alasan ekspor dicatat di audit). */
export function ReportsPage() {
  const lookups = useKeuLookups()
  const [type, setType] = useState('receivables')
  const [draft, setDraft] = useState<Record<string, string>>({})
  const [filters, setFilters] = useState<Record<string, string>>({})
  const [exporting, setExporting] = useState(false)
  const { data, loading, error } = useLoad(() => keuAdmin.get<Report>(`reports/${type}`, filters as Params), [type, JSON.stringify(filters)])
  const set = (key: string, value: string) => setDraft((d) => ({ ...d, [key]: value }))

  return (
    <KeuPage title="Laporan Keuangan" description="Seluruh angka dihitung dari data tagihan dan transaksi; laporan ditampilkan maks. 500 baris — gunakan ekspor CSV untuk data lengkap." trail={[{ label: 'Laporan' }]}
      actions={<Can permission={P.REPORTS_EXPORT}><Button variant="outline" leftIcon={<Download className="size-4" />} onClick={() => setExporting(true)}>Ekspor CSV</Button></Can>}>
      <Card>
        <div className="flex flex-wrap items-end gap-3">
          <Select label="Jenis laporan" options={lookups?.enums.report_types ?? []} value={type} onChange={(e) => setType(e.target.value)} />
          <Select label="Semester" placeholder="Semua" options={opts(lookups?.academic_terms, (t) => t.label)} value={draft.academic_term_id ?? ''} onChange={(e) => set('academic_term_id', e.target.value)} />
          <Select label="Fakultas" placeholder="Semua" options={opts(lookups?.faculties, (f) => f.name)} value={draft.faculty_id ?? ''} onChange={(e) => set('faculty_id', e.target.value)} />
          <Select label="Prodi" placeholder="Semua" options={opts(lookups?.study_programs, (p) => p.name)} value={draft.study_program_id ?? ''} onChange={(e) => set('study_program_id', e.target.value)} />
          <Input label="Angkatan" type="number" value={draft.admission_year ?? ''} onChange={(e) => set('admission_year', e.target.value)} />
          <Select label="Kelompok" placeholder="Semua" options={lookups?.enums.bill_group ?? []} value={draft.bill_group ?? ''} onChange={(e) => set('bill_group', e.target.value)} />
          <Select label="Metode bayar" placeholder="Semua" options={lookups?.enums.payment_method ?? []} value={draft.method ?? ''} onChange={(e) => set('method', e.target.value)} />
          <Input label="Dari" type="date" value={draft.date_from ?? ''} onChange={(e) => set('date_from', e.target.value)} />
          <Input label="Sampai" type="date" value={draft.date_to ?? ''} onChange={(e) => set('date_to', e.target.value)} />
          <Input label="NIM/nama" value={draft.search ?? ''} onChange={(e) => set('search', e.target.value)} />
          <Button variant="outline" onClick={() => setFilters(Object.fromEntries(Object.entries(draft).filter(([, v]) => v !== '')))}>Terapkan</Button>
        </div>
      </Card>
      {error && <Alert variant="danger">{describeError(error)}</Alert>}
      {loading && <p className="text-sm text-ink-secondary">Memuat...</p>}
      {data && !loading && (
        <>
          {Object.keys(data.summary).length > 0 && (
            <Card title={data.title}>
              <div className="flex flex-wrap gap-6">
                {Object.entries(data.summary).map(([key, value]) => (
                  <div key={key}>
                    <p className="text-xs text-ink-tertiary">{SUMMARY_LABELS[key] ?? key.replace(/_/g, ' ')}</p>
                    {value !== null && typeof value === 'object'
                      ? <ul className="text-sm">{Object.entries(value as Record<string, unknown>).map(([k, v]) => <li key={k}>{lookups?.enums.payment_method.find((o) => o.value === k)?.label ?? k}: <strong>{key === 'by_method' ? money(String(v)) : String(v)}</strong></li>)}</ul>
                      : <p className="text-lg font-semibold">{['count', 'students', 'accounts'].includes(key) ? String(value) : cell(key, value)}</p>}
                  </div>
                ))}
                <div><p className="text-xs text-ink-tertiary">baris</p><p className="text-lg font-semibold">{data.total_rows}</p></div>
              </div>
            </Card>
          )}
          <Card noPadding>
            <DataTable rowKey={(r) => JSON.stringify(r)} data={data.rows} emptyMessage="Tidak ada data untuk filter ini." columns={data.columns.map((c) => ({ header: c.label, cell: (r: Record<string, unknown>) => cell(c.key, r[c.key]) }))} />
            {data.total_rows > data.rows.length && <p className="border-t border-border p-3 text-xs text-ink-tertiary">Menampilkan {data.rows.length} dari {data.total_rows} baris. Ekspor CSV untuk data lengkap.</p>}
          </Card>
        </>
      )}
      <ReasonDialog open={exporting} title="Ekspor laporan" description="Ekspor data keuangan mahasiswa dicatat di log audit beserta alasannya." confirmLabel="Unduh CSV" onClose={() => setExporting(false)}
        onConfirm={(v) => keuAdmin.download(`reports/${type}/export`, { ...filters, reason: String(v.reason ?? '') }, `${type}.csv`)} />
    </KeuPage>
  )
}

interface Setting { key: string; value: unknown; type: string; description: string }
interface Channel { id: string; code: string; name: string; provider: string; method: string; bank_code: string | null; fee_amount: string; fee_bearer: string; expiry_minutes: number; instructions: string | null; counts_toward_quota: boolean; environment: string; sort_order: number; category: string; category_label: string; status: string; status_label: string; counts_in_quota: boolean; connected: boolean }
interface Channels { quota: { limit: number; used: number; plan: string | null; source: string }; configured_providers: string[]; channels: Channel[] }

const QUOTA_SOURCE: Record<string, string> = { override: 'penimpaan Super Admin', plan_limits: 'batas paket langganan', plan_default: 'acuan paket (config/keu.php)', default: 'bawaan (tanpa langganan aktif)' }

/** Pengaturan KEU & kanal pembayaran (kuota paket). */
export function SettingsPage() {
  const canSettings = usePermission(P.SETTINGS_READ)
  const canChannels = usePermission(P.CHANNELS_READ)

  return (
    <KeuPage title="Pengaturan Keuangan" description="Parameter kebijakan KEU per kampus dan kanal pembayaran. Semua perubahan tercatat di log audit." trail={[{ label: 'Pengaturan' }]}>
      {canChannels && <ChannelsCard />}
      {canSettings && <SettingsCard />}
    </KeuPage>
  )
}

function ChannelsCard() {
  const { data, error, reload } = useLoad(() => keuAdmin.get<Channels>('payment-channels'))
  const [editing, setEditing] = useState<Partial<Channel> | null>(null)
  const action = useAction()
  const canUpdate = usePermission(P.CHANNELS_UPDATE)
  const setStatus = (c: Channel, status: string) => action.run(() => keuAdmin.post(`payment-channels/${c.id}/status`, { status }), (x) => x.message).then((x) => x && reload())

  return (
    <Card noPadding title="Kanal pembayaran" actions={<Can permission={P.CHANNELS_UPDATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing({ category: 'bank', method: 'va', fee_amount: '0', fee_bearer: 'campus', expiry_minutes: 1440, environment: 'sandbox', counts_toward_quota: true })}>Kanal</Button></Can>}>
      <div className="flex flex-col gap-2 p-4">
        <Feedback state={action} />
        {error && <Alert variant="danger">{describeError(error)}</Alert>}
        {data && (
          <>
            <p className="text-sm">Kuota kanal online produksi: <strong>{data.quota.used} dari {data.quota.limit}</strong> terpakai (paket {data.quota.plan ?? '—'}, sumber: {QUOTA_SOURCE[data.quota.source] ?? data.quota.source}). Tunai, transfer manual, dan kanal sandbox tidak dihitung.</p>
            {data.configured_providers.length === 0
              ? <Alert variant="warning">Belum ada penyedia pembayaran online yang terkonfigurasi di server (kredensial kosong). Kanal online belum dapat dipakai bertransaksi; mahasiswa hanya dapat membayar tunai di loket atau transfer manual dengan unggah bukti.</Alert>
              : <p className="text-xs text-ink-tertiary">Penyedia terkonfigurasi: {data.configured_providers.join(', ')} (belum diverifikasi end-to-end dengan akun produksi).</p>}
          </>
        )}
      </div>
      <DataTable rowKey={(r) => r.id} data={data?.channels ?? []} emptyMessage="Belum ada kanal. Jalankan `php artisan keu:install` untuk kanal tunai & transfer manual." columns={[
        { header: 'Kanal', cell: (r) => <span>{r.name}<span className="block text-xs text-ink-tertiary">{r.code} · {r.category_label} · {r.provider}/{r.method}</span></span> },
        { header: 'Biaya', cell: (r) => `${money(r.fee_amount)} (${r.fee_bearer === 'student' ? 'mahasiswa' : 'kampus'})` },
        { header: 'Lingkungan', cell: (r) => <span className="flex flex-wrap gap-1"><Badge variant={r.environment === 'production' ? 'primary' : 'neutral'}>{r.environment}</Badge>{r.counts_in_quota && <Badge variant="warning">Dihitung kuota</Badge>}</span> },
        { header: 'Terhubung', cell: (r) => r.connected ? <Badge variant="success">Ya</Badge> : <Badge variant="danger">Belum</Badge> },
        { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status_label} /> },
        { header: 'Aksi', cell: (r) => canUpdate ? <div className="flex gap-1">
          {r.status !== 'active' && <Button size="sm" onClick={() => setStatus(r, 'active')}>Aktifkan</Button>}
          {r.status === 'active' && <Button size="sm" variant="outline" onClick={() => setStatus(r, 'maintenance')}>Pemeliharaan</Button>}
          {r.status !== 'inactive' && <Button size="sm" variant="ghost" onClick={() => setStatus(r, 'inactive')}>Nonaktifkan</Button>}
          <Button size="sm" variant="ghost" onClick={() => setEditing(r)}>Ubah</Button>
        </div> : null },
      ]} />
      <FormDialog open={editing !== null} title={editing?.id ? 'Ubah kanal' : 'Kanal baru'} initial={editing ?? {}} wide onClose={() => setEditing(null)}
        description="Kanal baru berstatus nonaktif. Kanal online hanya dapat dipakai bila penyedianya terkonfigurasi di server."
        fields={[
          { name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama tampilan', required: true },
          { name: 'category', label: 'Kategori', type: 'select', required: true, options: [{ value: 'bank', label: 'Bank (VA/H2H)' }, { value: 'ecommerce', label: 'E-commerce/payment gateway' }, { value: 'offline', label: 'Offline (loket/transfer manual)' }] },
          { name: 'provider', label: 'Penyedia (mis. midtrans, cash, manual)', required: true },
          { name: 'method', label: 'Metode', type: 'select', required: true, options: ['va', 'qris', 'ewallet', 'retail', 'biller', 'cash', 'manual_transfer'].map((v) => ({ value: v, label: v })) },
          { name: 'bank_code', label: 'Kode bank' },
          { name: 'fee_amount', label: 'Biaya admin (Rp)', type: 'number', required: true },
          { name: 'fee_bearer', label: 'Biaya ditanggung', type: 'select', required: true, options: [{ value: 'campus', label: 'Kampus' }, { value: 'student', label: 'Mahasiswa' }] },
          { name: 'expiry_minutes', label: 'Masa berlaku instruksi (menit)', type: 'number', required: true },
          { name: 'environment', label: 'Lingkungan', type: 'select', required: true, options: [{ value: 'sandbox', label: 'Sandbox (uji)' }, { value: 'production', label: 'Produksi' }] },
          { name: 'counts_toward_quota', label: 'Dihitung kuota paket', type: 'checkbox' },
          { name: 'sort_order', label: 'Urutan tampil', type: 'number' },
          { name: 'instructions', label: 'Instruksi pembayaran untuk mahasiswa', type: 'textarea' },
        ]}
        onSubmit={async (v) => {
          await (editing?.id ? keuAdmin.put(`payment-channels/${editing.id}`, v) : keuAdmin.post('payment-channels', v))
          reload()
        }} />
    </Card>
  )
}

function SettingsCard() {
  const { data, error, reload } = useLoad(() => keuAdmin.get<Setting[]>('settings'))
  const [values, setValues] = useState<Record<string, string>>({})
  const canUpdate = usePermission(P.SETTINGS_UPDATE)
  const action = useAction()
  const display = (s: Setting) => values[s.key] ?? (typeof s.value === 'object' ? JSON.stringify(s.value) : String(s.value ?? ''))

  async function save() {
    const payload: Record<string, unknown> = {}

    for (const s of data ?? []) {
      if (values[s.key] === undefined) continue
      const raw = values[s.key]
      try {
        payload[s.key] = s.type === 'json' ? JSON.parse(raw) : s.type === 'integer' ? Number(raw) : s.type === 'boolean' ? raw === 'true' : raw
      } catch {
        throw { status: 422, message: `Format JSON ${s.key} tidak valid.` }
      }
    }

    await keuAdmin.put('settings', { settings: payload })
    setValues({})
    reload()
  }

  return (
    <Card title="Parameter kebijakan" actions={canUpdate ? <Button size="sm" isLoading={action.busy} disabled={Object.keys(values).length === 0} onClick={() => action.run(save, 'Pengaturan disimpan.')}>Simpan perubahan</Button> : undefined}>
      <Feedback state={action} />
      {error && <Alert variant="danger">{describeError(error)}</Alert>}
      <div className="grid gap-4 md:grid-cols-2">
        {(data ?? []).filter((s) => s.key !== 'keu.payment_channels.limit_override').map((s) => (
          <div key={s.key} className="flex flex-col gap-1">
            {s.type === 'boolean' ? (
              <Select label={s.description} options={[{ value: 'true', label: 'Ya' }, { value: 'false', label: 'Tidak' }]} disabled={!canUpdate} value={display(s)} onChange={(e) => setValues({ ...values, [s.key]: e.target.value })} />
            ) : s.type === 'json' ? (
              <Textarea label={s.description} rows={3} className="font-mono text-xs" disabled={!canUpdate} value={display(s)} onChange={(e) => setValues({ ...values, [s.key]: e.target.value })} />
            ) : (
              <Input label={s.description} type={s.type === 'integer' ? 'number' : 'text'} disabled={!canUpdate} value={display(s)} onChange={(e) => setValues({ ...values, [s.key]: e.target.value })} />
            )}
            <span className="font-mono text-[11px] text-ink-tertiary">{s.key}</span>
          </div>
        ))}
      </div>
      <p className="mt-4 text-xs text-ink-tertiary">Rekening penerimaan transfer manual (keu.bank_accounts) diisi sebagai JSON: [{'{'}"bank":"BNI","account_number":"1234567890","account_name":"Universitas X"{'}'}].</p>
    </Card>
  )
}
