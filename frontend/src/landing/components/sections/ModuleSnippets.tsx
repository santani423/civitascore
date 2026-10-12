import type { ReactNode } from 'react'
import { Check, Clock, FileText, Lock } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import type { ModuleKey } from '../../data/catalog'
import {
  CLASS_AVERAGE_GPA,
  CURRICULUM_CREDITS,
  GPA_TREND,
  GRADE_DISTRIBUTION,
  INVOICES,
  STUDENT_ROWS,
  WEEK_BLOCKS,
  type Tone,
} from '../../data/demo'
import { Avatar, MockChip } from '../mock/Frame'
import { Columns, Sparkline } from '../mock/Charts'

const blockTone: Record<Tone, string> = {
  brand: 'border-l-lp-viz1 bg-lp-viz1/10',
  gold: 'border-l-lp-viz2 bg-lp-viz2/10',
  info: 'border-l-lp-viz3 bg-lp-viz3/10',
}

function SnippetTitle({ children, aside }: { children: string; aside?: ReactNode }) {
  return (
    <div className="mb-3 flex items-center justify-between gap-2">
      <p className="truncate text-[12px] font-semibold text-lp-fg">{children}</p>
      {aside}
    </div>
  )
}

/** Decorative product glimpse for each module card (the card text carries the meaning). */
export function ModuleSnippet({ moduleKey }: { moduleKey: ModuleKey }) {
  const { m, fmt } = useLandingI18n()
  const s = m.modules.snippets

  switch (moduleKey) {
    case 'studentData':
      return (
        <div>
          <SnippetTitle aside={<MockChip tone="muted">{s.cohort}</MockChip>}>{s.studentsTitle}</SnippetTitle>
          <ul className="divide-y divide-lp-line rounded-lg border border-lp-line bg-lp-surface">
            {STUDENT_ROWS.map((row) => (
              <li key={row.nim} className="flex items-center gap-2.5 px-3 py-2">
                <Avatar name={row.name} size={24} />
                <div className="min-w-0 flex-1">
                  <p className="truncate text-[12px] font-medium text-lp-fg">{row.name}</p>
                  <p className="lp-tabular truncate text-[10.5px] text-lp-subtle">
                    {row.nim} · {m.demo.programs[row.program]}
                  </p>
                </div>
                <MockChip tone={row.onLeave ? 'gold' : 'ok'}>{row.onLeave ? s.leave : s.active}</MockChip>
              </li>
            ))}
          </ul>
        </div>
      )

    case 'lectures': {
      // 2024-01-01 was a Monday.
      const days = Array.from({ length: 5 }, (_, i) => fmt.weekday(new Date(2024, 0, 1 + i), 'short'))
      return (
        <div>
          <SnippetTitle>{s.scheduleTitle}</SnippetTitle>
          <div className="grid grid-cols-5 gap-1.5">
            {days.map((day, i) => (
              <div key={day} className="min-w-0">
                <p className="mb-1.5 text-center text-[10.5px] font-semibold text-lp-subtle">{day}</p>
                <div className="space-y-1.5">
                  {WEEK_BLOCKS[i].map((block) => (
                    <div key={block.course + block.start} className={cn('rounded-md border-l-2 px-1.5 py-1.5', blockTone[block.tone])}>
                      <p className="lp-tabular text-[9.5px] text-lp-subtle">{block.start}</p>
                      <p className="line-clamp-2 text-[10.5px] font-medium leading-tight text-lp-fg">{m.demo.courses[block.course]}</p>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
      )
    }

    case 'curriculum': {
      const max = Math.max(...CURRICULUM_CREDITS)
      return (
        <div>
          <SnippetTitle aside={<MockChip tone="brand">{m.demo.programs.informatics}</MockChip>}>{s.curriculumTitle}</SnippetTitle>
          <ul className="space-y-2.5">
            {CURRICULUM_CREDITS.map((credits, i) => (
              <li key={i} className="flex items-center gap-3">
                <span className="w-[4.75rem] shrink-0 truncate text-[11px] text-lp-muted">{fill(s.semester, { n: i + 1 })}</span>
                <span className="h-2 flex-1 overflow-hidden rounded-full bg-lp-sunken">
                  <span className="block h-full rounded-full bg-lp-viz1" style={{ width: `${(credits / max) * 100}%` }} />
                </span>
                <span className="lp-tabular w-14 shrink-0 text-right text-[11px] font-semibold text-lp-fg">{fill(s.credits, { n: credits })}</span>
              </li>
            ))}
          </ul>
        </div>
      )
    }

    case 'assessment':
      return (
        <div>
          <SnippetTitle
            aside={
              <span className="text-[10.5px] text-lp-subtle">
                {s.averageGpa} <span className="lp-tabular font-bold text-lp-fg">{fmt.number(CLASS_AVERAGE_GPA, 2)}</span>
              </span>
            }
          >
            {`${s.gradesTitle} · ${m.demo.courses.databases}`}
          </SnippetTitle>
          <Columns
            height={78}
            items={GRADE_DISTRIBUTION.map((g) => ({ key: g.grade, label: g.grade, value: g.value }))}
            format={(v) => fmt.percent(v, 0)}
          />
        </div>
      )

    case 'people':
      return (
        <div className="rounded-lg border border-lp-line bg-lp-surface p-3.5">
          <div className="flex items-center gap-3">
            <Avatar name="Maria Kristanti" size={40} />
            <div className="min-w-0">
              <p className="truncate text-[13px] font-semibold text-lp-fg">Dr. Maria Kristanti, M.Kom.</p>
              <p className="truncate text-[11px] text-lp-subtle">
                {s.profileRank} · {s.profileUnit}
              </p>
            </div>
          </div>
          <div className="mt-3 flex flex-wrap gap-1.5">
            <MockChip tone="ok">
              <Check size={10} className="mr-1" />
              {s.profileCert}
            </MockChip>
            <MockChip tone="info">{fill(s.profileTrainings, { n: 3 })}</MockChip>
            <MockChip tone="muted">{m.demo.programs.informatics}</MockChip>
          </div>
        </div>
      )

    case 'services':
      return (
        <div>
          <SnippetTitle>{s.requestsTitle}</SnippetTitle>
          <ul className="space-y-1.5">
            {[
              { label: s.requestActive, done: false },
              { label: s.requestLegal, done: true },
            ].map((req) => (
              <li key={req.label} className="flex items-center gap-2.5 rounded-lg border border-lp-line bg-lp-surface px-3 py-2">
                <FileText size={14} className="shrink-0 text-lp-subtle" />
                <span className="min-w-0 flex-1 truncate text-[11.5px] text-lp-fg">{req.label}</span>
                <MockChip tone={req.done ? 'ok' : 'info'}>{req.done ? s.done : s.processing}</MockChip>
              </li>
            ))}
          </ul>
        </div>
      )

    case 'finance':
      return (
        <div>
          <SnippetTitle>{s.invoicesTitle}</SnippetTitle>
          <ul className="space-y-1.5">
            {INVOICES.map((inv) => (
              <li key={inv.key} className="flex items-center gap-2.5 rounded-lg border border-lp-line bg-lp-surface px-3 py-2">
                <div className="min-w-0 flex-1">
                  <p className="truncate text-[11.5px] text-lp-fg">{s[inv.key]}</p>
                  <p className="lp-tabular text-[11px] font-semibold text-lp-muted">{fmt.currency(inv.amount)}</p>
                </div>
                <MockChip tone={inv.paid ? 'ok' : 'warn'}>{inv.paid ? s.invoicePaid : s.invoicePending}</MockChip>
              </li>
            ))}
          </ul>
        </div>
      )

    case 'admissions': {
      const stages = [s.funnelRegister, s.funnelVerify, s.funnelSelect, s.funnelReenroll]
      return (
        <div>
          <SnippetTitle aside={<MockChip tone="muted">{s.concept}</MockChip>}>{s.funnelTitle}</SnippetTitle>
          <ol className="space-y-1.5">
            {stages.map((stage, i) => (
              <li key={stage} className="flex items-center gap-2.5">
                <span className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-dashed border-lp-strong text-[10px] font-semibold text-lp-subtle">
                  {i + 1}
                </span>
                <span
                  className="flex h-7 items-center rounded-md border border-dashed border-lp-strong bg-lp-surface/60 px-2.5 text-[11px] text-lp-muted"
                  style={{ width: `${100 - i * 14}%` }}
                >
                  <span className="truncate">{stage}</span>
                </span>
              </li>
            ))}
          </ol>
        </div>
      )
    }

    case 'analytics': {
      const last = GPA_TREND.ipk[GPA_TREND.ipk.length - 1]
      return (
        <div className="rounded-lg border border-lp-line bg-lp-surface p-3.5">
          <div className="flex items-end justify-between gap-3">
            <div>
              <p className="text-[11px] text-lp-subtle">{s.trendTitle}</p>
              <p className="lp-tabular mt-0.5 font-display text-[22px] font-bold text-lp-fg">{fmt.number(last, 2)}</p>
            </div>
            <MockChip tone="ok">+{fmt.number(last - GPA_TREND.ipk[0], 2)}</MockChip>
          </div>
          <Sparkline values={GPA_TREND.ipk} className="mt-2 h-12" />
        </div>
      )
    }

    case 'governance': {
      const steps = [s.stepSubmitted, s.stepAdvisor, s.stepHead, s.stepBureau]
      const current = 2
      return (
        <div>
          <SnippetTitle aside={<Lock size={13} className="text-lp-subtle" />}>{s.approvalTitle}</SnippetTitle>
          <ol className="flex items-start">
            {steps.map((step, i) => {
              const done = i < current
              const isCurrent = i === current
              return (
                <li key={step} className="relative flex flex-1 flex-col items-center text-center">
                  {i > 0 && <span className={cn('absolute right-1/2 top-3 h-0.5 w-full', i <= current ? 'bg-lp-viz1' : 'bg-lp-line')} />}
                  <span
                    className={cn(
                      'relative flex h-6 w-6 items-center justify-center rounded-full border-2',
                      done && 'border-lp-viz1 bg-lp-viz1 text-lp-onbrand',
                      isCurrent && 'border-lp-viz1 bg-lp-surface text-lp-brand-text',
                      !done && !isCurrent && 'border-lp-line bg-lp-surface text-lp-subtle',
                    )}
                  >
                    {done ? <Check size={12} strokeWidth={3} /> : isCurrent ? <Clock size={11} /> : <span className="text-[10px] font-semibold">{i + 1}</span>}
                  </span>
                  <span className={cn('mt-1.5 px-0.5 text-[10px] leading-tight', isCurrent ? 'font-semibold text-lp-fg' : 'text-lp-subtle')}>{step}</span>
                </li>
              )
            })}
          </ol>
        </div>
      )
    }
  }
}
