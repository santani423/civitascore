import { useCallback } from 'react'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { useFetch } from '@/hooks/useFetch'
import { dashboardService } from '@/services/dashboardService'
import { formatDateRange, toLocalIsoDate } from '@/utils/formatters'

const AGENDA_WINDOW_DAYS = 90
const AGENDA_LIMIT = 6

const CATEGORY_VARIANT: Record<string, BadgeVariant> = {
  registration: 'info',
  krs: 'primary',
  lecture: 'neutral',
  midterm_exam: 'danger',
  final_exam: 'danger',
  holiday: 'neutral',
  graduation: 'success',
  other: 'neutral',
}

export function AcademicAgendaList() {
  const fetchAgenda = useCallback(() => {
    const today = new Date()
    const horizon = new Date(today.getTime() + AGENDA_WINDOW_DAYS * 86_400_000)

    return dashboardService.getAcademicAgenda(toLocalIsoDate(today), toLocalIsoDate(horizon))
  }, [])
  const { data, isLoading, error } = useFetch(fetchAgenda)
  const items = (data ?? []).slice(0, AGENDA_LIMIT)

  return (
    <Card title="Agenda Akademik" description={`Jadwal penting ${AGENDA_WINDOW_DAYS} hari ke depan`} noPadding>
      {error ? (
        <p className="px-5 py-3.5 text-sm text-danger">{error}</p>
      ) : isLoading ? (
        <p className="px-5 py-3.5 text-sm text-ink-secondary">Memuat...</p>
      ) : items.length === 0 ? (
        <p className="px-5 py-3.5 text-sm text-ink-secondary">Belum ada agenda dalam {AGENDA_WINDOW_DAYS} hari ke depan.</p>
      ) : (
        <ul className="divide-y divide-border">
          {items.map((item) => (
            <li key={item.id} className="flex items-center justify-between gap-3 px-5 py-3.5">
              <div>
                <p className="text-sm font-medium text-ink-primary">{item.title}</p>
                <p className="text-xs text-ink-tertiary">
                  {formatDateRange(`${item.start_date}T00:00:00`, `${item.end_date}T00:00:00`)}
                </p>
              </div>
              <Badge variant={CATEGORY_VARIANT[item.category] ?? 'neutral'}>{item.category_label}</Badge>
            </li>
          ))}
        </ul>
      )}
    </Card>
  )
}
