import type { ReactNode } from 'react'

/** Bagian halaman publik dengan lebar konten standar. */
export function Section({ title, description, children, actions }: { title?: string; description?: string; children: ReactNode; actions?: ReactNode }) {
  return (
    <section className="mx-auto w-full max-w-6xl px-4 py-8">
      {(title || actions) && (
        <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
          <div>
            {title && <h1 className="text-2xl font-semibold text-ink-primary">{title}</h1>}
            {description && <p className="mt-1 text-sm text-ink-secondary">{description}</p>}
          </div>
          {actions}
        </div>
      )}
      {children}
    </section>
  )
}

/** Gaya isi HTML — dipakai bersama tampilan publik & RichTextEditor agar hasil editor sama dengan yang tampil. */
export const RICH_TEXT_CLASS = 'prose prose-sm max-w-none text-ink-primary [&_a]:text-primary [&_blockquote]:my-2 [&_blockquote]:border-l-4 [&_blockquote]:border-border-strong [&_blockquote]:pl-3 [&_blockquote]:text-ink-secondary [&_h2]:mt-4 [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:mt-3 [&_h3]:text-base [&_h3]:font-semibold [&_h4]:mt-3 [&_h4]:font-semibold [&_hr]:my-4 [&_hr]:border-border [&_li]:ml-5 [&_ol]:list-decimal [&_p]:my-2 [&_ul]:list-disc'

/** HTML konten yang SUDAH disanitasi server (HtmlSanitizer allowlist). */
export function RichText({ html }: { html: string | null | undefined }) {
  if (!html) return null

  return <div className={RICH_TEXT_CLASS} dangerouslySetInnerHTML={{ __html: html }} />
}
