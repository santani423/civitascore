import { useEffect, useRef } from 'react'
import { useSite } from '@/pmb/components/SiteContext'

declare global {
  interface Window {
    turnstile?: { render: (el: HTMLElement, options: Record<string, unknown>) => string; reset: (id?: string) => void }
  }
}

/**
 * CAPTCHA (Cloudflare Turnstile) bila tenant/instansi mengaktifkannya
 * (config pmb.captcha); tanpa driver → tidak dirender dan backend tidak
 * mensyaratkan token (lingkungan pengembangan).
 */
export function Captcha({ onToken }: { onToken: (token: string | null) => void }) {
  const site = useSite()
  const ref = useRef<HTMLDivElement>(null)
  const driver = site?.captcha.driver
  const siteKey = site?.captcha.site_key

  useEffect(() => {
    if (driver !== 'turnstile' || !siteKey || !ref.current) return
    const el = ref.current
    const render = () => window.turnstile?.render(el, { sitekey: siteKey, callback: (token: string) => onToken(token), 'expired-callback': () => onToken(null) })

    if (window.turnstile) {
      render()

      return
    }

    const script = document.createElement('script')
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js'
    script.async = true
    script.onload = render
    document.head.appendChild(script)
  }, [driver, siteKey, onToken])

  if (driver !== 'turnstile') return null

  return <div ref={ref} className="min-h-[65px]" />
}
