/** Bentuk data API PMB (app/Modules/Admission/Controllers). Nominal uang = string desimal "150000.00". */
export interface ApplicantAccount {
  id: string
  name: string
  email: string
  phone: string | null
  status: string
  email_verified: boolean
  phone_verified: boolean
  marketing_consent?: boolean
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number; per_page: number; total: number; last_page: number }
}

export interface OpenWavePath {
  wave_path_id: string
  wave: { id: string; code: string; name: string; is_ods: boolean }
  period: { id: string; code: string; name: string }
  path: { id: string; code: string; name: string; category: string; description: string | null }
  fee: string
  payment_timing: 'before_submit' | 'after_submit'
  closes_at: string
  max_choices: number
  public_notes: string | null
  eligible: boolean
  ineligible_reason: string | null
}

export interface ApplicationSummary {
  id: string
  registration_number: string | null
  period: { id: string; code: string; name: string } | null
  wave: { id: string; code: string; name: string; registration_closes_at: string | null; announcement_at: string | null; form_lock_at: string | null } | null
  path: { id: string; code: string; name: string; category: string } | null
  form_status: string
  verification_status: string
  selection_status: string
  registration_payment_status: string
  payment_timing: string | null
  registration_fee: string | null
  registration_invoice_id: string | null
  current_stage: string
  current_stage_label: string
  first_choice: string | null
  submitted_at: string | null
  revision_due_at: string | null
  has_published_result: boolean
  re_registration_status: string | null
  created_at: string | null
}

export interface CompletenessStep {
  key: string
  complete: boolean
  missing: Array<{ field?: string; message?: string; label?: string; document_type?: string; name?: string; status?: string; semester?: number }>
}

export interface FormFieldSchema {
  id: string
  section: string
  key: string
  label: string
  help_text: string | null
  placeholder: string | null
  type: string
  options: Array<{ value: string; label: string }> | null
  is_required: boolean
  validation: Record<string, unknown> | null
  visibility_rule: Record<string, unknown> | null
  is_sensitive: boolean
}

export interface ApplicationDetail extends ApplicationSummary {
  config: Record<string, unknown>
  schema: { template: { id: string; code: string; version: number; name: string }; core: Record<string, Record<string, string>>; fields: FormFieldSchema[] }
  editable_fields: string[] | null
  revision_targets: { fields?: string[]; documents?: string[] } | null
  primary_education_id: string | null
  supporting: Record<string, unknown> & { kip_number_filled: boolean }
  choices: Array<{ priority: number; study_program_id: string; study_program: string; study_system_id: string; study_system: string }>
  answers: Record<string, unknown>
  declarations: Array<{ id: string; title: string; body: string; version: number; slug: string }>
  offered_choices: Array<{ study_program_id: string; study_program: string; degree_level: string; faculty: string | null; study_system_id: string; study_system: string }>
  completeness: { steps: CompletenessStep[]; can_submit: boolean }
  lock_version: number
}

export interface ApplicantDocument {
  id: string
  document_type: { id: string; code: string; name: string; description: string | null; allowed_mime_types: string[]; max_size_kb: number; allows_multiple: boolean } | null
  stage: string
  slot: number
  is_mandatory: boolean
  status: string
  instructions: string | null
  locked: boolean
  current_version: { version_no: number; scan_status: string | null; original_name: string | null; uploaded_at: string | null } | null
  versions_count: number
  last_review: { decision: string; reason_code: string | null; note: string | null } | null
}

export interface Transaction {
  id: string
  status: string
  channel: string
  provider: string
  amount: string
  unique_code: number | null
  va_number: string | null
  qr_string: string | null
  redirect_url: string | null
  expires_at: string | null
  paid_at: string | null
  receipt_number: string | null
  instructions: Record<string, unknown> | Array<unknown> | null
  failure_reason: string | null
  manual_proof: { status: string; reason_code: string | null } | null
  created_at: string | null
  provider_reference?: string | null
}

export interface Invoice {
  id: string
  invoice_number: string
  type: string
  status: string
  amount_gross: string
  amount_discount: string
  amount_net: string
  amount_paid: string
  outstanding: string
  minimum_for_enrollment: string | null
  due_at: string | null
  paid_at: string | null
  needs_resolution: boolean
  items: Array<{ description: string; code: string; item_type: string; amount: string; installment_seq: number | null; due_at: string | null }>
  transactions: Transaction[]
}

export interface PaymentChannel {
  code: string
  label: string
  provider: string
  admin_fee: string
  fee_borne_by: string
  bank_accounts?: Array<{ bank: string; account_number: string; account_name: string }>
}
