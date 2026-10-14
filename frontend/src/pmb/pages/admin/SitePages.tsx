import { useEffect, useState } from 'react'
import { Plus, Trash2, Upload } from 'lucide-react'
import { pmbAdmin } from '@/pmb/api/admin'
import { adminClient } from '@/pmb/api/http'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { AdminList } from '@/pmb/components/AdminList'
import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { Tabs } from '@/pmb/components/Tabs'
import { AdminPage, Can, P, options, useLookups } from '@/pmb/components/admin'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Badge } from '@/components/ui/Badge'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { Checkbox } from '@/components/ui/Checkbox'
import { Alert } from '@/components/ui/Alert'
import { DataTable } from '@/components/ui/DataTable'
import { date, dateTime, money } from '@/pmb/lib/format'

const CONTENT_TYPES = [
  ['page', 'Halaman'], ['faq', 'FAQ'], ['news', 'Berita'], ['guide', 'Panduan'], ['brochure', 'Brosur'], ['banner', 'Banner beranda'], ['declaration', 'Pernyataan pendaftar'],
  ['privacy_policy', 'Kebijakan privasi'], ['terms', 'Ketentuan pendaftaran'], ['result_template', 'Template hasil'], ['loa_template', 'Template LoA'], ['orientation_info', 'Info orientasi'], ['re_registration_info', 'Info daftar ulang'],
].map(([value, label]) => ({ value, label }))

interface Content { id: string; type: string; slug: string; title: string; category: string | null; version: number; status: string; publish_at: string | null; unpublish_at: string | null; updated_at: string; body?: string | null; period_id?: string | null; file_id?: string | null; seo_title?: string | null; seo_description?: string | null; sort_order?: number; versioned?: boolean }

/** ADM-21 Konten website: per tipe, editor, jadwal tayang, versi dokumen legal. */
export function ContentsPage() {
  const lookups = useLookups()
  const [reload, setReload] = useState(0)
  const [editing, setEditing] = useState<Content | 'new' | null>(null)
  const action = useAction()

  const open = async (row: Content) => setEditing(await pmbAdmin.get<Content>(`contents/${row.id}`))
  const fields = (isNew: boolean): FieldDef[] => [
    ...(isNew ? [{ name: 'type', label: 'Tipe', type: 'select', required: true, options: CONTENT_TYPES } as FieldDef] : []),
    { name: 'title', label: 'Judul', required: true },
    { name: 'slug', label: 'Slug (huruf-kecil-dan-strip)' },
    { name: 'category', label: 'Kategori (FAQ/berita)' },
    { name: 'body', label: 'Isi (HTML; tag berbahaya dibuang otomatis). Template hasil/LoA boleh memakai {{placeholder}}.', type: 'textarea' },
    { name: 'period_id', label: 'Khusus periode', type: 'select', options: options(lookups?.periods, (p) => p.name) },
    { name: 'publish_at', label: 'Tayang mulai', type: 'datetime' },
    { name: 'unpublish_at', label: 'Tayang sampai', type: 'datetime' },
    { name: 'seo_title', label: 'Judul SEO' },
    { name: 'seo_description', label: 'Deskripsi SEO', type: 'textarea' },
    { name: 'sort_order', label: 'Urutan', type: 'number' },
  ]

  const uploadImage = async (file: File | undefined) => {
    if (!file) return
    const form = new FormData()
    form.append('file', file)
    form.append('kind', file.type === 'application/pdf' ? 'document' : 'image')
    await action.run(async () => {
      const { data } = await adminClient.post<{ data: { url: string | null } }>('/pmb/admin/uploads/images', form)
      if (data.data.url) await navigator.clipboard?.writeText(data.data.url)
      return data.data
    }, (r) => (r.url ? 'Berkas diunggah; URL disalin ke clipboard untuk disisipkan ke isi konten.' : 'Berkas diunggah dan sedang dipindai.'))
  }

  return (
    <AdminPage title="Konten Website PMB" trail={[{ label: 'Konten' }]} actions={
      <Can permission={P.CONTENT_MANAGE}>
        <label className="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-border-strong px-3 py-2 text-sm"><Upload className="size-4" />Unggah gambar/PDF<input type="file" className="hidden" accept="image/jpeg,image/png,application/pdf" onChange={(e) => uploadImage(e.target.files?.[0])} /></label>
        <Button leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Konten baru</Button>
      </Can>
    }>
      <Feedback state={action} />
      <AdminList<Content> path="contents" reloadKey={reload} rowKey={(r) => r.id} itemLabel="konten"
        filters={[{ name: 'type', label: 'Tipe', type: 'select', options: CONTENT_TYPES }, { name: 'status', label: 'Status', type: 'select', options: [{ value: 'draft', label: 'Draf' }, { value: 'published', label: 'Terbit' }, { value: 'archived', label: 'Arsip' }] }]}
        columns={[
          { header: 'Judul', cell: (r) => <button type="button" className="text-left font-medium text-primary" onClick={() => open(r)}>{r.title}<span className="block text-xs text-ink-tertiary">{CONTENT_TYPES.find((t) => t.value === r.type)?.label} · /{r.slug}</span></button> },
          { header: 'Versi', cell: (r) => r.version },
          { header: 'Tayang', cell: (r) => (r.publish_at ? date(r.publish_at) : '—') },
          { header: 'Status', cell: (r) => <StatusBadge status={r.status} /> },
          { header: '', cell: (r) => (
            <Can permission={P.CONTENT_MANAGE}>
              <div className="flex gap-1">
                {r.status !== 'published' ? <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post(`contents/${r.id}/publish`), 'Dipublikasikan.').then(() => setReload(reload + 1))}>Terbitkan</Button>
                  : <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post(`contents/${r.id}/unpublish`), 'Diturunkan.').then(() => setReload(reload + 1))}>Turunkan</Button>}
                {['declaration', 'privacy_policy', 'terms', 'result_template', 'loa_template'].includes(r.type) && r.status === 'published' && <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post(`contents/${r.id}/new-version`), 'Versi baru dibuat (draf).').then(() => setReload(reload + 1))}>Versi baru</Button>}
                <Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus konten?') && action.run(() => pmbAdmin.del(`contents/${r.id}`), 'Dihapus.').then(() => setReload(reload + 1))}><Trash2 className="size-4" /></Button>
              </div>
            </Can>
          ) },
        ]} />
      <FormDialog open={editing !== null} wide title={editing === 'new' ? 'Konten baru' : 'Ubah konten'} fields={fields(editing === 'new')} initial={editing && editing !== 'new' ? (editing as unknown as Record<string, unknown>) : { type: 'news' }} onClose={() => setEditing(null)}
        description={editing && editing !== 'new' && editing.versioned && editing.status === 'published' ? 'Versi terpublikasi tidak dapat diubah; buat versi baru.' : undefined}
        onSubmit={async (v) => {
          if (editing === 'new') await pmbAdmin.post('contents', v)
          else if (editing) await pmbAdmin.patch(`contents/${editing.id}`, v)
          setReload(reload + 1)
        }} />
    </AdminPage>
  )
}

interface ProgramRow { id: string; code: string; name: string; degree_level: string; faculty: { name: string } | null; profile: { slug: string; summary: string | null; description: string | null; accreditation_label: string | null; accreditation_valid_until: string | null; degree_title: string | null; study_duration: string | null; career_prospects: string | null; is_published: boolean; cover_file_id: string | null; brochure_file_id: string | null } | null }

/** ADM-18 Profil prodi (ber-scope fakultas/prodi). */
export function ProgramProfilesPage() {
  const rows = useLoad(() => pmbAdmin.get<ProgramRow[]>('program-profiles'), [])
  const action = useAction()
  const [editing, setEditing] = useState<ProgramRow | null>(null)

  const uploadFile = async (file: File, kind: 'image' | 'document') => {
    const form = new FormData()
    form.append('file', file)
    form.append('kind', kind)
    const { data } = await adminClient.post<{ data: { id: string } }>('/pmb/admin/uploads/images', form)

    return data.data.id
  }

  return (
    <AdminPage title="Profil Program Studi" trail={[{ label: 'Konten' }]}>
      <Feedback state={action} />
      <Card noPadding>
        <DataTable rowKey={(r) => r.id} data={rows.data ?? []} emptyMessage={rows.loading ? 'Memuat...' : 'Tidak ada prodi dalam cakupan Anda.'} columns={[
          { header: 'Program studi', cell: (r) => <span>{r.name}<span className="block text-xs text-ink-tertiary">{r.degree_level} · {r.faculty?.name}</span></span> },
          { header: 'Profil', cell: (r) => (r.profile ? `/${r.profile.slug}` : <Badge variant="warning">Belum diisi</Badge>) },
          { header: 'Status', cell: (r) => (r.profile ? <Badge variant={r.profile.is_published ? 'success' : 'neutral'}>{r.profile.is_published ? 'Tampil' : 'Disembunyikan'}</Badge> : '—') },
          { header: '', cell: (r) => (
            <Can permission={P.PROGRAM_PROFILE_MANAGE}>
              <div className="flex gap-1">
                <Button size="sm" variant="ghost" onClick={() => setEditing(r)}>Ubah</Button>
                {r.profile && <Button size="sm" variant="ghost" onClick={() => action.run(() => pmbAdmin.post(`program-profiles/${r.id}/publish`, { is_published: !r.profile?.is_published }), r.profile?.is_published ? 'Disembunyikan.' : 'Dipublikasikan.').then(rows.reload)}>{r.profile.is_published ? 'Sembunyikan' : 'Publikasikan'}</Button>}
              </div>
            </Can>
          ) },
        ]} />
      </Card>
      <FormDialog open={editing !== null} wide title={`Profil ${editing?.name ?? ''}`} fields={[
        { name: 'slug', label: 'Slug URL' }, { name: 'summary', label: 'Ringkasan (maks 300 karakter)', type: 'textarea' }, { name: 'description', label: 'Deskripsi (HTML)', type: 'textarea' },
        { name: 'accreditation_label', label: 'Akreditasi' }, { name: 'accreditation_valid_until', label: 'Akreditasi berlaku s.d.', type: 'date' },
        { name: 'degree_title', label: 'Gelar' }, { name: 'study_duration', label: 'Lama studi' }, { name: 'career_prospects', label: 'Prospek karier', type: 'textarea' },
        { name: 'cover', label: 'Gambar sampul (JPG/PNG)', type: 'file', accept: 'image/jpeg,image/png' }, { name: 'brochure', label: 'Brosur (PDF)', type: 'file', accept: 'application/pdf' },
      ]} initial={(editing?.profile ?? {}) as unknown as Record<string, unknown>} onClose={() => setEditing(null)} onSubmit={async (v) => {
        if (!editing) return
        const { cover, brochure, ...rest } = v
        const body: Record<string, unknown> = { ...rest }
        if (cover instanceof File) body.cover_file_id = await uploadFile(cover, 'image')
        if (brochure instanceof File) body.brochure_file_id = await uploadFile(brochure, 'document')
        await pmbAdmin.put(`program-profiles/${editing.id}`, body)
        rows.reload()
      }} />
    </AdminPage>
  )
}

interface Setting { key: string; type: string; default: unknown; description: string; value: unknown }

type SettingsTab = 'general' | 'nim' | 'vouchers'

/** ADM-20 Pengaturan PMB, format NIM, voucher. */
export function SettingsPage() {
  const [tab, setTab] = useState<SettingsTab>('general')

  return (
    <AdminPage title="Pengaturan PMB" trail={[{ label: 'Konfigurasi' }]}>
      <Tabs<SettingsTab> active={tab} onChange={setTab} tabs={[{ key: 'general', label: 'Umum' }, { key: 'nim', label: 'Format NIM' }, { key: 'vouchers', label: 'Voucher' }]} />
      {tab === 'general' && <GeneralSettings />}
      {tab === 'nim' && <NimSettings />}
      {tab === 'vouchers' && <Vouchers />}
    </AdminPage>
  )
}

function GeneralSettings() {
  const settings = useLoad(() => pmbAdmin.get<Setting[]>('settings'), [])
  const [values, setValues] = useState<Record<string, unknown>>({})
  const [jsonText, setJsonText] = useState<Record<string, string>>({})
  const action = useAction()

  useEffect(() => {
    if (!settings.data) return
    setValues(Object.fromEntries(settings.data.map((s) => [s.key, s.value])))
    setJsonText(Object.fromEntries(settings.data.filter((s) => s.type === 'json').map((s) => [s.key, JSON.stringify(s.value, null, 2)])))
  }, [settings.data])

  const save = () => action.run(async () => {
    const payload: Record<string, unknown> = {}

    for (const s of settings.data ?? []) {
      payload[s.key] = s.type === 'json' ? JSON.parse(jsonText[s.key] ?? 'null') : s.type === 'integer' ? Number(values[s.key]) : values[s.key]
    }

    await pmbAdmin.put('settings', { values: payload })
  }, 'Pengaturan disimpan.').then(settings.reload)

  return (
    <Card>
      <Feedback state={action} />
      <div className="grid gap-4 md:grid-cols-2">
        {(settings.data ?? []).map((s) => (
          <div key={s.key} className={s.type === 'json' ? 'md:col-span-2' : undefined}>
            {s.type === 'boolean' ? <Checkbox label={s.description} checked={Boolean(values[s.key])} onChange={(e) => setValues({ ...values, [s.key]: e.target.checked })} />
              : s.type === 'json' ? <Textarea label={s.description} hint={s.key} rows={5} className="font-mono text-xs" value={jsonText[s.key] ?? ''} onChange={(e) => setJsonText({ ...jsonText, [s.key]: e.target.value })} />
                : <Input label={s.description} hint={s.key} type={s.type === 'integer' ? 'number' : 'text'} value={String(values[s.key] ?? '')} onChange={(e) => setValues({ ...values, [s.key]: e.target.value })} />}
          </div>
        ))}
      </div>
      <Can permission={P.CONFIG_MANAGE}><Button className="mt-4" isLoading={action.busy} onClick={save}>Simpan pengaturan</Button></Can>
    </Card>
  )
}

function NimSettings() {
  const nim = useLoad(() => pmbAdmin.get<{ format: string; tokens: string[] }>('nim-settings'), [])
  const lookups = useLookups()
  const [format, setFormat] = useState('')
  const [sample, setSample] = useState({ program: '', path: '', system: '' })
  const [preview, setPreview] = useState<{ samples: string[]; sequence_key: string } | null>(null)
  const action = useAction()

  useEffect(() => setFormat(nim.data?.format ?? ''), [nim.data])

  return (
    <Card title="Format NIM" description="Token: {YY} {YYYY} {PRODI} {JALUR} {SISTEM} {SEQ:n} (wajib satu {SEQ:n}). Urutan nomor dihitung per kombinasi token selain SEQ.">
      <Feedback state={action} />
      <div className="flex flex-col gap-3">
        <Input label="Format" value={format} onChange={(e) => setFormat(e.target.value)} />
        <div className="flex flex-wrap gap-1">{(nim.data?.tokens ?? []).map((t) => <button key={t} type="button" className="rounded border border-border px-2 py-0.5 text-xs" onClick={() => setFormat(`${format}{${t === 'SEQ:n' ? 'SEQ:4' : t}}`)}>{`{${t}}`}</button>)}</div>
        <div className="flex flex-wrap gap-3">
          <Select label="Contoh prodi" placeholder="TI" options={(lookups?.study_programs ?? []).map((p) => ({ value: p.code, label: p.name }))} value={sample.program} onChange={(e) => setSample({ ...sample, program: e.target.value })} />
          <Select label="Contoh jalur" placeholder="REG" options={(lookups?.admission_paths ?? []).map((p) => ({ value: p.code, label: p.name }))} value={sample.path} onChange={(e) => setSample({ ...sample, path: e.target.value })} />
          <Select label="Contoh sistem" placeholder="R" options={(lookups?.study_systems ?? []).map((p) => ({ value: p.code, label: p.name }))} value={sample.system} onChange={(e) => setSample({ ...sample, system: e.target.value })} />
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={() => action.run(async () => setPreview(await pmbAdmin.post('nim-settings/preview', { format, program: sample.program || undefined, path: sample.path || undefined, system: sample.system || undefined })))}>Pratinjau</Button>
          <Can permission={P.NIM}><Button isLoading={action.busy} onClick={() => action.run(() => pmbAdmin.put('nim-settings', { format }), 'Format NIM disimpan.').then(nim.reload)}>Simpan</Button></Can>
        </div>
        {preview && <Alert variant="info">Contoh: {preview.samples.join(', ')} · kunci urutan: <code>{preview.sequence_key}</code></Alert>}
      </div>
    </Card>
  )
}

interface Voucher { id: string; code: string; name: string; applies_to: string; discount_type: string; discount_value: string; max_discount: string | null; max_uses: number | null; used_count: number; valid_from: string | null; valid_until: string | null; is_active: boolean; scope: Record<string, unknown> | null }

function Vouchers() {
  const [reload, setReload] = useState(0)
  const [editing, setEditing] = useState<Voucher | 'new' | null>(null)
  const action = useAction()

  return (
    <>
      <Feedback state={action} />
      <AdminList<Voucher> path="vouchers" reloadKey={reload} rowKey={(r) => r.id} itemLabel="voucher" toolbar={<Can permission={P.CONFIG_MANAGE}><Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Voucher</Button></Can>} columns={[
        { header: 'Kode', cell: (r) => <span className="font-mono">{r.code}</span> },
        { header: 'Nama', cell: (r) => r.name },
        { header: 'Potongan', cell: (r) => (r.discount_type === 'percent' ? `${Number(r.discount_value)}%${r.max_discount ? ` (maks ${money(r.max_discount)})` : ''}` : money(r.discount_value)) },
        { header: 'Untuk', cell: (r) => (r.applies_to === 'registration_fee' ? 'Biaya pendaftaran' : 'Biaya awal') },
        { header: 'Pemakaian', cell: (r) => `${r.used_count}${r.max_uses ? ` / ${r.max_uses}` : ''}` },
        { header: 'Berlaku', cell: (r) => `${dateTime(r.valid_from)} – ${dateTime(r.valid_until)}` },
        { header: 'Status', cell: (r) => <Badge variant={r.is_active ? 'success' : 'neutral'}>{r.is_active ? 'Aktif' : 'Nonaktif'}</Badge> },
        { header: '', cell: (r) => <Can permission={P.CONFIG_MANAGE}><div className="flex gap-1"><Button size="sm" variant="ghost" onClick={() => setEditing(r)}>Ubah</Button>{r.used_count === 0 && <Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => action.run(() => pmbAdmin.del(`vouchers/${r.id}`), 'Dihapus.').then(() => setReload(reload + 1))}><Trash2 className="size-4" /></Button>}</div></Can> },
      ]} />
      <FormDialog open={editing !== null} title="Voucher" fields={[
        { name: 'code', label: 'Kode', required: true }, { name: 'name', label: 'Nama', required: true },
        { name: 'applies_to', label: 'Berlaku untuk', type: 'select', required: true, options: [{ value: 'registration_fee', label: 'Biaya pendaftaran' }, { value: 'initial_tuition', label: 'Biaya awal kuliah' }] },
        { name: 'discount_type', label: 'Jenis potongan', type: 'select', required: true, options: [{ value: 'percent', label: 'Persen' }, { value: 'fixed', label: 'Nominal tetap' }] },
        { name: 'discount_value', label: 'Nilai potongan', type: 'number', required: true }, { name: 'max_discount', label: 'Potongan maksimum (Rp)', type: 'number' },
        { name: 'max_uses', label: 'Kuota pemakaian', type: 'number' }, { name: 'valid_from', label: 'Berlaku mulai', type: 'datetime' }, { name: 'valid_until', label: 'Berlaku sampai', type: 'datetime' },
        { name: 'scope', label: 'Batasan (JSON, mis. {"wave_path_ids":["..."]})', type: 'json' }, { name: 'is_active', label: 'Aktif', type: 'checkbox' },
      ]} initial={editing && editing !== 'new' ? (editing as unknown as Record<string, unknown>) : { applies_to: 'registration_fee', discount_type: 'percent', is_active: true }} onClose={() => setEditing(null)} onSubmit={async (v) => {
        if (editing === 'new') await pmbAdmin.post('vouchers', v)
        else if (editing) await pmbAdmin.patch(`vouchers/${editing.id}`, v)
        setReload(reload + 1)
      }} />
    </>
  )
}
