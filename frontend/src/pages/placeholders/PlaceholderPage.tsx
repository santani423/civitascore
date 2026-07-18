import { Construction } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'

export interface PlaceholderPageProps {
  title: string
}

/** Stand-in for every sidebar destination that isn't built yet — keeps navigation and routing complete without faking real content. */
export function PlaceholderPage({ title }: PlaceholderPageProps) {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader title={title} breadcrumb={[{ label: 'Dashboard', path: '/dashboard' }, { label: title }]} />

      <Card>
        <EmptyState
          icon={Construction}
          title="Modul ini belum tersedia"
          description={`Halaman "${title}" akan dikembangkan pada fase pengembangan berikutnya, setelah integrasi API modul terkait selesai.`}
        />
      </Card>
    </div>
  )
}
