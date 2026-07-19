import type { LucideIcon } from 'lucide-react'

export interface NavChildItem {
  label: string
  path: string
  /** Permission slug(s) needed to see this item — OR-match, same semantics as usePermission(). Omit if every authenticated user should see it. */
  permission?: string | string[]
}

export interface NavItem {
  label: string
  path: string
  icon: LucideIcon
  children?: NavChildItem[]
  /** Permission slug(s) needed to see this item — OR-match, same semantics as usePermission(). Omit if every authenticated user should see it. Ignored when `children` is set — visibility of a parent with children is derived from its children instead. */
  permission?: string | string[]
}
