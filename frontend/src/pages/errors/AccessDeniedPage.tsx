import { ShieldAlert } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { EmptyState } from '@/components/ui/EmptyState'
import { Button } from '@/components/ui/Button'
import { ROUTES } from '@/constants/routes'

/** Rendered inline by RequirePermission (not a redirect) so the URL the user tried stays visible in the address bar. */
export function AccessDeniedPage() {
  const navigate = useNavigate()

  return (
    <div className="flex min-h-[60vh] items-center justify-center">
      <EmptyState
        icon={ShieldAlert}
        title="Anda tidak memiliki akses ke halaman ini"
        description="Hubungi admin universitas Anda kalau ini seharusnya bisa Anda akses."
        action={
          <Button variant="outline" onClick={() => navigate(ROUTES.dashboard)}>
            Kembali ke Dashboard
          </Button>
        }
      />
    </div>
  )
}
