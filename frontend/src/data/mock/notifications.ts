import type { NotificationItem } from '@/types/dashboard'

export const NOTIFICATIONS: NotificationItem[] = [
  {
    id: 'notif-1',
    type: 'warning',
    title: 'Periode KRS akan berakhir',
    description: 'Periode pengisian KRS semester ganjil berakhir dalam 2 hari.',
    isRead: false,
    timestamp: new Date(Date.now() - 15 * 60_000).toISOString(),
  },
  {
    id: 'notif-2',
    type: 'success',
    title: 'Pembayaran terverifikasi',
    description: 'Pembayaran UKT mahasiswa Ahmad Fauzan telah terverifikasi.',
    isRead: false,
    timestamp: new Date(Date.now() - 2 * 60 * 60_000).toISOString(),
  },
  {
    id: 'notif-3',
    type: 'info',
    title: 'Pengumuman baru',
    description: 'Jadwal UTS semester ganjil 2026/2027 telah dipublikasikan.',
    isRead: true,
    timestamp: new Date(Date.now() - 20 * 60 * 60_000).toISOString(),
  },
  {
    id: 'notif-4',
    type: 'danger',
    title: 'Pengajuan cuti ditolak',
    description: 'Pengajuan cuti akademik atas nama Rizky Ananda ditolak Dekan.',
    isRead: true,
    timestamp: new Date(Date.now() - 2 * 24 * 60 * 60_000).toISOString(),
  },
]
