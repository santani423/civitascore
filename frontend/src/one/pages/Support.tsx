import { useState } from 'react'
import {
  BedDouble,
  CircleQuestionMark,
  Clock,
  Compass,
  FileBadge,
  FileText,
  HeartPulse,
  IdCard,
  Laptop,
  MessageCircleHeart,
  MessagesSquare,
  PauseCircle,
  Send,
  type LucideIcon,
} from 'lucide-react'
import { useI18n, type TKey } from '../i18n'
import { useApp } from '../store/app'
import { useToast } from '../store/toast'
import { serviceCatalog, type ServiceKey } from '../data/services'
import { Card, CardHeader } from '../components/ui/Card'
import { StatusBadge } from '../components/ui/Badge'
import { Button } from '../components/ui/Button'
import { Accordion, IconTile, PageHeader } from '../components/ui/Display'
import { EmptyState } from '../components/ui/Feedback'
import { Field, SearchInput, Textarea } from '../components/ui/Form'
import { Modal } from '../components/ui/Overlay'
import { DataTable } from '../components/ui/DataTable'

const serviceIcon: Record<ServiceKey, LucideIcon> = {
  letter: FileText,
  transcript: FileBadge,
  leave: PauseCircle,
  idcard: IdCard,
  it: Laptop,
  counseling: MessageCircleHeart,
  health: HeartPulse,
  dorm: BedDouble,
}

export function ServicesPage() {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const requests = useApp((s) => s.requests)
  const addRequest = useApp((s) => s.addRequest)
  const [active, setActive] = useState<ServiceKey | null>(null)
  const [note, setNote] = useState('')
  const [error, setError] = useState<string>()
  const name = (k: string) => t(`services.items.${k}.title` as TKey)

  const close = () => {
    setActive(null)
    setNote('')
    setError(undefined)
  }

  return (
    <>
      <PageHeader title={t('services.title')} description={t('services.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('nav.services') }]} />
      <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {serviceCatalog.map((s) => (
          <li key={s.key}>
            <Card as="article" className="flex h-full flex-col p-5">
              <IconTile icon={serviceIcon[s.key]} tone="royal" size="lg" />
              <h2 className="mt-4 text-[15.5px] font-semibold text-one-fg">{name(s.key)}</h2>
              <p className="t-small mt-1 text-one-muted">{t(`services.items.${s.key}.desc` as TKey)}</p>
              <p className="mt-3 inline-flex items-center gap-1.5 text-[12.5px] text-one-subtle">
                <Clock size={13} aria-hidden /> {t('services.eta', { value: s.eta })}
              </p>
              <div className="mt-auto pt-4">
                <Button variant="secondary" size="sm" block onClick={() => setActive(s.key)}>{t('services.request')}</Button>
              </div>
            </Card>
          </li>
        ))}
      </ul>

      <Card as="section" aria-labelledby="req-h" className="mt-8">
        <CardHeader id="req-h" title={t('services.myRequests')} />
        <div className="mt-4">
          <DataTable
            caption={t('services.myRequests')}
            rows={requests}
            rowKey={(r) => r.id}
            empty={<EmptyState compact icon={FileText} title={t('states.emptyGeneric')} />}
            columns={[
              { key: 's', header: t('nav.services'), render: (r) => <span className="font-medium">{name(r.service)}</span> },
              { key: 'n', header: t('services.reason'), render: (r) => <span className="text-one-muted">{r.note}</span> },
              { key: 'd', header: t('common.date'), render: (r) => fmt.date(r.createdAt), sortValue: (r) => r.createdAt },
              { key: 'st', header: t('common.status'), render: (r) => <StatusBadge status={r.status} /> },
            ]}
          />
        </div>
      </Card>

      <Modal
        open={!!active}
        onClose={close}
        title={active ? t('services.requestTitle', { name: name(active) }) : ''}
        description={active ? t(`services.items.${active}.desc` as TKey) : undefined}
        footer={
          <>
            <Button variant="secondary" onClick={close}>{t('common.cancel')}</Button>
            <Button
              icon={Send}
              onClick={() => {
                if (note.trim().length < 5) {
                  setError(t('validation.required'))
                  return
                }
                if (active) {
                  addRequest(active, note.trim())
                  toast(t('services.requestedToast', { name: name(active) }))
                }
                close()
              }}
            >
              {t('common.submit')}
            </Button>
          </>
        }
      >
        <Field label={t('services.reason')} error={error} required>
          <Textarea value={note} onChange={(e) => setNote(e.target.value)} />
        </Field>
      </Modal>
    </>
  )
}

export function HelpPage() {
  const { t } = useI18n()
  const toast = useToast()
  const [query, setQuery] = useState('')
  const faqs = ([1, 2, 3, 4, 5] as const).map((n) => ({
    id: `q${n}`,
    title: t(`help.faqs.q${n}`),
    content: t(`help.faqs.a${n}`),
  }))
  const list = faqs.filter((f) => `${f.title} ${f.content}`.toLowerCase().includes(query.toLowerCase()))

  return (
    <>
      <PageHeader title={t('help.title')} description={t('help.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('help.title') }]} />
      <SearchInput size="lg" value={query} onValueChange={setQuery} label={t('help.searchPlaceholder')} placeholder={t('help.searchPlaceholder')} className="mb-8 max-w-2xl" clearLabel={t('common.clearFilters')} />
      <div className="grid gap-6 lg:grid-cols-3">
        <section aria-labelledby="faq-h" className="lg:col-span-2">
          <h2 id="faq-h" className="t-h2 mb-4 text-one-fg">{t('help.faq')}</h2>
          {list.length ? (
            <Accordion key={query} items={list} />
          ) : (
            <Card><EmptyState icon={CircleQuestionMark} title={t('states.emptySearch', { query })} body={t('states.emptySearchBody')} /></Card>
          )}
        </section>
        <Card padded className="self-start">
          <IconTile icon={MessagesSquare} tone="teal" size="lg" />
          <h2 className="t-h3 mt-4 text-one-fg">{t('help.contact')}</h2>
          <p className="t-small mt-1 text-one-muted">{t('help.contactDesc')}</p>
          <Button className="mt-5" block icon={MessagesSquare} onClick={() => toast(t('help.chatToast'), 'info')}>{t('help.chat')}</Button>
          <Button className="mt-2" block variant="secondary" to="/one/services">{t('nav.services')}</Button>
        </Card>
      </div>
    </>
  )
}

export function NotFoundPage() {
  const { t } = useI18n()
  return (
    <Card>
      <EmptyState icon={Compass} title="404" body={t('states.emptyGeneric')} action={<Button to="/one">{t('states.goToDashboard')}</Button>} />
    </Card>
  )
}
