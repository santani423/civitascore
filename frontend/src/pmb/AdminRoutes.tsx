import { lazy, Suspense, type ComponentType, type ReactNode } from 'react'
import { PMB } from '@/pmb/routes'

const named = <T extends Record<string, unknown>>(loader: () => Promise<T>, name: keyof T) =>
  lazy(() => loader().then((m) => ({ default: m[name] as ComponentType })))

const config = () => import('@/pmb/pages/admin/ConfigPages')
const templates = () => import('@/pmb/pages/admin/TemplatePages')
const site = () => import('@/pmb/pages/admin/SitePages')
const apps = () => import('@/pmb/pages/admin/ApplicationPages')
const exams = () => import('@/pmb/pages/admin/ExamPages')
const selection = () => import('@/pmb/pages/admin/SelectionPages')
const outcome = () => import('@/pmb/pages/admin/OutcomePages')
const insight = () => import('@/pmb/pages/admin/InsightPages')

const pages = {
  DashboardPage: named(() => import('@/pmb/pages/admin/DashboardPage'), 'DashboardPage'),
  PeriodsPage: named(config, 'PeriodsPage'),
  PeriodDetailPage: named(config, 'PeriodDetailPage'),
  MasterDataPage: named(config, 'MasterDataPage'),
  FormTemplatesPage: named(templates, 'FormTemplatesPage'),
  FormTemplateDetailPage: named(templates, 'FormTemplateDetailPage'),
  SchemesPage: named(templates, 'SchemesPage'),
  SchemeDetailPage: named(templates, 'SchemeDetailPage'),
  ContentsPage: named(site, 'ContentsPage'),
  ProgramProfilesPage: named(site, 'ProgramProfilesPage'),
  SettingsPage: named(site, 'SettingsPage'),
  ApplicationsPage: named(apps, 'ApplicationsPage'),
  ApplicationDetailPage: named(apps, 'ApplicationDetailPage'),
  VerificationPage: named(() => import('@/pmb/pages/admin/VerificationPage'), 'VerificationPage'),
  FinancePage: named(() => import('@/pmb/pages/admin/FinancePage'), 'FinancePage'),
  ExamsPage: named(exams, 'ExamsPage'),
  ExamScheduleDetailPage: named(exams, 'ExamScheduleDetailPage'),
  ExamSessionPage: named(exams, 'ExamSessionPage'),
  ScoringPage: named(exams, 'ScoringPage'),
  SelectionPage: named(selection, 'SelectionPage'),
  RunDetailPage: named(selection, 'RunDetailPage'),
  AnnouncementsPage: named(outcome, 'AnnouncementsPage'),
  ReRegistrationsPage: named(outcome, 'ReRegistrationsPage'),
  ReRegistrationDetailPage: named(outcome, 'ReRegistrationDetailPage'),
  ReportsPage: named(insight, 'ReportsPage'),
  CommunicationPage: named(insight, 'CommunicationPage'),
  AuditPage: named(insight, 'AuditPage'),
}

function S({ children }: { children: ReactNode }) {
  return <Suspense fallback={<p className="text-sm text-ink-secondary">Memuat...</p>}>{children}</Suspense>
}

const el = (Page: ComponentType) => <S><Page /></S>

/** Rute admin PMB di dalam DashboardLayout (izin per rute dari adminNav.ts). */
export const PMB_ADMIN_ROUTES: Array<{ path: string; element: ReactNode }> = [
  { path: PMB.admin.dashboard, element: el(pages.DashboardPage) },
  { path: PMB.admin.periods, element: el(pages.PeriodsPage) },
  { path: PMB.admin.period, element: el(pages.PeriodDetailPage) },
  { path: PMB.admin.master, element: el(pages.MasterDataPage) },
  { path: PMB.admin.forms, element: el(pages.FormTemplatesPage) },
  { path: PMB.admin.form, element: el(pages.FormTemplateDetailPage) },
  { path: PMB.admin.schemes, element: el(pages.SchemesPage) },
  { path: PMB.admin.scheme, element: el(pages.SchemeDetailPage) },
  { path: PMB.admin.contents, element: el(pages.ContentsPage) },
  { path: PMB.admin.programProfiles, element: el(pages.ProgramProfilesPage) },
  { path: PMB.admin.settings, element: el(pages.SettingsPage) },
  { path: PMB.admin.applications, element: el(pages.ApplicationsPage) },
  { path: PMB.admin.application, element: el(pages.ApplicationDetailPage) },
  { path: PMB.admin.verification, element: el(pages.VerificationPage) },
  { path: PMB.admin.finance, element: el(pages.FinancePage) },
  { path: PMB.admin.exams, element: el(pages.ExamsPage) },
  { path: PMB.admin.examSchedule, element: el(pages.ExamScheduleDetailPage) },
  { path: PMB.admin.examSession, element: el(pages.ExamSessionPage) },
  { path: PMB.admin.scoring, element: el(pages.ScoringPage) },
  { path: PMB.admin.selection, element: el(pages.SelectionPage) },
  { path: PMB.admin.selectionRun, element: el(pages.RunDetailPage) },
  { path: PMB.admin.announcements, element: el(pages.AnnouncementsPage) },
  { path: PMB.admin.reRegistrations, element: el(pages.ReRegistrationsPage) },
  { path: PMB.admin.reRegistration, element: el(pages.ReRegistrationDetailPage) },
  { path: PMB.admin.reports, element: el(pages.ReportsPage) },
  { path: PMB.admin.communication, element: el(pages.CommunicationPage) },
  { path: PMB.admin.audit, element: el(pages.AuditPage) },
]
