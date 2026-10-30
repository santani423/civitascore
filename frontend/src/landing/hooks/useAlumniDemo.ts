import { useEffect, useState } from 'react'
import { fill, useLandingI18n } from '../i18n'
import { daysUntil } from '../data/alumni'

/** Shows the skeleton briefly on arrival, standing in for the fetch this module will do once it has an API. */
export function useDemoLoading(delay = 420): boolean {
  const [loading, setLoading] = useState(true)
  useEffect(() => {
    const timer = window.setTimeout(() => setLoading(false), delay)
    return () => window.clearTimeout(timer)
  }, [delay])
  return loading
}

/** "Hari ini" / "Besok" / "3 hari lagi". */
export function useCountdown() {
  const { m } = useLandingI18n()
  return (date: Date) => {
    const days = daysUntil(date)
    if (days <= 0) return m.alumni.events.today
    if (days === 1) return m.alumni.events.tomorrow
    return fill(m.alumni.events.inDays, { n: days })
  }
}
