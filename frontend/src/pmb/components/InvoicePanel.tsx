import { useState } from 'react'
import { Copy, Receipt } from 'lucide-react'
import { pmbApplicant } from '@/pmb/api/applicant'
import type { Invoice, PaymentChannel, Transaction } from '@/pmb/api/types'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { FormDialog, toFormData } from '@/pmb/components/FormDialog'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { KeyValue } from '@/pmb/components/KeyValue'
import { dateTime, money } from '@/pmb/lib/format'

/**
 * Tagihan & pembayaran (POR-06, daftar ulang): pilih kanal → instruksi
 * (VA/QRIS/transfer + kode unik), unggah bukti transfer manual, voucher,
 * kuitansi. Satu transaksi pending per tagihan (F4).
 */
export function InvoicePanel({ invoice, onChanged, allowInstallment = false }: { invoice: Invoice; onChanged: () => void; allowInstallment?: boolean }) {
  const channels = useLoad(() => pmbApplicant.paymentChannels(), [])
  const action = useAction()
  const [channel, setChannel] = useState('')
  const [amount, setAmount] = useState('')
  const [voucher, setVoucher] = useState('')
  const [proofFor, setProofFor] = useState<Transaction | null>(null)
  const pending = invoice.transactions.find((t) => t.status === 'pending' || t.status === 'created')
  const payable = ['unpaid', 'partially_paid', 'expired'].includes(invoice.status) && !pending
  const selected = channels.data?.find((c: PaymentChannel) => c.code === channel)

  return (
    <Card title={`${invoice.type === 'registration_fee' ? 'Biaya Pendaftaran' : 'Biaya Awal Kuliah'} · ${invoice.invoice_number}`} actions={<StatusBadge status={invoice.status} />}>
      <div className="flex flex-col gap-4">
        <Feedback state={action} />
        <KeyValue columns={3} items={[
          ['Total tagihan', money(invoice.amount_net)],
          ['Sudah dibayar', money(invoice.amount_paid)],
          ['Sisa', money(invoice.outstanding)],
          ['Jatuh tempo', dateTime(invoice.due_at)],
          ...(invoice.minimum_for_enrollment ? [['Minimum untuk daftar ulang', money(invoice.minimum_for_enrollment)] as [string, string]] : []),
          ...(Number(invoice.amount_discount) > 0 ? [['Potongan', money(invoice.amount_discount)] as [string, string]] : []),
        ]} />
        {invoice.items.length > 0 && (
          <ul className="divide-y divide-border rounded-lg border border-border text-sm">
            {invoice.items.map((item, i) => <li key={i} className="flex justify-between gap-3 px-3 py-2"><span>{item.description}{item.installment_seq ? ` (cicilan ${item.installment_seq})` : ''}</span><span>{money(item.amount)}</span></li>)}
          </ul>
        )}
        {invoice.needs_resolution && <Alert variant="warning">Pembayaran diterima setelah tagihan kedaluwarsa dan sedang ditinjau panitia.</Alert>}

        {pending && <TransactionInstructions transaction={pending} onProof={() => setProofFor(pending)} onCancel={() => action.run(() => pmbApplicant.cancelTransaction(pending.id), 'Transaksi dibatalkan.').then(onChanged)} onRefresh={() => action.run(() => pmbApplicant.transaction(pending.id)).then(onChanged)} />}

        {payable && (
          <div className="flex flex-col gap-3 rounded-lg border border-border p-3">
            <p className="text-sm font-medium text-ink-primary">Bayar sekarang</p>
            {channels.error && <Alert variant="danger">{channels.error.message}</Alert>}
            <Select label="Metode pembayaran" placeholder="Pilih metode" options={(channels.data ?? []).map((c) => ({ value: c.code, label: `${c.label}${Number(c.admin_fee) > 0 && c.fee_borne_by === 'applicant' ? ` (+ biaya ${money(c.admin_fee)})` : ''}` }))} value={channel} onChange={(e) => setChannel(e.target.value)} />
            {allowInstallment && <Input label="Nominal (kosongkan untuk melunasi sisa)" type="number" min={1} value={amount} onChange={(e) => setAmount(e.target.value)} hint={invoice.minimum_for_enrollment ? `Minimal ${money(invoice.minimum_for_enrollment)} agar dapat daftar ulang.` : undefined} />}
            <Button disabled={!channel} isLoading={action.busy} onClick={() => action.run(() => pmbApplicant.createTransaction(invoice.id, channel, amount || undefined), 'Instruksi pembayaran dibuat.').then(onChanged)}>
              Buat instruksi pembayaran
            </Button>
            {selected?.provider === 'cashier' && <p className="text-xs text-ink-secondary">Bayar tunai di loket kampus dengan menyebutkan nomor tagihan; petugas akan mencatat pembayaran Anda.</p>}
            <div className="flex items-end gap-2">
              <Input label="Kode voucher" value={voucher} onChange={(e) => setVoucher(e.target.value.toUpperCase())} />
              <Button variant="outline" disabled={!voucher} onClick={() => action.run(() => pmbApplicant.applyVoucher(invoice.id, voucher), 'Voucher diterapkan.').then(onChanged)}>Terapkan</Button>
            </div>
          </div>
        )}

        {invoice.transactions.filter((t) => t !== pending).length > 0 && (
          <details className="text-sm">
            <summary className="cursor-pointer text-ink-secondary">Riwayat transaksi</summary>
            <ul className="mt-2 space-y-1">
              {invoice.transactions.filter((t) => t !== pending).map((t) => (
                <li key={t.id} className="flex flex-wrap items-center justify-between gap-2 rounded border border-border px-3 py-2">
                  <span>{t.channel} · {money(t.amount)} · {dateTime(t.paid_at ?? t.created_at)}</span>
                  <span className="flex items-center gap-2">{t.manual_proof && <StatusBadge status={t.manual_proof.status} label={`Bukti: ${t.manual_proof.status === 'submitted' ? 'menunggu' : t.manual_proof.status === 'approved' ? 'disetujui' : 'ditolak'}`} />}<StatusBadge status={t.status} /></span>
                  {t.failure_reason && <span className="w-full text-xs text-danger">{t.failure_reason}</span>}
                </li>
              ))}
            </ul>
          </details>
        )}

        {Number(invoice.amount_paid) > 0 && (
          <Button variant="outline" leftIcon={<Receipt className="size-4" />} onClick={() => action.run(async () => window.open((await pmbApplicant.receipt(invoice.id)).url, '_blank', 'noopener'))}>Unduh kuitansi</Button>
        )}
      </div>

      <FormDialog
        open={proofFor !== null}
        title="Unggah bukti transfer"
        description={proofFor ? `Transfer tepat ${money(proofFor.amount)} (sudah termasuk kode unik).` : undefined}
        onClose={() => setProofFor(null)}
        fields={[
          { name: 'file', label: 'Foto/PDF bukti transfer', type: 'file', required: true, accept: 'image/jpeg,image/png,application/pdf' },
          { name: 'sender_bank', label: 'Bank pengirim', required: true },
          { name: 'sender_name', label: 'Nama pemilik rekening', required: true },
          { name: 'transfer_date', label: 'Tanggal transfer', type: 'date', required: true },
          { name: 'transferred_amount', label: 'Nominal yang ditransfer', type: 'number', required: true },
        ]}
        initial={{ transferred_amount: proofFor?.amount, transfer_date: new Date().toISOString().slice(0, 10) }}
        onSubmit={async (values) => {
          if (!proofFor) return
          await pmbApplicant.manualProof(proofFor.id, toFormData(values))
          onChanged()
        }}
      />
    </Card>
  )
}

function TransactionInstructions({ transaction: t, onProof, onCancel, onRefresh }: { transaction: Transaction; onProof: () => void; onCancel: () => void; onRefresh: () => void }) {
  // Backend: [{title, steps[]}] per kanal (PaymentService::manualInstructions, XenditGateway::instructions).
  const blocks = Array.isArray(t.instructions) ? (t.instructions as Array<{ title?: string; steps?: string[] }>) : []

  return (
    <div className="flex flex-col gap-3 rounded-lg border border-primary-200 bg-primary-50/50 p-3 dark:bg-primary-950/30">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <p className="text-sm font-medium text-ink-primary">Menunggu pembayaran · {t.channel}</p>
        <span className="text-xs text-ink-secondary">Berlaku s.d. {dateTime(t.expires_at)}</span>
      </div>
      <p className="text-2xl font-semibold text-ink-primary">{money(t.amount)}</p>
      {t.unique_code ? <p className="text-xs text-ink-secondary">Termasuk kode unik {t.unique_code} — transfer tepat sesuai nominal agar mudah dicocokkan.</p> : null}
      {t.va_number && (
        <div className="flex items-center gap-2"><span className="text-sm">Nomor VA:</span><code className="rounded bg-surface px-2 py-1 font-mono">{t.va_number}</code><button type="button" aria-label="Salin" onClick={() => navigator.clipboard?.writeText(t.va_number ?? '')}><Copy className="size-4 text-ink-tertiary" /></button></div>
      )}
      {t.redirect_url && <a href={t.redirect_url} target="_blank" rel="noopener" className="text-sm text-primary">Buka QR / halaman pembayaran</a>}
      {blocks.map((block) => (
        <div key={block.title}>
          {block.title && <p className="text-sm font-medium text-ink-primary">{block.title}</p>}
          <ol className="list-decimal space-y-0.5 pl-5 text-sm text-ink-secondary">{(block.steps ?? []).map((step) => <li key={step}>{step}</li>)}</ol>
        </div>
      ))}
      {t.manual_proof && <p className="text-sm text-ink-secondary">Bukti transfer: {t.manual_proof.status === 'submitted' ? 'menunggu verifikasi keuangan' : t.manual_proof.status}.</p>}
      <div className="flex flex-wrap gap-2">
        {t.provider === 'manual' && !t.manual_proof && <Button size="sm" onClick={onProof}>Unggah bukti transfer</Button>}
        {t.provider !== 'manual' && t.provider !== 'cashier' && <Button size="sm" variant="outline" onClick={onRefresh}>Cek status</Button>}
        {!t.manual_proof && <Button size="sm" variant="ghost" onClick={onCancel}>Ganti metode</Button>}
      </div>
    </div>
  )
}
