import { useState, type ReactNode } from 'react'
import { Link, useParams } from 'react-router-dom'
import { ArrowRight, BookOpenText, CalendarDays, Clock, Mail, MapPin, MessageCircle, MessageSquare, Newspaper, Phone, Search, type LucideIcon } from 'lucide-react'
import { buttonClass } from '@/landing/components/ui/buttonClass'
import { pmbPublic } from '@/pmb/api/public'
import { useLoad } from '@/pmb/components/useLoad'
import { RichText, Section } from '@/pmb/components/Section'
import { useSite } from '@/pmb/components/SiteContext'
import { FaqItem } from '@/pmb/components/site'
import { cardClass, linkClass } from '@/pmb/components/siteClass'
import { Card } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { date } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'

const TITLES: Record<string, [string, string]> = {
  faq: ['Pertanyaan Umum (FAQ)', 'Jawaban atas pertanyaan yang sering diajukan.'],
  news: ['Berita & Informasi', 'Kabar terbaru seputar penerimaan mahasiswa baru.'],
  guide: ['Panduan Pendaftaran', 'Langkah-langkah mendaftar secara daring.'],
}

const muted = 'lp-small text-lp-muted'
const iconTile = 'flex size-11 shrink-0 items-center justify-center rounded-lp bg-lp-brand-soft text-lp-brand-text'
/** Wadah bacaan untuk isi HTML (berita, halaman, dokumen legal). */
const articleClass = cardClass(false, 'mx-auto max-w-3xl p-6 sm:p-10')

/** PUB-07/08/09 daftar konten per tipe (FAQ berbentuk akordeon dengan pencarian). */
export function ContentListPage({ type }: { type: 'faq' | 'news' | 'guide' }) {
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const { data, loading } = useLoad(() => pmbPublic.contents(type, { search: query || undefined }), [type, query])
  const [title, description] = TITLES[type]
  const categories = [...new Set((data ?? []).map((c) => c.category ?? 'Umum'))]
  const Icon = type === 'news' ? Newspaper : BookOpenText

  return (
    <Section title={title} description={description}>
      <form role="search" className="mb-8 max-w-xl" onSubmit={(e) => { e.preventDefault(); setQuery(search) }}>
        <Input placeholder="Cari..." aria-label={`Cari ${title.toLowerCase()}`} value={search} onChange={(e) => setSearch(e.target.value)} leftIcon={<Search className="size-4" />} />
      </form>
      {loading && <p className={muted}>Memuat...</p>}
      {data?.length === 0 && <p className={muted}>Belum ada konten.{type === 'faq' && <> Punya pertanyaan? <Link to={PMB.contact} className={linkClass}>Hubungi panitia</Link>.</>}</p>}
      {type === 'faq' ? (
        <div className="max-w-3xl">
          {categories.map((category) => (
            <div key={category} className="mb-8 last:mb-0">
              <h2 className="lp-eyebrow mb-3 text-lp-brand-text">{category}</h2>
              <div className="space-y-2.5">
                {(data ?? []).filter((c) => (c.category ?? 'Umum') === category).map((c) => <FaqItem key={c.id} title={c.title} body={c.body} />)}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <ul className="grid gap-5 sm:grid-cols-2">
          {(data ?? []).map((c) => (
            <li key={c.id} className="flex">
              <Link to={type === 'news' ? to(PMB.newsDetail, { slug: c.slug }) : to(PMB.page, { slug: `${type}--${c.slug}` })} className={cardClass(true, 'flex w-full flex-col p-6')}>
                <div className="flex items-start gap-4">
                  <span aria-hidden className={iconTile}><Icon size={20} /></span>
                  <div className="min-w-0">
                    {c.published_at && <p className="flex items-center gap-1.5 text-[0.7813rem] text-lp-subtle"><CalendarDays size={13} aria-hidden />{date(c.published_at)}</p>}
                    <h2 className="lp-h4 mt-1 text-lp-fg group-hover:text-lp-brand-text">{c.title}</h2>
                  </div>
                </div>
                {c.excerpt && <p className="lp-small mt-3 line-clamp-3 text-lp-muted">{c.excerpt}</p>}
                <span aria-hidden className="mt-auto inline-flex items-center gap-1.5 pt-5 text-[0.8438rem] font-semibold text-lp-brand-text">
                  Baca selengkapnya
                  <ArrowRight size={15} className="transition-transform group-hover:translate-x-0.5" />
                </span>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </Section>
  )
}

/** Detail berita / halaman / panduan. Slug halaman "tipe--slug" untuk tipe selain news. */
export function ContentDetailPage({ type: fixedType }: { type?: string }) {
  const { slug = '' } = useParams()
  const [type, realSlug] = fixedType ? [fixedType, slug] : slug.includes('--') ? slug.split('--', 2) : ['page', slug]
  const { data, error, loading } = useLoad(() => pmbPublic.content(type, realSlug), [type, realSlug])

  if (error?.status === 404) return <Section title="Halaman tidak ditemukan"><Link to={PMB.home} className={linkClass}>Kembali ke beranda</Link></Section>
  if (loading || !data) return <Section><p className={muted}>Memuat...</p></Section>

  return (
    <Section title={data.title} description={data.publish_at ? date(data.publish_at) : undefined}>
      <article className={articleClass}><RichText html={data.body} /></article>
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
      {loading && site ? <p className={muted}>Memuat...</p> : data ? <article className={articleClass}><RichText html={data.body} /></article> : <p className={muted}>Dokumen belum dipublikasikan.</p>}
    </Section>
  )
}

/** PUB-11 Info daftar ulang (konten re_registration_info). */
export function ReRegistrationInfoPage() {
  const { data, loading } = useLoad(() => pmbPublic.contents('re_registration_info'), [])

  return (
    <Section title="Informasi Daftar Ulang" description="Tata cara daftar ulang bagi pendaftar yang diterima." actions={<Link to={PMB.login} className={buttonClass('primary', 'md')}>Masuk portal<ArrowRight size={16} aria-hidden /></Link>}>
      {loading && <p className={muted}>Memuat...</p>}
      <div className="space-y-4">
        {(data ?? []).map((c) => <Card key={c.id} title={c.title}><DetailBody type="re_registration_info" slug={c.slug} /></Card>)}
      </div>
      {data?.length === 0 && <p className={muted}>Informasi daftar ulang belum dipublikasikan.</p>}
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
    <Section title="Kontak Panitia PMB" description="Hubungi panitia untuk pertanyaan seputar pendaftaran.">
      <div className="grid gap-5 md:grid-cols-2">
        <div className={cardClass(false, 'p-4 sm:p-6')}>
          <ul className="space-y-1">
            {c.address && <ContactRow icon={MapPin} label="Alamat">{c.address}</ContactRow>}
            {c.phone && <ContactRow icon={Phone} label="Telepon"><a className={linkClass} href={`tel:${c.phone}`}>{c.phone}</a></ContactRow>}
            {c.whatsapp && <ContactRow icon={MessageCircle} label="WhatsApp"><a className={linkClass} href={`https://wa.me/${c.whatsapp.replace(/\D/g, '')}`} target="_blank" rel="noopener">{c.whatsapp}</a></ContactRow>}
            {c.email && <ContactRow icon={Mail} label="Email"><a className={`${linkClass} break-all`} href={`mailto:${c.email}`}>{c.email}</a></ContactRow>}
            {c.hours && <ContactRow icon={Clock} label="Jam layanan">{c.hours}</ContactRow>}
            {!c.address && !c.phone && !c.email && <li className={`${muted} p-3`}>Kontak panitia belum diatur.</li>}
          </ul>
          <p className="mt-4 flex items-start gap-2.5 rounded-lp-sm bg-lp-sunken px-3.5 py-3 text-[0.8438rem] leading-relaxed text-lp-muted">
            <MessageSquare size={17} aria-hidden className="mt-0.5 shrink-0 text-lp-brand-text" />
            <span>Sudah punya akun? Kirim pertanyaan lewat <Link to={PMB.portal.messages} className={linkClass}>Pesan di portal</Link>.</span>
          </p>
        </div>
        {c.map_url && <iframe title="Peta lokasi" src={c.map_url} className="h-72 w-full rounded-lp-lg border border-lp-line shadow-lp-sm md:h-full md:min-h-72" loading="lazy" />}
      </div>
    </Section>
  )
}

function ContactRow({ icon: Icon, label, children }: { icon: LucideIcon; label: string; children: ReactNode }) {
  return (
    <li className="flex items-start gap-3.5 rounded-lp-sm px-3 py-3">
      <span aria-hidden className="flex size-9 shrink-0 items-center justify-center rounded-full border border-lp-brand/25 text-lp-brand-text">
        <Icon size={16} />
      </span>
      <span className="min-w-0 text-[0.9063rem] text-lp-fg">
        <span className="block text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-lp-subtle">{label}</span>
        {children}
      </span>
    </li>
  )
}
