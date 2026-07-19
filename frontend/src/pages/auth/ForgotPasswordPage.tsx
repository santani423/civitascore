import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link } from 'react-router-dom'
import { Mail, ArrowLeft } from 'lucide-react'
import { Input } from '@/components/ui/Input'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { authService } from '@/services/authService'
import type { NormalizedApiError } from '@/services/api'
import { APP_NAME } from '@/constants/app'
import { ROUTES } from '@/constants/routes'

const forgotPasswordSchema = z.object({
  email: z.string().min(1, 'Email wajib diisi.').email('Format email tidak valid.'),
})

type ForgotPasswordFormValues = z.infer<typeof forgotPasswordSchema>

export function ForgotPasswordPage() {
  const [formError, setFormError] = useState<string | null>(null)
  const [sent, setSent] = useState(false)

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ForgotPasswordFormValues>({
    resolver: zodResolver(forgotPasswordSchema),
    defaultValues: { email: '' },
  })

  const onSubmit = async (values: ForgotPasswordFormValues) => {
    setFormError(null)

    try {
      await authService.forgotPassword(values.email)
      setSent(true)
    } catch (error) {
      setFormError((error as NormalizedApiError).message ?? 'Gagal mengirim tautan reset password.')
    }
  }

  return (
    <div className="w-full max-w-sm">
      <div className="mb-8">
        <h1 className="text-2xl font-semibold tracking-tight text-ink-primary">Lupa Kata Sandi</h1>
        <p className="mt-1.5 text-sm text-ink-secondary">
          Masukkan email akun {APP_NAME} Anda, kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
        </p>
      </div>

      {formError && (
        <Alert variant="danger" className="mb-5" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      {sent ? (
        <Alert variant="success">
          Jika email terdaftar, tautan reset password sudah dikirim. Silakan periksa kotak masuk Anda.
        </Alert>
      ) : (
        <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
          <Input
            label="Email"
            type="text"
            autoComplete="username"
            leftIcon={<Mail className="size-4" />}
            error={errors.email?.message}
            {...register('email')}
          />

          <Button type="submit" size="lg" isLoading={isSubmitting} className="mt-1 w-full">
            Kirim Tautan Reset
          </Button>
        </form>
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
