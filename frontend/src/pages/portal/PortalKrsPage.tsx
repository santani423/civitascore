import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Select } from '@/components/ui/Select'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

const KRS_SUMMARY = {
  totalSks: 21,
  maxSks: 24,
  status: 'Disetujui',
}

const KRS_ITEMS = [
  {
    id: '1',
    code: 'IF301',
    course: 'Struktur Data',
    sks: 3,
    class: 'A',
    lecturer: 'Dr. Ahmad Fauzi',
    schedule: 'Senin 08:00-09:40',
  },
  {
    id: '2',
    code: 'IF302',
    course: 'Basis Data',
    sks: 3,
    class: 'A',
    lecturer: 'Dr. Siti Nurhaliza',
    schedule: 'Senin 10:00-11:40',
  },
  {
    id: '3',
    code: 'UM101',
    course: 'Bahasa Inggris Akademik',
    sks: 2,
    class: 'B',
    lecturer: 'Rina Marlina, M.Pd.',
    schedule: 'Selasa 13:00-14:40',
  },
  {
    id: '4',
    code: 'IF303',
    course: 'Pemrograman Web',
    sks: 3,
    class: 'A',
    lecturer: 'Yusuf Maulana, M.Kom.',
    schedule: 'Rabu 08:00-10:30',
  },
  {
    id: '5',
    code: 'IF304',
    course: 'Jaringan Komputer',
    sks: 3,
    class: 'B',
    lecturer: 'Dr. Hendra Wijaya',
    schedule: 'Kamis 10:00-12:30',
  },
]

const KRS_COLUMNS: DataTableColumn<(typeof KRS_ITEMS)[number]>[] = [
  { header: 'Kode', cell: (row) => row.code },
  { header: 'Mata Kuliah', cell: (row) => row.course },
  { header: 'SKS', cell: (row) => row.sks },
  { header: 'Kelas', cell: (row) => row.class },
  { header: 'Dosen Pengampu', cell: (row) => row.lecturer },
  { header: 'Jadwal', cell: (row) => row.schedule },
]

const AVAILABLE_COURSE_OPTIONS = [
  { value: 'if305', label: 'Pemrograman Berorientasi Objek' },
  { value: 'if306', label: 'Sistem Operasi' },
  { value: 'if307', label: 'Kecerdasan Buatan' },
]

const AVAILABLE_CLASS_OPTIONS = [
  { value: 'a', label: 'Kelas A' },
  { value: 'b', label: 'Kelas B' },
]

export function PortalKrsPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Kartu Rencana Studi (KRS)"
        description="Semester Ganjil 2026/2027"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Akademik' },
          { label: 'KRS' },
        ]}
      />

      <Card>
        <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
          <div>
            <p className="text-ink-tertiary">Total SKS Diambil</p>
            <p className="text-lg font-semibold text-ink-primary">{KRS_SUMMARY.totalSks}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Maksimal SKS</p>
            <p className="text-lg font-semibold text-ink-primary">{KRS_SUMMARY.maxSks}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Status KRS</p>
            <Badge variant="success">{KRS_SUMMARY.status}</Badge>
          </div>
        </div>
      </Card>

      <Card title="Mata Kuliah Diambil" noPadding>
        <DataTable columns={KRS_COLUMNS} data={KRS_ITEMS} rowKey={(row) => row.id} />
      </Card>

      <Card title="Tambah Mata Kuliah">
        {/* Murni tampilan, belum ada logic tambah/hapus sungguhan pada tabel di atas. */}
        <form onSubmit={(e) => e.preventDefault()} className="flex flex-col gap-4 sm:flex-row sm:items-end">
          <div className="flex-1">
            <Select label="Mata Kuliah" options={AVAILABLE_COURSE_OPTIONS} placeholder="Pilih mata kuliah" />
          </div>
          <div className="flex-1">
            <Select label="Kelas" options={AVAILABLE_CLASS_OPTIONS} placeholder="Pilih kelas" />
          </div>
          <Button type="submit" variant="outline">
            Tambahkan ke KRS
          </Button>
        </form>
      </Card>
    </div>
  )
}
