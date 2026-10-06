import { useCallback, useState } from 'react'
import { Link } from 'react-router-dom'
import { CalendarDays, Clock, MapPin, UserRound } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import type { ScheduleSlot, TodaySlot } from '@/types/studentPortal'
import { formatDate } from '@/utils/portalFormat'
import { cn } from '@/utils/cn'

const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']

const STATUS: Record<TodaySlot['status'], { label: string; variant: 'success' | 'info' | 'neutral' }> = {
  ongoing: { label: 'Sedang berlangsung', variant: 'success' },
  upcoming: { label: 'Akan dimulai', variant: 'info' },
  finished: { label: 'Selesai', variant: 'neutral' },
}

function SlotCard({ slot, status }: { slot: ScheduleSlot; status?: TodaySlot['status'] }) {
  return (
    <div
      className={cn(
        'flex flex-col gap-1.5 rounded-lg border p-3',
        status === 'ongoing' ? 'border-primary bg-primary-50/50 dark:bg-primary-950/40' : 'border-border',
      )}
    >
      <div className="flex flex-wrap items-start justify-between gap-2">
        <Link to={ROUTES.portal.mataKuliahDetail.replace(':id', slot.class_section_id)} className="font-medium text-ink-primary hover:text-primary">
          {slot.course_name}
        </Link>
        <div className="flex flex-wrap gap-1">
          {status && <Badge variant={STATUS[status].variant}>{STATUS[status].label}</Badge>}
          {slot.is_pending_approval && <Badge variant="warning">Menunggu persetujuan KRS</Badge>}
        </div>
      </div>
      <p className="flex items-center gap-1.5 text-xs text-ink-secondary">
        <Clock className="size-3.5" /> {slot.start_time}–{slot.end_time}
      </p>
      <p className="flex items-center gap-1.5 text-xs text-ink-secondary">
        <MapPin className="size-3.5" /> {slot.room ?? 'Ruangan belum ditetapkan'} · Kelas {slot.class_code} · {slot.credits} SKS
      </p>
      <p className="flex items-center gap-1.5 text-xs text-ink-secondary">
        <UserRound className="size-3.5" /> {slot.lecturer?.name ?? 'Dosen belum ditetapkan'}
      </p>
    </div>
  )
}

export function PortalSchedulePage() {
  const fetchSchedule = useCallback(() => studentPortalService.schedule(), [])
  const { data, isLoading, error, refetch } = useFetch(fetchSchedule)
  const [view, setView] = useState<'today' | 'week'>('today')

  if (isLoading && !data) return <PortalLoading cards={0} rows={6} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Jadwal Kuliah"
        description={data.term ? `Semester ${data.term.label}` : 'Belum ada semester aktif'}
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Jadwal' }]}
        actions={
          <Link to={ROUTES.portal.kalender}>
            <Button variant="outline" size="sm" leftIcon={<CalendarDays className="size-3.5" />}>
              Tampilan bulanan
            </Button>
          </Link>
        }
      />

      <div className="inline-flex w-full rounded-lg border border-border bg-surface p-1 sm:w-auto" role="tablist">
        {(['today', 'week'] as const).map((option) => (
          <button
            key={option}
            type="button"
            role="tab"
            aria-selected={view === option}
            onClick={() => setView(option)}
            className={cn(
              'flex-1 rounded-md px-4 py-1.5 text-sm font-medium transition-colors sm:flex-none',
              view === option ? 'bg-primary text-white' : 'text-ink-secondary hover:bg-surface-hover',
            )}
          >
            {option === 'today' ? 'Hari Ini' : 'Mingguan'}
          </button>
        ))}
      </div>

      {data.slots.length === 0 ? (
        <PortalEmpty
          icon={CalendarDays}
          title="Belum ada jadwal kuliah."
          description="Jadwal muncul setelah KRS Anda disetujui dosen wali."
          action={
            <Link to={ROUTES.portal.krs}>
              <Button size="sm">Buka KRS</Button>
            </Link>
          }
        />
      ) : view === 'today' ? (
        <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
          <Card title={`Hari ini — ${formatDate(data.now, 'long')}`} className="lg:col-span-2">
            {!data.is_in_term ? (
              <p className="text-sm text-ink-secondary">Hari ini di luar masa perkuliahan semester aktif.</p>
            ) : data.today.length === 0 ? (
              <p className="text-sm text-ink-secondary">Tidak ada kuliah hari ini.</p>
            ) : (
              <div className="flex flex-col gap-3">
                {data.today.map((slot) => (
                  <SlotCard key={slot.id} slot={slot} status={slot.status} />
                ))}
              </div>
            )}
          </Card>
          <Card title="Kuliah Berikutnya">
            {data.next_class ? (
              <div className="flex flex-col gap-2">
                <p className="text-sm text-ink-secondary">{formatDate(data.next_class.date, 'long')}</p>
                <SlotCard slot={data.next_class} />
              </div>
            ) : (
              <p className="text-sm text-ink-secondary">Tidak ada kuliah dalam 7 hari ke depan.</p>
            )}
          </Card>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
          {DAYS.map((day, index) => {
            const slots = data.slots.filter((slot) => slot.day_of_week === index + 1)
            if (slots.length === 0 && index >= 5) return null

            return (
              <Card key={day} title={day} description={`${slots.length} kelas`}>
                {slots.length === 0 ? (
                  <p className="text-sm text-ink-tertiary">Tidak ada kuliah.</p>
                ) : (
                  <div className="flex flex-col gap-2.5">
                    {slots.map((slot) => (
                      <SlotCard key={slot.id} slot={slot} />
                    ))}
                  </div>
                )}
              </Card>
            )
          })}
        </div>
      )}

      <p className="text-xs text-ink-tertiary">Waktu ditampilkan sesuai zona waktu kampus ({data.timezone}).</p>
    </div>
  )
}
