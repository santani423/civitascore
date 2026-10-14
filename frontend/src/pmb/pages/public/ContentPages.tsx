import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Mail, MapPin, MessageCircle, Phone, Clock } from 'lucide-react'
import { pmbPublic } from '@/pmb/api/public'
import { useLoad } from '@/pmb/components/useLoad'
import { RichText, Section } from '@/pmb/components/Section'
import { useSite } from '@/pmb/components/SiteContext'
import { Card } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { date } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'

const TITLES: Record<string, [string, string]> = {
  faq: ['Pertanyaan Umum (FAQ)', 'Jawaban atas pertanyaan yang sering diajukan.'],
  news: ['Berita & Informasi', 'Kabar terbaru seputar penerimaan mahasiswa baru.'],
  guide: ['Panduan Pendaftaran', 'Langkah-langkah mendaftar secara daring.'],
}

/** PUB-07/08/09 daftar konten per tipe (FAQ berbentuk akordeon dengan pencarian). */
export function ContentListPage({ type }: { type: 'faq' | 'news' | 'guide' }) {
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const { data, loading } = useLoad(() => pmbPublic.contents(type, { search: query || undefined }), [type, query])
  const [title, description] = TITLES[type]
  const categories = [...new Set((data ?? []).map((c) => c.category ?? 'Umum'))]

  return (
    <Section title={title} description={description}>
      <form className="mb-5 flex gap-2" onSubmit={(e) => { e.preventDefault(); setQuery(search) }}>
        <Input placeholder="Cari..." value={search} onChange={(e) => setSearch(e.target.value)} />
      </form>
      {loading && <p className="text-sm text-ink-secondary">Memuat...</p>}
      {data?.length === 0 && <p className="text-sm text-ink-secondary">Belum ada konten.{type === 'faq' && <> Punya pertanyaan? <Link to={PMB.contact} className="text-primary">Hubungi panitia</Link>.</>}</p>}
      {type === 'faq' ? (
        categories.map((category) => (
          <div key={category} className="mb-5">
            <h2 className="mb-2 text-sm font-semibold uppercase tracking-wide text-ink-tertiary">{category}</h2>
            <div className="space-y-2">
              {(data ?? []).filter((c) => (c.category ?? 'Umum') === category).map((c) => (
                <details key={c.id} className="rounded-lg border border-border bg-surface p-3"><summary className="cursor-pointer font-medium text-ink-primary">{c.title}</summary><RichText html={c.body} /></details>
              ))}
            </div>
          </div>
        ))
      ) : (
        <div className="grid gap-4 sm:grid-cols-2">
          {(data ?? []).map((c) => (
            <Link key={c.id} to={type === 'news' ? to(PMB.newsDetail, { slug: c.slug }) : to(PMB.page, { slug: `${type}--${c.slug}` })}>
              <Card className="h-full transition-shadow hover:shadow-md">
                <p className="font-semibold text-ink-primary">{c.title}</p>
                {c.published_at && <p className="text-xs text-ink-tertiary">{date(c.published_at)}</p>}
                <p className="mt-2 line-clamp-3 text-sm text-ink-secondary">{c.excerpt}</p>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </Section>
  )
}

/** Detail berita / halaman / panduan. Slug halaman "tipe--slug" untuk tipe selain news. */
export function ContentDetailPage({ type: fixedType }: { type?: string }) {
  const { slug = '' } = useParams()
  const [type, realSlug] = fixedType ? [fixedType, slug] : slug.includes('--') ? slug.split('--', 2) : ['page', slug]
  const { data, error, loading } = useLoad(() => pmbPublic.content(type, realSlug), [type, realSlug])

  if (error?.status === 404) return <Section title="Halaman tidak ditemukan"><Link to={PMB.home} className="text-primary">Kembali ke beranda</Link></Section>
  if (loading || !data) return <Section><p className="text-sm text-ink-secondary">Memuat...</p></Section>

  return (
    <Section title={data.title} description={data.publish_at ? date(data.publish_at) : undefined}>
      <Card><RichText html={data.body} /></Card>
    </Section>
  )
}

/** PUB-14 Kebijakan privasi & ketentuan (versi aktif). */
export function LegalPage() {
  const { type = 'privacy_policy' } = useParams()
  const site = useSite()
  const meta = type === 'terms' ? site?.legal.terms : site?.legal.privacy_policy
  const { data, loading } = useLoad(() => (meta ? pmbPublic.content(type, meta.slug) : Promise.resolve(null)), [type, meta?.slug])

  return (
    <Section title={meta?.title ?? (type === 'terms' ? 'Ketentuan Pendaftaran' : 'Kebijakan Privasi')} description={meta ? `Versi ${meta.version}` : undefined}>
      {loading && site ? <p className="text-sm text-ink-secondary">Memuat...</p> : data ? <Card><RichText html={data.body} /></Card> : <p className="text-sm text-ink-secondary">Dokumen belum dipublikasikan.</p>}
    </Section>
  )
}

/** PUB-11 Info daftar ulang (konten re_registration_info). */
export function ReRegistrationInfoPage() {
  const { data, loading } = useLoad(() => pmbPublic.contents('re_registration_info'), [])

  return (
    <Section title="Informasi Daftar Ulang" description="Tata cara daftar ulang bagi pendaftar yang diterima." actions={<Link to={PMB.login} className="rounded-lg bg-primary px-4 py-2 text-sm text-white">Masuk portal</Link>}>
      {loading && <p className="text-sm text-ink-secondary">Memuat...</p>}
      {(data ?? []).map((c) => <Card key={c.id} title={c.title} className="mb-4"><DetailBody type="re_registration_info" slug={c.slug} /></Card>)}
      {data?.length === 0 && <p className="text-sm text-ink-secondary">Informasi daftar ulang belum dipublikasikan.</p>}
    </Section>
  )
}

function DetailBody({ type, slug }: { type: string; slug: string }) {
  const { data } = useLoad(() => pmbPublic.content(type, slug), [type, slug])

  return <RichText html={data?.body} />
}

/** PUB-12 Kontak panitia. */
export function ContactPage() {
  const site = useSite()
  const c = site?.contact ?? {}

  return (
    <Section title="Kontak Panitia PMB">
      <div className="grid gap-4 md:grid-cols-2">
        <Card>
          <ul className="space-y-3 text-sm text-ink-primary">
            {c.address && <li className="flex gap-2"><MapPin className="size-4 text-primary" />{c.address}</li>}
            {c.phone && <li className="flex gap-2"><Phone className="size-4 text-primary" /><a href={`tel:${c.phone}`}>{c.phone}</a></li>}
            {c.whatsapp && <li className="flex gap-2"><MessageCircle className="size-4 text-primary" /><a href={`https://wa.me/${c.whatsapp.replace(/\D/g, '')}`} target="_blank" rel="noopener">{c.whatsapp}</a></li>}
            {c.email && <li className="flex gap-2"><Mail className="size-4 text-primary" /><a href={`mailto:${c.email}`}>{c.email}</a></li>}
            {c.hours && <li className="flex gap-2"><Clock className="size-4 text-primary" />{c.hours}</li>}
            {!c.address && !c.phone && !c.email && <li className="text-ink-secondary">Kontak panitia belum diatur.</li>}
          </ul>
          <p className="mt-4 text-sm text-ink-secondary">Sudah punya akun? Kirim pertanyaan lewat <Link to={PMB.portal.messages} className="text-primary">Pesan di portal</Link>.</p>
        </Card>
        {c.map_url && <iframe title="Peta lokasi" src={c.map_url} className="h-72 w-full rounded-xl border border-border" loading="lazy" />}
      </div>
    </Section>
  )
}
