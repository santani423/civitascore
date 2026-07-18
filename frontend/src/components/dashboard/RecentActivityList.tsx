import { UserPlus, FileText, GraduationCap, Wallet, MailCheck, MessageSquareCheck, type LucideIcon } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { RECENT_ACTIVITIES } from '@/data/mock/activities'
import type { ActivityType } from '@/types/dashboard'
import { formatRelativeTime } from '@/utils/formatters'

const ACTIVITY_ICON: Record<ActivityType, LucideIcon> = {
  student_registered: UserPlus,
  krs_submitted: FileText,
  grade_published: GraduationCap,
  payment_received: Wallet,
  letter_approved: MailCheck,
  complaint_resolved: MessageSquareCheck,
}

export function RecentActivityList() {
  return (
    <Card title="Aktivitas Terbaru" description="Kejadian terbaru di seluruh sistem" noPadding>
      <ul className="divide-y divide-border">
        {RECENT_ACTIVITIES.map((activity) => {
          const Icon = ACTIVITY_ICON[activity.type]

          return (
            <li key={activity.id} className="flex items-start gap-3 px-5 py-3.5">
              <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary dark:bg-primary-950">
                <Icon className="size-4" aria-hidden />
              </span>
              <div className="flex-1">
                <p className="text-sm font-medium text-ink-primary">{activity.title}</p>
                <p className="text-xs text-ink-secondary">{activity.description}</p>
                <p className="mt-1 text-xs text-ink-tertiary">
                  {activity.actor} &middot; {formatRelativeTime(activity.timestamp)}
                </p>
              </div>
            </li>
          )
        })}
      </ul>
    </Card>
  )
}
