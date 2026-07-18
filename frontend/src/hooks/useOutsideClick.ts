import { useEffect, type RefObject } from 'react'

export function useOutsideClick(ref: RefObject<HTMLElement | null>, onOutsideClick: () => void, active = true): void {
  useEffect(() => {
    if (!active) return

    function handlePointerDown(event: PointerEvent) {
      if (ref.current && !ref.current.contains(event.target as Node)) {
        onOutsideClick()
      }
    }

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        onOutsideClick()
      }
    }

    document.addEventListener('pointerdown', handlePointerDown)
    document.addEventListener('keydown', handleKeyDown)

    return () => {
      document.removeEventListener('pointerdown', handlePointerDown)
      document.removeEventListener('keydown', handleKeyDown)
    }
  }, [ref, onOutsideClick, active])
}
