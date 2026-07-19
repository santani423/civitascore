import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { PasswordInput } from '@/components/ui/PasswordInput'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { authService } from '@/services/authService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'

const resetPasswordSchema = z
  .object({
    password: z.string().min(8, 'Kata sandi minimal 8 karakter.'),
    password_confirmation: z.string().min(1, 'Konfirmasi kata sandi wajib diisi.'),
  })
  .refine((values) => values.password === values.password_confirmation, {
    message: 'Konfirmasi kata sandi tidak cocok.',
    path: ['password_confirmation'],
  })

type ResetPasswordFormValues = z.infer<typeof resetPasswordSchema>

export function ResetPasswordPage() {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const [formError, setFormError] = useState<string | null>(null)

  const token = searchParams.get('token') ?? ''
  const email = searchParams.get('email') ?? ''

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ResetPasswordFormValues>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: { password: '', password_confirmation: '' },
  })

  const onSubmit = async (values: ResetPasswordFormValues) => {
    setFormError(null)

    try {
      await authService.resetPassword({ token, email, ...values })
      navigate(ROUTES.login, { replace: true, state: { passwordResetSuccess: true } })
    } catch (error) {
      setFormError((error as NormalizedApiError).message ?? 'Gagal memperbarui kata sandi.')
    }
  }

  return (
    <div className="w-full max-w-sm">
      <div className="mb-8">
        <h1 className="text-2xl font-semibold tracking-tight text-ink-primary">Atur Ulang Kata Sandi</h1>
        <p className="mt-1.5 text-sm text-ink-secondary">Masukkan kata sandi baru untuk akun {email || 'Anda'}.</p>
      </div>

      {!token || !email ? (
        <Alert variant="danger">
          Tautan reset password tidak valid. Silakan minta tautan baru dari halaman lupa kata sandi.
        </Alert>
      ) : (
        <>
          {formError && (
            <Alert variant="danger" className="mb-5" onDismiss={() => setFormError(null)}>
              {formError}
            </Alert>
          )}

          <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
            <PasswordInput
              label="Kata Sandi Baru"
              autoComplete="new-password"
              error={errors.password?.message}
              {...register('password')}
            />

            <PasswordInput
              label="Konfirmasi Kata Sandi Baru"
              autoComplete="new-password"
              error={errors.password_confirmation?.message}
              {...register('password_confirmation')}
            />

            <Button type="submit" size="lg" isLoading={isSubmitting} className="mt-1 w-full">
              Simpan Kata Sandi Baru
            </Button>
          </form>
        </>
      )}

      <Link
        to={ROUTES.login}
        className="mt-6 flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
      >
        <ArrowLeft className="size-4" /> Kembali ke halaman masuk
      </Link>
    </div>
  )
}
