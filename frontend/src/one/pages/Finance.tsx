import { useState } from 'react'
import { CalendarClock, CircleCheck, Copy, CreditCard, Landmark, Receipt, Smartphone, Wallet, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../i18n'
import { useMockData } from '../hooks/useMockData'
import { useApp } from '../store/app'
import { useToast } from '../store/toast'
import { invoices, tuitionBreakdown, type Invoice } from '../data/services'
import { student } from '../data/people'
import { daysUntil } from '../lib/dates'
import { Card, CardBody, CardHeader } from '../components/ui/Card'
import { Badge, StatusBadge } from '../components/ui/Badge'
import { Button } from '../components/ui/Button'
import { Async, EmptyState, StatsSkeleton, TableSkeleton } from '../components/ui/Feedback'
import { PageHeader, Progress, Stat } from '../components/ui/Display'
import { DataTable } from '../components/ui/DataTable'
import { Modal } from '../components/ui/Overlay'
import { FilterChips } from '../components/ui/Display'

type Method = 'va' | 'card' | 'ewallet'
const methodIcon: Record<Method, LucideIcon> = { va: Landmark, card: CreditCard, ewallet: Smartphone }

export function FinancePage() {
  const { t, fmt } = useI18n()
  const paidIds = useApp((s) => s.paid)
  const [paying, setPaying] = useState<Invoice | null>(null)
  const [filter, setFilter] = useState<'all' | 'paid' | 'unpaid'>('all')
  const q = useMockData(() => invoices, [] as Invoice[])

  return (
    <>
      <PageHeader title={t('finance.title')} description={t('finance.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('finance.title') }]} />
      <Async
        query={q}
        skeleton={<div className="space-y-6"><StatsSkeleton /><TableSkeleton /></div>}
        isEmpty={(d) => d.length === 0}
        empty={<Card><EmptyState icon={Receipt} title={t('states.emptyPayments')} body={t('states.emptyPaymentsBody')} /></Card>}
      >
        {(raw) => {
          const list = raw.map((i) => ({ ...i, status: paidIds.includes(i.id) ? ('paid' as const) : i.status }))
          const current = list.filter((i) => i.semester === student.semester)
          const tuition = current.reduce((a, i) => a + i.amount, 0)
          const paid = current.filter((i) => i.status === 'paid').reduce((a, i) => a + i.amount, 0)
          const outstanding = tuition - paid
          const next = list.filter((i) => i.status === 'unpaid').sort((a, b) => a.due.localeCompare(b.due))[0]
          const status = outstanding === 0 ? 'paid' : paid > 0 ? 'partial' : 'unpaid'
          const rows = list.filter((i) => filter === 'all' || i.status === filter)

          return (
            <div className="space-y-6">
              <div className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <Stat label={t('finance.tuitionFee')} value={fmt.compactCurrency(tuition)} icon={Wallet} tone="royal" hint={t('finance.semesterTuition', { n: student.semester })} />
                <Stat label={t('finance.outstanding')} value={fmt.compactCurrency(outstanding)} icon={Receipt} tone={outstanding ? 'warn' : 'ok'} hint={fmt.currency(outstanding)} />
                <Stat
                  label={t('finance.dueDate')}
                  value={next ? fmt.date(next.due, { day: 'numeric', month: 'short' }) : '—'}
                  icon={CalendarClock}
                  tone={next && daysUntil(next.due) <= 7 ? 'bad' : 'gold'}
                  hint={next ? t('common.daysLeft', { count: daysUntil(next.due) }) : undefined}
                />
                <Stat label={t('finance.paymentStatus')} value={<StatusBadge status={status} className="h-7 px-3 text-[13px]" />} icon={CircleCheck} tone={status === 'paid' ? 'ok' : 'warn'}>
                  <Progress value={paid} max={tuition || 1} tone="ok" label={t('finance.paidOf', { paid: fmt.currency(paid), total: fmt.currency(tuition) })} className="mt-3" />
                  <p className="mt-1.5 text-[12px] text-one-subtle">{t('finance.paidOf', { paid: fmt.compactCurrency(paid), total: fmt.compactCurrency(tuition) })}</p>
                </Stat>
              </div>

              <div className="grid gap-6 lg:grid-cols-3">
                {next ? (
                  <section aria-labelledby="np-h" className="relative overflow-hidden rounded-2xl bg-one-hero p-6 text-white shadow-lift lg:col-span-2">
                    <div aria-hidden className="one-cover-pattern absolute inset-0 opacity-25" />
                    <div className="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                      <div>
                        <p className="t-caption text-one-spark">{t('finance.nextPayment')}</p>
                        <h2 id="np-h" className="mt-2 font-display text-[30px] font-bold tabular sm:text-[36px]">{fmt.currency(next.amount)}</h2>
                        <p className="mt-1 text-white/80">{next.description}</p>
                        <p className="mt-3 inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[13px] ring-1 ring-inset ring-white/15">
                          <CalendarClock size={14} aria-hidden />
                          {t('finance.dueDate')}: {fmt.date(next.due, { weekday: 'long', day: 'numeric', month: 'long' })}
                        </p>
                      </div>
                      <Button variant="inverse" size="lg" icon={Wallet} onClick={() => setPaying(next)}>
                        {t('finance.payNow')}
                      </Button>
                    </div>
                  </section>
                ) : (
                  <Card className="lg:col-span-2"><EmptyState icon={CircleCheck} title={t('status.paid')} /></Card>
                )}
                <Card as="section" aria-labelledby="bd-h">
                  <CardHeader id="bd-h" title={t('finance.breakdown')} description={t('finance.semesterTuition', { n: student.semester })} />
                  <CardBody>
                    <dl className="space-y-3">
                      {tuitionBreakdown.map((b) => (
                        <div key={b.label} className="flex justify-between gap-3 text-[14px]">
                          <dt className="text-one-muted">{b.label}</dt>
                          <dd className="font-medium text-one-fg tabular">{fmt.currency(b.amount)}</dd>
                        </div>
                      ))}
                      <div className="flex justify-between gap-3 border-t border-one-line pt-3 text-[14.5px]">
                        <dt className="font-semibold text-one-fg">{t('finance.tuitionFee')}</dt>
                        <dd className="font-bold text-one-fg tabular">{fmt.currency(tuitionBreakdown.reduce((a, b) => a + b.amount, 0))}</dd>
                      </div>
                    </dl>
                  </CardBody>
                </Card>
              </div>

              <Card as="section" aria-labelledby="ph-h">
                <CardHeader
                  id="ph-h"
                  title={t('finance.paymentHistory')}
                  action={
                    <FilterChips
                      label={t('common.filter')}
                      value={filter}
                      onChange={setFilter}
                      className="hidden sm:flex"
                      options={[
                        { value: 'all', label: t('common.all') },
                        { value: 'unpaid', label: t('status.unpaid') },
                        { value: 'paid', label: t('status.paid') },
                      ]}
                    />
                  }
                />
                <div className="mt-4">
                  <DataTable
                    caption={t('finance.paymentHistory')}
                    rows={rows}
                    rowKey={(i) => i.id}
                    initialSort={{ key: 'date', dir: 'desc' }}
                    empty={<EmptyState compact icon={Receipt} title={t('states.emptyPayments')} body={t('states.emptyPaymentsBody')} />}
                    columns={[
                      { key: 'id', header: t('finance.invoice'), render: (i) => <span className="font-mono text-[13px]">{i.id}</span>, sortValue: (i) => i.id },
                      { key: 'desc', header: t('finance.description'), render: (i) => i.description },
                      { key: 'date', header: t('common.date'), render: (i) => fmt.date(i.date), sortValue: (i) => i.date },
                      { key: 'amount', header: t('finance.amount'), render: (i) => fmt.currency(i.amount), align: 'right', sortValue: (i) => i.amount },
                      { key: 'status', header: t('common.status'), render: (i) => <StatusBadge status={i.status} /> },
                      {
                        key: 'act',
                        header: t('common.actions'),
                        align: 'right',
                        hideOnMobile: false,
                        render: (i) =>
                          i.status === 'unpaid' ? (
                            <Button size="sm" onClick={() => setPaying(i)}>{t('finance.payNow')}</Button>
                          ) : (
                            <Button size="sm" variant="ghost" icon={Receipt}>{t('finance.receipt')}</Button>
                          ),
                      },
                    ]}
                  />
                </div>
              </Card>
            </div>
          )
        }}
      </Async>
      <PayModal invoice={paying} onClose={() => setPaying(null)} />
    </>
  )
}

function PayModal({ invoice, onClose }: { invoice: Invoice | null; onClose: () => void }) {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const markPaid = useApp((s) => s.add)
  const [method, setMethod] = useState<Method>('va')
  const [loading, setLoading] = useState(false)
  const va = `8808 ${student.studentId.slice(0, 4)} ${student.studentId.slice(4)} 0${invoice?.semester ?? 5}`

  return (
    <Modal
      open={!!invoice}
      onClose={onClose}
      title={t('finance.payTitle')}
      description={invoice ? `${invoice.id} · ${invoice.description}` : undefined}
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>{t('common.cancel')}</Button>
          <Button
            loading={loading}
            icon={CircleCheck}
            onClick={() => {
              setLoading(true)
              window.setTimeout(() => {
                if (invoice) markPaid('paid', invoice.id)
                setLoading(false)
                toast(t('finance.paySimulated'))
                onClose()
              }, 900)
            }}
          >
            {t('common.confirm')} · {invoice && fmt.currency(invoice.amount)}
          </Button>
        </>
      }
    >
      <fieldset>
        <legend className="mb-2 text-[13px] font-medium text-one-fg">{t('finance.method')}</legend>
        <div className="grid gap-2 sm:grid-cols-3">
          {(['va', 'card', 'ewallet'] as Method[]).map((m) => {
            const Icon = methodIcon[m]
            const on = method === m
            return (
              <label
                key={m}
                className={cn(
                  'flex cursor-pointer flex-col gap-2 rounded-2xl border p-3.5 transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-one-royal',
                  on ? 'border-one-royal bg-one-royal/5' : 'border-one-line hover:bg-one-sunken/50',
                )}
              >
                <input type="radio" name="method" value={m} checked={on} onChange={() => setMethod(m)} className="sr-only" />
                <Icon size={20} aria-hidden className={on ? 'text-one-royal' : 'text-one-subtle'} />
                <span className="text-[13px] font-medium text-one-fg">{t(`finance.methods.${m}`)}</span>
              </label>
            )
          })}
        </div>
      </fieldset>
      {method === 'va' && (
        <div className="mt-5 rounded-2xl bg-one-sunken/70 p-4">
          <p className="text-[12.5px] text-one-subtle">{t('finance.virtualAccount')}</p>
          <div className="mt-1 flex items-center justify-between gap-3">
            <p className="font-mono text-[18px] font-semibold tracking-wider text-one-fg">{va}</p>
            <Button
              size="sm"
              variant="secondary"
              icon={Copy}
              onClick={() => {
                void navigator.clipboard?.writeText(va.replace(/\s/g, ''))
                toast(t('common.copied'), 'info')
              }}
            >
              {t('common.copy')}
            </Button>
          </div>
        </div>
      )}
      <div className="mt-5 flex items-center gap-2">
        <Badge tone="sky">{t('app.demoBadge')}</Badge>
        <p className="text-[12.5px] text-one-subtle">{t('finance.paySimulated')}</p>
      </div>
    </Modal>
  )
}
