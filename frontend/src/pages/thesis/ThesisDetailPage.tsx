import { useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { thesisService } from '@/services/thesisService'
import type { ThesisStatus } from '@/types/thesis'
import { ROUTES } from '@/constants/routes'

const STATUS_LABEL: Record<ThesisStatus, string> = {
  proposal: 'Pengajuan Judul',
  bimbingan: 'Bimbingan',
  seminar_proposal: 'Seminar Proposal',
  penelitian: 'Penelitian',
  sidang: 'Sidang',
  selesai: 'Selesai',
}

const STATUS_VARIANT: Record<ThesisStatus, BadgeVariant> = {
  proposal: 'neutral',
  bimbingan: 'info',
  seminar_proposal: 'info',
  penelitian: 'warning',
  sidang: 'warning',
  selesai: 'success',
}

function DetailField({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value ?? '-'}</p>
    </div>
  )
}

export function ThesisDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getThesis = useCallback(() => thesisService.show(id ?? ''), [id])
  const { data: thesis, isLoading, error } = useFetch(getThesis)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Skripsi"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Skripsi', path: ROUTES.skripsi },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.skripsi)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {thesis && (
        <Card title={thesis.title} description={`${thesis.student_name ?? '-'} · ${thesis.student_nim ?? '-'}`}>
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={STATUS_VARIANT[thesis.status]}>{STATUS_LABEL[thesis.status]}</Badge>
            </div>
            <DetailField label="Jenis" value={thesis.thesis_type} />
            <DetailField label="Pembimbing" value={thesis.supervisor_name} />
            <DetailField label="Diajukan" value={thesis.submitted_at} />
            <DetailField label="Selesai" value={thesis.completed_at} />
          </div>
        </Card>
      )}
    </div>
  )
}
