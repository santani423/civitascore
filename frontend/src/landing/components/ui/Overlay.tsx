import { useEffect, useId, useRef, type ReactNode, type RefObject } from 'react'
import { createPortal } from 'react-dom'
import { X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { IconButton } from './Button'

const FOCUSABLE =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'

/**
 * Modal behavior shared by Dialog and Drawer: focus moves in and is
 * trapped, Escape closes, page scroll locks, focus returns to the opener.
 */
function useModal(open: boolean, onClose: () => void, panelRef: RefObject<HTMLElement | null>) {
  const onCloseRef = useRef(onClose)
  useEffect(() => {
    onCloseRef.current = onClose
  })

  useEffect(() => {
    if (!open) return
    const opener = document.activeElement as HTMLElement | null
    const panel = panelRef.current
    const first = panel?.querySelector<HTMLElement>('[data-autofocus]') ?? panel?.querySelector<HTMLElement>(FOCUSABLE)
    ;(first ?? panel)?.focus()

    const { overflow, paddingRight } = document.body.style
    const scrollbar = window.innerWidth - document.documentElement.clientWidth
    document.body.style.overflow = 'hidden'
    if (scrollbar > 0) document.body.style.paddingRight = `${scrollbar}px`

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
      document.body.style.overflow = overflow
      document.body.style.paddingRight = paddingRight
      opener?.focus?.({ preventScroll: true })
    }
  }, [open, panelRef])
}

/** Renders outside the page tree (so section transforms can't trap it) but inside the landing token scope. */
function LandingPortal({ children }: { children: ReactNode }) {
  const { locale } = useLandingI18n()
  return createPortal(
    <div className="lp-root" lang={locale}>
      {children}
    </div>,
    document.body,
  )
}

interface DialogProps {
  open: boolean
  onClose: () => void
  title: ReactNode
  description?: ReactNode
  children: ReactNode
  footer?: ReactNode
  size?: 'md' | 'lg'
  icon?: ReactNode
}

export function Dialog({ open, onClose, title, description, children, footer, size = 'md', icon }: DialogProps) {
  const { m } = useLandingI18n()
  const panelRef = useRef<HTMLDivElement>(null)
  const titleId = useId()
  const descId = useId()
  useModal(open, onClose, panelRef)
  if (!open) return null

  return (
    <LandingPortal>
      <div className="fixed inset-0 z-[80] flex items-end justify-center sm:items-center sm:p-6">
        <div aria-hidden className="absolute inset-0 animate-fade-in bg-lp-scrim/55 backdrop-blur-[3px]" onClick={onClose} />
        <div
          ref={panelRef}
          role="dialog"
          aria-modal="true"
          aria-labelledby={titleId}
          aria-describedby={description ? descId : undefined}
          tabIndex={-1}
          className={cn(
            'relative flex max-h-[92dvh] w-full animate-rise-in flex-col rounded-t-lp-xl border border-lp-line bg-lp-elev shadow-lp-xl focus:outline-none sm:rounded-lp-xl',
            size === 'md' ? 'sm:max-w-lg' : 'sm:max-w-2xl',
          )}
        >
          <div className="flex items-start gap-4 border-b border-lp-line px-5 py-5 sm:px-7">
            {icon}
            <div className="min-w-0 flex-1">
              <h2 id={titleId} className="lp-h3 text-lp-fg">
                {title}
              </h2>
              {description && (
                <div id={descId} className="lp-small mt-1.5 text-lp-muted">
                  {description}
                </div>
              )}
            </div>
            <IconButton onClick={onClose} aria-label={m.common.close} className="-mr-2 -mt-1">
              <X size={18} aria-hidden />
            </IconButton>
          </div>
          <div className="overflow-y-auto overscroll-contain px-5 py-6 sm:px-7">{children}</div>
          {footer && <div className="flex flex-col-reverse gap-2 border-t border-lp-line px-5 py-4 sm:flex-row sm:justify-end sm:px-7">{footer}</div>}
        </div>
      </div>
    </LandingPortal>
  )
}

interface DrawerProps {
  open: boolean
  onClose: () => void
  label: string
  id?: string
  children: ReactNode
}

export function Drawer({ open, onClose, label, id, children }: DrawerProps) {
  const panelRef = useRef<HTMLDivElement>(null)
  useModal(open, onClose, panelRef)
  if (!open) return null
  return (
    <LandingPortal>
      <div className="fixed inset-0 z-[70]">
        <div aria-hidden className="absolute inset-0 animate-fade-in bg-lp-scrim/50 backdrop-blur-[2px]" onClick={onClose} />
        <div
          ref={panelRef}
          id={id}
          role="dialog"
          aria-modal="true"
          aria-label={label}
          tabIndex={-1}
          className="absolute inset-y-0 right-0 flex w-[min(90vw,380px)] animate-drawer-right flex-col border-l border-lp-line bg-lp-canvas shadow-lp-xl focus:outline-none"
        >
          {children}
        </div>
      </div>
    </LandingPortal>
  )
}
