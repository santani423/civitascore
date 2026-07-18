import { StatCard } from '@/components/ui/StatCard'
import { SUMMARY_CARDS } from '@/data/mock/dashboard'

export function SummaryCardsGrid() {
  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      {SUMMARY_CARDS.map((card) => (
        <StatCard
          key={card.id}
          label={card.label}
          value={card.value}
          icon={card.icon}
          changePercent={card.changePercent}
          trend={card.trend}
          caption={card.caption}
        />
      ))}
    </div>
  )
}
