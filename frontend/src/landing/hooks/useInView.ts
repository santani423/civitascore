import { useEffect, useRef, useState } from 'react'

/** True once the element has scrolled into view (never flips back). */
export function useInView<T extends Element>(options: IntersectionObserverInit = { threshold: 0.12, rootMargin: '0px 0px -8% 0px' }) {
  const ref = useRef<T>(null)
  const [inView, setInView] = useState(() => typeof IntersectionObserver === 'undefined')
  const { threshold, rootMargin } = options

  useEffect(() => {
    const el = ref.current
    if (!el || inView) return
    const io = new IntersectionObserver(
      (entries) => {
        if (entries.some((e) => e.isIntersecting)) {
          setInView(true)
          io.disconnect()
        }
      },
      { threshold, rootMargin },
    )
    io.observe(el)
    return () => io.disconnect()
  }, [inView, threshold, rootMargin])

  return [ref, inView] as const
}
