import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import type { UniversityComparisonRow } from '@/types/tenancy'
import { formatNumber } from '@/utils/formatters'

export interface UniversityComparisonTableProps {
  data: UniversityComparisonRow[]
  isLoading: boolean
}

export function UniversityComparisonTable({ data, isLoading }: UniversityComparisonTableProps) {
  const columns: DataTableColumn<UniversityComparisonRow>[] = [
    { header: 'Universitas', cell: (row) => <span className="font-medium text-ink-primary">{row.name}</span> },
    { header: 'Mahasiswa', cell: (row) => formatNumber(row.students) },
    { header: 'Dosen', cell: (row) => formatNumber(row.lecturers) },
    { header: 'Pegawai', cell: (row) => formatNumber(row.employees) },
    { header: 'Tagihan Belum Dibayar', cell: (row) => formatNumber(row.unpaid_invoices) },
    { header: 'Menunggu Persetujuan', cell: (row) => formatNumber(row.pending_approvals) },
  ]

  return (
    <Card title="Perbandingan Antar Universitas" noPadding>
      <DataTable
        columns={columns}
        data={data}
        rowKey={(row) => row.id}
        emptyMessage={isLoading ? 'Memuat...' : 'Belum ada universitas.'}
      />
    </Card>
  )
}
