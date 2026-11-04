import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, CalendarClock, CalendarDays, GraduationCap, Megaphone, MessageSquare, Newspaper, Route as RouteIcon, type LucideIcon } from 'lucide-react'
import { buttonClass } from '@/landing/components/ui/buttonClass'
import { pmbPublic, type PublicWave } from '@/pmb/api/public'
import { useLoad } from '@/pmb/components/useLoad'
import { useSite } from '@/pmb/components/SiteContext'
import { RichText } from '@/pmb/components/Section'
import { Chip, FaqItem, MoreLink, SectionHeading, WaveStatus } from '@/pmb/components/site'
import { cardClass } from '@/pmb/components/siteClass'
import { countdown, date, dateTime, money } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'
import { cn } from '@/utils/cn'

type Wave = PublicWave & { period: string }

const iconTile = 'flex size-11 shrink-0 items-center justify-center rounded-lp bg-lp-brand-soft text-lp-brand-text'

/** PUB-01 Beranda: gelombang aktif + hitung mundur, jalur, prodi, berita, FAQ ringkas — bergaya landing page. */
export function HomePage() {
  const site = useSite()
  const periods = useLoad(() => pmbPublic.periods(), [])
  const programs = useLoad(() => pmbPublic.programs(), [])
  const paths = useLoad(() => pmbPublic.paths(), [])
  const news = useLoad(() => pmbPublic.contents('news'), [])
  const faq = useLoad(() => pmbPublic.contents('faq'), [])
  const [now, setNow] = useState(() => Date.now())

  useEffect(() => {
    const timer = window.setInterval(() => setNow(Date.now()), 1000)

    return () => window.clearInterval(timer)
  }, [])

  const waves: Wave[] = (periods.data ?? []).flatMap((p) => p.waves.map((w) => ({ ...w, period: p.name })))
  const openWave = waves.find((w) => w.status === 'open')
  const nextWave = waves.find((w) => w.status === 'scheduled')
  const name = site?.university.name
  const stats = [
    { value: programs.data?.length, label: 'Program studi' },
    { value: paths.data?.length, label: 'Jalur masuk' },
    { value: periods.data ? waves.filter((w) => w.status === 'open').length : undefined, label: 'Gelombang dibuka' },
  ].filter((s) => s.value !== undefined)

  return (
    <>
      <section aria-labelledby="pmb-hero-title" className="lp-on-band relative isolate overflow-hidden bg-lp-band">
        <div aria-hidden className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
        <div aria-hidden className="pointer-events-none absolute -right-40 -top-40 -z-10 h-[36rem] w-[36rem] rounded-full bg-lp-accent/[0.14] blur-3xl" />
        <div aria-hidden className="lp-sweep-br absolute -bottom-px right-0 -z-10 hidden h-24 w-48 bg-lp-accent md:block lg:h-32 lg:w-64" />

        <div className="lp-container grid gap-10 pb-20 pt-14 sm:pt-16 lg:grid-cols-12 lg:items-center lg:gap-12 lg:pb-28 lg:pt-20">
          <div className="lg:col-span-7">
            <p className="lp-eyebrow text-lp-accent">Penerimaan Mahasiswa Baru</p>
            <h1 id="pmb-hero-title" className="lp-display mt-4 text-lp-band-fg">
              {name ? <>Daftar Kuliah di <span className="text-lp-accent">{name}</span></> : <>Penerimaan <span className="text-lp-accent">Mahasiswa Baru</span></>}
            </h1>
            <p className="lp-lead mt-6 max-w-xl text-lp-band-muted">
              Pilih program studi dan jalur masuk, lengkapi formulir serta dokumen, lalu pantau setiap tahap seleksi hingga daftar ulang — semuanya secara daring.
            </p>
            <div className="mt-9 flex flex-col gap-3 min-[420px]:flex-row min-[420px]:flex-wrap">
              <Link to={PMB.register} className={buttonClass('primary', 'lg')}>
                Daftar Sekarang
                <ArrowRight size={18} aria-hidden />
              </Link>
              <Link to={PMB.programs} className={buttonClass('bandOutline', 'lg')}>Lihat Program Studi</Link>
            </div>
            {stats.length > 0 && (
              <dl className="mt-10 flex flex-wrap gap-x-10 gap-y-5">
                {stats.map((stat) => (
                  <div key={stat.label} className="flex flex-col-reverse">
                    <dt className="mt-1 text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-band-muted">{stat.label}</dt>
                    <dd className="lp-tabular font-display text-[2rem] font-extrabold leading-none text-lp-band-fg">{stat.value}</dd>
                  </div>
                ))}
              </dl>
            )}
          </div>

          <div className="lg:col-span-5">
            <WaveSpotlight open={openWave} next={nextWave} loading={periods.loading} now={now} />
          </div>
        </div>
      </section>

      {site?.banners && site.banners.length > 0 && (
        <section aria-label="Pengumuman" className="bg-lp-canvas pt-12 sm:pt-14">
          <div className="lp-container">
            <ul className="grid gap-4 sm:grid-cols-2">
              {site.banners.map((b) => (
                <li key={b.id} className={cardClass(false, 'relative flex gap-4 overflow-hidden p-5 sm:p-6')}>
                  <span aria-hidden className="absolute inset-y-0 left-0 w-1 bg-lp-brand" />
                  <span aria-hidden className={iconTile}><Megaphone size={20} /></span>
                  <div className="min-w-0">
                    <h2 className="lp-h4 text-lp-fg">{b.title}</h2>
                    <div className="lp-small mt-1 text-lp-muted"><RichText html={b.body} /></div>
                  </div>
                </li>
              ))}
            </ul>
          </div>
        </section>
      )}

      <section aria-labelledby="pmb-waves-title" className="lp-section bg-lp-canvas">
        <div className="lp-container">
          <SectionHeading
            id="pmb-waves-title"
            eyebrow="Jadwal Pendaftaran"
            title="Gelombang Pendaftaran"
            description="Setiap gelombang memiliki jalur, biaya, dan tanggal pengumuman masing-masing."
            action={<MoreLink to={PMB.schedule}>Jadwal lengkap</MoreLink>}
          />
          {waves.length === 0 ? (
            <Placeholder icon={CalendarClock} text={periods.loading ? 'Memuat gelombang...' : 'Belum ada gelombang yang diumumkan.'} />
          ) : (
            <ul className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {waves.map((w) => (
                <li key={w.id} className={cardClass(false, 'relative flex flex-col overflow-hidden p-6')}>
                  {w.status === 'open' && <span aria-hidden className="absolute inset-x-0 top-0 h-1 bg-lp-brand" />}
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <h3 className="lp-h3 text-lp-fg">{w.name}</h3>
                      <p className="mt-0.5 text-[0.8125rem] text-lp-subtle">{w.period}</p>
                    </div>
                    <WaveStatus status={w.status} />
                  </div>
                  <ul className="mt-5 space-y-3 text-[0.875rem]">
                    <DateRow icon={CalendarClock} label="Pendaftaran" value={`${date(w.registration_opens_at)} – ${date(w.registration_closes_at)}`} />
                    <DateRow icon={Megaphone} label="Pengumuman" value={date(w.announcement_at)} />
                  </ul>
                  {w.paths.length > 0 && (
                    <div className="mt-auto pt-5">
                      <ul className="flex flex-wrap gap-1.5 border-t border-lp-line pt-4">
                        {w.paths.map((p) => <li key={p.code}><Chip>{p.name} · {money(p.fee)}</Chip></li>)}
                      </ul>
                    </div>
                  )}
                </li>
              ))}
            </ul>
          )}
        </div>
      </section>

      <section aria-labelledby="pmb-paths-title" className="lp-section lp-warm">
        <div className="lp-container">
          <SectionHeading
            id="pmb-paths-title"
            eyebrow="Jalur Masuk"
            title="Pilih jalur yang sesuai"
            description="Setiap jalur memiliki persyaratan dokumen dan komponen seleksi tersendiri."
            action={<MoreLink to={PMB.paths}>Semua jalur</MoreLink>}
          />
          <ul className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {(paths.data ?? []).map((p) => (
              <li key={p.code} className="flex">
                <Link to={to(PMB.path, { code: p.code.toLowerCase() })} className={cardClass(true, 'flex w-full flex-col p-6')}>
                  <span aria-hidden className={iconTile}><RouteIcon size={20} /></span>
                  <h3 className="lp-h4 mt-4 text-lp-fg group-hover:text-lp-brand-text">{p.name}</h3>
                  <p className="lp-small mt-2 line-clamp-3 text-lp-muted">{p.description}</p>
                  <p className={cn('mt-auto pt-5 text-[0.8125rem] font-semibold', p.open_waves.length > 0 ? 'text-lp-ok' : 'text-lp-subtle')}>
                    {p.open_waves.length > 0 ? `Dibuka · tutup ${date(p.open_waves[0].closes_at)}` : 'Belum dibuka'}
                  </p>
                </Link>
              </li>
            ))}
          </ul>
          {paths.data?.length === 0 && <Placeholder icon={RouteIcon} text="Belum ada jalur yang dipublikasikan." />}
        </div>
      </section>

      <section aria-labelledby="pmb-programs-title" className="lp-section bg-lp-canvas">
        <div className="lp-container">
          <SectionHeading
            id="pmb-programs-title"
            eyebrow="Program Studi"
            title="Temukan program studimu"
            description="Lihat profil, akreditasi, prospek karier, dan rincian biaya setiap program studi."
            action={<MoreLink to={PMB.programs}>Semua prodi</MoreLink>}
          />
          <ul className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {(programs.data ?? []).slice(0, 6).map((p) => (
              <li key={p.slug} className="flex">
                <Link to={to(PMB.program, { slug: p.slug })} className={cardClass(true, 'flex w-full flex-col p-6')}>
                  <span aria-hidden className={iconTile}><GraduationCap size={20} /></span>
                  <h3 className="lp-h4 mt-4 text-lp-fg group-hover:text-lp-brand-text">{p.name}</h3>
                  <p className="mt-1 text-[0.8125rem] font-medium text-lp-subtle">{p.degree_level}{p.faculty && ` · ${p.faculty}`}</p>
                  <p className="lp-small mt-2 line-clamp-2 text-lp-muted">{p.summary}</p>
                  <span aria-hidden className="mt-auto inline-flex items-center gap-1.5 pt-5 text-[0.8438rem] font-semibold text-lp-brand-text">
                    Lihat profil
                    <ArrowRight size={15} className="transition-transform group-hover:translate-x-0.5" />
                  </span>
                </Link>
              </li>
            ))}
          </ul>
          {programs.data?.length === 0 && <Placeholder icon={GraduationCap} text="Belum ada program studi yang membuka pendaftaran." />}
        </div>
      </section>

      <section aria-label="Berita dan pertanyaan umum" className="lp-section lp-warm">
        <div className="lp-container grid gap-14 lg:grid-cols-2 lg:gap-12">
          <div>
            <SectionHeading id="pmb-news-title" eyebrow="Kabar Terbaru" title="Berita & Informasi" action={<MoreLink to={PMB.news}>Semua berita</MoreLink>} />
            {(news.data ?? []).length > 0 ? (
              <ul className={cardClass(false, 'mt-8 divide-y divide-lp-line overflow-hidden')}>
                {(news.data ?? []).slice(0, 4).map((n) => (
                  <li key={n.id}>
                    <Link to={to(PMB.newsDetail, { slug: n.slug })} className="group flex items-start gap-4 p-5 transition-colors hover:bg-lp-sunken">
                      <span aria-hidden className={iconTile}><Newspaper size={18} /></span>
                      <span className="min-w-0 flex-1">
                        <span className="flex items-center gap-1.5 text-[0.7813rem] text-lp-subtle"><CalendarDays size={13} aria-hidden />{date(n.published_at)}</span>
                        <span className="lp-h4 mt-1 block text-lp-fg group-hover:text-lp-brand-text">{n.title}</span>
                      </span>
                      <ArrowRight size={16} aria-hidden className="mt-1 shrink-0 text-lp-subtle transition-transform group-hover:translate-x-0.5 group-hover:text-lp-brand-text" />
                    </Link>
                  </li>
                ))}
              </ul>
            ) : (
              <Placeholder icon={Newspaper} text={news.loading ? 'Memuat berita...' : 'Belum ada berita.'} />
            )}
          </div>
          <div>
            <SectionHeading id="pmb-faq-title" eyebrow="Bantuan" title="Pertanyaan Umum" action={<MoreLink to={PMB.faq}>Semua FAQ</MoreLink>} />
            {(faq.data ?? []).length > 0 ? (
              <div className="mt-8 space-y-2.5">
                {(faq.data ?? []).slice(0, 5).map((f) => <FaqItem key={f.id} title={f.title} body={f.body} />)}
              </div>
            ) : (
              <Placeholder icon={MessageSquare} text={faq.loading ? 'Memuat FAQ...' : 'Belum ada FAQ.'} />
            )}
          </div>
        </div>
      </section>

      <section aria-labelledby="pmb-cta-title" className="bg-lp-canvas py-16 sm:py-20 lg:py-24">
        <div className="lp-container">
          <div className="lp-on-brand relative isolate overflow-hidden rounded-lp-xl bg-lp-brand px-6 py-10 shadow-lp-lg sm:px-10 sm:py-12 lg:px-14">
            {/* Dekoratif, seperti CtaBanner landing; dijauhkan dari kolom teks agar kontras teks putih tetap ≥ 4.5:1. */}
            <div aria-hidden className="absolute inset-y-0 right-0 -z-10 hidden w-[28%] bg-gradient-to-l from-lp-accent/80 to-transparent lg:block" />
            <div aria-hidden className="absolute -right-24 -top-24 -z-10 h-80 w-80 rounded-full border-[28px] border-white/10 lg:-right-10" />
            <div aria-hidden className="absolute -bottom-32 right-40 -z-10 h-64 w-64 rounded-full border-[18px] border-white/[0.07]" />

            <div className="flex flex-col gap-10 lg:flex-row lg:items-center lg:justify-between">
              <div className="max-w-2xl">
                <h2 id="pmb-cta-title" className="lp-h2 text-lp-onbrand">Siap memulai pendaftaran?</h2>
                <p className="lp-lead mt-4 text-lp-onbrand">Buat akun pendaftar, lengkapi formulir, dan pantau hasil seleksi dari satu portal.</p>
                <div className="mt-8 flex flex-col gap-3 min-[420px]:flex-row min-[420px]:flex-wrap">
                  <Link to={PMB.register} className={buttonClass('band', 'lg')}>
                    Buat Akun Pendaftar
                    <ArrowRight size={18} aria-hidden />
                  </Link>
                  <Link to={PMB.contact} className={buttonClass('bandOutline', 'lg')}>
                    <MessageSquare size={17} aria-hidden />
                    Hubungi Panitia
                  </Link>
                </div>
              </div>
              <div aria-hidden className="hidden shrink-0 lg:block">
                <span className="flex size-40 items-center justify-center rounded-full bg-white shadow-lp-xl ring-8 ring-white/20">
                  {site?.university.logo_url ? <img src={site.university.logo_url} alt="" className="size-24 object-contain" /> : <GraduationCap size={64} className="text-lp-brand" />}
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  )
}

/** Panel hero: gelombang yang sedang dibuka (hitung mundur) atau yang berikutnya. */
function WaveSpotlight({ open, next, loading, now }: { open?: Wave; next?: Wave; loading: boolean; now: number }) {
  const wave = open ?? next

  return (
    <div className="rounded-lp-xl border border-lp-band-line bg-lp-band-elev p-6 shadow-lp-xl sm:p-8">
      {wave ? (
        <>
          <div className="flex items-center justify-between gap-3">
            <p className="lp-eyebrow text-lp-band-muted">{open ? 'Pendaftaran dibuka' : 'Segera dibuka'}</p>
            <span aria-hidden className={cn('size-2.5 rounded-full', open ? 'bg-lp-accent ring-[6px] ring-lp-accent/20' : 'bg-lp-band-muted')} />
          </div>
          <p className="lp-h3 mt-3 text-lp-band-fg">{wave.name}</p>
          <p className="text-[0.875rem] text-lp-band-muted">{wave.period}</p>

          <p className="mt-6 text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-band-muted">{open ? 'Ditutup dalam' : 'Dibuka pada'}</p>
          <p className="lp-tabular mt-1.5 font-display text-[2rem] font-extrabold leading-tight text-lp-accent sm:text-[2.25rem]">
            {open ? (open.registration_closes_at ? countdown(open.registration_closes_at, now) : '—') : dateTime(wave.registration_opens_at)}
          </p>

          <dl className="mt-6 grid grid-cols-2 gap-4 border-t border-lp-band-line pt-5 text-[0.875rem]">
            <div>
              <dt className="text-lp-band-muted">Penutupan</dt>
              <dd className="mt-0.5 font-semibold text-lp-band-fg">{date(wave.registration_closes_at)}</dd>
            </div>
            <div>
              <dt className="text-lp-band-muted">Pengumuman</dt>
              <dd className="mt-0.5 font-semibold text-lp-band-fg">{date(wave.announcement_at)}</dd>
            </div>
          </dl>
          {wave.paths.length > 0 && (
            <p className="mt-5 text-[0.8125rem] text-lp-band-muted">
              Jalur: <span className="text-lp-band-fg">{wave.paths.map((p) => p.name).join(', ')}</span>
            </p>
          )}
        </>
      ) : (
        <>
          <p className="lp-eyebrow text-lp-band-muted">Jadwal</p>
          <p className="lp-h3 mt-3 text-lp-band-fg">{loading ? 'Memuat jadwal...' : 'Pendaftaran belum dibuka'}</p>
          {!loading && <p className="lp-small mt-2 text-lp-band-muted">Pantau jadwal gelombang berikutnya pada halaman Jadwal.</p>}
          <Link to={PMB.schedule} className={buttonClass('bandOutline', 'md', 'mt-6')}>
            Lihat jadwal
            <ArrowRight size={16} aria-hidden />
          </Link>
        </>
      )}
    </div>
  )
}

function DateRow({ icon: Icon, label, value }: { icon: LucideIcon; label: string; value: string }) {
  return (
    <li className="flex gap-3">
      <Icon size={16} aria-hidden className="mt-0.5 shrink-0 text-lp-brand-text" />
      <span>
        <span className="block text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-lp-subtle">{label}</span>
        <span className="text-lp-fg">{value}</span>
      </span>
    </li>
  )
}

function Placeholder({ icon: Icon, text }: { icon: LucideIcon; text: string }) {
  return (
    <div className="mt-8 flex items-center gap-4 rounded-lp border border-dashed border-lp-strong bg-lp-surface p-6">
      <span aria-hidden className={iconTile}><Icon size={20} /></span>
      <p className="lp-small text-lp-muted">{text}</p>
    </div>
  )
}
