import { Outlet } from 'react-router-dom'
import { GraduationCap, ShieldCheck, BarChart3 } from 'lucide-react'
import { APP_NAME, APP_DESCRIPTION } from '@/constants/app'
import { ThemeToggle } from '@/components/layout/ThemeToggle'
import logoAtmaJaya from '@/assets/images/logo-atmajaya.gif'

const HIGHLIGHTS = [
  { icon: GraduationCap, text: 'Kelola seluruh proses akademik dalam satu sistem terpadu.' },
  { icon: ShieldCheck, text: 'Akses berbasis peran dengan audit jejak perubahan data.' },
  { icon: BarChart3, text: 'Statistik dan laporan akademik real-time untuk pimpinan.' },
]

export function AuthLayout() {
  return (
    <div className="grid min-h-screen bg-background lg:grid-cols-2">
      <div className="relative hidden flex-col justify-between overflow-hidden bg-primary-950 p-10 text-white lg:flex">
        <div
          className="absolute inset-0 opacity-[0.08]"
          style={{
            backgroundImage:
              'radial-gradient(circle at 20% 20%, white 1px, transparent 1px), radial-gradient(circle at 80% 60%, white 1px, transparent 1px)',
            backgroundSize: '32px 32px',
          }}
          aria-hidden
        />

        <div className="relative flex items-center gap-3">
          <img src={logoAtmaJaya} alt={APP_NAME} className="size-11 object-contain" />
          <div>
            <p className="text-base font-semibold">{APP_NAME}</p>
            <p className="text-sm text-primary-200">{APP_DESCRIPTION}</p>
          </div>
        </div>

        <div className="relative space-y-6">
          <h1 className="text-3xl font-semibold leading-tight tracking-tight">
            Satu sistem, seluruh kegiatan akademik universitas.
          </h1>
          <ul className="space-y-4">
            {HIGHLIGHTS.map(({ icon: Icon, text }) => (
              <li key={text} className="flex items-start gap-3 text-sm text-primary-100">
                <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-white/10">
                  <Icon className="size-4" aria-hidden />
                </span>
                {text}
              </li>
            ))}
          </ul>
        </div>

        <p className="relative text-xs text-primary-300">© {new Date().getFullYear()} {APP_NAME}. Seluruh hak cipta dilindungi.</p>
      </div>

      <div className="relative flex flex-col">
        <div className="flex items-center justify-between p-4 sm:p-6 lg:justify-end">
          <div className="flex items-center gap-2 lg:hidden">
            <img src={logoAtmaJaya} alt={APP_NAME} className="size-8 object-contain" />
            <span className="text-sm font-semibold text-ink-primary">{APP_NAME}</span>
          </div>
          <ThemeToggle />
        </div>

        <div className="flex flex-1 items-center justify-center px-4 pb-10 sm:px-6">
          <Outlet />
        </div>
      </div>
    </div>
  )
}
