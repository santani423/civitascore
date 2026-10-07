import { useEffect, useId, useRef, type ReactNode, type RefObject } from 'react'
import { X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../../i18n'

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'

/**
 * Shared dialog behavior for Modal, Drawer and the command palette:
 * focus moves in and is trapped, Escape closes, body scroll locks,
 * and focus returns to the opener on close.
 */
export function useDialog(open: boolean, onClose: () => void, panelRef: RefObject<HTMLElement | null>) {
  const onCloseRef = useRef(onClose)
  onCloseRef.current = onClose

  useEffect(() => {
    if (!open) return
    const opener = document.activeElement as HTMLElement | null
    const panel = panelRef.current
    const first = panel?.querySelector<HTMLElement>('[data-autofocus]') ?? panel?.querySelector<HTMLElement>(FOCUSABLE)
    ;(first ?? panel)?.focus()

    const prevOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'

    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        e.stopPropagation()
        onCloseRef.current()
        return
      }
      if (e.key !== 'Tab' || !panel) return
      const nodes = Array.from(panel.querySelectorAll<HTMLElement>(FOCUSABLE)).filter((n) => n.offsetParent !== null)
      if (nodes.length === 0) return
      const firstNode = nodes[0]
      const lastNode = nodes[nodes.length - 1]
      if (e.shiftKey && document.activeElement === firstNode) {
        e.preventDefault()
        lastNode.focus()
      } else if (!e.shiftKey && document.activeElement === lastNode) {
        e.preventDefault()
        firstNode.focus()
      }
    }
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('keydown', onKey)
      document.body.style.overflow = prevOverflow
      opener?.focus?.()
    }
  }, [open, panelRef])
}

interface ModalProps {
  open: boolean
  onClose: () => void
  title: ReactNode
  description?: ReactNode
  children?: ReactNode
  footer?: ReactNode
  size?: 'sm' | 'md' | 'lg'
}

export function Modal({ open, onClose, title, description, children, footer, size = 'md' }: ModalProps) {
  const { t } = useI18n()
  const panelRef = useRef<HTMLDivElement>(null)
  const titleId = useId()
  const descId = useId()
  useDialog(open, onClose, panelRef)
  if (!open) return null

  return (
    <div className="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6">
      <div aria-hidden className="absolute inset-0 animate-fade-in bg-one-hero/50 backdrop-blur-[2px]" onClick={onClose} />
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        aria-describedby={description ? descId : undefined}
        tabIndex={-1}
        className={cn(
          'relative flex max-h-[92vh] w-full animate-rise-in flex-col rounded-t-3xl border border-one-line bg-one-elev shadow-overlay sm:rounded-2xl',
          size === 'sm' && 'sm:max-w-md',
          size === 'md' && 'sm:max-w-lg',
          size === 'lg' && 'sm:max-w-2xl',
        )}
      >
        <div className="flex items-start justify-between gap-4 border-b border-one-line px-5 py-4 sm:px-6">
          <div>
            <h2 id={titleId} className="t-h2 text-one-fg">
              {title}
            </h2>
            {description && (
              <p id={descId} className="t-small mt-1 text-one-subtle">
                {description}
              </p>
            )}
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label={t('common.close')}
            className="-mr-2 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-one-subtle hover:bg-one-sunken hover:text-one-fg"
          >
            <X size={18} aria-hidden />
          </button>
        </div>
        <div className="overflow-y-auto px-5 py-5 sm:px-6">{children}</div>
        {footer && (
          <div className="flex flex-col-reverse gap-2 border-t border-one-line px-5 py-4 sm:flex-row sm:justify-end sm:px-6">{footer}</div>
        )}
      </div>
    </div>
  )
}

interface DrawerProps {
  open: boolean
  onClose: () => void
  side?: 'left' | 'right'
  label: string
  children: ReactNode
  className?: string
}

export function Drawer({ open, onClose, side = 'right', label, children, className }: DrawerProps) {
  const panelRef = useRef<HTMLDivElement>(null)
  useDialog(open, onClose, panelRef)
  if (!open) return null
  return (
    <div className="fixed inset-0 z-[60]">
      <div aria-hidden className="absolute inset-0 animate-fade-in bg-one-hero/50 backdrop-blur-[2px]" onClick={onClose} />
      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-label={label}
        tabIndex={-1}
        className={cn(
          'absolute inset-y-0 flex w-[min(88vw,340px)] flex-col border-one-line bg-one-card shadow-overlay',
          side === 'left' ? 'left-0 animate-drawer-left border-r' : 'right-0 animate-drawer-right border-l',
          className,
        )}
      >
        {children}
      </div>
    </div>
  )
}
