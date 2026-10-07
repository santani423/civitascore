/**
 * Semantic tone → class map. Tailwind needs literal class names, so every
 * combination is spelled out here once and reused by Badge, StatCard, icons…
 */
export type UiTone = 'neutral' | 'brand' | 'royal' | 'sky' | 'teal' | 'gold' | 'ok' | 'warn' | 'bad'

export const tone: Record<UiTone, { text: string; soft: string; solid: string; ring: string; dot: string }> = {
  neutral: { text: 'text-one-muted', soft: 'bg-one-sunken text-one-muted', solid: 'bg-one-muted', ring: 'ring-one-line2/60', dot: 'bg-one-subtle' },
  brand: { text: 'text-one-brand', soft: 'bg-one-brand/10 text-one-brand', solid: 'bg-one-brand', ring: 'ring-one-brand/20', dot: 'bg-one-brand' },
  royal: { text: 'text-one-royal', soft: 'bg-one-royal/10 text-one-royal', solid: 'bg-one-royal', ring: 'ring-one-royal/20', dot: 'bg-one-royal' },
  sky: { text: 'text-one-sky', soft: 'bg-one-sky/10 text-one-sky', solid: 'bg-one-sky', ring: 'ring-one-sky/20', dot: 'bg-one-sky' },
  teal: { text: 'text-one-teal', soft: 'bg-one-teal/10 text-one-teal', solid: 'bg-one-teal', ring: 'ring-one-teal/20', dot: 'bg-one-teal' },
  gold: { text: 'text-one-gold', soft: 'bg-one-gold/10 text-one-gold', solid: 'bg-one-gold', ring: 'ring-one-gold/25', dot: 'bg-one-gold' },
  ok: { text: 'text-one-ok', soft: 'bg-one-ok/10 text-one-ok', solid: 'bg-one-ok', ring: 'ring-one-ok/20', dot: 'bg-one-ok' },
  warn: { text: 'text-one-warn', soft: 'bg-one-warn/10 text-one-warn', solid: 'bg-one-warn', ring: 'ring-one-warn/25', dot: 'bg-one-warn' },
  bad: { text: 'text-one-bad', soft: 'bg-one-bad/10 text-one-bad', solid: 'bg-one-bad', ring: 'ring-one-bad/20', dot: 'bg-one-bad' },
}

/** Calendar / timeline categories — each kind keeps one hue everywhere. */
export const kindTone = {
  lecture: 'royal',
  exam: 'bad',
  assignment: 'gold',
  event: 'teal',
  payment: 'warn',
} as const satisfies Record<string, UiTone>

export const statusTone: Record<string, UiTone> = {
  upcoming: 'royal',
  inProgress: 'sky',
  submitted: 'teal',
  graded: 'ok',
  overdue: 'bad',
  paid: 'ok',
  unpaid: 'warn',
  pending: 'warn',
  partial: 'warn',
  open: 'ok',
  closingSoon: 'warn',
  closed: 'neutral',
  available: 'ok',
  onLoan: 'neutral',
  returned: 'neutral',
  present: 'ok',
  late: 'warn',
  excused: 'sky',
  absent: 'bad',
  active: 'royal',
  completed: 'ok',
  passed: 'ok',
  scheduled: 'royal',
  inReview: 'sky',
  approved: 'ok',
  verified: 'ok',
  missing: 'bad',
}
