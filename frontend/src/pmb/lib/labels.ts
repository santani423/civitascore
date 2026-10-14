import type { BadgeVariant } from '@/components/ui/Badge'

/** Label Bahasa Indonesia untuk status mesin keadaan PMB (docs/pmb/09). */
const LABELS: Record<string, string> = {
  draft: 'Draf', submitted: 'Terkirim', revision_requested: 'Perlu perbaikan', locked: 'Terkunci', withdrawn: 'Ditarik', void: 'Batal',
  not_started: 'Belum diperiksa', in_review: 'Diperiksa', verified: 'Terverifikasi', rejected: 'Ditolak',
  not_eligible: 'Belum memenuhi syarat', eligible: 'Siap seleksi', scheduled: 'Terjadwal', assessed: 'Dinilai', absent: 'Tidak hadir', disqualified: 'Didiskualifikasi', ranked: 'Diperingkat',
  not_required: 'Tidak perlu', unpaid: 'Belum bayar', pending: 'Menunggu', paid: 'Lunas', expired: 'Kedaluwarsa', partially_paid: 'Sebagian', cancelled: 'Dibatalkan', refund_pending: 'Refund diproses', refunded: 'Dikembalikan',
  created: 'Dibuat', success: 'Berhasil', failed: 'Gagal',
  missing: 'Belum diunggah', uploaded: 'Diunggah', approved: 'Disetujui',
  published: 'Terbit', closed: 'Ditutup', archived: 'Diarsipkan', open: 'Dibuka', announced: 'Diumumkan', completed: 'Selesai', retired: 'Pensiun',
  computed: 'Dihitung', under_review: 'Ditinjau', pending_approval: 'Menunggu pengesahan', superseded: 'Digantikan', publishing: 'Menerbitkan',
  accepted: 'Diterima', waitlisted: 'Cadangan', unpublished: 'Belum terbit',
  awaiting_confirmation: 'Menunggu konfirmasi', confirmed: 'Dikonfirmasi', in_progress: 'Berlangsung', enrolled: 'Resmi mahasiswa', declined: 'Menolak tawaran',
  processing: 'Diproses', succeeded: 'Berhasil', manual_intervention: 'Perlu tindakan',
  registered: 'Terdaftar', present: 'Hadir', excused: 'Izin',
  pass: 'Lulus', waitlist: 'Cadangan', fail: 'Tidak lulus',
  sending: 'Mengirim', sent: 'Terkirim', ready: 'Siap', clean: 'Aman', scanning: 'Dipindai',
  active: 'Aktif', unverified: 'Belum verifikasi', suspended: 'Dibekukan', deactivated: 'Nonaktif',
  pending_applicant: 'Menunggu pendaftar', confirmed_duplicate: 'Duplikat', not_duplicate: 'Bukan duplikat', merged: 'Digabung',
  passed: 'Lolos', not_applicable: 'Tidak berlaku', dispatched: 'Terkirim', applied: 'Diterapkan', returned: 'Dikembalikan',
  registration_fee: 'Biaya pendaftaran', initial_tuition: 'Biaya awal',
}

const VARIANTS: Record<string, BadgeVariant> = {
  verified: 'success', paid: 'success', success: 'success', approved: 'success', published: 'success', accepted: 'success', enrolled: 'success', succeeded: 'success', present: 'success', pass: 'success', passed: 'success', sent: 'success', ready: 'success', clean: 'success', active: 'success', completed: 'success', applied: 'success',
  revision_requested: 'warning', pending: 'warning', partially_paid: 'warning', in_review: 'warning', waitlisted: 'warning', waitlist: 'warning', pending_approval: 'warning', awaiting_confirmation: 'warning', under_review: 'warning', manual_intervention: 'warning', refund_pending: 'warning', unverified: 'warning', processing: 'info', in_progress: 'info', scheduled: 'info', submitted: 'info', computed: 'info', confirmed: 'info', open: 'info',
  rejected: 'danger', failed: 'danger', expired: 'danger', cancelled: 'danger', fail: 'danger', absent: 'danger', disqualified: 'danger', declined: 'danger', suspended: 'danger', withdrawn: 'neutral', void: 'neutral',
}

export function statusLabel(status: string | null | undefined): string {
  if (!status) return '—'

  return LABELS[status] ?? status.replace(/_/g, ' ')
}

export function statusVariant(status: string | null | undefined): BadgeVariant {
  return (status && VARIANTS[status]) || 'neutral'
}

export const DECISION_LABELS: Record<string, string> = { accepted: 'Diterima', waitlisted: 'Cadangan', rejected: 'Tidak diterima', withdrawn: 'Dibatalkan' }

export const STAGE_STEPS = ['draft', 'awaiting_payment', 'submitted', 'verification', 'selection', 'result', 're_registration', 'enrolled']
