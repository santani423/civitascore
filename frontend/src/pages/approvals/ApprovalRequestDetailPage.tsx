import { useCallback, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Check, Search, Send, X } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { approvalRequestService, approvalRequestStepService } from '@/services/approvalService'
import { userService } from '@/services/userManagementService'
import type { NormalizedApiError } from '@/services/api'
import type { ApprovalHistoryEvent, ApprovalRequest, ApprovalRequestStatus } from '@/types/approval'
import type { UserSummary } from '@/types/userManagement'
import { ROUTES } from '@/constants/routes'
import { formatRelativeTime, humanizeSlug } from '@/utils/formatters'

const STATUS_LABEL: Record<ApprovalRequestStatus, string> = {
  submitted: 'Diajukan',
  in_progress: 'Diproses',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}

const STATUS_VARIANT: Record<ApprovalRequestStatus, BadgeVariant> = {
  submitted: 'info',
  in_progress: 'warning',
  approved: 'success',
  rejected: 'danger',
}

const EVENT_LABEL: Record<ApprovalHistoryEvent, string> = {
  submitted: 'Diajukan',
  step_advanced: 'Lanjut ke langkah berikutnya',
  approved: 'Disetujui',
  rejected: 'Ditolak',
  returned_to_previous_step: 'Dikembalikan ke langkah sebelumnya',
}

export function ApprovalRequestDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getRequest = useCallback(() => approvalRequestService.show(id ?? ''), [id])
  const { data: request, isLoading, error, setData } = useFetch(getRequest)

  const [activeModal, setActiveModal] = useState<'approve' | 'reject' | 'delegate' | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)

  if (!id) {
    return <Alert variant="danger">Pengajuan tidak ditemukan.</Alert>
  }

  const handleActionDone = (updated: ApprovalRequest) => {
    setData(updated)
    setActiveModal(null)
  }

  const canAct = request && ['submitted', 'in_progress'].includes(request.status) && request.current_step_id

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Pengajuan"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Persetujuan', path: ROUTES.persetujuan },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.persetujuan)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {actionError && (
        <Alert variant="danger" onDismiss={() => setActionError(null)}>
          {actionError}
        </Alert>
      )}

      {request && (
        <>
          <Card title={humanizeSlug(request.requestable_type.split('\\').pop() ?? request.requestable_type)}>
            <div className="flex flex-wrap items-center gap-4 text-sm">
              <Badge variant={STATUS_VARIANT[request.status]}>{STATUS_LABEL[request.status]}</Badge>
              <span className="text-ink-secondary">
                Diajukan {request.submitted_at ? formatRelativeTime(request.submitted_at) : '-'}
              </span>
              {request.completed_at && (
                <span className="text-ink-secondary">Selesai {formatRelativeTime(request.completed_at)}</span>
              )}
            </div>
            {request.notes && <p className="mt-3 text-sm text-ink-secondary">{request.notes}</p>}

            {canAct && (
              <div className="mt-5 flex flex-wrap gap-2 border-t border-border pt-4">
                <Button leftIcon={<Check className="size-4" />} onClick={() => setActiveModal('approve')}>
                  Setujui
                </Button>
                <Button variant="danger" leftIcon={<X className="size-4" />} onClick={() => setActiveModal('reject')}>
                  Tolak
                </Button>
                <Button variant="outline" leftIcon={<Send className="size-4" />} onClick={() => setActiveModal('delegate')}>
                  Delegasikan
                </Button>
              </div>
            )}
          </Card>

          <Card title="Riwayat" noPadding>
            <ul className="divide-y divide-border">
              {request.histories.length === 0 && <li className="px-4 py-3 text-sm text-ink-tertiary">Belum ada riwayat.</li>}
              {request.histories.map((history) => (
                <li key={history.id} className="px-4 py-3">
                  <p className="text-sm font-medium text-ink-primary">{EVENT_LABEL[history.event]}</p>
                  {history.description && <p className="text-sm text-ink-secondary">{history.description}</p>}
                  <p className="mt-0.5 text-xs text-ink-tertiary">{formatRelativeTime(history.created_at)}</p>
                </li>
              ))}
            </ul>
          </Card>
        </>
      )}

      {request && activeModal === 'approve' && (
        <ApproveModal
          request={request}
          onClose={() => setActiveModal(null)}
          onDone={handleActionDone}
          onError={setActionError}
        />
      )}
      {request && activeModal === 'reject' && (
        <RejectModal request={request} onClose={() => setActiveModal(null)} onDone={handleActionDone} onError={setActionError} />
      )}
      {request && activeModal === 'delegate' && (
        <DelegateModal
          request={request}
          onClose={() => setActiveModal(null)}
          onDone={handleActionDone}
          onError={setActionError}
        />
      )}
    </div>
  )
}

function ApproveModal({
  request,
  onClose,
  onDone,
  onError,
}: {
  request: ApprovalRequest
  onClose: () => void
  onDone: (updated: ApprovalRequest) => void
  onError: (message: string) => void
}) {
  const [comment, setComment] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handleSubmit = async () => {
    setIsSubmitting(true)
    try {
      const updated = await approvalRequestStepService.approve(request.current_step_id!, comment || undefined)
      onDone(updated)
    } catch (error) {
      onError((error as NormalizedApiError).message ?? 'Gagal menyetujui pengajuan.')
      onClose()
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Modal open onClose={onClose} title="Setujui Pengajuan">
      <Input label="Komentar (opsional)" value={comment} onChange={(event) => setComment(event.target.value)} />
      <div className="mt-5 flex justify-end gap-2">
        <Button variant="outline" onClick={onClose}>
          Batal
        </Button>
        <Button isLoading={isSubmitting} onClick={handleSubmit}>
          Setujui
        </Button>
      </div>
    </Modal>
  )
}

function RejectModal({
  request,
  onClose,
  onDone,
  onError,
}: {
  request: ApprovalRequest
  onClose: () => void
  onDone: (updated: ApprovalRequest) => void
  onError: (message: string) => void
}) {
  const [comment, setComment] = useState('')
  const [validationError, setValidationError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handleSubmit = async () => {
    if (!comment.trim()) {
      setValidationError('Komentar wajib diisi untuk penolakan.')
      return
    }
    setValidationError(null)
    setIsSubmitting(true)

    try {
      const updated = await approvalRequestStepService.reject(request.current_step_id!, comment)
      onDone(updated)
    } catch (error) {
      onError((error as NormalizedApiError).message ?? 'Gagal menolak pengajuan.')
      onClose()
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Modal open onClose={onClose} title="Tolak Pengajuan">
      {validationError && <Alert variant="danger">{validationError}</Alert>}
      <Input label="Alasan Penolakan" value={comment} onChange={(event) => setComment(event.target.value)} />
      <div className="mt-5 flex justify-end gap-2">
        <Button variant="outline" onClick={onClose}>
          Batal
        </Button>
        <Button variant="danger" isLoading={isSubmitting} onClick={handleSubmit}>
          Tolak
        </Button>
      </div>
    </Modal>
  )
}

function DelegateModal({
  request,
  onClose,
  onDone,
  onError,
}: {
  request: ApprovalRequest
  onClose: () => void
  onDone: (updated: ApprovalRequest) => void
  onError: (message: string) => void
}) {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<UserSummary[]>([])
  const [selectedUser, setSelectedUser] = useState<UserSummary | null>(null)
  const [comment, setComment] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)

  const handleSearch = (value: string) => {
    setQuery(value)
    setSelectedUser(null)
    if (value.trim().length < 2) {
      setResults([])
      return
    }
    userService.index({ search: value, per_page: 10 }).then((result) => setResults(result.data))
  }

  const handleSubmit = async () => {
    if (!selectedUser) return
    setIsSubmitting(true)

    try {
      const updated = await approvalRequestStepService.delegate(request.current_step_id!, selectedUser.id, comment || undefined)
      onDone(updated)
    } catch (error) {
      onError((error as NormalizedApiError).message ?? 'Gagal mendelegasikan pengajuan.')
      onClose()
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Modal open onClose={onClose} title="Delegasikan Pengajuan">
      <Input
        label="Cari Pengguna Tujuan"
        leftIcon={<Search className="size-4" />}
        value={selectedUser ? selectedUser.name : query}
        onChange={(event) => handleSearch(event.target.value)}
      />
      {results.length > 0 && !selectedUser && (
        <div className="mt-1 max-h-40 overflow-y-auto rounded-lg border border-border">
          {results.map((user) => (
            <button
              key={user.id}
              type="button"
              onClick={() => {
                setSelectedUser(user)
                setResults([])
              }}
              className="flex w-full flex-col px-3 py-2 text-left text-sm hover:bg-surface-hover"
            >
              <span className="font-medium text-ink-primary">{user.name}</span>
              <span className="text-xs text-ink-tertiary">{user.email}</span>
            </button>
          ))}
        </div>
      )}

      <div className="mt-3">
        <Input label="Komentar (opsional)" value={comment} onChange={(event) => setComment(event.target.value)} />
      </div>

      <div className="mt-5 flex justify-end gap-2">
        <Button variant="outline" onClick={onClose}>
          Batal
        </Button>
        <Button isLoading={isSubmitting} disabled={!selectedUser} onClick={handleSubmit}>
          Delegasikan
        </Button>
      </div>
    </Modal>
  )
}
