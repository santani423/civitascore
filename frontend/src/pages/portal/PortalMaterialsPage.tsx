import { useCallback, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { FileText, Search } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { MaterialItem } from '@/components/portal/MaterialItem'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'

export function PortalMaterialsPage() {
  const fetchMaterials = useCallback(() => studentPortalService.materials(), [])
  const { data, isLoading, error, refetch } = useFetch(fetchMaterials)
  const [search, setSearch] = useState('')

  const courses = useMemo(() => {
    const term = search.trim().toLowerCase()

    return (data?.courses ?? [])
      .map((course) => ({
        ...course,
        materials: term
          ? course.materials.filter((material) => `${material.title} ${course.course.name}`.toLowerCase().includes(term))
          : course.materials,
      }))
      .filter((course) => course.materials.length > 0)
  }, [data, search])

  if (isLoading && !data) return <PortalLoading cards={0} rows={6} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Materi Perkuliahan"
        description="Seluruh materi dari mata kuliah yang Anda ikuti semester ini."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Perkuliahan' }, { label: 'Materi' }]}
      />

      <div className="max-w-sm">
        <Input placeholder="Cari materi atau mata kuliah" value={search} onChange={(event) => setSearch(event.target.value)} leftIcon={<Search className="size-4" />} />
      </div>

      {courses.length === 0 ? (
        <PortalEmpty icon={FileText} title={search ? 'Materi tidak ditemukan.' : 'Belum ada materi yang dibagikan.'} />
      ) : (
        courses.map((course) => (
          <Card
            key={course.id}
            title={course.course.name}
            description={`${course.course.code} · Kelas ${course.class_code} · ${course.lecturer?.name ?? '-'}`}
            actions={
              <Link to={ROUTES.portal.mataKuliahDetail.replace(':id', course.id)} className="text-xs font-medium text-primary hover:underline">
                Detail kelas
              </Link>
            }
          >
            <div className="flex flex-col gap-2">
              {course.materials.map((material) => (
                <div key={material.id}>
                  {material.meeting_number && <p className="mb-1 text-xs font-medium text-ink-tertiary">Pertemuan {material.meeting_number}</p>}
                  <MaterialItem material={material} />
                </div>
              ))}
            </div>
          </Card>
        ))
      )}
    </div>
  )
}
