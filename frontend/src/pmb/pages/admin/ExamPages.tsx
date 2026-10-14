import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Plus, Printer, QrCode, Trash2 } from 'lucide-react'
import { pmbAdmin } from '@/pmb/api/admin'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminList } from '@/pmb/components/AdminList'
import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { Tabs } from '@/pmb/components/Tabs'
import { AdminPage, Can, P, WavePathPicker } from '@/pmb/components/admin'
import { usePermission } from '@/hooks/usePermission'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { DataTable } from '@/components/ui/DataTable'
import { date, dateTime } from '@/pmb/lib/format'
import { statusLabel } from '@/pmb/lib/labels'
import { PMB, to } from '@/pmb/routes'

interface Venue { id: string; name: string; address: string | null; map_url: string | null; is_active: boolean; rooms: Array<{ id: string; code: string; name: string; capacity: number; is_accessible: boolean; facilities: string[] | null; is_active: boolean }> }
interface Schedule { id: string; title: string; mode: string; exam_date: string; status: string; wave_path_ids: string[]; selection_component_id: string; component?: { code: string; name: string; source: string } | null; sessions_count?: number; participants_count?: number; instructions?: string | null; cbt_duration_minutes?: number | null; cbt_question_count?: number | null; cbt_randomize?: boolean; external_reference?: string | null }

type ExamTab = 'schedules' | 'venues' | 'reschedules'

const MODES = [['onsite', 'Luring'], ['online_proctored', 'Daring terawasi'], ['cbt', 'CBT'], ['interview', 'Wawancara'], ['portfolio_review', 'Penilaian portofolio']].map(([value, label]) => ({ value, label }))

/** ADM-50/51 Ujian: jadwal per gelombang, lokasi & ruang, permintaan jadwal ulang. */
export function ExamsPage() {
  const [tab, setTab] = useState<ExamTab>('schedules')

  return (
    <AdminPage title="Ujian Seleksi" trail={[{ label: 'Ujian' }]}>
      <Tabs<ExamTab> active={tab} onChange={setTab} tabs={[{ key: 'schedules', label: 'Jadwal' }, { key: 'venues', label: 'Lokasi & ruang' }, { key: 'reschedules', label: 'Permintaan jadwal ulang' }]} />
      {tab === 'schedules' && <SchedulesTab />}
      {tab === 'venues' && <VenuesTab />}
      {tab === 'reschedules' && <RescheduleTab />}
    </AdminPage>
  )
}

function SchedulesTab() {
  const [target, setTarget] = useState<{ period?: string; wave?: string }>({})
  const schedules = useLoad(() => (target.wave ? pmbAdmin.get<Schedule[]>(`waves/${target.wave}/exam-schedules`) : Promise.resolve([])), [target.wave])
  const wavePaths = useLoad(() => (target.wave ? pmbAdmin.get<Array<{ id: string; admission_path: { name: string } | null; selection_scheme_id: string | null }>>(`waves/${target.wave}/paths`) : Promise.resolve([])), [target.wave])
  const [components, setComponents] = useState<Array<{ value: string; label: string }>>([])
  const [creating, setCreating] = useState(false)

  useEffect(() => {
    const schemeIds = [...new Set((wavePaths.data ?? []).map((wp) => wp.selection_scheme_id).filter(Boolean))] as string[]
    Promise.all(schemeIds.map((id) => pmbAdmin.get<{ code: string; components: Array<{ id: string; code: string; name: string; source: string }> }>(`selection-schemes/${id}`)))
      .then((schemes) => setComponents(schemes.flatMap((s) => s.components.filter((c) => !['administrative', 'report_card', 'achievement'].includes(c.source)).map((c) => ({ value: c.id, label: `${s.code} · ${c.name} (${c.source})` })))))
      .catch(() => setComponents([]))
  }, [wavePaths.data])

  return (
    <div className="flex flex-col gap-4">
      <div className="flex flex-wrap items-end justify-between gap-3">
        <WavePathPicker level="wave" value={target} onChange={setTarget} />
        <Can permission={P.EXAMS_MANAGE}><Button disabled={!target.wave} leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Jadwal ujian</Button></Can>
      </div>
      <Card noPadding>
        <DataTable rowKey={(s) => s.id} data={schedules.data ?? []} emptyMessage={target.wave ? 'Belum ada jadwal ujian.' : 'Pilih periode dan gelombang.'} columns={[
          { header: 'Jadwal', cell: (s) => <Link className="font-medium text-primary" to={to(PMB.admin.examSchedule, { id: s.id })}>{s.title}<span className="block text-xs text-ink-tertiary">{s.component?.name}</span></Link> },
          { header: 'Tanggal', cell: (s) => date(s.exam_date) }, { header: 'Mode', cell: (s) => MODES.find((m) => m.value === s.mode)?.label },
          { header: 'Sesi', cell: (s) => s.sessions_count ?? 0 }, { header: 'Peserta', cell: (s) => s.participants_count ?? 0 },
          { header: 'Status', cell: (s) => <StatusBadge status={s.status} /> },
        ]} />
      </Card>
      <FormDialog open={creating} title="Jadwal ujian" fields={scheduleFields(components, (wavePaths.data ?? []).map((wp) => ({ value: wp.id, label: wp.admission_path?.name ?? wp.id })))} initial={{ mode: 'onsite', wave_path_ids: (wavePaths.data ?? []).map((wp) => wp.id) }} onClose={() => setCreating(false)} onSubmit={async (v) => { await pmbAdmin.post(`waves/${target.wave}/exam-schedules`, v); schedules.reload() }} />
    </div>
  )
}

const scheduleFields = (components: Array<{ value: string; label: string }>, wavePaths: Array<{ value: string; label: string }>): FieldDef[] => [
  { name: 'title', label: 'Judul', required: true },
  { name: 'selection_component_id', label: 'Komponen seleksi', type: 'select', required: true, options: components },
  { name: 'wave_path_ids', label: 'Jalur peserta', type: 'multiselect', required: true, options: wavePaths },
  { name: 'mode', label: 'Mode', type: 'select', required: true, options: MODES },
  { name: 'exam_date', label: 'Tanggal ujian', type: 'date', required: true },
  { name: 'instructions', label: 'Instruksi untuk peserta', type: 'textarea' },
  { name: 'cbt_duration_minutes', label: 'Durasi CBT (menit)', type: 'number', visible: (v) => v.mode === 'cbt' },
  { name: 'cbt_question_count', label: 'Jumlah soal ditampilkan (kosong = semua)', type: 'number', visible: (v) => v.mode === 'cbt' },
  { name: 'cbt_randomize', label: 'Acak urutan soal', type: 'checkbox', visible: (v) => v.mode === 'cbt' },
  { name: 'external_reference', label: 'Referensi sistem CBT eksternal', visible: (v) => v.mode === 'cbt' },
]

function VenuesTab() {
  const venues = useLoad(() => pmbAdmin.get<Venue[]>('exam-venues'), [])
  const action = useAction()
  const [venueForm, setVenueForm] = useState<Venue | 'new' | null>(null)
  const [roomForm, setRoomForm] = useState<{ venue: Venue; room: Venue['rooms'][number] | null } | null>(null)

  return (
    <div className="flex flex-col gap-4">
      <Feedback state={action} />
      <Can permission={P.EXAMS_MANAGE}><div><Button leftIcon={<Plus className="size-4" />} onClick={() => setVenueForm('new')}>Lokasi</Button></div></Can>
      {(venues.data ?? []).map((v) => (
        <Card key={v.id} title={v.name} description={v.address ?? undefined} actions={<Can permission={P.EXAMS_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => setVenueForm(v)}>Ubah</Button><Button size="sm" variant="ghost" onClick={() => setRoomForm({ venue: v, room: null })}>Tambah ruang</Button><Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus lokasi?') && action.run(() => pmbAdmin.del(`exam-venues/${v.id}`), 'Dihapus.').then(venues.reload)}><Trash2 className="size-4" /></Button></div></Can>} noPadding>
          <DataTable rowKey={(r) => r.id} data={v.rooms} emptyMessage="Belum ada ruang." columns={[
            { header: 'Ruang', cell: (r) => `${r.code} · ${r.name}` }, { header: 'Kapasitas', cell: (r) => r.capacity },
            { header: 'Aksesibel', cell: (r) => (r.is_accessible ? 'Ya' : 'Tidak') }, { header: 'Fasilitas', cell: (r) => (r.facilities ?? []).join(', ') || '—' },
            { header: 'Status', cell: (r) => <Badge variant={r.is_active ? 'success' : 'neutral'}>{r.is_active ? 'Aktif' : 'Nonaktif'}</Badge> },
            { header: '', cell: (r) => <Can permission={P.EXAMS_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => setRoomForm({ venue: v, room: r })}>Ubah</Button><Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => action.run(() => pmbAdmin.del(`exam-rooms/${r.id}`), 'Dihapus.').then(venues.reload)}><Trash2 className="size-4" /></Button></div></Can> },
          ]} />
        </Card>
      ))}
      <FormDialog open={venueForm !== null} title="Lokasi ujian" fields={[{ name: 'name', label: 'Nama', required: true }, { name: 'address', label: 'Alamat', type: 'textarea' }, { name: 'map_url', label: 'URL peta' }, { name: 'is_active', label: 'Aktif', type: 'checkbox' }]} initial={venueForm && venueForm !== 'new' ? (venueForm as unknown as Record<string, unknown>) : { is_active: true }} onClose={() => setVenueForm(null)} onSubmit={async (v) => {
        if (venueForm === 'new') await pmbAdmin.post('exam-venues', v)
        else if (venueForm) await pmbAdmin.patch(`exam-venues/${venueForm.id}`, v)
        venues.reload()
      }} />
      <FormDialog open={roomForm !== null} title="Ruang ujian" fields={[{ name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true }, { name: 'capacity', label: 'Kapasitas', type: 'number', required: true }, { name: 'is_accessible', label: 'Ramah disabilitas', type: 'checkbox' }, { name: 'facilities', label: 'Fasilitas (JSON array, mis. ["komputer","AC"])', type: 'json' }, { name: 'is_active', label: 'Aktif', type: 'checkbox' }]} initial={roomForm?.room ? (roomForm.room as unknown as Record<string, unknown>) : { is_active: true }} onClose={() => setRoomForm(null)} onSubmit={async (v) => {
        if (!roomForm) return
        if (roomForm.room) await pmbAdmin.patch(`exam-rooms/${roomForm.room.id}`, v)
        else await pmbAdmin.post(`exam-venues/${roomForm.venue.id}/rooms`, v)
        venues.reload()
      }} />
    </div>
  )
}

function RescheduleTab() {
  const [reload, setReload] = useState(0)
  const [deciding, setDeciding] = useState<{ id: string; schedule_id: string | null } | null>(null)
  const sessions = useLoad(() => (deciding?.schedule_id ? pmbAdmin.get<Schedule & { sessions: Array<{ id: string; code: string; starts_at: string; capacity: number; participants_count: number }> }>(`exam-schedules/${deciding.schedule_id}`) : Promise.resolve(null)), [deciding?.schedule_id])

  return (
    <>
      <AdminList<{ id: string; status: string; reason: string; participant_number: string | null; name: string | null; schedule: string | null; schedule_id: string | null; current_session: string | null; current_starts_at: string | null; attachment_url: string | null; created_at: string | null }>
        path="reschedule-requests" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="permintaan"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'pending', label: 'Menunggu' }, { value: 'approved', label: 'Disetujui' }, { value: 'rejected', label: 'Ditolak' }] }]}
        columns={[
          { header: 'Peserta', cell: (r) => <span>{r.name}<span className="block text-xs text-ink-tertiary">{r.participant_number}</span></span> },
          { header: 'Jadwal', cell: (r) => <span>{r.schedule}<span className="block text-xs text-ink-tertiary">{r.current_session} · {dateTime(r.current_starts_at)}</span></span> },
          { header: 'Alasan', cell: (r) => <span className="whitespace-normal">{r.reason}{r.attachment_url && <a className="ml-2 text-primary" href={r.attachment_url} target="_blank" rel="noopener">bukti</a>}</span> },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
          { header: '', cell: (r) => r.status === 'pending' && <Button size="sm" variant="ghost" onClick={() => setDeciding(r)}>Putuskan</Button> },
        ]} />
      <FormDialog open={deciding !== null} title="Putuskan jadwal ulang" fields={[
        { name: 'approve', label: 'Keputusan', type: 'select', required: true, options: [{ value: '1', label: 'Setujui & pindahkan' }, { value: '0', label: 'Tolak' }] },
        { name: 'target_session_id', label: 'Sesi tujuan', type: 'select', options: (sessions.data?.sessions ?? []).map((s) => ({ value: s.id, label: `${s.code} · ${dateTime(s.starts_at)} (${s.participants_count}/${s.capacity})` })), visible: (v) => v.approve === '1' },
        { name: 'note', label: 'Catatan', type: 'textarea' },
      ]} onClose={() => setDeciding(null)} onSubmit={async (v) => { if (deciding) await pmbAdmin.post(`reschedule-requests/${deciding.id}/decision`, { ...v, approve: v.approve === '1' }); setReload(reload + 1) }} />
    </>
  )
}

interface ScheduleDetail extends Schedule { wave: { id: string; name: string; is_ods: boolean } | null; sessions: Array<{ id: string; code: string; starts_at: string; ends_at: string; checkin_opens_at: string | null; capacity: number; participants_count: number; online_url: string | null; scores_locked_at: string | null; room_id: string | null; room: { name: string; venue: { name: string } | null } | null; examiners: Array<{ user_id: string; role: string; user: { name: string } | null }> }>; unassigned_count: number; cbt_question_total: number | null }

/** ADM-51 Detail jadwal: sesi, penempatan otomatis, petugas, bank soal CBT, publikasi. */
export function ExamScheduleDetailPage() {
  const { id = '' } = useParams()
  const s = useLoad(() => pmbAdmin.get<ScheduleDetail>(`exam-schedules/${id}`), [id])
  const staff = useLoad(() => pmbAdmin.get<Array<{ id: string; name: string }>>('staff', { permission: P.SCORES_INPUT }).catch(() => []), [])
  const proctors = useLoad(() => pmbAdmin.get<Array<{ id: string; name: string }>>('staff', { permission: P.EXAMS_ATTENDANCE }).catch(() => []), [])
  const venues = useLoad(() => pmbAdmin.get<Venue[]>('exam-venues'), [])
  const action = useAction()
  const [sessionForm, setSessionForm] = useState<ScheduleDetail['sessions'][number] | 'new' | null>(null)
  const [examinerFor, setExaminerFor] = useState<ScheduleDetail['sessions'][number] | null>(null)
  const [dialog, setDialog] = useState<null | 'edit' | 'cancel' | 'autoExaminers' | 'question'>(null)
  const questions = useLoad(() => (s.data?.mode === 'cbt' ? pmbAdmin.get<Array<{ id: string; question: string; options: Array<{ key: string; text: string }>; correct_option: string; points: string; sort_order: number }>>(`exam-schedules/${id}/cbt-questions`) : Promise.resolve([])), [id, s.data?.mode])
  const [editingQuestion, setEditingQuestion] = useState<{ id: string } | null>(null)

  if (!s.data) return <p className="text-sm text-ink-secondary">{s.error?.message ?? 'Memuat...'}</p>

  const d = s.data
  const rooms = (venues.data ?? []).flatMap((v) => v.rooms.filter((r) => r.is_active).map((r) => ({ value: r.id, label: `${v.name} · ${r.name} (${r.capacity})` })))
  const users = [...(staff.data ?? []), ...(proctors.data ?? [])].filter((u, i, all) => all.findIndex((x) => x.id === u.id) === i)

  return (
    <AdminPage title={d.title} description={`${d.wave?.name ?? ''} · ${date(d.exam_date)} · ${MODES.find((m) => m.value === d.mode)?.label}`} trail={[{ label: 'Ujian', path: PMB.admin.exams }, { label: d.title }]}
      actions={
        <Can permission={P.EXAMS_MANAGE}>
          <div className="flex flex-wrap gap-2">
            <StatusBadge status={d.status} />
            {d.status !== 'completed' && <Button size="sm" variant="outline" onClick={() => setDialog('edit')}>Ubah</Button>}
            <Button size="sm" variant="outline" disabled={d.unassigned_count === 0} onClick={() => action.run(() => pmbAdmin.post<{ assigned: number; unassigned: number }>(`exam-schedules/${id}/auto-assign`, { strategy: 'registration_number' }), (r) => `${r.assigned} peserta ditempatkan${r.unassigned ? `, ${r.unassigned} belum mendapat kursi` : ''}.`).then(s.reload)}>Tempatkan otomatis ({d.unassigned_count})</Button>
            <Button size="sm" variant="outline" onClick={() => setDialog('autoExaminers')}>Bagi petugas</Button>
            {d.status === 'draft' && <Button size="sm" onClick={() => window.confirm('Publikasikan jadwal? Kartu peserta diterbitkan dan peserta diberi notifikasi.') && action.run(() => pmbAdmin.post(`exam-schedules/${id}/publish`), 'Jadwal dipublikasikan.').then(s.reload)}>Publikasikan</Button>}
            {['draft', 'published'].includes(d.status) && <Button size="sm" variant="ghost" onClick={() => setDialog('cancel')}>Batalkan</Button>}
          </div>
        </Can>
      }>
      <Feedback state={action} />
      <Card title="Sesi" noPadding actions={<Can permission={P.EXAMS_MANAGE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setSessionForm('new')}>Sesi</Button></Can>}>
        <DataTable rowKey={(x) => x.id} data={d.sessions} emptyMessage="Belum ada sesi." columns={[
          { header: 'Sesi', cell: (x) => <Link className="font-medium text-primary" to={to(PMB.admin.examSession, { id: x.id })}>{x.code}</Link> },
          { header: 'Waktu', cell: (x) => `${dateTime(x.starts_at)} – ${dateTime(x.ends_at).split(' ').pop()}` },
          { header: 'Ruang', cell: (x) => (x.room ? `${x.room.venue?.name} · ${x.room.name}` : x.online_url ? 'Daring' : '—') },
          { header: 'Terisi', cell: (x) => `${x.participants_count}/${x.capacity}` },
          { header: 'Petugas', cell: (x) => x.examiners.map((e) => `${e.user?.name} (${e.role})`).join(', ') || '—' },
          { header: 'Nilai', cell: (x) => (x.scores_locked_at ? <Badge variant="success">Terkunci</Badge> : '—') },
          { header: '', cell: (x) => <Can permission={P.EXAMS_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => setSessionForm(x)}>Ubah</Button><Button size="sm" variant="ghost" onClick={() => setExaminerFor(x)}>Petugas</Button><Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => action.run(() => pmbAdmin.del(`exam-sessions/${x.id}`), 'Sesi dihapus.').then(s.reload)}><Trash2 className="size-4" /></Button></div></Can> },
        ]} />
      </Card>
      {d.mode === 'cbt' && (
        <Card title={`Bank soal CBT (${questions.data?.length ?? 0})`} noPadding actions={<Can permission={P.EXAMS_MANAGE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => { setEditingQuestion(null); setDialog('question') }}>Soal</Button></Can>}>
          <DataTable rowKey={(q) => q.id} data={questions.data ?? []} emptyMessage="Belum ada soal." columns={[
            { header: '#', cell: (q) => q.sort_order + 1 }, { header: 'Soal', cell: (q) => <span className="line-clamp-2 whitespace-normal">{q.question}</span> },
            { header: 'Kunci', cell: (q) => q.correct_option }, { header: 'Poin', cell: (q) => q.points },
            { header: '', cell: (q) => <Can permission={P.EXAMS_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => { setEditingQuestion(q); setDialog('question') }}>Ubah</Button><Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => action.run(() => pmbAdmin.del(`cbt-questions/${q.id}`), 'Soal dihapus.').then(questions.reload)}><Trash2 className="size-4" /></Button></div></Can> },
          ]} />
        </Card>
      )}
      <FormDialog open={sessionForm !== null} title="Sesi ujian" fields={[
        { name: 'code', label: 'Kode sesi', required: true }, { name: 'room_id', label: 'Ruang (kosong untuk daring)', type: 'select', options: rooms },
        { name: 'starts_at', label: 'Mulai', type: 'datetime', required: true }, { name: 'ends_at', label: 'Selesai', type: 'datetime', required: true },
        { name: 'checkin_opens_at', label: 'Check-in dibuka', type: 'datetime' }, { name: 'capacity', label: 'Kapasitas', type: 'number', required: true }, { name: 'online_url', label: 'URL ujian daring' },
        ...(sessionForm !== 'new' && d.status === 'published' ? [{ name: 'reason', label: 'Alasan perubahan (peserta diberi tahu)', type: 'textarea', required: true } as FieldDef] : []),
      ]} initial={sessionForm && sessionForm !== 'new' ? (sessionForm as unknown as Record<string, unknown>) : { code: `S${d.sessions.length + 1}` }} onClose={() => setSessionForm(null)} onSubmit={async (v) => {
        if (sessionForm === 'new') await pmbAdmin.post(`exam-schedules/${id}/sessions`, v)
        else if (sessionForm) await pmbAdmin.patch(`exam-sessions/${sessionForm.id}`, v)
        s.reload()
      }} />
      <FormDialog open={examinerFor !== null} title={`Petugas sesi ${examinerFor?.code ?? ''}`} fields={[
        { name: 'examiners_examiner', label: 'Penguji', type: 'multiselect', options: (staff.data ?? []).map((u) => ({ value: u.id, label: u.name })) },
        { name: 'examiners_interviewer', label: 'Pewawancara', type: 'multiselect', options: (staff.data ?? []).map((u) => ({ value: u.id, label: u.name })) },
        { name: 'examiners_proctor', label: 'Pengawas', type: 'multiselect', options: users.map((u) => ({ value: u.id, label: u.name })) },
      ]} initial={{ examiners_examiner: examinerFor?.examiners.filter((e) => e.role === 'examiner').map((e) => e.user_id), examiners_interviewer: examinerFor?.examiners.filter((e) => e.role === 'interviewer').map((e) => e.user_id), examiners_proctor: examinerFor?.examiners.filter((e) => e.role === 'proctor').map((e) => e.user_id) }} onClose={() => setExaminerFor(null)} onSubmit={async (v) => {
        if (!examinerFor) return
        const examiners = (['examiner', 'interviewer', 'proctor'] as const).flatMap((role) => ((v[`examiners_${role}`] as string[]) ?? []).map((user_id) => ({ user_id, role })))
        await pmbAdmin.put(`exam-sessions/${examinerFor.id}/examiners`, { examiners })
        s.reload()
      }} />
      <FormDialog open={dialog === 'autoExaminers'} title="Bagi petugas ke semua sesi" fields={[
        { name: 'role', label: 'Peran', type: 'select', required: true, options: [{ value: 'proctor', label: 'Pengawas' }, { value: 'examiner', label: 'Penguji' }, { value: 'interviewer', label: 'Pewawancara' }] },
        { name: 'user_ids', label: 'Petugas', type: 'multiselect', required: true, options: users.map((u) => ({ value: u.id, label: u.name })) },
        { name: 'per_session', label: 'Jumlah per sesi', type: 'number' },
      ]} initial={{ role: 'proctor', per_session: 1 }} onClose={() => setDialog(null)} onSubmit={async (v) => { await pmbAdmin.post(`exam-schedules/${id}/auto-examiners`, v); s.reload() }} />
      <FormDialog open={dialog === 'edit'} title="Ubah jadwal" fields={scheduleFields([{ value: d.selection_component_id, label: d.component?.name ?? 'Komponen' }], d.wave_path_ids.map((x) => ({ value: x, label: x })))} initial={d as unknown as Record<string, unknown>} onClose={() => setDialog(null)} onSubmit={async (v) => { await pmbAdmin.patch(`exam-schedules/${id}`, v); s.reload() }} />
      <ReasonDialog open={dialog === 'cancel'} title="Batalkan jadwal ujian" description="Peserta diberi notifikasi pembatalan." onClose={() => setDialog(null)} onConfirm={async (v) => { await pmbAdmin.post(`exam-schedules/${id}/cancel`, v); s.reload() }} />
      <FormDialog open={dialog === 'question'} wide title="Soal CBT" fields={[
        { name: 'question', label: 'Pertanyaan', type: 'textarea', required: true },
        { name: 'options', label: 'Opsi (JSON: [{"key":"A","text":"..."},{"key":"B","text":"..."}])', type: 'json', required: true },
        { name: 'correct_option', label: 'Kunci jawaban (mis. A)', required: true }, { name: 'points', label: 'Poin', type: 'number' }, { name: 'sort_order', label: 'Urutan', type: 'number' },
      ]} initial={editingQuestion ? (editingQuestion as unknown as Record<string, unknown>) : { options: [{ key: 'A', text: '' }, { key: 'B', text: '' }, { key: 'C', text: '' }, { key: 'D', text: '' }], points: 1, sort_order: questions.data?.length ?? 0 }} onClose={() => setDialog(null)} onSubmit={async (v) => {
        if (editingQuestion) await pmbAdmin.patch(`cbt-questions/${editingQuestion.id}`, v)
        else await pmbAdmin.post(`exam-schedules/${id}/cbt-questions`, v)
        questions.reload()
      }} />
    </AdminPage>
  )
}

interface SessionDetail { id: string; code: string; starts_at: string; ends_at: string; capacity: number; scores_locked_at: string | null; schedule: { id: string; title: string; mode: string; exam_date: string } | null; room: { name: string; venue: { name: string } | null } | null; examiners: Array<{ user: { name: string } | null; role: string }>; participants: Array<{ id: string; participant_number: string; name: string | null; attendance_status: string; checked_in_at: string | null; note: string | null }>; attendance_statuses: string[] }

/** ADM-52 Detail sesi: presensi (QR/manual), daftar hadir, pindah peserta, kunci nilai. */
export function ExamSessionPage() {
  const { id = '' } = useParams()
  const session = useLoad(() => pmbAdmin.get<SessionDetail>(`exam-sessions/${id}`), [id])
  const action = useAction()
  const canManage = usePermission(P.EXAMS_MANAGE)
  const [token, setToken] = useState('')
  const [moving, setMoving] = useState<{ id: string } | null>(null)

  if (!session.data) return <p className="text-sm text-ink-secondary">{session.error?.message ?? 'Memuat...'}</p>

  const d = session.data
  const checkIn = (body: Record<string, unknown>, message: string) => action.run(() => pmbAdmin.post(`exam-sessions/${id}/check-in`, body), message).then(session.reload)

  return (
    <AdminPage title={`Sesi ${d.code}`} description={`${d.schedule?.title ?? ''} · ${dateTime(d.starts_at)} · ${d.room ? `${d.room.venue?.name} ${d.room.name}` : 'Daring'}`} trail={[{ label: 'Ujian', path: PMB.admin.exams }, ...(d.schedule ? [{ label: d.schedule.title, path: to(PMB.admin.examSchedule, { id: d.schedule.id }) }] : []), { label: d.code }]}
      actions={
        <div className="flex flex-wrap gap-2">
          <Button size="sm" variant="outline" leftIcon={<Printer className="size-4" />} onClick={() => action.run(() => pmbAdmin.open(`exam-sessions/${id}/attendance-sheet`))}>Daftar hadir (PDF)</Button>
          <Link to={`${PMB.admin.scoring}?session=${id}`}><Button size="sm" variant="outline">Nilai sesi</Button></Link>
          <Can permission={P.SCORES_LOCK}>{!d.scores_locked_at && <Button size="sm" onClick={() => window.confirm('Kunci nilai sesi? Perubahan selanjutnya memerlukan koreksi beralasan.') && action.run(() => pmbAdmin.post(`exam-sessions/${id}/lock-scores`), 'Nilai sesi dikunci.').then(session.reload)}>Kunci nilai</Button>}</Can>
        </div>
      }>
      <Feedback state={action} />
      {d.scores_locked_at && <Alert variant="success">Nilai dikunci pada {dateTime(d.scores_locked_at)}.</Alert>}
      <Card title="Check-in" description="Pindai QR pada kartu peserta (pemindai kode batang bertindak sebagai keyboard) atau ubah status manual di tabel.">
        <form className="flex items-end gap-2" onSubmit={(e) => { e.preventDefault(); if (token) checkIn({ token, status: 'present' }, 'Peserta hadir dicatat.').then(() => setToken('')) }}>
          <Input label="Token QR" autoFocus value={token} onChange={(e) => setToken(e.target.value)} leftIcon={<QrCode className="size-4" />} />
          <Button type="submit" disabled={!token}>Catat hadir</Button>
        </form>
      </Card>
      <Card noPadding title={`Peserta (${d.participants.length}/${d.capacity})`}>
        <DataTable rowKey={(p) => p.id} data={d.participants} emptyMessage="Belum ada peserta." columns={[
          { header: 'No. peserta', cell: (p) => <span className="font-mono">{p.participant_number}</span> }, { header: 'Nama', cell: (p) => p.name },
          { header: 'Kehadiran', cell: (p) => <Select options={d.attendance_statuses.map((x) => ({ value: x, label: statusLabel(x) }))} value={p.attendance_status} onChange={(e) => checkIn({ participant_id: p.id, status: e.target.value }, 'Status kehadiran diubah.')} /> },
          { header: 'Check-in', cell: (p) => dateTime(p.checked_in_at) },
          { header: '', cell: (p) => canManage && <Button size="sm" variant="ghost" onClick={() => setMoving(p)}>Pindah sesi</Button> },
        ]} />
      </Card>
      <ReasonDialog open={moving !== null} title="Pindahkan peserta" extraFields={[{ name: 'target_session_id', label: 'ID sesi tujuan', required: true, hint: 'Lihat daftar sesi pada halaman jadwal.' }]} onClose={() => setMoving(null)} onConfirm={async (v) => { if (moving) await pmbAdmin.post(`exam-participants/${moving.id}/move`, v); session.reload() }} />
    </AdminPage>
  )
}

/** ADM-53 Nilai saya (penguji): sesi yang ditugaskan, input nilai/rubrik, simpan per sesi. */
export function ScoringPage() {
  const sessions = useLoad(() => pmbAdmin.get<Array<{ id: string; code: string; starts_at: string; schedule: string | null; mode: string | null; component: string | null; room: string | null; participants_count: number; scores_locked: boolean; roles: string[] }>>('examiner/sessions'), [])
  const initial = new URLSearchParams(window.location.search).get('session')
  const [active, setActive] = useState<string | null>(initial)

  return (
    <AdminPage title="Penilaian Ujian" description="Sesi yang ditugaskan kepada Anda." trail={[{ label: 'Ujian' }]}>
      <div className="grid gap-4 lg:grid-cols-3">
        <Card noPadding>
          {sessions.data?.length === 0 && <p className="p-4 text-sm text-ink-secondary">Tidak ada sesi yang ditugaskan.</p>}
          <ul className="divide-y divide-border">{(sessions.data ?? []).map((s) => (
            <li key={s.id}><button type="button" onClick={() => setActive(s.id)} className={`w-full p-3 text-left hover:bg-surface-hover ${active === s.id ? 'bg-surface-hover' : ''}`}><p className="text-sm font-medium">{s.schedule} · {s.code}</p><p className="text-xs text-ink-tertiary">{dateTime(s.starts_at)} · {s.component} · {s.participants_count} peserta {s.scores_locked && '· terkunci'}</p></button></li>
          ))}</ul>
        </Card>
        <div className="lg:col-span-2">{active ? <ScoreSheet sessionId={active} /> : <Card><p className="text-sm text-ink-secondary">Pilih sesi.</p></Card>}</div>
      </div>
    </AdminPage>
  )
}

function ScoreSheet({ sessionId }: { sessionId: string }) {
  const data = useLoad(() => pmbAdmin.get<{ session: { id: string; code: string; scores_locked_at: string | null }; component: { name: string; source: string; score_type: string; raw_min: string | null; raw_max: string | null; rubric: { criteria?: Array<{ key: string; label?: string; weight: number; max?: number }> } | null }; rows: Array<{ participant_id: string; participant_number: string; name: string | null; attendance_status: string; scores: Array<{ examiner_user_id: string | null; raw_score: string | null; passed: boolean | null; rubric_scores: Record<string, number> | null; note: string | null }> }> }>(`exam-sessions/${sessionId}/scores`), [sessionId])
  const [values, setValues] = useState<Record<string, { raw_score?: string; passed?: string; note?: string; rubric?: Record<string, string> }>>({})
  const action = useAction()

  useEffect(() => {
    if (!data.data) return
    setValues(Object.fromEntries(data.data.rows.map((r) => {
      const mine = r.scores[r.scores.length - 1]

      return [r.participant_id, { raw_score: mine?.raw_score ?? '', passed: mine?.passed === null || mine?.passed === undefined ? '' : mine.passed ? '1' : '0', note: mine?.note ?? '', rubric: Object.fromEntries(Object.entries(mine?.rubric_scores ?? {}).map(([k, v]) => [k, String(v)])) }]
    })))
  }, [data.data])

  if (!data.data) return <Card><p className="text-sm text-ink-secondary">{data.error?.message ?? 'Memuat...'}</p></Card>

  const d = data.data
  const criteria = d.component.rubric?.criteria ?? []
  const locked = d.session.scores_locked_at !== null
  const passFail = d.component.score_type === 'pass_fail'

  const save = () => action.run(() => pmbAdmin.put<{ saved: number }>(`exam-sessions/${sessionId}/scores`, {
    rows: d.rows.filter((r) => r.attendance_status === 'present').map((r) => {
      const v = values[r.participant_id] ?? {}

      return {
        participant_id: r.participant_id,
        raw_score: criteria.length === 0 && !passFail && v.raw_score !== '' ? Number(v.raw_score) : null,
        rubric_scores: criteria.length > 0 ? Object.fromEntries(criteria.map((c) => [c.key, Number(v.rubric?.[c.key] ?? 0)])) : null,
        passed: passFail ? v.passed === '1' : null,
        note: v.note || null,
      }
    }),
  }), (r) => `${r.saved} nilai disimpan.`).then(data.reload)

  return (
    <Card title={`${d.component.name} · sesi ${d.session.code}`} description={passFail ? 'Lulus/tidak' : criteria.length > 0 ? `Rubrik: ${criteria.map((c) => `${c.label ?? c.key} (${c.weight}%)`).join(', ')}` : `Rentang ${d.component.raw_min}–${d.component.raw_max}`}>
      <Feedback state={action} />
      {locked && <Alert variant="info">Nilai sesi sudah dikunci.</Alert>}
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead><tr className="text-left text-xs text-ink-tertiary"><th className="p-1">Peserta</th><th className="p-1">Hadir</th>{criteria.length > 0 ? criteria.map((c) => <th key={c.key} className="p-1">{c.label ?? c.key}</th>) : <th className="p-1">Nilai</th>}<th className="p-1">Catatan</th></tr></thead>
          <tbody>
            {d.rows.map((r) => {
              const v = values[r.participant_id] ?? {}
              const disabled = locked || r.attendance_status !== 'present'
              const set = (patch: typeof v) => setValues({ ...values, [r.participant_id]: { ...v, ...patch } })

              return (
                <tr key={r.participant_id} className="border-t border-border">
                  <td className="p-1">{r.name}<span className="block font-mono text-xs text-ink-tertiary">{r.participant_number}</span></td>
                  <td className="p-1"><StatusBadge status={r.attendance_status} /></td>
                  {criteria.length > 0 ? criteria.map((c) => <td key={c.key} className="p-1"><input type="number" min={0} max={c.max ?? 100} disabled={disabled} className="w-16 rounded border border-border bg-surface px-1" value={v.rubric?.[c.key] ?? ''} onChange={(e) => set({ rubric: { ...(v.rubric ?? {}), [c.key]: e.target.value } })} /></td>)
                    : <td className="p-1">{passFail ? <select disabled={disabled} className="rounded border border-border bg-surface" value={v.passed ?? ''} onChange={(e) => set({ passed: e.target.value })}><option value="">—</option><option value="1">Lulus</option><option value="0">Tidak</option></select> : <input type="number" step="0.01" disabled={disabled} className="w-20 rounded border border-border bg-surface px-1" value={v.raw_score ?? ''} onChange={(e) => set({ raw_score: e.target.value })} />}</td>}
                  <td className="p-1"><input disabled={disabled} className="w-full rounded border border-border bg-surface px-1" value={v.note ?? ''} onChange={(e) => set({ note: e.target.value })} /></td>
                </tr>
              )
            })}
          </tbody>
        </table>
      </div>
      {!locked && <Button className="mt-4" isLoading={action.busy} onClick={save}>Simpan nilai</Button>}
      <p className="mt-2 text-xs text-ink-tertiary">Hanya peserta berstatus hadir yang dapat dinilai. Data peserta ditampilkan minimal (tanpa NIK/kontak).</p>
    </Card>
  )
}

