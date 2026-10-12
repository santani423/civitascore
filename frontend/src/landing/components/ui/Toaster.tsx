import { CheckCircle2, Info, TriangleAlert, X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { useLandingUi } from '../../store/ui'

const icons = { success: CheckCircle2, info: Info, error: TriangleAlert }

/** Transient notifications + the polite live region used for non-visual announcements. */
export function Toaster() {
  const { m } = useLandingI18n()
  const toasts = useLandingUi((s) => s.toasts)
  const dismiss = useLandingUi((s) => s.dismissToast)
  const announcement = useLandingUi((s) => s.announcement)

  return (
    <>
      <div aria-live="polite" aria-atomic="true" className="sr-only">
        {announcement}
      </div>
      <div
        role="status"
        aria-live="polite"
        className="pointer-events-none fixed inset-x-0 bottom-4 z-[90] flex flex-col items-center gap-2 px-4 sm:bottom-6"
      >
        {toasts.map((t) => {
          const Icon = icons[t.tone]
          return (
            <div
              key={t.id}
              className="pointer-events-auto flex w-full max-w-sm animate-rise-in items-start gap-3 rounded-lp border border-lp-line bg-lp-elev px-4 py-3 shadow-lp-lg"
            >
              <Icon
                size={18}
                aria-hidden
                className={cn('mt-0.5 shrink-0', t.tone === 'success' && 'text-lp-ok', t.tone === 'info' && 'text-lp-info', t.tone === 'error' && 'text-lp-bad')}
              />
              <p className="lp-small flex-1 text-lp-fg">{t.message}</p>
              <button
                type="button"
                onClick={() => dismiss(t.id)}
                aria-label={m.common.close}
                className="-mr-1 rounded-full p-1 text-lp-subtle hover:bg-lp-sunken hover:text-lp-fg"
              >
                <X size={14} aria-hidden />
              </button>
            </div>
          )
        })}
      </div>
    </>
  )
}
