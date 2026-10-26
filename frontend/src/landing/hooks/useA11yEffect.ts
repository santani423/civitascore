import { useLayoutEffect } from 'react'
import { useA11y } from '../store/a11y'

const ATTRS = ['data-lp', 'data-lp-text', 'data-lp-contrast', 'data-lp-font', 'data-lp-spacing', 'data-lp-colorsafe', 'data-lp-easy', 'data-lp-focus', 'data-lp-targets', 'data-lp-motion']

/**
 * Mirrors the preferences onto <html> as data attributes (the CSS in
 * styles/landing.css keys off them; dialogs portaled to <body> inherit them
 * too). Everything is removed when the landing unmounts, so the application
 * itself is never restyled. Default values set no attribute at all.
 */
export function useA11yEffect() {
  const prefs = useA11y()

  useLayoutEffect(() => {
    const html = document.documentElement
    const toggle = (name: string, value: string | null) => (value === null ? html.removeAttribute(name) : html.setAttribute(name, value))
    toggle('data-lp', '')
    toggle('data-lp-text', prefs.textSize === 'md' ? null : prefs.textSize)
    toggle('data-lp-contrast', prefs.contrast === 'normal' ? null : prefs.contrast)
    toggle('data-lp-font', prefs.font === 'default' ? null : prefs.font)
    toggle('data-lp-spacing', prefs.spacing === 'normal' ? null : prefs.spacing)
    toggle('data-lp-colorsafe', prefs.colorSafe ? 'on' : null)
    toggle('data-lp-easy', prefs.easyRead ? 'on' : null)
    toggle('data-lp-focus', prefs.strongFocus ? 'strong' : null)
    toggle('data-lp-targets', prefs.largeTargets ? 'large' : null)
    toggle('data-lp-motion', prefs.reduceMotion ? 'reduce' : null)
  }, [prefs.textSize, prefs.contrast, prefs.font, prefs.spacing, prefs.colorSafe, prefs.easyRead, prefs.strongFocus, prefs.largeTargets, prefs.reduceMotion])

  useLayoutEffect(
    () => () => {
      ATTRS.forEach((name) => document.documentElement.removeAttribute(name))
    },
    [],
  )
}
