import { cn } from '@/utils/cn'

export function Tabs<T extends string>({ tabs, active, onChange }: { tabs: Array<{ key: T; label: string; badge?: number | string }>; active: T; onChange: (key: T) => void }) {
  return (
    <div className="flex gap-1 overflow-x-auto border-b border-border">
      {tabs.map((tab) => (
        <button
          key={tab.key}
          type="button"
          onClick={() => onChange(tab.key)}
          className={cn(
            '-mb-px whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition-colors',
            active === tab.key ? 'border-primary text-primary' : 'border-transparent text-ink-secondary hover:text-ink-primary',
          )}
        >
          {tab.label}
          {tab.badge !== undefined && tab.badge !== 0 && <span className="ml-1.5 rounded-full bg-surface-hover px-1.5 text-xs">{tab.badge}</span>}
        </button>
      ))}
    </div>
  )
}
