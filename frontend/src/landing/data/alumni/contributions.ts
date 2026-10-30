import type { Campaign, ContributionProgram, Contributor } from './types'

/** Sample programs. Rupiah figures are in billions and agree with the yearly series below (35,2 M in 2021–2025). */
export const CONTRIBUTION_PROGRAMS: ContributionProgram[] = [
  {
    key: 'mentoring',
    value: 1240,
    target: 1500,
    unit: 'count',
    title: 'Alumni Mentoring',
    description: 'Mentor alumni mendampingi mahasiswa dan lulusan baru dalam menentukan arah karier.',
    involvement: ['Mendampingi 1–3 mentee selama enam bulan', 'Satu pertemuan daring per bulan', 'Dipasangkan sesuai bidang keahlian'],
  },
  {
    key: 'scholarship',
    value: 412,
    target: 500,
    unit: 'count',
    title: 'Beasiswa Alumni',
    description: 'Beasiswa dari alumni untuk mahasiswa berprestasi dan mahasiswa dari keluarga prasejahtera.',
    involvement: ['Berdonasi secara pribadi atau per angkatan', 'Mendirikan beasiswa atas nama angkatan', 'Menerima laporan penerima setiap tahun'],
  },
  {
    key: 'donation',
    value: 35.2,
    target: 50,
    unit: 'rupiahBillions',
    title: 'Donasi Pendidikan',
    description: 'Donasi untuk laboratorium, perpustakaan, dan fasilitas belajar di seluruh fakultas.',
    involvement: ['Donasi sekali atau rutin', 'Memilih fakultas atau fasilitas tujuan', 'Laporan penggunaan dana yang terbuka'],
  },
  {
    key: 'lecturer',
    value: 286,
    target: 320,
    unit: 'count',
    title: 'Guest Lecturer',
    description: 'Alumni praktisi mengajar sebagai dosen tamu dan membawa kasus nyata ke ruang kelas.',
    involvement: ['Mengajar satu sesi per semester', 'Bahan ajar disusun bersama dosen pengampu', 'Luring di kampus atau daring'],
  },
  {
    key: 'sharing',
    value: 174,
    target: 200,
    unit: 'count',
    title: 'Career Sharing',
    description: 'Sesi berbagi perjalanan karier yang jujur untuk mahasiswa tingkat akhir.',
    involvement: ['Berbagi pengalaman selama 60 menit', 'Terbuka untuk seluruh bidang', 'Dapat dilakukan bersama rekan seangkatan'],
  },
  {
    key: 'industry',
    value: 96,
    target: 120,
    unit: 'count',
    title: 'Kerja Sama Industri',
    description: 'Alumni menghubungkan perusahaannya dengan universitas untuk riset, magang, dan rekrutmen.',
    involvement: ['Menjadi penghubung perusahaan dan fakultas', 'Mengusulkan topik riset atau magang', 'Membuka jalur rekrutmen'],
  },
]

/** Rupiah (billions) donated per full year — sums to CONTRIBUTION_PROGRAMS' donation figure. */
export const DONATION_BY_YEAR = [
  { year: '2021', value: 4.8 },
  { year: '2022', value: 5.6 },
  { year: '2023', value: 6.9 },
  { year: '2024', value: 8.2 },
  { year: '2025', value: 9.7 },
]

export const MENTORING_HOURS_BY_YEAR = [
  { year: '2021', value: 3200 },
  { year: '2022', value: 4100 },
  { year: '2023', value: 5400 },
  { year: '2024', value: 6800 },
  { year: '2025', value: 8300 },
]

/** Rupiah (billions) by the faculty it went to — also sums to the donation figure. */
export const DONATION_BY_FACULTY = [
  { faculty: 'bisnis-dan-inovasi-sosial', value: 9.8 },
  { faculty: 'kedokteran-dan-ilmu-kesehatan', value: 8.1 },
  { faculty: 'hukum', value: 6.4 },
  { faculty: 'biosains-teknologi-dan-inovasi', value: 4.6 },
  { faculty: 'psikologi', value: 3.5 },
  { faculty: 'pendidikan-dan-bahasa', value: 2.8 },
] as const

export const CAMPAIGNS: Campaign[] = [
  { slug: 'laboratorium-simulasi-klinis', title: 'Laboratorium Simulasi Klinis', description: 'Peralatan simulasi baru untuk pendidikan kedokteran dan farmasi.', raised: 3.4, goal: 5, donors: 612 },
  { slug: 'beasiswa-angkatan-2001', title: 'Beasiswa Angkatan 2001', description: 'Beasiswa penuh empat tahun dari satu angkatan untuk mahasiswa prasejahtera.', raised: 1.2, goal: 2, donors: 148 },
  { slug: 'perpustakaan-digital', title: 'Perpustakaan Digital Terbuka', description: 'Akses jurnal internasional untuk seluruh mahasiswa dan alumni.', raised: 0.9, goal: 1.5, donors: 397 },
]

export const CONTRIBUTORS: Contributor[] = [
  { alumnus: 'michael-wirawan', kind: 'scholarship', statement: 'Saya kuliah dengan bantuan orang yang tidak saya kenal. Kini giliran saya.' },
  { alumnus: 'gregorius-bima-santoso', kind: 'mentoring', statement: 'Dua jam sebulan tidak banyak bagi saya, tetapi bisa mengubah arah seseorang.' },
  { alumnus: 'maria-clarissa-wibisono', kind: 'lecturer', statement: 'Mengajar membuat saya terus bertanya apakah cara kerja kami sudah benar.' },
  { alumnus: 'stefanus-aditya-nugroho', kind: 'industry', statement: 'Riset terbaik lahir saat kampus dan lapangan benar-benar saling bicara.' },
]
