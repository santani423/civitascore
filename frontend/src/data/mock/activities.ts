import type { ActivityItem } from '@/types/dashboard'

export const RECENT_ACTIVITIES: ActivityItem[] = [
  {
    id: 'act-1',
    type: 'student_registered',
    title: 'Mahasiswa baru didaftarkan',
    description: 'Nadia Putri Ramadhan terdaftar di Program Studi Teknik Informatika.',
    actor: 'Bagian Akademik',
    timestamp: new Date(Date.now() - 6 * 60_000).toISOString(),
  },
  {
    id: 'act-2',
    type: 'krs_submitted',
    title: 'KRS diajukan',
    description: 'Ahmad Fauzan mengajukan KRS semester ganjil 2026/2027 (21 SKS).',
    actor: 'Ahmad Fauzan',
    timestamp: new Date(Date.now() - 42 * 60_000).toISOString(),
  },
  {
    id: 'act-3',
    type: 'grade_published',
    title: 'Nilai dipublikasikan',
    description: 'Nilai mata kuliah Basis Data untuk kelas TI-3A telah dipublikasikan.',
    actor: 'Dr. Siti Marlina, M.Kom.',
    timestamp: new Date(Date.now() - 3 * 60 * 60_000).toISOString(),
  },
  {
    id: 'act-4',
    type: 'payment_received',
    title: 'Pembayaran diterima',
    description: 'Pembayaran UKT semester ganjil sebesar Rp 6.500.000 telah diverifikasi.',
    actor: 'Bagian Keuangan',
    timestamp: new Date(Date.now() - 5 * 60 * 60_000).toISOString(),
  },
  {
    id: 'act-5',
    type: 'letter_approved',
    title: 'Surat disetujui',
    description: 'Surat keterangan aktif kuliah atas nama Bagas Prasetyo telah disetujui.',
    actor: 'Bagian Akademik',
    timestamp: new Date(Date.now() - 26 * 60 * 60_000).toISOString(),
  },
  {
    id: 'act-6',
    type: 'complaint_resolved',
    title: 'Pengaduan diselesaikan',
    description: 'Pengaduan terkait akses portal akademik telah ditandai selesai.',
    actor: 'Tim IT Support',
    timestamp: new Date(Date.now() - 30 * 60 * 60_000).toISOString(),
  },
]
