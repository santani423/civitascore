import { useCallback, useEffect, useId, useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { AlertCircle, CalendarClock, Check, CheckCircle2, ChevronDown, Copy, Mail, MessageSquare } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../i18n'
import type { Messages } from '../i18n/messages/id'
import { landingConfig } from '../config'
import { ECOSYSTEM, type CategoryKey } from '../data/catalog'
import { useLandingUi, type RequestMode } from '../store/ui'
import { Dialog } from './ui/Overlay'
import { Button } from './ui/Button'

const MESSAGE_MAX = 1000
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/

type ErrorCode = keyof Messages['form']['errors']
type RoleKey = Exclude<keyof Messages['form']['roles'], 'placeholder'>
const ROLE_KEYS: RoleKey[] = ['leadership', 'faculty', 'lecturer', 'staff', 'it', 'other']

// Messages are error *codes*, translated at render time — so switching
// language while the dialog is open re-translates visible errors.
const base = {
  name: z.string().trim().min(1, 'nameRequired' satisfies ErrorCode).max(120),
  email: z.string().trim().min(1, 'emailRequired' satisfies ErrorCode).regex(EMAIL_RE, 'emailInvalid' satisfies ErrorCode),
  institution: z.string().trim().min(1, 'institutionRequired' satisfies ErrorCode).max(160),
  role: z.string().min(1, 'roleRequired' satisfies ErrorCode),
  interests: z.array(z.string()),
}
const schemas = {
  demo: z.object({ ...base, message: z.string().trim().max(MESSAGE_MAX, 'messageTooLong' satisfies ErrorCode) }),
  contact: z.object({
    ...base,
    message: z.string().trim().min(1, 'messageRequired' satisfies ErrorCode).max(MESSAGE_MAX, 'messageTooLong' satisfies ErrorCode),
  }),
}
type FormValues = z.infer<typeof schemas.demo>

type Result = { kind: 'mailto'; summary: string; href: string } | { kind: 'manual'; summary: string }

/**
 * Demo request / contact form. There is no backend for it on purpose: with
 * VITE_LANDING_CONTACT_EMAIL set it opens a pre-filled email draft,
 * otherwise it shows a copyable summary and says the channel isn't set up.
 */
export default function RequestDialog() {
  const { m } = useLandingI18n()
  const { open, mode, interests } = useLandingUi((s) => s.request)
  const close = useLandingUi((s) => s.closeRequest)
  const [result, setResult] = useState<Result | null>(null)
  const onClose = () => {
    close()
    setResult(null)
  }
  const Icon = mode === 'demo' ? CalendarClock : MessageSquare

  return (
    <Dialog
      open={open}
      onClose={onClose}
      size="lg"
      title={result ? m.form.successTitle : mode === 'demo' ? m.form.demoTitle : m.form.contactTitle}
      description={result ? undefined : mode === 'demo' ? m.form.demoDescription : m.form.contactDescription}
      icon={
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lp bg-lp-brand/10 text-lp-brand-text">
          {result ? <CheckCircle2 size={20} aria-hidden /> : <Icon size={20} aria-hidden />}
        </span>
      }
    >
      {result ? (
        <RequestResult result={result} onDone={onClose} />
      ) : (
        <RequestForm key={`${mode}:${interests.join(',')}`} mode={mode} initialInterests={interests} onCancel={onClose} onSubmitted={setResult} />
      )}
    </Dialog>
  )
}

function RequiredMark({ label }: { label: string }) {
  return (
    <>
      <span aria-hidden className="ml-0.5 text-lp-bad">
        *
      </span>
      <span className="sr-only"> ({label})</span>
    </>
  )
}

function FieldError({ id, text }: { id: string; text?: string }) {
  if (!text) return null
  return (
    <p id={id} className="mt-1.5 flex items-center gap-1.5 text-[12.5px] text-lp-bad">
      <AlertCircle size={13} aria-hidden />
      {text}
    </p>
  )
}

const fieldBase =
  'mt-1.5 block w-full rounded-lp-sm border bg-lp-surface px-3.5 text-[15px] text-lp-fg placeholder:text-lp-subtle transition-[border-color,box-shadow] duration-150 focus:border-lp-brand focus:outline-none focus:ring-4 focus:ring-lp-brand/15'
const fieldState = (invalid: boolean) => (invalid ? 'border-lp-bad focus:border-lp-bad focus:ring-lp-bad/15' : 'border-lp-strong')
const labelClass = 'text-[13.5px] font-medium text-lp-fg'

function RequestForm({
  mode,
  initialInterests,
  onCancel,
  onSubmitted,
}: {
  mode: RequestMode
  initialInterests: CategoryKey[]
  onCancel: () => void
  onSubmitted: (result: Result) => void
}) {
  const { m } = useLandingI18n()
  const f = m.form
  const formId = useId()
  const {
    register,
    handleSubmit,
    watch,
    formState: { errors, submitCount },
  } = useForm<FormValues>({
    resolver: zodResolver(schemas[mode]),
    defaultValues: { name: '', email: '', institution: '', role: '', interests: initialInterests, message: '' },
    mode: 'onTouched',
  })
  const messageLength = watch('message')?.length ?? 0
  const errorText = (code?: string) => {
    if (!code) return undefined
    const template = f.errors[code as ErrorCode]
    return template ? fill(template, { max: MESSAGE_MAX }) : code
  }
  const ids = {
    name: `${formId}-name`,
    email: `${formId}-email`,
    institution: `${formId}-institution`,
    role: `${formId}-role`,
    message: `${formId}-message`,
    summary: `${formId}-summary`,
  }
  const showSummary = submitCount > 0 && Object.keys(errors).length > 0

  const onValid = (values: FormValues) => {
    const roleLabel = f.roles[values.role as RoleKey] ?? values.role
    const interestLabels = values.interests.map((k) => m.platform.categories[k as CategoryKey]?.name ?? k)
    const lines = [
      `${f.fields.name}: ${values.name}`,
      `${f.fields.email}: ${values.email}`,
      `${f.fields.institution}: ${values.institution}`,
      `${f.fields.role}: ${roleLabel}`,
      ...(mode === 'demo' && interestLabels.length ? [`${f.fields.interests}: ${interestLabels.join(', ')}`] : []),
      ...(values.message ? ['', `${f.fields.message}:`, values.message] : []),
    ]
    const summary = lines.join('\n')
    if (landingConfig.contactEmail) {
      const subject = fill(mode === 'demo' ? f.emailSubjectDemo : f.emailSubjectContact, { institution: values.institution })
      const href = `mailto:${landingConfig.contactEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(summary)}`
      window.location.href = href
      onSubmitted({ kind: 'mailto', summary, href })
    } else {
      onSubmitted({ kind: 'manual', summary })
    }
  }

  return (
    <form noValidate onSubmit={handleSubmit(onValid)}>
      {showSummary && (
        <div id={ids.summary} role="alert" className="mb-5 flex items-center gap-2 rounded-lp-sm border border-lp-bad/30 bg-lp-bad/5 px-3.5 py-2.5 text-[13.5px] text-lp-bad">
          <AlertCircle size={16} aria-hidden />
          {f.errors.summary}
        </div>
      )}

      <div className="grid gap-5 sm:grid-cols-2">
        <div>
          <label htmlFor={ids.name} className={labelClass}>
            {f.fields.name}
            <RequiredMark label={f.required} />
          </label>
          <input
            id={ids.name}
            data-autofocus
            autoComplete="name"
            placeholder={f.placeholders.name}
            aria-invalid={!!errors.name}
            aria-describedby={errors.name ? `${ids.name}-error` : undefined}
            className={cn(fieldBase, 'h-11', fieldState(!!errors.name))}
            {...register('name')}
          />
          <FieldError id={`${ids.name}-error`} text={errorText(errors.name?.message)} />
        </div>
        <div>
          <label htmlFor={ids.email} className={labelClass}>
            {f.fields.email}
            <RequiredMark label={f.required} />
          </label>
          <input
            id={ids.email}
            type="email"
            inputMode="email"
            autoComplete="email"
            placeholder={f.placeholders.email}
            aria-invalid={!!errors.email}
            aria-describedby={errors.email ? `${ids.email}-error` : undefined}
            className={cn(fieldBase, 'h-11', fieldState(!!errors.email))}
            {...register('email')}
          />
          <FieldError id={`${ids.email}-error`} text={errorText(errors.email?.message)} />
        </div>
        <div>
          <label htmlFor={ids.institution} className={labelClass}>
            {f.fields.institution}
            <RequiredMark label={f.required} />
          </label>
          <input
            id={ids.institution}
            autoComplete="organization"
            placeholder={f.placeholders.institution}
            aria-invalid={!!errors.institution}
            aria-describedby={errors.institution ? `${ids.institution}-error` : undefined}
            className={cn(fieldBase, 'h-11', fieldState(!!errors.institution))}
            {...register('institution')}
          />
          <FieldError id={`${ids.institution}-error`} text={errorText(errors.institution?.message)} />
        </div>
        <div>
          <label htmlFor={ids.role} className={labelClass}>
            {f.fields.role}
            <RequiredMark label={f.required} />
          </label>
          <div className="relative">
            <select
              id={ids.role}
              aria-invalid={!!errors.role}
              aria-describedby={errors.role ? `${ids.role}-error` : undefined}
              className={cn(fieldBase, 'h-11 appearance-none pr-10', fieldState(!!errors.role))}
              {...register('role')}
            >
              <option value="">{f.roles.placeholder}</option>
              {ROLE_KEYS.map((key) => (
                <option key={key} value={key}>
                  {f.roles[key]}
                </option>
              ))}
            </select>
            <ChevronDown size={16} aria-hidden className="pointer-events-none absolute right-3.5 top-1/2 mt-[3px] -translate-y-1/2 text-lp-subtle" />
          </div>
          <FieldError id={`${ids.role}-error`} text={errorText(errors.role?.message)} />
        </div>
      </div>

      {mode === 'demo' && (
        <fieldset className="mt-6">
          <legend className={labelClass}>{f.fields.interests}</legend>
          <div className="mt-2.5 flex flex-wrap gap-2">
            {ECOSYSTEM.map((cat) => {
              const CatIcon = cat.icon
              return (
                <label key={cat.key} className="group relative cursor-pointer">
                  <input type="checkbox" value={cat.key} className="peer sr-only" {...register('interests')} />
                  <span className="inline-flex items-center gap-2 rounded-full border border-lp-strong px-3.5 py-2 text-[13px] font-medium text-lp-muted transition-colors group-hover:border-lp-fg/30 group-hover:text-lp-fg peer-checked:border-lp-brand peer-checked:bg-lp-brand/10 peer-checked:text-lp-brand-text peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-lp-ring">
                    <CatIcon size={14} aria-hidden />
                    {m.platform.categories[cat.key].name}
                  </span>
                </label>
              )
            })}
          </div>
        </fieldset>
      )}

      <div className="mt-6">
        <div className="flex items-baseline justify-between gap-3">
          <label htmlFor={ids.message} className={labelClass}>
            {mode === 'contact' ? (
              <>
                {f.fields.message}
                <RequiredMark label={f.required} />
              </>
            ) : (
              f.fields.messageOptional
            )}
          </label>
          <span className={cn('lp-tabular text-[12px]', messageLength > MESSAGE_MAX ? 'text-lp-bad' : 'text-lp-subtle')}>
            {fill(f.charCount, { count: messageLength, max: MESSAGE_MAX })}
          </span>
        </div>
        <textarea
          id={ids.message}
          rows={4}
          placeholder={f.placeholders.message}
          aria-invalid={!!errors.message}
          aria-describedby={errors.message ? `${ids.message}-error` : undefined}
          className={cn(fieldBase, 'resize-y py-3', fieldState(!!errors.message))}
          {...register('message')}
        />
        <FieldError id={`${ids.message}-error`} text={errorText(errors.message?.message)} />
      </div>

      <div className="mt-7 flex flex-col gap-4 border-t border-lp-line pt-5 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-[12.5px] text-lp-subtle">{f.privacyNote}</p>
        <div className="flex flex-col-reverse gap-2 sm:flex-row">
          <Button variant="secondary" onClick={onCancel}>
            {f.cancel}
          </Button>
          <Button type="submit">{mode === 'demo' ? f.submitDemo : f.submitContact}</Button>
        </div>
      </div>
    </form>
  )
}

function RequestResult({ result, onDone }: { result: Result; onDone: () => void }) {
  const { m } = useLandingI18n()
  const toast = useLandingUi((s) => s.toast)
  const [copied, setCopied] = useState(false)
  const summaryRef = useRef<HTMLPreElement>(null)
  const doneRef = useRef<HTMLButtonElement>(null)
  const summaryId = useId()

  // The form (and its focused submit button) was just replaced — keep focus inside the dialog.
  useEffect(() => {
    doneRef.current?.focus()
  }, [])

  const copy = useCallback(async () => {
    try {
      await navigator.clipboard.writeText(result.summary)
      setCopied(true)
      toast(m.toast.copied)
      window.setTimeout(() => setCopied(false), 2400)
    } catch {
      if (summaryRef.current) {
        const range = document.createRange()
        range.selectNodeContents(summaryRef.current)
        window.getSelection()?.removeAllRanges()
        window.getSelection()?.addRange(range)
      }
      toast(m.toast.copyFailed, 'error')
    }
  }, [result.summary, toast, m.toast.copied, m.toast.copyFailed])

  return (
    <div>
      <p className="lp-body text-lp-muted">{result.kind === 'mailto' ? m.form.successMailto : m.form.successManual}</p>
      <p id={summaryId} className="mt-6 text-[12px] font-semibold uppercase tracking-wider text-lp-subtle">
        {m.form.summaryTitle}
      </p>
      <pre
        ref={summaryRef}
        aria-labelledby={summaryId}
        tabIndex={0}
        className="mt-2 max-h-64 overflow-auto whitespace-pre-wrap break-words rounded-lp-sm border border-lp-line bg-lp-sunken p-4 font-sans text-[13.5px] leading-relaxed text-lp-fg"
      >
        {result.summary}
      </pre>
      <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        {result.kind === 'mailto' && (
          <a
            href={result.href}
            className="inline-flex h-11 items-center justify-center gap-2 rounded-full px-5 text-[14.5px] font-semibold text-lp-muted hover:bg-lp-sunken hover:text-lp-fg"
          >
            <Mail size={16} aria-hidden />
            {m.form.openEmail}
          </a>
        )}
        <Button variant="secondary" onClick={copy}>
          {copied ? <Check size={16} aria-hidden /> : <Copy size={16} aria-hidden />}
          {copied ? m.form.copied : m.form.copy}
        </Button>
        <Button ref={doneRef} onClick={onDone}>
          {m.form.done}
        </Button>
      </div>
    </div>
  )
}
