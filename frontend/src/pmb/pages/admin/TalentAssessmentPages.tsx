import { useState, type ReactNode } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Archive, Ban, Copy, Eye, Pencil, Plus, Rocket, Send, ShieldCheck, Trash2, Undo2 } from 'lucide-react'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminPage } from '@/pmb/components/admin'
import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { KeyValue } from '@/pmb/components/KeyValue'
import { Tabs } from '@/pmb/components/Tabs'
import { Section } from '@/pmb/components/Section'
import { VersionBadge } from '@/pmb/components/talentShared'
import { LIKERT_LABEL, usePeriodOptions } from '@/pmb/lib/talent'
import { usePermission } from '@/hooks/usePermission'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { EmptyState } from '@/components/ui/EmptyState'
import { Modal } from '@/components/ui/Modal'
import { dateTime } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'
import { talentApi, TP, type AssessmentTypeRow, type DimensionRow, type QuestionRow, type VersionDetail, type VersionRow } from '@/pmb/api/talent'

const DOMAIN_LABEL: Record<string, string> = {
  interest: 'Minat (RIASEC)', aptitude: 'Bakat & penalaran', academic_readiness: 'Kesiapan akademik', learning_preference: 'Preferensi belajar', career_aspiration: 'Aspirasi karier',
}

const PURPOSE_LABEL: Record<string, string> = { exploratory: 'Eksplorasi (bukan seleksi)', admissions: 'Seleksi (perlu persetujuan fakultas)' }

const ACTION_LABEL: Record<string, string> = { created: 'Dibuat', updated: 'Diubah', deleted: 'Dihapus' }

/** Daftar jenis asesmen One Atma Talent dengan status aktif dan versi berjalan. */
export function TalentAssessmentsPage() {
  const [reload, setReload] = useState(0)
  const types = useLoad(() => talentApi.types(), [reload])
  const action = useAction()
  const [creating, setCreating] = useState(false)
  const canCreate = usePermission(TP.ASSESSMENTS_CREATE)
  const canManage = usePermission(TP.ASSESSMENTS_MANAGE)
  const refresh = () => setReload((n) => n + 1)

  const toggle = (t: AssessmentTypeRow) => {
    if (!window.confirm(t.is_active ? `Nonaktifkan "${t.name}"? Peserta tidak akan melihatnya di daftar asesmen.` : `Aktifkan "${t.name}"?`)) return
    action.run(() => talentApi.updateType(t.id, { is_active: !t.is_active }), t.is_active ? 'Asesmen dinonaktifkan.' : 'Asesmen diaktifkan.').then(refresh)
  }

  const columns: DataTableColumn<AssessmentTypeRow>[] = [
    { header: 'Jenis asesmen', cell: (t) => <Link className="font-medium text-primary hover:underline" to={to(PMB.admin.talentType, { id: t.id })}>{t.name}<span className="block text-xs font-normal text-ink-tertiary">{t.code}</span></Link> },
    { header: 'Domain', cell: (t) => <span>{DOMAIN_LABEL[t.domain] ?? t.domain}{!t.domain_supports_scoring && <span className="block text-xs text-ink-tertiary">Belum ada scoring engine</span>}</span> },
    { header: 'Versi terbit', cell: (t) => t.published_version ? <span>Versi {t.published_version.version_no} · {t.published_version.title}</span> : <span className="text-ink-tertiary">Belum ada</span> },
    { header: 'Versi berjalan', cell: (t) => t.open_version ? <VersionBadge status={t.open_version.status} /> : <span className="text-ink-tertiary">—</span> },
    { header: 'Peserta', cell: (t) => <span className="tabular-nums">{t.sessions_count ?? 0}</span> },
    { header: 'Status', cell: (t) => <Badge variant={t.is_active ? 'success' : 'neutral'}>{t.is_active ? 'Aktif' : 'Nonaktif'}</Badge> },
    { header: '', cell: (t) => canManage && <Button size="sm" variant="ghost" onClick={() => toggle(t)}>{t.is_active ? 'Nonaktifkan' : 'Aktifkan'}</Button> },
  ]

  return (
    <AdminPage title="Asesmen & Versi" description="Jenis asesmen dan kondisi versinya. Versi yang sudah diajukan atau terbit tidak dapat diubah." trail={[{ label: 'One Atma Talent', path: PMB.admin.talent }]}
      actions={canCreate && <Button leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Jenis asesmen baru</Button>}>
      <Feedback state={action} />
      {types.error && <Alert variant="danger">{types.error.message}</Alert>}
      <Card noPadding>
        {types.loading && !types.data ? <p className="p-5 text-sm text-ink-secondary">Memuat...</p> : (
          <DataTable columns={columns} data={types.data ?? []} rowKey={(t) => t.id} emptyMessage="Belum ada jenis asesmen. Buat jenis baru untuk memulai." />
        )}
      </Card>
      <FormDialog
        open={creating}
        title="Jenis asesmen baru"
        description="Kode bersifat tetap. Domain menentukan dimensi yang wajib dan apakah scoring tersedia."
        initial={{ domain: 'interest', is_active: true }}
        fields={[
          { name: 'code', label: 'Kode', required: true, hint: 'Huruf, angka, tanda hubung. Contoh: MINAT-RIASEC' },
          { name: 'name', label: 'Nama', required: true },
          { name: 'domain', label: 'Domain', type: 'select', required: true, options: Object.entries(DOMAIN_LABEL).map(([value, label]) => ({ value, label: value === 'interest' ? `${label} — siap dinilai` : `${label} — belum ada scoring` })) },
          { name: 'description', label: 'Deskripsi', type: 'textarea' },
          { name: 'is_active', label: 'Aktif', type: 'checkbox' },
        ]}
        onClose={() => setCreating(false)}
        onSubmit={async (values) => { await action.run(() => talentApi.createType(values), 'Jenis asesmen dibuat.'); setCreating(false); refresh() }}
      />
    </AdminPage>
  )
}

/** Detail jenis asesmen: data jenis, daftar versi, dan pembuatan versi draft baru. */
export function TalentTypePage() {
  const { id = '' } = useParams()
  const [reload, setReload] = useState(0)
  const refresh = () => setReload((n) => n + 1)
  const types = useLoad(() => talentApi.types(), [reload])
  const versions = useLoad(() => talentApi.versions(id), [id, reload])
  const periods = usePeriodOptions()
  const action = useAction()
  const [creating, setCreating] = useState(false)
  const [cloning, setCloning] = useState<VersionRow | null>(null)
  const canCreate = usePermission(TP.ASSESSMENTS_CREATE)
  const type = types.data?.find((t) => t.id === id)
  const openVersion = (versions.data ?? []).find((v) => ['draft', 'in_review', 'approved'].includes(v.status))

  const columns: DataTableColumn<VersionRow>[] = [
    { header: 'Versi', cell: (v) => <Link className="font-medium text-primary hover:underline" to={to(PMB.admin.talentVersion, { id: v.id })}>Versi {v.version_no}<span className="block text-xs font-normal text-ink-secondary">{v.title}</span></Link> },
    { header: 'Tujuan', cell: (v) => <span className="text-sm">{PURPOSE_LABEL[v.purpose] ?? v.purpose}</span> },
    { header: 'Status', cell: (v) => <VersionBadge status={v.status} /> },
    { header: 'Dimensi / butir', cell: (v) => <span className="tabular-nums">{v.dimensions_count} / {v.items_count}</span> },
    { header: 'Peserta', cell: (v) => <span className="tabular-nums">{v.sessions_count}</span> },
    { header: 'Diajukan', cell: (v) => <span className="text-sm">{dateTime(v.submitted_at)}</span> },
    { header: 'Terbit', cell: (v) => <span className="text-sm">{dateTime(v.published_at)}</span> },
    { header: '', cell: (v) => (canCreate && v.status !== 'draft') && <Button size="sm" variant="ghost" leftIcon={<Copy className="size-4" />} onClick={() => setCloning(v)}>Salin</Button> },
  ]

  return (
    <AdminPage title={type?.name ?? 'Jenis asesmen'} description={type ? `${DOMAIN_LABEL[type.domain] ?? type.domain} · kode ${type.code}` : undefined} trail={[{ label: 'One Atma Talent', path: PMB.admin.talent }, { label: 'Asesmen & Versi', path: PMB.admin.talentAssessments }]}
      actions={canCreate && !openVersion && <Button leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Versi draft baru</Button>}>
      <Feedback state={action} />
      {types.error && <Alert variant="danger">{types.error.message}</Alert>}
      {openVersion && (
        <Alert variant="info">
          Ada versi yang belum selesai: <Link className="font-medium text-primary hover:underline" to={to(PMB.admin.talentVersion, { id: openVersion.id })}>Versi {openVersion.version_no} ({openVersion.status === 'draft' ? 'draft' : openVersion.status === 'in_review' ? 'ditelaah' : 'disetujui'})</Link>. Selesaikan versi itu sebelum membuat versi baru.
        </Alert>
      )}
      {type && !type.is_active && <Alert variant="warning">Jenis asesmen ini nonaktif. Peserta tidak melihatnya di daftar asesmen.</Alert>}
      <Card title="Riwayat versi" description="Versi terbit dan versi sebelumnya tetap dapat dibaca. Versi yang sudah terbit tidak diubah.">
        {versions.loading && !versions.data ? <p className="text-sm text-ink-secondary">Memuat...</p> : (
          <DataTable columns={columns} data={versions.data ?? []} rowKey={(v) => v.id} emptyMessage="Belum ada versi. Buat versi draft untuk menyusun instrumen." />
        )}
      </Card>

      <FormDialog
        open={creating}
        title="Versi draft baru"
        description="Versi baru berstatus draft. Dimensi dan butir disusun di halaman versi."
        initial={{ purpose: 'exploratory' }}
        fields={[
          { name: 'title', label: 'Judul versi', required: true, placeholder: 'Minat & Bakat 2027/2028' },
          { name: 'purpose', label: 'Tujuan penggunaan', type: 'select', required: true, hint: 'Tujuan dikunci setelah versi dibuat. Versi seleksi tidak dapat diterbitkan sebelum ada persetujuan fakultas.', options: Object.entries(PURPOSE_LABEL).map(([value, label]) => ({ value, label })) },
          { name: 'instructions', label: 'Petunjuk untuk peserta', type: 'textarea' },
          { name: 'duration_minutes', label: 'Durasi (menit)', type: 'number', min: '1', max: '600', hint: 'Kosongkan bila tidak dibatasi waktu.' },
          { name: 'available_from', label: 'Dibuka mulai', type: 'datetime' },
          { name: 'available_until', label: 'Ditutup pada', type: 'datetime' },
          { name: 'period_id', label: 'Periode PMB', type: 'select', options: periods, hint: 'Kosongkan untuk berlaku di semua periode.' },
        ] satisfies FieldDef[]}
        onClose={() => setCreating(false)}
        onSubmit={async (values) => { await action.run(() => talentApi.createVersion(id, values), 'Versi draft dibuat.'); setCreating(false); refresh() }}
      />
      <FormDialog
        open={cloning !== null}
        title="Salin menjadi versi baru"
        description="Dimensi dan butir disalin ke draft baru. Versi asal tidak berubah."
        fields={[{ name: 'change_note', label: 'Catatan perubahan', type: 'textarea', hint: 'Mis. revisi butir kedua setelah masukan panelis.' }]}
        onClose={() => setCloning(null)}
        onSubmit={async (values) => {
          if (!cloning) return
          const created = await action.run(() => talentApi.cloneVersion(cloning.id, String(values.change_note ?? '')), 'Versi baru dibuat dari salinan.')
          setCloning(null)
          if (created) window.location.assign(to(PMB.admin.talentVersion, { id: created.id }))
        }}
      />
    </AdminPage>
  )
}

type VersionTab = 'ringkasan' | 'dimensi' | 'pertanyaan' | 'persetujuan' | 'perbandingan' | 'riwayat'

/**
 * Halaman versi: siklus draft → ditelaah → disetujui → terbit → diarsip, serta isi instrumen.
 * Tombol hanya tampil bila status mengizinkan dan izin tersedia; backend tetap memeriksa keduanya.
 */
export function TalentVersionPage() {
  const { id = '' } = useParams()
  const navigate = useNavigate()
  const [reload, setReload] = useState(0)
  const refresh = () => setReload((n) => n + 1)
  const detail = useLoad(() => talentApi.version(id), [id, reload])
  const action = useAction()
  const [tab, setTab] = useState<VersionTab>('ringkasan')
  const [dialog, setDialog] = useState<'submit' | 'edit' | 'clone' | 'archive' | 'return' | 'reject' | null>(null)
  const perms = {
    manage: usePermission(TP.ASSESSMENTS_MANAGE),
    create: usePermission(TP.ASSESSMENTS_CREATE),
    remove: usePermission(TP.ASSESSMENTS_ARCHIVE),
    approve: usePermission(TP.ASSESSMENTS_APPROVE),
    publish: usePermission(TP.ASSESSMENTS_PUBLISH),
    questions: usePermission(TP.QUESTIONS_READ),
    questionCreate: usePermission(TP.QUESTIONS_CREATE),
    questionManage: usePermission(TP.QUESTIONS_MANAGE),
    questionDelete: usePermission(TP.QUESTIONS_DELETE),
  }
  const v = detail.data
  const periods = usePeriodOptions()

  const decide = (decision: 'approve' | 'return' | 'reject', comment?: string) => action.run(() => talentApi.decide(id, decision, comment), decision === 'approve' ? 'Disetujui. Langkah berikutnya menunggu keputusan.' : decision === 'return' ? 'Dikembalikan ke draft.' : 'Ditolak.').then(() => { setDialog(null); refresh() })

  const questionsQuery = useLoad(() => (perms.questions ? talentApi.questions({ version_id: id, per_page: 100, sort: 'sort_order' }) : Promise.resolve({ data: [], meta: { current_page: 1, per_page: 100, total: 0, last_page: 1 } })), [id, reload, perms.questions])

  if (detail.loading && !v) return <p className="p-6 text-sm text-ink-secondary">Memuat versi...</p>
  if (detail.error) return <Alert variant="danger">{detail.error.message}</Alert>
  if (!v) return null

  const headerActions: ReactNode[] = []

  if (v.status === 'draft') {
    if (perms.manage) headerActions.push(<Button key="submit" leftIcon={<Send className="size-4" />} disabled={!v.readiness.ok} onClick={() => setDialog('submit')}>Ajukan untuk ditelaah</Button>)
    if (perms.remove && v.can_delete) headerActions.push(<Button key="delete" variant="outline" leftIcon={<Trash2 className="size-4" />} onClick={() => window.confirm('Hapus draft ini? Tindakan ini tidak dapat dibatalkan.') && action.run(() => talentApi.deleteVersion(id), 'Draft dihapus.').then(() => navigate(to(PMB.admin.talentType, { id: v.type.id })))}>Hapus draft</Button>)
  }

  if (v.status === 'in_review' && v.approval?.can_decide && perms.approve) {
    headerActions.push(<Button key="approve" leftIcon={<ShieldCheck className="size-4" />} onClick={() => window.confirm('Setujui langkah ini?') && decide('approve').then(() => undefined)}>Setujui</Button>)
    headerActions.push(<Button key="return" variant="outline" leftIcon={<Undo2 className="size-4" />} onClick={() => setDialog('return')}>Kembalikan</Button>)
    headerActions.push(<Button key="reject" variant="outline" leftIcon={<Ban className="size-4" />} onClick={() => setDialog('reject')}>Tolak</Button>)
  }

  if (v.status === 'approved' && perms.publish) {
    headerActions.push(<Button key="publish" leftIcon={<Rocket className="size-4" />} onClick={() => window.confirm(`Terbitkan Versi ${v.version_no}? Versi terbit sebelumnya akan diarsipkan.`) && action.run(() => talentApi.publish(id), 'Versi diterbitkan.').then(refresh)}>Terbitkan</Button>)
  }

  if (v.status === 'published' && perms.remove) {
    headerActions.push(<Button key="archive" variant="outline" leftIcon={<Archive className="size-4" />} onClick={() => setDialog('archive')}>Arsipkan</Button>)
  }

  if (v.status !== 'draft' && perms.create) {
    headerActions.push(<Button key="clone" variant="outline" leftIcon={<Copy className="size-4" />} onClick={() => setDialog('clone')}>Salin menjadi versi baru</Button>)
  }

  return (
    <AdminPage title={`Versi ${v.version_no} · ${v.title}`} description={`${v.type.name} · ${PURPOSE_LABEL[v.purpose] ?? v.purpose}`} trail={[{ label: 'One Atma Talent', path: PMB.admin.talent }, { label: 'Asesmen & Versi', path: PMB.admin.talentAssessments }, { label: v.type.name, path: to(PMB.admin.talentType, { id: v.type.id }) }]}
      actions={<div className="flex flex-wrap items-center gap-2"><VersionBadge status={v.status} />{headerActions}</div>}>
      <Feedback state={action} />

      {v.status === 'draft' && !v.readiness.ok && <Alert variant="warning" title="Belum siap diajukan">{v.readiness.message}</Alert>}
      {v.status === 'published' && <Alert variant="success">Versi ini sedang dipakai peserta. Isinya tidak dapat diubah; untuk revisi, salin menjadi versi baru.</Alert>}
      {v.status === 'rejected' && <Alert variant="danger">Versi ini ditolak. Untuk merevisi, salin menjadi versi baru.</Alert>}
      {v.status === 'archived' && <Alert variant="info">Versi ini diarsipkan. Hasil peserta yang sudah ada tetap dapat dibaca.</Alert>}

      <Tabs<VersionTab>
        active={tab}
        onChange={setTab}
        tabs={[
          { key: 'ringkasan', label: 'Ringkasan' },
          { key: 'dimensi', label: 'Dimensi', badge: v.dimensions.length },
          { key: 'pertanyaan', label: 'Pertanyaan', badge: v.items_count },
          { key: 'persetujuan', label: 'Persetujuan' },
          { key: 'perbandingan', label: 'Perbandingan' },
          { key: 'riwayat', label: 'Riwayat', badge: v.history.length },
        ]}
      />

      {tab === 'ringkasan' && (
        <Section title="Konfigurasi" description="Tujuan penggunaan dikunci setelah versi dibuat. Pemisahan tujuan menjaga hasil eksplorasi tidak dipakai untuk seleksi." actions={perms.manage && v.can_edit && <Button size="sm" variant="outline" leftIcon={<Pencil className="size-4" />} onClick={() => setDialog('edit')}>Ubah</Button>}>
          <Card>
            <KeyValue columns={2} items={[
              ['Judul', v.title],
              ['Tujuan', PURPOSE_LABEL[v.purpose] ?? v.purpose],
              ['Durasi', v.duration_minutes ? `${v.duration_minutes} menit` : 'Tidak dibatasi'],
              ['Dibuka', dateTime(v.available_from)],
              ['Ditutup', dateTime(v.available_until)],
              ['Periode PMB', periods.find((p) => p.value === v.period_id)?.label ?? 'Semua periode'],
              ['Versi induk', v.parent_version_id ? 'Salinan dari versi sebelumnya' : 'Versi pertama'],
              ['Catatan perubahan', v.change_note ?? '—'],
              ['Sesi peserta', Object.entries(v.sessions_by_status).map(([k, n]) => `${k}: ${n}`).join(' · ') || 'Belum ada'],
              ['Scoring', v.scoring_config?.engine ? String(v.scoring_config.engine) : 'Belum ada scoring engine'],
            ]} />
            {v.instructions && <p className="mt-4 whitespace-pre-line rounded-md bg-surface-hover p-3 text-sm text-ink-secondary">{v.instructions}</p>}
          </Card>
        </Section>
      )}

      {tab === 'dimensi' && <DimensionsPanel version={v} canEdit={perms.manage && v.can_edit} onChanged={refresh} />}

      {tab === 'pertanyaan' && (
        <QuestionsPanel
          version={v}
          rows={questionsQuery.data?.data ?? []}
          loading={questionsQuery.loading}
          canCreate={perms.questionCreate && v.can_edit}
          canManage={perms.questionManage && v.can_edit}
          canDelete={perms.questionDelete && v.can_edit}
          onChanged={refresh}
        />
      )}

      {tab === 'persetujuan' && <ApprovalPanel version={v} />}

      {tab === 'perbandingan' && <ComparePanel version={v} />}

      {tab === 'riwayat' && (
        <Card title="Riwayat perubahan" description="Setiap perubahan status dan konfigurasi dicatat di audit.">
          {v.history.length === 0 ? <EmptyState title="Belum ada riwayat" /> : (
            <ol className="flex flex-col gap-3 text-sm">
              {v.history.map((h, i) => (
                <li key={i} className="border-l-2 border-border pl-3">
                  <p className="font-medium text-ink-primary">{ACTION_LABEL[h.action] ?? h.action}{h.new && 'status' in h.new ? ` → ${String(h.new.status)}` : ''}</p>
                  <p className="text-xs text-ink-tertiary">{dateTime(h.at)} · {h.user ?? 'Sistem'}</p>
                  {h.reason && <p className="mt-1 text-ink-secondary">{h.reason}</p>}
                </li>
              ))}
            </ol>
          )}
        </Card>
      )}

      <FormDialog open={dialog === 'submit'} title="Ajukan untuk ditelaah" description="Versi dibekukan selama ditelaah. Penelaah tidak boleh sama dengan penyusun."
        fields={[{ name: 'note', label: 'Catatan untuk penelaah', type: 'textarea' }]}
        onClose={() => setDialog(null)}
        onSubmit={async (values) => { await action.run(() => talentApi.submit(id, String(values.note ?? '')), 'Diajukan untuk ditelaah.'); setDialog(null); refresh() }} />

      <FormDialog open={dialog === 'edit'} title="Ubah konfigurasi" initial={{ title: v.title, instructions: v.instructions ?? '', duration_minutes: v.duration_minutes ?? '', available_from: v.available_from ?? '', available_until: v.available_until ?? '', period_id: v.period_id ?? '' }}
        fields={[
          { name: 'title', label: 'Judul versi', required: true },
          { name: 'instructions', label: 'Petunjuk untuk peserta', type: 'textarea' },
          { name: 'duration_minutes', label: 'Durasi (menit)', type: 'number', min: '1', max: '600' },
          { name: 'available_from', label: 'Dibuka mulai', type: 'datetime' },
          { name: 'available_until', label: 'Ditutup pada', type: 'datetime' },
          { name: 'period_id', label: 'Periode PMB', type: 'select', options: periods },
        ]}
        onClose={() => setDialog(null)}
        onSubmit={async (values) => { await action.run(() => talentApi.updateVersion(id, { ...values, duration_minutes: values.duration_minutes || null, period_id: values.period_id || null }), 'Konfigurasi disimpan.'); setDialog(null); refresh() }} />

      <ReasonDialog open={dialog === 'return'} title="Kembalikan ke draft" description="Penyusun akan menerima versi ini kembali untuk diperbaiki." confirmLabel="Kembalikan" reasonLabel="Alasan" reasonName="comment" onClose={() => setDialog(null)} onConfirm={(vals) => decide('return', String(vals.comment ?? ''))} />
      <ReasonDialog open={dialog === 'reject'} title="Tolak versi" description="Versi ditolak dan tidak dapat diedit. Revisi dibuat sebagai salinan." confirmLabel="Tolak" reasonLabel="Alasan penolakan" reasonName="comment" onClose={() => setDialog(null)} onConfirm={(vals) => decide('reject', String(vals.comment ?? ''))} />
      <ReasonDialog open={dialog === 'archive'} title="Arsipkan versi" description="Versi diarsipkan. Peserta tidak lagi mengerjakannya; hasil yang ada tetap tersedia." confirmLabel="Arsipkan" reasonLabel="Alasan" reasonName="reason" onClose={() => setDialog(null)} onConfirm={(vals) => action.run(() => talentApi.archive(id, String(vals.reason ?? '')), 'Versi diarsipkan.').then(() => { setDialog(null); refresh() })} />

      <FormDialog open={dialog === 'clone'} title="Salin menjadi versi baru" description="Dimensi dan butir disalin ke draft baru. Versi ini tidak berubah."
        fields={[{ name: 'change_note', label: 'Catatan perubahan', type: 'textarea' }]}
        onClose={() => setDialog(null)}
        onSubmit={async (values) => { const created = await action.run(() => talentApi.cloneVersion(id, String(values.change_note ?? '')), 'Versi baru dibuat dari salinan.'); setDialog(null); if (created) navigate(to(PMB.admin.talentVersion, { id: created.id })) }} />
    </AdminPage>
  )
}

/** Dimensi versi: kode RIASEC, nama, dan interpretasi yang ditulis tim asesmen. */
function DimensionsPanel({ version, canEdit, onChanged }: { version: VersionDetail; canEdit: boolean; onChanged: () => void }) {
  const action = useAction()
  const [editing, setEditing] = useState<DimensionRow | 'new' | null>(null)
  const columns: DataTableColumn<DimensionRow>[] = [
    { header: 'Kode', cell: (d) => <span className="font-mono font-semibold">{d.code}</span> },
    { header: 'Nama', cell: (d) => d.name },
    { header: 'Butir', cell: (d) => <span className="tabular-nums">{d.items_count}</span> },
    { header: 'Interpretasi', cell: (d) => <span className="line-clamp-2 text-sm text-ink-secondary">{d.interpretation ?? 'Belum ditulis tim asesmen'}</span> },
    { header: '', cell: (d) => canEdit && <div className="flex gap-1"><Button size="sm" variant="ghost" aria-label={`Ubah ${d.code}`} onClick={() => setEditing(d)}><Pencil className="size-4" /></Button><Button size="sm" variant="ghost" aria-label={`Hapus ${d.code}`} onClick={() => window.confirm(`Hapus dimensi ${d.code}?`) && action.run(() => talentApi.deleteDimension(d.id), 'Dimensi dihapus.').then(onChanged)}><Trash2 className="size-4" /></Button></div> },
  ]

  return (
    <Section title="Dimensi" description="Untuk minat: R, I, A, S, E, C. Interpretasi hanya ditampilkan setelah disetujui tim asesmen." actions={canEdit && <Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Tambah dimensi</Button>}>
      <Feedback state={action} />
      <Card noPadding>
        <DataTable columns={columns} data={version.dimensions} rowKey={(d) => d.id} emptyMessage="Belum ada dimensi." />
      </Card>
      <FormDialog
        open={editing !== null}
        title={editing === 'new' || editing === null ? 'Tambah dimensi' : `Ubah dimensi ${editing.code}`}
        initial={editing && editing !== 'new' ? { code: editing.code, name: editing.name, description: editing.description ?? '', interpretation: editing.interpretation ?? '', sort_order: editing.sort_order } : { sort_order: version.dimensions.length }}
        fields={[
          { name: 'code', label: 'Kode', required: true, hint: 'Untuk minat: R, I, A, S, E, C' },
          { name: 'name', label: 'Nama', required: true },
          { name: 'description', label: 'Deskripsi', type: 'textarea' },
          { name: 'interpretation', label: 'Interpretasi', type: 'textarea', hint: 'Ditulis dan disetujui tim asesmen. Kosong = tidak ditampilkan.' },
          { name: 'sort_order', label: 'Urutan', type: 'number', min: '0' },
        ]}
        onClose={() => setEditing(null)}
        onSubmit={async (values) => {
          const body = { ...values, interpretation: values.interpretation || null, description: values.description || null }
          await action.run(() => (editing && editing !== 'new' ? talentApi.updateDimension(editing.id, body) : talentApi.createDimension(version.id, body)), 'Dimensi disimpan.')
          setEditing(null)
          onChanged()
        }}
      />
    </Section>
  )
}

/** Bank soal versi ini: pertanyaan Likert per dimensi. Pengubahan hanya saat versi masih draft dan butir belum dijawab. */
function QuestionsPanel({ version, rows, loading, canCreate, canManage, canDelete, onChanged }: {
  version: VersionDetail; rows: QuestionRow[]; loading: boolean; canCreate: boolean; canManage: boolean; canDelete: boolean; onChanged: () => void
}) {
  const action = useAction()
  const [editing, setEditing] = useState<QuestionRow | 'new' | null>(null)
  const [previewing, setPreviewing] = useState<string | null>(null)
  const preview = useLoad(() => (previewing ? talentApi.preview(previewing) : Promise.resolve(null)), [previewing])
  const dimensionOptions = version.dimensions.map((d) => ({ value: d.id, label: `${d.code} · ${d.name}` }))

  const columns: DataTableColumn<QuestionRow>[] = [
    { header: 'Kode', cell: (q) => <span className="font-mono text-xs">{q.code ?? '—'}</span> },
    { header: 'Pertanyaan', cell: (q) => <span className="line-clamp-2">{q.stem}</span> },
    { header: 'Dimensi', cell: (q) => <span className="font-mono">{q.dimension?.code ?? '—'}</span> },
    { header: 'Arah', cell: (q) => <span className="text-sm text-ink-secondary">{q.is_reversed ? 'Terbalik' : 'Biasa'}</span> },
    { header: 'Status', cell: (q) => <Badge variant={q.status === 'approved' ? 'success' : 'neutral'}>{q.status === 'approved' ? 'Disetujui' : 'Draft'}</Badge> },
    { header: 'Dipakai', cell: (q) => q.in_use ? <Badge variant="warning">Sudah dijawab</Badge> : <span className="text-ink-tertiary">—</span> },
    { header: '', cell: (q) => (
      <div className="flex gap-1">
        <Button size="sm" variant="ghost" aria-label="Pratinjau" onClick={() => setPreviewing(q.id)}><Eye className="size-4" /></Button>
        {canManage && q.editable && <Button size="sm" variant="ghost" aria-label="Ubah" onClick={() => setEditing(q)}><Pencil className="size-4" /></Button>}
        {canDelete && q.editable && <Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus pertanyaan ini?') && action.run(() => talentApi.deleteItem(q.id), 'Pertanyaan dihapus.').then(onChanged)}><Trash2 className="size-4" /></Button>}
      </div>
    ) },
  ]

  return (
    <Section title="Pertanyaan" description="Bank soal versi ini. Pertanyaan yang sudah dijawab peserta dikunci; untuk mengubahnya, salin versi."
      actions={canCreate && dimensionOptions.length > 0 && <Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Tambah pertanyaan</Button>}>
      <Feedback state={action} />
      {!version.can_edit && <Alert variant="info">Versi ini tidak dapat diubah. Pertanyaan hanya dapat dibaca.</Alert>}
      <Card noPadding>
        {loading && rows.length === 0 ? <p className="p-5 text-sm text-ink-secondary">Memuat...</p> : (
          <DataTable columns={columns} data={rows} rowKey={(q) => q.id} emptyMessage={version.dimensions.length === 0 ? 'Tambahkan dimensi terlebih dahulu.' : 'Belum ada pertanyaan.'} />
        )}
      </Card>
      <FormDialog
        open={editing !== null}
        title={editing === 'new' ? 'Tambah pertanyaan' : 'Ubah pertanyaan'}
        description="Kode kosong dibuat otomatis (Q-001, Q-002, ...). Kode harus unik dalam versi."
        initial={editing && editing !== 'new' ? { dimension_id: editing.dimension?.id ?? '', code: editing.code ?? '', stem: editing.stem, is_reversed: editing.is_reversed, sort_order: editing.sort_order } : { dimension_id: dimensionOptions[0]?.value ?? '', is_reversed: false }}
        fields={[
          { name: 'dimension_id', label: 'Dimensi', type: 'select', required: true, options: dimensionOptions },
          { name: 'code', label: 'Kode pertanyaan', hint: 'Huruf, angka, titik, garis bawah, tanda hubung.' },
          { name: 'stem', label: 'Teks pertanyaan', type: 'textarea', required: true, hint: 'Ditulis dalam kalimat yang dapat dipahami peserta. Minimal 5 karakter.' },
          { name: 'is_reversed', label: 'Pernyataan terbalik (nilai dibalik saat penilaian)', type: 'checkbox' },
          { name: 'sort_order', label: 'Urutan', type: 'number', min: '0' },
        ]}
        onClose={() => setEditing(null)}
        onSubmit={async (values) => {
          const body = { ...values, code: values.code || null }
          await action.run(() => (editing && editing !== 'new' ? talentApi.updateItem(editing.id, body) : talentApi.createItem(version.id, body)), 'Pertanyaan disimpan.')
          setEditing(null)
          onChanged()
        }}
      />
      <Modal open={previewing !== null} onClose={() => setPreviewing(null)} title="Pratinjau peserta">
        {preview.loading && <p className="text-sm text-ink-secondary">Memuat...</p>}
        {preview.data && (
          <div className="flex flex-col gap-4">
            <p className="text-base text-ink-primary">{preview.data.stem}</p>
            <ol className="grid gap-2 sm:grid-cols-5">
              {Array.from({ length: preview.data.scale.max - preview.data.scale.min + 1 }, (_, i) => preview.data!.scale.min + i).map((n) => (
                <li key={n} className="rounded-lg border border-border px-3 py-3 text-center text-sm"><span className="block text-lg font-semibold tabular-nums">{n}</span>{LIKERT_LABEL[n] ?? n}</li>
              ))}
            </ol>
            <p className="text-xs text-ink-tertiary">Peserta tidak melihat dimensi atau arah butir.</p>
          </div>
        )}
      </Modal>
    </Section>
  )
}

/** Status persetujuan dan langkah approval engine. Keputusan dilakukan dari tombol di header. */
function ApprovalPanel({ version }: { version: VersionDetail }) {
  if (!version.approval) {
    return <Card><EmptyState icon={ShieldCheck} title="Belum diajukan" description="Ajukan versi untuk ditelaah. Persetujuan dilakukan dua pihak: penelaah, lalu pejabat pengesah." /></Card>
  }

  return (
    <Card title="Persetujuan" description={`Status pengajuan: ${version.approval.status}. Penyusun tidak boleh menyetujui pengajuannya sendiri.`}>
      <ol className="flex flex-col gap-3">
        {version.approval.steps.map((step) => (
          <li key={step.sequence} className="flex items-center justify-between rounded-md border border-border px-4 py-3 text-sm">
            <span><span className="font-medium text-ink-primary">{step.sequence}. {step.name}</span>{step.acted_at && <span className="block text-xs text-ink-tertiary">{dateTime(step.acted_at)}</span>}</span>
            <Badge variant={step.status === 'approved' ? 'success' : step.status === 'in_review' ? 'warning' : 'neutral'}>{step.status}</Badge>
          </li>
        ))}
      </ol>
      {version.approval.can_decide && <p className="mt-4 text-sm text-ink-secondary">Langkah aktif: <span className="font-medium">{version.approval.current_step}</span>. Keputusan dibuat dari tombol di bagian atas halaman.</p>}
    </Card>
  )
}

/** Perbedaan dimensi dan butir antara versi ini dan versi lain dari jenis yang sama. */
function ComparePanel({ version }: { version: VersionDetail }) {
  const [other, setOther] = useState('')
  const versions = useLoad(() => talentApi.versions(version.type.id), [version.type.id])
  const diff = useLoad(() => (other ? talentApi.diff(version.id, other) : Promise.resolve(null)), [other, version.id])
  const options = (versions.data ?? []).filter((x) => x.id !== version.id).map((x) => ({ value: x.id, label: `Versi ${x.version_no} · ${x.title} (${x.status})` }))

  return (
    <Card title="Perbandingan versi" description="Butir dibandingkan per kode. Butir yang kodenya sama tetapi teksnya berbeda ditandai berubah.">
      <div className="mb-4 max-w-md">
        <label className="flex flex-col gap-1.5 text-sm"><span className="font-medium text-ink-primary">Bandingkan dengan</span>
          <select className="rounded-md border border-border bg-transparent px-3 py-2 text-sm" value={other} onChange={(e) => setOther(e.target.value)}>
            <option value="">Pilih versi</option>
            {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
          </select>
        </label>
      </div>
      {!other && <EmptyState title="Pilih versi pembanding" />}
      {diff.loading && <p className="text-sm text-ink-secondary">Membandingkan...</p>}
      {diff.error && <Alert variant="danger">{diff.error.message}</Alert>}
      {diff.data && (
        <div className="grid gap-5 lg:grid-cols-2">
          <div>
            <h3 className="mb-2 text-sm font-semibold text-ink-primary">Dimensi</h3>
            <ul className="flex flex-col gap-1 text-sm">
              <li>Ditambahkan: {diff.data.dimensions.added.join(', ') || '—'}</li>
              <li>Dihapus: {diff.data.dimensions.removed.join(', ') || '—'}</li>
              <li>Berubah: {diff.data.dimensions.changed.map((c) => `${c.code} (${c.fields.join(', ')})`).join('; ') || '—'}</li>
            </ul>
          </div>
          <div>
            <h3 className="mb-2 text-sm font-semibold text-ink-primary">Butir</h3>
            <ul className="flex flex-col gap-2 text-sm">
              {diff.data.items.added.map((i) => <li key={`a-${i.key}`}><Badge variant="success">Baru</Badge> <span className="ml-1">{i.stem}</span></li>)}
              {diff.data.items.removed.map((i) => <li key={`r-${i.key}`}><Badge variant="danger">Dihapus</Badge> <span className="ml-1">{i.stem}</span></li>)}
              {diff.data.items.changed.map((i) => <li key={`c-${i.key}`}><Badge variant="warning">Berubah</Badge> <span className="ml-1">{i.from}<span className="text-ink-tertiary"> → </span>{i.to}</span></li>)}
              {diff.data.items.added.length + diff.data.items.removed.length + diff.data.items.changed.length === 0 && <li className="text-ink-tertiary">Tidak ada perbedaan butir.</li>}
            </ul>
          </div>
        </div>
      )}
    </Card>
  )
}

