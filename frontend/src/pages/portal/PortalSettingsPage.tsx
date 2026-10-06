import { Link } from 'react-router-dom'
import { KeyRound, MonitorSmartphone, UserCircle } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { ROUTES } from '@/constants/routes'

/**
 * Pengaturan akun mahasiswa — mengarah ke fitur akun yang sudah ada
 * (ganti password, sesi & perangkat, profil), tidak membuat pengaturan baru.
 */
export function PortalSettingsPage() {
  const items = [
    {
      icon: KeyRound,
      title: 'Ubah Password',
      description: 'Ganti password akun Anda secara berkala. Jangan gunakan NIM atau tanggal lahir.',
      to: ROUTES.changePassword,
      action: 'Ubah password',
    },
    {
      icon: MonitorSmartphone,
      title: 'Sesi & Perangkat',
      description: 'Lihat perangkat yang sedang login dan keluarkan sesi yang tidak dikenal.',
      to: ROUTES.pengaturan.security,
      action: 'Kelola sesi',
    },
    {
      icon: UserCircle,
      title: 'Profil',
      description: 'Perbarui nomor telepon, alamat, dan foto profil.',
      to: ROUTES.portal.profil,
      action: 'Buka profil',
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Pengaturan" breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengaturan' }]} />

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        {items.map((item) => (
          <Card key={item.title}>
            <item.icon className="size-5 text-primary" />
            <p className="mt-3 font-medium text-ink-primary">{item.title}</p>
            <p className="mt-1 text-sm text-ink-secondary">{item.description}</p>
            <Link to={item.to} className="mt-4 block">
              <Button size="sm" variant="outline" className="w-full">
                {item.action}
              </Button>
            </Link>
          </Card>
        ))}
      </div>
    </div>
  )
}
