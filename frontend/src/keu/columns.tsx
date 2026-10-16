import { Link } from 'react-router-dom'
import { Badge } from '@/components/ui/Badge'
import { date, dateTime, money } from '@/pmb/lib/format'
import { KeuBadge } from '@/keu/components'
import { KEU, to } from '@/keu/routes'
import type { Invoice, Transaction } from '@/keu/types'

/** Kolom tabel tagihan & transaksi yang dipakai ulang di beberapa halaman KEU. */
export function invoiceColumns() {
  return [
    { header: 'No. Tagihan', cell: (r: Invoice) => <Link className="text-primary" to={to(KEU.admin.invoice, { id: r.id })}>{r.invoice_number ?? '(lama)'}<span className="block text-xs text-ink-tertiary">{r.bill_group_label} · {r.period}</span></Link> },
    { header: 'Mahasiswa', cell: (r: Invoice) => r.student ? <Link className="text-primary" to={to(KEU.admin.student, { id: r.student.id })}>{r.student.nim}<span className="block text-xs text-ink-tertiary">{r.student.name}</span></Link> : '—' },
    { header: 'Neto', cell: (r: Invoice) => money(r.amount) },
    { header: 'Dibayar', cell: (r: Invoice) => money(r.paid_amount) },
    { header: 'Sisa', cell: (r: Invoice) => money(r.outstanding) },
    { header: 'Jatuh tempo', cell: (r: Invoice) => date(r.due_date) },
    { header: 'Status', cell: (r: Invoice) => <span className="flex gap-1"><KeuBadge status={r.status} label={r.status_label} />{r.is_overdue && <KeuBadge status="unpaid" overdue />}</span> },
  ]
}

export function transactionColumns() {
  return [
    { header: 'Transaksi', cell: (r: Transaction) => <Link className="text-primary" to={to(KEU.admin.transaction, { id: r.id })}>{r.transaction_number}<span className="block text-xs text-ink-tertiary">{r.receipt_number ?? dateTime(r.created_at)}</span></Link> },
    { header: 'Mahasiswa', cell: (r: Transaction) => r.student ? <span>{r.student.nim}<span className="block text-xs text-ink-tertiary">{r.student.name}</span></span> : '—' },
    { header: 'Metode', cell: (r: Transaction) => <span>{r.method_label}<span className="block text-xs text-ink-tertiary">{r.channel?.name ?? r.provider}</span></span> },
    { header: 'Nominal', cell: (r: Transaction) => money(r.amount) },
    { header: 'Status', cell: (r: Transaction) => <span className="flex flex-col gap-0.5"><KeuBadge status={r.status} label={r.status_label} />{r.needs_resolution && <Badge variant="warning">Perlu resolusi</Badge>}</span> },
    { header: 'Rekonsiliasi', cell: (r: Transaction) => <KeuBadge status={r.reconciliation_status} label={r.reconciliation_label} /> },
    { header: 'Dibayar', cell: (r: Transaction) => dateTime(r.paid_at) },
  ]
}
