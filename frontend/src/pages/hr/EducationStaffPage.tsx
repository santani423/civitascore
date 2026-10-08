import { useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import { Download, Plus, Tags, UserCheck, UserX, UsersRound } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { StatCard } from '@/components/ui/StatCard'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { useHrOptions } from '@/hooks/useHrOptions'
import { hrEmployeeService } from '@/services/hrService'
import type { NormalizedApiError } from '@/services/api'
import type { EducationStaffSummary, ExportFormat, HrEmployee } from '@/types/hr'
import { ROUTES } from '@/constants/routes'
import { formatNumber } from '@/utils/formatters'
import { pickFilterParams } from '@/utils/listInitial'
import { EducationStaffFormModal } from './EducationStaffFormModal'

const FILTER_KEYS = ['staff_category', 'work_unit_id', 'employment_status', 'is_active']

const ACTIVE_OPTIONS = [
  { value: '', label: 'Semua status' },
  { value: '1', label: 'Aktif' },
  { value: '0', label: 'Nonaktif' },
]

const tendikPath = (id: string) => ROUTES.sdm.tendikDetail.replace(':id', id)

const formatRatio = (value: number | null) => (value === null ? '-' : `1 : ${formatNumber(value)}`)

/**
 * Modul SDM #4 — Data Tenaga Kependidikan (RANCANGAN-AKUN-SDM §5.4): daftar
 * tendik dengan filter kategori/unit/status, rekap komposisi, dan rasio
 * tendik terhadap mahasiswa/dosen per fakultas untuk laporan akreditasi.
 */
export function EducationStaffPage() {
  const navigate = useNavigate()
  // Backend mensyaratkan keduanya (HrEmployeePolicy::create) — usePermission sendiri OR-match.
  const canCreateEmployee = usePermission('hr_employees.create')
  const canCreateStaff = usePermission('hr_staff.create')
  const canCreate = canCreateEmployee && canCreateStaff
  const canExport = usePermission('hr_employees.export')
  const options = useHrOptions()

  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, FILTER_KEYS))
  const [searchInput, setSearchInput] = useState('')
  const [filterInput, setFilterInput] = useState<Record<string, string>>(initialFilter)
  const [showForm, setShowForm] = useState(false)
  const [exporting, setExporting] = useState<ExportFormat | null>(null)
  const [exportError, setExportError] = useState<string | null>(null)

  const list = usePaginatedList<HrEmployee>({ fetcher: hrEmployeeService.staff, initialFilter })
  const summary = useFetch(hrEmployeeService.staffSummary)

  const setFilterValue = (key: string, value: string) => setFilterInput((current) => ({ ...current, [key]: value }))

  const applyFilter = (next: Record<string, string>) => {
    setFilterInput(next)
    list.setSearch(searchInput)
    list.setFilter(Object.fromEntries(Object.entries(next).filter(([, value]) => value !== '')))
  }

  /** Klik baris rekap → daftar langsung tersaring ke kelompok itu (tendik aktif). */
  const focusOn = (key: 'staff_category' | 'work_unit_id', value: string) => {
    applyFilter({ [key]: value, is_active: '1' })
    document.getElementById('daftar-tendik')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  const handleExport = async (format: ExportFormat) => {
    setExportError(null)
    setExporting(format)
    try {
      await hrEmployeeService.export(
        format,
        { search: list.search || undefined, filter: { ...list.filter, employee_type: 'staff' } },
        `tenaga-kependidikan.${format}`,
      )
    } catch (error) {
      setExportError((error as NormalizedApiError).message)
    } finally {
      setExporting(null)
    }
  }

  const columns: DataTableColumn<HrEmployee>[] = [
    {
      header: 'Tenaga Kependidikan',
      cell: (row) => (
        <Link to={tendikPath(row.id)} className="block">
          <p className="font-medium text-primary hover:underline">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.nip ? `NIP ${row.nip}` : 'NIP belum diisi'}</p>
        </Link>
      ),
    },
    {
      header: 'Kategori',
      cell: (row) =>
        row.staff_category_label ? <Badge variant="info">{row.staff_category_label}</Badge> : <Badge variant="warning">Belum diisi</Badge>,
    },
    {
      header: 'Unit Kerja / Jabatan',
      cell: (row) => (
        <div>
          <p>{row.unit_kerja || '-'}</p>
          {row.position && <p className="text-xs text-ink-tertiary">{row.position}</p>}
        </div>
      ),
    },
    { header: 'Penugasan', cell: (row) => row.assigned_facility ?? '-' },
    { header: 'Status Kepegawaian', cell: (row) => row.employment_status_label },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Data Tenaga Kependidikan"
        description="Staf administrasi, laboran, teknisi, pustakawan, dan tenaga kependidikan lain beserta rekap komposisinya."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'SDM' }, { label: 'Data Tenaga Kependidikan' }]}
        actions={
          <div className="flex flex-wrap gap-2">
            {canExport && (
              <>
                <Button
                  variant="outline"
                  leftIcon={<Download className="size-4" />}
                  isLoading={exporting === 'xlsx'}
                  disabled={exporting !== null}
                  onClick={() => void handleExport('xlsx')}
                >
                  Excel
                </Button>
                <Button
                  variant="outline"
                  leftIcon={<Download className="size-4" />}
                  isLoading={exporting === 'pdf'}
                  disabled={exporting !== null}
                  onClick={() => void handleExport('pdf')}
                >
                  PDF
                </Button>
              </>
            )}
            {canCreate && (
              <Button leftIcon={<Plus className="size-4" />} onClick={() => setShowForm(true)}>
                Tambah Tendik
              </Button>
            )}
          </div>
        }
      />

      {exportError && (
        <Alert variant="danger" onDismiss={() => setExportError(null)}>
          {exportError}
        </Alert>
      )}

      {summary.error && <Alert variant="danger">{summary.error}</Alert>}
      {summary.data && <SummaryCards summary={summary.data} />}

      <Card noPadding id="daftar-tendik" className="scroll-mt-4">
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Nama, NIP, atau email..."
            value={searchInput}
            onChange={(event) => setSearchInput(event.target.value)}
            onKeyDown={(event) => {
              if (event.key === 'Enter') applyFilter(filterInput)
            }}
          />
          <Select
            label="Kategori"
            value={filterInput.staff_category ?? ''}
            onChange={(event) => setFilterValue('staff_category', event.target.value)}
            options={[{ value: '', label: 'Semua kategori' }, ...(options?.enums.staff_category ?? [])]}
          />
          <Select
            label="Unit Kerja"
            value={filterInput.work_unit_id ?? ''}
            onChange={(event) => setFilterValue('work_unit_id', event.target.value)}
            options={[{ value: '', label: 'Semua unit' }, ...(options?.work_units ?? [])]}
          />
          <Select
            label="Status Kepegawaian"
            value={filterInput.employment_status ?? ''}
            onChange={(event) => setFilterValue('employment_status', event.target.value)}
            options={[{ value: '', label: 'Semua' }, ...(options?.enums.employment_status ?? [])]}
          />
          <Select
            label="Status"
            value={filterInput.is_active ?? ''}
            onChange={(event) => setFilterValue('is_active', event.target.value)}
            options={ACTIVE_OPTIONS}
          />
          <Button variant="outline" onClick={() => applyFilter(filterInput)}>
            Terapkan Filter
          </Button>
        </div>

        {list.error ? (
          <Alert variant="danger" className="m-4">
            {list.error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={list.data}
            rowKey={(row) => row.id}
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada tenaga kependidikan yang sesuai.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="tenaga kependidikan" />
      </Card>

      {summary.data && (
        <>
          <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <BreakdownCard
              title="Komposisi per Kategori"
              description="Tendik aktif. Klik baris untuk menyaring daftar."
              rows={summary.data.by_category.map((row) => ({ key: row.value, label: row.label, total: row.total }))}
              onSelect={(key) => focusOn('staff_category', key)}
            />
            <BreakdownCard
              title="Sebaran per Unit Kerja"
              description="Tendik aktif per unit kerja."
              rows={summary.data.by_work_unit.map((row) => ({
                key: row.work_unit_id ?? '',
                label: row.name,
                total: row.total,
              }))}
              onSelect={(key) => key !== '' && focusOn('work_unit_id', key)}
            />
          </div>
          <RatioCard summary={summary.data} />
        </>
      )}

      {showForm && (
        <EducationStaffFormModal
          onClose={() => setShowForm(false)}
          onSaved={(created) => {
            setShowForm(false)
            navigate(tendikPath(created.id))
          }}
        />
      )}
    </div>
  )
}

function SummaryCards({ summary }: { summary: EducationStaffSummary }) {
  const { totals } = summary

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard label="Total Tendik" value={formatNumber(totals.total)} icon={UsersRound} caption="termasuk nonaktif" />
      <StatCard label="Tendik Aktif" value={formatNumber(totals.active)} icon={UserCheck} />
      <StatCard label="Nonaktif" value={formatNumber(totals.inactive)} icon={UserX} caption="resign, pensiun, dll." />
      <StatCard
        label="Belum Berkategori"
        value={formatNumber(totals.uncategorized)}
        icon={Tags}
        caption={totals.uncategorized > 0 ? 'perlu dilengkapi' : 'semua sudah lengkap'}
      />
    </div>
  )
}

function BreakdownCard({
  title,
  description,
  rows,
  onSelect,
}: {
  title: string
  description: string
  rows: Array<{ key: string; label: string; total: number }>
  onSelect: (key: string) => void
}) {
  const max = Math.max(1, ...rows.map((row) => row.total))

  return (
    <Card title={title} description={description}>
      {rows.length === 0 ? (
        <p className="text-sm text-ink-tertiary">Belum ada data.</p>
      ) : (
        <ul className="flex max-h-80 flex-col gap-1 overflow-y-auto">
          {rows.map((row) => (
            <li key={row.key || row.label}>
              <button
                type="button"
                onClick={() => onSelect(row.key)}
                disabled={row.total === 0 || row.key === ''}
                className="group flex w-full flex-col gap-1 rounded-lg px-2 py-1.5 text-left text-sm hover:bg-surface-hover disabled:cursor-default disabled:hover:bg-transparent"
              >
                <span className="flex items-center justify-between gap-3">
                  <span className="truncate text-ink-primary">{row.label}</span>
                  <span className="font-medium tabular-nums text-ink-secondary">{formatNumber(row.total)}</span>
                </span>
                <span className="h-1.5 w-full overflow-hidden rounded-full bg-surface-hover">
                  <span className="block h-full rounded-full bg-primary" style={{ width: `${(row.total / max) * 100}%` }} />
                </span>
              </button>
            </li>
          ))}
        </ul>
      )}
    </Card>
  )
}

function RatioCard({ summary }: { summary: EducationStaffSummary }) {
  const { overall, by_faculty: faculties } = summary.ratios

  return (
    <Card
      title="Rasio Tendik per Fakultas"
      description="Perbandingan tendik aktif terhadap dosen aktif dan mahasiswa aktif — bahan laporan akreditasi."
      noPadding
    >
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead className="border-b border-border text-left text-xs uppercase tracking-wide text-ink-tertiary">
            <tr>
              <th className="px-4 py-2 font-medium">Fakultas</th>
              <th className="px-4 py-2 text-right font-medium">Tendik</th>
              <th className="px-4 py-2 text-right font-medium">Dosen</th>
              <th className="px-4 py-2 text-right font-medium">Mahasiswa</th>
              <th className="px-4 py-2 text-right font-medium">Tendik : Mahasiswa</th>
              <th className="px-4 py-2 text-right font-medium">Tendik : Dosen</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {faculties.map((row) => (
              <tr key={row.faculty_id}>
                <td className="px-4 py-2 text-ink-primary">{row.faculty_name}</td>
                <td className="px-4 py-2 text-right tabular-nums">{formatNumber(row.staff)}</td>
                <td className="px-4 py-2 text-right tabular-nums">{formatNumber(row.lecturers)}</td>
                <td className="px-4 py-2 text-right tabular-nums">{formatNumber(row.students)}</td>
                <td className="px-4 py-2 text-right tabular-nums">{formatRatio(row.students_per_staff)}</td>
                <td className="px-4 py-2 text-right tabular-nums">{formatRatio(row.lecturers_per_staff)}</td>
              </tr>
            ))}
          </tbody>
          <tfoot className="border-t border-border-strong font-medium text-ink-primary">
            <tr>
              <td className="px-4 py-2">Seluruh universitas</td>
              <td className="px-4 py-2 text-right tabular-nums">{formatNumber(overall.staff)}</td>
              <td className="px-4 py-2 text-right tabular-nums">{formatNumber(overall.lecturers)}</td>
              <td className="px-4 py-2 text-right tabular-nums">{formatNumber(overall.students)}</td>
              <td className="px-4 py-2 text-right tabular-nums">{formatRatio(overall.students_per_staff)}</td>
              <td className="px-4 py-2 text-right tabular-nums">{formatRatio(overall.lecturers_per_staff)}</td>
            </tr>
          </tfoot>
        </table>
      </div>
      {overall.staff_outside_faculty > 0 && (
        <p className="border-t border-border px-4 py-3 text-xs text-ink-tertiary">
          {formatNumber(overall.staff_outside_faculty)} tendik bertugas di unit non-fakultas (rektorat, biro, unit pusat) —
          hanya dihitung pada rasio tingkat universitas.
        </p>
      )}
    </Card>
  )
}
