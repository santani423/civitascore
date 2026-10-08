import { useFetch } from '@/hooks/useFetch'
import { hrOptionsService } from '@/services/hrService'

/** Pilihan dropdown Modul SDM (enum berlabel + master unit kerja/jabatan/fakultas). `null` selama memuat. */
export function useHrOptions() {
  const { data } = useFetch(hrOptionsService.get)
  return data
}
