import { Badge } from '@/components/ui/Badge'
import { statusLabel, statusVariant } from '@/pmb/lib/labels'

export function StatusBadge({ status, label }: { status: string | null | undefined; label?: string }) {
  return <Badge variant={statusVariant(status)}>{label ?? statusLabel(status)}</Badge>
}
