import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { CalendarDays, ClipboardList, GraduationCap, Wallet, type LucideIcon } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { StatCard } from '@/components/ui/StatCard'
import { useAuthStore } from '@/stores/authStore'
import { ROUTES } from '@/constants/routes'
import { formatCurrencyIDR } from '@/utils/formatters'

/**
 * Data contoh statis — halaman ini murni tampilan (fase UI dulu, belum
 * tersambung ke API sungguhan), lihat pesan permintaan yang meminta UI
 * penuh dengan data contoh untuk seluruh fitur Portal Mahasiswa.
 */
const TODAY_SCHEDULE = [
  { time: '08:00 - 09:40', course: 'Struktur Data', room: 'Lab Komputer 2', lecturer: 'Dr. Ahmad Fauzi' },
  { time: '10:00 - 11:40', course: 'Basis Data', room: 'Ruang 301', lecturer: 'Dr. Siti Nurhaliza' },
  { time: '13:00 - 14:40', course: 'Bahasa Inggris Akademik', room: 'Ruang 205', lecturer: 'Rina Marlina, M.Pd.' },
]

const UPCOMING_TASKS = [
  { title: 'Tugas Besar 1 - Struktur Data', course: 'Struktur Data', dueDate: '2026-07-22', status: 'Belum Dikumpulkan' as const },
  { title: 'Kuis Normalisasi Database', course: 'Basis Data', dueDate: '2026-07-24', status: 'Belum Dikumpulkan' as const },
  { title: 'Esai Argumentatif', course: 'Bahasa Inggris Akademik', dueDate: '2026-07-27', status: 'Dikumpulkan' as const },
]

const TASK_STATUS_VARIANT: Record<(typeof UPCOMING_TASKS)[number]['status'], BadgeVariant> = {
  'Belum Dikumpulkan': 'warning',
  Dikumpulkan: 'success',
}

const ANNOUNCEMENTS = [
  { title: 'Jadwal Ujian Tengah Semester Ganjil 2026/2027', date: '2026-07-18' },
  { title: 'Perpanjangan Masa Pembayaran UKT Semester Ganjil', date: '2026-07-15' },
  { title: 'Pendaftaran Beasiswa Unggulan Dibuka', date: '2026-07-10' },
]

const LETTER_REQUESTS = [
  { title: 'Surat Keterangan Aktif Kuliah', submittedAt: '2026-07-14', status: 'Selesai' as const },
  { title: 'Surat Keterangan Bebas Perpustakaan', submittedAt: '2026-07-19', status: 'Diproses' as const },
]

const LETTER_STATUS_VARIANT: Record<(typeof LETTER_REQUESTS)[number]['status'], BadgeVariant> = {
  Selesai: 'success',
  Diproses: 'info',
}

const ADVISING_SCHEDULE = [
  { title: 'Bimbingan Akademik Semester Ganjil', with: 'Dr. Bambang Sutrisno (Dosen PA)', date: '2026-07-25', time: '10:00' },
]

const ACADEMIC_CALENDAR = [
  { title: 'Ujian Tengah Semester', date: '28 Jul - 1 Agu 2026' },
  { title: 'Batas Akhir Pengisian KRS Susulan', date: '31 Jul 2026' },
  { title: 'Ujian Akhir Semester', date: '15 - 19 Des 2026' },
]

function DashboardCard({
  title,
  icon: Icon,
  action,
  children,
}: {
  title: string
  icon: LucideIcon
  action?: { label: string; to: string }
  children: ReactNode
}) {
  return (
    <Card
      title={
        <span className="flex items-center gap-2">
          <Icon className="size-4 text-primary" />
          {title}
        </span>
      }
      actions={
        action && (
          <Link to={action.to} className="text-sm font-medium text-primary hover:underline">
            {action.label}
          </Link>
        )
      }
    >
      {children}
    </Card>
  )
}

export function PortalDashboardPage() {
  const userName = useAuthStore((state) => state.session?.user.name)

  return (
    <div className="flex flex-col gap-6">
      <PageHeader
        title="Dashboard Mahasiswa"
        description={userName ? `Selamat datang kembali, ${userName}.` : 'Ringkasan aktivitas akademik Anda.'}
      />

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="IP Semester" value="3.72" icon={GraduationCap} caption="Semester Ganjil 2026/2027" />
        <StatCard label="IPK" value="3.65" icon={GraduationCap} caption="8 semester" />
        <StatCard label="Tagihan Aktif" value={formatCurrencyIDR(5_000_000)} icon={Wallet} caption="Jatuh tempo 15 Agu 2026" />
        <StatCard label="Status KRS" value="Disetujui" icon={ClipboardList} caption="Ganjil 2026/2027" />
      </div>

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <DashboardCard title="Jadwal Hari Ini" icon={CalendarDays} action={{ label: 'Lihat Semua', to: ROUTES.portal.jadwal }}>
          <ul className="divide-y divide-border">
            {TODAY_SCHEDULE.map((item) => (
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
        </DashboardCard>

        <DashboardCard title="Tugas Mendekati Deadline" icon={ClipboardList} action={{ label: 'Lihat Semua', to: ROUTES.portal.tugas }}>
          <ul className="divide-y divide-border">
            {UPCOMING_TASKS.map((task) => (
              <li key={task.title} className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                <div className="min-w-0">
                  <p className="truncate font-medium text-ink-primary">{task.title}</p>
                  <p className="text-xs text-ink-tertiary">
                    {task.course} · Tenggat {task.dueDate}
                  </p>
                </div>
                <Badge variant={TASK_STATUS_VARIANT[task.status]}>{task.status}</Badge>
              </li>
            ))}
          </ul>
        </DashboardCard>
      </div>

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <DashboardCard title="Pengumuman" icon={CalendarDays} action={{ label: 'Lihat Semua', to: ROUTES.portal.pengumuman }}>
          <ul className="divide-y divide-border">
            {ANNOUNCEMENTS.map((item) => (
              <li key={item.title} className="py-3 first:pt-0 last:pb-0">
                <p className="font-medium text-ink-primary">{item.title}</p>
                <p className="text-xs text-ink-tertiary">{item.date}</p>
              </li>
            ))}
          </ul>
        </DashboardCard>

        <DashboardCard title="Status Pengajuan Surat" icon={ClipboardList} action={{ label: 'Lihat Semua', to: ROUTES.portal.surat }}>
          <ul className="divide-y divide-border">
            {LETTER_REQUESTS.map((item) => (
              <li key={item.title} className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                <div>
                  <p className="font-medium text-ink-primary">{item.title}</p>
                  <p className="text-xs text-ink-tertiary">Diajukan {item.submittedAt}</p>
                </div>
                <Badge variant={LETTER_STATUS_VARIANT[item.status]}>{item.status}</Badge>
              </li>
            ))}
          </ul>
        </DashboardCard>
      </div>

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <DashboardCard
          title="Jadwal Bimbingan"
          icon={CalendarDays}
          action={{ label: 'Lihat Semua', to: ROUTES.portal.bimbinganAkademik }}
        >
          <ul className="divide-y divide-border">
            {ADVISING_SCHEDULE.map((item) => (
              <li key={item.title} className="py-3 first:pt-0 last:pb-0">
                <p className="font-medium text-ink-primary">{item.title}</p>
                <p className="text-xs text-ink-tertiary">
                  {item.with} · {item.date} pukul {item.time}
                </p>
              </li>
            ))}
          </ul>
        </DashboardCard>

        <DashboardCard title="Kalender Akademik" icon={CalendarDays}>
          <ul className="divide-y divide-border">
            {ACADEMIC_CALENDAR.map((item) => (
              <li key={item.title} className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                <p className="font-medium text-ink-primary">{item.title}</p>
                <span className="whitespace-nowrap text-sm text-ink-secondary">{item.date}</span>
              </li>
            ))}
          </ul>
        </DashboardCard>
      </div>
    </div>
  )
}
