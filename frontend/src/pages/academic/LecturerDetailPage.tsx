import { useCallback, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { lecturerService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'
import { LecturerFormModal } from './LecturersPage'

export function LecturerDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const canUpdate = usePermission('lecturers.update')
  const canDelete = usePermission('lecturers.delete')

  const getLecturer = useCallback(() => lecturerService.show(id ?? ''), [id])
  const { data: lecturer, isLoading, error, setData: setLecturer } = useFetch(getLecturer)

  const [showEditForm, setShowEditForm] = useState(false)
  const [deleteError, setDeleteError] = useState<string | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  const handleDelete = async () => {
    if (!lecturer || !window.confirm(`Hapus dosen "${lecturer.name}"?`)) return

    setDeleteError(null)
    setIsDeleting(true)

    try {
      await lecturerService.remove(lecturer.id)
      navigate(ROUTES.dosen)
    } catch (deleteErr) {
      setDeleteError((deleteErr as NormalizedApiError).message)
      setIsDeleting(false)
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Dosen"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Dosen', path: ROUTES.dosen },
          { label: 'Detail' },
        ]}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button variant="outline" onClick={() => navigate(ROUTES.dosen)}>
              Kembali
            </Button>
            {canUpdate && lecturer && <Button onClick={() => setShowEditForm(true)}>Ubah Dosen</Button>}
            {canDelete && lecturer && (
              <Button variant="danger" isLoading={isDeleting} onClick={handleDelete}>
                Hapus Dosen
              </Button>
            )}
          </div>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}
      {deleteError && (
        <Alert variant="danger" onDismiss={() => setDeleteError(null)}>
          {deleteError}
        </Alert>
      )}

      {lecturer && (
        <Card title={lecturer.name} description={`NIDN ${lecturer.nidn}`}>
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={lecturer.is_active ? 'success' : 'neutral'}>{lecturer.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
            </div>
            <div>
              <p className="text-ink-tertiary">Fakultas</p>
              <p className="font-medium text-ink-primary">{lecturer.faculty_name ?? '-'}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Email</p>
              <p className="font-medium text-ink-primary">{lecturer.email ?? '-'}</p>
            </div>
          </div>
        </Card>
      )}

      {showEditForm && lecturer && (
        <LecturerFormModal
          lecturer={lecturer}
          onClose={() => setShowEditForm(false)}
          onSaved={(updated) => {
            setShowEditForm(false)
            setLecturer(updated)
          }}
        />
      )}
    </div>
  )
}
