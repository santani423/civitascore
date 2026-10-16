import { Wallet } from 'lucide-react'
import type { NavItem } from '@/types/navigation'
import { ROUTES } from '@/constants/routes'
import { KEU } from '@/keu/routes'

/**
 * Menu Keuangan (Modul KEU, app/Modules/Finance). Permission tiap item =
 * gate baca endpoint utama halamannya (Routes/api.php), OR-match. "Beasiswa"
 * tetap milik modul Scholarship (program beasiswa), terpisah dari
 * "Beasiswa, Potongan & Voucher" KEU (penetapan potongan pada tagihan).
 */
export const KEU_NAV_ITEM: NavItem = {
  label: 'Keuangan',
  path: KEU.admin.dashboard,
  icon: Wallet,
  children: [
    { label: 'Dashboard Keuangan', path: KEU.admin.dashboard, permission: 'finance_dashboard.read' },
    { label: 'Tagihan', path: KEU.admin.invoices, permission: 'invoices.read' },
    { label: 'Penerbitan Massal', path: KEU.admin.billingRuns, permission: 'billing_runs.read' },
    { label: 'Penyesuaian Tagihan', path: KEU.admin.adjustments, permission: 'invoice_adjustments.read' },
    { label: 'Loket Kasir', path: KEU.admin.cashier, permission: 'cashier_sessions.read' },
    { label: 'Verifikasi Transfer', path: KEU.admin.verification, permission: 'payment_transactions.approve' },
    { label: 'Transaksi Pembayaran', path: KEU.admin.transactions, permission: 'payment_transactions.read' },
    { label: 'Rekonsiliasi', path: KEU.admin.reconciliations, permission: 'reconciliations.read' },
    { label: 'Deposit & Refund', path: KEU.admin.deposits, permission: ['student_deposits.read', 'refunds.read'] },
    { label: 'Cicilan', path: KEU.admin.installments, permission: 'installments.read' },
    { label: 'Denda', path: KEU.admin.penalties, permission: 'penalties.read' },
    { label: 'Beasiswa, Potongan & Voucher', path: KEU.admin.aid, permission: ['financial_aid.read', 'vouchers.read'] },
    { label: 'UKT', path: KEU.admin.ukt, permission: 'ukt.read' },
    { label: 'Cekal Keuangan', path: KEU.admin.clearance, permission: 'finance_clearance.read' },
    { label: 'Tarif & Komponen', path: KEU.admin.tariffs, permission: ['fee_rates.read', 'fee_components.read'] },
    { label: 'Laporan Keuangan', path: KEU.admin.reports, permission: 'finance_reports.read' },
    { label: 'Pengaturan Keuangan', path: KEU.admin.settings, permission: ['finance_settings.read', 'payment_channels.read'] },
    { label: 'Program Beasiswa', path: ROUTES.keuangan.beasiswa, permission: 'scholarships.read' },
  ],
}

/** Rute detail KEU mewarisi izin baca halaman induknya. */
export const KEU_DETAIL_ROUTE_PERMISSIONS: Record<string, string | string[]> = {
  [KEU.admin.invoice]: 'invoices.read',
  [KEU.admin.billingRun]: 'billing_runs.read',
  [KEU.admin.transaction]: 'payment_transactions.read',
  [KEU.admin.reconciliation]: 'reconciliations.read',
  [KEU.admin.rateTable]: 'fee_rates.read',
  [KEU.admin.block]: 'finance_clearance.read',
  [KEU.admin.student]: 'invoices.read',
  [KEU.portal.invoice]: 'student_finance.read',
}
