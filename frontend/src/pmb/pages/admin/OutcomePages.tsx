import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { pmbAdmin } from '@/pmb/api/admin'
import { adminClient } from '@/pmb/api/http'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminList } from '@/pmb/components/AdminList'
import { FormDialog } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { Tabs } from '@/pmb/components/Tabs'
import { KeyValue } from '@/pmb/components/KeyValue'
import { AdminPage, Can, P, options, useLookups } from '@/pmb/components/admin'
import { usePermission } from '@/hooks/usePermission'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { Select } from '@/components/ui/Select'
import { DataTable } from '@/components/ui/DataTable'
import { dateTime, money } from '@/pmb/lib/format'
import { DECISION_LABELS, statusLabel } from '@/pmb/lib/labels'
import { PMB, to } from '@/pmb/routes'

type ATab = 'decisions' | 'batches' | 'seats' | 'changes'

/** ADM-61..63 Pengumuman: keputusan, batch publikasi, kursi & cadangan, koreksi hasil. */
export function AnnouncementsPage() {
  const canBatches = usePermission(P.RESULTS_READ)
  const canSeats = usePermission(P.WAITLIST)
  const canCorrect = usePermission(P.RESULTS_CORRECT)
  const [tab, setTab] = useState<ATab>('decisions')

  return (
    <AdminPage title="Pengumuman & Keputusan" trail={[{ label: 'Pengumuman' }]}>
      <Tabs<ATab> active={tab} onChange={setTab} tabs={[
        { key: 'decisions', label: 'Keputusan' },
        ...(canBatches ? [{ key: 'batches' as ATab, label: 'Jadwal publikasi' }] : []),
        ...(canSeats ? [{ key: 'seats' as ATab, label: 'Kursi & cadangan' }] : []),
        ...(canCorrect ? [{ key: 'changes' as ATab, label: 'Koreksi & pengajuan' }] : []),
      ]} />
      {tab === 'decisions' && <DecisionsTab />}
      {tab === 'batches' && <BatchesTab />}
      {tab === 'seats' && <SeatsTab />}
      {tab === 'changes' && <ChangesTab />}
    </AdminPage>
  )
}

interface DecisionRow { id: string; application_id: string; registration_number: string | null; name: string | null; email: string | null; decision: string; version: number; source: string; program: string | null; study_system: string | null; waitlist_rank: number | null; publication_status: string; published_at: string | null; letter: { number: string; status: string } | null; re_registration_status: string | null; scholarship_award: { percent?: number; scholarship_id?: string | null } | null }

function DecisionsTab() {
  const lookups = useLookups()
  const [reload, setReload] = useState(0)
  const [award, setAward] = useState<DecisionRow | null>(null)
  const canAward = usePermission(P.SELECTION_MANAGE)
  const canUnpublished = usePermission(P.RESULTS_READ)

  return (
    <>
      <AdminList<DecisionRow> path="decisions" reloadKey={reload} rowKey={(r) => r.id} itemLabel="keputusan"
        filters={[
          { name: 'period_id', label: 'Periode', type: 'select', options: options(lookups?.periods, (p) => p.name) },
          { name: 'decision', label: 'Keputusan', type: 'select', options: Object.entries(DECISION_LABELS).map(([value, label]) => ({ value, label })) },
          { name: 'study_program_id', label: 'Prodi', type: 'select', options: options(lookups?.study_programs, (p) => p.name) },
          ...(canUnpublished ? [{ name: 'publication_status', label: 'Publikasi', type: 'select' as const, options: [{ value: 'unpublished', label: 'Belum terbit' }, { value: 'published', label: 'Terbit' }] }] : []),
        ]}
        columns={[
          { header: 'Pendaftar', cell: (r) => <Link className="text-primary" to={to(PMB.admin.application, { id: r.application_id })}>{r.name}<span className="block text-xs text-ink-tertiary">{r.registration_number}</span></Link> },
          { header: 'Keputusan', cell: (r) => <span><StatusBadge status={r.decision} label={DECISION_LABELS[r.decision]} /><span className="block text-xs text-ink-tertiary">v{r.version} · {r.source}{r.waitlist_rank ? ` · cadangan #${r.waitlist_rank}` : ''}</span></span> },
          { header: 'Prodi', cell: (r) => (r.program ? `${r.program} · ${r.study_system}` : '—') },
          { header: 'Publikasi', cell: (r) => <span><StatusBadge status={r.publication_status} /><span className="block text-xs text-ink-tertiary">{dateTime(r.published_at)}</span></span> },
          { header: 'LoA', cell: (r) => (r.letter ? `${r.letter.number} (${statusLabel(r.letter.status)})` : '—') },
          { header: 'Daftar ulang', cell: (r) => (r.re_registration_status ? <StatusBadge status={r.re_registration_status} /> : '—') },
          { header: 'Beasiswa', cell: (r) => (r.scholarship_award?.percent ? `${r.scholarship_award.percent}%` : '—') },
          { header: '', cell: (r) => canAward && r.decision === 'accepted' && <Button size="sm" variant="ghost" onClick={() => setAward(r)}>Beasiswa</Button> },
        ]} />
      <ReasonDialog open={award !== null} title="Beasiswa jalur masuk" description="Potongan diterapkan pada tagihan biaya awal; program beasiswa diteruskan ke modul Beasiswa saat konversi." reasonName="note" reasonLabel="Catatan" extraFields={[
        { name: 'scholarship_id', label: 'Program beasiswa', type: 'select', options: options(lookups?.scholarships, (s) => s.name) },
        { name: 'percent', label: 'Potongan (%)', type: 'number', required: true },
      ]} onClose={() => setAward(null)} onConfirm={async (v) => { if (award) await pmbAdmin.post(`decisions/${award.id}/scholarship-award`, v); setReload(reload + 1) }} />
    </>
  )
}

function BatchesTab() {
  const [reload, setReload] = useState(0)
  const [dialog, setDialog] = useState<{ kind: 'reschedule' | 'cancel'; id: string } | null>(null)
  const action = useAction()

  return (
    <>
      <Feedback state={action} />
      <Alert variant="info">Batch dibuat otomatis saat hasil disahkan, dijadwalkan pada waktu pengumuman gelombang. Mempercepat jadwal memerlukan persetujuan.</Alert>
      <AdminList<{ id: string; status: string; selection_run_id: string; wave: string | null; path: string | null; scheduled_publish_at: string; published_at: string | null; decisions_count: number; failure_reason: string | null }>
        path="announcement-batches" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="batch"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: ['scheduled', 'publishing', 'published', 'failed', 'cancelled'].map((v) => ({ value: v, label: statusLabel(v) })) }]}
        columns={[
          { header: 'Gelombang · jalur', cell: (r) => <Link className="text-primary" to={to(PMB.admin.selectionRun, { id: r.selection_run_id })}>{r.wave} · {r.path}</Link> },
          { header: 'Jadwal', cell: (r) => dateTime(r.scheduled_publish_at) }, { header: 'Terbit', cell: (r) => dateTime(r.published_at) },
          { header: 'Keputusan', cell: (r) => r.decisions_count }, { header: 'Status', cell: (r) => <span><StatusBadge status={r.status} />{r.failure_reason && <span className="block text-xs text-danger">{r.failure_reason}</span>}</span> },
          { header: '', cell: (r) => r.status === 'scheduled' && <Can permission={P.CONFIG_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => setDialog({ kind: 'reschedule', id: r.id })}>Ubah jadwal</Button><Button size="sm" variant="ghost" onClick={() => setDialog({ kind: 'cancel', id: r.id })}>Batalkan</Button></div></Can> },
        ]} />
      <ReasonDialog open={dialog?.kind === 'reschedule'} title="Ubah jadwal publikasi" extraFields={[{ name: 'publish_at', label: 'Jadwal baru', type: 'datetime', required: true }]} onClose={() => setDialog(null)} onConfirm={async (v) => {
        if (!dialog) return
        const res = await adminClient.patch(`/pmb/admin/announcement-batches/${dialog.id}`, v)
        if (res.status === 202) action.run(() => Promise.resolve(), 'Percepatan diajukan untuk persetujuan.')
        setReload(reload + 1)
      }} />
      <ReasonDialog open={dialog?.kind === 'cancel'} title="Batalkan publikasi" onClose={() => setDialog(null)} onConfirm={async (v) => { if (dialog) await pmbAdmin.post(`announcement-batches/${dialog.id}/cancel`, v); setReload(reload + 1) }} />
    </>
  )
}

function SeatsTab() {
  const lookups = useLookups()
  const [period, setPeriod] = useState('')
  const seats = useLoad(() => (period ? pmbAdmin.get<Array<{ quota_id: string; program: string; study_system: string; path: string; capacity: number; accepted_active: number; free_seats: number; waitlist_remaining: number; next_candidate: { application_id: string; waitlist_rank: number } | null }>>(`periods/${period}/seats`) : Promise.resolve([])), [period])
  const [promoting, setPromoting] = useState<{ quota_id: string; free_seats: number } | null>(null)

  return (
    <Card noPadding title="Kursi & cadangan" actions={<Select options={options(lookups?.periods, (p) => p.name)} placeholder="Pilih periode" value={period} onChange={(e) => setPeriod(e.target.value)} />}>
      <DataTable rowKey={(r) => r.quota_id} data={seats.data ?? []} emptyMessage={period ? 'Belum ada kuota.' : 'Pilih periode.'} columns={[
        { header: 'Prodi', cell: (r) => `${r.program} · ${r.study_system}` }, { header: 'Jalur', cell: (r) => r.path },
        { header: 'Kapasitas', cell: (r) => r.capacity }, { header: 'Diterima aktif', cell: (r) => r.accepted_active },
        { header: 'Kursi kosong', cell: (r) => (r.free_seats > 0 ? <Badge variant="warning">{r.free_seats}</Badge> : 0) },
        { header: 'Cadangan tersisa', cell: (r) => r.waitlist_remaining },
        { header: '', cell: (r) => r.free_seats > 0 && r.waitlist_remaining > 0 && <Button size="sm" onClick={() => setPromoting(r)}>Promosikan</Button> },
      ]} />
      <FormDialog open={promoting !== null} title="Promosikan cadangan" description="Peserta cadangan teratas diterima, diberi tagihan biaya awal dan batas daftar ulang individual." fields={[{ name: 'count', label: 'Jumlah', type: 'number', required: true }, { name: 'deadline', label: 'Batas daftar ulang (kosong = default)', type: 'datetime' }]} initial={{ count: promoting?.free_seats ?? 1 }} onClose={() => setPromoting(null)} onSubmit={async (v) => { if (promoting) await pmbAdmin.post(`quotas/${promoting.quota_id}/promote-waitlist`, v, { idempotent: true }); seats.reload() }} />
    </Card>
  )
}

function ChangesTab() {
  const [reload, setReload] = useState(0)
  const [requesting, setRequesting] = useState(false)

  return (
    <>
      <AdminList<{ id: string; type: string; status: string; reason: string; payload: Record<string, unknown>; requested_by: string | null; approval: { id: string; status: string } | null; result_note: string | null; created_at: string | null; applied_at: string | null }>
        path="change-requests" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="pengajuan"
        toolbar={<Button size="sm" onClick={() => setRequesting(true)}>Ajukan koreksi hasil</Button>}
        filters={[{ name: 'type', label: 'Jenis', type: 'select', options: ['score_correction', 'result_correction', 're_registration_cancellation', 'bulk_extension', 'publication_acceleration', 'bulk_refund'].map((v) => ({ value: v, label: v.replace(/_/g, ' ') })) }]}
        columns={[
          { header: 'Jenis', cell: (r) => r.type.replace(/_/g, ' ') }, { header: 'Alasan', cell: (r) => <span className="whitespace-normal">{r.reason}</span> },
          { header: 'Pengaju', cell: (r) => r.requested_by }, { header: 'Diajukan', cell: (r) => dateTime(r.created_at) },
          { header: 'Status', cell: (r) => <span><StatusBadge status={r.status} />{r.result_note && <span className="block text-xs text-ink-tertiary">{r.result_note}</span>}</span> },
          { header: '', cell: (r) => r.approval && <Link className="text-sm text-primary" to={`/persetujuan/${r.approval.id}`}>Persetujuan</Link> },
        ]} />
      <FormDialog open={requesting} title="Ajukan koreksi hasil (setelah terbit)" description="Tanpa usulan → dibuat run koreksi untuk aplikasi tersebut. Dengan usulan → diterapkan langsung setelah disetujui. Pendaftar diberi tahu." fields={[
        { name: 'application_ids', label: 'ID aplikasi (pisahkan dengan koma)', type: 'textarea', required: true },
        { name: 'proposed', label: 'Usulan (JSON opsional: {"<id aplikasi>": {"decision":"accepted","quota_id":"..."}})', type: 'json' },
        { name: 'reason', label: 'Alasan', type: 'textarea', required: true },
        { name: 'evidence', label: 'Bukti (opsional)', type: 'file' },
      ]} onClose={() => setRequesting(false)} onSubmit={async (v) => {
        const form = new FormData()
        String(v.application_ids).split(/[\s,]+/).filter(Boolean).forEach((x) => form.append('application_ids[]', x))
        form.append('reason', String(v.reason))
        if (v.proposed && typeof v.proposed === 'object') Object.entries(v.proposed as Record<string, { decision: string; quota_id?: string }>).forEach(([appId, p]) => { form.append(`proposed[${appId}][decision]`, p.decision); if (p.quota_id) form.append(`proposed[${appId}][quota_id]`, p.quota_id) })
        if (v.evidence instanceof File) form.append('evidence', v.evidence)
        await adminClient.post('/pmb/admin/result-corrections', form)
        setReload(reload + 1)
      }} />
    </>
  )
}

const CHECK_LABELS: Record<string, string> = {
  OFFER_ACCEPTED: 'Tawaran diterima', PDDIKTI_DATA_COMPLETE: 'Data PDDikti lengkap', GUARDIAN_DATA_COMPLETE: 'Data orang tua lengkap', FINAL_DOCUMENTS_APPROVED: 'Dokumen final disetujui',
  HEALTH_CLEARED: 'Surat sehat', PAYMENT_MINIMUM_MET: 'Pembayaran minimum', ORIGINAL_DOCUMENTS_CHECKED: 'Dokumen asli diperiksa', NO_OPEN_DUPLICATE_FLAG: 'Tidak ada indikasi duplikat',
}

type RTab = 'queue' | 'conversions'

/** ADM-64/66 Antrean daftar ulang & konversi mahasiswa. */
export function ReRegistrationsPage() {
  const lookups = useLookups()
  const canConversions = usePermission(P.CONVERSION_READ)
  const [tab, setTab] = useState<RTab>('queue')
  const [bulk, setBulk] = useState(false)
  const [reload, setReload] = useState(0)

  return (
    <AdminPage title="Daftar Ulang" trail={[{ label: 'Daftar ulang' }]} actions={<Can permission={P.REREG_EXTEND}><Button variant="outline" onClick={() => setBulk(true)}>Perpanjangan massal</Button></Can>}>
      <Tabs<RTab> active={tab} onChange={setTab} tabs={[{ key: 'queue', label: 'Antrean' }, ...(canConversions ? [{ key: 'conversions' as RTab, label: 'Konversi mahasiswa' }] : [])]} />
      {tab === 'queue' && (
        <AdminList<{ id: string; status: string; application_id: string; registration_number: string | null; name: string | null; program: string | null; study_system: string | null; deadline_at: string; extended: boolean; submitted_at: string | null }>
          path="re-registrations" reloadKey={reload} rowKey={(r) => r.id} itemLabel="daftar ulang"
          filters={[
            { name: 'status', label: 'Status', type: 'select', options: ['awaiting_confirmation', 'confirmed', 'in_progress', 'submitted', 'revision_requested', 'approved', 'enrolled', 'declined', 'expired', 'cancelled'].map((v) => ({ value: v, label: statusLabel(v) })) },
            { name: 'period_id', label: 'Periode', type: 'select', options: options(lookups?.periods, (p) => p.name) },
            { name: 'study_program_id', label: 'Prodi', type: 'select', options: options(lookups?.study_programs, (p) => p.name) },
          ]}
          columns={[
            { header: 'Pendaftar', cell: (r) => <Link className="text-primary" to={to(PMB.admin.reRegistration, { id: r.id })}>{r.name}<span className="block text-xs text-ink-tertiary">{r.registration_number}</span></Link> },
            { header: 'Prodi', cell: (r) => `${r.program} · ${r.study_system}` },
            { header: 'Batas', cell: (r) => <span>{dateTime(r.deadline_at)}{r.extended && <Badge className="ml-1">diperpanjang</Badge>}</span> },
            { header: 'Dikirim', cell: (r) => dateTime(r.submitted_at) }, { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
          ]} />
      )}
      {tab === 'conversions' && <ConversionsTab />}
      <ReasonDialog open={bulk} title="Perpanjangan massal daftar ulang" description="Mis. karena gangguan sistem pembayaran. Memerlukan persetujuan pejabat pengesah." extraFields={[
        { name: 'period_id', label: 'Periode', type: 'select', options: options(lookups?.periods, (p) => p.name) },
        { name: 'study_program_id', label: 'Prodi (opsional)', type: 'select', options: options(lookups?.study_programs, (p) => p.name) },
        { name: 'until', label: 'Batas baru', type: 'datetime', required: true },
      ]} onClose={() => setBulk(false)} onConfirm={async (v) => { await pmbAdmin.post('re-registrations/bulk-extensions', { filter: { period_id: v.period_id, study_program_id: v.study_program_id }, until: v.until, reason: v.reason }); setReload(reload + 1) }} />
    </AdminPage>
  )
}

function ConversionsTab() {
  const [reload, setReload] = useState(0)
  const [retrying, setRetrying] = useState<{ id: string; last_error_code: string | null } | null>(null)

  return (
    <>
      <AdminList<{ id: string; status: string; nim: string | null; application_id: string; registration_number: string | null; name: string | null; attempts: number; steps_completed: string[] | null; last_error_code: string | null; last_error_message: string | null; next_attempt_at: string | null; succeeded_at: string | null }>
        path="conversions" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="konversi"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: ['pending', 'processing', 'succeeded', 'failed', 'manual_intervention'].map((v) => ({ value: v, label: statusLabel(v) })) }]}
        columns={[
          { header: 'Pendaftar', cell: (r) => <Link className="text-primary" to={to(PMB.admin.application, { id: r.application_id })}>{r.name}<span className="block text-xs text-ink-tertiary">{r.registration_number}</span></Link> },
          { header: 'NIM', cell: (r) => <span className="font-mono">{r.nim ?? '—'}</span> },
          { header: 'Langkah', cell: (r) => (r.steps_completed ?? []).length + '/4' },
          { header: 'Percobaan', cell: (r) => r.attempts },
          { header: 'Galat terakhir', cell: (r) => (r.last_error_code ? <span className="whitespace-normal text-danger">{r.last_error_code}: {r.last_error_message}</span> : '—') },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
          { header: '', cell: (r) => ['failed', 'manual_intervention'].includes(r.status) && <Can permission={P.CONVERSION_RETRY}><Button size="sm" variant="ghost" onClick={() => setRetrying(r)}>Coba lagi</Button></Can> },
        ]} />
      <FormDialog open={retrying !== null} title="Ulangi konversi" description={retrying?.last_error_code === 'USER_EMAIL_CONFLICT' ? 'Email pendaftar sudah dipakai akun lain — isi email pengganti untuk akun mahasiswa.' : 'Langkah yang sudah selesai tidak diulang.'} fields={[{ name: 'email_override', label: 'Email akun mahasiswa pengganti (opsional)', type: 'email' }]} onClose={() => setRetrying(null)} onSubmit={async (v) => { if (retrying) await pmbAdmin.post(`conversions/${retrying.id}/retry`, v); setReload(reload + 1) }} />
    </>
  )
}

interface ReRegDetail { id: string; status: string; lock_version: number; application_id: string; registration_number: string | null; name: string; email: string; program: string | null; study_system: string | null; deadline_at: string; extended_until: string | null; extension_reason: string | null; revision_targets: { fields?: string[]; documents?: string[] } | null; almamater_size: string | null; declarations: unknown; pddikti: Record<string, unknown> | null; guardians: Array<Record<string, unknown>>; checks: Record<string, { status: string; evaluation: string; note: string | null }>; documents: Array<{ id: string; type: string | null; code: string | null; status: string; is_mandatory: boolean; current_version_id: string | null; requires_original_check: boolean }>; invoice: { id: string; invoice_number: string; amount_net: string; amount_paid: string; minimum_for_enrollment: string | null; status: string } | null; conversion: { id: string; status: string; nim: string | null } | null; approved_by: string | null; timestamps: Record<string, string | null> }

/** ADM-65 Pemeriksaan daftar ulang: checklist, dokumen final, pembayaran, setujui → konversi. */
export function ReRegistrationDetailPage() {
  const { id = '' } = useParams()
  const rr = useLoad(() => pmbAdmin.get<ReRegDetail>(`re-registrations/${id}`), [id])
  const action = useAction()
  const canDecide = usePermission(P.REREG_DECIDE)
  const [dialog, setDialog] = useState<null | 'revision' | 'extend' | 'restore' | 'cancel' | { check: string }>(null)
  const [review, setReview] = useState<ReRegDetail['documents'][number] | null>(null)

  if (!rr.data) return <p className="text-sm text-ink-secondary">{rr.error?.message ?? 'Memuat...'}</p>

  const d = rr.data
  const allPassed = Object.values(d.checks).every((c) => ['passed', 'not_applicable'].includes(c.status))

  return (
    <AdminPage title={d.name} description={`${d.registration_number} · ${d.program} · ${d.study_system}`} trail={[{ label: 'Daftar ulang', path: PMB.admin.reRegistrations }, { label: d.registration_number ?? '' }]}
      actions={
        <div className="flex flex-wrap gap-2">
          <StatusBadge status={d.status} />
          {canDecide && d.status === 'submitted' && <><Button size="sm" disabled={!allPassed} onClick={() => action.run(() => pmbAdmin.post<{ conversion_status: string }>(`re-registrations/${id}/approve`, { lock_version: d.lock_version }, { idempotent: true }), 'Disetujui; konversi mahasiswa diproses.').then(rr.reload)}>Setujui</Button><Button size="sm" variant="outline" onClick={() => setDialog('revision')}>Minta perbaikan</Button></>}
          <Can permission={P.REREG_EXTEND}>
            {['awaiting_confirmation', 'confirmed', 'in_progress', 'revision_requested'].includes(d.status) && <Button size="sm" variant="outline" onClick={() => setDialog('extend')}>Perpanjang</Button>}
            {['declined', 'expired'].includes(d.status) && <Button size="sm" variant="outline" onClick={() => setDialog('restore')}>Pulihkan</Button>}
          </Can>
          <Can permission={P.RESULTS_CORRECT}>{!['cancelled', 'declined', 'expired'].includes(d.status) && <Button size="sm" variant="ghost" onClick={() => setDialog('cancel')}>Batalkan penerimaan</Button>}</Can>
        </div>
      }>
      <Feedback state={action} />
      {canDecide && d.status === 'submitted' && !allPassed && <Alert variant="warning">Semua pemeriksaan harus lolos sebelum disetujui.</Alert>}
      <div className="grid gap-4 lg:grid-cols-2">
        <Card title="Pemeriksaan">
          <ul className="space-y-1 text-sm">
            {Object.entries(d.checks).map(([code, c]) => (
              <li key={code} className="flex items-center justify-between gap-2 rounded border border-border px-3 py-1.5">
                <span>{CHECK_LABELS[code] ?? code}{c.note && <span className="block text-xs text-ink-tertiary">{c.note}</span>}</span>
                <span className="flex items-center gap-1"><StatusBadge status={c.status} />{c.evaluation === 'manual' && canDecide && <Button size="sm" variant="ghost" onClick={() => setDialog({ check: code })}>Tandai</Button>}</span>
              </li>
            ))}
          </ul>
        </Card>
        <Card title="Ringkasan">
          <KeyValue items={[
            ['Batas', dateTime(d.deadline_at)], ['Perpanjangan', d.extended_until ? `${dateTime(d.extended_until)} (${d.extension_reason})` : '—'],
            ['Tagihan biaya awal', d.invoice ? `${money(d.invoice.amount_paid)} / ${money(d.invoice.amount_net)} (${statusLabel(d.invoice.status)})` : '—'],
            ['Minimum daftar ulang', money(d.invoice?.minimum_for_enrollment)], ['Ukuran almamater', d.almamater_size],
            ['NIM', d.conversion?.nim ?? '—'], ['Disetujui oleh', d.approved_by],
            ...Object.entries(d.timestamps).filter(([, v]) => v).map(([k, v]) => [k.replace('_at', ''), dateTime(v)] as [string, string]),
          ]} />
        </Card>
        <Card title="Data PDDikti"><KeyValue items={Object.entries(d.pddikti ?? {}).map(([k, v]) => [k.replace(/_/g, ' '), v === null ? null : String(v)])} /></Card>
        <Card title="Orang tua / wali">{d.guardians.map((g, i) => <p key={i} className="text-sm">{String(g.full_name)} ({String(g.relationship)}) · {String(g.phone ?? '—')} · {String(g.occupation ?? '—')}</p>)}</Card>
      </div>
      <Card title="Dokumen daftar ulang" noPadding>
        <DataTable rowKey={(x) => x.id} data={d.documents} emptyMessage="Tidak ada dokumen daftar ulang." columns={[
          { header: 'Dokumen', cell: (x) => `${x.type}${x.is_mandatory ? ' *' : ''}${x.requires_original_check ? ' (cek asli)' : ''}` },
          { header: 'Status', cell: (x) => <StatusBadge status={x.status} /> },
          { header: '', cell: (x) => (
            <div className="flex gap-1">
              {x.current_version_id && <Can permission={P.DOCUMENTS_DOWNLOAD}><Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.open(`document-versions/${x.current_version_id}/download`, { purpose: 'validasi daftar ulang' }))}>Lihat</Button></Can>}
              {canDecide && x.current_version_id && ['uploaded', 'in_review'].includes(x.status) && <Button size="sm" onClick={() => setReview(x)}>Tinjau</Button>}
            </div>
          ) },
        ]} />
      </Card>
      <FormDialog open={review !== null} title={`Tinjau ${review?.type ?? ''}`} fields={[
        { name: 'decision', label: 'Keputusan', type: 'select', required: true, options: [{ value: 'approved', label: 'Setujui' }, { value: 'revision_requested', label: 'Minta perbaikan' }, { value: 'rejected', label: 'Tolak' }] },
        { name: 'reason_code', label: 'Kode alasan', type: 'select', options: ['UNREADABLE', 'WRONG_DOCUMENT', 'INCOMPLETE_PAGES', 'EXPIRED', 'NAME_MISMATCH', 'DATA_MISMATCH', 'NOT_LEGALIZED', 'SUSPECTED_FORGERY', 'PHOTO_NOT_COMPLIANT', 'OTHER'].map((c) => ({ value: c, label: c })), visible: (v) => v.decision !== 'approved' },
        { name: 'note_for_applicant', label: 'Catatan untuk pendaftar', type: 'textarea', visible: (v) => v.decision !== 'approved' },
      ]} initial={{ decision: 'approved' }} onClose={() => setReview(null)} onSubmit={async (v) => { if (review) await pmbAdmin.post(`application-documents/${review.id}/reviews`, { ...v, version_id: review.current_version_id }); rr.reload() }} />
      <FormDialog open={typeof dialog === 'object' && dialog !== null} title="Hasil pemeriksaan" fields={[{ name: 'status', label: 'Status', type: 'select', required: true, options: [{ value: 'passed', label: 'Lolos' }, { value: 'failed', label: 'Tidak lolos' }, { value: 'not_applicable', label: 'Tidak berlaku' }] }, { name: 'note', label: 'Catatan' }]} initial={{ status: 'passed' }} onClose={() => setDialog(null)} onSubmit={async (v) => { if (typeof dialog === 'object' && dialog) await pmbAdmin.post(`re-registrations/${id}/checks/${dialog.check}`, v); rr.reload() }} />
      <FormDialog open={dialog === 'revision'} title="Minta perbaikan daftar ulang" fields={[
        { name: 'documents', label: 'Dokumen', type: 'multiselect', options: d.documents.map((x) => ({ value: x.code ?? x.id, label: x.type ?? x.id })) },
        { name: 'fields', label: 'Data', type: 'multiselect', options: ['religion', 'mother_name', 'nationality', 'address_line', 'village', 'district', 'city', 'province', 'region_code', 'residence_type', 'transportation'].map((f) => ({ value: f, label: f })) },
        { name: 'note', label: 'Catatan', type: 'textarea', required: true },
      ]} onClose={() => setDialog(null)} onSubmit={async (v) => { await pmbAdmin.post(`re-registrations/${id}/request-revision`, { targets: { documents: v.documents, fields: v.fields }, note: v.note }); rr.reload() }} />
      <ReasonDialog open={dialog === 'extend'} title="Perpanjang batas daftar ulang" extraFields={[{ name: 'until', label: 'Batas baru', type: 'datetime', required: true }]} onClose={() => setDialog(null)} onConfirm={async (v) => { await pmbAdmin.post(`re-registrations/${id}/extend`, v); rr.reload() }} />
      <ReasonDialog open={dialog === 'restore'} title="Pulihkan daftar ulang" reasonName="note" reasonLabel="Catatan" extraFields={[{ name: 'reason_code', label: 'Dasar pemulihan', type: 'select', required: true, options: [{ value: 'DECLINE_MISTAKE', label: 'Salah tolak (≤ 24 jam)' }, { value: 'LATE_CALLBACK', label: 'Pembayaran sah terlambat tercatat' }] }]} onClose={() => setDialog(null)} onConfirm={async (v) => { await pmbAdmin.post(`re-registrations/${id}/restore`, v); rr.reload() }} />
      <FormDialog open={dialog === 'cancel'} title="Batalkan penerimaan" description="Memerlukan persetujuan; kursi dilepas dan LoA dicabut setelah disetujui." fields={[{ name: 'reason', label: 'Alasan', type: 'textarea', required: true }, { name: 'evidence', label: 'Bukti (opsional)', type: 'file' }]} onClose={() => setDialog(null)} onSubmit={async (v) => {
        const form = new FormData()
        form.append('reason', String(v.reason))
        if (v.evidence instanceof File) form.append('evidence', v.evidence)
        await adminClient.post(`/pmb/admin/re-registrations/${id}/cancel`, form)
        rr.reload()
      }} />
    </AdminPage>
  )
}
