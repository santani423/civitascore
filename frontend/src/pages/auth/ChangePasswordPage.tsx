import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { PasswordInput } from '@/components/ui/PasswordInput'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { authService } from '@/services/authService'
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
 * Ditampilkan lewat redirect paksa dari ProtectedRoute ketika akun masih
 * memakai password default (NIM+tanggal lahir untuk mahasiswa) — lihat
 * users.must_change_password di backend. Tidak ada jalan keluar selain
 * berhasil ganti password (tidak ada tombol "lewati"/logout link di sini
 * dengan sengaja).
 */
export function ChangePasswordPage() {
  const navigate = useNavigate()
  const session = useAuthStore((state) => state.session)
  const setSession = useAuthStore((state) => state.setSession)
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
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
      setFormError((error as NormalizedApiError).message ?? 'Gagal mengubah password. Silakan coba lagi.')
    }
  }

  return (
    <div className="w-full max-w-sm">
      <div className="mb-8">
        <h1 className="text-2xl font-semibold tracking-tight text-ink-primary">Ubah Password</h1>
        <p className="mt-1.5 text-sm text-ink-secondary">
          Akun Anda di {APP_NAME} masih menggunakan password default. Buat password baru sebelum melanjutkan.
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
      </form>
    </div>
  )
}
