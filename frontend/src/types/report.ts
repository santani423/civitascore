export type ReportType = 'mahasiswa' | 'akademik' | 'keuangan' | 'sdm'

export interface ReportTypeOption {
  type: ReportType
  label: string
}

export interface ReportColumn {
  key: string
  label: string
}

export interface ReportResult {
  type: ReportType
  label: string
  columns: ReportColumn[]
  summary: Record<string, string | number | null>
  rows: Array<Record<string, string | number | null>>
  total_rows: number
  truncated: boolean
}
