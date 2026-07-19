import { Link } from 'react-router-dom'
import { Users, GraduationCap, Briefcase, Building2, UserCheck, BookOpen, Wallet, ClipboardList, type LucideIcon } from 'lucide-react'
import { StatCard } from '@/components/ui/StatCard'
import { SkeletonCard } from '@/components/ui/Skeleton'
import type { DashboardSummary } from '@/types/dashboard'
import { formatNumber } from '@/utils/formatters'

interface CardDefinition {
  key: keyof DashboardSummary
  label: string
  icon: LucideIcon
}

/** Order matches the requirement list — Total Mahasiswa first, Menunggu Persetujuan last. */
const CARD_DEFINITIONS: CardDefinition[] = [
  { key: 'total_students', label: 'Total Mahasiswa', icon: Users },
  { key: 'total_lecturers', label: 'Total Dosen', icon: GraduationCap },
  { key: 'total_employees', label: 'Total Pegawai', icon: Briefcase },
  { key: 'total_study_programs', label: 'Program Studi', icon: Building2 },
  { key: 'active_students', label: 'Mahasiswa Aktif', icon: UserCheck },
  { key: 'active_classes', label: 'Kelas Aktif', icon: BookOpen },
  { key: 'unpaid_invoices', label: 'Tagihan Belum Dibayar', icon: Wallet },
  { key: 'pending_approvals', label: 'Menunggu Persetujuan', icon: ClipboardList },
]

export interface SummaryCardsGridProps {
  summary: DashboardSummary
  isLoading: boolean
  /** Tujuan klik per kartu (sudah termasuk query filter) — kartu tanpa entri dirender statis seperti sebelumnya. */
  links?: Partial<Record<keyof DashboardSummary, string>>
}

/** A key missing from `summary` means the user isn't permitted to see it (see DashboardController) — that card is skipped entirely, not shown as zero. */
export function SummaryCardsGrid({ summary, isLoading, links }: SummaryCardsGridProps) {
  if (isLoading) {
    return (
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {CARD_DEFINITIONS.map((card) => (
          <SkeletonCard key={card.key} />
        ))}
      </div>
    )
  }

  const visibleCards = CARD_DEFINITIONS.filter((card) => summary[card.key] !== undefined)

  if (visibleCards.length === 0) return null

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {visibleCards.map((card) => {
        const stat = <StatCard label={card.label} value={formatNumber(summary[card.key] ?? 0)} icon={card.icon} />
        const link = links?.[card.key]

        return link ? (
          <Link key={card.key} to={link} className="block rounded-xl focus:outline-none focus:ring-2 focus:ring-primary">
            {stat}
          </Link>
        ) : (
          <div key={card.key}>{stat}</div>
        )
      })}
    </div>
  )
}
