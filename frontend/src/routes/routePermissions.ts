import { NAV_ITEMS, PLATFORM_NAV_ITEMS } from '@/constants/nav'
import { ROUTES } from '@/constants/routes'
import type { NavItem } from '@/types/navigation'

/**
 * `path -> permission` derived straight from the nav items that already
 * carry the correct permission per route (see constants/nav.ts) — one
 * source of truth, so the route guard can never drift from what the
 * sidebar already shows/hides.
 */
function collectNavPermissions(items: NavItem[], map: Map<string, string | string[]>): void {
  for (const item of items) {
    if (item.children?.length) {
      for (const child of item.children) {
        if (child.permission) map.set(child.path, child.permission)
      }
    } else if (item.permission) {
      map.set(item.path, item.permission)
    }
  }
}

const routePermissions = new Map<string, string | string[]>()
collectNavPermissions(NAV_ITEMS, routePermissions)
collectNavPermissions(PLATFORM_NAV_ITEMS, routePermissions)

/**
 * Detail routes aren't sidebar items, so they can't be derived above —
 * each inherits the same read permission as its parent List route.
 *
 * `/persetujuan` and `/persetujuan/:id` are deliberately absent: backend's
 * ApprovalRequestPolicy already allows a requester (or assigned approver)
 * to view their own request without approval_requests.read — hard-gating
 * the route here would block access the API itself grants. Data isolation
 * for that page is handled by the API's own scoping, not a route guard.
 */
const DETAIL_ROUTE_PERMISSIONS: Record<string, string | string[]> = {
  [ROUTES.mahasiswaDetail]: 'students.read',
  [ROUTES.dosenDetail]: 'lecturers.read',
  [ROUTES.pegawaiDetail]: 'employees.read',
  [ROUTES.akademik.programStudiDetail]: 'study_programs.read',
  [ROUTES.akademik.kelasJadwalDetail]: 'classes.read',
  [ROUTES.keuangan.tagihanDetail]: 'invoices.read',
}

for (const [path, permission] of Object.entries(DETAIL_ROUTE_PERMISSIONS)) {
  routePermissions.set(path, permission)
}

// nav.ts sets `permission: 'approval_requests.read'` on the "Pengajuan"
// child, but that governs SIDEBAR visibility (declutter the menu for users
// without tenant-wide read access) — it is not a hard access boundary the
// way it is for every other item. ApprovalRequestPolicy already allows a
// requester or assigned approver to view their own request without that
// permission, so the route-derivation above would incorrectly lock them
// out of a page the API happily serves them. Force it open here; the API's
// own scoping is what actually protects the data on this one page.
routePermissions.delete(ROUTES.persetujuan)

export function getRoutePermission(path: string): string | string[] | undefined {
  return routePermissions.get(path)
}
