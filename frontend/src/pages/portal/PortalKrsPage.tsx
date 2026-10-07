import { useCallback, useMemo, useState } from 'react'
import { CheckCircle2, ChevronDown, Clock, Download, History, Plus, Search, Trash2, Users, XCircle } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Modal } from '@/components/ui/Modal'
import { Input } from '@/components/ui/Input'
import { PortalEmpty, PortalError, PortalLoading, ProgressBar } from '@/components/portal/PortalState'
import { KrsStatusBadge } from '@/components/portal/badges'
import { useFetch } from '@/hooks/useFetch'
import { downloadBlob, studentPortalService } from '@/services/studentPortalService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'
import type { KrsOffering, KrsOfferingClass, KrsOverview, KrsPlanItem, PortalSchedule } from '@/types/studentPortal'
import { formatDate, formatDateTime } from '@/utils/portalFormat'
import { cn } from '@/utils/cn'

function scheduleText(schedules: PortalSchedule[]): string {
  if (schedules.length === 0) return 'Jadwal belum ditetapkan'

  return schedules.map((s) => `${s.day_label} ${s.start_time}–${s.end_time}${s.room ? ` (${s.room})` : ''}`).join('; ')
}

function PlanItemRow({ item, canRemove, busy, onRemove }: { item: KrsPlanItem; canRemove: boolean; busy: boolean; onRemove: () => void }) {
  const { class_section: cs } = item

  return (
    <li className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
      <div className="min-w-0">
        <div className="flex flex-wrap items-center gap-2">
          <p className="font-medium text-ink-primary">{cs.course.name}</p>
          <Badge variant={item.status === 'enrolled' ? 'success' : item.status === 'pending' ? 'info' : 'neutral'}>{item.status_label}</Badge>
        </div>
        <p className="text-xs text-ink-secondary">
          {cs.course.code} · Kelas {cs.class_code} · {cs.course.credits} SKS · {cs.lecturer?.name ?? 'Dosen belum ditetapkan'}
        </p>
        <p className="text-xs text-ink-tertiary">{scheduleText(cs.schedules)}</p>
      </div>
      {canRemove && item.status === 'draft' && (
        <Button size="sm" variant="ghost" className="self-start text-danger sm:self-center" leftIcon={<Trash2 className="size-3.5" />} isLoading={busy} onClick={onRemove}>
          Hapus
        </Button>
      )}
    </li>
  )
}

function OfferingCard({
  offering,
  canEdit,
  busyClassId,
  onAdd,
}: {
  offering: KrsOffering
  canEdit: boolean
  busyClassId: string | null
  onAdd: (classSection: KrsOfferingClass) => void
}) {
  const [open, setOpen] = useState(false)
  const blocked = offering.blockers.length > 0

  return (
    <li className="rounded-xl border border-border bg-surface">
      <button type="button" onClick={() => setOpen((value) => !value)} className="flex w-full items-start justify-between gap-3 p-4 text-left" aria-expanded={open}>
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <p className="font-medium text-ink-primary">{offering.course.name}</p>
            {offering.is_selected && <Badge variant="success">Dipilih</Badge>}
            {offering.is_retake && <Badge variant="warning">Mengulang (nilai {offering.best_grade})</Badge>}
            {blocked && !offering.is_selected && <Badge variant="danger">Tidak dapat diambil</Badge>}
          </div>
          <p className="text-xs text-ink-secondary">
            {offering.course.code} · {offering.course.credits} SKS · Semester {offering.course.semester_level} · {offering.classes.length} kelas
          </p>
        </div>
        <ChevronDown className={cn('mt-1 size-4 shrink-0 text-ink-tertiary transition-transform', open && 'rotate-180')} />
      </button>

      {open && (
        <div className="flex flex-col gap-3 border-t border-border p-4">
          {offering.prerequisites.length > 0 && (
            <div className="text-xs">
              <p className="mb-1 font-medium text-ink-secondary">Prasyarat</p>
              <ul className="flex flex-wrap gap-1.5">
                {offering.prerequisites.map((prerequisite) => (
                  <li key={prerequisite.course_id}>
                    <Badge variant={prerequisite.satisfied ? 'success' : 'danger'}>
                      {prerequisite.satisfied ? <CheckCircle2 className="size-3" /> : <XCircle className="size-3" />}
                      {prerequisite.code} {prerequisite.name} (min. {prerequisite.min_grade})
                    </Badge>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {blocked && (
            <Alert variant="warning">{offering.blockers.join(' ')}</Alert>
          )}

          <ul className="flex flex-col gap-2">
            {offering.classes.map((classSection) => {
              const conflict = classSection.conflicts.length > 0
              const disabled = !canEdit || blocked || offering.is_selected || classSection.is_full || conflict

              return (
                <li
                  key={classSection.id}
                  className={cn(
                    'flex flex-col gap-2 rounded-lg border p-3 sm:flex-row sm:items-center sm:justify-between',
                    classSection.is_selected ? 'border-primary bg-primary-50/40 dark:bg-primary-950/40' : 'border-border',
                  )}
                >
                  <div className="min-w-0 text-sm">
                    <p className="font-medium text-ink-primary">Kelas {classSection.class_code}</p>
                    <p className="text-xs text-ink-secondary">{classSection.lecturer?.name ?? 'Dosen belum ditetapkan'}</p>
                    <p className="text-xs text-ink-tertiary">{scheduleText(classSection.schedules)}</p>
                    <p className={cn('mt-1 inline-flex items-center gap-1 text-xs', classSection.is_full ? 'text-danger' : 'text-ink-secondary')}>
                      <Users className="size-3" />
                      {classSection.is_full ? 'Kelas penuh' : `Sisa ${classSection.seats_left} dari ${classSection.capacity} kursi`}
                    </p>
                    {conflict && <p className="mt-1 text-xs text-danger">Bentrok dengan: {classSection.conflicts.join(', ')}</p>}
                  </div>
                  {classSection.is_selected ? (
                    <Badge variant="success" className="self-start sm:self-center">Kelas dipilih</Badge>
                  ) : (
                    <Button
                      size="sm"
                      variant="outline"
                      leftIcon={<Plus className="size-3.5" />}
                      disabled={disabled}
                      isLoading={busyClassId === classSection.id}
                      onClick={() => onAdd(classSection)}
                      className="self-start sm:self-center"
                    >
                      Pilih Kelas
                    </Button>
                  )}
                </li>
              )
            })}
          </ul>
        </div>
      )}
    </li>
  )
}

export function PortalKrsPage() {
  const fetchKrs = useCallback(() => studentPortalService.krs(), [])
  const { data: krs, setData: setKrs, isLoading, error, refetch } = useFetch(fetchKrs)

  const fetchOfferings = useCallback(() => studentPortalService.krsOfferings(), [])
  const { data: offerings, isLoading: offeringsLoading, refetch: refetchOfferings } = useFetch(fetchOfferings)

  const fetchHistory = useCallback(() => studentPortalService.krsHistory(), [])
  const { data: history } = useFetch(fetchHistory)

  const [message, setMessage] = useState<{ variant: 'success' | 'danger'; text: string } | null>(null)
  const [busyId, setBusyId] = useState<string | null>(null)
  const [confirmSubmit, setConfirmSubmit] = useState(false)
  const [search, setSearch] = useState('')
  const [showHistory, setShowHistory] = useState(false)
  const [downloading, setDownloading] = useState(false)

  const filteredOfferings = useMemo(() => {
    const term = search.trim().toLowerCase()
    const courses = offerings?.courses ?? []

    return term ? courses.filter((offering) => `${offering.course.code} ${offering.course.name}`.toLowerCase().includes(term)) : courses
  }, [offerings, search])

  const run = async (id: string, action: () => Promise<{ data: KrsOverview; message: string | null }>) => {
    setBusyId(id)
    setMessage(null)

    try {
      const result = await action()
      setKrs(result.data)
      setMessage({ variant: 'success', text: result.message ?? 'Berhasil.' })
      refetchOfferings()
    } catch (err) {
      setMessage({ variant: 'danger', text: (err as NormalizedApiError).message })
    } finally {
      setBusyId(null)
    }
  }

  const handleDownload = async () => {
    if (!krs?.term) return
    setDownloading(true)
    setMessage(null)

    try {
      await downloadBlob(`/student/documents/krs?academic_term_id=${krs.term.id}`, `KRS-${krs.term.label.replace(/\W+/g, '-')}.pdf`)
    } catch (err) {
      setMessage({ variant: 'danger', text: (err as NormalizedApiError).message })
    } finally {
      setDownloading(false)
    }
  }

  if (isLoading && !krs) return <PortalLoading cards={3} rows={5} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!krs) return null

  const usage = krs.max_credits ? (krs.total_credits / krs.max_credits) * 100 : 0

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Kartu Rencana Studi (KRS)"
        description={krs.term ? `Semester ${krs.term.label}` : 'Belum ada semester aktif'}
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'KRS' }]}
        actions={
          krs.items.length > 0 ? (
            <Button variant="outline" size="sm" leftIcon={<Download className="size-3.5" />} isLoading={downloading} onClick={handleDownload}>
              Cetak KRS
            </Button>
          ) : undefined
        }
      />

      {message && (
        <Alert variant={message.variant} onDismiss={() => setMessage(null)}>
          {message.text}
        </Alert>
      )}

      {krs.status === 'rejected' && krs.submission?.decision_note && (
        <Alert variant="danger" title="KRS ditolak dosen wali">
          {krs.submission.decision_note} — perbaiki KRS Anda lalu ajukan kembali.
        </Alert>
      )}

      {krs.notices.length > 0 && krs.status !== 'rejected' && (
        <Alert variant={krs.status === 'approved' ? 'success' : 'info'}>{krs.notices.join(' ')}</Alert>
      )}

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        <Card>
          <p className="text-xs text-ink-tertiary">Status KRS</p>
          <div className="mt-1.5">
            <KrsStatusBadge status={krs.status} label={krs.status_label} />
          </div>
          {krs.submission?.submitted_at && (
            <p className="mt-2 text-xs text-ink-secondary">Diajukan {formatDateTime(krs.submission.submitted_at)}</p>
          )}
          {krs.submission?.decided_at && (
            <p className="text-xs text-ink-secondary">
              Diputuskan {formatDateTime(krs.submission.decided_at)}
              {krs.submission.decided_by ? ` oleh ${krs.submission.decided_by}` : ''}
            </p>
          )}
        </Card>
        <Card>
          <p className="text-xs text-ink-tertiary">Beban SKS</p>
          <p className="mt-1 text-2xl font-semibold text-ink-primary">
            {krs.total_credits}
            <span className="text-sm font-normal text-ink-secondary"> / {krs.max_credits ?? '-'} SKS</span>
          </p>
          <div className="mt-2">
            <ProgressBar value={usage} tone={usage > 100 ? 'danger' : usage >= 90 ? 'warning' : 'primary'} />
          </div>
          <p className="mt-1.5 text-xs text-ink-tertiary">Batas maksimum dihitung dari IP semester sebelumnya.</p>
        </Card>
        <Card>
          <p className="text-xs text-ink-tertiary">Periode KRS</p>
          <p className="mt-1 text-sm font-medium text-ink-primary">
            {krs.term?.krs_start_date && krs.term.krs_end_date
              ? `${formatDate(krs.term.krs_start_date)} – ${formatDate(krs.term.krs_end_date)}`
              : 'Belum ditetapkan'}
          </p>
          <Badge variant={krs.term?.is_krs_open ? 'success' : 'neutral'} className="mt-1.5">
            {krs.term?.is_krs_open ? 'Dibuka' : 'Ditutup'}
          </Badge>
          <p className="mt-2 text-xs text-ink-secondary">Dosen wali: {krs.academic_advisor?.name ?? 'belum ditetapkan'}</p>
        </Card>
      </div>

      <Card
        title="Mata Kuliah Dipilih"
        description={`${krs.items.length} mata kuliah`}
        actions={
          <div className="flex flex-wrap gap-2">
            {krs.can_cancel_submission && (
              <Button size="sm" variant="outline" isLoading={busyId === 'cancel'} onClick={() => run('cancel', studentPortalService.cancelKrs)}>
                Tarik Pengajuan
              </Button>
            )}
            {krs.can_submit && (
              <Button size="sm" onClick={() => setConfirmSubmit(true)}>
                Ajukan KRS
              </Button>
            )}
          </div>
        }
      >
        {krs.items.length === 0 ? (
          <PortalEmpty
            icon={Clock}
            title="Belum ada KRS untuk semester ini."
            description={krs.can_edit ? 'Pilih kelas dari daftar mata kuliah yang ditawarkan di bawah.' : undefined}
          />
        ) : (
          <ul className="flex flex-col divide-y divide-border">
            {krs.items.map((item) => (
              <PlanItemRow
                key={item.id}
                item={item}
                canRemove={krs.can_edit}
                busy={busyId === item.id}
                onRemove={() => run(item.id, () => studentPortalService.removeKrsItem(item.id))}
              />
            ))}
          </ul>
        )}
      </Card>

      {krs.can_edit && (
        <Card title="Mata Kuliah Ditawarkan" description="Kelas program studi Anda pada semester ini. Validasi prasyarat, kuota, bentrok jadwal, dan batas SKS dilakukan otomatis.">
          <div className="mb-4 max-w-sm">
            <Input
              placeholder="Cari kode atau nama mata kuliah"
              value={search}
              onChange={(event) => setSearch(event.target.value)}
              leftIcon={<Search className="size-4" />}
            />
          </div>
          {offeringsLoading && !offerings ? (
            <PortalLoading cards={0} rows={4} />
          ) : filteredOfferings.length === 0 ? (
            <PortalEmpty title="Tidak ada mata kuliah yang ditawarkan." description={search ? 'Coba kata kunci lain.' : undefined} />
          ) : (
            <ul className="flex flex-col gap-3">
              {filteredOfferings.map((offering) => (
                <OfferingCard
                  key={offering.course.id}
                  offering={offering}
                  canEdit={krs.can_edit}
                  busyClassId={busyId}
                  onAdd={(classSection) => run(classSection.id, () => studentPortalService.addKrsItem(classSection.id))}
                />
              ))}
            </ul>
          )}
        </Card>
      )}

      <Card
        title="Riwayat KRS"
        actions={
          <Button size="sm" variant="ghost" leftIcon={<History className="size-3.5" />} onClick={() => setShowHistory((value) => !value)}>
            {showHistory ? 'Sembunyikan' : 'Tampilkan'}
          </Button>
        }
      >
        {!showHistory ? (
          <p className="text-sm text-ink-secondary">KRS semester-semester sebelumnya beserta status persetujuannya.</p>
        ) : !history || history.length === 0 ? (
          <p className="text-sm text-ink-secondary">Belum ada riwayat KRS.</p>
        ) : (
          <ul className="flex flex-col divide-y divide-border">
            {history.map((entry) => (
              <li key={entry.term?.id ?? entry.status} className="flex flex-col gap-1 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p className="font-medium text-ink-primary">{entry.term?.label ?? '-'}</p>
                  <p className="text-xs text-ink-secondary">
                    {entry.course_count} mata kuliah · {entry.total_credits} SKS
                  </p>
                </div>
                <KrsStatusBadge status={entry.status} label={entry.status_label} />
              </li>
            ))}
          </ul>
        )}
      </Card>

      <Modal open={confirmSubmit} onClose={() => setConfirmSubmit(false)} title="Ajukan KRS ke dosen wali?">
        <div className="flex flex-col gap-4 text-sm">
          <p className="text-ink-secondary">
            Anda akan mengajukan <span className="font-medium text-ink-primary">{krs.items.length} mata kuliah ({krs.total_credits} SKS)</span>. Setelah diajukan,
            KRS tidak dapat diubah kecuali Anda menarik kembali pengajuan selama periode KRS masih dibuka.
          </p>
          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setConfirmSubmit(false)}>
              Batal
            </Button>
            <Button
              isLoading={busyId === 'submit'}
              onClick={async () => {
                await run('submit', studentPortalService.submitKrs)
                setConfirmSubmit(false)
              }}
            >
              Ya, Ajukan
            </Button>
          </div>
        </div>
      </Modal>
    </div>
  )
}
