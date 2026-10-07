import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { PasswordInput } from '@/components/ui/PasswordInput'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { authService } from '@/services/authService'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { NormalizedApiError } from '@/services/api'
import { useAuthStore } from '@/stores/authStore'
import { ROUTES } from '@/constants/routes'
import { APP_NAME } from '@/constants/app'

const changePasswordSchema = z
  .object({
    current_password: z.string().min(1, 'Password saat ini wajib diisi.'),
    password: z.string().min(8, 'Password baru minimal 8 karakter.'),
    password_confirmation: z.string().min(1, 'Konfirmasi password wajib diisi.'),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Konfirmasi password tidak sama dengan password baru.',
    path: ['password_confirmation'],
  })
  .refine((data) => data.password !== data.current_password, {
    message: 'Password baru tidak boleh sama dengan password saat ini.',
    path: ['password'],
  })

type ChangePasswordFormValues = z.infer<typeof changePasswordSchema>

/**
 * Dua mode:
 * - Paksa: redirect dari ProtectedRoute ketika akun masih memakai password
 *   default/sementara (NIM+tanggal lahir mahasiswa, password awal dosen dari
 *   SDM) — lihat users.must_change_password. Tidak ada jalan keluar selain
 *   berhasil ganti password (tidak ada tombol "lewati" dengan sengaja).
 * - Sukarela: dibuka sendiri dari "Profil Saya" — ada tombol Batal.
 */
export function ChangePasswordPage() {
  const navigate = useNavigate()
  const session = useAuthStore((state) => state.session)
  const setSession = useAuthStore((state) => state.setSession)
  const [formError, setFormError] = useState<string | null>(null)
  const isForced = session?.user.mustChangePassword ?? false

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<ChangePasswordFormValues>({
    resolver: zodResolver(changePasswordSchema),
    defaultValues: { current_password: '', password: '', password_confirmation: '' },
  })

  const onSubmit = async (values: ChangePasswordFormValues) => {
    setFormError(null)

    try {
      await authService.changePassword(values)

      if (session) {
        setSession({ ...session, user: { ...session.user, mustChangePassword: false } })
      }

      navigate(ROUTES.dashboard, { replace: true })
    } catch (error) {
      // Field errors (e.g. "Password saat ini tidak sesuai.") land under the
      // matching input instead of a generic "Validasi data gagal." banner.
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <div className="w-full max-w-sm">
      <div className="mb-8">
        <h1 className="text-2xl font-semibold tracking-tight text-ink-primary">Ubah Password</h1>
        <p className="mt-1.5 text-sm text-ink-secondary">
          {isForced
            ? `Akun Anda di ${APP_NAME} masih menggunakan password sementara. Buat password baru sebelum melanjutkan.`
            : `Ganti password akun ${APP_NAME} Anda. Gunakan minimal 8 karakter.`}
        </p>
      </div>

      {formError && (
        <Alert variant="danger" className="mb-5" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <PasswordInput
          label="Password Saat Ini"
          autoComplete="current-password"
          error={errors.current_password?.message}
          {...register('current_password')}
        />

        <PasswordInput
          label="Password Baru"
          autoComplete="new-password"
          error={errors.password?.message}
          {...register('password')}
        />

        <PasswordInput
          label="Konfirmasi Password Baru"
          autoComplete="new-password"
          error={errors.password_confirmation?.message}
          {...register('password_confirmation')}
        />

        <Button type="submit" size="lg" isLoading={isSubmitting} className="mt-1 w-full">
          Simpan Password Baru
        </Button>

        {!isForced && (
          <Button type="button" variant="ghost" size="lg" className="w-full" onClick={() => navigate(-1)}>
            Batal
          </Button>
        )}
      </form>
    </div>
  )
}
