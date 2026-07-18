import type { ReactNode } from 'react'
import { cn } from '@/utils/cn'
import { EmptyState } from '@/components/ui/EmptyState'

export interface DataTableColumn<T> {
  header: string
  cell: (row: T) => ReactNode
  className?: string
}

export interface DataTableProps<T> {
  columns: DataTableColumn<T>[]
  data: T[]
  rowKey: (row: T) => string
  emptyMessage?: string
}

export function DataTable<T>({ columns, data, rowKey, emptyMessage = 'Tidak ada data.' }: DataTableProps<T>) {
  if (data.length === 0) {
    return <EmptyState title={emptyMessage} />
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-left text-sm">
        <thead>
          <tr className="border-b border-border text-xs uppercase tracking-wide text-ink-tertiary">
            {columns.map((column) => (
              <th key={column.header} className={cn('whitespace-nowrap px-4 py-2.5 font-medium', column.className)}>
                {column.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {data.map((row) => (
            <tr key={rowKey(row)} className="transition-colors hover:bg-surface-hover">
              {columns.map((column) => (
                <td key={column.header} className={cn('whitespace-nowrap px-4 py-3 text-ink-primary', column.className)}>
                  {column.cell(row)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
