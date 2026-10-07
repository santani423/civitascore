import { useCallback, useState, type ReactNode } from 'react'
import { useNavigate } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { KeyRound } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { lecturerProfileService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import type { Lecturer } from '@/types/academic'
import { applyServerErrors } from '@/utils/applyServerErrors'
import { formatRelativeTime } from '@/utils/formatters'
import { ROUTES } from '@/constants/routes'
import { EDUCATION_LEVEL_LABEL, EMPLOYMENT_STATUS_LABEL, FUNCTIONAL_RANK_LABEL } from '@/constants/lecturer'

function Field({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <div className="font-medium text-ink-primary">{children}</div>
    </div>
  )
}

/**
 * "Profil Saya" untuk dosen yang sedang login — data selalu milik sendiri
 * (backend me-resolve lewat lecturers.user_id, bukan id dari klien). Data
 * induk (NIDN, nama, email, kepegawaian) dikelola Bagian SDM; dosen hanya
 * mengubah kontak dan password-nya sendiri.
 */
export function LecturerProfilePage() {
  const navigate = useNavigate()
  const getProfile = useCallback(() => lecturerProfileService.show(), [])
  const { data: profile, isLoading, error, setData: setProfile } = useFetch(getProfile)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Profil Saya"
        description="Data diri dan akun login Anda sebagai dosen."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Profil Saya' }]}
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="warning">{error}</Alert>}

      {profile && (
        <>
          <Card title={profile.name} description={`NIDN ${profile.nidn}`}>
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <Field label="NIP">{profile.nip ?? '-'}</Field>
              <Field label="Fakultas">{profile.faculty_name ?? '-'}</Field>
              <Field label="Email">{profile.email ?? '-'}</Field>
              <Field label="Status Kepegawaian">
                {profile.employment_status ? EMPLOYMENT_STATUS_LABEL[profile.employment_status] : '-'}
              </Field>
              <Field label="Jabatan Fungsional">
                {profile.functional_rank ? FUNCTIONAL_RANK_LABEL[profile.functional_rank] : '-'}
              </Field>
              <Field label="Pendidikan Terakhir">
                {profile.highest_education ? EDUCATION_LEVEL_LABEL[profile.highest_education] : '-'}
              </Field>
              <Field label="Mulai Bertugas">{profile.hired_at ?? '-'}</Field>
              <Field label="Status">
                <Badge variant={profile.is_active ? 'success' : 'neutral'}>{profile.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
              </Field>
            </div>
            <p className="mt-4 text-xs text-ink-tertiary">
              Perubahan NIDN, nama, email, fakultas, atau data kepegawaian diajukan ke Bagian SDM.
            </p>
          </Card>

          <ContactCard profile={profile} onSaved={setProfile} />

          <Card
            title="Akun & Keamanan"
            actions={
              <Button
                variant="outline"
                size="sm"
                leftIcon={<KeyRound className="size-4" />}
                onClick={() => navigate(ROUTES.changePassword)}
              >
                Ubah Password
              </Button>
            }
          >
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
              <Field label="Email Login">{profile.account?.email ?? profile.email ?? '-'}</Field>
              <Field label="Password">
                {profile.account?.password_changed_at
                  ? `Terakhir diganti ${formatRelativeTime(profile.account.password_changed_at)}`
                  : 'Belum pernah diganti'}
              </Field>
            </div>
          </Card>
        </>
      )}
    </div>
  )
}

const contactSchema = z.object({
  phone: z.string().trim().max(30, 'Nomor telepon maksimal 30 karakter.'),
})

type ContactFormValues = z.infer<typeof contactSchema>

function ContactCard({ profile, onSaved }: { profile: Lecturer; onSaved: (profile: Lecturer) => void }) {
  const [formError, setFormError] = useState<string | null>(null)
  const [saved, setSaved] = useState(false)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting, isDirty },
    reset,
  } = useForm<ContactFormValues>({
    resolver: zodResolver(contactSchema),
    defaultValues: { phone: profile.phone ?? '' },
  })

  const onSubmit = async (values: ContactFormValues) => {
    setFormError(null)
    setSaved(false)

    try {
      const updated = await lecturerProfileService.update({ phone: values.phone === '' ? null : values.phone })
      onSaved(updated)
      reset({ phone: updated.phone ?? '' })
      setSaved(true)
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Card title="Kontak">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}
      {saved && (
        <Alert variant="success" className="mb-4" onDismiss={() => setSaved(false)}>
          Profil berhasil diperbarui.
        </Alert>
      )}
      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-wrap items-end gap-3" noValidate>
        <div className="w-full sm:w-72">
          <Input label="No. Telepon" error={errors.phone?.message} {...register('phone')} />
        </div>
        <Button type="submit" isLoading={isSubmitting} disabled={!isDirty}>
          Simpan
        </Button>
      </form>
    </Card>
  )
}
