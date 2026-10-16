/**
 * Rute Modul KEU (Administrasi Keuangan Mahasiswa). `/keuangan/tagihan(/:id)`
 * dan `/keuangan/pembayaran` sudah ada sebelum KEU dan dipertahankan
 * (bookmark lama tetap jalan); `/keuangan/beasiswa` tetap milik modul
 * Beasiswa (Scholarship).
 */
export const KEU = {
  admin: {
    dashboard: '/keuangan',
    invoices: '/keuangan/tagihan',
    invoice: '/keuangan/tagihan/:id',
    billingRuns: '/keuangan/penerbitan',
    billingRun: '/keuangan/penerbitan/:id',
    adjustments: '/keuangan/penyesuaian',
    transactions: '/keuangan/pembayaran',
    transaction: '/keuangan/pembayaran/:id',
    verification: '/keuangan/verifikasi-transfer',
    cashier: '/keuangan/kasir',
    reconciliations: '/keuangan/rekonsiliasi',
    reconciliation: '/keuangan/rekonsiliasi/:id',
    tariffs: '/keuangan/tarif',
    rateTable: '/keuangan/tarif/:id',
    aid: '/keuangan/potongan',
    installments: '/keuangan/cicilan',
    penalties: '/keuangan/denda',
    ukt: '/keuangan/ukt',
    deposits: '/keuangan/deposit',
    clearance: '/keuangan/cekal',
    block: '/keuangan/cekal/:id',
    student: '/keuangan/mahasiswa/:id',
    reports: '/keuangan/laporan',
    settings: '/keuangan/pengaturan',
  },
  portal: {
    home: '/portal/keuangan',
    invoice: '/portal/keuangan/tagihan/:id',
  },
} as const

export function to(path: string, params: Record<string, string>): string {
  return Object.entries(params).reduce((acc, [key, value]) => acc.replace(`:${key}`, encodeURIComponent(value)), path)
}
