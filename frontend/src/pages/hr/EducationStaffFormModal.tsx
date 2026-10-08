import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { useHrOptions } from '@/hooks/useHrOptions'
import { hrEmployeeService } from '@/services/hrService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { EmploymentStatus, Gender, HrEmployeeDetail, HrEmployeePayload } from '@/types/hr'

const staffSchema = z.object({
  front_title: z.string().trim().max(50, 'Gelar depan maksimal 50 karakter.'),
  name: z.string().trim().min(1, 'Nama wajib diisi.').max(255, 'Nama maksimal 255 karakter.'),
  back_title: z.string().trim().max(100, 'Gelar belakang maksimal 100 karakter.'),
  nip: z.string().trim().max(50, 'NIP maksimal 50 karakter.'),
  nik: z.string().trim().regex(/^(\d{16})?$/, 'NIK harus 16 digit angka.'),
  email: z.string().trim().email('Format email tidak valid.').or(z.literal('')),
  phone: z.string().trim().max(30, 'Nomor telepon maksimal 30 karakter.'),
  gender: z.string(),
  birth_place: z.string().trim().max(100, 'Tempat lahir maksimal 100 karakter.'),
  birth_date: z.string(),
  address: z.string().trim().max(1000, 'Alamat maksimal 1000 karakter.'),
  employment_status: z.string().min(1, 'Status kepegawaian wajib dipilih.'),
  staff_category: z.string().min(1, 'Kategori tenaga kependidikan wajib dipilih.'),
  work_unit_id: z.string(),
  position_id: z.string(),
  faculty_id: z.string(),
  highest_education: z.string(),
  joined_at: z.string(),
  assigned_facility: z.string().trim().max(255, 'Penugasan maksimal 255 karakter.'),
  competency_summary: z.string().trim().max(2000, 'Ringkasan kompetensi maksimal 2000 karakter.'),
})

type StaffFormValues = z.infer<typeof staffSchema>

const emptyToNull = (value: string): string | null => (value === '' ? null : value)

/**
 * Tambah/ubah tenaga kependidikan. Saat mengubah, unit kerja & jabatan
 * terkunci (backend menolaknya — wajib lewat Penempatan & Mutasi / Riwayat
 * Jabatan supaya riwayatnya tercatat), dan NIK hanya dikirim bila diisi
 * baru: detail pegawai mengembalikan NIK tersamar, bukan nilai aslinya.
 */
export function EducationStaffFormModal({
  employee,
  onClose,
  onSaved,
}: {
  employee?: HrEmployeeDetail
  onClose: () => void
  onSaved: (employee: HrEmployeeDetail) => void
}) {
  const [formError, setFormError] = useState<string | null>(null)
  const options = useHrOptions()
  const isEdit = employee !== undefined

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<StaffFormValues>({
    resolver: zodResolver(staffSchema),
    defaultValues: {
      front_title: employee?.front_title ?? '',
      name: employee?.name ?? '',
      back_title: employee?.back_title ?? '',
      nip: employee?.nip ?? '',
      nik: '',
      email: employee?.email ?? '',
      phone: employee?.phone ?? '',
      gender: employee?.gender ?? '',
      birth_place: employee?.birth_place ?? '',
      birth_date: employee?.birth_date ?? '',
      address: employee?.address ?? '',
      employment_status: employee?.employment_status ?? 'permanent',
      staff_category: employee?.staff_category ?? '',
      work_unit_id: employee?.work_unit_id ?? '',
      position_id: employee?.position_id ?? '',
      faculty_id: employee?.faculty_id ?? '',
      highest_education: employee?.highest_education ?? '',
      joined_at: employee?.joined_at ?? '',
      assigned_facility: employee?.assigned_facility ?? '',
      competency_summary: employee?.competency_summary ?? '',
    },
  })

  const onSubmit = async (values: StaffFormValues) => {
    setFormError(null)
    const payload: HrEmployeePayload = {
      front_title: emptyToNull(values.front_title),
      name: values.name,
      back_title: emptyToNull(values.back_title),
      nip: emptyToNull(values.nip),
      email: emptyToNull(values.email),
      phone: emptyToNull(values.phone),
      gender: emptyToNull(values.gender) as Gender | null,
      birth_place: emptyToNull(values.birth_place),
      birth_date: emptyToNull(values.birth_date),
      address: emptyToNull(values.address),
      employment_status: values.employment_status as EmploymentStatus,
      staff_category: values.staff_category,
      faculty_id: emptyToNull(values.faculty_id),
      highest_education: emptyToNull(values.highest_education),
      joined_at: emptyToNull(values.joined_at),
      assigned_facility: emptyToNull(values.assigned_facility),
      competency_summary: emptyToNull(values.competency_summary),
      ...(values.nik !== '' && { nik: values.nik }),
    }

    try {
      onSaved(
        employee
          ? await hrEmployeeService.update(employee.id, payload)
          : await hrEmployeeService.create({
              ...payload,
              employee_type: 'staff',
              work_unit_id: emptyToNull(values.work_unit_id),
              position_id: emptyToNull(values.position_id),
            }),
      )
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  const enums = options?.enums
  const positionOptions = (options?.positions ?? []).map((position) => ({
    value: position.value,
    label: position.type === 'structural' ? `${position.label} (Struktural)` : position.label,
  }))

  return (
    <Modal open onClose={onClose} title={isEdit ? 'Ubah Tenaga Kependidikan' : 'Tambah Tenaga Kependidikan'} className="max-w-3xl">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5" noValidate>
        <fieldset className="flex flex-col gap-4">
          <legend className="mb-2 text-sm font-semibold text-ink-primary">Identitas</legend>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-[8rem_1fr_10rem]">
            <Input label="Gelar Depan" error={errors.front_title?.message} {...register('front_title')} />
            <Input label="Nama Lengkap" error={errors.name?.message} {...register('name')} />
            <Input label="Gelar Belakang" error={errors.back_title?.message} {...register('back_title')} />
          </div>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Input label="NIP / Nomor Induk Pegawai" error={errors.nip?.message} {...register('nip')} />
            <Input
              label="NIK"
              inputMode="numeric"
              placeholder={isEdit && employee?.nik ? employee.nik : undefined}
              hint={isEdit ? 'Kosongkan bila tidak diubah.' : undefined}
              error={errors.nik?.message}
              {...register('nik')}
            />
            <Select
              label="Jenis Kelamin"
              options={[{ value: '', label: '-' }, ...(enums?.gender ?? [])]}
              error={errors.gender?.message}
              {...register('gender')}
            />
            <Input label="Tempat Lahir" error={errors.birth_place?.message} {...register('birth_place')} />
            <Input type="date" label="Tanggal Lahir" error={errors.birth_date?.message} {...register('birth_date')} />
            <Input type="email" label="Email" error={errors.email?.message} {...register('email')} />
            <Input label="No. Telepon" error={errors.phone?.message} {...register('phone')} />
          </div>
          <Textarea label="Alamat" rows={2} error={errors.address?.message} {...register('address')} />
        </fieldset>

        <fieldset className="flex flex-col gap-4">
          <legend className="mb-2 text-sm font-semibold text-ink-primary">Kepegawaian</legend>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Select
              label="Kategori Tenaga Kependidikan"
              options={enums?.staff_category ?? []}
              placeholder="Pilih kategori"
              error={errors.staff_category?.message}
              {...register('staff_category')}
            />
            <Select
              label="Status Kepegawaian"
              options={enums?.employment_status ?? []}
              error={errors.employment_status?.message}
              {...register('employment_status')}
            />
            <Select
              label="Unit Kerja"
              options={[{ value: '', label: 'Belum ditempatkan' }, ...(options?.work_units ?? [])]}
              disabled={isEdit}
              hint={isEdit ? 'Diubah lewat menu Penempatan & Mutasi.' : undefined}
              error={errors.work_unit_id?.message}
              {...register('work_unit_id')}
            />
            <Select
              label="Jabatan"
              options={[{ value: '', label: 'Belum ada jabatan' }, ...positionOptions]}
              disabled={isEdit}
              hint={isEdit ? 'Diubah lewat Riwayat Jabatan atau Mutasi.' : undefined}
              error={errors.position_id?.message}
              {...register('position_id')}
            />
            <Select
              label="Fakultas Penempatan"
              options={[{ value: '', label: 'Tidak di fakultas' }, ...(options?.faculties ?? [])]}
              hint="Dipakai untuk rasio tendik per fakultas. Bila kosong, mengikuti fakultas unit kerja."
              error={errors.faculty_id?.message}
              {...register('faculty_id')}
            />
            <Select
              label="Pendidikan Terakhir"
              options={[{ value: '', label: '-' }, ...(enums?.education_level ?? [])]}
              error={errors.highest_education?.message}
              {...register('highest_education')}
            />
            <Input type="date" label="Tanggal Mulai Bekerja" error={errors.joined_at?.message} {...register('joined_at')} />
          </div>
        </fieldset>

        <fieldset className="flex flex-col gap-4">
          <legend className="mb-2 text-sm font-semibold text-ink-primary">Penugasan & Kompetensi</legend>
          <Input
            label="Laboratorium / Fasilitas yang Menjadi Tanggung Jawab"
            placeholder="mis. Laboratorium Komputer Dasar, Perpustakaan Pusat"
            hint="Terutama untuk laboran, teknisi, pustakawan, dan pranata komputer."
            error={errors.assigned_facility?.message}
            {...register('assigned_facility')}
          />
          <Textarea
            label="Ringkasan Kompetensi"
            rows={3}
            placeholder="mis. Sertifikasi K3 Laboratorium, Pustakawan Ahli Pertama, CCNA"
            hint="Detail sertifikat dicatat di menu Pelatihan & Sertifikasi."
            error={errors.competency_summary?.message}
            {...register('competency_summary')}
          />
        </fieldset>

        <div className="flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="submit" isLoading={isSubmitting}>
            Simpan
          </Button>
        </div>
      </form>
    </Modal>
  )
}
