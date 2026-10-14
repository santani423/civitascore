import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'

export interface ReasonDialogProps {
  open: boolean
  title: string
  description?: string
  confirmLabel?: string
  reasonLabel?: string
  reasonName?: string
  extraFields?: FieldDef[]
  onClose: () => void
  onConfirm: (values: Record<string, unknown>) => Promise<unknown>
}

/** Konfirmasi aksi sensitif dengan alasan wajib (dicatat di audit, 03 §A.4). */
export function ReasonDialog({ reasonLabel = 'Alasan', reasonName = 'reason', extraFields = [], confirmLabel = 'Konfirmasi', ...props }: ReasonDialogProps) {
  return (
    <FormDialog
      {...props}
      submitLabel={confirmLabel}
      fields={[...extraFields, { name: reasonName, label: reasonLabel, type: 'textarea', required: true }]}
      onSubmit={props.onConfirm}
    />
  )
}
