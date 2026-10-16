import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Plus, Upload } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { DataTable } from '@/components/ui/DataTable'
import { Input } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Checkbox } from '@/components/ui/Checkbox'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, describeError, useAction } from '@/pmb/components/useAction'
import { FormDialog } from '@/pmb/components/FormDialog'
import { date, dateTime, money } from '@/pmb/lib/format'
import { usePermission } from '@/hooks/usePermission'
import { keuAdmin } from '@/keu/api'
import { Can, Figure, KeuBadge, KeuList, KeuPage, StudentPicker } from '@/keu/components'
import { P } from '@/keu/permissions'
import { opts, useKeuLookups } from '@/keu/lookups'
import { KEU, to } from '@/keu/routes'
import type { StudentRef, Transaction } from '@/keu/types'
import { parseStatementCsv } from '@/keu/statementCsv'
import type { NormalizedApiError } from '@/services/api'

interface Batch { id: string; batch_number: string; source: string; statement_date: string | null; channel: string | null; status: string; status_label: string; total_items: number; matched_items: number; exception_items: number; total_amount: string; created_by: string | null; closed_by: string | null; closed_at: string | null }
interface Item { id: string; external_reference: string | null; external_amount: string | null; external_date: string | null; external_description: string | null; result: string; result_label: string; resolution: string | null; resolution_label: string | null; resolution_note: string | null; resolved_by: string | null; transaction: Transaction | null }

const SOURCES = [{ value: 'bank_statement', label: 'Mutasi rekening bank' }, { value: 'provider_settlement', label: 'Laporan settlement penyedia' }, { value: 'cash_deposit', label: 'Setoran kas loket' }]

/** Rekonsiliasi (F-12): impor mutasi → cocokkan → selesaikan pengecualian → tutup. */
export function ReconciliationsPage() {
  const lookups = useKeuLookups()
  const navigate = useNavigate()
  const [creating, setCreating] = useState(false)

  return (
    <KeuPage title="Rekonsiliasi" description="Mencocokkan transaksi tercatat dengan mutasi rekening, laporan penyedia, atau setoran kas. Dana yang tidak teridentifikasi tidak masuk ke mahasiswa mana pun sebelum diklaim." trail={[{ label: 'Rekonsiliasi' }]}
      actions={<Can permission={P.RECON_CREATE}><Button leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>Batch baru</Button></Can>}>
      <KeuList<Batch> path="reconciliations" search={false} rowKey={(r) => r.id} itemLabel="batch"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'open', label: 'Terbuka' }, { value: 'closed', label: 'Ditutup' }] }]}
        columns={[
          { header: 'Batch', cell: (r) => <Link className="text-primary" to={to(KEU.admin.reconciliation, { id: r.id })}>{r.batch_number}<span className="block text-xs text-ink-tertiary">{SOURCES.find((s) => s.value === r.source)?.label}{r.channel ? ` · ${r.channel}` : ''}</span></Link> },
          { header: 'Tanggal mutasi', cell: (r) => date(r.statement_date) },
          { header: 'Baris', cell: (r) => `${r.matched_items} cocok / ${r.total_items}` },
          { header: 'Pengecualian terbuka', cell: (r) => <span className={r.exception_items > 0 ? 'font-semibold text-danger' : undefined}>{r.exception_items}</span> },
          { header: 'Nominal', cell: (r) => money(r.total_amount) },
          { header: 'Status', cell: (r) => <span><KeuBadge status={r.status} label={r.status_label} />{r.closed_by && <span className="block text-xs text-ink-tertiary">{r.closed_by} · {dateTime(r.closed_at)}</span>}</span> },
        ]} />
      <FormDialog open={creating} title="Batch rekonsiliasi baru" onClose={() => setCreating(false)}
        fields={[
          { name: 'source', label: 'Sumber', type: 'select', required: true, options: SOURCES },
          { name: 'statement_date', label: 'Tanggal mutasi', type: 'date', required: true },
          { name: 'payment_channel_id', label: 'Kanal (untuk settlement penyedia)', type: 'select', options: opts(lookups?.payment_channels, (c) => c.name), visible: (v) => v.source === 'provider_settlement' },
        ]}
        onSubmit={async (v) => {
          const { data } = await keuAdmin.post<Batch>('reconciliations', v)
          navigate(to(KEU.admin.reconciliation, { id: data.id }))
        }} />
    </KeuPage>
  )
}

export function ReconciliationDetailPage() {
  const { id = '' } = useParams()
  const { data, error, reload } = useLoad(() => keuAdmin.get<Batch & { items: Item[] }>(`reconciliations/${id}`), [id])
  const action = useAction()
  const [importing, setImporting] = useState(false)
  const [resolving, setResolving] = useState<Item | null>(null)
  const canUpdate = usePermission(P.RECON_UPDATE)

  if (error) return <KeuPage title="Rekonsiliasi" trail={[{ label: 'Rekonsiliasi', path: KEU.admin.reconciliations }]}><Alert variant="danger">{describeError(error)}</Alert></KeuPage>
  if (!data) return <p className="text-sm text-ink-secondary">Memuat...</p>
  const open = data.status === 'open'

  return (
    <KeuPage title={`Rekonsiliasi ${data.batch_number}`} description={`${SOURCES.find((s) => s.value === data.source)?.label} · ${date(data.statement_date)}`} trail={[{ label: 'Rekonsiliasi', path: KEU.admin.reconciliations }, { label: data.batch_number }]}
      actions={open ? <div className="flex gap-2">
        {canUpdate && <Button variant="outline" leftIcon={<Upload className="size-4" />} onClick={() => setImporting(true)}>Impor mutasi</Button>}
        <Can permission={P.RECON_FINALIZE}><Button onClick={() => action.run(() => keuAdmin.post(`reconciliations/${id}/close`), (r) => r.message).then((r) => r && reload())}>Tutup batch</Button></Can>
      </div> : undefined}>
      <Feedback state={action} />
      <Card>
        <div className="grid gap-4 sm:grid-cols-5">
          <Figure label="Status" value={<KeuBadge status={data.status} label={data.status_label} />} />
          <Figure label="Baris" value={data.total_items} />
          <Figure label="Cocok" value={data.matched_items} tone="success" />
          <Figure label="Pengecualian terbuka" value={data.exception_items} tone={data.exception_items ? 'danger' : undefined} />
          <Figure label="Total nominal" value={money(data.total_amount)} />
        </div>
        <p className="mt-3 text-xs text-ink-tertiary">Batch hanya dapat ditutup bila semua pengecualian sudah diselesaikan. Petugas yang mencocokkan manual tidak dapat menutup batch yang sama.</p>
      </Card>
      <Card noPadding>
        <DataTable rowKey={(r) => r.id} data={data.items} emptyMessage="Belum ada baris. Impor mutasi terlebih dahulu." columns={[
          { header: 'Tanggal', cell: (r) => date(r.external_date) },
          { header: 'Mutasi', cell: (r) => <span className="whitespace-normal text-xs">{r.external_description ?? '—'}<span className="block text-ink-tertiary">{r.external_reference}</span></span> },
          { header: 'Nominal', cell: (r) => money(r.external_amount) },
          { header: 'Transaksi sistem', cell: (r) => r.transaction ? <Link className="text-primary" to={to(KEU.admin.transaction, { id: r.transaction.id })}>{r.transaction.transaction_number}<span className="block text-xs text-ink-tertiary">{money(r.transaction.amount)} · {r.transaction.student?.nim}</span></Link> : '—' },
          { header: 'Hasil', cell: (r) => <span><KeuBadge status={r.resolution ? 'resolved' : r.result} label={r.result_label} />{r.resolution && <span className="block max-w-xs whitespace-normal text-xs text-ink-tertiary">{r.resolution_label}: {r.resolution_note} ({r.resolved_by})</span>}</span> },
          { header: 'Aksi', cell: (r) => open && canUpdate && r.result !== 'matched' && !r.resolution ? <Button size="sm" variant="outline" onClick={() => setResolving(r)}>Selesaikan</Button> : null },
        ]} />
      </Card>
      {importing && <ImportDialog batchId={id} onClose={() => setImporting(false)} onDone={(m) => { reload(); action.run(async () => m, (x) => x) }} />}
      {resolving && <ResolveDialog item={resolving} onClose={() => setResolving(null)} onDone={(m) => { reload(); action.run(async () => m, (x) => x) }} />}
    </KeuPage>
  )
}

function ImportDialog({ batchId, onClose, onDone }: { batchId: string; onClose: () => void; onDone: (message: string) => void }) {
  const [rows, setRows] = useState<ReturnType<typeof parseStatementCsv>>([])
  const [flagMissing, setFlagMissing] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function readFile(file: File | undefined) {
    setError(null)
    if (!file) return setRows([])
    const parsed = parseStatementCsv(await file.text())
    setRows(parsed)
    if (parsed.length === 0) setError('Tidak ada baris yang terbaca. Pastikan kolom: tanggal, keterangan, nominal, referensi.')
  }

  async function submit() {
    setBusy(true)
    setError(null)

    try {
      const { message } = await keuAdmin.post(`reconciliations/${batchId}/import`, { rows, flag_missing: flagMissing })
      onDone(message)
      onClose()
    } catch (err) {
      setError(describeError(err as NormalizedApiError))
    } finally {
      setBusy(false)
    }
  }

  return (
    <Modal open onClose={onClose} title="Impor mutasi (CSV)" className="max-w-3xl">
      <div className="flex flex-col gap-4">
        <p className="text-sm text-ink-secondary">Ekspor mutasi dari internet banking sebagai CSV dengan kolom <strong>tanggal, keterangan, nominal, referensi</strong> (pemisah koma atau titik koma). Format nominal Indonesia (1.400.000,00) didukung. Berkas dibaca di peramban; hanya baris yang dikirim ke server (maks. 5.000 baris).</p>
        {error && <Alert variant="danger">{error}</Alert>}
        <Input type="file" accept=".csv,.txt" label="Berkas CSV" onChange={(e) => readFile(e.target.files?.[0])} />
        {rows.length > 0 && (
          <div className="max-h-64 overflow-y-auto rounded-lg border border-border">
            <DataTable rowKey={(r) => `${r.date}-${r.amount}-${r.reference}-${r.description}`} data={rows.slice(0, 50)} columns={[
              { header: 'Tanggal', cell: (r) => r.date }, { header: 'Keterangan', cell: (r) => r.description }, { header: 'Nominal', cell: (r) => money(r.amount) }, { header: 'Referensi', cell: (r) => r.reference ?? '—' },
            ]} />
            {rows.length > 50 && <p className="p-2 text-xs text-ink-tertiary">…dan {rows.length - 50} baris lainnya.</p>}
          </div>
        )}
        <Checkbox label="Tandai transaksi sistem pada tanggal ini yang tidak ada di mutasi (missing in statement)" checked={flagMissing} onChange={(e) => setFlagMissing(e.target.checked)} />
        <div className="flex justify-end gap-2">
          <Button variant="outline" onClick={onClose}>Batal</Button>
          <Button isLoading={busy} disabled={rows.length === 0 || rows.length > 5000} onClick={submit}>Impor {rows.length} baris</Button>
        </div>
      </div>
    </Modal>
  )
}

function ResolveDialog({ item, onClose, onDone }: { item: Item; onClose: () => void; onDone: (message: string) => void }) {
  const lookups = useKeuLookups()
  const [student, setStudent] = useState<StudentRef | null>(null)

  return (
    <FormDialog open title="Selesaikan pengecualian" onClose={onClose}
      description={`${item.result_label} · ${money(item.external_amount)} · ${item.external_description ?? ''}. “Diklaim & dialokasikan ke mahasiswa” mencatat dana sebagai pembayaran mahasiswa terpilih (masuk deposit, lalu dapat dipakai untuk tagihan).`}
      prepend={<StudentPicker value={student} onChange={setStudent} label="Mahasiswa (untuk klaim dana tak teridentifikasi)" />}
      fields={[
        { name: 'resolution', label: 'Penyelesaian', type: 'select', required: true, options: (lookups?.enums.reconciliation_resolutions ?? []).filter((o) => item.result === 'unidentified' || o.value !== 'allocated_to_student') },
        { name: 'transaction_id', label: 'ID transaksi sistem (untuk cocok manual)', visible: (v) => v.resolution === 'manual_match' },
        { name: 'note', label: 'Catatan', type: 'textarea', required: true },
      ]}
      onSubmit={async (v) => {
        const { message } = await keuAdmin.post(`reconciliation-items/${item.id}/resolve`, { ...v, student_id: v.resolution === 'allocated_to_student' ? student?.id : null })
        onDone(message)
      }} />
  )
}
