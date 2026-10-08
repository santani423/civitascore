import { useCallback, useState, type ReactNode } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Modal } from '@/components/ui/Modal'
import { Input } from '@/components/ui/Input'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { hrEmployeeService } from '@/services/hrService'
import type { NormalizedApiError } from '@/services/api'
import type { HrEmployeeDetail } from '@/types/hr'
import { ROUTES } from '@/constants/routes'
import { formatDate } from '@/utils/portalFormat'
import { EducationStaffFormModal } from './EducationStaffFormModal'

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <div className="font-medium text-ink-primary">{children}</div>
    </div>
  )
}

export function EducationStaffDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  // Backend mensyaratkan izin pegawai umum DAN izin tendik (HrEmployeePolicy).
  const canUpdateEmployee = usePermission('hr_employees.update')
  const canUpdateStaff = usePermission('hr_staff.update')
  const canDeleteEmployee = usePermission('hr_employees.delete')
  const canDeleteStaff = usePermission('hr_staff.delete')
  const canUpdate = canUpdateEmployee && canUpdateStaff
  const canDelete = canDeleteEmployee && canDeleteStaff

  const getEmployee = useCallback(() => hrEmployeeService.show(id ?? ''), [id])
  const { data: employee, isLoading, error, setData: setEmployee } = useFetch(getEmployee)

  const [showEditForm, setShowEditForm] = useState(false)
  const [showDeactivate, setShowDeactivate] = useState(false)
  const [actionError, setActionError] = useState<string | null>(null)
  const [actionMessage, setActionMessage] = useState<string | null>(null)
  const [pendingAction, setPendingAction] = useState<string | null>(null)

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

  const handleActivate = (target: HrEmployeeDetail) =>
    runAction('activate', async () => {
      setEmployee(await hrEmployeeService.activate(target.id))
      setActionMessage('Tenaga kependidikan diaktifkan kembali.')
    })

  const handleDelete = (target: HrEmployeeDetail) => {
    if (
      !window.confirm(
        `Hapus data "${target.name}"?\n\nGunakan hapus hanya untuk data salah input. Untuk pegawai yang resign/pensiun, pilih Nonaktifkan agar riwayatnya tetap tercatat.`,
      )
    ) {
      return
    }

    void runAction('delete', async () => {
      await hrEmployeeService.remove(target.id)
      navigate(ROUTES.sdm.tendik)
    })
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Tenaga Kependidikan"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Data Tenaga Kependidikan', path: ROUTES.sdm.tendik },
          { label: 'Detail' },
        ]}
        actions={
          <div className="flex flex-wrap gap-2">
            <Button variant="outline" onClick={() => navigate(ROUTES.sdm.tendik)}>
              Kembali
            </Button>
            {canUpdate && employee && (
              <>
                <Button onClick={() => setShowEditForm(true)}>Ubah Data</Button>
                {employee.is_active ? (
                  <Button variant="outline" disabled={pendingAction !== null} onClick={() => setShowDeactivate(true)}>
                    Nonaktifkan
                  </Button>
                ) : (
                  <Button
                    variant="outline"
                    isLoading={pendingAction === 'activate'}
                    disabled={pendingAction !== null}
                    onClick={() => void handleActivate(employee)}
                  >
                    Aktifkan Kembali
                  </Button>
                )}
              </>
            )}
            {canDelete && employee && (
              <Button variant="danger" isLoading={pendingAction === 'delete'} onClick={() => handleDelete(employee)}>
                Hapus
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

      {employee && (
        <>
          {employee.employee_type !== 'staff' && (
            <Alert variant="info">Pegawai ini terdaftar sebagai {employee.employee_type_label}, bukan tenaga kependidikan.</Alert>
          )}
          {!employee.is_active && (
            <Alert variant="warning">
              Nonaktif sejak {formatDate(employee.inactive_at)}
              {employee.inactive_reason ? ` — ${employee.inactive_reason}` : ''}.
            </Alert>
          )}

          <Card title={employee.full_name} description={employee.nip ? `NIP ${employee.nip}` : 'NIP belum diisi'}>
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <Field label="Status">
                <Badge variant={employee.is_active ? 'success' : 'neutral'}>{employee.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
              </Field>
              <Field label="NIK">{employee.nik ?? '-'}</Field>
              <Field label="Jenis Kelamin">{employee.gender_label ?? '-'}</Field>
              <Field label="Tempat, Tanggal Lahir">
                {employee.birth_place || employee.birth_date
                  ? `${employee.birth_place ?? '-'}, ${formatDate(employee.birth_date, 'long')}`
                  : '-'}
              </Field>
              <Field label="Email">{employee.email ?? '-'}</Field>
              <Field label="No. Telepon">{employee.phone ?? '-'}</Field>
              <Field label="Alamat">{employee.address ?? '-'}</Field>
              <Field label="Akun Login">
                {employee.user ? (
                  employee.user.email
                ) : (
                  <span className="text-ink-tertiary">Belum tertaut</span>
                )}
              </Field>
            </div>
          </Card>

          <Card title="Kepegawaian">
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <Field label="Status Kepegawaian">{employee.employment_status_label}</Field>
              <Field label="Unit Kerja">{employee.work_unit_name ?? employee.unit_kerja ?? '-'}</Field>
              <Field label="Jabatan">{employee.position ?? '-'}</Field>
              <Field label="Pangkat / Golongan">{employee.rank ? `${employee.rank.grade} — ${employee.rank.name}` : '-'}</Field>
              <Field label="Fakultas Penempatan">{employee.faculty_name ?? '-'}</Field>
              <Field label="Pendidikan Terakhir">{employee.highest_education_label ?? '-'}</Field>
              <Field label="Tanggal Mulai Bekerja">{formatDate(employee.joined_at)}</Field>
            </div>
          </Card>

          <Card title="Profil Tenaga Kependidikan" description="Kategori, penugasan, dan ringkasan kompetensi.">
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
              <Field label="Kategori">
                {employee.staff_category_label ? (
                  <Badge variant="info">{employee.staff_category_label}</Badge>
                ) : (
                  <Badge variant="warning">Belum diisi</Badge>
                )}
              </Field>
              <Field label="Laboratorium / Fasilitas yang Menjadi Tanggung Jawab">{employee.assigned_facility ?? '-'}</Field>
              <div className="sm:col-span-2">
                <Field label="Ringkasan Kompetensi">
                  <p className="whitespace-pre-line">{employee.competency_summary ?? '-'}</p>
                </Field>
              </div>
            </div>
          </Card>
        </>
      )}

      {showEditForm && employee && (
        <EducationStaffFormModal
          employee={employee}
          onClose={() => setShowEditForm(false)}
          onSaved={(updated) => {
            setShowEditForm(false)
            setEmployee(updated)
            setActionMessage('Data tenaga kependidikan berhasil diperbarui.')
          }}
        />
      )}

      {showDeactivate && employee && (
        <DeactivateModal
          employee={employee}
          onClose={() => setShowDeactivate(false)}
          onDone={(updated) => {
            setShowDeactivate(false)
            setEmployee(updated)
            setActionMessage('Tenaga kependidikan dinonaktifkan.')
          }}
        />
      )}
    </div>
  )
}

/** Nonaktifkan (resign/pensiun/meninggal/diberhentikan) — alasan wajib, data & riwayat tetap tersimpan. */
function DeactivateModal({
  employee,
  onClose,
  onDone,
}: {
  employee: HrEmployeeDetail
  onClose: () => void
  onDone: (employee: HrEmployeeDetail) => void
}) {
  const [reason, setReason] = useState('')
  const [inactiveAt, setInactiveAt] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [formError, setFormError] = useState<string | null>(null)

  const submit = async () => {
    if (reason.trim() === '') {
      setFormError('Alasan wajib diisi.')
      return
    }

    setSubmitting(true)
    setFormError(null)
    try {
      onDone(await hrEmployeeService.deactivate(employee.id, { reason: reason.trim(), inactive_at: inactiveAt || null }))
    } catch (error) {
      setFormError((error as NormalizedApiError).message)
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <Modal open onClose={onClose} title={`Nonaktifkan ${employee.name}`} className="max-w-lg">
      <div className="flex flex-col gap-4">
        {formError && (
          <Alert variant="danger" onDismiss={() => setFormError(null)}>
            {formError}
          </Alert>
        )}
        <Input
          label="Alasan"
          placeholder="mis. Resign, Pensiun, Kontrak berakhir"
          value={reason}
          onChange={(event) => setReason(event.target.value)}
        />
        <Input
          type="date"
          label="Tanggal Efektif"
          hint="Kosongkan untuk hari ini."
          value={inactiveAt}
          onChange={(event) => setInactiveAt(event.target.value)}
        />
        <div className="flex justify-end gap-2">
          <Button variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button variant="danger" isLoading={submitting} onClick={() => void submit()}>
            Nonaktifkan
          </Button>
        </div>
      </div>
    </Modal>
  )
}
