import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Ban, CheckCircle2, Flag, MessageSquarePlus, Rocket, Undo2 } from 'lucide-react'
import { AdminList } from '@/pmb/components/AdminList'
import { AdminPage, useLookups } from '@/pmb/components/admin'
import { FormDialog } from '@/pmb/components/FormDialog'
import { KeyValue } from '@/pmb/components/KeyValue'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { Section } from '@/pmb/components/Section'
import { Tabs } from '@/pmb/components/Tabs'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { useLoad } from '@/pmb/components/useLoad'
import { ReviewBadge, SessionBadge, VersionBadge } from '@/pmb/components/talentShared'
import { usePeriodOptions, useTypeOptions } from '@/pmb/lib/talent'
import { usePermission } from '@/hooks/usePermission'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { EmptyState } from '@/components/ui/EmptyState'
import { dateTime } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'
import { talentApi, TP, SESSION_STATUS_LABEL, type AuditRow, type SessionDetail, type SessionRow, type SessionStatus } from '@/pmb/api/talent'

const SESSION_STATUS_OPTIONS = (Object.keys(SESSION_STATUS_LABEL) as SessionStatus[]).map((value) => ({ value, label: SESSION_STATUS_LABEL[value] }))

/** Daftar peserta asesmen dengan filter. Nama hanya tampil bagi yang berizin melihat data pendaftar. */
export function TalentParticipantsPage() {
  const { options: typeOptions } = useTypeOptions()
  const periods = usePeriodOptions()
  const lookups = useLookups()

  return (
    <AdminPage title="Peserta & Sesi" description="Pantau status sesi, review, dan hasil peserta. Jawaban mentah dibuka dari halaman sesi bila berwenang."
      trail={[{ label: 'One Atma Talent', path: PMB.admin.talent }]}>
      <AdminList<SessionRow>
        path="assessment-sessions"
        rowKey={(s) => s.id}
        itemLabel="sesi"
        filters={[
          { name: 'status', label: 'Status sesi', type: 'select', options: SESSION_STATUS_OPTIONS },
          { name: 'review_status', label: 'Review', type: 'select', options: [{ value: 'flagged', label: 'Perlu review' }, { value: 'reviewed', label: 'Sudah direview' }, { value: 'none', label: 'Belum direview' }] },
          { name: 'type_id', label: 'Jenis asesmen', type: 'select', options: typeOptions },
          { name: 'period_id', label: 'Periode PMB', type: 'select', options: periods },
          { name: 'study_program_id', label: 'Program studi', type: 'select', options: (lookups?.study_programs ?? []).map((p) => ({ value: p.id, label: `${p.code} · ${p.name}` })) },
          { name: 'mode', label: 'Mode', type: 'select', options: [{ value: 'online', label: 'Daring (mandiri)' }] },
        ]}
        columns={[
          { header: 'Nomor pendaftaran', cell: (s) => <Link className="font-medium text-primary hover:underline" to={to(PMB.admin.talentSession, { id: s.id })}>{s.registration_number ?? '—'}<span className="block text-xs font-normal text-ink-tertiary">{s.applicant_name ?? 'Nama disembunyikan'}</span></Link> },
          { header: 'Program studi', cell: (s) => <span className="text-sm">{s.study_program?.name ?? '—'}</span> },
          { header: 'Periode', cell: (s) => <span className="text-sm">{s.period?.name ?? '—'}</span> },
          { header: 'Asesmen', cell: (s) => <span className="text-sm">{s.assessment.name}<span className="block text-xs text-ink-tertiary">Versi {s.version.version_no} · <VersionBadge status={s.version.status} /></span></span> },
          { header: 'Mulai', cell: (s) => <span className="text-sm">{dateTime(s.started_at)}</span> },
          { header: 'Dikirim', cell: (s) => <span className="text-sm">{dateTime(s.submitted_at)}</span> },
          { header: 'Status', cell: (s) => <SessionBadge status={s.status} /> },
          { header: 'Review', cell: (s) => <ReviewBadge status={s.review_status} /> },
          { header: 'Hasil', cell: (s) => s.result_status === 'published' ? <Badge variant="success">{s.holland_code ?? 'Terbit'}</Badge> : <span className="text-sm text-ink-tertiary">{s.result_status === 'scored' ? 'Menunggu publikasi' : '—'}</span> },
        ]}
        emptyMessage="Belum ada peserta yang memulai asesmen."
      />
    </AdminPage>
  )
}

type SessionTab = 'ringkasan' | 'respons' | 'riwayat' | 'review'

/** Detail sesi: ringkasan skor, jawaban (bila berwenang), riwayat kejadian, dan review sebelum publikasi. */
export function TalentSessionPage() {
  const { id = '' } = useParams()
  const [reload, setReload] = useState(0)
  const refresh = () => setReload((n) => n + 1)
  const session = useLoad(() => talentApi.session(id), [id, reload])
  const review = useLoad(() => talentApi.review(id), [id, reload])
  const action = useAction()
  const [tab, setTab] = useState<SessionTab>('ringkasan')
  const [dialog, setDialog] = useState<'flag' | 'unflag' | 'complete' | 'note' | 'invalidate' | null>(null)
  const perms = {
    review: usePermission(TP.RESULTS_REVIEW),
    publish: usePermission(TP.RESULTS_PUBLISH),
    invalidate: usePermission(TP.SESSIONS_INVALIDATE),
  }
  const s = session.data
  const open = s !== undefined && s !== null && s.status !== 'published' && s.status !== 'invalidated'

  const run = (fn: () => Promise<unknown>, text: string) => action.run(fn, text).then(() => { setDialog(null); refresh() })

  if (session.loading && !s) return <p className="p-6 text-sm text-ink-secondary">Memuat sesi...</p>
  if (session.error) return <Alert variant="danger">{session.error.message}</Alert>
  if (!s) return null

  const headerActions: React.ReactNode[] = []

  if (open && perms.review) {
    if (s.review_status === 'flagged') headerActions.push(<Button key="unflag" variant="outline" leftIcon={<Undo2 className="size-4" />} onClick={() => setDialog('unflag')}>Batalkan penandaan</Button>)
    else headerActions.push(<Button key="flag" variant="outline" leftIcon={<Flag className="size-4" />} onClick={() => setDialog('flag')}>Tandai perlu review</Button>)
    if (s.review_status !== 'reviewed') headerActions.push(<Button key="complete" variant="outline" leftIcon={<CheckCircle2 className="size-4" />} onClick={() => setDialog('complete')}>Tandai review selesai</Button>)
    headerActions.push(<Button key="note" variant="ghost" leftIcon={<MessageSquarePlus className="size-4" />} onClick={() => setDialog('note')}>Catatan</Button>)
  }

  if (open && perms.publish && review.data?.can_publish) {
    headerActions.push(<Button key="publish" leftIcon={<Rocket className="size-4" />} onClick={() => window.confirm('Terbitkan hasil ke peserta? Hasil tampil di portal peserta setelah ini.') && run(() => talentApi.publishSession(id), 'Hasil dipublikasikan.')}>Terbitkan hasil</Button>)
  }

  if (open && perms.invalidate) {
    headerActions.push(<Button key="invalidate" variant="danger" leftIcon={<Ban className="size-4" />} onClick={() => setDialog('invalidate')}>Invalidasi sesi</Button>)
  }

  return (
    <AdminPage title={`Sesi ${s.registration_number ?? ''}`} description={`${s.assessment.name ?? ''} · Versi ${s.version.version_no}`}
      trail={[{ label: 'One Atma Talent', path: PMB.admin.talent }, { label: 'Peserta & Sesi', path: PMB.admin.talentParticipants }]}
      actions={<div className="flex flex-wrap items-center gap-2"><SessionBadge status={s.status} /><ReviewBadge status={s.review_status} />{headerActions}</div>}>
      <Feedback state={action} />
      {s.status === 'invalidated' && s.invalidation && <Alert variant="danger" title="Sesi diinvalidasi">{s.invalidation.reason} · {dateTime(s.invalidation.at)}{s.invalidation.by ? ` · ${s.invalidation.by}` : ''}</Alert>}
      {s.review_status === 'flagged' && <Alert variant="warning">Sesi ditandai perlu review. Hasil tidak dapat diterbitkan sebelum review selesai.</Alert>}

      <Tabs<SessionTab>
        active={tab}
        onChange={setTab}
        tabs={[
          { key: 'ringkasan', label: 'Ringkasan' },
          { key: 'respons', label: 'Jawaban', badge: s.responses?.length },
          { key: 'riwayat', label: 'Riwayat', badge: s.events.length },
          { key: 'review', label: 'Review & publikasi' },
        ]}
      />

      {tab === 'ringkasan' && <SummaryPanel s={s} />}
      {tab === 'respons' && <ResponsesPanel s={s} />}
      {tab === 'riwayat' && <HistoryPanel s={s} />}
      {tab === 'review' && <ReviewPanel review={review.data ?? null} loading={review.loading} />}

      <FormDialog open={dialog === 'note'} title="Tambah catatan review" fields={[{ name: 'body', label: 'Catatan', type: 'textarea', required: true }]} onClose={() => setDialog(null)}
        onSubmit={async (v) => { await run(() => talentApi.addNote(id, String(v.body)), 'Catatan disimpan.') }} />
      <ReasonDialog open={dialog === 'flag'} title="Tandai perlu review" description="Publikasi ditahan sampai review selesai. Alasan tercatat di riwayat." confirmLabel="Tandai" reasonLabel="Alasan" reasonName="reason" onClose={() => setDialog(null)} onConfirm={(v) => run(() => talentApi.flag(id, String(v.reason ?? '')), 'Sesi ditandai perlu review.')} />
      <ReasonDialog open={dialog === 'unflag'} title="Batalkan penandaan" description="Penandaan dibatalkan tanpa menyelesaikan review." confirmLabel="Batalkan penandaan" reasonLabel="Alasan" reasonName="note" onClose={() => setDialog(null)} onConfirm={(v) => run(() => talentApi.unflag(id, String(v.note ?? '')), 'Penandaan dibatalkan.')} />
      <ReasonDialog open={dialog === 'complete'} title="Tandai review selesai" description="Hasil dapat diterbitkan setelah review selesai." confirmLabel="Selesai" reasonLabel="Catatan review" reasonName="note" onClose={() => setDialog(null)} onConfirm={(v) => run(() => talentApi.completeReview(id, String(v.note ?? '')), 'Review selesai.')} />
      <ReasonDialog open={dialog === 'invalidate'} title="Invalidasi sesi" description="Sesi tidak lagi dihitung dan hasilnya tidak tampil. Jawaban tetap disimpan untuk audit. Sesi yang sudah terbit tidak dapat diinvalidasi." confirmLabel="Invalidasi" reasonLabel="Alasan (wajib, minimal 10 karakter)" reasonName="reason" onClose={() => setDialog(null)} onConfirm={(v) => run(() => talentApi.invalidate(id, String(v.reason ?? '')), 'Sesi diinvalidasi.')} />
    </AdminPage>
  )
}

function SummaryPanel({ s }: { s: SessionDetail }) {
  const duration = s.submitted_at && s.started_at ? Math.round((new Date(s.submitted_at).getTime() - new Date(s.started_at).getTime()) / 60000) : null

  return (
    <div className="flex flex-col gap-5">
      <Card>
        <KeyValue columns={2} items={[
          ['Nomor pendaftaran', s.registration_number ?? '—'],
          ['Nama peserta', s.applicant_name ?? 'Disembunyikan'],
          ['Program studi', s.study_program?.name ?? '—'],
          ['Periode PMB', s.period?.name ?? '—'],
          ['Asesmen', s.assessment.name ?? '—'],
          ['Versi', `Versi ${s.version.version_no} · ${s.version.title}`],
          ['Mode', s.mode === 'online' ? 'Daring (mandiri)' : s.mode],
          ['Mulai', dateTime(s.started_at)],
          ['Batas waktu', s.deadline_at ? dateTime(s.deadline_at) : 'Tidak dibatasi'],
          ['Dikirim', dateTime(s.submitted_at)],
          ['Lama mengerjakan', duration === null ? '—' : `${duration} menit`],
          ['Holland Code', s.holland_code ?? '—'],
          ['Versi scoring', s.scoring_engine ?? '—'],
          ['Dipublikasikan', dateTime(s.published_at)],
        ]} />
      </Card>
      <Section title="Skor per dimensi" description="Rata-rata dan persentase posisi pada rentang skala. Bukan skor terstandar.">
        <Card noPadding>
          <DataTable<SessionDetail['scores'][number]>
            columns={[
              { header: 'Peringkat', cell: (r) => <span className="tabular-nums">{r.rank}</span> },
              { header: 'Dimensi', cell: (r) => <span className="font-mono font-semibold">{r.dimension_code}</span> },
              { header: 'Butir', cell: (r) => <span className="tabular-nums">{r.item_count}</span> },
              { header: 'Skor mentah', cell: (r) => <span className="tabular-nums">{r.raw_score}</span> },
              { header: 'Rata-rata', cell: (r) => <span className="tabular-nums">{r.mean_score}</span> },
              { header: 'Persentase', cell: (r) => <span className="tabular-nums">{r.percent_score}%</span> },
            ]}
            data={s.scores}
            rowKey={(r) => r.dimension_code}
            emptyMessage="Belum ada skor. Sesi yang tidak lengkap saat waktu habis tidak dinilai."
          />
        </Card>
      </Section>
    </div>
  )
}

function ResponsesPanel({ s }: { s: SessionDetail }) {
  if (s.responses_restricted || s.responses === null) {
    return <Alert variant="info">Jawaban mentah hanya ditampilkan untuk peran yang memiliki izin data sensitif PMB.</Alert>
  }

  return (
    <Card noPadding>
      <DataTable<NonNullable<SessionDetail['responses']>[number]>
        columns={[
          { header: 'Dimensi', cell: (r) => <span className="font-mono">{r.dimension_code}</span> },
          { header: 'Pertanyaan', cell: (r) => <span className="line-clamp-2">{r.question}</span> },
          { header: 'Arah', cell: (r) => <span className="text-sm">{r.reversed ? 'Terbalik' : 'Biasa'}</span> },
          { header: 'Nilai', cell: (r) => <span className="font-semibold tabular-nums">{r.value}</span> },
        ]}
        data={s.responses}
        rowKey={(r) => r.item_id}
        emptyMessage="Belum ada jawaban tersimpan."
      />
    </Card>
  )
}

function HistoryPanel({ s }: { s: SessionDetail }) {
  return (
    <div className="grid gap-5 lg:grid-cols-2">
      <Card title="Kejadian sesi" description="Mulai, kirim, tutup, publikasi, dan invalidasi.">
        {s.events.length === 0 ? <EmptyState title="Belum ada kejadian" /> : (
          <ol className="flex flex-col gap-3 text-sm">
            {s.events.map((e, i) => (
              <li key={i} className="border-l-2 border-border pl-3">
                <p className="font-medium text-ink-primary">{EVENT_LABEL[e.type] ?? e.type}</p>
                <p className="text-xs text-ink-tertiary">{dateTime(e.at)} · {e.actor}</p>
              </li>
            ))}
          </ol>
        )}
      </Card>
      <Card title="Catatan review" description="Catatan, penandaan, dan penyelesaian review.">
        {s.review_notes.length === 0 ? <EmptyState title="Belum ada catatan" /> : (
          <ol className="flex flex-col gap-3 text-sm">
            {s.review_notes.map((n, i) => (
              <li key={i} className="border-l-2 border-border pl-3">
                <p className="font-medium text-ink-primary">{NOTE_LABEL[n.kind] ?? n.kind}</p>
                {n.body && <p className="text-ink-secondary">{n.body}</p>}
                <p className="text-xs text-ink-tertiary">{dateTime(n.at)} · {n.author ?? 'Staf'}</p>
              </li>
            ))}
          </ol>
        )}
      </Card>
    </div>
  )
}

function ReviewPanel({ review, loading }: { review: import('@/pmb/api/talent').ReviewPayload | null; loading: boolean }) {
  if (loading && !review) return <p className="text-sm text-ink-secondary">Memuat review...</p>
  if (!review) return null

  return (
    <div className="flex flex-col gap-5">
      <Card title="Kelengkapan publikasi" description="Semua item harus terpenuhi sebelum hasil dapat diterbitkan.">
        <ul className="flex flex-col gap-2">
          {review.checks.map((c) => (
            <li key={c.key} className="flex items-center justify-between rounded-md border border-border px-4 py-2 text-sm">
              <span>{c.label}</span>
              <Badge variant={c.ok ? 'success' : 'danger'}>{c.ok ? 'Terpenuhi' : 'Belum'}</Badge>
            </li>
          ))}
        </ul>
        <p className="mt-4 text-sm text-ink-secondary">{review.can_publish ? 'Siap diterbitkan.' : 'Belum dapat diterbitkan.'}</p>
      </Card>
      <Card title="Riwayat publikasi" description="Hasil dapat ditelusuri ke sesi, versi, versi scoring, dan kejadian publikasi.">
        {review.publication_history.length === 0 ? <EmptyState title="Belum dipublikasikan" /> : (
          <ul className="flex flex-col gap-2 text-sm">
            {review.publication_history.map((p, i) => (
              <li key={i}>{dateTime(p.at)} · versi {String(p.payload?.version_id ?? '—')} · {String(p.payload?.scoring_engine ?? '—')} · {String(p.payload?.holland_code ?? '—')}</li>
            ))}
          </ul>
        )}
      </Card>
    </div>
  )
}

const EVENT_LABEL: Record<string, string> = {
  started: 'Sesi dimulai', submitted: 'Asesmen dikirim', expired: 'Waktu habis', published: 'Hasil dipublikasikan', invalidated: 'Sesi diinvalidasi', review_flagged: 'Ditandai perlu review', review_completed: 'Review selesai',
}

const NOTE_LABEL: Record<string, string> = { note: 'Catatan', flag: 'Ditandai perlu review', unflag: 'Penandaan dibatalkan', review_completed: 'Review selesai' }

const AUDIT_OBJECT: Record<string, string> = {
  AssessmentType: 'Jenis asesmen', AssessmentVersion: 'Versi', AssessmentDimension: 'Dimensi', AssessmentItem: 'Pertanyaan', AssessmentSession: 'Sesi',
}

const AUDIT_ACTION: Record<string, string> = { created: 'Dibuat', updated: 'Diubah', deleted: 'Dihapus', restored: 'Dipulihkan' }

/** Riwayat audit One Atma Talent: siapa mengubah apa, kapan, dan dengan alasan apa. */
export function TalentAuditPage() {
  const columns = useMemo<DataTableColumn<AuditRow>[]>(() => [
    { header: 'Waktu', cell: (r) => <span className="text-sm">{dateTime(r.at)}</span> },
    { header: 'Aksi', cell: (r) => <Badge variant={r.action === 'deleted' ? 'danger' : 'neutral'}>{AUDIT_ACTION[r.action] ?? r.action}</Badge> },
    { header: 'Objek', cell: (r) => <span className="text-sm">{AUDIT_OBJECT[r.auditable_type] ?? r.auditable_type}<span className="block font-mono text-xs text-ink-tertiary">{r.auditable_id}</span></span> },
    { header: 'Perubahan', cell: (r) => <span className="text-sm text-ink-secondary">{changeSummary(r)}</span> },
    { header: 'Oleh', cell: (r) => <span className="text-sm">{r.user ?? 'Sistem'}</span> },
    { header: 'Alasan', cell: (r) => <span className="text-sm">{r.reason ?? '—'}</span> },
  ], [])

  return (
    <AdminPage title="Audit Talenta" description="Riwayat perubahan jenis, versi, dimensi, pertanyaan, dan sesi. Riwayat tidak dapat dihapus."
      trail={[{ label: 'One Atma Talent', path: PMB.admin.talent }]}>
      <AdminList<AuditRow>
        path="assessment-audit-logs"
        rowKey={(r) => r.id}
        itemLabel="catatan"
        search={false}
        filters={[
          { name: 'action', label: 'Aksi', type: 'select', options: [{ value: 'created', label: 'Dibuat' }, { value: 'updated', label: 'Diubah' }, { value: 'deleted', label: 'Dihapus' }] },
          { name: 'from', label: 'Dari tanggal', type: 'date' },
          { name: 'to', label: 'Sampai tanggal', type: 'date' },
        ]}
        columns={columns}
        emptyMessage="Belum ada riwayat."
      />
    </AdminPage>
  )
}

function changeSummary(r: AuditRow): string {
  const next = r.new_values ?? {}
  const prev = r.old_values ?? {}
  const keys = Object.keys(next)

  if (keys.length === 0) return '—'

  return keys.map((k) => (k in prev ? `${k}: ${String(prev[k])} → ${String(next[k])}` : `${k}: ${String(next[k])}`)).join('; ')
}
