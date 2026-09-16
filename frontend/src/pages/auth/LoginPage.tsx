import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Link, useLocation, useNavigate, type Location } from 'react-router-dom'
import { IdCard, Mail } from 'lucide-react'
import { Input } from '@/components/ui/Input'
import { PasswordInput } from '@/components/ui/PasswordInput'
import { Checkbox } from '@/components/ui/Checkbox'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { authService } from '@/services/authService'
import type { NormalizedApiError } from '@/services/api'
import type { LoginCredentials } from '@/types/auth'
import { useAuthStore } from '@/stores/authStore'
import { APP_NAME, APP_DESCRIPTION, APP_VERSION } from '@/constants/app'
import { ROUTES } from '@/constants/routes'
import { cn } from '@/utils/cn'

type LoginMode = 'mahasiswa' | 'lainnya'

/**
 * `mode` menentukan apakah `identifier` divalidasi (dan nantinya dikirim ke
 * backend) sebagai NIM atau email — lihat AuthenticateUserAction di backend
 * yang punya dua jalur login terpisah. Satu form, satu field identifier,
 * hanya validator + label yang berganti berdasarkan tab yang aktif.
 */
const loginSchema = z.discriminatedUnion('mode', [
  z.object({
    mode: z.literal('mahasiswa'),
    identifier: z
      .string()
      .min(1, 'NIM wajib diisi.')
      .regex(/^\d+$/, 'NIM hanya boleh berisi angka.'),
    password: z.string().min(1, 'Kata sandi wajib diisi.'),
    remember: z.boolean(),
  }),
  z.object({
    mode: z.literal('lainnya'),
    identifier: z.string().min(1, 'Email wajib diisi.').email('Format email tidak valid.'),
    password: z.string().min(1, 'Kata sandi wajib diisi.').min(6, 'Kata sandi minimal 6 karakter.'),
    remember: z.boolean(),
  }),
])

type LoginFormValues = z.infer<typeof loginSchema>

interface LocationState {
  from?: Location
}

const TABS: Array<{ mode: LoginMode; label: string }> = [
  { mode: 'mahasiswa', label: 'Mahasiswa' },
  { mode: 'lainnya', label: 'Dosen / Staf / Admin' },
]

export function LoginPage() {
  const navigate = useNavigate()
  const location = useLocation()
  const setSession = useAuthStore((state) => state.setSession)
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm<LoginFormValues>({
    resolver: zodResolver(loginSchema),
    defaultValues: { mode: 'mahasiswa', identifier: '', password: '', remember: true },
  })

  const mode = watch('mode')

  const switchMode = (nextMode: LoginMode) => {
    if (nextMode === mode) return
    setValue('mode', nextMode)
    setValue('identifier', '')
    setFormError(null)
  }

  const onSubmit = async (values: LoginFormValues) => {
    setFormError(null)

    try {
      const credentials: LoginCredentials =
        values.mode === 'mahasiswa'
          ? { nim: values.identifier, password: values.password, remember: values.remember }
          : { email: values.identifier, password: values.password, remember: values.remember }

      const session = await authService.login(credentials)
      setSession(session)

      const redirectTo = (location.state as LocationState | null)?.from?.pathname ?? ROUTES.dashboard
      navigate(redirectTo, { replace: true })
    } catch (error) {
      const apiError = error as NormalizedApiError
      // `message` di top-level cuma "Validasi data gagal." (generik) — alasan
      // sebenarnya (mis. "NIM atau password tidak sesuai.") ada di `errors`,
      // lihat AuthenticateUserAction di backend.
      const specificMessage = Object.values(apiError.errors ?? {})[0]?.[0]
      setFormError(specificMessage ?? apiError.message ?? 'Gagal masuk. Silakan coba lagi.')
    }
  }

  return (
    <div className="w-full max-w-sm">
      <div className="mb-8">
        <h1 className="text-2xl font-semibold tracking-tight text-ink-primary">Masuk ke {APP_NAME}</h1>
        <p className="mt-1.5 text-sm text-ink-secondary">{APP_DESCRIPTION}</p>
      </div>

      <div className="mb-5 grid grid-cols-2 gap-1 rounded-lg bg-surface-muted p-1">
        {TABS.map((tab) => (
          <button
            key={tab.mode}
            type="button"
            onClick={() => switchMode(tab.mode)}
            className={cn(
              'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
              mode === tab.mode ? 'bg-surface text-ink-primary shadow-sm' : 'text-ink-secondary hover:text-ink-primary',
            )}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {formError && (
        <Alert variant="danger" className="mb-5" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        {mode === 'mahasiswa' ? (
          <Input
            label="NIM"
            type="text"
            inputMode="numeric"
            autoComplete="username"
            placeholder="Masukkan NIM"
            leftIcon={<IdCard className="size-4" />}
            error={errors.identifier?.message}
            {...register('identifier')}
          />
        ) : (
          <Input
            label="Email"
            type="text"
            autoComplete="username"
            placeholder="Masukkan email"
            leftIcon={<Mail className="size-4" />}
            error={errors.identifier?.message}
            {...register('identifier')}
          />
        )}

        <PasswordInput
          label="Kata Sandi"
          autoComplete="current-password"
          placeholder="Masukkan password"
          error={errors.password?.message}
          {...register('password')}
        />

        <div className="flex items-center justify-between">
          <Checkbox label="Ingat saya" {...register('remember')} />
          {mode === 'lainnya' && (
            <Link to={ROUTES.forgotPassword} className="text-sm font-medium text-primary hover:underline">
              Lupa kata sandi?
            </Link>
          )}
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
