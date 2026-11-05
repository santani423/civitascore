import type { ReactNode } from 'react'

/**
 * Halaman publik: kepala charcoal seperti hero halaman dalam landing (bila ada judul), lalu isi
 * selebar kontainer landing. `actions` tampil di atas charcoal — pakai buttonClass('primary' | 'bandOutline').
 */
export function Section({ title, description, children, actions }: { title?: string; description?: string; children: ReactNode; actions?: ReactNode }) {
  return (
    <>
      {(title || actions) && (
        <div className="lp-on-band relative isolate overflow-hidden bg-lp-band">
          <div aria-hidden className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
          <div aria-hidden className="pointer-events-none absolute -right-48 -top-48 -z-10 h-[30rem] w-[30rem] rounded-full bg-lp-accent/[0.12] blur-3xl" />
          <div className="lp-container flex flex-wrap items-end justify-between gap-6 py-10 sm:py-12 lg:py-14">
            <div className="max-w-3xl">
              <p className="lp-eyebrow text-lp-accent">Penerimaan Mahasiswa Baru</p>
              {title && <h1 className="lp-h2 mt-3 text-lp-band-fg">{title}</h1>}
              {description && <p className="lp-lead mt-3 text-lp-band-muted">{description}</p>}
            </div>
            {actions && <div className="flex flex-wrap gap-3">{actions}</div>}
          </div>
        </div>
      )}
      <section className="lp-container py-10 sm:py-12">{children}</section>
    </>
  )
}

/** Gaya isi HTML — dipakai bersama tampilan publik & RichTextEditor agar hasil editor sama dengan yang tampil. */
export const RICH_TEXT_CLASS = 'prose prose-sm max-w-none text-ink-primary [&_a]:text-primary [&_blockquote]:my-2 [&_blockquote]:border-l-4 [&_blockquote]:border-border-strong [&_blockquote]:pl-3 [&_blockquote]:text-ink-secondary [&_h2]:mt-4 [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:mt-3 [&_h3]:text-base [&_h3]:font-semibold [&_h4]:mt-3 [&_h4]:font-semibold [&_hr]:my-4 [&_hr]:border-border [&_li]:ml-5 [&_ol]:list-decimal [&_p]:my-2 [&_ul]:list-disc'

/** HTML konten yang SUDAH disanitasi server (HtmlSanitizer allowlist). */
export function RichText({ html }: { html: string | null | undefined }) {
  if (!html) return null

  return <div className={RICH_TEXT_CLASS} dangerouslySetInnerHTML={{ __html: html }} />
}
