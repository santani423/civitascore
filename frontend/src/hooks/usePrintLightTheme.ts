import { useEffect } from 'react'

/**
 * Paksa tema terang selama dialog cetak browser terbuka (Ctrl+P / tombol
 * Cetak) — token warna dan varian `dark:` mengikuti kelas `dark` di <html>,
 * dan hasil cetak tema gelap (teks terang di kertas putih) tidak terbaca.
 * Kelas dikembalikan sesudah cetak; useThemeSync tetap pemilik state tema.
 */
export function usePrintLightTheme(): void {
  useEffect(() => {
    const root = document.documentElement
    let wasDark = false

    const handleBeforePrint = () => {
      wasDark = root.classList.contains('dark')
      root.classList.remove('dark')
    }
    const handleAfterPrint = () => {
      if (wasDark) root.classList.add('dark')
    }

    window.addEventListener('beforeprint', handleBeforePrint)
    window.addEventListener('afterprint', handleAfterPrint)

    return () => {
      window.removeEventListener('beforeprint', handleBeforePrint)
      window.removeEventListener('afterprint', handleAfterPrint)
    }
  }, [])
}
