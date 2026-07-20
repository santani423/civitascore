import { useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { alumniService } from '@/services/alumniService'
import type { AlumniEmploymentStatus } from '@/types/alumni'
import { ROUTES } from '@/constants/routes'

const EMPLOYMENT_STATUS_LABEL: Record<AlumniEmploymentStatus, string> = {
  bekerja: 'Bekerja',
  wirausaha: 'Wirausaha',
  melanjutkan_studi: 'Melanjutkan Studi',
  mencari_kerja: 'Mencari Kerja',
  belum_bekerja: 'Belum Bekerja',
}

const EMPLOYMENT_STATUS_VARIANT: Record<AlumniEmploymentStatus, BadgeVariant> = {
  bekerja: 'success',
  wirausaha: 'success',
  melanjutkan_studi: 'info',
  mencari_kerja: 'warning',
  belum_bekerja: 'neutral',
}

function DetailField({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value ?? '-'}</p>
    </div>
  )
}

export function AlumniDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getAlumni = useCallback(() => alumniService.show(id ?? ''), [id])
  const { data: alumni, isLoading, error } = useFetch(getAlumni)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Alumni"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Alumni', path: ROUTES.alumni },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.alumni)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {alumni && (
        <Card
          title={alumni.student_name ?? alumni.student_id}
          description={`${alumni.student_nim ?? '-'} · ${alumni.study_program_name ?? '-'}`}
        >
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <p className="text-ink-tertiary">Status Pekerjaan</p>
              <Badge variant={EMPLOYMENT_STATUS_VARIANT[alumni.employment_status]}>
                {EMPLOYMENT_STATUS_LABEL[alumni.employment_status]}
              </Badge>
            </div>
            <DetailField label="Tahun Lulus" value={alumni.graduation_year} />
            <div>
              <p className="text-ink-tertiary">Terverifikasi</p>
              <Badge variant={alumni.is_verified ? 'success' : 'neutral'}>{alumni.is_verified ? 'Ya' : 'Belum'}</Badge>
            </div>
            <DetailField label="Perusahaan" value={alumni.company_name} />
            <DetailField label="Jabatan" value={alumni.job_title} />
            <DetailField label="Masa Tunggu Kerja (bulan)" value={alumni.waiting_period_months} />
          </div>
        </Card>
      )}
    </div>
  )
}
