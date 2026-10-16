/** Pembacaan CSV mutasi rekening di peramban (impor rekonsiliasi). */
/** Nominal mutasi format Indonesia (1.400.000,50) maupun internasional — sama dengan ReconciliationController::amount(). */
export function parseAmount(cell: string): string {
  let raw = cell.replace(/[^0-9.,-]/g, '')

  if (raw.includes(',') && raw.includes('.')) {
    raw = raw.lastIndexOf(',') > raw.lastIndexOf('.') ? raw.replace(/\./g, '').replace(',', '.') : raw.replace(/,/g, '')
  } else if (raw.includes(',')) {
    raw = /,\d{1,2}$/.test(raw) ? raw.replace(',', '.') : raw.replace(/,/g, '')
  } else if ((raw.match(/\./g) ?? []).length > 1 || /\.\d{3}$/.test(raw)) {
    raw = raw.replace(/\./g, '')
  }

  return raw === '' ? '0' : raw
}

/** Tanggal mutasi: YYYY-MM-DD, atau DD/MM/YYYY & DD-MM-YYYY (format bank Indonesia). */
export function parseDate(cell: string): string {
  const value = cell.trim()
  const dmy = /^(\d{1,2})[/-](\d{1,2})[/-](\d{4})/.exec(value)

  return dmy ? `${dmy[3]}-${dmy[2].padStart(2, '0')}-${dmy[1].padStart(2, '0')}` : value.slice(0, 10)
}

/** CSV kolom: tanggal, keterangan, nominal, referensi (pemisah koma atau titik koma; baris judul diabaikan). */
export function parseStatementCsv(text: string): Array<{ date: string; description: string; amount: string; reference: string | null }> {
  return text.split(/\r\n|\n|\r/).map((line) => line.trim()).filter(Boolean).flatMap((line, index) => {
    const cells = line.split(line.includes(';') ? ';' : ',').map((c) => c.replace(/^"|"$/g, '').trim())

    if ((index === 0 && !/\d/.test(cells[0] ?? '')) || cells.length < 3) return []

    return [{ date: parseDate(cells[0]), description: cells[1] ?? '', amount: parseAmount(cells[2]), reference: cells[3] || null }]
  })
}
