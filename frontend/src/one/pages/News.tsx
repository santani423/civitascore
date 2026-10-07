import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { ArrowLeft, CalendarDays, Clock, Megaphone, Newspaper, Share2, User } from 'lucide-react'
import { useI18n, type TKey } from '../i18n'
import { useMockData } from '../hooks/useMockData'
import { useToast } from '../store/toast'
import { announcements, news, newsById, type Article, type NewsCategory } from '../data/campus'
import { Card, CardBody, CardHeader } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import { Button } from '../components/ui/Button'
import { Async, EmptyState, NewsSkeleton } from '../components/ui/Feedback'
import { Cover, FilterChips, MetaItem, PageHeader } from '../components/ui/Display'
import { BookmarkButton, NewsCard, SectionTitle, newsIcon } from '../components/shared/bits'

const cats: NewsCategory[] = ['campus', 'academic', 'research', 'student', 'international', 'career', 'events', 'achievement']
const annTone = { academic: 'royal', finance: 'warn', exam: 'bad', campus: 'teal' } as const
const annLabel: Record<keyof typeof annTone, TKey> = {
  academic: 'notifCategory.academic',
  finance: 'notifCategory.finance',
  exam: 'notifCategory.exam',
  campus: 'newsCategory.campus',
}

export function NewsPage() {
  const { t, fmt } = useI18n()
  const [cat, setCat] = useState<'all' | NewsCategory>('all')
  const q = useMockData(() => news, [] as Article[])
  const list = q.data.filter((n) => cat === 'all' || n.category === cat)

  return (
    <>
      <PageHeader title={t('news.title')} description={t('news.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('nav.announcements') }]} />
      <FilterChips
        className="mb-6"
        label={t('common.category')}
        value={cat}
        onChange={setCat}
        options={[{ value: 'all', label: t('common.all') }, ...cats.map((c) => ({ value: c, label: t(`newsCategory.${c}`) }))]}
      />
      <Async
        query={q}
        skeleton={<NewsSkeleton />}
        isEmpty={() => list.length === 0}
        empty={<Card><EmptyState icon={Newspaper} title={t('states.emptyNews')} body={t('states.emptyNewsBody')} action={<Button variant="secondary" onClick={() => setCat('all')}>{t('common.clearFilters')}</Button>} /></Card>}
      >
        {() => {
          const [lead, ...rest] = list
          const side = rest.slice(0, 3)
          const grid = rest.slice(3)
          return (
            <div className="space-y-10">
              <section aria-label={t('news.featured')} className="grid gap-6 lg:grid-cols-5">
                <article className="group relative overflow-hidden rounded-3xl lg:col-span-3">
                  <Cover tone={lead.tone} icon={newsIcon[lead.category]} className="aspect-[16/10] min-h-[320px] transition-transform duration-500 group-hover:scale-[1.015]" />
                  <div aria-hidden className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent" />
                  <div className="absolute inset-x-0 bottom-0 p-6 text-white sm:p-8">
                    <div className="flex items-center gap-2">
                      <Badge tone="neutral" className="bg-one-spark text-one-ink ring-0">{t('news.featured')}</Badge>
                      <Badge tone="neutral" className="bg-white/90 text-one-ink ring-0">{t(`newsCategory.${lead.category}`)}</Badge>
                    </div>
                    <h2 className="t-h1 mt-3 text-white">
                      <Link to={`/one/news/${lead.id}`} className="after:absolute after:inset-0 hover:underline">{lead.title}</Link>
                    </h2>
                    <p className="mt-2 line-clamp-2 max-w-2xl text-white/80">{lead.excerpt}</p>
                    <p className="mt-3 text-[13px] text-white/70">{fmt.date(lead.date)} · {t('news.readTime', { count: lead.readTime })}</p>
                  </div>
                </article>
                <div className="flex flex-col gap-4 lg:col-span-2">
                  <p className="t-caption text-one-subtle">{t('news.latest')}</p>
                  {side.map((a) => <NewsCard key={a.id} article={a} layout="row" />)}
                </div>
              </section>

              {grid.length > 0 && (
                <section aria-labelledby="more-h">
                  <SectionTitle id="more-h" title={t('news.latest')} />
                  <ul className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    {grid.map((a) => <li key={a.id}><NewsCard article={a} /></li>)}
                  </ul>
                </section>
              )}

              <Card as="section" aria-labelledby="off-h">
                <CardHeader id="off-h" title={t('news.announcements')} icon={<span className="flex h-9 w-9 items-center justify-center rounded-xl bg-one-gold/10 text-one-gold"><Megaphone size={17} aria-hidden /></span>} />
                <CardBody>
                  <ul className="grid gap-3 md:grid-cols-2">
                    {announcements.map((a) => (
                      <li key={a.id} className="rounded-2xl border border-one-line p-4">
                        <div className="flex items-center justify-between gap-2">
                          <Badge tone={annTone[a.category]}>{t(annLabel[a.category])}</Badge>
                          <time dateTime={a.date} className="text-[12px] text-one-subtle">{fmt.date(a.date)}</time>
                        </div>
                        <h3 className="mt-2.5 font-semibold text-one-fg">{a.title}</h3>
                        <p className="t-small mt-1 text-one-muted">{a.body}</p>
                      </li>
                    ))}
                  </ul>
                </CardBody>
              </Card>
            </div>
          )
        }}
      </Async>
    </>
  )
}

export function NewsDetailPage() {
  const { id = '' } = useParams()
  const { t, fmt } = useI18n()
  const toast = useToast()
  const article = newsById(id)
  const q = useMockData(() => article ?? null, null)
  if (!article) {
    return <Card><EmptyState icon={Newspaper} title={t('news.notFound')} action={<Button variant="secondary" to="/one/news" icon={ArrowLeft}>{t('news.backToNews')}</Button>} /></Card>
  }
  // Same-category stories first, then the most recent others.
  const related = news
    .filter((n) => n.id !== article.id)
    .sort((a, b) => Number(b.category === article.category) - Number(a.category === article.category))
    .slice(0, 3)

  return (
    <Async query={q} skeleton={<NewsSkeleton />}>
      {() => (
        <article className="mx-auto max-w-4xl">
          <Link to="/one/news" className="mb-5 inline-flex items-center gap-1.5 rounded text-[13px] font-medium text-one-subtle hover:text-one-fg">
            <ArrowLeft size={15} aria-hidden /> {t('news.backToNews')}
          </Link>
          <Badge tone="royal">{t(`newsCategory.${article.category}`)}</Badge>
          <h1 className="t-display mt-3 text-one-fg">{article.title}</h1>
          <p className="mt-3 text-[17px] leading-relaxed text-one-muted">{article.excerpt}</p>
          <div className="mt-5 flex flex-wrap items-center justify-between gap-4 border-y border-one-line py-3">
            <div className="flex flex-wrap gap-x-5 gap-y-1">
              <MetaItem icon={User}>{t('news.by', { author: article.author })}</MetaItem>
              <MetaItem icon={CalendarDays}>{fmt.date(article.date, { day: 'numeric', month: 'long', year: 'numeric' })}</MetaItem>
              <MetaItem icon={Clock}>{t('news.readTime', { count: article.readTime })}</MetaItem>
            </div>
            <div className="flex items-center gap-1">
              <BookmarkButton id={article.id} />
              <Button
                variant="ghost"
                size="sm"
                icon={Share2}
                onClick={() => {
                  void navigator.clipboard?.writeText(window.location.href)
                  toast(t('common.linkCopied'), 'info')
                }}
              >
                {t('common.share')}
              </Button>
            </div>
          </div>
          <Cover tone={article.tone} icon={newsIcon[article.category]} className="mt-6 aspect-[21/9] rounded-3xl" label={article.title} />
          <div className="mx-auto mt-8 max-w-2xl space-y-5">
            {article.body.map((p, i) => (
              <p key={i} className={i === 0 ? 'text-[18px] leading-relaxed text-one-fg first-letter:float-left first-letter:mr-2 first-letter:font-display first-letter:text-[52px] first-letter:font-bold first-letter:leading-[0.9] first-letter:text-one-royal' : 'text-[16.5px] leading-relaxed text-one-muted'}>
                {p}
              </p>
            ))}
          </div>
          <section aria-labelledby="rel-h" className="mt-14">
            <SectionTitle id="rel-h" title={t('news.related')} />
            <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {related.map((a) => <li key={a.id}><NewsCard article={a} /></li>)}
            </ul>
          </section>
        </article>
      )}
    </Async>
  )
}
