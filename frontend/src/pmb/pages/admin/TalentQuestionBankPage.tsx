import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Copy, Eye } from 'lucide-react'
import { AdminList } from '@/pmb/components/AdminList'
import { AdminPage } from '@/pmb/components/admin'
import { FormDialog } from '@/pmb/components/FormDialog'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { useLoad } from '@/pmb/components/useLoad'
import { VersionBadge } from '@/pmb/components/talentShared'
import { LIKERT_LABEL, useTypeOptions } from '@/pmb/lib/talent'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { Modal } from '@/components/ui/Modal'
import { usePermission } from '@/hooks/usePermission'
import { PMB, to } from '@/pmb/routes'
import { talentApi, TP, type ItemPreview, type QuestionRow } from '@/pmb/api/talent'

const DIMENSION_OPTIONS = ['R', 'I', 'A', 'S', 'E', 'C'].map((code) => ({ value: code, label: code }))

/**
 * Bank soal lintas versi: telusuri, pratinjau, dan salin butir ke draft jenis yang sama.
 * Pembuatan dan pengubahan dilakukan di tab Pertanyaan pada halaman versi, sehingga selalu
 * terikat ke versi draft yang tepat.
 */
export function TalentQuestionBankPage() {
  const [reload, setReload] = useState(0)
  const { options: typeOptions, types } = useTypeOptions()
  const action = useAction()
  const canCopy = usePermission(TP.QUESTIONS_CREATE)
  const [previewing, setPreviewing] = useState<string | null>(null)
  const [copying, setCopying] = useState<QuestionRow | null>(null)
  const preview = useLoad(() => (previewing ? talentApi.preview(previewing) : Promise.resolve(null as ItemPreview | null)), [previewing])
  const drafts = useLoad(() => (copying?.version ? talentApi.versions(copying.version.assessment_type_id) : Promise.resolve([])), [copying?.id])

  return (
    <AdminPage title="Bank Soal" description="Pertanyaan RIASEC dari seluruh versi. Hanya pertanyaan di versi draft yang dapat diubah; pertanyaan yang sudah dijawab dikunci."
      trail={[{ label: 'One Atma Talent', path: PMB.admin.talent }]}>
      <Feedback state={action} />
      <AdminList<QuestionRow>
        path="assessment-questions"
        reloadKey={reload}
        rowKey={(q) => q.id}
        itemLabel="pertanyaan"
        filters={[
          { name: 'type_id', label: 'Jenis asesmen', type: 'select', options: typeOptions },
          { name: 'dimension', label: 'Dimensi', type: 'select', options: DIMENSION_OPTIONS },
          { name: 'status', label: 'Status', type: 'select', options: [{ value: 'draft', label: 'Draft' }, { value: 'approved', label: 'Disetujui' }] },
        ]}
        columns={[
          { header: 'Kode', cell: (q) => <span className="font-mono text-xs">{q.code ?? '—'}</span> },
          { header: 'Pertanyaan', cell: (q) => <span className="line-clamp-2">{q.stem}</span> },
          { header: 'Dimensi', cell: (q) => <span className="font-mono">{q.dimension?.code ?? '—'}</span> },
          { header: 'Versi', cell: (q) => q.version ? <Link className="text-primary hover:underline" to={to(PMB.admin.talentVersion, { id: q.version.id })}>Versi {q.version.version_no}<span className="block text-xs text-ink-tertiary">{q.version.title}</span></Link> : '—' },
          { header: 'Status versi', cell: (q) => q.version ? <VersionBadge status={q.version.status} /> : '—' },
          { header: 'Status', cell: (q) => <Badge variant={q.status === 'approved' ? 'success' : 'neutral'}>{q.status === 'approved' ? 'Disetujui' : 'Draft'}</Badge> },
          { header: 'Dipakai', cell: (q) => q.in_use ? <Badge variant="warning">Dijawab</Badge> : <span className="text-ink-tertiary">—</span> },
          { header: 'Aksi', cell: (q) => (
            <div className="flex gap-1">
              <Button size="sm" variant="ghost" aria-label="Pratinjau" onClick={() => setPreviewing(q.id)}><Eye className="size-4" /></Button>
              {canCopy && q.version && <Button size="sm" variant="ghost" aria-label="Salin ke draft" leftIcon={<Copy className="size-4" />} onClick={() => setCopying(q)}>Salin</Button>}
            </div>
          ) },
        ]}
        baseParams={{ sort: 'created_at', direction: 'desc' }}
        emptyMessage="Belum ada pertanyaan. Tambahkan pertanyaan di tab Pertanyaan pada halaman versi."
      />

      <Modal open={previewing !== null} onClose={() => setPreviewing(null)} title="Pratinjau peserta">
        {preview.data && (
          <div className="flex flex-col gap-4">
            <p className="text-base text-ink-primary">{preview.data.stem}</p>
            <ol className="grid gap-2 sm:grid-cols-5">
              {Array.from({ length: preview.data.scale.max - preview.data.scale.min + 1 }, (_, i) => preview.data!.scale.min + i).map((n) => (
                <li key={n} className="rounded-lg border border-border px-3 py-3 text-center text-sm"><span className="block text-lg font-semibold tabular-nums">{n}</span>{LIKERT_LABEL[n] ?? n}</li>
              ))}
            </ol>
          </div>
        )}
      </Modal>

      <FormDialog
        open={copying !== null}
        title="Salin ke versi draft"
        description="Salinan dibuat di versi draft yang dipilih. Dimensi dengan kode yang sama harus sudah ada di versi tujuan."
        fields={[{
          name: 'version_id',
          label: 'Versi tujuan',
          type: 'select',
          required: true,
          options: (drafts.data ?? []).filter((d) => d.status === 'draft' && d.id !== copying?.version?.id).map((d) => ({ value: d.id, label: `Versi ${d.version_no} · ${d.title}` })),
          hint: types.length === 0 ? undefined : 'Hanya versi draft dari jenis asesmen yang sama.',
        }]}
        onClose={() => setCopying(null)}
        onSubmit={async (values) => {
          if (!copying) return
          await action.run(() => talentApi.copyItem(copying.id, String(values.version_id)), 'Pertanyaan disalin ke versi draft.')
          setCopying(null)
          setReload((n) => n + 1)
        }}
      />
    </AdminPage>
  )
}
