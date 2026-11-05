import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { REVIEW_STATUS_LABEL, SESSION_STATUS_LABEL, VERSION_STATUS_LABEL, type ReviewStatus, type SessionStatus, type VersionStatus } from '@/pmb/api/talent'

const VERSION_VARIANT: Record<VersionStatus, BadgeVariant> = {
  draft: 'neutral', in_review: 'info', approved: 'primary', rejected: 'danger', published: 'success', archived: 'neutral',
}

const SESSION_VARIANT: Record<SessionStatus, BadgeVariant> = {
  in_progress: 'warning', submitted: 'info', expired: 'neutral', published: 'success', invalidated: 'danger',
}

const REVIEW_VARIANT: Record<ReviewStatus, BadgeVariant> = { none: 'neutral', flagged: 'warning', reviewed: 'success' }

export function VersionBadge({ status }: { status: VersionStatus }) {
  return <Badge variant={VERSION_VARIANT[status]}>{VERSION_STATUS_LABEL[status]}</Badge>
}

export function SessionBadge({ status }: { status: SessionStatus }) {
  return <Badge variant={SESSION_VARIANT[status]}>{SESSION_STATUS_LABEL[status]}</Badge>
}

export function ReviewBadge({ status }: { status: ReviewStatus }) {
  return <Badge variant={REVIEW_VARIANT[status]}>{REVIEW_STATUS_LABEL[status]}</Badge>
}

