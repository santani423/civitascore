import { useId, useState, type ReactNode } from 'react'
import { Plus } from 'lucide-react'
import { cn } from '@/utils/cn'

export interface AccordionItem {
  key: string
  title: string
  content: ReactNode
}

/** Disclosure list — one panel open at a time; height animates via a grid-rows transition. */
export function Accordion({ items, defaultOpen, headingLevel = 3 }: { items: AccordionItem[]; defaultOpen?: string; headingLevel?: 3 | 4 }) {
  const [open, setOpen] = useState<string | null>(defaultOpen ?? null)
  const baseId = useId()
  const Heading = headingLevel === 3 ? 'h3' : 'h4'

  return (
    <div className="divide-y divide-lp-line border-y border-lp-line">
      {items.map((item) => {
        const expanded = open === item.key
        const buttonId = `${baseId}-${item.key}-button`
        const regionId = `${baseId}-${item.key}-region`
        return (
          <div key={item.key}>
            <Heading className="m-0">
              <button
                type="button"
                id={buttonId}
                aria-expanded={expanded}
                aria-controls={regionId}
                onClick={() => setOpen(expanded ? null : item.key)}
                className="group flex w-full items-start justify-between gap-6 py-5 text-left sm:py-6"
              >
                <span className="lp-h4 text-[1.0625rem] text-lp-fg transition-colors group-hover:text-lp-brand-text sm:text-[1.125rem]">{item.title}</span>
                <span
                  aria-hidden
                  className={cn(
                    'mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-lp-line text-lp-muted transition-[transform,background-color,color,border-color] duration-300',
                    expanded && 'rotate-45 border-lp-brand bg-lp-brand text-lp-onbrand',
                  )}
                >
                  <Plus size={15} />
                </span>
              </button>
            </Heading>
            <div
              id={regionId}
              role="region"
              aria-labelledby={buttonId}
              className={cn('grid transition-[grid-template-rows] duration-300 ease-out', expanded ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]')}
            >
              <div className="overflow-hidden" inert={!expanded}>
                <div className="lp-body max-w-3xl pb-6 pr-12 text-lp-muted">{item.content}</div>
              </div>
            </div>
          </div>
        )
      })}
    </div>
  )
}
