import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { ClipboardCheck, Hand } from 'lucide-react'
import { pmbAdmin } from '@/pmb/api/admin'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminList } from '@/pmb/components/AdminList'
import { FormDialog } from '@/pmb/components/FormDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { Tabs } from '@/pmb/components/Tabs'
import { AdminPage, Can, P, options, useLookups } from '@/pmb/components/admin'
import { usePermission } from '@/hooks/usePermission'
import { StatCard } from '@/components/ui/StatCard'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { dateTime } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'

interface Stats { queue: number; in_review: number; mine: number; revision_requested: number; verified_today: number; rejected: number; open_duplicate_flags: number; my_assignments: Array<{ application_id: string; registration_number: string | null; name: string | null; claimed_until: string | null }> }

type VTab = 'queue' | 'photos' | 'duplicates' | 'recovery'

/** ADM-02/23/33/34 Antrean verifikasi, penugasan, galeri foto, duplikat, pemulihan akun. */
export function VerificationPage() {
  const navigate = useNavigate()
  const lookups = useLookups()
  const canDecide = usePermission(P.VERIFICATION_DECIDE)
  const canOverride = usePermission(P.VERIFICATION_OVERRIDE)
  const stats = useLoad(() => (canDecide ? pmbAdmin.get<Stats>('verification/stats') : Promise.resolve(null)), [canDecide])
  const action = useAction()
  const [tab, setTab] = useState<VTab>('queue')
  const [path, setPath] = useState('')
  const [selected, setSelected] = useState<string[]>([])
  const [assigning, setAssigning] = useState(false)
  const [reload, setReload] = useState(0)
  const reviewers = useLoad(() => pmbAdmin.get<Array<{ id: string; name: string; email: string }>>('staff', { permission: P.VERIFICATION_DECIDE }).catch(() => []), [])

  const claimNext = async () => {
    const result = await action.run(() => pmbAdmin.post<{ application_id: string } | null>('verification/claim-next', { admission_path_id: path || undefined }))
    if (result?.application_id) navigate(to(PMB.admin.application, { id: result.application_id }))
    else if (result === null) action.run(() => Promise.resolve(null), 'Antrean kosong.')
  }

  const s = stats.data

  return (
    <AdminPage title="Verifikasi Administrasi" trail={[{ label: 'Verifikasi' }]} actions={canDecide && (
      <div className="flex items-end gap-2">
        <Select options={options(lookups?.admission_paths, (p) => p.name)} placeholder="Semua jalur" value={path} onChange={(e) => setPath(e.target.value)} />
        <Button leftIcon={<Hand className="size-4" />} isLoading={action.busy} onClick={claimNext}>Ambil berikutnya</Button>
      </div>
    )}>
      <Feedback state={action} />
      {s && (
        <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
          <StatCard label="Antrean" value={String(s.queue)} icon={ClipboardCheck} />
          <StatCard label="Diperiksa" value={String(s.in_review)} icon={ClipboardCheck} />
          <StatCard label="Milik saya" value={String(s.mine)} icon={ClipboardCheck} />
          <StatCard label="Perlu perbaikan" value={String(s.revision_requested)} icon={ClipboardCheck} />
          <StatCard label="Terverifikasi hari ini" value={String(s.verified_today)} icon={ClipboardCheck} />
          <StatCard label="Indikasi duplikat" value={String(s.open_duplicate_flags)} icon={ClipboardCheck} />
        </div>
      )}
      {s && s.my_assignments.length > 0 && (
        <Card title="Klaim saya">
          <ul className="divide-y divide-border text-sm">{s.my_assignments.map((m) => <li key={m.application_id} className="flex justify-between py-2"><Link className="text-primary" to={to(PMB.admin.application, { id: m.application_id })}>{m.name} · {m.registration_number}</Link><span className="text-xs text-ink-tertiary">{m.claimed_until ? `s.d. ${dateTime(m.claimed_until)}` : 'ditugaskan'}</span></li>)}</ul>
        </Card>
      )}
      <Tabs<VTab> active={tab} onChange={setTab} tabs={[{ key: 'queue', label: 'Antrean' }, { key: 'photos', label: 'Galeri pas foto' }, ...(canOverride ? [{ key: 'duplicates' as VTab, label: 'Indikasi duplikat' }, { key: 'recovery' as VTab, label: 'Pemulihan akun' }] : [])]} />

      {tab === 'queue' && (
        <>
          <Can permission={P.VERIFICATION_ASSIGN}>
            <div className="flex flex-wrap items-center gap-2">
              <span className="text-sm text-ink-secondary">{selected.length} dipilih</span>
              <Button size="sm" variant="outline" disabled={selected.length === 0} onClick={() => setAssigning(true)}>Tugaskan ke petugas</Button>
              <Button size="sm" variant="outline" disabled={selected.length === 0} onClick={() => action.run(() => pmbAdmin.post<{ verified: number; skipped: Array<unknown> }>('verification/bulk-verify', { application_ids: selected }, { idempotent: true }), (r) => `${r.verified ?? 0} aplikasi diverifikasi massal.`).then(() => { setSelected([]); setReload(reload + 1) })}>Verifikasi massal</Button>
            </div>
          </Can>
          <AdminList<{ id: string; registration_number: string | null; applicant: { name: string }; path: string | null; wave: string | null; verification_status: string; registration_payment_status: string; reviewer: string | null; submitted_at: string | null }>
            path="applications" reloadKey={reload} rowKey={(r) => r.id} itemLabel="aplikasi" baseParams={{ form_status: 'submitted', verification_status: 'not_started,in_review', sort: 'submitted_at' }}
            filters={[{ name: 'admission_path_id', label: 'Jalur', type: 'select', options: options(lookups?.admission_paths, (p) => p.name) }]}
            columns={[
              { header: '', cell: (r) => <input type="checkbox" checked={selected.includes(r.id)} onChange={(e) => setSelected(e.target.checked ? [...selected, r.id] : selected.filter((x) => x !== r.id))} /> },
              { header: 'Pendaftar', cell: (r) => <Link to={to(PMB.admin.application, { id: r.id })} className="text-primary">{r.applicant.name}<span className="block text-xs text-ink-tertiary">{r.registration_number}</span></Link> },
              { header: 'Jalur', cell: (r) => `${r.path} · ${r.wave}` },
              { header: 'Bayar', cell: (r) => <StatusBadge status={r.registration_payment_status} /> },
              { header: 'Status', cell: (r) => <StatusBadge status={r.verification_status} /> },
              { header: 'Petugas', cell: (r) => r.reviewer ?? '—' },
              { header: 'Dikirim', cell: (r) => dateTime(r.submitted_at) },
            ]} />
          <FormDialog open={assigning} title="Tugaskan verifikasi" description={`${selected.length} aplikasi dibagi ke petugas terpilih.`} fields={[
            { name: 'reviewer_ids', label: 'Petugas verifikasi', type: 'multiselect', required: true, options: (reviewers.data ?? []).map((u) => ({ value: u.id, label: u.name })) },
            { name: 'strategy', label: 'Strategi', type: 'select', required: true, options: [{ value: 'round_robin', label: 'Bergiliran' }, { value: 'by_path', label: 'Per jalur' }] },
          ]} initial={{ strategy: 'round_robin' }} onClose={() => setAssigning(false)} onSubmit={async (v) => {
            await pmbAdmin.post('verification/assignments', { application_ids: selected, reviewer_ids: v.reviewer_ids, strategy: v.strategy })
            setSelected([])
            setReload(reload + 1)
          }} />
        </>
      )}
      {tab === 'photos' && <PhotoGallery />}
      {tab === 'duplicates' && <DuplicateFlags />}
      {tab === 'recovery' && <RecoveryRequests />}
    </AdminPage>
  )
}

function PhotoGallery() {
  const photos = useLoad(() => pmbAdmin.list<{ id: string; registration_number: string | null; name: string | null; url: string | null }>('verification/photos', { per_page: 60 }), [])
  const [selected, setSelected] = useState<string[]>([])
  const action = useAction()

  return (
    <Card title="Pas foto menunggu persetujuan" description="Klik foto untuk memilih; foto yang tidak sesuai ditinjau satu per satu di halaman pendaftar." actions={<Button size="sm" disabled={selected.length === 0} isLoading={action.busy} onClick={() => action.run(() => pmbAdmin.post<{ approved: number }>('verification/bulk-photos', { document_ids: selected }), (r) => `${r.approved} foto disetujui.`).then(() => { setSelected([]); photos.reload() })}>Setujui {selected.length} foto</Button>}>
      <Feedback state={action} />
      {photos.data?.data.length === 0 && <p className="text-sm text-ink-secondary">Tidak ada foto yang menunggu.</p>}
      <div className="grid grid-cols-3 gap-3 sm:grid-cols-6">
        {(photos.data?.data ?? []).map((p) => (
          <button key={p.id} type="button" onClick={() => setSelected(selected.includes(p.id) ? selected.filter((x) => x !== p.id) : [...selected, p.id])} className={`overflow-hidden rounded-lg border-2 text-left ${selected.includes(p.id) ? 'border-primary' : 'border-transparent'}`}>
            {p.url ? <img src={p.url} alt={p.name ?? ''} className="aspect-[3/4] w-full object-cover" /> : <div className="flex aspect-[3/4] items-center justify-center bg-surface-hover text-xs">dipindai</div>}
            <p className="truncate p-1 text-xs">{p.name}</p>
          </button>
        ))}
      </div>
    </Card>
  )
}

function DuplicateFlags() {
  const [reload, setReload] = useState(0)
  const [resolving, setResolving] = useState<{ id: string } | null>(null)

  return (
    <>
      <AdminList<{ id: string; type: string; status: string; similarity_score: string | null; note: string | null; account: { name: string; email: string } | null; related_account: { name: string; email: string } | null; application: { id: string; registration_number: string | null } | null; created_at: string | null }>
        path="duplicate-flags" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="indikasi"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'open', label: 'Terbuka' }, { value: 'confirmed_duplicate', label: 'Duplikat' }, { value: 'not_duplicate', label: 'Bukan duplikat' }, { value: 'merged', label: 'Digabung' }] }]}
        columns={[
          { header: 'Akun', cell: (r) => <span>{r.account?.name}<span className="block text-xs text-ink-tertiary">{r.account?.email}</span></span> },
          { header: 'Akun terkait', cell: (r) => (r.related_account ? <span>{r.related_account.name}<span className="block text-xs text-ink-tertiary">{r.related_account.email}</span></span> : '—') },
          { header: 'Jenis', cell: (r) => <Badge>{r.type}</Badge> },
          { header: 'Skor', cell: (r) => r.similarity_score ?? '—' },
          { header: 'Aplikasi', cell: (r) => (r.application ? <Link className="text-primary" to={to(PMB.admin.application, { id: r.application.id })}>{r.application.registration_number ?? 'draf'}</Link> : '—') },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
          { header: '', cell: (r) => r.status === 'open' && <Button size="sm" variant="ghost" onClick={() => setResolving(r)}>Putuskan</Button> },
        ]} />
      <FormDialog open={resolving !== null} title="Putuskan indikasi duplikat" description="Konfirmasi duplikat menonaktifkan akun sekunder dan menarik aplikasi aktifnya." fields={[
        { name: 'status', label: 'Keputusan', type: 'select', required: true, options: [{ value: 'not_duplicate', label: 'Bukan duplikat' }, { value: 'confirmed_duplicate', label: 'Konfirmasi duplikat' }, { value: 'merged', label: 'Gabungkan' }] },
        { name: 'secondary_account_id', label: 'ID akun sekunder (untuk konfirmasi/gabung)' },
        { name: 'note', label: 'Catatan', type: 'textarea', required: true },
      ]} onClose={() => setResolving(null)} onSubmit={async (v) => { if (resolving) await pmbAdmin.patch(`duplicate-flags/${resolving.id}`, v); setReload(reload + 1) }} />
    </>
  )
}

function RecoveryRequests() {
  const [reload, setReload] = useState(0)
  const [deciding, setDeciding] = useState<{ id: string } | null>(null)
  const action = useAction()

  return (
    <>
      <Feedback state={action} />
      <Alert variant="info">Cocokkan data dengan foto identitas sebelum menyetujui. Melihat foto identitas tercatat di log akses sensitif.</Alert>
      <AdminList<{ id: string; status: string; full_name: string; birth_date: string | null; new_email: string; account: { name: string; email: string } | null; matches_account: boolean; identity_file_available: boolean; created_at: string | null }>
        path="recovery-requests" reloadKey={reload} search={false} rowKey={(r) => r.id} itemLabel="permintaan"
        filters={[{ name: 'status', label: 'Status', type: 'select', options: [{ value: 'pending', label: 'Menunggu' }, { value: 'approved', label: 'Disetujui' }, { value: 'rejected', label: 'Ditolak' }] }]}
        columns={[
          { header: 'Pemohon', cell: (r) => <span>{r.full_name}<span className="block text-xs text-ink-tertiary">lahir {dateTime(r.birth_date)}</span></span> },
          { header: 'Email baru', cell: (r) => r.new_email },
          { header: 'Akun cocok', cell: (r) => (r.account ? `${r.account.name} (${r.account.email})` : <Badge variant="warning">Tidak ditemukan</Badge>) },
          { header: 'Diajukan', cell: (r) => dateTime(r.created_at) },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
          { header: '', cell: (r) => (
            <div className="flex gap-1">
              {r.identity_file_available && <Button size="sm" variant="ghost" onClick={() => action.run(async () => { const res = await pmbAdmin.post<{ url: string }>(`recovery-requests/${r.id}/decision`, { view_identity: true }); window.open(res.url, '_blank', 'noopener') })}>Lihat identitas</Button>}
              {r.status === 'pending' && <Button size="sm" variant="ghost" onClick={() => setDeciding(r)}>Putuskan</Button>}
            </div>
          ) },
        ]} />
      <FormDialog open={deciding !== null} title="Putuskan pemulihan akun" fields={[{ name: 'approve', label: 'Setujui (email akun diganti & tautan atur sandi dikirim)', type: 'checkbox' }, { name: 'note', label: 'Catatan', type: 'textarea', required: true }]} onClose={() => setDeciding(null)} onSubmit={async (v) => { if (deciding) await pmbAdmin.post(`recovery-requests/${deciding.id}/decision`, { approve: Boolean(v.approve), note: v.note }); setReload(reload + 1) }} />
    </>
  )
}

