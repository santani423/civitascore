import { useEffect, useState } from 'react'
import { useFieldArray, useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Trash2 } from 'lucide-react'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Checkbox } from '@/components/ui/Checkbox'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { classSectionService, lecturerService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import type { ClassSection, Lecturer, ScheduleConflict, UpdateClassTeachingResult } from '@/types/academic'
import { applyServerErrors } from '@/utils/applyServerErrors'

/** ISO-8601 day_of_week, sama dengan ClassSchedule::DAY_LABELS di backend. */
const DAY_OPTIONS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'].map((label, index) => ({
  value: String(index + 1),
  label,
}))

const TIME_PATTERN = /^([01]\d|2[0-3]):[0-5]\d$/

const scheduleSchema = z
  .object({
    day_of_week: z.string().min(1, 'Pilih hari.'),
    start_time: z.string().regex(TIME_PATTERN, 'Isi jam mulai.'),
    end_time: z.string().regex(TIME_PATTERN, 'Isi jam selesai.'),
    room: z.string().max(100, 'Maksimal 100 karakter.'),
  })
  // "HH:MM" berurutan secara leksikografis, jadi perbandingan string cukup.
  .refine((row) => row.end_time > row.start_time, { message: 'Jam selesai harus setelah jam mulai.', path: ['end_time'] })

const teachingSchema = z
  .object({
    lecturer_id: z.string(),
    schedules: z.array(scheduleSchema).max(7, 'Maksimal 7 jadwal per kelas.'),
    force: z.boolean(),
    reason: z.string().max(500, 'Maksimal 500 karakter.'),
  })
  .refine((values) => !values.force || values.reason.trim().length >= 10, {
    message: 'Alasan minimal 10 karakter.',
    path: ['reason'],
  })

const CONFLICT_LABEL: Record<ScheduleConflict['type'], string> = {
  lecturer: 'Bentrok dosen',
  room: 'Bentrok ruangan',
  internal: 'Bentrok antarjadwal',
}

function conflictDetails(error: NormalizedApiError): ScheduleConflict[] | null {
  const details = error.errors as unknown as { code?: string; conflicts?: ScheduleConflict[] } | undefined

  return error.status === 409 && details?.code === 'SCHEDULE_CONFLICT' ? (details.conflicts ?? []) : null
}

type TeachingFormValues = z.infer<typeof teachingSchema>

const EMPTY_SCHEDULE: TeachingFormValues['schedules'][number] = { day_of_week: '1', start_time: '', end_time: '', room: '' }

function lecturerLabel(lecturer: Lecturer): string {
  return `${lecturer.name} — ${lecturer.nidn}`
}

/**
 * Panel "Dosen & Jadwal" (Bagian Akademik, classes.update): menetapkan dosen
 * pengampu dan mengganti seluruh jadwal mingguan kelas sekaligus. Bentrok
 * dengan kelas lain di periode yang sama ditolak backend (409
 * SCHEDULE_CONFLICT) dan dirinci per kelas; bila semuanya bentrok dosen,
 * penyimpanan bisa dipaksa dengan alasan yang dicatat di audit log.
 */
export function ClassTeachingModal({
  classSection,
  onClose,
  onSaved,
}: {
  classSection: ClassSection
  onClose: () => void
  onSaved: (result: UpdateClassTeachingResult) => void
}) {
  const [formError, setFormError] = useState<string | null>(null)
  const [conflicts, setConflicts] = useState<ScheduleConflict[] | null>(null)
  const [lecturerSearch, setLecturerSearch] = useState('')
  const [lecturers, setLecturers] = useState<Lecturer[] | null>(null)
  const [lecturerLoadError, setLecturerLoadError] = useState<string | null>(null)
  // Label setiap dosen yang pernah tampil, supaya dosen terpilih tetap ada di
  // pilihan walau tidak cocok dengan pencarian berikutnya.
  const [lecturerLabels, setLecturerLabels] = useState<Record<string, string>>(() =>
    classSection.lecturer ? { [classSection.lecturer.id]: classSection.lecturer.name } : {},
  )

  useEffect(() => {
    const timeout = setTimeout(() => {
      lecturerService
        .index({ per_page: 100, search: lecturerSearch || undefined, filter: { is_active: '1' } })
        .then((result) => {
          setLecturers(result.data)
          setLecturerLabels((current) => ({
            ...current,
            ...Object.fromEntries(result.data.map((lecturer) => [lecturer.id, lecturerLabel(lecturer)])),
          }))
        })
        .catch((error: NormalizedApiError) => setLecturerLoadError(error.message))
    }, 250)

    return () => clearTimeout(timeout)
  }, [lecturerSearch])

  const {
    register,
    control,
    handleSubmit,
    watch,
    setValue,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<TeachingFormValues>({
    resolver: zodResolver(teachingSchema),
    defaultValues: {
      lecturer_id: classSection.lecturer_id ?? '',
      schedules: classSection.schedules.map((schedule) => ({
        day_of_week: String(schedule.day_of_week),
        start_time: schedule.start_time,
        end_time: schedule.end_time,
        room: schedule.room ?? '',
      })),
      force: false,
      reason: '',
    },
  })

  const { fields, append, remove } = useFieldArray({ control, name: 'schedules' })
  const selectedLecturerId = watch('lecturer_id')
  const force = watch('force')
  // Hanya bentrok dosen yang boleh dipaksa — satu bentrok ruangan/antarjadwal
  // saja sudah membuat penyimpanan tidak bisa dipaksa.
  const canForce = conflicts !== null && conflicts.length > 0 && conflicts.every((conflict) => conflict.forceable)

  // Pilihan "paksa" tersembunyi tidak boleh ikut terkirim/divalidasi.
  useEffect(() => {
    if (!canForce) setValue('force', false)
  }, [canForce, setValue])

  const lecturerOptions = [
    { value: '', label: 'Belum ditetapkan' },
    ...(selectedLecturerId && !lecturers?.some((lecturer) => lecturer.id === selectedLecturerId)
      ? [{ value: selectedLecturerId, label: lecturerLabels[selectedLecturerId] ?? 'Dosen terpilih' }]
      : []),
    ...(lecturers ?? []).map((lecturer) => ({ value: lecturer.id, label: lecturerLabel(lecturer) })),
  ]

  const onSubmit = async (values: TeachingFormValues) => {
    setFormError(null)
    const forced = values.force && canForce

    try {
      const result = await classSectionService.updateTeaching(classSection.id, {
        lecturer_id: values.lecturer_id || null,
        schedules: values.schedules.map((schedule) => ({
          day_of_week: Number(schedule.day_of_week),
          start_time: schedule.start_time,
          end_time: schedule.end_time,
          room: schedule.room.trim() || null,
        })),
        ...(forced ? { force: true, reason: values.reason.trim() } : {}),
      })
      onSaved(result)
    } catch (error) {
      const apiError = error as NormalizedApiError
      const scheduleConflicts = conflictDetails(apiError)

      setConflicts(scheduleConflicts)
      setFormError(scheduleConflicts ? apiError.message : applyServerErrors(apiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title="Dosen & Jadwal" className="max-w-2xl">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      {conflicts && conflicts.length > 0 && (
        <div className="mb-4 flex flex-col gap-2 rounded-lg border border-border p-3 text-sm">
          <p className="font-medium text-ink-primary">Jadwal yang bentrok</p>
          <ul className="flex flex-col gap-1">
            {conflicts.map((conflict, index) => (
              <li key={`${conflict.type}-${conflict.class_section_id ?? 'self'}-${index}`} className="flex flex-wrap items-center gap-2">
                <Badge variant={conflict.forceable ? 'warning' : 'danger'}>{CONFLICT_LABEL[conflict.type]}</Badge>
                <span className="text-ink-secondary">{conflict.message}</span>
              </li>
            ))}
          </ul>
          {!canForce && (
            <p className="text-xs text-ink-tertiary">Bentrok ruangan dan antarjadwal tidak dapat dipaksa — ubah hari, jam, atau ruangan.</p>
          )}
        </div>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5" noValidate>
        <div className="flex flex-col gap-3">
          <p className="text-sm font-medium text-ink-primary">Dosen Pengampu</p>
          {lecturerLoadError && <Alert variant="danger">{lecturerLoadError}</Alert>}
          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <Input
              placeholder="Cari nama, NIDN, atau email dosen..."
              aria-label="Cari dosen"
              value={lecturerSearch}
              onChange={(event) => setLecturerSearch(event.target.value)}
            />
            <Select
              aria-label="Dosen pengampu"
              disabled={lecturers === null && !lecturerLoadError}
              error={errors.lecturer_id?.message}
              options={lecturerOptions}
              {...register('lecturer_id')}
            />
          </div>
          {lecturers !== null && lecturers.length === 0 && (
            <p className="text-xs text-ink-tertiary">Tidak ada dosen aktif yang cocok dengan pencarian.</p>
          )}
        </div>

        <div className="flex flex-col gap-3">
          <div>
            <p className="text-sm font-medium text-ink-primary">Jadwal Mingguan</p>
            <p className="text-xs text-ink-tertiary">
              Menyimpan akan mengganti seluruh jadwal kelas. Kosongkan ruangan untuk kelas daring.
            </p>
          </div>
          {errors.schedules?.message && <p className="text-xs text-danger">{errors.schedules.message}</p>}
          {errors.schedules?.root?.message && <p className="text-xs text-danger">{errors.schedules.root.message}</p>}

          {fields.length === 0 && <p className="text-sm text-ink-tertiary">Belum ada jadwal.</p>}

          {fields.map((field, index) => (
            <div
              key={field.id}
              className="grid grid-cols-2 items-start gap-2 rounded-lg border border-border p-3 sm:grid-cols-[8rem_1fr_1fr_1fr_auto] sm:border-0 sm:p-0"
            >
              <Select
                aria-label={`Hari jadwal ${index + 1}`}
                options={DAY_OPTIONS}
                error={errors.schedules?.[index]?.day_of_week?.message}
                {...register(`schedules.${index}.day_of_week`)}
              />
              <Input
                type="time"
                aria-label={`Jam mulai jadwal ${index + 1}`}
                error={errors.schedules?.[index]?.start_time?.message}
                {...register(`schedules.${index}.start_time`)}
              />
              <Input
                type="time"
                aria-label={`Jam selesai jadwal ${index + 1}`}
                error={errors.schedules?.[index]?.end_time?.message}
                {...register(`schedules.${index}.end_time`)}
              />
              <Input
                placeholder="Ruangan"
                aria-label={`Ruangan jadwal ${index + 1}`}
                error={errors.schedules?.[index]?.room?.message}
                {...register(`schedules.${index}.room`)}
              />
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="h-10 justify-self-end"
                aria-label={`Hapus jadwal ${index + 1}`}
                onClick={() => remove(index)}
              >
                <Trash2 className="size-4 text-danger" />
              </Button>
            </div>
          ))}

          {fields.length < 7 && (
            <Button
              type="button"
              variant="outline"
              size="sm"
              leftIcon={<Plus className="size-4" />}
              className="self-start"
              onClick={() => append(EMPTY_SCHEDULE)}
            >
              Tambah Jadwal
            </Button>
          )}
        </div>

        {canForce && (
          <div className="flex flex-col gap-3 rounded-lg border border-border p-3">
            <Checkbox label="Paksa simpan meski jadwal dosen bentrok" {...register('force')} />
            {force && (
              <Textarea
                label="Alasan"
                rows={2}
                placeholder="Mis. team teaching bergantian dengan dosen tamu."
                hint="Dicatat di audit log bersama daftar bentroknya."
                error={errors.reason?.message}
                {...register('reason')}
              />
            )}
          </div>
        )}

        <div className="flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="submit" isLoading={isSubmitting} variant={canForce && force ? 'danger' : 'primary'}>
            {canForce && force ? 'Paksa Simpan' : 'Simpan'}
          </Button>
        </div>
      </form>
    </Modal>
  )
}
