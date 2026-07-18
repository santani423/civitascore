import type { PendingApprovalItem } from '@/types/dashboard'

const hoursAgo = (hours: number) => new Date(Date.now() - hours * 60 * 60_000).toISOString()

export const PENDING_APPROVALS: PendingApprovalItem[] = [
  {
    id: 'appr-1',
    category: 'krs',
    title: 'Persetujuan KRS - Semester Ganjil',
    requester: 'Ahmad Fauzan',
    submittedAt: hoursAgo(1),
    waitingSteps: 2,
  },
  {
    id: 'appr-2',
    category: 'leave',
    title: 'Pengajuan Cuti Akademik',
    requester: 'Dewi Kartika',
    submittedAt: hoursAgo(5),
    waitingSteps: 4,
  },
  {
    id: 'appr-3',
    category: 'grade_change',
    title: 'Perubahan Nilai - Basis Data',
    requester: 'Dr. Siti Marlina, M.Kom.',
    submittedAt: hoursAgo(20),
    waitingSteps: 1,
  },
  {
    id: 'appr-4',
    category: 'letter',
    title: 'Surat Keterangan Aktif Kuliah',
    requester: 'Bagas Prasetyo',
    submittedAt: hoursAgo(28),
    waitingSteps: 1,
  },
  {
    id: 'appr-5',
    category: 'scholarship',
    title: 'Pengajuan Beasiswa PPA',
    requester: 'Nadia Putri Ramadhan',
    submittedAt: hoursAgo(40),
    waitingSteps: 3,
  },
]
