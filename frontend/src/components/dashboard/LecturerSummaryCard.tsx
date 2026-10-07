import { useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { useAuthStore, selectPermissions } from '@/stores/authStore'
import { lecturerProfileService } from '@/services/academicService'
import { ROUTES } from '@/constants/routes'
import { FUNCTIONAL_RANK_LABEL } from '@/constants/lecturer'

const QUICK_LINKS = [
  { label: 'Penilaian', path: ROUTES.akademik.penilaian, permission: 'grades.read' },
  { label: 'Absensi', path: ROUTES.akademik.absensi, permission: 'attendance.read' },
  { label: 'Ujian', path: ROUTES.akademik.ujian, permission: 'exams.read' },
  { label: 'Bank Soal', path: ROUTES.akademik.bankSoal, permission: 'question_bank.read' },
]

/**
 * Ringkasan identitas dosen yang login + pintasan ke fitur dosen. Hanya
 * dirender untuk pemegang lecturer_profile.read (lihat DashboardPage).
 */
export function LecturerSummaryCard() {
  const navigate = useNavigate()
  const permissions = useAuthStore(selectPermissions)
  const getProfile = useCallback(() => lecturerProfileService.show(), [])
  const { data: profile, isLoading, error } = useFetch(getProfile)

  if (error) return <Alert variant="warning">{error}</Alert>
  if (isLoading || !profile) return null

  const subtitle = [
    `NIDN ${profile.nidn}`,
    profile.faculty_name,
    profile.functional_rank ? FUNCTIONAL_RANK_LABEL[profile.functional_rank] : null,
  ]
    .filter(Boolean)
    .join(' · ')

  return (
    <Card
      title={profile.name}
      description={subtitle}
      actions={
        <Button variant="outline" size="sm" onClick={() => navigate(ROUTES.profilDosen)}>
          Profil Saya
        </Button>
      }
    >
      <div className="flex flex-wrap gap-2">
        {QUICK_LINKS.filter((link) => permissions.includes(link.permission)).map((link) => (
          <Button key={link.path} variant="secondary" size="sm" onClick={() => navigate(link.path)}>
            {link.label}
          </Button>
        ))}
      </div>
    </Card>
  )
}
