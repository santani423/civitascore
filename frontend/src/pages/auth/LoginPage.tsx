import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link, useLocation, useNavigate, type Location } from 'react-router-dom'
import { Mail } from 'lucide-react'
import { Input } from '@/components/ui/Input'
import { PasswordInput } from '@/components/ui/PasswordInput'
import { Checkbox } from '@/components/ui/Checkbox'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { authService } from '@/services/authService'
import type { NormalizedApiError } from '@/services/api'
import { useAuthStore } from '@/stores/authStore'
import { APP_NAME, APP_DESCRIPTION, APP_VERSION } from '@/constants/app'
import { ROUTES } from '@/constants/routes'

const loginSchema = z.object({
  email: z.string().min(1, 'Email wajib diisi.').email('Format email tidak valid.'),
  password: z.string().min(1, 'Kata sandi wajib diisi.').min(6, 'Kata sandi minimal 6 karakter.'),
  remember: z.boolean(),
})

type LoginFormValues = z.infer<typeof loginSchema>

interface LocationState {
  from?: Location
}

export function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const setSession = useAuthStore((state) => state.setSession)
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { email: '', password: '', remember: true },
  })

  const onSubmit = async (values: LoginFormValues) => {
    setFormError(null)

    try {
      const session = await authService.login(values)
      setSession(session)

      const redirectTo = (location.state as LocationState | null)?.from?.pathname ?? ROUTES.dashboard
      navigate(redirectTo, { replace: true })
    } catch (error) {
      setFormError((error as NormalizedApiError).message ?? 'Gagal masuk. Silakan coba lagi.')
    }
  }

  return (
    <div className="w-full max-w-sm">
      <div className="mb-8">
        <h1 className="text-2xl font-semibold tracking-tight text-ink-primary">Masuk ke {APP_NAME}</h1>
        <p className="mt-1.5 text-sm text-ink-secondary">{APP_DESCRIPTION}</p>
      </div>

      {formError && (
        <Alert variant="danger" className="mb-5" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <Input
          label="Email atau Username"
          type="text"
          autoComplete="username"
          leftIcon={<Mail className="size-4" />}
          error={errors.email?.message}
          {...register('email')}
        />

        <PasswordInput
          label="Kata Sandi"
          autoComplete="current-password"
          error={errors.password?.message}
          {...register('password')}
        />

        <div className="flex items-center justify-between">
          <Checkbox label="Ingat saya" {...register('remember')} />
          <Link to={ROUTES.forgotPassword} className="text-sm font-medium text-primary hover:underline">
            Lupa kata sandi?
          </Link>
        </div>

        <Button type="submit" size="lg" isLoading={isSubmitting} className="mt-1 w-full">
          Masuk
        </Button>
      </form>

      <div className="mt-8 flex items-center justify-between border-t border-border pt-4 text-xs text-ink-tertiary">
        <span>Versi {APP_VERSION}</span>
        <span>© {new Date().getFullYear()} {APP_NAME}</span>
      </div>
    </div>
  )
}
