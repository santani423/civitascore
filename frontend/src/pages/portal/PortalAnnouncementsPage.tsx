import { Pin } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { ROUTES } from '@/constants/routes'

type AnnouncementScope = 'Seluruh Universitas' | 'Fakultas Ilmu Komputer' | 'Program Studi Teknik Informatika'

const ANNOUNCEMENTS: {
  id: string
  title: string
  publishedAt: string
  scope: AnnouncementScope
  excerpt: string
  isPinned: boolean
}[] = [
  {
    id: 'ann-1',
    title: 'Jadwal Ujian Tengah Semester Ganjil 2026/2027',
    publishedAt: '2026-07-18',
    scope: 'Seluruh Universitas',
    excerpt:
      'Ujian Tengah Semester akan dilaksanakan pada tanggal 28 Juli - 1 Agustus 2026. Mahasiswa diwajibkan membawa kartu ujian dan hadir 15 menit sebelum ujian dimulai.',
    isPinned: true,
  },
  {
    id: 'ann-2',
    title: 'Perpanjangan Masa Pembayaran UKT Semester Ganjil',
    publishedAt: '2026-07-15',
    scope: 'Seluruh Universitas',
    excerpt:
      'Masa pembayaran Uang Kuliah Tunggal (UKT) semester ganjil diperpanjang hingga tanggal 15 Agustus 2026. Keterlambatan pembayaran dapat mengakibatkan penangguhan akses akademik.',
    isPinned: true,
  },
  {
    id: 'ann-3',
    title: 'Pendaftaran Beasiswa Unggulan Dibuka',
    publishedAt: '2026-07-10',
    scope: 'Fakultas Ilmu Komputer',
    excerpt:
      'Pendaftaran Beasiswa Unggulan bagi mahasiswa berprestasi telah dibuka. Berkas pendaftaran dapat diajukan melalui menu Beasiswa pada portal mahasiswa paling lambat 5 Agustus 2026.',
    isPinned: false,
  },
  {
    id: 'ann-4',
    title: 'Perubahan Ruang Kuliah Mata Kuliah Basis Data',
    publishedAt: '2026-07-08',
    scope: 'Program Studi Teknik Informatika',
    excerpt:
      'Sehubungan dengan renovasi gedung, perkuliahan Basis Data yang semula di Ruang 301 dipindahkan ke Ruang 405 mulai pekan depan.',
    isPinned: false,
  },
  {
    id: 'ann-5',
    title: 'Sosialisasi Kurikulum Merdeka Belajar Kampus Merdeka',
    publishedAt: '2026-07-02',
    scope: 'Program Studi Teknik Informatika',
    excerpt:
      'Program studi mengundang seluruh mahasiswa untuk mengikuti sosialisasi implementasi Kurikulum MBKM yang akan diselenggarakan secara daring melalui Zoom.',
    isPinned: false,
  },
]

export function PortalAnnouncementsPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pengumuman"
        description="Daftar pengumuman untuk Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengumuman' }]}
      />

      <div className="flex flex-col gap-4">
        {ANNOUNCEMENTS.map((announcement) => (
          <Card key={announcement.id}>
            <h3 className="flex items-center gap-1.5 font-semibold text-ink-primary">
              {announcement.isPinned && <Pin className="size-3.5 shrink-0 text-primary" />}
              {announcement.title}
            </h3>
            <p className="mt-1 text-xs text-ink-tertiary">
              Diterbitkan {announcement.publishedAt} · {announcement.scope}
            </p>
            <p className="mt-2 text-sm text-ink-secondary line-clamp-2">{announcement.excerpt}</p>
          </Card>
        ))}
      </div>
    </div>
  )
}
