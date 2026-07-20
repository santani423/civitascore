import { useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { internshipService } from '@/services/internshipService'
import type { InternshipProgramType, InternshipStatus } from '@/types/internship'
import { ROUTES } from '@/constants/routes'

const PROGRAM_TYPE_LABEL: Record<InternshipProgramType, string> = {
  magang: 'Magang',
  kkn: 'KKN',
  mbkm: 'MBKM',
}

const STATUS_LABEL: Record<InternshipStatus, string> = {
  terdaftar: 'Terdaftar',
  berlangsung: 'Berlangsung',
  selesai: 'Selesai',
  dibatalkan: 'Dibatalkan',
}

const STATUS_VARIANT: Record<InternshipStatus, BadgeVariant> = {
  terdaftar: 'neutral',
  berlangsung: 'info',
  selesai: 'success',
  dibatalkan: 'danger',
}

function DetailField({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value ?? '-'}</p>
    </div>
  )
}

export function InternshipDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getInternship = useCallback(() => internshipService.show(id ?? ''), [id])
  const { data: internship, isLoading, error } = useFetch(getInternship)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Magang dan MBKM"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Magang dan MBKM', path: ROUTES.magangMbkm },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.magangMbkm)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {internship && (
        <Card
          title={internship.institution_name}
          description={`${internship.student_name ?? '-'} · ${internship.student_nim ?? '-'}`}
        >
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={STATUS_VARIANT[internship.status]}>{STATUS_LABEL[internship.status]}</Badge>
            </div>
            <div>
              <p className="text-ink-tertiary">Jenis Program</p>
              <Badge variant="info">{PROGRAM_TYPE_LABEL[internship.program_type]}</Badge>
            </div>
            <DetailField label="Posisi" value={internship.position} />
            <DetailField label="Pembimbing" value={internship.supervisor_name} />
            <DetailField label="Mulai" value={internship.start_date} />
            <DetailField label="Selesai" value={internship.end_date} />
            <DetailField label="SKS Dikonversi" value={internship.sks_converted} />
          </div>
        </Card>
      )}
    </div>
  )
}
