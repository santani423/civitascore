import { useCallback, useState, type ReactNode } from 'react'
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
import type { Lecturer, LecturerAccountCredentials } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { EDUCATION_LEVEL_LABEL, EMPLOYMENT_STATUS_LABEL, FUNCTIONAL_RANK_LABEL } from '@/constants/lecturer'
import { formatRelativeTime } from '@/utils/formatters'
import { LecturerFormModal } from './LecturersPage'
import { LecturerCredentialsModal } from './LecturerCredentialsModal'

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <div className="font-medium text-ink-primary">{children}</div>
    </div>
  )
}

export function LecturerDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const canUpdate = usePermission('lecturers.update')
  const canDelete = usePermission('lecturers.delete')

  const getLecturer = useCallback(() => lecturerService.show(id ?? ''), [id])
  const { data: lecturer, isLoading, error, setData: setLecturer } = useFetch(getLecturer)

  const [showEditForm, setShowEditForm] = useState(false)
  const [actionError, setActionError] = useState<string | null>(null)
  const [actionMessage, setActionMessage] = useState<string | null>(null)
  const [pendingAction, setPendingAction] = useState<string | null>(null)
  const [credentials, setCredentials] = useState<LecturerAccountCredentials | null>(null)

  const runAction = async (key: string, action: () => Promise<void>) => {
    setActionError(null)
    setActionMessage(null)
    setPendingAction(key)

    try {
      await action()
    } catch (actionErr) {
      setActionError((actionErr as NormalizedApiError).message)
    } finally {
      setPendingAction(null)
    }
  }

  const handleDelete = () => {
    if (!lecturer) return
    const accountNote = lecturer.has_account
      ? '\n\nRole Dosen pada akun loginnya akan dicabut dan akun dinonaktifkan.'
      : ''
    if (!window.confirm(`Hapus dosen "${lecturer.name}"?${accountNote}`)) return

    void runAction('delete', async () => {
      await lecturerService.remove(lecturer.id)
      navigate(ROUTES.dosen)
    })
  }

  const handleCreateAccount = (target: Lecturer) =>
    runAction('create-account', async () => {
      const result = await lecturerService.createAccount(target.id)
      setLecturer(result.lecturer)
      setCredentials(result.credentials)
    })

  const handleResetPassword = (target: Lecturer) => {
    if (!window.confirm(`Reset password akun "${target.name}"? Dosen akan keluar dari semua perangkat.`)) return

    void runAction('reset-password', async () => {
      const result = await lecturerService.resetAccountPassword(target.id)
      setLecturer(result.lecturer)
      setCredentials(result.credentials)
    })
  }

  const handleToggleAccount = (target: Lecturer, activate: boolean) => {
    if (!activate && !window.confirm(`Nonaktifkan akun login "${target.name}"? Dosen tidak akan bisa login.`)) return

    void runAction('toggle-account', async () => {
      setLecturer(await lecturerService.setAccountActive(target.id, activate))
      setActionMessage(activate ? 'Akun login diaktifkan.' : 'Akun login dinonaktifkan.')
    })
  }

  const account = lecturer?.account ?? null

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
              <Button variant="danger" isLoading={pendingAction === 'delete'} onClick={handleDelete}>
                Hapus Dosen
              </Button>
            )}
          </div>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}
      {actionError && (
        <Alert variant="danger" onDismiss={() => setActionError(null)}>
          {actionError}
        </Alert>
      )}
      {actionMessage && (
        <Alert variant="success" onDismiss={() => setActionMessage(null)}>
          {actionMessage}
        </Alert>
      )}

      {lecturer && (
        <>
          <Card title={lecturer.name} description={`NIDN ${lecturer.nidn}`}>
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <Field label="Status">
                <Badge variant={lecturer.is_active ? 'success' : 'neutral'}>
                  {lecturer.is_active ? 'Aktif' : 'Nonaktif'}
                </Badge>
              </Field>
              <Field label="NIP">{lecturer.nip ?? '-'}</Field>
              <Field label="Fakultas">{lecturer.faculty_name ?? '-'}</Field>
              <Field label="Email">{lecturer.email ?? '-'}</Field>
              <Field label="No. Telepon">{lecturer.phone ?? '-'}</Field>
            </div>
          </Card>

          <Card title="Kepegawaian & Akademik">
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
              <Field label="Status Kepegawaian">
                {lecturer.employment_status ? EMPLOYMENT_STATUS_LABEL[lecturer.employment_status] : '-'}
              </Field>
              <Field label="Jabatan Fungsional">
                {lecturer.functional_rank ? FUNCTIONAL_RANK_LABEL[lecturer.functional_rank] : '-'}
              </Field>
              <Field label="Pendidikan Terakhir">
                {lecturer.highest_education ? EDUCATION_LEVEL_LABEL[lecturer.highest_education] : '-'}
              </Field>
              <Field label="Mulai Bertugas">{lecturer.hired_at ?? '-'}</Field>
            </div>
          </Card>

          <Card
            title="Akun Login"
            description="Akun yang dipakai dosen untuk masuk ke sistem dengan role Dosen."
            actions={
              canUpdate &&
              (account ? (
                account.manageable && (
                  <div className="flex flex-wrap gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      isLoading={pendingAction === 'reset-password'}
                      disabled={pendingAction !== null}
                      onClick={() => handleResetPassword(lecturer)}
                    >
                      Reset Password
                    </Button>
                    <Button
                      variant={account.is_active ? 'danger' : 'primary'}
                      size="sm"
                      isLoading={pendingAction === 'toggle-account'}
                      disabled={pendingAction !== null}
                      onClick={() => handleToggleAccount(lecturer, !account.is_active)}
                    >
                      {account.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun'}
                    </Button>
                  </div>
                )
              ) : (
                <Button
                  size="sm"
                  isLoading={pendingAction === 'create-account'}
                  disabled={pendingAction !== null}
                  onClick={() => handleCreateAccount(lecturer)}
                >
                  Buat Akun Login
                </Button>
              ))
            }
          >
            {account ? (
              <div className="flex flex-col gap-4">
                <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                  <Field label="Email Login">{account.email}</Field>
                  <Field label="Status Akun">
                    <Badge variant={account.is_active ? 'success' : 'danger'}>
                      {account.is_active ? 'Aktif' : 'Nonaktif'}
                    </Badge>
                  </Field>
                  <Field label="Password">
                    {account.must_change_password ? (
                      <Badge variant="warning">Password sementara — wajib diganti</Badge>
                    ) : account.password_changed_at ? (
                      `Diganti ${formatRelativeTime(account.password_changed_at)}`
                    ) : (
                      'Sudah diatur'
                    )}
                  </Field>
                </div>
                {account.manageable === false && (
                  <Alert variant="info">
                    Akun ini juga memiliki role lain atau terdaftar di universitas lain, sehingga reset password dan
                    status akunnya hanya dapat dikelola oleh Administrator Universitas.
                  </Alert>
                )}
              </div>
            ) : (
              <p className="text-sm text-ink-secondary">
                Dosen ini belum memiliki akun login.
                {!lecturer.email && ' Isi email dosen terlebih dahulu sebelum membuat akun.'}
              </p>
            )}
          </Card>
        </>
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

      {credentials && <LecturerCredentialsModal credentials={credentials} onClose={() => setCredentials(null)} />}
    </div>
  )
}
