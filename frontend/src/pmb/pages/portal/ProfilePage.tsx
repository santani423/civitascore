import { useState } from 'react'
import { pmbApplicant } from '@/pmb/api/applicant'
import { AchievementManager, EducationManager, GuardianManager, IdentityForm } from '@/pmb/components/ProfileManagers'
import { Tabs } from '@/pmb/components/Tabs'
import { Card } from '@/components/ui/Card'
import { PageHeader } from '@/components/ui/PageHeader'

type Tab = 'identity' | 'education' | 'guardians' | 'achievements'

/** POR-02 Profil & data diri (dipakai ulang oleh semua pendaftaran akun ini). */
export function ProfilePage() {
  const [tab, setTab] = useState<Tab>('identity')

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Profil & Data Diri" description="Data ini dipakai untuk semua pendaftaran Anda." />
      <Tabs<Tab> active={tab} onChange={setTab} tabs={[{ key: 'identity', label: 'Identitas' }, { key: 'education', label: 'Pendidikan' }, { key: 'guardians', label: 'Orang tua / wali' }, { key: 'achievements', label: 'Prestasi' }]} />
      <Card>
        {tab === 'identity' && <IdentityForm onSave={(data, lockVersion) => pmbApplicant.saveProfile({ ...data, lock_version: lockVersion })} />}
        {tab === 'education' && <EducationManager />}
        {tab === 'guardians' && <GuardianManager />}
        {tab === 'achievements' && <AchievementManager />}
      </Card>
    </div>
  )
}
