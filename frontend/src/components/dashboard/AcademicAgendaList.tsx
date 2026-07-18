import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { ACADEMIC_AGENDA } from '@/data/mock/agenda'
import type { AgendaCategory } from '@/types/dashboard'
import { formatDateRange } from '@/utils/formatters'

const CATEGORY_LABEL: Record<AgendaCategory, string> = {
  krs: 'KRS',
  exam: 'Ujian',
  payment: 'Pembayaran',
  grading: 'Penilaian',
  graduation: 'Wisuda',
}

const CATEGORY_VARIANT: Record<AgendaCategory, BadgeVariant> = {
  krs: 'primary',
  exam: 'danger',
  payment: 'warning',
  grading: 'info',
  graduation: 'success',
}

export function AcademicAgendaList() {
  return (
    <Card title="Agenda Akademik" description="Jadwal penting semester ini" noPadding>
      <ul className="divide-y divide-border">
        {ACADEMIC_AGENDA.map((item) => (
          <li key={item.id} className="flex items-center justify-between gap-3 px-5 py-3.5">
            <div>
              <p className="text-sm font-medium text-ink-primary">{item.title}</p>
              <p className="text-xs text-ink-tertiary">{formatDateRange(item.startDate, item.endDate)}</p>
            </div>
            <Badge variant={CATEGORY_VARIANT[item.category]}>{CATEGORY_LABEL[item.category]}</Badge>
          </li>
        ))}
      </ul>
    </Card>
  )
}
