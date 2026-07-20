import { useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { bookService } from '@/services/libraryService'
import type { BookLoanStatus } from '@/types/library'
import { ROUTES } from '@/constants/routes'
import { formatDateRange } from '@/utils/formatters'

const STATUS_LABEL: Record<BookLoanStatus, string> = {
  dipinjam: 'Dipinjam',
  dikembalikan: 'Dikembalikan',
  terlambat: 'Terlambat',
}

const STATUS_VARIANT: Record<BookLoanStatus, BadgeVariant> = {
  dipinjam: 'info',
  dikembalikan: 'success',
  terlambat: 'danger',
}

function DetailField({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value ?? '-'}</p>
    </div>
  )
}

export function BookDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getBook = useCallback(() => bookService.show(id ?? ''), [id])
  const { data: book, isLoading, error } = useFetch(getBook)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Buku"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Perpustakaan', path: ROUTES.perpustakaan },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.perpustakaan)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {book && (
        <>
          <Card title={book.title} description={book.author}>
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <div>
                <p className="text-ink-tertiary">Status</p>
                <Badge variant={book.is_active ? 'success' : 'neutral'}>{book.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
              </div>
              <DetailField label="Penerbit" value={book.publisher} />
              <DetailField label="ISBN" value={book.isbn} />
              <DetailField label="Kategori" value={book.category} />
              <DetailField label="Stok" value={book.stock} />
            </div>
          </Card>

          <Card title="Riwayat Peminjaman" noPadding>
            {book.loans && book.loans.length > 0 ? (
              <ul className="divide-y divide-border">
                {book.loans.map((loan) => (
                  <li key={loan.id} className="flex items-center justify-between gap-3 p-3">
                    <div className="min-w-0">
                      <p className="truncate font-medium text-ink-primary">{loan.student_name ?? '-'}</p>
                      <p className="text-xs text-ink-tertiary">{loan.student_nim ?? '-'}</p>
                      <p className="text-xs text-ink-tertiary">{formatDateRange(loan.borrowed_at, loan.due_at)}</p>
                    </div>
                    <Badge variant={STATUS_VARIANT[loan.status]}>{STATUS_LABEL[loan.status]}</Badge>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="p-4 text-sm text-ink-tertiary">Belum ada riwayat peminjaman untuk buku ini.</p>
            )}
          </Card>
        </>
      )}
    </div>
  )
}
