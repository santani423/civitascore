/** Bentuk data API KEU (app/Modules/Finance/Support/Presenter.php). Nominal = string desimal "150000.00". */
export interface Option {
  value: string
  label: string
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; per_page: number; total: number; last_page: number }
}

export interface StudentRef {
  id: string
  nim: string
  name: string
  study_program: string | null
  admission_year: number | null
  status: string | null
  status_label: string | null
}

export interface Installment {
  id: string
  invoice_id: string
  seq: number
  schedule_version: number
  amount: string
  paid_amount: string
  remaining: string
  due_date: string
  status: string
  status_label: string
  is_overdue: boolean
}

export interface Allocation {
  id: string
  invoice_id: string
  payment_transaction_id: string | null
  invoice_installment_id: string | null
  amount: string
  allocation_type: string
  status: string
  paid_at: string | null
  method: string | null
  reference: string | null
  reversal_reason: string | null
}

export interface Adjustment {
  id: string
  adjustment_number: string
  invoice_id: string
  invoice_number: string | null
  student: StudentRef | null
  type: string
  type_label: string
  amount: string
  reason_code: string
  reason_note: string
  status: string
  status_label: string
  requested_by: string | null
  decided_by: string | null
  decision_note: string | null
  created_at: string | null
  applied_at: string | null
}

export interface Invoice {
  id: string
  invoice_number: string | null
  student: StudentRef | null
  student_id: string
  academic_term_id: string | null
  period: string
  bill_group: string
  bill_group_label: string
  gross_amount: string
  discount_amount: string
  adjustment_amount: string
  amount: string
  paid_amount: string
  outstanding: string
  status: string
  status_label: string
  is_overdue: boolean
  overdue_since: string | null
  due_date: string
  issued_at: string | null
  settled_at: string | null
  parent_invoice_id: string | null
  source_type: string | null
  created_at: string | null
  installments?: Installment[]
}

export interface InvoiceDetail extends Invoice {
  items: Array<{ id: string; line_type: string; line_type_label: string; description: string; quantity: string; unit_amount: string; amount: string }>
  installments: Installment[]
  payments: Allocation[]
  adjustments: Adjustment[]
  penalty_invoices: Invoice[]
  cancel_reason: string | null
  rate_snapshot: Record<string, unknown> | null
  notes: string | null
}

export interface Proof {
  id: string
  status: string
  status_label: string
  sender_bank: string | null
  sender_name: string | null
  transfer_date: string | null
  transferred_amount: string
  reason_code: string | null
  note: string | null
  duplicate_warning: boolean
  file_upload_id: string | null
}

export interface Transaction {
  id: string
  transaction_number: string
  receipt_number: string | null
  student: StudentRef | null
  payer_name: string | null
  channel: { id: string; code: string; name: string } | null
  provider: string
  method: string
  method_label: string
  target_type: string
  target_id: string | null
  amount: string
  channel_fee: string
  status: string
  status_label: string
  va_number: string | null
  qr_string: string | null
  redirect_url: string | null
  unique_code: number | null
  instructions: { bank_accounts?: BankAccount[]; steps?: Array<{ title: string; steps: string[] }>; channel_instructions?: string | null } | null
  expires_at: string | null
  paid_at: string | null
  reconciliation_status: string
  reconciliation_label: string
  refunded_amount: string
  failure_reason: string | null
  needs_resolution: boolean
  proof: Proof | null
  allocations: Allocation[] | null
  recorded_by: string | null
  verified_by: string | null
  reversal_reason: string | null
  created_at: string | null
}

export interface ProofRow extends Transaction {
  proof_id: string
  reviewed_by: string | null
  reviewed_at: string | null
  submitted_at: string | null
}

export interface BankAccount {
  bank: string
  account_number: string
  account_name: string
}

export interface Refund {
  id: string
  refund_number: string
  student: StudentRef | null
  amount: string
  reason_code: string
  reason_note: string
  beneficiary_bank: string
  beneficiary_account: string
  beneficiary_name: string
  beneficiary_relation: string
  status: string
  status_label: string
  requested_by: string | null
  decision_note: string | null
  execution_reference: string | null
  failure_reason: string | null
  created_at: string | null
  executed_at: string | null
}

export interface Award {
  id: string
  student: StudentRef | null
  policy: { id: string; code: string; name: string; kind: string; kind_label: string } | null
  calc_type: string
  calc_type_label: string
  value: string
  max_amount: string | null
  fee_component_ids: string[] | null
  start_term: string | null
  end_term: string | null
  start_term_id: string | null
  end_term_id: string | null
  status: string
  status_label: string
  basis: string
  decision_note: string | null
  revoke_reason: string | null
  requested_by: string | null
  created_at: string | null
}

export interface Exemption {
  id: string
  academic_block_id: string
  service: string
  valid_from: string | null
  valid_until: string | null
  reason: string
  promise_amount: string | null
  promise_date: string | null
  status: string
  status_label: string
  requested_by: string | null
  requested_by_name?: string | null
  granted_by_name?: string | null
  decision_note: string | null
  student: StudentRef | null
  created_at: string | null
}

export interface Block {
  id: string
  student: StudentRef | null
  source: string
  rule_code: string | null
  services: string[]
  reason: string
  shortfall: string | null
  status: string
  status_label: string
  effective_from: string | null
  revoked_at: string | null
  revoke_reason: string | null
  exemptions: Exemption[] | null
}

export interface Clearance {
  clear: boolean
  outstanding: string
  accruing_penalty: string
  pending_verification: number
}

export interface StatementEntry {
  date: string | null
  invoice_number: string | null
  type: string
  description: string | null
  amount: string
  balance: string
}

export interface Lookups {
  academic_terms: Array<{ id: string; label: string; academic_year: string; is_current: boolean }>
  faculties: Array<{ id: string; code: string; name: string }>
  study_programs: Array<{ id: string; faculty_id: string; code: string; name: string }>
  fee_components: Array<{ id: string; code: string; name: string; category: string; is_active: boolean; discountable: boolean; installable: boolean }>
  ukt_groups: Array<{ id: string; code: string; name: string; level: number; is_active: boolean }>
  discount_policies: Array<{ id: string; code: string; name: string; kind: string; calc_type: string; value: string; is_active: boolean }>
  installment_plans: Array<{ id: string; code: string; name: string; bill_groups: string[]; terms: Array<{ percent: number }>; is_active: boolean; auto_approve: boolean }>
  payment_channels: Array<{ id: string; code: string; name: string; category: string; method: string; status: string }>
  enums: Record<
    | 'invoice_status' | 'bill_group' | 'fee_category' | 'calc_basis' | 'aid_kind' | 'aid_calc_type' | 'adjustment_type' | 'payment_method' | 'transaction_status'
    | 'penalty_calc_type' | 'penalty_period_unit' | 'clearance_requirement' | 'services' | 'proof_reject_reasons' | 'refund_reasons' | 'reconciliation_resolutions' | 'report_types',
    Option[]
  >
}

export interface Report {
  type: string
  title: string
  columns: Array<{ key: string; label: string }>
  rows: Array<Record<string, unknown>>
  summary: Record<string, unknown>
  total_rows: number
}
