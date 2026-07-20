import { useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { scholarshipService } from '@/services/scholarshipService'
import type { ScholarshipApplicationStatus } from '@/types/scholarship'
import { ROUTES } from '@/constants/routes'
import { formatCurrencyIDR, formatNumber } from '@/utils/formatters'

const STATUS_LABEL: Record<ScholarshipApplicationStatus, string> = {
  submitted: 'Diajukan',
  under_review: 'Ditinjau',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}

const STATUS_VARIANT: Record<ScholarshipApplicationStatus, BadgeVariant> = {
  submitted: 'neutral',
  under_review: 'info',
  approved: 'success',
  rejected: 'danger',
}

function DetailField({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value ?? '-'}</p>
    </div>
  )
}

export function ScholarshipDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getScholarship = useCallback(() => scholarshipService.show(id ?? ''), [id])
  const { data: scholarship, isLoading, error } = useFetch(getScholarship)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Beasiswa"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Beasiswa', path: ROUTES.keuangan.beasiswa },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.keuangan.beasiswa)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {scholarship && (
        <>
          <Card title={scholarship.name} description={scholarship.academic_year}>
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <div>
                <p className="text-ink-tertiary">Status</p>
                <Badge variant={scholarship.is_active ? 'success' : 'neutral'}>
                  {scholarship.is_active ? 'Aktif' : 'Nonaktif'}
                </Badge>
              </div>
              <DetailField label="Penyedia" value={scholarship.provider} />
              <DetailField label="Kuota" value={formatNumber(scholarship.quota)} />
              <DetailField label="Jumlah Bantuan" value={formatCurrencyIDR(Number(scholarship.amount))} />
              <DetailField label="Pendaftaran Dibuka" value={scholarship.registration_start} />
              <DetailField label="Pendaftaran Ditutup" value={scholarship.registration_end} />
            </div>
          </Card>

          <Card title="Pengajuan Beasiswa" noPadding>
            {scholarship.applications && scholarship.applications.length > 0 ? (
              <ul className="divide-y divide-border">
                {scholarship.applications.map((application) => (
                  <li key={application.id} className="flex items-center justify-between gap-3 p-3">
                    <div className="min-w-0">
                      <p className="truncate font-medium text-ink-primary">{application.student_name}</p>
                      <p className="text-xs text-ink-tertiary">{application.student_nim}</p>
                    </div>
                    <Badge variant={STATUS_VARIANT[application.status]}>{STATUS_LABEL[application.status]}</Badge>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="p-4 text-sm text-ink-tertiary">Belum ada pengajuan untuk beasiswa ini.</p>
            )}
          </Card>
        </>
      )}
    </div>
  )
}
