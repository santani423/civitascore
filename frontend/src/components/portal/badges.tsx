import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import type { AssignmentStatus, KrsStatus, StudentRequestStatus, StudentStatusInfo } from '@/types/studentPortal'

const KRS_VARIANT: Record<KrsStatus, BadgeVariant> = {
  no_active_term: 'neutral',
  not_started: 'warning',
  draft: 'neutral',
  submitted: 'info',
  approved: 'success',
  rejected: 'danger',
}

export function KrsStatusBadge({ status, label }: { status: KrsStatus; label: string }) {
  return <Badge variant={KRS_VARIANT[status]}>{label}</Badge>
}

const ASSIGNMENT_VARIANT: Record<AssignmentStatus, BadgeVariant> = {
  not_submitted: 'warning',
  submitted: 'info',
  late: 'danger',
  graded: 'success',
  missed: 'danger',
}

export function AssignmentStatusBadge({ status, label }: { status: AssignmentStatus; label: string }) {
  return <Badge variant={ASSIGNMENT_VARIANT[status]}>{label}</Badge>
}

const REQUEST_VARIANT: Record<StudentRequestStatus, BadgeVariant> = {
  draft: 'neutral',
  submitted: 'info',
  approved: 'success',
  rejected: 'danger',
  cancelled: 'neutral',
}

export function RequestStatusBadge({ status, label }: { status: StudentRequestStatus; label: string }) {
  return <Badge variant={REQUEST_VARIANT[status]}>{label}</Badge>
}

const STUDENT_STATUS_VARIANT: Record<StudentStatusInfo['value'], BadgeVariant> = {
  active: 'success',
  leave: 'warning',
  graduated: 'info',
  inactive: 'neutral',
  dropped_out: 'danger',
  resigned: 'danger',
}

export function StudentStatusBadge({ status }: { status: StudentStatusInfo }) {
  return <Badge variant={STUDENT_STATUS_VARIANT[status.value]}>{status.label}</Badge>
}

/** Nilai huruf: A/AB hijau, B/BC biru, C kuning, D/E merah, kosong = belum dinilai. */
export function GradeBadge({ grade }: { grade: string | null }) {
  if (!grade) return <Badge variant="neutral">Belum dinilai</Badge>

  const variant: BadgeVariant = ['A', 'AB'].includes(grade)
    ? 'success'
    : ['B', 'BC'].includes(grade)
      ? 'info'
      : grade === 'C'
        ? 'warning'
        : 'danger'

  return <Badge variant={variant}>{grade}</Badge>
}
