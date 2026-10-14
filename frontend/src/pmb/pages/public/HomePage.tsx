import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { CalendarClock, GraduationCap, Route as RouteIcon } from 'lucide-react'
import { pmbPublic } from '@/pmb/api/public'
import { useLoad } from '@/pmb/components/useLoad'
import { useSite } from '@/pmb/components/SiteContext'
import { Section } from '@/pmb/components/Section'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { countdown, date, dateTime, money } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'

/** PUB-01 Beranda: gelombang aktif + hitung mundur, jalur, prodi, berita, FAQ ringkas. */
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

  const waves = (periods.data ?? []).flatMap((p) => p.waves.map((w) => ({ ...w, period: p.name })))
  const openWave = waves.find((w) => w.status === 'open')
  const nextWave = waves.find((w) => w.status === 'scheduled')

  return (
    <>
      <div className="bg-gradient-to-br from-primary-700 to-primary-900 text-white">
        <div className="mx-auto max-w-6xl px-4 py-14">
          <p className="text-sm uppercase tracking-wider text-white/70">Penerimaan Mahasiswa Baru</p>
          <h1 className="mt-2 text-3xl font-bold sm:text-4xl">{site?.university.name ?? 'Penerimaan Mahasiswa Baru'}</h1>
          {openWave ? (
            <div className="mt-6 inline-flex flex-col gap-1 rounded-xl bg-white/10 p-4">
              <span className="text-sm text-white/80">{openWave.name} ({openWave.period}) ditutup dalam</span>
              <span className="font-mono text-2xl font-semibold">{openWave.registration_closes_at ? countdown(openWave.registration_closes_at, now) : '—'}</span>
            </div>
          ) : nextWave ? (
            <p className="mt-6 text-white/90">Pendaftaran {nextWave.name} dibuka {dateTime(nextWave.registration_opens_at)}.</p>
          ) : (
            !periods.loading && <p className="mt-6 text-white/90">Pendaftaran belum dibuka. Pantau jadwal pada halaman Jadwal.</p>
          )}
          <div className="mt-6 flex flex-wrap gap-3">
            <Link to={PMB.register} className="rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-primary-800">Daftar Sekarang</Link>
            <Link to={PMB.programs} className="rounded-lg border border-white/60 px-5 py-2.5 text-sm font-semibold text-white">Lihat Program Studi</Link>
          </div>
        </div>
      </div>

      {site?.banners && site.banners.length > 0 && (
        <Section>
          <div className="grid gap-3 sm:grid-cols-2">
            {site.banners.map((b) => <Card key={b.id} title={b.title}><div className="text-sm text-ink-secondary" dangerouslySetInnerHTML={{ __html: b.body ?? '' }} /></Card>)}
          </div>
        </Section>
      )}

      <Section title="Gelombang Pendaftaran">
        {waves.length === 0 ? (
          <p className="text-sm text-ink-secondary">{periods.loading ? 'Memuat...' : 'Belum ada gelombang yang diumumkan.'}</p>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {waves.map((w) => (
              <Card key={w.id}>
                <div className="flex items-start justify-between gap-2">
                  <div>
                    <p className="font-semibold text-ink-primary">{w.name}</p>
                    <p className="text-xs text-ink-tertiary">{w.period}</p>
                  </div>
                  <Badge variant={w.status === 'open' ? 'success' : 'neutral'}>{w.status === 'open' ? 'Dibuka' : w.status === 'scheduled' ? 'Segera' : 'Ditutup'}</Badge>
                </div>
                <ul className="mt-3 space-y-1 text-sm text-ink-secondary">
                  <li className="flex gap-2"><CalendarClock className="size-4" /> {date(w.registration_opens_at)} – {date(w.registration_closes_at)}</li>
                  <li>Pengumuman: {date(w.announcement_at)}</li>
                </ul>
                <div className="mt-3 flex flex-wrap gap-1">
                  {w.paths.map((p) => <Badge key={p.code} variant="info">{p.name} · {money(p.fee)}</Badge>)}
                </div>
              </Card>
            ))}
          </div>
        )}
      </Section>

      <Section title="Jalur Masuk" actions={<Link to={PMB.paths} className="text-sm text-primary hover:underline">Semua jalur</Link>}>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {(paths.data ?? []).map((p) => (
            <Link key={p.code} to={to(PMB.path, { code: p.code.toLowerCase() })}>
              <Card className="h-full transition-shadow hover:shadow-md">
                <RouteIcon className="size-5 text-primary" />
                <p className="mt-2 font-semibold text-ink-primary">{p.name}</p>
                <p className="mt-1 line-clamp-3 text-sm text-ink-secondary">{p.description}</p>
                {p.open_waves.length > 0 && <p className="mt-2 text-xs text-primary">Dibuka · tutup {date(p.open_waves[0].closes_at)}</p>}
              </Card>
            </Link>
          ))}
        </div>
      </Section>

      <Section title="Program Studi" actions={<Link to={PMB.programs} className="text-sm text-primary hover:underline">Semua prodi</Link>}>
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {(programs.data ?? []).slice(0, 6).map((p) => (
            <Link key={p.slug} to={to(PMB.program, { slug: p.slug })}>
              <Card className="h-full transition-shadow hover:shadow-md">
                <GraduationCap className="size-5 text-primary" />
                <p className="mt-2 font-semibold text-ink-primary">{p.name}</p>
                <p className="text-xs text-ink-tertiary">{p.degree_level} · {p.faculty}</p>
                <p className="mt-1 line-clamp-2 text-sm text-ink-secondary">{p.summary}</p>
              </Card>
            </Link>
          ))}
        </div>
      </Section>

      <div className="mx-auto grid max-w-6xl gap-6 px-4 pb-10 lg:grid-cols-2">
        <Card title="Berita Terbaru" actions={<Link to={PMB.news} className="text-sm text-primary">Semua</Link>}>
          <ul className="divide-y divide-border">
            {(news.data ?? []).slice(0, 4).map((n) => (
              <li key={n.id} className="py-2"><Link to={to(PMB.newsDetail, { slug: n.slug })} className="text-sm font-medium text-ink-primary hover:text-primary">{n.title}</Link><p className="text-xs text-ink-tertiary">{date(n.published_at)}</p></li>
            ))}
            {news.data?.length === 0 && <li className="py-2 text-sm text-ink-secondary">Belum ada berita.</li>}
          </ul>
        </Card>
        <Card title="Pertanyaan Umum" actions={<Link to={PMB.faq} className="text-sm text-primary">Semua FAQ</Link>}>
          <div className="space-y-2">
            {(faq.data ?? []).slice(0, 5).map((f) => (
              <details key={f.id} className="rounded-lg border border-border p-2"><summary className="cursor-pointer text-sm font-medium text-ink-primary">{f.title}</summary><div className="mt-2 text-sm text-ink-secondary" dangerouslySetInnerHTML={{ __html: f.body ?? '' }} /></details>
            ))}
            {faq.data?.length === 0 && <p className="text-sm text-ink-secondary">Belum ada FAQ.</p>}
          </div>
        </Card>
      </div>
    </>
  )
}
