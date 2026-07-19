import { useCallback, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Checkbox } from '@/components/ui/Checkbox'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { SkeletonRow } from '@/components/ui/Skeleton'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import {
  notificationChannelService,
  notificationPreferenceService,
  notificationTemplateService,
} from '@/services/notificationService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type {
  NotificationChannel,
  NotificationChannelConfig,
  NotificationTemplate,
  UserNotificationPreference,
} from '@/types/notification'
import { ROUTES } from '@/constants/routes'

const CHANNEL_OPTIONS: NotificationChannel[] = ['database', 'mail', 'push', 'whatsapp', 'sms']

const templateSchema = z.object({
  event_key: z.string().min(1, 'Event key wajib diisi.').max(150),
  name: z.string().min(1, 'Nama wajib diisi.').max(150),
  channel: z.enum(CHANNEL_OPTIONS as [NotificationChannel, ...NotificationChannel[]]),
  subject: z.string().max(200).optional().or(z.literal('')),
  body_template: z.string().min(1, 'Isi template wajib diisi.'),
})

type TemplateFormValues = z.infer<typeof templateSchema>

export function NotificationsPage() {
  const canManageTemplates = usePermission('notification_templates.update')
  const canCreateTemplate = usePermission('notification_templates.create')
  const canManageChannels = usePermission('notification_channels.update')

  const templates = usePaginatedList<NotificationTemplate>({ fetcher: notificationTemplateService.index })
  const [templateModal, setTemplateModal] = useState<{ template: NotificationTemplate | null } | null>(null)

  const getChannels = useCallback(() => notificationChannelService.index(), [])
  const channels = useFetch(getChannels)
  const [channelError, setChannelError] = useState<string | null>(null)
  const [togglingChannel, setTogglingChannel] = useState<string | null>(null)

  const getPreferences = useCallback(() => notificationPreferenceService.index(), [])
  const preferences = useFetch(getPreferences)
  const [preferenceError, setPreferenceError] = useState<string | null>(null)
  const [newPrefChannel, setNewPrefChannel] = useState<NotificationChannel>('database')
  const [newPrefType, setNewPrefType] = useState('')
  const [isSavingPreference, setIsSavingPreference] = useState(false)

  const handleToggleChannel = async (channel: NotificationChannelConfig) => {
    setChannelError(null)
    setTogglingChannel(channel.id)

    try {
      await notificationChannelService.update(channel.id, !channel.is_enabled)
      channels.refetch()
    } catch (error) {
      setChannelError((error as NormalizedApiError).message ?? 'Gagal mengubah channel.')
    } finally {
      setTogglingChannel(null)
    }
  }

  const handleTogglePreference = async (preference: UserNotificationPreference) => {
    setPreferenceError(null)

    try {
      await notificationPreferenceService.upsert({
        channel: preference.channel,
        notification_type: preference.notification_type,
        is_enabled: !preference.is_enabled,
      })
      preferences.refetch()
    } catch (error) {
      setPreferenceError((error as NormalizedApiError).message ?? 'Gagal mengubah preferensi.')
    }
  }

  const handleAddPreference = async () => {
    if (!newPrefType.trim()) return
    setPreferenceError(null)
    setIsSavingPreference(true)

    try {
      await notificationPreferenceService.upsert({
        channel: newPrefChannel,
        notification_type: newPrefType,
        is_enabled: true,
      })
      setNewPrefType('')
      preferences.refetch()
    } catch (error) {
      setPreferenceError((error as NormalizedApiError).message ?? 'Gagal menambahkan preferensi.')
    } finally {
      setIsSavingPreference(false)
    }
  }

  const templateColumns: DataTableColumn<NotificationTemplate>[] = [
    {
      header: 'Template',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.event_key}</p>
        </div>
      ),
    },
    { header: 'Channel', cell: (row) => <Badge variant="info">{row.channel}</Badge> },
    { header: 'Subjek', cell: (row) => row.subject ?? '-' },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
    {
      header: '',
      cell: (row) =>
        canManageTemplates ? (
          <Button variant="ghost" size="sm" onClick={() => setTemplateModal({ template: row })}>
            Ubah
          </Button>
        ) : null,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Notifikasi"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengaturan' }, { label: 'Notifikasi' }]}
      />

      <Card
        title="Template Notifikasi"
        noPadding
        actions={
          canCreateTemplate && (
            <Button size="sm" leftIcon={<Plus className="size-3.5" />} onClick={() => setTemplateModal({ template: null })}>
              Tambah Template
            </Button>
          )
        }
      >
        {templates.error ? (
          <Alert variant="danger" className="m-4">
            {templates.error}
          </Alert>
        ) : (
          <DataTable
            columns={templateColumns}
            data={templates.data}
            rowKey={(row) => row.id}
            emptyMessage={templates.isLoading ? 'Memuat...' : 'Belum ada template.'}
          />
        )}
      </Card>

      <Card title="Channel Notifikasi" description="Aktifkan/nonaktifkan jalur pengiriman notifikasi" noPadding>
        {channelError && (
          <Alert variant="danger" className="m-4" onDismiss={() => setChannelError(null)}>
            {channelError}
          </Alert>
        )}
        {channels.isLoading ? (
          <div className="flex flex-col gap-3 p-4">
            <SkeletonRow />
          </div>
        ) : channels.error ? (
          <Alert variant="danger" className="m-4">
            {channels.error}
          </Alert>
        ) : (
          <ul className="divide-y divide-border">
            {(channels.data ?? []).map((channel) => (
              <li key={channel.id} className="flex items-center justify-between px-4 py-3">
                <div>
                  <p className="text-sm font-medium text-ink-primary">{channel.name}</p>
                  <p className="text-xs text-ink-tertiary">{channel.code}</p>
                </div>
                <Checkbox
                  checked={channel.is_enabled}
                  disabled={!canManageChannels || togglingChannel === channel.id}
                  onChange={() => handleToggleChannel(channel)}
                />
              </li>
            ))}
          </ul>
        )}
      </Card>

      <Card title="Preferensi Notifikasi Saya" noPadding>
        {preferenceError && (
          <Alert variant="danger" className="m-4" onDismiss={() => setPreferenceError(null)}>
            {preferenceError}
          </Alert>
        )}

        {preferences.isLoading ? (
          <div className="flex flex-col gap-3 p-4">
            <SkeletonRow />
          </div>
        ) : preferences.error ? (
          <Alert variant="danger" className="m-4">
            {preferences.error}
          </Alert>
        ) : (
          <ul className="divide-y divide-border">
            {(preferences.data ?? []).length === 0 && (
              <li className="px-4 py-3 text-sm text-ink-tertiary">Belum ada preferensi kustom — semua notifikasi memakai pengaturan bawaan.</li>
            )}
            {(preferences.data ?? []).map((preference) => (
              <li key={preference.id} className="flex items-center justify-between px-4 py-3">
                <div>
                  <p className="text-sm font-medium text-ink-primary">{preference.notification_type}</p>
                  <p className="text-xs text-ink-tertiary">{preference.channel}</p>
                </div>
                <Checkbox checked={preference.is_enabled} onChange={() => handleTogglePreference(preference)} />
              </li>
            ))}
          </ul>
        )}

        <div className="flex flex-wrap items-end gap-3 border-t border-border p-4">
          <Select
            label="Channel"
            value={newPrefChannel}
            onChange={(event) => setNewPrefChannel(event.target.value as NotificationChannel)}
            options={CHANNEL_OPTIONS.map((value) => ({ value, label: value }))}
          />
          <Input
            label="Tipe Notifikasi"
            placeholder="mis. approval.submitted"
            value={newPrefType}
            onChange={(event) => setNewPrefType(event.target.value)}
          />
          <Button isLoading={isSavingPreference} disabled={!newPrefType.trim()} onClick={handleAddPreference}>
            Tambah
          </Button>
        </div>
      </Card>

      {templateModal && (
        <TemplateFormModal
          template={templateModal.template}
          onClose={() => setTemplateModal(null)}
          onSaved={() => {
            setTemplateModal(null)
            templates.refetch()
          }}
        />
      )}
    </div>
  )
}

function TemplateFormModal({
  template,
  onClose,
  onSaved,
}: {
  template: NotificationTemplate | null
  onClose: () => void
  onSaved: () => void
}) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<TemplateFormValues>({
    resolver: zodResolver(templateSchema),
    defaultValues: {
      event_key: template?.event_key ?? '',
      name: template?.name ?? '',
      channel: template?.channel ?? 'database',
      subject: template?.subject ?? '',
      body_template: template?.body_template ?? '',
    },
  })

  const onSubmit = async (values: TemplateFormValues) => {
    setFormError(null)
    const payload = { ...values, subject: values.subject || null }

    try {
      if (template) {
        await notificationTemplateService.update(template.id, payload)
      } else {
        await notificationTemplateService.create(payload)
      }
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title={template ? 'Ubah Template' : 'Tambah Template'} className="max-w-lg">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <Input label="Event Key" error={errors.event_key?.message} {...register('event_key')} />
        <Input label="Nama" error={errors.name?.message} {...register('name')} />
        <Select
          label="Channel"
          options={CHANNEL_OPTIONS.map((value) => ({ value, label: value }))}
          error={errors.channel?.message}
          {...register('channel')}
        />
        <Input label="Subjek (opsional)" error={errors.subject?.message} {...register('subject')} />
        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink-primary">Isi Template</label>
          <textarea
            rows={4}
            className="w-full rounded-lg border border-border-strong bg-surface px-3 py-2 text-sm text-ink-primary focus:outline-none focus:ring-2 focus:ring-primary"
            {...register('body_template')}
          />
          {errors.body_template && <p className="mt-1 text-xs text-danger">{errors.body_template.message}</p>}
        </div>

        <div className="mt-2 flex justify-end gap-2">
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
