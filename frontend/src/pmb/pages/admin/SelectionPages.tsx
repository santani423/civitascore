import { useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Play } from 'lucide-react'
import { pmbAdmin } from '@/pmb/api/admin'
import { adminClient } from '@/pmb/api/http'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminList } from '@/pmb/components/AdminList'
import { FormDialog } from '@/pmb/components/FormDialog'
import { ReasonDialog } from '@/pmb/components/ReasonDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { Tabs } from '@/pmb/components/Tabs'
import { KeyValue } from '@/pmb/components/KeyValue'
import { AdminPage, Can, P, WavePathPicker, useLookups } from '@/pmb/components/admin'
import { usePermission } from '@/hooks/usePermission'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { Select } from '@/components/ui/Select'
import { dateTime } from '@/pmb/lib/format'
import { PMB, to } from '@/pmb/routes'

type STab = 'runs' | 'scores' | 'import'

interface RunRow { id: string; status: string; run_type: string; wave: string | null; path: string | null; wave_path_id: string; scheme: string | null; summary: Record<string, unknown> | null; has_unresolved_ties: boolean; result_hash: string | null; created_by: string | null; computed_at: string | null; approved_at: string | null; failure_reason: string | null; created_at: string | null }

/** ADM-55/56 Seleksi: run per gelombang-jalur, matriks nilai, impor nilai. */
export function SelectionPage() {
  const [tab, setTab] = useState<STab>('runs')
  const [target, setTarget] = useState<{ period?: string; wave?: string; wavePath?: string }>({})

  return (
    <AdminPage title="Proses Seleksi" trail={[{ label: 'Seleksi' }]}>
      <WavePathPicker value={target} onChange={setTarget} />
      <Tabs<STab> active={tab} onChange={setTab} tabs={[{ key: 'runs', label: 'Run seleksi' }, { key: 'scores', label: 'Matriks nilai' }, { key: 'import', label: 'Impor nilai' }]} />
      {tab === 'runs' && <RunsTab wavePath={target.wavePath} />}
      {tab === 'scores' && (target.wavePath ? <ScoresTab wavePath={target.wavePath} /> : <Alert variant="info">Pilih gelombang-jalur.</Alert>)}
      {tab === 'import' && <ImportTab wave={target.wave} />}
    </AdminPage>
  )
}

function RunsTab({ wavePath }: { wavePath?: string }) {
  const [reload, setReload] = useState(0)
  const action = useAction()

  return (
    <>
      <Feedback state={action} />
      <AdminList<RunRow> path="selection-runs" reloadKey={reload} search={false} baseParams={{ wave_path_id: wavePath }} rowKey={(r) => r.id} itemLabel="run"
        toolbar={<Can permission={P.SELECTION_MANAGE}><Button size="sm" disabled={!wavePath} leftIcon={<Play className="size-4" />} isLoading={action.busy} onClick={() => action.run(() => pmbAdmin.post('selection-runs', { wave_path_id: wavePath }), 'Run dibuat; perhitungan selesai dalam beberapa saat.').then(() => setReload(reload + 1))}>Jalankan seleksi</Button></Can>}
        columns={[
          { header: 'Run', cell: (r) => <Link className="font-medium text-primary" to={to(PMB.admin.selectionRun, { id: r.id })}>{r.wave} · {r.path}<span className="block text-xs text-ink-tertiary">{r.scheme} · {r.run_type}</span></Link> },
          { header: 'Ringkasan', cell: (r) => { const by = (r.summary?.by_recommendation ?? {}) as Record<string, number>; return r.summary ? `${r.summary.eligible ?? 0} eligible · ${by.pass ?? 0} lulus · ${by.waitlist ?? 0} cadangan` : '—' } },
          { header: 'Seri', cell: (r) => (r.has_unresolved_ties ? <Badge variant="warning">Ada seri</Badge> : '—') },
          { header: 'Dihitung', cell: (r) => dateTime(r.computed_at) },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
        ]} />
    </>
  )
}

function ScoresTab({ wavePath }: { wavePath: string }) {
  const [page, setPage] = useState(1)
  const data = useLoad(() => adminClient.get<{ data: Array<{ application_id: string; registration_number: string | null; name: string | null; selection_status: string; scores: Record<string, Array<{ id: string; raw_score: string | null; passed: boolean | null; source: string; locked: boolean }>> }>; meta: { components: Array<{ id: string; code: string; name: string; score_type: string; raw_min: string | null; raw_max: string | null; weight: string }>; last_page: number; total: number } }>(`/pmb/admin/wave-paths/${wavePath}/scores`, { params: { page } }).then((r) => r.data), [wavePath, page])
  const canManage = usePermission(P.SELECTION_MANAGE)
  const canCorrect = usePermission(P.RESULTS_CORRECT)
  const [editing, setEditing] = useState<{ id: string; locked: boolean; raw_score: string | null; passed: boolean | null; pass_fail: boolean } | null>(null)
  const components = data.data?.meta.components ?? []

  return (
    <Card noPadding>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead><tr className="border-b border-border text-left text-xs text-ink-tertiary"><th className="p-2">Pendaftar</th><th className="p-2">Status</th>{components.map((c) => <th key={c.id} className="p-2">{c.name}<span className="block font-normal">bobot {Number(c.weight)}</span></th>)}</tr></thead>
          <tbody>
            {(data.data?.data ?? []).map((row) => (
              <tr key={row.application_id} className="border-b border-border">
                <td className="p-2"><Link className="text-primary" to={to(PMB.admin.application, { id: row.application_id })}>{row.name}</Link><span className="block text-xs text-ink-tertiary">{row.registration_number}</span></td>
                <td className="p-2"><StatusBadge status={row.selection_status} /></td>
                {components.map((c) => {
                  const scores = row.scores[c.code] ?? []

                  return (
                    <td key={c.id} className="p-2">
                      {scores.length === 0 && <span className="text-ink-tertiary">—</span>}
                      {scores.map((sc) => (
                        <button key={sc.id} type="button" disabled={!(sc.locked ? canCorrect : canManage)} onClick={() => setEditing({ id: sc.id, locked: sc.locked, raw_score: sc.raw_score, passed: sc.passed, pass_fail: c.score_type === 'pass_fail' })} className="mr-1 rounded px-1 hover:bg-surface-hover">
                          {c.score_type === 'pass_fail' ? (sc.passed ? 'Lulus' : 'Tidak') : sc.raw_score}{sc.locked && ' 🔒'}
                        </button>
                      ))}
                    </td>
                  )
                })}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {data.data && data.data.meta.last_page > 1 && <div className="flex justify-between p-3 text-sm"><Button size="sm" variant="outline" disabled={page <= 1} onClick={() => setPage(page - 1)}>Sebelumnya</Button><span>Halaman {page}/{data.data.meta.last_page} · {data.data.meta.total} pendaftar</span><Button size="sm" variant="outline" disabled={page >= data.data.meta.last_page} onClick={() => setPage(page + 1)}>Berikutnya</Button></div>}
      <FormDialog open={editing !== null} title={editing?.locked ? 'Ajukan koreksi nilai terkunci' : 'Koreksi nilai'} description={editing?.locked ? 'Nilai sudah dikunci oleh run — koreksi memerlukan persetujuan pejabat pengesah.' : undefined} fields={[
        ...(editing?.pass_fail ? [{ name: 'passed', label: 'Hasil', type: 'select' as const, options: [{ value: '1', label: 'Lulus' }, { value: '0', label: 'Tidak' }] }] : [{ name: 'raw_score', label: 'Nilai baru', type: 'number' as const }]),
        { name: 'reason', label: 'Alasan', type: 'textarea', required: true },
        ...(editing?.locked ? [{ name: 'evidence', label: 'Bukti (opsional)', type: 'file' as const }] : []),
      ]} initial={{ raw_score: editing?.raw_score, passed: editing?.passed === null || editing?.passed === undefined ? '' : editing.passed ? '1' : '0' }} onClose={() => setEditing(null)} onSubmit={async (v) => {
        if (!editing) return
        const passed = v.passed === undefined || v.passed === null ? undefined : v.passed === '1'

        if (editing.locked) {
          const form = new FormData()
          form.append('selection_score_id', editing.id)
          if (v.raw_score !== null && v.raw_score !== undefined) form.append('raw_score', String(v.raw_score))
          if (passed !== undefined) form.append('passed', passed ? '1' : '0')
          form.append('reason', String(v.reason))
          if (v.evidence instanceof File) form.append('evidence', v.evidence)
          await adminClient.post('/pmb/admin/score-corrections', form)
        } else {
          await pmbAdmin.patch(`selection-scores/${editing.id}`, { raw_score: v.raw_score, passed, reason: v.reason })
        }

        data.reload()
      }} />
    </Card>
  )
}

function ImportTab({ wave }: { wave?: string }) {
  const schedules = useLoad(() => (wave ? pmbAdmin.get<Array<{ id: string; title: string; selection_component_id: string; component?: { name: string } | null }>>(`waves/${wave}/exam-schedules`) : Promise.resolve([])), [wave])
  const [schedule, setSchedule] = useState('')
  const [file, setFile] = useState<File | null>(null)
  const [result, setResult] = useState<{ id: string; status: string; valid_count: number; errors: Array<{ row: number; message: string }> | null; preview: Array<Record<string, unknown>> } | null>(null)
  const action = useAction()
  const selected = schedules.data?.find((s) => s.id === schedule)

  const validate = () => action.run(async () => {
    if (!selected || !file) return
    const form = new FormData()
    form.append('exam_schedule_id', selected.id)
    form.append('selection_component_id', selected.selection_component_id)
    form.append('file', file)
    const { data } = await adminClient.post<{ data: NonNullable<typeof result> }>('/pmb/admin/score-imports', form)
    setResult(data.data)
  })

  return (
    <Card title="Impor nilai dari berkas" description="Format XLSX/CSV kolom: participant_number, score. Tahap 1 memvalidasi tanpa menyimpan; tahap 2 menyimpan baris valid.">
      <Feedback state={action} />
      {!wave && <Alert variant="info">Pilih gelombang.</Alert>}
      <div className="flex flex-wrap items-end gap-3">
        <Select label="Jadwal ujian" placeholder="Pilih jadwal" options={(schedules.data ?? []).map((s) => ({ value: s.id, label: `${s.title} (${s.component?.name ?? ''})` }))} value={schedule} onChange={(e) => setSchedule(e.target.value)} />
        <input type="file" accept=".xlsx,.csv" onChange={(e) => setFile(e.target.files?.[0] ?? null)} className="text-sm" />
        <Can permission={P.SCORES_IMPORT}><Button disabled={!selected || !file} isLoading={action.busy} onClick={validate}>Validasi</Button></Can>
      </div>
      {result && (
        <div className="mt-4 flex flex-col gap-3">
          <Alert variant={result.errors && result.errors.length > 0 ? 'warning' : 'success'}>{result.valid_count} baris valid{result.errors && result.errors.length > 0 ? `, ${result.errors.length} baris galat` : ''}.</Alert>
          {result.errors && result.errors.length > 0 && <ul className="list-disc pl-5 text-sm text-danger">{result.errors.slice(0, 50).map((e, i) => <li key={i}>Baris {e.row}: {e.message}</li>)}</ul>}
          <Can permission={P.SCORES_IMPORT}><div><Button disabled={result.valid_count === 0 || result.status === 'committed'} onClick={() => action.run(() => pmbAdmin.post<{ saved: number }>(`score-imports/${result.id}/commit`), (r) => `${r.saved} nilai disimpan.`).then(() => setResult({ ...result, status: 'committed' }))}>Simpan {result.valid_count} nilai</Button></div></Can>
        </div>
      )}
    </Card>
  )
}

interface RunDetail { id: string; status: string; run_type: string; wave_path_id: string; summary: Record<string, unknown> | null; quota_snapshot: Record<string, unknown> | null; result_hash: string | null; current_hash: string | null; has_unresolved_ties: boolean; review_note: string | null; failure_reason: string | null; wave: { name: string; announcement_at: string } | null; path: { name: string } | null; scheme: { code: string; version: number; passing_score: string | null; allocation_method: string } | null; components: Array<{ id: string; code: string; name: string; weight: string }>; created_by: string | null; submitted_by: string | null; computed_at: string | null; approved_at: string | null; approval: { id: string; status: string; steps: Array<{ id: string; sequence: number; status: string; approver: string | null; acted_at: string | null }> } | null; batches: Array<{ id: string; scheduled_publish_at: string; status: string; published_at: string | null; decisions_count: number }> }
interface ResultRow { id: string; application_id: string; registration_number: string | null; name: string | null; eligible: boolean; ineligible_reason: string | null; component_scores: Record<string, { normalized?: number; raw?: number | null; passed?: boolean | null }> | null; final_score: string | null; rank: number | null; recommendation: string; fail_reason: string | null; effective_recommendation: string; assigned_program: string | null; assigned_system: string | null; tie_unresolved: boolean; override: { recommendation: string; reason: string } | null }

/** ADM-56 Detail run: ringkasan, peringkat, override, seri, ajukan pengesahan, status approval. */
export function RunDetailPage() {
  const { id = '' } = useParams()
  const run = useLoad(() => pmbAdmin.get<RunDetail>(`selection-runs/${id}`), [id])
  const action = useAction()
  const [reload, setReload] = useState(0)
  const [overriding, setOverriding] = useState<ResultRow | null>(null)
  const [submitting, setSubmitting] = useState(false)
  const [tieGroup, setTieGroup] = useState<string[]>([])
  const [resolving, setResolving] = useState(false)
  const lookups = useLookups()

  if (!run.data) return <p className="text-sm text-ink-secondary">{run.error?.message ?? 'Memuat...'}</p>

  const r = run.data
  const reviewable = ['computed', 'under_review'].includes(r.status)
  const programName = (pid?: string) => lookups?.study_programs.find((p) => p.id === pid)?.name ?? pid ?? ''
  const systemName = (sid?: string) => lookups?.study_systems.find((x) => x.id === sid)?.name ?? sid ?? ''
  type SnapshotQuota = { study_program_id?: string; study_system_id?: string; capacity?: number; remaining?: number; assigned?: number; waitlisted?: number }
  const quotaOptions = Object.entries((r.quota_snapshot ?? {}) as Record<string, SnapshotQuota>).map(([qid, q]) => ({ value: qid, label: `${programName(q.study_program_id)} — ${systemName(q.study_system_id)} (sisa ${q.remaining ?? '—'})` }))
  const perQuota = Object.entries(((r.summary?.per_quota ?? {}) as Record<string, SnapshotQuota>))
  const byRecommendation = (r.summary?.by_recommendation ?? {}) as Record<string, number>

  return (
    <AdminPage title={`Run seleksi — ${r.wave?.name ?? ''} · ${r.path?.name ?? ''}`} description={`${r.scheme?.code} v${r.scheme?.version} · ${r.run_type}`} trail={[{ label: 'Seleksi', path: PMB.admin.selection }, { label: 'Run' }]}
      actions={
        <div className="flex flex-wrap gap-2">
          <StatusBadge status={r.status} />
          {r.status === 'computed' && <Can permission={P.SELECTION_MANAGE}><Button size="sm" variant="outline" onClick={() => action.run(() => pmbAdmin.post(`selection-runs/${id}/review`), 'Peninjauan dimulai.').then(run.reload)}>Mulai tinjau</Button></Can>}
          {reviewable && <Can permission={P.SELECTION_SUBMIT}><Button size="sm" disabled={r.has_unresolved_ties} onClick={() => setSubmitting(true)}>Ajukan pengesahan</Button></Can>}
        </div>
      }>
      <Feedback state={action} />
      {r.failure_reason && <Alert variant="danger">{r.failure_reason}</Alert>}
      {r.has_unresolved_ties && <Alert variant="warning">Ada peserta dengan skor seri pada batas kuota. Pilih kelompok seri di tabel dan tetapkan urutan (menghasilkan run baru).</Alert>}
      <div className="grid gap-4 lg:grid-cols-3">
        <Card title="Ringkasan" className="lg:col-span-2">
          <KeyValue columns={3} items={[
            ['Peserta', String(r.summary?.total ?? 0)], ['Eligible', String(r.summary?.eligible ?? 0)], ['Lulus', String(byRecommendation.pass ?? 0)],
            ['Cadangan', String(byRecommendation.waitlist ?? 0)], ['Tidak lulus', String(byRecommendation.fail ?? 0)], ['Seri belum selesai', String(r.summary?.ties_unresolved ?? 0)],
            ['Ambang', r.scheme?.passing_score ?? '—'], ['Dihitung', dateTime(r.computed_at)], ['Disahkan', dateTime(r.approved_at)],
          ]} />
          {perQuota.length > 0 && (
            <table className="mt-4 w-full text-sm">
              <thead><tr className="text-left text-xs text-ink-tertiary"><th>Prodi</th><th>Sistem</th><th>Kapasitas</th><th>Sisa kursi</th><th>Ditempatkan</th><th>Cadangan</th></tr></thead>
              <tbody>{perQuota.map(([qid, q]) => <tr key={qid} className="border-t border-border"><td>{programName(q.study_program_id)}</td><td>{systemName(q.study_system_id)}</td><td>{q.capacity}</td><td>{q.remaining}</td><td>{q.assigned}</td><td>{q.waitlisted}</td></tr>)}</tbody>
            </table>
          )}
          <p className="mt-3 break-all font-mono text-xs text-ink-tertiary">Hash: {r.result_hash}{r.current_hash && r.current_hash !== r.result_hash ? ` → ${r.current_hash} (berubah karena override)` : ''}</p>
        </Card>
        <Card title="Pengesahan">
          {r.approval ? (
            <>
              <ol className="space-y-1 text-sm">{r.approval.steps.map((st) => <li key={st.id} className="flex justify-between"><span>Langkah {st.sequence}{st.approver ? ` · ${st.approver}` : ''}</span><StatusBadge status={st.status} /></li>)}</ol>
              <Link className="mt-3 inline-block text-sm text-primary" to={`/persetujuan/${r.approval.id}`}>Buka di Persetujuan untuk menyetujui/menolak</Link>
            </>
          ) : <p className="text-sm text-ink-secondary">Belum diajukan.</p>}
          {r.batches.map((b) => <p key={b.id} className="mt-2 text-sm">Pengumuman: {dateTime(b.scheduled_publish_at)} <StatusBadge status={b.status} /></p>)}
        </Card>
      </div>
      {tieGroup.length > 0 && <Alert variant="info">{tieGroup.length} peserta dipilih sebagai satu kelompok seri (urutan pilihan = urutan peringkat). <Button size="sm" className="ml-2" onClick={() => setResolving(true)} disabled={tieGroup.length < 2}>Tetapkan urutan</Button><Button size="sm" variant="ghost" onClick={() => setTieGroup([])}>Batal</Button></Alert>}
      <AdminList<ResultRow> path={`selection-runs/${id}/results`} reloadKey={reload} rowKey={(x) => x.id} itemLabel="peserta"
        filters={[{ name: 'recommendation', label: 'Rekomendasi', type: 'select', options: [{ value: 'pass', label: 'Lulus' }, { value: 'waitlist', label: 'Cadangan' }, { value: 'fail', label: 'Tidak lulus' }] }, { name: 'ties_only', label: 'Hanya seri', type: 'select', options: [{ value: '1', label: 'Ya' }] }]}
        columns={[
          ...(reviewable ? [{ header: '', cell: (x: ResultRow) => x.tie_unresolved && <input type="checkbox" checked={tieGroup.includes(x.application_id)} onChange={(e) => setTieGroup(e.target.checked ? [...tieGroup, x.application_id] : tieGroup.filter((y) => y !== x.application_id))} /> }] : []),
          { header: 'Rank', cell: (x) => x.rank ?? '—' },
          { header: 'Pendaftar', cell: (x) => <Link className="text-primary" to={to(PMB.admin.application, { id: x.application_id })}>{x.name}<span className="block text-xs text-ink-tertiary">{x.registration_number}</span></Link> },
          ...r.components.map((c) => ({ header: c.code, cell: (x: ResultRow) => { const sc = x.component_scores?.[c.code]; return sc ? (sc.normalized !== undefined ? Number(sc.normalized).toFixed(2) : sc.passed === null || sc.passed === undefined ? '—' : sc.passed ? 'L' : 'TL') : '—' } })),
          { header: 'Skor akhir', cell: (x) => x.final_score ?? '—' },
          { header: 'Rekomendasi', cell: (x) => <span className="flex flex-wrap gap-1"><StatusBadge status={x.effective_recommendation} />{x.override && <Badge variant="warning" title={x.override.reason}>override</Badge>}{x.tie_unresolved && <Badge variant="warning">seri</Badge>}</span> },
          { header: 'Ditempatkan', cell: (x) => (x.assigned_program ? `${x.assigned_program} · ${x.assigned_system}` : x.fail_reason ?? x.ineligible_reason ?? '—') },
          { header: '', cell: (x) => reviewable && <Can permission={P.SELECTION_MANAGE}><Button size="sm" variant="ghost" onClick={() => setOverriding(x)}>Override</Button></Can> },
        ]} />
      <FormDialog open={overriding !== null} title={`Override ${overriding?.name ?? ''}`} description="Override dicatat dengan alasan dan mengubah hash hasil." fields={[
        { name: 'recommendation', label: 'Rekomendasi', type: 'select', required: true, options: [{ value: 'pass', label: 'Lulus' }, { value: 'waitlist', label: 'Cadangan' }, { value: 'fail', label: 'Tidak lulus' }] },
        { name: 'quota_id', label: 'Kuota tujuan', type: 'select', options: quotaOptions, visible: (v) => v.recommendation === 'pass' },
        { name: 'reason', label: 'Alasan', type: 'textarea', required: true },
      ]} onClose={() => setOverriding(null)} onSubmit={async (v) => { if (overriding) await pmbAdmin.patch(`selection-run-results/${overriding.id}/override`, v); setReload(reload + 1); run.reload() }} />
      <ReasonDialog open={submitting} title="Ajukan pengesahan hasil" description={`Hasil (hash ${r.current_hash ?? r.result_hash}) dikirim ke alur persetujuan; setelah disahkan, pengumuman dijadwalkan pada ${dateTime(r.wave?.announcement_at)}.`} reasonName="note" reasonLabel="Catatan pengajuan" onClose={() => setSubmitting(false)} onConfirm={async (v) => { await pmbAdmin.post(`selection-runs/${id}/submit`, v); run.reload() }} />
      <ReasonDialog open={resolving} title="Tetapkan urutan seri" description="Urutan mengikuti urutan centang. Run baru dihitung dengan urutan manual ini." onClose={() => setResolving(false)} onConfirm={async (v) => { await pmbAdmin.post(`selection-runs/${id}/resolve-ties`, { groups: [{ application_ids_ordered: tieGroup, reason: v.reason }] }); setTieGroup([]); run.reload() }} />
    </AdminPage>
  )
}

