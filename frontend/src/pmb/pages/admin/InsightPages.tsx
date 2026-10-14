import { useEffect, useState } from 'react'
import { Download, Send } from 'lucide-react'
import { pmbAdmin, type Params } from '@/pmb/api/admin'
import { adminClient } from '@/pmb/api/http'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminList } from '@/pmb/components/AdminList'
import { FormDialog } from '@/pmb/components/FormDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { Tabs } from '@/pmb/components/Tabs'
import { AdminPage, P, options, useLookups } from '@/pmb/components/admin'
import { usePermission } from '@/hooks/usePermission'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { Select } from '@/components/ui/Select'
import { Input } from '@/components/ui/Input'
import { Textarea } from '@/components/ui/Textarea'
import { DataTable } from '@/components/ui/DataTable'
import { ListPagination } from '@/components/ui/ListPagination'
import { dateTime } from '@/pmb/lib/format'
import { statusLabel } from '@/pmb/lib/labels'
import { cn } from '@/utils/cn'

const REPORTS = [['applicants', 'Rekap Pendaftar'], ['payments', 'Rekap Pembayaran'], ['info-sources', 'Sumber Informasi'], ['selection', 'Laporan Seleksi'], ['re-registration', 'Daftar Ulang & Konversi'], ['finance', 'Keuangan PMB'], ['quota', 'Keterisian Kuota'], ['pddikti-completeness', 'Kelengkapan Data PDDikti']]

type RTab = 'report' | 'exports'

/** ADM-69 Laporan: pemilih laporan, filter, tabel, ekspor beralasan, ekspor saya. */
export function ReportsPage() {
  const lookups = useLookups()
  const canExport = usePermission(P.REPORTS_EXPORT)
  const [tab, setTab] = useState<RTab>('report')
  const [code, setCode] = useState('applicants')
  const [filters, setFilters] = useState<Record<string, string>>({})
  const [applied, setApplied] = useState<Record<string, string>>({})
  const [page, setPage] = useState(1)
  const [exporting, setExporting] = useState(false)
  const [exportsReload, setExportsReload] = useState(0)
  const report = useLoad(() => adminClient.get<{ data: { title: string; columns: Array<{ key: string; label: string }>; rows: Array<Record<string, unknown>>; available: Array<{ code: string; title: string }> }; meta: { current_page: number; per_page: number; total: number; last_page: number } }>(`/pmb/admin/reports/${code}`, { params: { ...applied, page, per_page: 50 } }).then((r) => r.data), [code, applied, page])
  const data = report.data?.data

  useEffect(() => setPage(1), [code, applied])

  return (
    <AdminPage title="Laporan PMB" trail={[{ label: 'Laporan' }]}>
      <Tabs<RTab> active={tab} onChange={setTab} tabs={[{ key: 'report', label: 'Laporan' }, { key: 'exports', label: 'Ekspor saya' }]} />
      {tab === 'report' && (
        <>
          <Card>
            <div className="flex flex-wrap items-end gap-3">
              <Select label="Laporan" options={(data?.available ?? REPORTS.map(([c, t]) => ({ code: c, title: t }))).map((r) => ({ value: r.code, label: r.title }))} value={code} onChange={(e) => setCode(e.target.value)} />
              <Select label="Periode" placeholder="Semua" options={options(lookups?.periods, (p) => p.name)} value={filters.period_id ?? ''} onChange={(e) => setFilters({ ...filters, period_id: e.target.value })} />
              <Select label="Jalur" placeholder="Semua" options={options(lookups?.admission_paths, (p) => p.name)} value={filters.admission_path_id ?? ''} onChange={(e) => setFilters({ ...filters, admission_path_id: e.target.value })} />
              <Select label="Fakultas" placeholder="Semua" options={options(lookups?.faculties, (p) => p.name)} value={filters.faculty_id ?? ''} onChange={(e) => setFilters({ ...filters, faculty_id: e.target.value })} />
              <Select label="Prodi" placeholder="Semua" options={options(lookups?.study_programs, (p) => p.name)} value={filters.study_program_id ?? ''} onChange={(e) => setFilters({ ...filters, study_program_id: e.target.value })} />
              <Input label="Dari" type="date" value={filters.from ?? ''} onChange={(e) => setFilters({ ...filters, from: e.target.value })} />
              <Input label="Sampai" type="date" value={filters.to ?? ''} onChange={(e) => setFilters({ ...filters, to: e.target.value })} />
              <Button variant="outline" onClick={() => setApplied(Object.fromEntries(Object.entries(filters).filter(([, v]) => v)))}>Terapkan</Button>
              {canExport && <Button leftIcon={<Download className="size-4" />} onClick={() => setExporting(true)}>Ekspor</Button>}
            </div>
          </Card>
          {report.error && <Alert variant="danger">{report.error.message}</Alert>}
          <Card noPadding title={data?.title}>
            <DataTable rowKey={(r) => JSON.stringify(r)} data={data?.rows ?? []} emptyMessage={report.loading ? 'Memuat...' : 'Tidak ada data.'} columns={(data?.columns ?? []).map((c) => ({ header: c.label, cell: (r: Record<string, unknown>) => { const v = r[c.key]; return v === null || v === undefined ? '—' : typeof v === 'object' ? JSON.stringify(v) : String(v) } }))} />
            <ListPagination meta={report.data?.meta ?? null} page={page} onPageChange={setPage} itemLabel="baris" />
          </Card>
          <FormDialog open={exporting} title="Ekspor laporan" description="Ekspor yang memuat data pribadi tercatat di log ekspor (pengguna, alasan, filter). Tautan berlaku 24 jam dan hanya untuk Anda." fields={[
            { name: 'format', label: 'Format', type: 'select', required: true, options: [{ value: 'xlsx', label: 'Excel (XLSX)' }, { value: 'csv', label: 'CSV' }, { value: 'pdf', label: 'PDF' }] },
            { name: 'columns', label: 'Kolom (kosong = semua)', type: 'multiselect', options: (data?.columns ?? []).map((c) => ({ value: c.key, label: c.label })) },
            { name: 'reason', label: 'Alasan ekspor', type: 'textarea', required: true },
          ]} initial={{ format: 'xlsx' }} onClose={() => setExporting(false)} onSubmit={async (v) => { await pmbAdmin.post(`reports/${code}/exports`, { ...v, filters: applied }); setExportsReload(exportsReload + 1); setTab('exports') }} />
        </>
      )}
      {tab === 'exports' && <MyExports reloadKey={exportsReload} />}
    </AdminPage>
  )
}

function MyExports({ reloadKey }: { reloadKey: number }) {
  const action = useAction()
  const [tick, setTick] = useState(0)

  useEffect(() => {
    const timer = window.setInterval(() => setTick((t) => t + 1), 5000)

    return () => window.clearInterval(timer)
  }, [])

  return (
    <>
      <Feedback state={action} />
      <AdminList<{ id: string; title: string; format: string; status: string; row_count: number | null; reason: string; contains_personal_data: boolean; error_message: string | null; expires_at: string | null; created_at: string | null }>
        path="exports" reloadKey={reloadKey + tick} search={false} rowKey={(r) => r.id} itemLabel="ekspor"
        columns={[
          { header: 'Laporan', cell: (r) => <span>{r.title}<span className="block text-xs text-ink-tertiary">{r.format.toUpperCase()} · {r.reason}</span></span> },
          { header: 'Baris', cell: (r) => r.row_count ?? '—' }, { header: 'Data pribadi', cell: (r) => (r.contains_personal_data ? <Badge variant="warning">Ya</Badge> : 'Tidak') },
          { header: 'Dibuat', cell: (r) => dateTime(r.created_at) }, { header: 'Kedaluwarsa', cell: (r) => dateTime(r.expires_at) },
          { header: 'Status', cell: (r) => <span><StatusBadge status={r.status} />{r.error_message && <span className="block text-xs text-danger">{r.error_message}</span>}</span> },
          { header: '', cell: (r) => r.status === 'ready' && <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.open(`exports/${r.id}`))}>Unduh</Button> },
        ]} />
    </>
  )
}

type CTab = 'chat' | 'broadcasts' | 'templates' | 'logs'

/** ADM-19/67/68 Komunikasi: quick chat, broadcast, template notifikasi, log pengiriman. */
export function CommunicationPage() {
  const canChat = usePermission(P.CHAT)
  const canBroadcast = usePermission(P.BROADCAST)
  const canTemplates = usePermission(P.CONFIG_MANAGE)
  const canLogs = usePermission([P.AUDIT, P.CONFIG_MANAGE])
  const tabs = [
    ...(canChat ? [{ key: 'chat' as CTab, label: 'Pesan pendaftar' }] : []),
    ...(canBroadcast ? [{ key: 'broadcasts' as CTab, label: 'Broadcast' }] : []),
    ...(canTemplates ? [{ key: 'templates' as CTab, label: 'Template notifikasi' }] : []),
    ...(canLogs ? [{ key: 'logs' as CTab, label: 'Log notifikasi' }] : []),
  ]
  const [tab, setTab] = useState<CTab>(tabs[0]?.key ?? 'chat')

  return (
    <AdminPage title="Komunikasi PMB" trail={[{ label: 'Komunikasi' }]}>
      <Tabs<CTab> active={tab} onChange={setTab} tabs={tabs} />
      {tab === 'chat' && canChat && <ChatInbox />}
      {tab === 'broadcasts' && canBroadcast && <Broadcasts />}
      {tab === 'templates' && canTemplates && <Templates />}
      {tab === 'logs' && canLogs && <NotificationLogs />}
    </AdminPage>
  )
}

interface ThreadRow { id: string; subject: string; status: string; unread: number; applicant: { id: string; name: string; email: string } | null; registration_number: string | null; assignee: { id: string; name: string } | null; last_message_at: string | null }

function ChatInbox() {
  const [active, setActive] = useState<string | null>(null)
  const [reload, setReload] = useState(0)

  return (
    <div className="grid gap-4 lg:grid-cols-5">
      <div className="lg:col-span-2">
        <AdminList<ThreadRow> path="chat-threads" reloadKey={reload} rowKey={(r) => r.id} itemLabel="percakapan"
          filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'open', label: 'Dibuka' }, { value: 'pending_applicant', label: 'Menunggu pendaftar' }, { value: 'closed', label: 'Ditutup' }] }, { name: 'mine', label: 'Penanggung jawab', type: 'select', options: [{ value: '1', label: 'Saya' }] }]}
          columns={[{ header: 'Percakapan', cell: (r) => (
            <button type="button" onClick={() => setActive(r.id)} className={cn('w-full text-left', active === r.id && 'font-semibold')}>
              <span className="flex items-center gap-2">{r.subject}{r.unread > 0 && <Badge variant="danger">{r.unread}</Badge>}</span>
              <span className="block text-xs text-ink-tertiary">{r.applicant?.name} · {dateTime(r.last_message_at)} · {statusLabel(r.status)}{r.assignee ? ` · ${r.assignee.name}` : ''}</span>
            </button>
          ) }]} />
      </div>
      <div className="lg:col-span-3">{active ? <AdminThread id={active} onChanged={() => setReload(reload + 1)} /> : <Card><p className="text-sm text-ink-secondary">Pilih percakapan.</p></Card>}</div>
    </div>
  )
}

function AdminThread({ id, onChanged }: { id: string; onChanged: () => void }) {
  const data = useLoad(() => pmbAdmin.get<{ thread: { id: string; subject: string; status: string; applicant: { name: string; email: string; phone: string | null } | null; application: { id: string; registration_number: string | null } | null; assignee: { id: string; name: string } | null }; messages: Array<{ id: string; sender_type: string; sender: string | null; body: string; read_at: string | null; created_at: string | null; attachment_url: string | null }>; statuses: string[]; security_notice: string }>(`chat-threads/${id}`), [id])
  const staff = useLoad(() => pmbAdmin.get<Array<{ id: string; name: string }>>('staff', { permission: P.CHAT }).catch(() => []), [])
  const [body, setBody] = useState('')
  const action = useAction()
  const t = data.data?.thread

  const reply = async () => {
    const form = new FormData()
    form.append('body', body)
    const ok = await action.run(() => adminClient.post(`/pmb/admin/chat-threads/${id}/messages`, form))
    if (ok !== undefined) { setBody(''); data.reload(); onChanged() }
  }

  return (
    <Card title={t?.subject} description={t ? `${t.applicant?.name} · ${t.applicant?.email}${t.application ? ` · ${t.application.registration_number}` : ''}` : undefined} actions={t && (
      <div className="flex gap-2">
        <Select options={(staff.data ?? []).map((u) => ({ value: u.id, label: u.name }))} placeholder="Penanggung jawab" value={t.assignee?.id ?? ''} onChange={(e) => action.run(() => pmbAdmin.patch(`chat-threads/${id}`, { assigned_user_id: e.target.value || null })).then(() => { data.reload(); onChanged() })} />
        <Select options={(data.data?.statuses ?? []).map((s) => ({ value: s, label: statusLabel(s) }))} value={t.status} onChange={(e) => action.run(() => pmbAdmin.patch(`chat-threads/${id}`, { status: e.target.value })).then(() => { data.reload(); onChanged() })} />
      </div>
    )}>
      <Feedback state={action} />
      <div className="flex max-h-[55vh] flex-col gap-2 overflow-y-auto">
        {(data.data?.messages ?? []).map((m) => (
          <div key={m.id} className={cn('max-w-[80%] rounded-lg px-3 py-2 text-sm', m.sender_type === 'user' ? 'self-end bg-primary text-white' : m.sender_type === 'system' ? 'self-center bg-amber-50 text-amber-800' : 'self-start bg-surface-hover')}>
            <p className="text-[11px] opacity-70">{m.sender}</p>
            <p className="whitespace-pre-line">{m.body}</p>
            {m.attachment_url && <a className="text-xs underline" href={m.attachment_url} target="_blank" rel="noopener">Lampiran</a>}
            <p className="mt-1 text-[10px] opacity-70">{dateTime(m.created_at)}</p>
          </div>
        ))}
      </div>
      <div className="mt-3 flex flex-col gap-2">
        <Textarea rows={3} value={body} onChange={(e) => setBody(e.target.value)} placeholder="Balas..." />
        <p className="text-xs text-ink-tertiary">{data.data?.security_notice}</p>
        <div><Button leftIcon={<Send className="size-4" />} disabled={!body.trim()} isLoading={action.busy} onClick={reply}>Kirim</Button></div>
      </div>
    </Card>
  )
}

interface BroadcastRow { id: string; title: string; channel: string; status: string; audience_filter: Record<string, unknown>; content_subject: string | null; content_body: string; scheduled_at: string | null; recipients_count: number | null; sent_count: number | null; failed_count: number | null; created_at: string | null }

function Broadcasts() {
  const lookups = useLookups()
  const [reload, setReload] = useState(0)
  const [editing, setEditing] = useState<BroadcastRow | 'new' | null>(null)
  const [scheduling, setScheduling] = useState<BroadcastRow | null>(null)
  const action = useAction()
  const fields = [
    { name: 'title', label: 'Judul internal', required: true },
    { name: 'channel', label: 'Kanal', type: 'select' as const, required: true, options: [{ value: 'email', label: 'Email' }, { value: 'sms', label: 'SMS/WA (maks 160 karakter)' }] },
    { name: 'period_id', label: 'Audiens: periode', type: 'select' as const, options: options(lookups?.periods, (p) => p.name) },
    { name: 'admission_path_id', label: 'Audiens: jalur', type: 'select' as const, options: options(lookups?.admission_paths, (p) => p.name) },
    { name: 'form_status', label: 'Audiens: status formulir', type: 'multiselect' as const, options: ['draft', 'submitted', 'revision_requested'].map((v) => ({ value: v, label: statusLabel(v) })) },
    { name: 'registration_payment_status', label: 'Audiens: pembayaran', type: 'multiselect' as const, options: ['unpaid', 'pending', 'paid'].map((v) => ({ value: v, label: statusLabel(v) })) },
    { name: 'decision', label: 'Audiens: keputusan', type: 'multiselect' as const, options: ['accepted', 'waitlisted', 'rejected'].map((v) => ({ value: v, label: statusLabel(v) })) },
    { name: 'marketing', label: 'Hanya yang menyetujui informasi promosi', type: 'checkbox' as const },
    { name: 'content_subject', label: 'Subjek (email)' },
    { name: 'content_body', label: 'Isi pesan (tanpa NIK/data sensitif)', type: 'textarea' as const, required: true },
  ]
  const toBody = (v: Record<string, unknown>) => ({ title: v.title, channel: v.channel, content_subject: v.content_subject, content_body: v.content_body, audience_filter: Object.fromEntries(['period_id', 'admission_path_id', 'form_status', 'registration_payment_status', 'decision', 'marketing'].map((k) => [k, v[k]]).filter(([, x]) => x !== null && x !== undefined && x !== '' && !(Array.isArray(x) && x.length === 0) && x !== false)) })

  return (
    <>
      <Feedback state={action} />
      <AdminList<BroadcastRow> path="broadcasts" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="broadcast" toolbar={<Button size="sm" onClick={() => setEditing('new')}>Broadcast baru</Button>} columns={[
        { header: 'Judul', cell: (r) => <span>{r.title}<span className="block text-xs text-ink-tertiary">{r.channel}</span></span> },
        { header: 'Jadwal', cell: (r) => dateTime(r.scheduled_at) }, { header: 'Penerima', cell: (r) => r.recipients_count ?? '—' },
        { header: 'Terkirim/gagal', cell: (r) => `${r.sent_count ?? 0}/${r.failed_count ?? 0}` }, { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
        { header: '', cell: (r) => (
          <div className="flex gap-1">
            {r.status === 'draft' && <><Button size="sm" variant="ghost" onClick={() => setEditing(r)}>Ubah</Button><Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post<{ count: number }>(`broadcasts/${r.id}/preview-audience`), (x) => `${x.count} penerima.`)}>Hitung penerima</Button><Button size="sm" onClick={() => setScheduling(r)}>Kirim/jadwalkan</Button></>}
            {['draft', 'scheduled'].includes(r.status) && <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post(`broadcasts/${r.id}/cancel`), 'Dibatalkan.').then(() => setReload(reload + 1))}>Batalkan</Button>}
          </div>
        ) },
      ]} />
      <FormDialog open={editing !== null} wide title="Broadcast" fields={fields} initial={editing && editing !== 'new' ? { ...editing, ...editing.audience_filter } : { channel: 'email' }} onClose={() => setEditing(null)} onSubmit={async (v) => {
        if (editing === 'new') await pmbAdmin.post('broadcasts', toBody(v))
        else if (editing) await pmbAdmin.patch(`broadcasts/${editing.id}`, toBody(v))
        setReload(reload + 1)
      }} />
      <FormDialog open={scheduling !== null} title="Kirim broadcast" description="Kosongkan jadwal untuk mengirim sekarang." fields={[{ name: 'scheduled_at', label: 'Jadwal kirim', type: 'datetime' }]} onClose={() => setScheduling(null)} onSubmit={async (v) => { if (scheduling) await pmbAdmin.post(`broadcasts/${scheduling.id}/schedule`, v); setReload(reload + 1) }} />
    </>
  )
}

interface TemplateRow { event_key: string; default_channels: string[]; priority: string; default_subject: string; default_body: string; placeholders: string[]; overrides: Array<{ id: string; channel: string; subject: string | null; body: string; is_active: boolean }> }

function Templates() {
  const list = useLoad(() => pmbAdmin.get<TemplateRow[]>('notification-templates'), [])
  const [editing, setEditing] = useState<{ row: TemplateRow; channel: string } | null>(null)
  const action = useAction()

  return (
    <>
      <Feedback state={action} />
      <Card noPadding>
        <DataTable rowKey={(r) => r.event_key} data={list.data ?? []} emptyMessage="Memuat..." columns={[
          { header: 'Peristiwa', cell: (r) => <span>{r.default_subject}<span className="block font-mono text-xs text-ink-tertiary">{r.event_key}</span></span> },
          { header: 'Prioritas', cell: (r) => r.priority },
          { header: 'Kanal', cell: (r) => r.default_channels.join(', ') },
          { header: 'Kustom', cell: (r) => (r.overrides.length ? r.overrides.map((o) => `${o.channel}${o.is_active ? '' : ' (nonaktif)'}`).join(', ') : 'Default') },
          { header: '', cell: (r) => (
            <div className="flex gap-1">
              {['mail', 'database', 'sms'].map((ch) => <Button key={ch} size="sm" variant="ghost" onClick={() => setEditing({ row: r, channel: ch })}>{ch}</Button>)}
              <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post('notification-templates/test', { event_key: r.event_key }), 'Email uji dikirim ke alamat Anda.')}>Uji</Button>
            </div>
          ) },
        ]} />
      </Card>
      <FormDialog open={editing !== null} wide title={`Template ${editing?.row.event_key} (${editing?.channel})`} description={`Placeholder: ${(editing?.row.placeholders ?? []).map((p) => `{{${p}}}`).join(' ')}`} fields={[
        { name: 'subject', label: 'Subjek' }, { name: 'body', label: 'Isi', type: 'textarea', required: true }, { name: 'is_active', label: 'Aktif (nonaktif = pakai default)', type: 'checkbox' },
      ]} initial={editing ? (() => { const o = editing.row.overrides.find((x) => x.channel === editing.channel); return { subject: o?.subject ?? editing.row.default_subject, body: o?.body ?? editing.row.default_body, is_active: o?.is_active ?? true } })() : undefined} onClose={() => setEditing(null)} onSubmit={async (v) => { if (editing) await pmbAdmin.put('notification-templates', { ...v, event_key: editing.row.event_key, channel: editing.channel, is_active: Boolean(v.is_active) }); list.reload() }} />
    </>
  )
}

function NotificationLogs() {
  return (
    <AdminList<{ id: string; event_type: string; event_key: string | null; aggregate_type: string; status: string; attempts: number; pending_channels: string[] | null; last_error: string | null; dispatched_at: string | null; created_at: string | null }>
      path="notification-logs" search={false} rowKey={(r) => r.id} itemLabel="log"
      filters={[{ name: 'status', label: 'Status', type: 'select', options: ['pending', 'dispatched', 'failed'].map((v) => ({ value: v, label: statusLabel(v) })) }]}
      columns={[
        { header: 'Peristiwa', cell: (r) => <span className="font-mono text-xs">{r.event_key ?? r.event_type}</span> }, { header: 'Objek', cell: (r) => r.aggregate_type },
        { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> }, { header: 'Percobaan', cell: (r) => r.attempts },
        { header: 'Kanal tertunda', cell: (r) => (r.pending_channels ?? []).join(', ') || '—' }, { header: 'Galat', cell: (r) => <span className="whitespace-normal text-xs">{r.last_error ?? '—'}</span> },
        { header: 'Dibuat', cell: (r) => dateTime(r.created_at) }, { header: 'Terkirim', cell: (r) => dateTime(r.dispatched_at) },
      ]} />
  )
}

type AuTab = 'summary' | 'audit' | 'sensitive' | 'exports'

/** ADM-07/70 Audit PMB (baca saja). */
export function AuditPage() {
  const [tab, setTab] = useState<AuTab>('summary')
  const summary = useLoad(() => pmbAdmin.get<{ audit_events: number; sensitive_access: number; sensitive_by_user: Array<{ user: string | null; total: number }>; exports: number; personal_exports: number; entities: string[] }>('audit/summary'), [])
  const [detail, setDetail] = useState<{ old_values: unknown; new_values: unknown } | null>(null)
  const params: Params = {}

  return (
    <AdminPage title="Audit PMB" trail={[{ label: 'Audit' }]}>
      <Tabs<AuTab> active={tab} onChange={setTab} tabs={[{ key: 'summary', label: 'Ringkasan 30 hari' }, { key: 'audit', label: 'Jejak perubahan' }, { key: 'sensitive', label: 'Akses data sensitif' }, { key: 'exports', label: 'Log ekspor' }]} />
      {tab === 'summary' && summary.data && (
        <div className="grid gap-4 md:grid-cols-2">
          <Card title="Aktivitas"><ul className="space-y-1 text-sm"><li>Perubahan tercatat: <strong>{summary.data.audit_events}</strong></li><li>Akses data sensitif: <strong>{summary.data.sensitive_access}</strong></li><li>Ekspor: <strong>{summary.data.exports}</strong> ({summary.data.personal_exports} berisi data pribadi)</li></ul></Card>
          <Card title="Akses sensitif per pengguna"><ul className="space-y-1 text-sm">{summary.data.sensitive_by_user.map((u) => <li key={u.user ?? '-'} className="flex justify-between"><span>{u.user}</span><span>{u.total}</span></li>)}</ul></Card>
        </div>
      )}
      {tab === 'audit' && (
        <AdminList<{ id: string; entity: string; entity_id: string; action: string; user: string; old_values: unknown; new_values: unknown; reason: string | null; ip_address: string | null; created_at: string | null }> path="audit-logs" baseParams={params} search={false} rowKey={(r) => r.id} itemLabel="entri"
          filters={[{ name: 'entity', label: 'Objek', type: 'select', options: (summary.data?.entities ?? []).map((e) => ({ value: e, label: e })) }, { name: 'entity_id', label: 'ID objek' }, { name: 'from', label: 'Dari', type: 'date' }, { name: 'to', label: 'Sampai', type: 'date' }]}
          columns={[
            { header: 'Waktu', cell: (r) => dateTime(r.created_at) }, { header: 'Pengguna', cell: (r) => r.user }, { header: 'Aksi', cell: (r) => r.action },
            { header: 'Objek', cell: (r) => <span>{r.entity}<span className="block font-mono text-xs text-ink-tertiary">{r.entity_id}</span></span> },
            { header: 'Alasan', cell: (r) => <span className="whitespace-normal">{r.reason ?? '—'}</span> },
            { header: '', cell: (r) => <Button size="sm" variant="ghost" onClick={() => setDetail(r)}>Perubahan</Button> },
          ]} />
      )}
      {tab === 'sensitive' && (
        <AdminList<{ id: string; user: string | null; subject: string; subject_id: string; field_or_document: string; purpose: string; ip_address: string | null; created_at: string | null }> path="sensitive-access-logs" search={false} rowKey={(r) => r.id} itemLabel="akses"
          filters={[{ name: 'subject_id', label: 'ID subjek' }, { name: 'from', label: 'Dari', type: 'date' }]}
          columns={[{ header: 'Waktu', cell: (r) => dateTime(r.created_at) }, { header: 'Pengguna', cell: (r) => r.user }, { header: 'Data', cell: (r) => `${r.subject} · ${r.field_or_document}` }, { header: 'Tujuan', cell: (r) => <span className="whitespace-normal">{r.purpose}</span> }, { header: 'IP', cell: (r) => r.ip_address }]} />
      )}
      {tab === 'exports' && (
        <AdminList<{ id: string; user: string | null; report_code: string; format: string; row_count: number | null; reason: string; contains_personal_data: boolean; status: string; created_at: string | null }> path="export-logs" search={false} rowKey={(r) => r.id} itemLabel="ekspor"
          filters={[{ name: 'personal_only', label: 'Data pribadi', type: 'select', options: [{ value: '1', label: 'Hanya data pribadi' }] }]}
          columns={[{ header: 'Waktu', cell: (r) => dateTime(r.created_at) }, { header: 'Pengguna', cell: (r) => r.user }, { header: 'Laporan', cell: (r) => `${r.report_code} (${r.format})` }, { header: 'Baris', cell: (r) => r.row_count ?? '—' }, { header: 'Alasan', cell: (r) => <span className="whitespace-normal">{r.reason}</span> }, { header: 'Pribadi', cell: (r) => (r.contains_personal_data ? 'Ya' : 'Tidak') }, { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> }]} />
      )}
      {detail && (
        <Card title="Detail perubahan" actions={<Button size="sm" variant="ghost" onClick={() => setDetail(null)}>Tutup</Button>}>
          <div className="grid gap-3 md:grid-cols-2"><div><p className="text-xs text-ink-tertiary">Sebelum</p><pre className="overflow-x-auto rounded bg-surface-hover p-2 text-xs">{JSON.stringify(detail.old_values, null, 2)}</pre></div><div><p className="text-xs text-ink-tertiary">Sesudah</p><pre className="overflow-x-auto rounded bg-surface-hover p-2 text-xs">{JSON.stringify(detail.new_values, null, 2)}</pre></div></div>
        </Card>
      )}
    </AdminPage>
  )
}
