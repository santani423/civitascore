/**
 * 5 Pilar Student Life Journey — official content from the university's
 * Student Life Journey page (BKK Unika Atma Jaya). Names and descriptions
 * stay in Indonesian in every language (the page marks them `lang="id"`);
 * only the page's own interface text is translated (i18n `studentLife.*`).
 * Do not paraphrase these or add pillar claims that are not in the source.
 */

/** Dictionary key for per-pillar interface text (alt text). */
export type PillarKey = 'achievement' | 'leadership' | 'interpersonal' | 'intellectual' | 'sustainability'

export interface Pillar {
  key: PillarKey
  /** Section anchor on /student-life-journey, also `{pillar}` in VITE_LANDING_ACTIVITIES_PILLAR_URL. */
  id: string
  /** 1–5, also `{number}` in VITE_LANDING_ACTIVITIES_PILLAR_URL. */
  number: number
  title: string
  description: string
  /**
   * Source images, as published on myjourney-api.atmajaya.ac.id. Both URLs
   * currently serve the same file (a 4167×4167 PNG of the pillar icon,
   * checked by checksum on 2026-10-26), so the page renders `image` only and
   * each file downloads once. Use `icon` for small renderings if BKK
   * publishes a distinct icon.
   */
  image: string
  icon: string
}

const ASSET_BASE = 'https://myjourney-api.atmajaya.ac.id/storage/bkk-ormawa'

/** Pixel size of the source images (for width/height, so space is reserved before they load). */
export const PILLAR_IMAGE_SIZE = 4167

/** Page title, split so "5 Pilar" can carry the accent color. */
export const STUDENT_LIFE_TITLE = { lead: '5 Pilar', rest: 'Student Life Journey' } as const

export const STUDENT_LIFE_INTRO =
  'Proses pengembangan diri, keterlibatan dan partisipasi mahasiswa Unika Atma Jaya sejak mengikuti proses inisiasi Student Life Journey bagi mahasiswa baru, menjalani berbagai aktivitas kampus dan perkuliahan hingga lulus yang terbagi menjadi 5 keunggulan utama yang disebut dengan 5 pilar.'

export const PILLARS: readonly Pillar[] = [
  {
    key: 'achievement',
    id: 'prestasi-dan-reputasi',
    number: 1,
    title: 'Prestasi dan Reputasi',
    description:
      'Prestasi adalah capaian sebagai peserta dan pemenang dalam sebuah kompetisi atau kejuaraan dalam bidang penalaran, olahraga, kesenian dan keagamaan atau sejenisnya di berbagai tingkatan (internasional, nasional, lokal). Reputasi adalah capaian atau dedikasi dalam suatu kegiatan tertentu dalam bidang penalaran, olahraga, kesenian dan keagamaan atau sejenisnya di berbagai tingkatan (internasional, nasional, lokal).',
    image: `${ASSET_BASE}/3d020671a7d118414e7bf51779ddcfea4899ac2b.png`,
    icon: `${ASSET_BASE}/fe04120ef39d9f7d88404e22f1a78704f7431f97.png`,
  },
  {
    key: 'leadership',
    id: 'kepemimpinan-transformatif',
    number: 2,
    title: 'Kepemimpinan Transformatif',
    // Source text reads "adalah adalah"; the duplicated word is dropped.
    description:
      'Kepemimpinan transformatif adalah kemampuan sebagai pemimpin yang antusias, berinisiatif, empatik dan inovatif untuk mendorong perubahan positif dalam sebuah kelompok dalam menjalani proses untuk mencapai tujuan kelompok.',
    image: `${ASSET_BASE}/df28b880b3b318f35506d1349d1312dbc0338472.png`,
    icon: `${ASSET_BASE}/4094f1a49f1cf654a5267037b77411d105ed3fb1.png`,
  },
  {
    key: 'interpersonal',
    id: 'kompetensi-interpersonal',
    number: 3,
    title: 'Kompetensi Interpersonal',
    description:
      'Kompetensi Interpersonal adalah sejumlah kemampuan yang dimiliki seseorang untuk bisa menjalin relasi, komunikasi dan berkolaborasi dengan orang lain, setelah terlebih dahulu memahami tentang kekuatan dan keterbatasan dirinya sendiri dalam berbagai hal.',
    image: `${ASSET_BASE}/075dceb07bc219dc8c0aa8c540e89b28f8e5d546.png`,
    icon: `${ASSET_BASE}/e459be8c5b4ae3498155095978f53f010dc68b80.png`,
  },
  {
    key: 'intellectual',
    id: 'keunggulan-intelektual',
    number: 4,
    title: 'Keunggulan Intelektual',
    description:
      'Keunggulan intelektual adalah pengembangan hard skill dalam bidang keilmuan tertentu yang diperoleh tidak saja lewat perkuliahan tetapi dari berbagai hal di luar perkuliahan.',
    image: `${ASSET_BASE}/bdf86598fb729edaf8fc9e7745987b61ce368d49.png`,
    icon: `${ASSET_BASE}/15e974a40c335be4eba1364dadac12afa4d61c91.png`,
  },
  {
    key: 'sustainability',
    id: 'perilaku-keberlanjutan',
    number: 5,
    title: 'Perilaku Keberlanjutan',
    description:
      'Perilaku keberlanjutan adalah tingkah laku yang bertujuan untuk menyelamatkan dan melindungi sumber daya alam yang terbatas, memelihara keberlanjutan budaya, memungkinkan akses yang adil bagi semua orang, dan mendorong kerja sama, saling membantu serta diarahkan untuk melindungi lingkungan alam dan lingkungan sosial.',
    image: `${ASSET_BASE}/d769594ed73ec7f2027ab2804f4bd2228b94f671.png`,
    icon: `${ASSET_BASE}/6cc2e890a2edd0344e1493dfb5dc216dfafd6aae.png`,
  },
]

/** "01" … "05". */
export function pillarNumber(pillar: Pillar): string {
  return String(pillar.number).padStart(2, '0')
}

/** Contact channels of BKK Unika Atma Jaya, as listed on the source page. */
export const BKK_CONTACT = {
  email: 'bkk@atmajaya.ac.id',
  address: 'Jl. Jenderal Sudirman RT 02 RW 04 No. 51, Karet Semanggi, Jakarta 12930',
  whatsapp: { label: '+62 852-8183-2852', href: 'https://api.whatsapp.com/send?phone=6285281832852' },
  phone: {
    areaCode: '(62-21)',
    numbers: [
      { label: '5727615', href: 'tel:+62215727615' },
      { label: '5703306', href: 'tel:+62215703306' },
      { label: '5708823', href: 'tel:+62215708823' },
    ],
    extension: 'Ext. 328',
  },
  socials: [
    { name: 'Facebook', href: 'https://www.facebook.com/uaj' },
    { name: 'Instagram', href: 'https://www.instagram.com/bkk_uaj/' },
    { name: 'YouTube', href: 'https://www.youtube.com/channel/UCZF1QeGn8psVgDGL_PF0Hyw' },
  ],
} as const
