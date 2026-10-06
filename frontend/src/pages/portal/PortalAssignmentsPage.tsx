import { useCallback, useState } from 'react'
import { Link } from 'react-router-dom'
import { NotebookPen } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { AssignmentStatusBadge } from '@/components/portal/badges'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import type { AssignmentStatus } from '@/types/studentPortal'
import { formatCountdown, formatDateTime } from '@/utils/portalFormat'
import { cn } from '@/utils/cn'

const FILTERS: { value: 'all' | AssignmentStatus; label: string }[] = [
  { value: 'all', label: 'Semua' },
  { value: 'not_submitted', label: 'Belum dikerjakan' },
  { value: 'submitted', label: 'Dikumpulkan' },
  { value: 'late', label: 'Terlambat' },
  { value: 'graded', label: 'Dinilai' },
  { value: 'missed', label: 'Tidak dikumpulkan' },
]

export function PortalAssignmentsPage() {
  const [filter, setFilter] = useState<'all' | AssignmentStatus>('all')
  const fetchAssignments = useCallback(() => studentPortalService.assignments(filter === 'all' ? undefined : filter), [filter])
  const { data, isLoading, error, refetch } = useFetch(fetchAssignments)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Tugas"
        description="Tugas dari seluruh mata kuliah yang Anda ikuti semester ini."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Perkuliahan' }, { label: 'Tugas' }]}
      />

      <div className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" role="tablist">
        {FILTERS.map((option) => (
          <button
            key={option.value}
            type="button"
            role="tab"
            aria-selected={filter === option.value}
            onClick={() => setFilter(option.value)}
            className={cn(
              'shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
              filter === option.value ? 'border-primary bg-primary text-white' : 'border-border text-ink-secondary hover:bg-surface-hover',
            )}
          >
            {option.label}
            {data?.counts && option.value !== 'all' && data.counts[option.value] > 0 ? ` (${data.counts[option.value]})` : ''}
          </button>
        ))}
      </div>

      {isLoading && !data ? (
        <PortalLoading cards={0} rows={5} />
      ) : error ? (
        <PortalError message={error} onRetry={refetch} />
      ) : !data || data.assignments.length === 0 ? (
        <PortalEmpty icon={NotebookPen} title={filter === 'all' ? 'Belum ada tugas.' : 'Tidak ada tugas dengan status ini.'} />
      ) : (
        <div className="flex flex-col gap-3">
          {data.assignments.map((assignment) => (
            <Link key={assignment.id} to={ROUTES.portal.tugasDetail.replace(':id', assignment.id)} className="group">
              <Card className="transition-shadow group-hover:shadow-popover">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                  <div className="min-w-0">
                    <p className="font-medium text-ink-primary group-hover:text-primary">{assignment.title}</p>
                    <p className="text-xs text-ink-secondary">
                      {assignment.course?.name} · {assignment.course?.lecturer?.name ?? '-'}
                    </p>
                  </div>
                  <AssignmentStatusBadge status={assignment.status} label={assignment.status_label} />
                </div>
                <p className={cn('mt-2 text-xs', assignment.is_past_due ? 'text-ink-tertiary' : 'text-ink-secondary')}>
                  Batas: {formatDateTime(assignment.due_at)} ({formatCountdown(assignment.due_at)})
                  {assignment.submission?.score && ` · Nilai ${assignment.submission.score}/${assignment.max_score}`}
                </p>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
