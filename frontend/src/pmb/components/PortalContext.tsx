import { createContext, useContext } from 'react'
import type { MeResponse } from '@/pmb/api/applicant'

export const PortalContext = createContext<{ me: MeResponse | null; reloadMe: () => void }>({ me: null, reloadMe: () => undefined })

export function usePortal() {
  return useContext(PortalContext)
}
