import { useCallback, useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { Camera, Lock, Pencil } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Textarea } from '@/components/ui/Textarea'
import { Alert } from '@/components/ui/Alert'
import { Avatar } from '@/components/ui/Avatar'
import { InfoList, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { StudentStatusBadge } from '@/components/portal/badges'
import { useFetch } from '@/hooks/useFetch'
import { fetchObjectUrl, studentPortalService } from '@/services/studentPortalService'
import { fileUploadService } from '@/services/fileUploadService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'
import { formatDate } from '@/utils/portalFormat'

const FIELD_LABELS: Record<string, string> = {
  name: 'Nama',
  email: 'Email',
  birth_place: 'Tempat lahir',
  tanggal_lahir: 'Tanggal lahir',
  gender: 'Jenis kelamin',
}

function usePhotoUrl(fileId: string | null | undefined) {
  const [url, setUrl] = useState<string | null>(null)

  useEffect(() => {
    if (!fileId) return

    let objectUrl: string | null = null
    let cancelled = false

    fetchObjectUrl(`/file-uploads/${fileId}/preview`)
      .then((value) => {
        objectUrl = value
        if (!cancelled) setUrl(value)
      })
      .catch(() => {
        if (!cancelled) setUrl(null)
      })

    return () => {
      cancelled = true
      if (objectUrl) URL.revokeObjectURL(objectUrl)
    }
  }, [fileId])

  return fileId ? url : null
}

export function PortalProfilePage() {
  const fetchProfile = useCallback(() => studentPortalService.profile(), [])
  const { data: profile, setData: setProfile, isLoading, error, refetch } = useFetch(fetchProfile)
  const photoUrl = usePhotoUrl(profile?.photo?.file_id)

  const [editing, setEditing] = useState(false)
  const [phone, setPhone] = useState('')
  const [address, setAddress] = useState('')
  const [saving, setSaving] = useState(false)
  const [uploading, setUploading] = useState(false)
  const [message, setMessage] = useState<{ variant: 'success' | 'danger'; text: string } | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})
  const fileInput = useRef<HTMLInputElement>(null)

  const startEdit = () => {
    setPhone(profile?.contact.phone ?? '')
    setAddress(profile?.address ?? '')
    setFieldErrors({})
    setEditing(true)
  }

  const save = async () => {
    setSaving(true)
    setMessage(null)
    setFieldErrors({})

    try {
      const result = await studentPortalService.updateProfile({ phone: phone || null, address: address || null })
      setProfile(result.data)
      setEditing(false)
      setMessage({ variant: 'success', text: result.message ?? 'Profil berhasil diperbarui.' })
    } catch (err) {
      const apiError = err as NormalizedApiError
      setFieldErrors(apiError.errors ?? {})
      setMessage({ variant: 'danger', text: apiError.message })
    } finally {
      setSaving(false)
    }
  }

  const uploadPhoto = async (file: File) => {
    setUploading(true)
    setMessage(null)

    try {
      const upload = await fileUploadService.upload(file)
      const result = await studentPortalService.updateProfile({ photo_file_id: upload.id })
      setProfile(result.data)
      setMessage({ variant: 'success', text: 'Foto profil diperbarui.' })
    } catch (err) {
      const apiError = err as NormalizedApiError
      setMessage({ variant: 'danger', text: apiError.errors?.photo_file_id?.[0] ?? apiError.errors?.file?.[0] ?? apiError.message })
    } finally {
      setUploading(false)
      if (fileInput.current) fileInput.current.value = ''
    }
  }

  if (isLoading && !profile) return <PortalLoading cards={0} rows={8} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!profile) return null

  const { personal, contact, academic } = profile

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Profil"
        description="Data diri dan data akademik Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Profil' }]}
      />

      {message && (
        <Alert variant={message.variant} onDismiss={() => setMessage(null)}>
          {message.text}
        </Alert>
      )}

      {profile.pending_data_change && (
        <Alert variant="info" title="Pengajuan perubahan data sedang ditinjau">
          {Object.entries(profile.pending_data_change.changes)
            .map(([field, value]) => `${FIELD_LABELS[field] ?? field}: ${value}`)
            .join(' · ')}
        </Alert>
      )}

      <Card>
        <div className="flex flex-col items-center gap-4 text-center sm:flex-row sm:text-left">
          <div className="relative">
            {photoUrl ? (
              <img src={photoUrl} alt={`Foto ${personal.name}`} className="size-20 rounded-full object-cover" />
            ) : (
              <Avatar name={personal.name} size="lg" />
            )}
            <button
              type="button"
              onClick={() => fileInput.current?.click()}
              disabled={uploading}
              aria-label="Ganti foto profil"
              className="absolute -bottom-1 -right-1 flex size-8 items-center justify-center rounded-full border border-border bg-surface text-ink-secondary shadow-card hover:text-primary disabled:opacity-50"
            >
              <Camera className="size-4" />
            </button>
            <input
              ref={fileInput}
              type="file"
              accept="image/png,image/jpeg"
              className="hidden"
              onChange={(event) => {
                const file = event.target.files?.[0]
                if (file) void uploadPhoto(file)
              }}
            />
          </div>
          <div className="min-w-0">
            <h2 className="text-lg font-semibold text-ink-primary">{personal.name}</h2>
            <p className="text-sm text-ink-secondary">
              {personal.nim} · {academic.study_program.name}
            </p>
            <div className="mt-2 flex flex-wrap justify-center gap-2 sm:justify-start">
              <StudentStatusBadge status={academic.status} />
            </div>
            <p className="mt-1 text-xs text-ink-tertiary">Foto JPG/PNG maksimal 2 MB.</p>
          </div>
        </div>
      </Card>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <Card
          title="Data Pribadi"
          description="Perubahan data ini memerlukan persetujuan Bagian Akademik."
          actions={
            <Link to={`${ROUTES.portal.pengajuan}?jenis=data_change`}>
              <Button size="sm" variant="outline">
                Ajukan perubahan
              </Button>
            </Link>
          }
        >
          <InfoList
            items={[
              { label: 'Nama lengkap', value: personal.name },
              { label: 'NIM', value: personal.nim },
              { label: 'Tempat lahir', value: personal.birth_place ?? '-' },
              { label: 'Tanggal lahir', value: formatDate(personal.birth_date, 'long') },
              { label: 'Jenis kelamin', value: personal.gender_label ?? '-' },
              { label: 'Email', value: contact.email ?? '-' },
            ]}
          />
        </Card>

        <Card
          title="Kontak & Alamat"
          description="Dapat Anda ubah sendiri."
          actions={
            !editing && (
              <Button size="sm" variant="outline" leftIcon={<Pencil className="size-3.5" />} onClick={startEdit}>
                Ubah
              </Button>
            )
          }
        >
          {editing ? (
            <div className="flex flex-col gap-3">
              <Input
                label="Nomor telepon"
                value={phone}
                onChange={(event) => setPhone(event.target.value)}
                error={fieldErrors.phone?.[0]}
                placeholder="+62 812 0000 0000"
                inputMode="tel"
              />
              <Textarea label="Alamat" rows={3} value={address} onChange={(event) => setAddress(event.target.value)} error={fieldErrors.address?.[0]} />
              <div className="flex justify-end gap-2">
                <Button variant="outline" onClick={() => setEditing(false)}>
                  Batal
                </Button>
                <Button isLoading={saving} onClick={save}>
                  Simpan
                </Button>
              </div>
            </div>
          ) : (
            <InfoList
              columns={1}
              items={[
                { label: 'Nomor telepon', value: contact.phone ?? '-' },
                { label: 'Alamat', value: profile.address ?? '-' },
              ]}
            />
          )}
        </Card>
      </div>

      <Card
        title="Data Akademik"
        description="Ditetapkan Bagian Akademik."
        actions={
          <span className="inline-flex items-center gap-1 text-xs text-ink-tertiary">
            <Lock className="size-3.5" /> Hanya baca
          </span>
        }
      >
        <InfoList
          columns={3}
          items={[
            { label: 'Program studi', value: `${academic.study_program.name} (${academic.study_program.degree_level})` },
            { label: 'Fakultas', value: academic.faculty?.name ?? '-' },
            { label: 'Angkatan', value: academic.admission_year },
            { label: 'Semester', value: academic.semester ?? '-' },
            { label: 'Status', value: academic.status.label },
            { label: 'Dosen wali', value: academic.academic_advisor?.name ?? 'Belum ditetapkan' },
            { label: 'Kurikulum', value: academic.curriculum?.name ?? '-' },
            { label: 'Terdaftar sejak', value: formatDate(academic.enrolled_at, 'long') },
          ]}
        />
      </Card>
    </div>
  )
}
