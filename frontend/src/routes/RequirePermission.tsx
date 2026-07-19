import type { ReactNode } from 'react'
import { usePermission } from '@/hooks/usePermission'
import { AccessDeniedPage } from '@/pages/errors/AccessDeniedPage'

export interface RequirePermissionProps {
  /** Omit to allow any authenticated user through unconditionally. */
  permission?: string | string[]
  children: ReactNode
}

/** Blocks direct URL access to a page the user lacks permission for — same slug(s) the sidebar already uses to hide the menu (see routes/routePermissions.ts). */
export function RequirePermission({ permission, children }: RequirePermissionProps) {
  const allowed = usePermission(permission ?? [])

  if (permission && !allowed) {
    return <AccessDeniedPage />
  }

  return children
}
