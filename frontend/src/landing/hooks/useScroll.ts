import { useEffect, useState } from 'react'

/** True when the page is scrolled past `offset` px (drives the navbar surface). */
export function useScrolled(offset = 8): boolean {
  const [scrolled, setScrolled] = useState(() => typeof window !== 'undefined' && window.scrollY > offset)

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > offset)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [offset])

  return scrolled
}

/** Id of the section currently crossing the middle band of the viewport (scrollspy). */
export function useActiveSection(ids: readonly string[]): string | null {
  const [active, setActive] = useState<string | null>(null)
  const key = ids.join('|')

  useEffect(() => {
    const els = key
      .split('|')
      .map((id) => document.getElementById(id))
      .filter((el): el is HTMLElement => el !== null)
    if (els.length === 0 || typeof IntersectionObserver === 'undefined') return
    const io = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) if (entry.isIntersecting) setActive(entry.target.id)
      },
      { rootMargin: '-40% 0px -55% 0px' },
    )
    els.forEach((el) => io.observe(el))
    return () => io.disconnect()
  }, [key])

  return active
}

export function prefersReducedMotion(): boolean {
  return typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

/**
 * Smooth-scrolls to an in-page section, updates the hash without adding a
 * history entry, and moves focus there so screen readers follow along.
 */
export function scrollToSection(id: string): boolean {
  const el = document.getElementById(id)
  if (!el) return false
  el.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' })
  window.history.replaceState(window.history.state, '', `${window.location.pathname}${window.location.search}#${id}`)
  if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '-1')
  el.setAttribute('data-section-focus', '')
  el.focus({ preventScroll: true })
  return true
}
