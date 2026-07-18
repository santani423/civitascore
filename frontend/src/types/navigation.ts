import type { LucideIcon } from 'lucide-react'

export interface NavChildItem {
  label: string
  path: string
}

export interface NavItem {
  label: string
  path: string
  icon: LucideIcon
  children?: NavChildItem[]
}
