import { lazy, Suspense, type ComponentType, type ReactNode } from 'react'
import { KEU } from '@/keu/routes'

const named = <T extends Record<string, unknown>>(loader: () => Promise<T>, name: keyof T) =>
  lazy(() => loader().then((m) => ({ default: m[name] as ComponentType })))

const billing = () => import('@/keu/pages/admin/BillingPages')
const payments = () => import('@/keu/pages/admin/PaymentPages')
const tariffs = () => import('@/keu/pages/admin/TariffPages')
const policies = () => import('@/keu/pages/admin/PolicyPages')
const deposits = () => import('@/keu/pages/admin/DepositPages')
const recon = () => import('@/keu/pages/admin/ReconciliationPages')
const clearance = () => import('@/keu/pages/admin/ClearancePages')
const reports = () => import('@/keu/pages/admin/ReportPages')
const portal = () => import('@/keu/pages/portal/PortalFinancePages')

const pages = {
  DashboardPage: named(() => import('@/keu/pages/admin/DashboardPage'), 'DashboardPage'),
  InvoicesPage: named(billing, 'InvoicesPage'),
  InvoiceDetailPage: named(billing, 'InvoiceDetailPage'),
  BillingRunsPage: named(billing, 'BillingRunsPage'),
  BillingRunDetailPage: named(billing, 'BillingRunDetailPage'),
  AdjustmentsPage: named(billing, 'AdjustmentsPage'),
  TransactionsPage: named(payments, 'TransactionsPage'),
  TransactionDetailPage: named(payments, 'TransactionDetailPage'),
  VerificationPage: named(payments, 'VerificationPage'),
  CashierPage: named(payments, 'CashierPage'),
  TariffsPage: named(tariffs, 'TariffsPage'),
  RateTableDetailPage: named(tariffs, 'RateTableDetailPage'),
  AidPage: named(policies, 'AidPage'),
  InstallmentsPage: named(policies, 'InstallmentsPage'),
  PenaltiesPage: named(policies, 'PenaltiesPage'),
  UktPage: named(policies, 'UktPage'),
  DepositsPage: named(deposits, 'DepositsPage'),
  StudentAccountPage: named(deposits, 'StudentAccountPage'),
  ReconciliationsPage: named(recon, 'ReconciliationsPage'),
  ReconciliationDetailPage: named(recon, 'ReconciliationDetailPage'),
  ClearancePage: named(clearance, 'ClearancePage'),
  BlockDetailPage: named(clearance, 'BlockDetailPage'),
  ReportsPage: named(reports, 'ReportsPage'),
  SettingsPage: named(reports, 'SettingsPage'),
  PortalFinancePage: named(portal, 'PortalFinancePage'),
  PortalInvoicePage: named(portal, 'PortalInvoicePage'),
}

function S({ children }: { children: ReactNode }) {
  return <Suspense fallback={<p className="text-sm text-ink-secondary">Memuat...</p>}>{children}</Suspense>
}

const el = (Page: ComponentType) => <S><Page /></S>

/** Rute Modul KEU di dalam DashboardLayout (izin per rute dari adminNav.ts & nav.ts). */
export const KEU_ROUTES: Array<{ path: string; element: ReactNode }> = [
  { path: KEU.admin.dashboard, element: el(pages.DashboardPage) },
  { path: KEU.admin.invoices, element: el(pages.InvoicesPage) },
  { path: KEU.admin.invoice, element: el(pages.InvoiceDetailPage) },
  { path: KEU.admin.billingRuns, element: el(pages.BillingRunsPage) },
  { path: KEU.admin.billingRun, element: el(pages.BillingRunDetailPage) },
  { path: KEU.admin.adjustments, element: el(pages.AdjustmentsPage) },
  { path: KEU.admin.transactions, element: el(pages.TransactionsPage) },
  { path: KEU.admin.transaction, element: el(pages.TransactionDetailPage) },
  { path: KEU.admin.verification, element: el(pages.VerificationPage) },
  { path: KEU.admin.cashier, element: el(pages.CashierPage) },
  { path: KEU.admin.tariffs, element: el(pages.TariffsPage) },
  { path: KEU.admin.rateTable, element: el(pages.RateTableDetailPage) },
  { path: KEU.admin.aid, element: el(pages.AidPage) },
  { path: KEU.admin.installments, element: el(pages.InstallmentsPage) },
  { path: KEU.admin.penalties, element: el(pages.PenaltiesPage) },
  { path: KEU.admin.ukt, element: el(pages.UktPage) },
  { path: KEU.admin.deposits, element: el(pages.DepositsPage) },
  { path: KEU.admin.student, element: el(pages.StudentAccountPage) },
  { path: KEU.admin.reconciliations, element: el(pages.ReconciliationsPage) },
  { path: KEU.admin.reconciliation, element: el(pages.ReconciliationDetailPage) },
  { path: KEU.admin.clearance, element: el(pages.ClearancePage) },
  { path: KEU.admin.block, element: el(pages.BlockDetailPage) },
  { path: KEU.admin.reports, element: el(pages.ReportsPage) },
  { path: KEU.admin.settings, element: el(pages.SettingsPage) },
  { path: KEU.portal.home, element: el(pages.PortalFinancePage) },
  { path: KEU.portal.invoice, element: el(pages.PortalInvoicePage) },
]
