import { createContext, useContext } from 'react'
import type { SiteInfo } from '@/pmb/api/public'

export const SiteContext = createContext<SiteInfo | null>(null)

export function useSite(): SiteInfo | null {
  return useContext(SiteContext)
}
