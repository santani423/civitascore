import { useId, type ReactNode } from 'react'
import { Accessibility, Check, RotateCcw, X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { CONTRASTS, FONTS, SPACINGS, TEXT_SIZES, useA11y, type A11yPrefs } from '../../store/a11y'
import { useLandingUi } from '../../store/ui'
import { Drawer } from '../ui/Overlay'
import { Button, IconButton } from '../ui/Button'

/** Icon button that opens the panel — sits in the navbar, so it never covers page content. */
export function AccessibilityButton({ className }: { className?: string }) {
  const { m } = useLandingI18n()
  const open = useA11y((s) => s.openPanel)
  const expanded = useA11y((s) => s.panelOpen)
  return (
    <IconButton
      onClick={open}
      aria-label={m.a11y.open}
      title={m.a11y.open}
      aria-haspopup="dialog"
      aria-expanded={expanded}
      aria-controls="lp-a11y-panel"
      className={cn('text-lp-brand-text', className)}
    >
      <Accessibility size={21} aria-hidden />
    </IconButton>
  )
}

function Group({ title, children }: { title: string; children: ReactNode }) {
  return (
    <section className="border-t border-lp-line px-5 py-5 first:border-t-0">
      <h3 className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{title}</h3>
      <div className="mt-4 space-y-6">{children}</div>
    </section>
  )
}

/** Native radio group styled as a segmented control — arrow keys, grouping and state come from the browser. */
function Choice<K extends keyof A11yPrefs & string>({
  prefKey,
  legend,
  values,
  labels,
  onPick,
  columns,
}: {
  prefKey: K
  legend: string
  values: readonly A11yPrefs[K][]
  labels: Record<string, string>
  onPick: (value: A11yPrefs[K]) => void
  columns: string
}) {
  const name = useId()
  const current = useA11y((s) => s[prefKey])
  return (
    <fieldset>
      <legend className="text-[0.9063rem] font-semibold text-lp-fg">{legend}</legend>
      <div className={cn('mt-2.5 grid gap-2', columns)}>
        {values.map((value) => {
          const text = labels[String(value)]
          const checked = current === value
          return (
            <label key={String(value)} className="relative cursor-pointer">
              <input
                type="radio"
                name={`${name}-${prefKey}`}
                value={String(value)}
                checked={checked}
                onChange={() => onPick(value)}
                className="peer absolute inset-0 h-full w-full cursor-pointer opacity-0"
              />
              <span
                className={cn(
                  'flex min-h-11 items-center justify-center gap-1.5 rounded-lp-sm border px-2 text-center text-[0.875rem] font-medium leading-tight transition-colors',
                  'peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-lp-ring',
                  checked ? 'border-lp-brand bg-lp-brand-soft text-lp-brand-text' : 'border-lp-strong bg-lp-surface text-lp-fg hover:border-lp-brand/50',
                )}
              >
                {checked && <Check size={15} aria-hidden className="shrink-0" />}
                {text}
              </span>
            </label>
          )
        })}
      </div>
    </fieldset>
  )
}

/** Native checkbox with role="switch": the label, hint and state are announced together. */
function Switch({ prefKey, label, hint, onToggle }: { prefKey: keyof A11yPrefs; label: string; hint: string; onToggle: (on: boolean) => void }) {
  const { m } = useLandingI18n()
  const id = useId()
  const on = Boolean(useA11y((s) => s[prefKey]))
  return (
    <div className="flex items-start gap-4">
      <div className="min-w-0 flex-1">
        <label htmlFor={id} className="block cursor-pointer text-[0.9063rem] font-semibold text-lp-fg">
          {label}
        </label>
        <p id={`${id}-hint`} className="mt-1 text-[0.8125rem] leading-relaxed text-lp-muted">
          {hint}
        </p>
      </div>
      <span className="relative flex min-h-11 shrink-0 flex-col items-center justify-center gap-1">
        <input
          id={id}
          type="checkbox"
          role="switch"
          checked={on}
          aria-describedby={`${id}-hint`}
          onChange={(e) => onToggle(e.target.checked)}
          className="peer absolute inset-0 h-full w-full cursor-pointer opacity-0"
        />
        <span
          aria-hidden
          className={cn(
            'flex h-7 w-12 items-center rounded-full border-2 px-0.5 transition-colors',
            'peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-lp-ring',
            on ? 'border-lp-brand bg-lp-brand' : 'border-lp-strong bg-lp-sunken',
          )}
        >
          <span className={cn('flex h-5 w-5 items-center justify-center rounded-full bg-lp-surface shadow-lp-sm transition-transform', on ? 'translate-x-5' : 'translate-x-0')}>
            {on && <Check size={12} strokeWidth={3} className="text-lp-brand-text" />}
          </span>
        </span>
        <span aria-hidden className="text-[0.6875rem] font-semibold text-lp-muted">
          {on ? m.a11y.on : m.a11y.off}
        </span>
      </span>
    </div>
  )
}

export function AccessibilityPanel() {
  const { m } = useLandingI18n()
  const a = m.a11y
  const open = useA11y((s) => s.panelOpen)
  const close = useA11y((s) => s.closePanel)
  const set = useA11y((s) => s.set)
  const reset = useA11y((s) => s.reset)
  const announce = useLandingUi((s) => s.announce)

  const say = (label: string, value: string) => announce(fill(a.changed, { label, value }))
  const pick = <K extends keyof A11yPrefs>(key: K, value: A11yPrefs[K], label: string, valueLabel: string) => {
    set(key, value)
    say(label, valueLabel)
  }
  const flip = (key: 'colorSafe' | 'easyRead' | 'strongFocus' | 'largeTargets' | 'reduceMotion', label: string, on: boolean) => {
    set(key, on)
    say(label, on ? a.on : a.off)
  }

  return (
    <Drawer open={open} onClose={close} label={a.title} id="lp-a11y-panel" scrim="clear" panelClassName="sm:w-[min(92vw,420px)]">
      <div className="flex shrink-0 items-start gap-3 border-b border-lp-line px-5 py-4">
        <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-lp bg-lp-brand-soft text-lp-brand-text">
          <Accessibility size={22} aria-hidden />
        </span>
        <div className="min-w-0 flex-1">
          <h2 className="lp-h3 text-lp-fg">{a.title}</h2>
          <p className="mt-1 text-[0.8125rem] leading-relaxed text-lp-muted">{a.description}</p>
        </div>
        <IconButton onClick={close} aria-label={a.close} className="-mr-2 -mt-1">
          <X size={20} aria-hidden />
        </IconButton>
      </div>

      <div className="flex-1 overflow-y-auto overscroll-contain">
        <Group title={a.groups.display}>
          <Choice
            prefKey="contrast"
            legend={a.contrast.label}
            values={CONTRASTS}
            labels={a.contrast}
            columns="grid-cols-3"
            onPick={(v) => pick('contrast', v, a.contrast.label, a.contrast[v])}
          />
          <Switch prefKey="colorSafe" label={a.colorSafe.label} hint={a.colorSafe.hint} onToggle={(on) => flip('colorSafe', a.colorSafe.label, on)} />
        </Group>

        <Group title={a.groups.text}>
          <Choice
            prefKey="textSize"
            legend={a.textSize.label}
            values={TEXT_SIZES}
            labels={a.textSize}
            columns="grid-cols-2 min-[360px]:grid-cols-4"
            onPick={(v) => pick('textSize', v, a.textSize.label, a.textSize[v])}
          />
          <Choice
            prefKey="font"
            legend={a.font.label}
            values={FONTS}
            labels={{ default: a.font.default, readable: a.font.readable }}
            columns="grid-cols-2"
            onPick={(v) => pick('font', v, a.font.label, a.font[v])}
          />
          <Choice
            prefKey="spacing"
            legend={a.spacing.label}
            values={SPACINGS}
            labels={a.spacing}
            columns="grid-cols-3"
            onPick={(v) => pick('spacing', v, a.spacing.label, a.spacing[v])}
          />
          <Switch prefKey="easyRead" label={a.easyRead.label} hint={a.easyRead.hint} onToggle={(on) => flip('easyRead', a.easyRead.label, on)} />
        </Group>

        <Group title={a.groups.navigation}>
          <Switch prefKey="strongFocus" label={a.strongFocus.label} hint={a.strongFocus.hint} onToggle={(on) => flip('strongFocus', a.strongFocus.label, on)} />
          <Switch prefKey="largeTargets" label={a.largeTargets.label} hint={a.largeTargets.hint} onToggle={(on) => flip('largeTargets', a.largeTargets.label, on)} />
        </Group>

        <Group title={a.groups.motion}>
          <Switch prefKey="reduceMotion" label={a.reduceMotion.label} hint={a.reduceMotion.hint} onToggle={(on) => flip('reduceMotion', a.reduceMotion.label, on)} />
        </Group>

        <p className="border-t border-lp-line px-5 py-5 text-[0.8125rem] leading-relaxed text-lp-subtle">{a.note}</p>
      </div>

      <div className="flex shrink-0 gap-2 border-t border-lp-line bg-lp-canvas p-4">
        <Button
          variant="secondary"
          className="flex-1"
          onClick={() => {
            reset()
            announce(a.resetDone)
          }}
        >
          <RotateCcw size={16} aria-hidden />
          {a.reset}
        </Button>
        <Button className="flex-1" onClick={close}>
          {a.close}
        </Button>
      </div>
    </Drawer>
  )
}
