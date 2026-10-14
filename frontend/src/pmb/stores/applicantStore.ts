import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { ApplicantAccount } from '@/pmb/api/types'

interface ApplicantSessionState {
  token: string | null
  expiresAt: string | null
  account: ApplicantAccount | null
  setSession: (token: string, expiresAt: string | null, account: ApplicantAccount) => void
  setAccount: (account: ApplicantAccount) => void
  clear: () => void
}

/**
 * Sesi pendaftar PMB — terpisah sepenuhnya dari sesi staf (authStore):
 * guard, token, dan kunci penyimpanan berbeda (KD-01).
 */
export const useApplicantStore = create<ApplicantSessionState>()(
  persist(
    (set) => ({
      token: null,
      expiresAt: null,
      account: null,
      setSession: (token, expiresAt, account) => set({ token, expiresAt, account }),
      setAccount: (account) => set({ account }),
      clear: () => set({ token: null, expiresAt: null, account: null }),
    }),
    { name: 'civitasone-pmb-applicant' },
  ),
)
