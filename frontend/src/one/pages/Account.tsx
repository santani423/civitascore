import { useState, type ReactNode } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import {
  Accessibility,
  Bell,
  BellOff,
  Camera,
  CheckCheck,
  Database,
  FileText,
  KeyRound,
  Laptop,
  Mail,
  MailOpen,
  Monitor,
  Moon,
  Pencil,
  Phone,
  ShieldCheck,
  Smartphone,
  SlidersHorizontal,
  Sun,
  Upload,
  User,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { LANGS, useI18n } from '../i18n'
import { useMockData } from '../hooks/useMockData'
import { useNotifications } from '../hooks/useShell'
import { useApp } from '../store/app'
import { usePrefs, type DataState, type FontScale, type ThemePref } from '../store/prefs'
import { useToast } from '../store/toast'
import { student, lecturerById } from '../data/people'
import { documents, type NotifCategory } from '../data/services'
import { Card, CardBody, CardHeader } from '../components/ui/Card'
import { Badge, StatusBadge } from '../components/ui/Badge'
import { Button, IconButton } from '../components/ui/Button'
import { Async, EmptyState, ListSkeleton, ProfileSkeleton } from '../components/ui/Feedback'
import { Avatar, Cover, FilterChips, IconTile, PageHeader } from '../components/ui/Display'
import { Field, Input, Switch, Textarea } from '../components/ui/Form'
import { Segmented, TabPanel, Tabs } from '../components/ui/Tabs'
import { Modal } from '../components/ui/Overlay'
import { NotifIcon, relativeTime } from '../components/shared/notif'

/* ============================ Notifications ============================ */

const notifCats: NotifCategory[] = ['academic', 'finance', 'assignment', 'exam', 'event', 'announcement', 'system']

export function NotificationsPage() {
  const { t, lang } = useI18n()
  const toast = useToast()
  const { items } = useNotifications()
  const markRead = useApp((s) => s.markRead)
  const markAllRead = useApp((s) => s.markAllRead)
  const [state, setState] = useState<'all' | 'unread' | 'read'>('all')
  const [cat, setCat] = useState<'all' | NotifCategory>('all')
  const q = useMockData(() => true, false, 350)

  const list = q.data ? items.filter((n) => (state === 'all' || (state === 'unread' ? !n.read : n.read)) && (cat === 'all' || n.category === cat)) : []
  const unread = items.filter((n) => !n.read).length

  return (
    <>
      <PageHeader
        title={t('notifications.title')}
        description={t('notifications.subtitle')}
        crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('notifications.title') }]}
        actions={
          <Button
            variant="secondary"
            icon={CheckCheck}
            disabled={unread === 0}
            onClick={() => {
              markAllRead(items.map((n) => n.id))
              toast(t('notifications.allMarked'))
            }}
          >
            {t('notifications.markAllRead')}
          </Button>
        }
      />
      <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <Segmented
          label={t('common.status')}
          value={state}
          onChange={setState}
          className="self-start"
          items={[
            { value: 'all', label: t('notifications.all') },
            { value: 'unread', label: `${t('notifications.unread')}${unread ? ` · ${unread}` : ''}` },
            { value: 'read', label: t('notifications.read') },
          ]}
        />
        <FilterChips label={t('common.category')} value={cat} onChange={setCat} options={[{ value: 'all', label: t('common.all') }, ...notifCats.map((c) => ({ value: c, label: t(`notifCategory.${c}`) }))]} />
      </div>
      <Card>
        <Async query={q} skeleton={<div className="px-5"><ListSkeleton rows={6} /></div>} isEmpty={() => list.length === 0} empty={<EmptyState icon={BellOff} title={t('states.emptyNotifications')} body={t('states.emptyNotificationsBody')} />}>
          {() => (
            <ul className="divide-y divide-one-line">
              {list.map((n) => (
                <li key={n.id} className={cn('group relative flex items-start gap-4 px-4 py-4 transition-colors sm:px-6', !n.read && 'bg-one-royal/[0.035]')}>
                  {!n.read && <span aria-hidden className="absolute left-0 top-0 h-full w-0.5 bg-one-royal" />}
                  <NotifIcon category={n.category} size="md" />
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                      <Badge tone="neutral">{t(`notifCategory.${n.category}`)}</Badge>
                      <span className="text-[12px] text-one-subtle">{relativeTime(n.at, lang)}</span>
                      {!n.read && <span className="sr-only">{t('notifications.unread')}</span>}
                    </div>
                    <Link to={n.to} onClick={() => markRead(n.id, true)} className={cn('mt-1.5 block text-[15px] hover:text-one-royal', n.read ? 'text-one-muted' : 'font-semibold text-one-fg')}>
                      {n.title}
                    </Link>
                    <p className="t-small mt-0.5 text-one-subtle">{n.body}</p>
                  </div>
                  <IconButton
                    icon={n.read ? Mail : MailOpen}
                    size="sm"
                    label={n.read ? t('notifications.markUnread') : t('notifications.markRead')}
                    onClick={() => markRead(n.id, !n.read)}
                  />
                </li>
              ))}
            </ul>
          )}
        </Async>
      </Card>
    </>
  )
}

/* ============================ Profile ============================ */

type PTab = 'personal' | 'academic' | 'documents' | 'preferences'

export function ProfilePage() {
  const { t } = useI18n()
  const toast = useToast()
  const [params, setParams] = useSearchParams()
  const tab = (params.get('tab') as PTab) || 'personal'
  const [editing, setEditing] = useState(false)
  const [phone, setPhone] = useState(student.phone)
  const [address, setAddress] = useState(student.address)
  const q = useMockData(() => student, null as typeof student | null)
  const advisor = lecturerById(student.advisorId)

  return (
    <>
      <PageHeader title={t('profile.title')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('profile.title') }]} />
      <Async query={q} skeleton={<ProfileSkeleton />} isEmpty={(d) => !d} empty={<Card><EmptyState icon={User} title={t('states.emptyGeneric')} /></Card>}>
        {() => (
          <>
            <Card className="mb-6 overflow-hidden">
              <Cover tone="navy" className="h-28 sm:h-36" />
              <div className="relative px-5 pb-6 sm:px-8">
                <div className="-mt-12 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                  <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div className="relative self-start">
                      <Avatar name={student.name} size="xl" ring />
                      <button type="button" aria-label={t('profile.changePhoto')} onClick={() => toast(t('profile.uploadedToast'), 'info')} className="absolute bottom-1 right-1 flex h-8 w-8 items-center justify-center rounded-full bg-one-brand text-one-onbrand ring-4 ring-one-card">
                        <Camera size={14} aria-hidden />
                      </button>
                    </div>
                    <div className="pb-1">
                      <h2 className="t-h1 text-one-fg">{student.name}</h2>
                      <p className="mt-1 text-[14px] text-one-muted">
                        {t('profile.studentId')} <span className="font-mono font-medium text-one-fg">{student.studentId}</span> · {student.program} · {t('common.semesterN', { n: student.semester })}
                      </p>
                      <p className="text-[13.5px] text-one-subtle">{student.faculty}</p>
                    </div>
                  </div>
                  <Button variant="secondary" icon={Pencil} onClick={() => setEditing(true)}>{t('profile.editProfile')}</Button>
                </div>
                <div className="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-[13.5px] text-one-muted">
                  <span className="inline-flex items-center gap-2"><Mail size={15} aria-hidden className="text-one-subtle" /> {student.email}</span>
                  <span className="inline-flex items-center gap-2"><Phone size={15} aria-hidden className="text-one-subtle" /> {phone}</span>
                  <Badge tone="ok" icon={ShieldCheck}>{t('status.active')}</Badge>
                </div>
              </div>
            </Card>

            <Tabs
              id="prof"
              label={t('profile.title')}
              value={tab}
              onChange={(v) => setParams(v === 'personal' ? {} : { tab: v })}
              className="mb-6"
              items={(['personal', 'academic', 'documents', 'preferences'] as PTab[]).map((v) => ({ value: v, label: t(`profile.tabs.${v}`) }))}
            />
            <TabPanel id="prof" value={tab} key={tab}>
              {tab === 'personal' && (
                <InfoGrid
                  rows={[
                    [t('profile.fields.fullName'), student.name],
                    [t('profile.fields.birth'), student.birth],
                    [t('profile.fields.gender'), student.gender],
                    [t('profile.fields.nationality'), student.nationality],
                    [t('common.email'), student.email],
                    [t('common.phone'), phone],
                    [t('profile.fields.address'), address, true],
                    [t('profile.fields.emergency'), student.emergency, true],
                  ]}
                />
              )}
              {tab === 'academic' && (
                <InfoGrid
                  rows={[
                    [t('profile.studentId'), <span key="sid" className="font-mono">{student.studentId}</span>],
                    [t('profile.faculty'), student.faculty],
                    [t('profile.program'), student.program],
                    [t('profile.semester'), t('common.semesterN', { n: student.semester })],
                    [t('profile.fields.entryYear'), student.entryYear],
                    [t('profile.fields.class'), student.class],
                    [t('profile.fields.advisor'), advisor?.name],
                    [t('profile.fields.status'), <StatusBadge key="st" status="active" />],
                    [t('dashboard.gpa'), student.gpa.toFixed(2)],
                    [t('dashboard.totalCredits'), t('dashboard.creditsOf', { done: student.creditsEarned, total: student.creditsRequired })],
                  ]}
                />
              )}
              {tab === 'documents' && (
                <Card>
                  <ul className="divide-y divide-one-line">
                    {documents.map((d) => (
                      <li key={d.id} className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
                        <IconTile icon={FileText} tone={d.status === 'missing' ? 'bad' : 'royal'} />
                        <div className="min-w-0 flex-1">
                          <p className="font-medium text-one-fg">{d.name}</p>
                          <p className="text-[12.5px] text-one-subtle">{d.type}</p>
                        </div>
                        <StatusBadge status={d.status} />
                        <Button size="sm" variant={d.status === 'missing' ? 'primary' : 'secondary'} icon={Upload} onClick={() => toast(t('profile.uploadedToast'))}>
                          {t('profile.upload')}
                        </Button>
                      </li>
                    ))}
                  </ul>
                </Card>
              )}
              {tab === 'preferences' && (
                <div className="grid gap-6 lg:grid-cols-2">
                  <LanguageCard />
                  <ThemeCard />
                </div>
              )}
            </TabPanel>
          </>
        )}
      </Async>

      <Modal
        open={editing}
        onClose={() => setEditing(false)}
        title={t('profile.editProfile')}
        footer={
          <>
            <Button variant="secondary" onClick={() => setEditing(false)}>{t('common.cancel')}</Button>
            <Button
              disabled={!/^\+?[\d\s-]{9,}$/.test(phone) || address.trim().length < 5}
              onClick={() => {
                setEditing(false)
                toast(t('profile.savedToast'))
              }}
            >
              {t('common.saveChanges')}
            </Button>
          </>
        }
      >
        <div className="space-y-4">
          <Field label={t('profile.fields.fullName')} hint={t('app.demoBadge')}>
            <Input value={student.name} disabled />
          </Field>
          <Field label={t('common.phone')} required error={/^\+?[\d\s-]{9,}$/.test(phone) ? undefined : t('admission.errors.phone')}>
            <Input type="tel" value={phone} onChange={(e) => setPhone(e.target.value)} />
          </Field>
          <Field label={t('profile.fields.address')} required error={address.trim().length < 5 ? t('validation.required') : undefined}>
            <Textarea value={address} onChange={(e) => setAddress(e.target.value)} />
          </Field>
        </div>
      </Modal>
    </>
  )
}

function InfoGrid({ rows }: { rows: Array<[string, ReactNode, boolean?]> }) {
  return (
    <Card>
      <dl className="grid sm:grid-cols-2">
        {rows.map(([k, v, wide]) => (
          <div key={k} className={cn('border-b border-one-line px-5 py-4 sm:px-6', wide && 'sm:col-span-2')}>
            <dt className="text-[12.5px] text-one-subtle">{k}</dt>
            <dd className="mt-1 text-[15px] font-medium text-one-fg">{v}</dd>
          </div>
        ))}
      </dl>
    </Card>
  )
}

/* ============================ Settings ============================ */

function LanguageCard() {
  const { t, lang } = useI18n()
  const set = usePrefs((s) => s.set)
  return (
    <Card padded>
      <h3 className="t-h3 text-one-fg">{t('settings.language')}</h3>
      <div role="radiogroup" aria-label={t('settings.language')} className="mt-4 grid gap-2 sm:grid-cols-3">
        {LANGS.map((l) => (
          <button
            key={l.code}
            type="button"
            role="radio"
            aria-checked={lang === l.code}
            lang={l.code}
            onClick={() => set('lang', l.code)}
            className={cn('rounded-2xl border p-4 text-left transition-colors', lang === l.code ? 'border-one-royal bg-one-royal/5' : 'border-one-line hover:bg-one-sunken/60')}
          >
            <span className="block font-display text-[20px] font-bold text-one-fg">{l.short}</span>
            <span className="mt-0.5 block text-[13px] text-one-muted">{l.label}</span>
          </button>
        ))}
      </div>
    </Card>
  )
}

function ThemeCard() {
  const { t } = useI18n()
  const theme = usePrefs((s) => s.theme)
  const set = usePrefs((s) => s.set)
  const opts: Array<{ v: ThemePref; icon: typeof Sun }> = [
    { v: 'light', icon: Sun },
    { v: 'dark', icon: Moon },
    { v: 'system', icon: Monitor },
  ]
  return (
    <Card padded>
      <h3 className="t-h3 text-one-fg">{t('settings.theme')}</h3>
      <div role="radiogroup" aria-label={t('settings.theme')} className="mt-4 grid grid-cols-3 gap-2">
        {opts.map(({ v, icon: Icon }) => (
          <button
            key={v}
            type="button"
            role="radio"
            aria-checked={theme === v}
            onClick={() => set('theme', v)}
            className={cn('overflow-hidden rounded-2xl border text-left transition-colors', theme === v ? 'border-one-royal ring-2 ring-one-royal/20' : 'border-one-line hover:border-one-line2')}
          >
            {/* Live preview: a nested token scope renders the real palette of that theme. */}
            <div className="flex h-16 sm:h-20">
              {(v === 'system' ? ['light', 'dark'] : [v]).map((m) => (
                <div key={m} data-theme={m} className="one-root flex flex-1 gap-1 p-2">
                  <div className="w-3 rounded bg-one-card" />
                  <div className="flex-1 space-y-1 rounded bg-one-card p-1.5">
                    <div className="h-1.5 w-2/3 rounded bg-one-fg/70" />
                    <div className="h-1.5 w-1/2 rounded bg-one-royal" />
                    <div className="h-1.5 w-3/4 rounded bg-one-line2" />
                  </div>
                </div>
              ))}
            </div>
            <span className="flex items-center gap-1.5 border-t border-one-line px-3 py-2 text-[13px] font-medium text-one-fg">
              <Icon size={14} aria-hidden /> {t(`theme.${v}`)}
            </span>
          </button>
        ))}
      </div>
    </Card>
  )
}

const settingsNav = [
  { id: 'account', icon: User, label: 'settings.account' },
  { id: 'preferences', icon: SlidersHorizontal, label: 'settings.preferences' },
  { id: 'accessibility', icon: Accessibility, label: 'settings.accessibility' },
  { id: 'demo', icon: Database, label: 'settings.demo' },
] as const

export function SettingsPage() {
  const { t } = useI18n()
  const toast = useToast()
  const prefs = usePrefs()
  const [twoFactor, setTwoFactor] = useState(true)
  const [pw, setPw] = useState({ current: '', next: '', confirm: '' })
  const [pwErrors, setPwErrors] = useState<Partial<Record<keyof typeof pw, string>>>({})

  const savePassword = () => {
    const e: typeof pwErrors = {}
    if (!pw.current) e.current = t('validation.required')
    if (pw.next.length < 8 || !/\d/.test(pw.next)) e.next = t('settings.passwordTooShort')
    if (pw.confirm !== pw.next) e.confirm = t('settings.passwordMismatch')
    setPwErrors(e)
    if (Object.keys(e).length) return
    setPw({ current: '', next: '', confirm: '' })
    toast(t('settings.passwordUpdated'))
  }

  return (
    <>
      <PageHeader title={t('settings.title')} description={t('settings.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('settings.title') }]} />
      <div className="grid gap-8 lg:grid-cols-[220px_1fr]">
        <nav aria-label={t('settings.title')} className="min-w-0 lg:sticky lg:top-24 lg:self-start">
          <ul className="no-scrollbar -mx-4 flex gap-1 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:px-0">
            {settingsNav.map((s) => (
              <li key={s.id}>
                <a href={`#${s.id}`} className="flex items-center gap-2.5 whitespace-nowrap rounded-xl px-3 py-2 text-[14px] font-medium text-one-muted hover:bg-one-sunken hover:text-one-fg">
                  <s.icon size={16} aria-hidden /> {t(s.label)}
                </a>
              </li>
            ))}
          </ul>
        </nav>

        <div className="min-w-0 space-y-8">
          <SettingsSection id="account" title={t('settings.account')} desc={t('settings.accountDesc')}>
            <Card padded className="flex items-center gap-4">
              <Avatar name={student.name} size="lg" />
              <div className="min-w-0 flex-1">
                <p className="font-semibold text-one-fg">{student.name}</p>
                <p className="truncate text-[13px] text-one-subtle">{student.email}</p>
              </div>
              <Button variant="secondary" size="sm" to="/one/profile">{t('nav.profile')}</Button>
            </Card>
            <Card>
              <CardHeader title={t('settings.password')} icon={<IconTile icon={KeyRound} tone="royal" size="sm" />} />
              <CardBody>
                <form
                  noValidate
                  onSubmit={(e) => {
                    e.preventDefault()
                    savePassword()
                  }}
                  className="grid gap-4 sm:grid-cols-2"
                >
                  <Field label={t('settings.currentPassword')} error={pwErrors.current} required className="sm:col-span-2">
                    <Input type="password" autoComplete="current-password" value={pw.current} onChange={(e) => setPw({ ...pw, current: e.target.value })} />
                  </Field>
                  <Field label={t('settings.newPassword')} hint={t('settings.passwordHint')} error={pwErrors.next} required>
                    <Input type="password" autoComplete="new-password" value={pw.next} onChange={(e) => setPw({ ...pw, next: e.target.value })} />
                  </Field>
                  <Field label={t('settings.confirmPassword')} error={pwErrors.confirm} required>
                    <Input type="password" autoComplete="new-password" value={pw.confirm} onChange={(e) => setPw({ ...pw, confirm: e.target.value })} />
                  </Field>
                  <div className="sm:col-span-2">
                    <Button type="submit">{t('settings.updatePassword')}</Button>
                  </div>
                </form>
              </CardBody>
            </Card>
            <Card>
              <CardHeader title={t('settings.security')} icon={<IconTile icon={ShieldCheck} tone="ok" size="sm" />} />
              <CardBody className="space-y-5">
                <Switch checked={twoFactor} onChange={setTwoFactor} label={t('settings.twoFactor')} description={t('settings.twoFactorDesc')} />
                <div>
                  <p className="text-[14px] font-medium text-one-fg">{t('settings.activeSessions')}</p>
                  <ul className="mt-3 divide-y divide-one-line rounded-xl border border-one-line">
                    {[
                      { icon: Laptop, name: 'Chrome · Windows', place: 'Jakarta, ID', current: true },
                      { icon: Smartphone, name: 'Civitas One · Android', place: 'Jakarta, ID', current: false },
                    ].map((s) => (
                      <li key={s.name} className="flex items-center gap-3 px-4 py-3">
                        <s.icon size={18} aria-hidden className="text-one-subtle" />
                        <div className="min-w-0 flex-1">
                          <p className="text-[14px] text-one-fg">{s.name}</p>
                          <p className="text-[12.5px] text-one-subtle">{s.place}</p>
                        </div>
                        {s.current && <Badge tone="ok" dot>{t('settings.thisDevice')}</Badge>}
                      </li>
                    ))}
                  </ul>
                </div>
              </CardBody>
            </Card>
          </SettingsSection>

          <SettingsSection id="preferences" title={t('settings.preferences')} desc={t('settings.preferencesDesc')}>
            <div className="grid gap-6 xl:grid-cols-2">
              <LanguageCard />
              <ThemeCard />
            </div>
            <Card>
              <CardHeader title={t('settings.notifications')} icon={<IconTile icon={Bell} tone="gold" size="sm" />} />
              <CardBody className="space-y-5">
                {(['email', 'push', 'digest'] as const).map((k) => (
                  <Switch
                    key={k}
                    checked={prefs.notif[k]}
                    onChange={(v) => {
                      prefs.set('notif', { ...prefs.notif, [k]: v })
                      toast(t('settings.savedToast'))
                    }}
                    label={t(`settings.notif.${k}`)}
                    description={t(`settings.notif.${k}Desc`)}
                  />
                ))}
              </CardBody>
            </Card>
          </SettingsSection>

          <SettingsSection id="accessibility" title={t('settings.accessibility')} desc={t('settings.accessibilityDesc')}>
            <Card>
              <CardBody className="space-y-6 pt-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                  <p className="text-[14px] font-medium text-one-fg">{t('settings.fontSize')}</p>
                  <Segmented
                    label={t('settings.fontSize')}
                    value={prefs.fontScale}
                    onChange={(v: FontScale) => prefs.set('fontScale', v)}
                    items={(['small', 'default', 'large'] as FontScale[]).map((v) => ({ value: v, label: t(`settings.fontSizes.${v}`) }))}
                  />
                </div>
                <Switch checked={prefs.reducedMotion} onChange={(v) => prefs.set('reducedMotion', v)} label={t('settings.reducedMotion')} description={t('settings.reducedMotionDesc')} />
                <Switch checked={prefs.highContrast} onChange={(v) => prefs.set('highContrast', v)} label={t('settings.contrast')} description={t('settings.contrastDesc')} />
              </CardBody>
            </Card>
          </SettingsSection>

          <SettingsSection id="demo" title={t('settings.demo')} desc={t('settings.demoDesc')}>
            <Card padded className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div className="flex items-center gap-3">
                <IconTile icon={Database} tone="sky" />
                <Badge tone="sky">{t('app.demoBadge')}</Badge>
              </div>
              <Segmented
                label={t('settings.demo')}
                value={prefs.dataState}
                onChange={(v: DataState) => prefs.set('dataState', v)}
                items={(['live', 'loading', 'empty', 'error'] as DataState[]).map((v) => ({ value: v, label: t(`settings.dataStates.${v}`) }))}
              />
            </Card>
          </SettingsSection>
        </div>
      </div>
    </>
  )
}

function SettingsSection({ id, title, desc, children }: { id: string; title: string; desc: string; children: ReactNode }) {
  return (
    <section id={id} aria-labelledby={`${id}-h`} className="scroll-mt-24 space-y-4">
      <div>
        <h2 id={`${id}-h`} className="t-h2 text-one-fg">{title}</h2>
        <p className="t-small mt-0.5 text-one-subtle">{desc}</p>
      </div>
      {children}
    </section>
  )
}
