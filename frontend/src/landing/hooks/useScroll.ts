import { useEffect, useState, type RefObject } from 'react'
import { useLocation } from 'react-router-dom'

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

/**
 * For a sideways-scrolling list of section links (sticky section navs on
 * phones): keeps the item marked `aria-current` scrolled into view, and
 * returns to the start when none is marked (e.g. back up in the hero).
 */
export function useKeepActiveInView(listRef: RefObject<HTMLElement | null>, active: string | null) {
  useEffect(() => {
    const list = listRef.current
    if (!list || list.scrollWidth <= list.clientWidth) return
    const item = list.querySelector<HTMLElement>('[aria-current]')
    const left = item ? item.offsetLeft - (list.clientWidth - item.offsetWidth) / 2 : 0
    list.scrollTo({ left, behavior: prefersReducedMotion() ? 'auto' : 'smooth' })
  }, [listRef, active])
}

/** Arriving at page#section (shared link, or a link from another landing page): jump there once rendered. */
export function useScrollToHash() {
  const { hash } = useLocation()
  useEffect(() => {
    if (!hash) return
    const frame = window.requestAnimationFrame(() => document.getElementById(decodeURIComponent(hash.slice(1)))?.scrollIntoView({ block: 'start' }))
    return () => window.cancelAnimationFrame(frame)
  }, [hash])
}

/** The OS preference, or the landing's own "Kurangi animasi" setting (mirrored on <html> by useA11yEffect). */
export function prefersReducedMotion(): boolean {
  return (
    typeof window !== 'undefined' &&
    (window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.getAttribute('data-lp-motion') === 'reduce')
  )
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
