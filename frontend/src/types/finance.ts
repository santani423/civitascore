export type InvoiceStatus = 'unpaid' | 'partial' | 'paid'

export interface Payment {
  id: string
  invoice_id: string
  invoice_period: string | null
  student_name: string | null
  amount: string
  paid_at: string
  method: string
  created_at: string
}

export interface Invoice {
  id: string
  student_id: string
  student_name: string | null
  student_nim: string | null
  period: string
  amount: string
  paid_amount: string
  status: InvoiceStatus
  due_date: string
  payments: Payment[]
  created_at: string
}
