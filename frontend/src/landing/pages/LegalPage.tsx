import { Link } from 'react-router-dom'
import { ArrowLeft, FileClock, MessageSquare } from 'lucide-react'
import { fill, useLandingI18n } from '../i18n'
import { useDocumentMeta } from '../hooks/useDocumentSetup'
import { useLandingUi } from '../store/ui'
import { LANDING_PATHS } from '../paths'
import { Button } from '../components/ui/Button'

/**
 * Privacy Policy / Terms of Service. The official documents are not written
 * yet, so this page says exactly that instead of publishing invented legal text.
 */
export function LegalPage({ doc }: { doc: 'privacy' | 'terms' }) {
  const { m } = useLandingI18n()
  const openRequest = useLandingUi((s) => s.openRequest)
  const copy = m.legal[doc]
  useDocumentMeta({
    title: fill(m.meta.pageTitle, { page: copy.title }),
    description: copy.intro,
    ogLocale: m.meta.ogLocale,
    path: LANDING_PATHS[doc],
  })

  return (
    <section aria-labelledby="legal-title" className="relative overflow-hidden pb-24 pt-32 sm:pb-32 sm:pt-40">
      <div aria-hidden className="lp-grid-bg pointer-events-none absolute inset-x-0 top-0 h-96" />
      <div className="lp-container relative max-w-3xl">
        <Link to={LANDING_PATHS.home} className="inline-flex items-center gap-2 text-[14px] font-medium text-lp-muted transition-colors hover:text-lp-fg">
          <ArrowLeft size={16} aria-hidden />
          {m.legal.back}
        </Link>
        <p className="lp-eyebrow mt-10 text-lp-brand-text">{m.common.brand}</p>
        <h1 id="legal-title" className="lp-h2 mt-4 text-lp-fg">
          {copy.title}
        </h1>
        <p className="mt-5 inline-flex items-center gap-2 rounded-full border border-dashed border-lp-strong px-3 py-1 text-[13px] font-medium text-lp-muted">
          <FileClock size={15} aria-hidden className="text-lp-gold-text" />
          {m.legal.status}
        </p>
        <p className="lp-lead mt-8 text-lp-muted">{copy.intro}</p>
        <div className="mt-10 flex flex-col gap-4 rounded-lp-lg border border-lp-line bg-lp-surface p-6 sm:flex-row sm:items-center sm:justify-between">
          <p className="lp-small text-lp-muted">{m.legal.contact}</p>
          <Button variant="secondary" className="shrink-0" onClick={() => openRequest('contact')}>
            <MessageSquare size={16} aria-hidden />
            {m.faq.contactCta}
          </Button>
        </div>
      </div>
    </section>
  )
}
