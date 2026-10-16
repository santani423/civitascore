import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Play, Plus } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { DataTable } from '@/components/ui/DataTable'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, describeError, useAction } from '@/pmb/components/useAction'
import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { KeyValue } from '@/pmb/components/KeyValue'
import { Tabs } from '@/pmb/components/Tabs'
import { dateTime, money } from '@/pmb/lib/format'
import { usePermission } from '@/hooks/usePermission'
import { keuAdmin } from '@/keu/api'
import { Can, KeuBadge, KeuList, KeuPage } from '@/keu/components'
import { P } from '@/keu/permissions'
import { opts, useKeuLookups } from '@/keu/lookups'
import { KEU, to } from '@/keu/routes'
import type { Block, Exemption, StudentRef } from '@/keu/types'

type Tab = 'blocks' | 'dispensations' | 'rules'

/** Cekal keuangan (FinanceBlockSource) — penegakan dilakukan SIAKAD di KRS & ujian. */
export function ClearancePage() {
  const [tab, setTab] = useState<Tab>('blocks')

  return (
    <KeuPage title="Cekal Keuangan" description="Aturan menentukan kapan mahasiswa dicekal karena kewajiban keuangan. Cekal tersimpan di SIAKAD dan dicek di server saat KRS dan ujian; dicabut otomatis begitu kewajiban terpenuhi." trail={[{ label: 'Cekal' }]}>
      <Tabs<Tab> active={tab} onChange={setTab} tabs={[{ key: 'blocks', label: 'Mahasiswa tercekal' }, { key: 'dispensations', label: 'Dispensasi' }, { key: 'rules', label: 'Aturan cekal' }]} />
      {tab === 'blocks' && <Blocks />}
      {tab === 'dispensations' && <Dispensations />}
      {tab === 'rules' && <Rules />}
    </KeuPage>
  )
}

function serviceLabel(services: Array<{ value: string; label: string }> | undefined, codes: string[]) {
  return codes.map((c) => services?.find((s) => s.value === c)?.label ?? c).join(', ')
}

function Blocks() {
  const lookups = useKeuLookups()
  const action = useAction()
  const [reload, setReload] = useState(0)

  return (
    <>
      <Feedback state={action} />
      <KeuList<Block> path="blocks" reloadKey={reload} rowKey={(r) => r.id} itemLabel="cekal"
        toolbar={<Can permission={P.CLR_UPDATE}><Button size="sm" variant="outline" leftIcon={<Play className="size-4" />} onClick={() => action.run(() => keuAdmin.post<{ evaluated: number }>('clearance/evaluate'), (x) => `${x.message} (${x.data.evaluated} mahasiswa)`).then((x) => x && setReload((n) => n + 1))}>Evaluasi semua</Button></Can>}
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'active', label: 'Aktif' }, { value: 'exempted', label: 'Didispensasi penuh' }, { value: 'revoked', label: 'Dicabut' }] }]}
        columns={[
          { header: 'Mahasiswa', cell: (r) => r.student ? <Link className="text-primary" to={to(KEU.admin.student, { id: r.student.id })}>{r.student.nim}<span className="block text-xs text-ink-tertiary">{r.student.name} · {r.student.study_program}</span></Link> : '—' },
          { header: 'Aturan', cell: (r) => r.rule_code },
          { header: 'Layanan dibatasi', cell: (r) => serviceLabel(lookups?.enums.services, r.services) },
          { header: 'Kekurangan', cell: (r) => money(r.shortfall) },
          { header: 'Sejak', cell: (r) => dateTime(r.effective_from) },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{(r.exemptions ?? []).some((e) => e.status === 'active') && <span className="block text-xs text-ink-tertiary">ada dispensasi aktif</span>}</span> },
          { header: '', cell: (r) => <Link className="text-sm text-primary" to={to(KEU.admin.block, { id: r.id })}>Detail</Link> },
        ]} />
    </>
  )
}

function Dispensations() {
  const lookups = useKeuLookups()
  const action = useAction()
  const [reload, setReload] = useState(0)
  const [deciding, setDeciding] = useState<{ row: Exemption; approve: boolean } | null>(null)
  const [revoking, setRevoking] = useState<Exemption | null>(null)
  const canApprove = usePermission(P.CLR_APPROVE)
  const done = (r: unknown) => r && setReload((n) => n + 1)

  return (
    <>
      <Feedback state={action} />
      <KeuList<Exemption> path="dispensations" search={false} reloadKey={reload} rowKey={(r) => r.id} itemLabel="dispensasi"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'pending', label: 'Menunggu' }, { value: 'active', label: 'Aktif' }, { value: 'rejected', label: 'Ditolak' }, { value: 'expired', label: 'Kedaluwarsa' }, { value: 'revoked', label: 'Dicabut' }] }]}
        columns={[
          { header: 'Mahasiswa', cell: (r) => <span>{r.student?.nim}<span className="block text-xs text-ink-tertiary">{r.student?.name}</span></span> },
          { header: 'Layanan', cell: (r) => serviceLabel(lookups?.enums.services, [r.service]) },
          { header: 'Berlaku', cell: (r) => <span className="text-xs">{dateTime(r.valid_from)}<br />s.d. {dateTime(r.valid_until)}</span> },
          { header: 'Alasan', cell: (r) => <span className="whitespace-normal text-xs">{r.reason}{r.promise_amount && <span className="block">Janji bayar {money(r.promise_amount)} ({r.promise_date})</span>}</span> },
          { header: 'Diajukan/diberi', cell: (r) => <span className="text-xs">{r.requested_by_name ?? '—'}{r.granted_by_name ? ` → ${r.granted_by_name}` : ''}</span> },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{r.decision_note && <span className="block text-xs text-ink-tertiary">{r.decision_note}</span>}</span> },
          { header: 'Aksi', cell: (r) => canApprove ? <div className="flex gap-1">
            {r.status === 'pending' && <><Button size="sm" onClick={() => setDeciding({ row: r, approve: true })}>Setujui</Button><Button size="sm" variant="outline" onClick={() => setDeciding({ row: r, approve: false })}>Tolak</Button></>}
            {r.status === 'active' && <Button size="sm" variant="ghost" onClick={() => setRevoking(r)}>Cabut</Button>}
          </div> : null },
        ]} />
      <FormDialog open={deciding !== null} title={deciding?.approve ? 'Setujui dispensasi' : 'Tolak dispensasi'} onClose={() => setDeciding(null)}
        fields={[{ name: 'note', label: deciding?.approve ? 'Catatan (opsional)' : 'Alasan penolakan', type: 'textarea', required: !deciding?.approve }]}
        onSubmit={(v) => action.run(() => keuAdmin.post(`dispensations/${deciding?.row.id}/decide`, { ...v, approve: deciding?.approve }), (x) => x.message).then(done)} />
      <ReasonDialog open={revoking !== null} title="Cabut dispensasi" onClose={() => setRevoking(null)}
        onConfirm={(v) => action.run(() => keuAdmin.post(`dispensations/${revoking?.id}/revoke`, v), (x) => x.message).then(done)} />
    </>
  )
}

interface Rule { id: string; code: string; name: string; services: string[]; requirement: string; requirement_label: string; bill_groups: string[] | null; terms: string; grace_days: number; min_outstanding_amount: string; include_penalties: boolean; study_program_id: string | null; study_program: string | null; active_from: string | null; active_until: string | null; is_active: boolean }

const TERMS = [{ value: 'current', label: 'Semester berjalan' }, { value: 'previous', label: 'Semester sebelumnya' }, { value: 'current_and_previous', label: 'Semester berjalan & sebelumnya' }, { value: 'all', label: 'Semua tagihan' }]

function Rules() {
  const lookups = useKeuLookups()
  const { data, error, reload } = useLoad(() => keuAdmin.get<Rule[]>('clearance-rules'))
  const [editing, setEditing] = useState<Partial<Rule> | null>(null)
  const [simulation, setSimulation] = useState<{ rule: string; students_evaluated: number; would_block: number; students: Array<{ student: StudentRef; shortfall: string }> } | null>(null)
  const action = useAction()
  const fields: FieldDef[] = [
    { name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true },
    { name: 'services', label: 'Layanan yang dibatasi', type: 'multiselect', required: true, options: lookups?.enums.services ?? [] },
    { name: 'requirement', label: 'Syarat', type: 'select', required: true, options: lookups?.enums.clearance_requirement ?? [] },
    { name: 'terms', label: 'Tagihan yang dihitung', type: 'select', required: true, options: TERMS },
    { name: 'bill_groups', label: 'Kelompok tagihan (kosong = semua)', type: 'multiselect', options: lookups?.enums.bill_group ?? [] },
    { name: 'grace_days', label: 'Tenggang setelah jatuh tempo (hari)', type: 'number', required: true },
    { name: 'min_outstanding_amount', label: 'Kekurangan minimal agar dicekal (Rp)', type: 'number', required: true },
    { name: 'study_program_id', label: 'Khusus program studi', type: 'select', options: opts(lookups?.study_programs, (p) => p.name) },
    { name: 'active_from', label: 'Aktif mulai', type: 'date' }, { name: 'active_until', label: 'Aktif sampai', type: 'date' },
    { name: 'include_penalties', label: 'Termasuk tagihan denda', type: 'checkbox' },
    { name: 'is_active', label: 'Aktif', type: 'checkbox' },
  ]

  return (
    <Card noPadding title="Aturan cekal keuangan" actions={<Can permission={P.CLR_UPDATE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing({ is_active: false, grace_days: 0, min_outstanding_amount: '0', terms: 'current', requirement: 'fully_paid', services: ['krs'] })}>Aturan</Button></Can>}>
      <div className="p-3"><Feedback state={action} />{error && <Alert variant="danger">{describeError(error)}</Alert>}</div>
      <DataTable rowKey={(r) => r.id} data={data ?? []} emptyMessage="Belum ada aturan cekal." columns={[
        { header: 'Aturan', cell: (r) => <span>{r.name}<span className="block text-xs text-ink-tertiary">{r.code}{r.study_program ? ` · ${r.study_program}` : ''}</span></span> },
        { header: 'Layanan', cell: (r) => serviceLabel(lookups?.enums.services, r.services) },
        { header: 'Syarat', cell: (r) => <span className="text-xs">{r.requirement_label} · {TERMS.find((t) => t.value === r.terms)?.label} · tenggang {r.grace_days} hari · min. {money(r.min_outstanding_amount)}</span> },
        { header: 'Status', cell: (r) => <KeuBadge status={r.is_active ? 'active' : 'inactive'} label={r.is_active ? 'Aktif' : 'Nonaktif'} /> },
        { header: 'Aksi', cell: (r) => <div className="flex gap-1">
          <Button size="sm" variant="ghost" onClick={() => action.run(() => keuAdmin.get<NonNullable<typeof simulation>>(`clearance-rules/${r.id}/simulate`)).then((x) => x && setSimulation(x))}>Simulasi</Button>
          <Can permission={P.CLR_UPDATE}><Button size="sm" variant="outline" onClick={() => setEditing(r)}>Ubah</Button></Can>
        </div> },
      ]} />
      {simulation && (
        <div className="border-t border-border p-4">
          <Alert variant="info" onDismiss={() => setSimulation(null)}>Simulasi {simulation.rule}: {simulation.would_block} dari {simulation.students_evaluated} mahasiswa aktif akan tercekal bila aturan ini aktif. Tidak ada cekal yang diterbitkan.</Alert>
          <DataTable rowKey={(r) => r.student.id} data={simulation.students} emptyMessage="Tidak ada mahasiswa terdampak." columns={[
            { header: 'NIM', cell: (r) => r.student.nim }, { header: 'Nama', cell: (r) => r.student.name }, { header: 'Prodi', cell: (r) => r.student.study_program }, { header: 'Kekurangan', cell: (r) => money(r.shortfall) },
          ]} />
        </div>
      )}
      <FormDialog open={editing !== null} title={editing?.id ? 'Ubah aturan cekal' : 'Aturan cekal baru'} initial={editing ?? {}} fields={fields} wide onClose={() => setEditing(null)}
        description="Jalankan simulasi sebelum mengaktifkan. Perubahan diterapkan saat evaluasi berikutnya (otomatis harian, setiap pembayaran, atau tombol Evaluasi semua)."
        onSubmit={async (v) => {
          await (editing?.id ? keuAdmin.put(`clearance-rules/${editing.id}`, v) : keuAdmin.post('clearance-rules', v))
          reload()
        }} />
    </Card>
  )
}

interface BlockDetail extends Block { details: Record<string, unknown> | null; histories: Array<{ action: string; from: string | null; to: string | null; actor: string; reason: string | null; payload: Record<string, unknown> | null; created_at: string | null }> }

const HISTORY_LABELS: Record<string, string> = {
  imposed: 'Cekal diterbitkan', revoked: 'Cekal dicabut', exempted: 'Dispensasi diberikan', exemption_requested: 'Dispensasi diajukan', exemption_rejected: 'Dispensasi ditolak',
  exemption_revoked: 'Dispensasi dicabut', exemption_expired: 'Dispensasi kedaluwarsa', fully_exempted: 'Seluruh layanan didispensasi', reactivated: 'Cekal aktif kembali',
}

export function BlockDetailPage() {
  const { id = '' } = useParams()
  const lookups = useKeuLookups()
  const { data, error, reload } = useLoad(() => keuAdmin.get<BlockDetail>(`blocks/${id}`), [id])
  const action = useAction()
  const [dialog, setDialog] = useState<'revoke' | 'grant' | null>(null)
  const run = (fn: () => Promise<{ message: string }>) => action.run(fn, (r) => r.message).then((r) => { if (r) reload() })

  if (error) return <KeuPage title="Cekal" trail={[{ label: 'Cekal', path: KEU.admin.clearance }]}><Alert variant="danger">{describeError(error)}</Alert></KeuPage>
  if (!data) return <p className="text-sm text-ink-secondary">Memuat...</p>
  const active = data.status !== 'revoked'

  return (
    <KeuPage title={`Cekal ${data.rule_code ?? ''} — ${data.student?.nim ?? ''}`} trail={[{ label: 'Cekal', path: KEU.admin.clearance }, { label: data.student?.nim ?? data.id }]}
      actions={active ? <div className="flex gap-2">
        <Can permission={P.CLR_CREATE}><Button variant="outline" onClick={() => setDialog('grant')}>Beri dispensasi</Button></Can>
        <Can permission={P.CLR_APPROVE}><Button variant="danger" onClick={() => setDialog('revoke')}>Buka cekal manual</Button></Can>
      </div> : undefined}>
      <Feedback state={action} />
      <Card>
        <KeyValue columns={3} items={[
          ['Mahasiswa', data.student ? <Link key="mahasiswa" className="text-primary" to={to(KEU.admin.student, { id: data.student.id })}>{data.student.nim} — {data.student.name}</Link> : '—'],
          ['Status', <KeuBadge key="status" status={data.status} label={data.status_label} />],
          ['Layanan dibatasi', serviceLabel(lookups?.enums.services, data.services)],
          ['Kekurangan saat dievaluasi', money(data.shortfall)],
          ['Sejak', dateTime(data.effective_from)],
          ['Dicabut', data.revoked_at ? `${dateTime(data.revoked_at)} — ${data.revoke_reason}` : '—'],
        ]} />
        <p className="mt-3 text-sm text-ink-secondary">{data.reason}</p>
      </Card>
      <Card noPadding title="Dispensasi">
        <DataTable rowKey={(r) => r.id} data={data.exemptions ?? []} emptyMessage="Belum ada dispensasi." columns={[
          { header: 'Layanan', cell: (r) => serviceLabel(lookups?.enums.services, [r.service]) },
          { header: 'Berlaku', cell: (r) => `${dateTime(r.valid_from)} – ${dateTime(r.valid_until)}` },
          { header: 'Alasan', cell: (r) => <span className="whitespace-normal text-xs">{r.reason}</span> },
          { header: 'Status', cell: (r) => <KeuBadge status={r.status} label={r.status_label} /> },
        ]} />
      </Card>
      <Card noPadding title="Riwayat">
        <DataTable rowKey={(r) => `${r.created_at}-${r.action}`} data={data.histories} columns={[
          { header: 'Waktu', cell: (r) => dateTime(r.created_at) },
          { header: 'Kejadian', cell: (r) => HISTORY_LABELS[r.action] ?? r.action },
          { header: 'Oleh', cell: (r) => r.actor },
          { header: 'Keterangan', cell: (r) => <span className="whitespace-normal text-xs">{r.reason ?? '—'}</span> },
        ]} />
      </Card>
      <ReasonDialog open={dialog === 'revoke'} title="Buka cekal manual" confirmLabel="Buka cekal" onClose={() => setDialog(null)}
        description="Pembukaan manual tercatat di audit. Bila kewajiban belum dipenuhi, evaluasi otomatis berikutnya akan menerbitkan cekal lagi — untuk pengecualian sementara gunakan dispensasi."
        onConfirm={(v) => run(() => keuAdmin.post(`blocks/${id}/revoke`, v))} />
      <FormDialog open={dialog === 'grant'} title="Beri dispensasi" onClose={() => setDialog(null)}
        description="Dispensasi melebihi batas hari petugas akan menunggu persetujuan pejabat (pemberi ≠ penyetuju)."
        fields={[
          { name: 'service', label: 'Layanan', type: 'select', required: true, options: (lookups?.enums.services ?? []).filter((s) => data.services.includes(s.value)) },
          { name: 'valid_until', label: 'Berlaku sampai', type: 'datetime', required: true },
          { name: 'reason', label: 'Alasan', type: 'textarea', required: true },
          { name: 'promise_amount', label: 'Janji bayar (Rp)', type: 'number' },
          { name: 'promise_date', label: 'Tanggal janji bayar', type: 'date' },
        ]}
        onSubmit={(v) => run(() => keuAdmin.post(`blocks/${id}/dispensations`, v))} />
    </KeuPage>
  )
}
