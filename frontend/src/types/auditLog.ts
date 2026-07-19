export type AuditAction = 'created' | 'updated' | 'deleted' | 'restored' | 'exported' | 'imported'

export interface AuditLogEntry {
  id: string
  user_id: string | null
  user_name: string | null
  auditable_type: string
  auditable_id: string
  action: AuditAction
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  reason: string | null
  ip_address: string | null
  user_agent: string | null
  created_at: string
}
