import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { ROUTES } from '@/constants/routes'

const WEEKLY_SCHEDULE = [
  {
    day: 'Senin',
    classes: [
      { time: '08:00 - 09:40', course: 'Struktur Data', room: 'Lab Komputer 2', lecturer: 'Dr. Ahmad Fauzi' },
      { time: '10:00 - 11:40', course: 'Basis Data', room: 'Ruang 301', lecturer: 'Dr. Siti Nurhaliza' },
    ],
  },
  {
    day: 'Selasa',
    classes: [
      { time: '13:00 - 14:40', course: 'Bahasa Inggris Akademik', room: 'Ruang 205', lecturer: 'Rina Marlina, M.Pd.' },
    ],
  },
  {
    day: 'Rabu',
    classes: [
      { time: '08:00 - 10:30', course: 'Pemrograman Web', room: 'Lab Komputer 1', lecturer: 'Yusuf Maulana, M.Kom.' },
    ],
  },
  {
    day: 'Kamis',
    classes: [
      { time: '10:00 - 12:30', course: 'Jaringan Komputer', room: 'Lab Jaringan', lecturer: 'Dr. Hendra Wijaya' },
    ],
  },
  {
    day: 'Jumat',
    classes: [
      { time: '08:00 - 09:40', course: 'Kewarganegaraan', room: 'Ruang 102', lecturer: 'Drs. Suherman, M.Si.' },
      { time: '10:00 - 11:40', course: 'Statistika', room: 'Ruang 210', lecturer: 'Dr. Lestari Handayani' },
    ],
  },
]

export function PortalSchedulePage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Jadwal Kuliah"
        description="Semester Ganjil 2026/2027"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Akademik' },
          { label: 'Jadwal Kuliah' },
        ]}
      />

      {WEEKLY_SCHEDULE.map((day) => (
        <Card key={day.day} title={day.day}>
          <ul className="divide-y divide-border">
            {day.classes.map((item) => (
              <li key={item.course} className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                <div>
                  <p className="font-medium text-ink-primary">{item.course}</p>
                  <p className="text-xs text-ink-tertiary">
                    {item.room} · {item.lecturer}
                  </p>
                </div>
                <span className="whitespace-nowrap text-sm text-ink-secondary">{item.time}</span>
              </li>
            ))}
          </ul>
        </Card>
      ))}
    </div>
  )
}
