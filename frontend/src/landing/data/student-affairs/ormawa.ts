import type { Ormawa } from './types'

/**
 * SAMPLE student organizations (see types.ts). Generic, function-based names
 * so they read as examples; no members, founding years, people, contacts or
 * social links are given — those must come from BKK, not be made up.
 */
export const ORMAWA: Ormawa[] = [
  {
    kind: 'ormawa',
    slug: 'bem-universitas',
    name: 'Badan Eksekutif Mahasiswa Universitas',
    short: 'BEM U',
    category: 'eksekutif',
    excerpt: 'Lembaga eksekutif tingkat universitas yang menjembatani aspirasi mahasiswa dan mengoordinasikan program kemahasiswaan lintas fakultas.',
    description: [
      'Badan Eksekutif Mahasiswa Universitas menjalankan program kerja kemahasiswaan di tingkat universitas dan menjadi mitra dialog antara mahasiswa dan pimpinan kampus.',
      'Pengurusnya berasal dari berbagai fakultas, sehingga setiap program dirancang agar relevan bagi mahasiswa dari beragam latar belakang studi.',
    ],
    vision: 'Menjadi wadah kepemimpinan mahasiswa yang inklusif, kritis, dan melayani.',
    mission: [
      'Menampung dan menyalurkan aspirasi mahasiswa secara terbuka.',
      'Menyelenggarakan program pengembangan kepemimpinan bagi mahasiswa.',
      'Membangun kolaborasi antara organisasi mahasiswa lintas fakultas.',
    ],
    focus: ['Advokasi mahasiswa', 'Kepemimpinan', 'Kolaborasi lintas fakultas'],
    programs: [
      { title: 'Forum Aspirasi Mahasiswa', description: 'Pertemuan terbuka setiap semester untuk menghimpun masukan mahasiswa tentang layanan kampus.' },
      { title: 'Sekolah Kepemimpinan', description: 'Rangkaian kelas singkat tentang manajemen organisasi, komunikasi, dan pengambilan keputusan.' },
      { title: 'Temu Ormawa', description: 'Ruang koordinasi rutin antarorganisasi mahasiswa untuk menyelaraskan agenda kegiatan.' },
    ],
  },
  {
    kind: 'ormawa',
    slug: 'dpm-universitas',
    name: 'Dewan Perwakilan Mahasiswa Universitas',
    short: 'DPM U',
    category: 'legislatif',
    excerpt: 'Lembaga legislatif mahasiswa yang menyusun aturan organisasi kemahasiswaan dan mengawasi pelaksanaan program kerja.',
    description: [
      'Dewan Perwakilan Mahasiswa Universitas berperan menyusun dan meninjau peraturan organisasi kemahasiswaan, serta mengawasi jalannya program kerja lembaga eksekutif.',
      'Melalui sidang dan rapat dengar pendapat, dewan memastikan setiap keputusan organisasi mahasiswa dapat dipertanggungjawabkan.',
    ],
    vision: 'Menjadi lembaga perwakilan mahasiswa yang transparan dan akuntabel.',
    mission: [
      'Menyusun peraturan organisasi kemahasiswaan secara partisipatif.',
      'Mengawasi pelaksanaan program kerja organisasi mahasiswa.',
      'Meningkatkan literasi berorganisasi di kalangan mahasiswa.',
    ],
    focus: ['Legislasi', 'Pengawasan', 'Tata kelola organisasi'],
    programs: [
      { title: 'Sidang Umum Mahasiswa', description: 'Forum tahunan untuk mengesahkan aturan dan laporan pertanggungjawaban organisasi.' },
      { title: 'Rapat Dengar Pendapat', description: 'Pertemuan berkala dengan pengurus organisasi untuk meninjau capaian program kerja.' },
      { title: 'Kelas Tata Kelola Organisasi', description: 'Pelatihan penyusunan anggaran dasar, notulen, dan laporan kegiatan bagi pengurus baru.' },
    ],
  },
  {
    kind: 'ormawa',
    slug: 'bem-fakultas-biosains-teknologi-inovasi',
    name: 'Badan Eksekutif Mahasiswa Fakultas Biosains, Teknologi dan Inovasi',
    short: 'BEM BTI',
    category: 'fakultas',
    faculty: 'biosains-teknologi-dan-inovasi',
    excerpt: 'Lembaga eksekutif tingkat fakultas yang menghubungkan mahasiswa lintas bidang sains dan teknologi melalui kegiatan ilmiah, inovasi, dan pengabdian.',
    description: [
      'Lembaga eksekutif fakultas ini mengelola program kemahasiswaan dan mendampingi himpunan mahasiswa di lingkungan fakultas dalam menjalankan kegiatannya.',
      'Program kerjanya mempertemukan mahasiswa dari beragam bidang sains dan teknologi untuk berkolaborasi dalam proyek inovasi yang bermanfaat bagi masyarakat.',
    ],
    vision: 'Menjadi penggerak mahasiswa yang inovatif, kolaboratif, dan peduli lingkungan sekitar.',
    mission: [
      'Mendorong kegiatan ilmiah dan kompetisi inovasi di kalangan mahasiswa.',
      'Mengembangkan program pengabdian berbasis sains dan teknologi.',
    ],
    focus: ['Inovasi', 'Kompetisi ilmiah', 'Pengabdian berbasis teknologi'],
    programs: [
      { title: 'Pekan Inovasi Mahasiswa', description: 'Pameran proyek mahasiswa dan diskusi tentang penerapan sains dan teknologi dalam kehidupan sehari-hari.' },
      { title: 'Sains Mengabdi', description: 'Kegiatan pengabdian masyarakat dengan solusi sains dan teknologi tepat guna.' },
    ],
  },
  {
    kind: 'ormawa',
    slug: 'bem-fakultas-psikologi',
    name: 'Badan Eksekutif Mahasiswa Fakultas Psikologi',
    short: 'BEM FPsi',
    category: 'fakultas',
    faculty: 'psikologi',
    excerpt: 'Lembaga eksekutif fakultas yang aktif mengangkat isu kesehatan mental dan kesejahteraan mahasiswa melalui diskusi dan kampanye.',
    description: [
      'Badan Eksekutif Mahasiswa Fakultas Psikologi menyelenggarakan kegiatan yang memperkenalkan ilmu psikologi kepada sesama mahasiswa dan masyarakat.',
      'Salah satu perhatian utamanya adalah kesejahteraan mahasiswa, yang diwujudkan dalam kuliah umum, kampanye, dan kelompok diskusi.',
    ],
    vision: 'Menjadi ruang tumbuh mahasiswa psikologi yang empatik dan berpikir ilmiah.',
    mission: [
      'Menyebarluaskan pengetahuan psikologi secara populer dan bertanggung jawab.',
      'Mendukung kesejahteraan mahasiswa melalui kegiatan edukatif.',
      'Mempererat hubungan antarangkatan.',
    ],
    focus: ['Kesehatan mental', 'Edukasi publik', 'Kesejahteraan mahasiswa'],
    programs: [
      { title: 'Kuliah Umum Psikologi', description: 'Diskusi terbuka bersama praktisi tentang topik psikologi yang dekat dengan kehidupan mahasiswa.' },
      { title: 'Kampanye Peduli Diri', description: 'Rangkaian konten dan kegiatan kecil untuk mengajak mahasiswa menjaga kesehatan mental.' },
    ],
  },
  {
    kind: 'ormawa',
    slug: 'bem-fakultas-hukum',
    name: 'Badan Eksekutif Mahasiswa Fakultas Hukum',
    short: 'BEM FH',
    category: 'fakultas',
    faculty: 'hukum',
    excerpt: 'Lembaga eksekutif fakultas yang mengembangkan nalar hukum mahasiswa melalui diskusi, debat, dan kegiatan literasi hukum.',
    description: [
      'Badan Eksekutif Mahasiswa Fakultas Hukum menjadi wadah mahasiswa hukum untuk berlatih berpikir kritis dan berargumentasi di luar perkuliahan.',
      'Kegiatannya meliputi diskusi isu hukum terkini, lomba debat, hingga penyuluhan hukum sederhana bagi masyarakat.',
    ],
    vision: 'Menjadi komunitas mahasiswa hukum yang berintegritas dan berpihak pada keadilan.',
    mission: [
      'Mengasah kemampuan analisis dan argumentasi hukum mahasiswa.',
      'Mendekatkan pengetahuan hukum kepada masyarakat.',
    ],
    focus: ['Debat hukum', 'Literasi hukum', 'Diskusi isu publik'],
    programs: [
      { title: 'Diskusi Hukum Bulanan', description: 'Kajian kasus dan peraturan terbaru yang dipandu oleh mahasiswa dan dosen pendamping.' },
      { title: 'Penyuluhan Hukum', description: 'Kegiatan edukasi hukum dasar untuk komunitas di sekitar kampus.' },
    ],
  },
  {
    kind: 'ormawa',
    slug: 'hima-sistem-informasi',
    name: 'Himpunan Mahasiswa Sistem Informasi',
    short: 'HIMSI',
    category: 'himpunan',
    faculty: 'biosains-teknologi-dan-inovasi',
    excerpt: 'Himpunan program studi yang mempertemukan mahasiswa sistem informasi untuk belajar bersama, berkompetisi, dan mengenal dunia kerja teknologi.',
    description: [
      'Himpunan Mahasiswa Sistem Informasi menjadi tempat mahasiswa program studi sistem informasi saling mendukung dalam studi dan pengembangan diri.',
      'Kegiatannya mencakup kelompok belajar, kunjungan industri, dan persiapan kompetisi karya ilmiah di bidang sistem informasi.',
    ],
    focus: ['Kelompok belajar', 'Kunjungan industri', 'Karya tulis ilmiah'],
    programs: [
      { title: 'Kelompok Belajar', description: 'Sesi belajar bersama untuk mata kuliah inti, dipandu mahasiswa senior.' },
      { title: 'Kunjungan Industri', description: 'Kunjungan ke perusahaan untuk melihat penerapan sistem informasi secara langsung.' },
      { title: 'Lomba Karya Tulis Ilmiah', description: 'Kompetisi tahunan yang mengajak mahasiswa menulis gagasan solusi digital.' },
    ],
    achievements: [{ year: 2025, title: 'Finalis kompetisi karya tulis ilmiah sistem informasi tingkat nasional' }],
  },
  {
    kind: 'ormawa',
    slug: 'bem-fakultas-bisnis-inovasi-sosial',
    name: 'Badan Eksekutif Mahasiswa Fakultas Bisnis dan Inovasi Sosial',
    short: 'BEM BIS',
    category: 'fakultas',
    faculty: 'bisnis-dan-inovasi-sosial',
    excerpt: 'Lembaga eksekutif tingkat fakultas yang mengajak mahasiswa mengembangkan gagasan bisnis dan inovasi sosial melalui studi kasus, diskusi, dan aksi nyata.',
    description: [
      'Lembaga eksekutif fakultas ini menyelenggarakan kegiatan yang menghubungkan wawasan bisnis dengan kepedulian sosial.',
      'Mahasiswa diajak menganalisis studi kasus, menyusun gagasan usaha, dan merancang inisiatif yang berdampak bagi komunitas.',
    ],
    vision: 'Menjadi ruang tumbuh calon pemimpin bisnis yang beretika dan peduli sesama.',
    mission: [
      'Memperkenalkan praktik bisnis melalui studi kasus dan bincang praktisi.',
      'Mendorong semangat kewirausahaan dan inovasi sosial di kalangan mahasiswa.',
    ],
    focus: ['Studi kasus bisnis', 'Kewirausahaan', 'Inovasi sosial'],
    programs: [
      { title: 'Kompetisi Studi Kasus', description: 'Tim mahasiswa menganalisis persoalan bisnis dan mempresentasikan rekomendasinya.' },
      { title: 'Inkubasi Gagasan Sosial', description: 'Pendampingan singkat untuk mengembangkan gagasan usaha yang berdampak sosial.' },
    ],
  },
  {
    kind: 'ormawa',
    slug: 'bem-fakultas-pendidikan-bahasa',
    name: 'Badan Eksekutif Mahasiswa Fakultas Pendidikan dan Bahasa',
    short: 'BEM PB',
    category: 'fakultas',
    faculty: 'pendidikan-dan-bahasa',
    excerpt: 'Lembaga eksekutif tingkat fakultas yang menumbuhkan semangat mendidik dan kecintaan pada bahasa melalui kelas literasi, diskusi, dan pengabdian.',
    description: [
      'Lembaga eksekutif fakultas ini mewadahi kegiatan mahasiswa di bidang pendidikan dan kebahasaan di luar perkuliahan.',
      'Kegiatannya meliputi kelas literasi, diskusi tentang praktik mengajar, dan pendampingan belajar bagi masyarakat.',
    ],
    focus: ['Literasi', 'Praktik mengajar', 'Pendampingan belajar'],
    programs: [
      { title: 'Kelas Literasi', description: 'Kegiatan membaca dan menulis bersama untuk menumbuhkan budaya literasi.' },
      { title: 'Mengajar untuk Komunitas', description: 'Pendampingan belajar bagi anak-anak di komunitas sekitar kampus.' },
    ],
  },
  {
    kind: 'ormawa',
    slug: 'hima-kedokteran',
    name: 'Himpunan Mahasiswa Kedokteran',
    short: 'HMK',
    category: 'himpunan',
    faculty: 'kedokteran-dan-ilmu-kesehatan',
    excerpt: 'Himpunan mahasiswa kedokteran yang aktif dalam edukasi kesehatan masyarakat dan pengembangan keterampilan klinis dasar.',
    description: [
      'Himpunan Mahasiswa Kedokteran mewadahi mahasiswa untuk mengembangkan keterampilan di luar kurikulum, seperti pertolongan pertama dan komunikasi kesehatan.',
      'Kegiatan pengabdiannya berfokus pada edukasi kesehatan yang mudah dipahami masyarakat.',
    ],
    vision: 'Menjadi himpunan yang menumbuhkan calon dokter yang peduli dan komunikatif.',
    mission: [
      'Menyelenggarakan pelatihan keterampilan dasar kesehatan bagi mahasiswa.',
      'Melaksanakan edukasi kesehatan bagi masyarakat.',
    ],
    focus: ['Edukasi kesehatan', 'Pertolongan pertama', 'Komunikasi kesehatan'],
    programs: [
      { title: 'Pelatihan Pertolongan Pertama', description: 'Simulasi penanganan awal kondisi darurat untuk mahasiswa umum.' },
      { title: 'Edukasi Kesehatan Komunitas', description: 'Penyuluhan pola hidup sehat di lingkungan sekitar kampus.' },
    ],
  },
  {
    kind: 'ormawa',
    slug: 'pers-mahasiswa',
    name: 'Lembaga Pers Mahasiswa',
    short: 'LPM',
    category: 'lainnya',
    excerpt: 'Lembaga pers mahasiswa yang meliput kehidupan kampus dan melatih anggotanya menulis, memotret, dan menyunting secara bertanggung jawab.',
    description: [
      'Lembaga Pers Mahasiswa menerbitkan liputan tentang kegiatan dan isu kampus, ditulis oleh mahasiswa untuk mahasiswa.',
      'Anggotanya belajar jurnalistik dasar, etika peliputan, fotografi berita, dan penyuntingan.',
    ],
    vision: 'Menjadi media mahasiswa yang jujur, berimbang, dan mencerahkan.',
    mission: [
      'Menyajikan informasi kampus yang akurat dan berimbang.',
      'Melatih keterampilan jurnalistik mahasiswa.',
    ],
    focus: ['Jurnalistik', 'Fotografi berita', 'Literasi media'],
    programs: [
      { title: 'Kelas Jurnalistik Dasar', description: 'Pelatihan menulis berita, wawancara, dan etika peliputan untuk anggota baru.' },
      { title: 'Liputan Kampus', description: 'Peliputan kegiatan mahasiswa yang diterbitkan secara berkala.' },
    ],
  },
]
