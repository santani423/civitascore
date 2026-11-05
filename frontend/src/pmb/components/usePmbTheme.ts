import { useLayoutEffect } from 'react'
import '@/landing/styles/landing.css'
import '@/pmb/styles/pmb.css'

const CLASSES = ['lp-theme', 'pmb-theme']

/**
 * Memasang tema landing pada <body> selama layout PMB tampil. Di <body>, bukan
 * di root layout, agar dialog yang di-portal ke document.body (Modal) ikut
 * bertema; dilepas lagi saat pindah ke halaman staf.
 */
export function usePmbTheme(): void {
  useLayoutEffect(() => {
    document.body.classList.add(...CLASSES)

    return () => document.body.classList.remove(...CLASSES)
  }, [])
}
