import { GradeBadge } from '@/components/portal/badges'
import type { GradeRow } from '@/types/studentPortal'
import { formatGpa } from '@/utils/portalFormat'

/**
 * Tabel nilai (KHS / daftar nilai per semester). Di layar sempit tampil
 * sebagai daftar kartu supaya tidak perlu geser horizontal.
 */
export function GradeTable({ rows, showScore = true }: { rows: GradeRow[]; showScore?: boolean }) {
  return (
    <>
      <ul className="flex flex-col divide-y divide-border sm:hidden">
        {rows.map((row) => (
          <li key={row.krs_item_id} className="flex items-center justify-between gap-3 py-3">
            <div className="min-w-0">
              <p className="truncate text-sm font-medium text-ink-primary">{row.course_name}</p>
              <p className="text-xs text-ink-secondary">
                {row.course_code} · {row.credits} SKS
                {row.is_graded && ` · Bobot ${formatGpa(row.weight)} · Mutu ${formatGpa(row.quality_points)}`}
              </p>
            </div>
            <GradeBadge grade={row.letter_grade} />
          </li>
        ))}
      </ul>

      <div className="hidden overflow-x-auto sm:block">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-border text-xs uppercase tracking-wide text-ink-tertiary">
              <th className="px-3 py-2 font-medium">Kode</th>
              <th className="px-3 py-2 font-medium">Mata Kuliah</th>
              <th className="px-3 py-2 text-center font-medium">SKS</th>
              {showScore && <th className="px-3 py-2 text-center font-medium">Nilai</th>}
              <th className="px-3 py-2 text-center font-medium">Huruf</th>
              <th className="px-3 py-2 text-center font-medium">Bobot</th>
              <th className="px-3 py-2 text-center font-medium">Mutu</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {rows.map((row) => (
              <tr key={row.krs_item_id} className="hover:bg-surface-hover">
                <td className="whitespace-nowrap px-3 py-2.5 text-ink-secondary">{row.course_code}</td>
                <td className="px-3 py-2.5 font-medium text-ink-primary">{row.course_name}</td>
                <td className="px-3 py-2.5 text-center">{row.credits}</td>
                {showScore && <td className="px-3 py-2.5 text-center">{row.score ?? '-'}</td>}
                <td className="px-3 py-2.5 text-center">
                  <GradeBadge grade={row.letter_grade} />
                </td>
                <td className="px-3 py-2.5 text-center">{row.weight !== null ? formatGpa(row.weight) : '-'}</td>
                <td className="px-3 py-2.5 text-center">{row.quality_points !== null ? formatGpa(row.quality_points) : '-'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </>
  )
}

/** Ringkasan angka (IPS/IPK/SKS) dalam kotak-kotak kecil. */
export function SummaryTiles({ items }: { items: { label: string; value: string }[] }) {
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
      {items.map((item) => (
        <div key={item.label} className="rounded-xl border border-border bg-surface p-3 text-center shadow-card">
          <p className="text-xl font-semibold text-ink-primary">{item.value}</p>
          <p className="text-xs text-ink-tertiary">{item.label}</p>
        </div>
      ))}
    </div>
  )
}
