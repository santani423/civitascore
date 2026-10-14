import { useEffect, useState } from 'react'
import { Pencil, Plus, Trash2 } from 'lucide-react'
import { pmbApplicant, type Achievement, type Education, type Guardian } from '@/pmb/api/applicant'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { FormDialog, type FieldDef } from '@/pmb/components/FormDialog'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import type { NormalizedApiError } from '@/services/api'

const opts = (pairs: Array<[string, string]>) => pairs.map(([value, label]) => ({ value, label }))

export const IDENTITY_FIELDS: Array<FieldDef & { core: string }> = [
  { core: 'full_name', name: 'full_name', label: 'Nama lengkap (sesuai ijazah)' },
  { core: 'nationality', name: 'nationality', label: 'Kewarganegaraan', type: 'select', options: opts([['ID', 'Indonesia (WNI)'], ['MY', 'Malaysia'], ['TL', 'Timor Leste'], ['SG', 'Singapura'], ['XX', 'Lainnya (WNA)']]) },
  { core: 'nik', name: 'nik', label: 'NIK (16 digit)', visible: (v) => (v.nationality ?? 'ID') === 'ID' },
  { core: 'passport_number', name: 'passport_number', label: 'Nomor paspor', visible: (v) => Boolean(v.nationality) && v.nationality !== 'ID' },
  { core: 'passport_country', name: 'passport_country', label: 'Negara penerbit paspor (kode 2 huruf)', visible: (v) => Boolean(v.nationality) && v.nationality !== 'ID' },
  { core: 'birth_place', name: 'birth_place', label: 'Tempat lahir' },
  { core: 'birth_date', name: 'birth_date', label: 'Tanggal lahir', type: 'date' },
  { core: 'gender', name: 'gender', label: 'Jenis kelamin', type: 'select', options: opts([['male', 'Laki-laki'], ['female', 'Perempuan']]) },
  { core: 'religion', name: 'religion', label: 'Agama', type: 'select', options: opts([['islam', 'Islam'], ['kristen', 'Kristen'], ['katolik', 'Katolik'], ['hindu', 'Hindu'], ['buddha', 'Buddha'], ['konghucu', 'Konghucu'], ['lainnya', 'Lainnya']]) },
  { core: 'address_line', name: 'address_line', label: 'Alamat (jalan, nomor)' },
  { core: 'rt', name: 'rt', label: 'RT' },
  { core: 'rw', name: 'rw', label: 'RW' },
  { core: 'village', name: 'village', label: 'Kelurahan/Desa' },
  { core: 'district', name: 'district', label: 'Kecamatan' },
  { core: 'city', name: 'city', label: 'Kabupaten/Kota' },
  { core: 'province', name: 'province', label: 'Provinsi' },
  { core: 'postal_code', name: 'postal_code', label: 'Kode pos' },
  { core: 'special_needs_note', name: 'special_needs_note', label: 'Kebutuhan khusus (opsional, untuk akomodasi ujian)', type: 'textarea' },
]

/**
 * Form identitas (D.2.1). Hanya field yang diubah yang dikirim — nilai
 * sensitif (NIK) datang tersamar dan tidak boleh dikirim balik.
 */
export function IdentityForm({ requirements, editable, onSave }: { requirements?: Record<string, string>; editable?: string[] | null; onSave: (data: Record<string, unknown>, lockVersion: number) => Promise<unknown> }) {
  const profile = useLoad(() => pmbApplicant.profile(), [])
  const [values, setValues] = useState<Record<string, string>>({})
  const [dirty, setDirty] = useState<Record<string, boolean>>({})
  const [errors, setErrors] = useState<Record<string, string>>({})
  const action = useAction()

  useEffect(() => {
    if (!profile.data) return
    const start: Record<string, string> = {}
    for (const f of IDENTITY_FIELDS) start[f.name] = profile.data[f.name] === null || profile.data[f.name] === undefined ? '' : String(profile.data[f.name])
    if (!start.nationality) start.nationality = 'ID'
    setValues(start)
    setDirty({})
  }, [profile.data])

  const locked = new Set(profile.data?.locked_fields ?? [])
  const canEdit = (name: string) => !locked.has(name) && (editable === undefined || editable === null || editable.includes(name))

  const save = async () => {
    setErrors({})
    const data = Object.fromEntries(Object.entries(values).filter(([k]) => dirty[k]).map(([k, v]) => [k, v === '' ? null : v]))

    try {
      await action.run(async () => {
        try {
          return await onSave(data, profile.data?.lock_version ?? 0)
        } catch (err) {
          const e = err as NormalizedApiError
          setErrors(Object.fromEntries(Object.entries(e.errors ?? {}).filter(([, v]) => Array.isArray(v)).map(([k, v]) => [k, (v as string[])[0]])))
          throw err
        }
      }, 'Data diri disimpan.')
    } finally {
      profile.reload()
    }
  }

  if (profile.loading && !profile.data) return <p className="text-sm text-ink-secondary">Memuat...</p>

  return (
    <div className="flex flex-col gap-4">
      <Feedback state={action} />
      {profile.data?.identity_locked && <Alert variant="info">Identitas terkunci setelah pendaftaran terkirim. Hubungi panitia untuk koreksi.</Alert>}
      {profile.data?.nik_warning && <Alert variant="warning">{profile.data.nik_warning}</Alert>}
      <div className="grid gap-3 sm:grid-cols-2">
        {IDENTITY_FIELDS.filter((f) => !f.visible || f.visible(values)).filter((f) => requirements?.[f.core] !== 'hidden').map((f) => {
          const required = requirements?.[f.core] === 'required' || ['full_name', 'birth_place', 'birth_date', 'address_line', 'city', 'province'].includes(f.name)
          const props = { label: f.label + (required ? ' *' : ''), error: errors[f.name], disabled: !canEdit(f.name), value: values[f.name] ?? '' }
          const onChange = (value: string) => {
            setValues({ ...values, [f.name]: value })
            setDirty({ ...dirty, [f.name]: true })
          }

          return f.type === 'select' ? <Select key={f.name} {...props} options={f.options ?? []} placeholder="Pilih..." onChange={(e) => onChange(e.target.value)} />
            : <Input key={f.name} {...props} type={f.type === 'date' ? 'date' : 'text'} onFocus={() => f.name === 'nik' && !dirty.nik && onChange('')} onChange={(e) => onChange(e.target.value)} />
        })}
      </div>
      <div><Button onClick={save} isLoading={action.busy} disabled={Object.keys(dirty).length === 0}>Simpan data diri</Button></div>
    </div>
  )
}

const EDUCATION_FIELDS: FieldDef[] = [
  { name: 'level', label: 'Jenjang', type: 'select', required: true, options: opts([['secondary', 'SMA/SMK/MA sederajat'], ['diploma', 'Diploma'], ['bachelor', 'Sarjana'], ['master', 'Magister']]) },
  { name: 'institution_name', label: 'Nama sekolah/perguruan tinggi', required: true },
  { name: 'institution_npsn', label: 'NPSN (8 digit, sekolah)' },
  { name: 'school_type', label: 'Jenis sekolah', type: 'select', options: opts([['sma', 'SMA'], ['smk', 'SMK'], ['ma', 'MA'], ['paket_c', 'Paket C'], ['luar_negeri', 'Luar negeri'], ['lainnya', 'Lainnya']]), visible: (v) => v.level === 'secondary' },
  { name: 'major', label: 'Jurusan' },
  { name: 'graduation_year', label: 'Tahun lulus', type: 'number' },
  { name: 'is_graduating', label: 'Masih kelas akhir (belum lulus)', type: 'checkbox' },
  { name: 'nisn', label: 'NISN (10 digit)', visible: (v) => v.level === 'secondary' },
  { name: 'previous_student_id', label: 'NIM asal', visible: (v) => v.level !== 'secondary' },
  { name: 'gpa', label: 'IPK', type: 'number', visible: (v) => v.level !== 'secondary' },
  { name: 'credits_earned', label: 'SKS ditempuh', type: 'number', visible: (v) => v.level !== 'secondary' },
  { name: 'city', label: 'Kota' },
  { name: 'province', label: 'Provinsi' },
]

/** Riwayat pendidikan + nilai rapor (D.2.2). `selectable` = pilih pendidikan utama untuk aplikasi. */
export function EducationManager({ selectedId, onSelect }: { selectedId?: string | null; onSelect?: (id: string) => void }) {
  const list = useLoad(() => pmbApplicant.educations(), [])
  const action = useAction()
  const [editing, setEditing] = useState<Education | 'new' | null>(null)
  const [scoresFor, setScoresFor] = useState<Education | null>(null)

  return (
    <div className="flex flex-col gap-3">
      <Feedback state={action} />
      {(list.data ?? []).map((e) => (
        <div key={e.id} className="flex flex-wrap items-start justify-between gap-3 rounded-lg border border-border p-3">
          <div>
            <p className="text-sm font-medium text-ink-primary">{e.institution_name} {selectedId === e.id && <Badge variant="success">Dipakai untuk pendaftaran</Badge>}</p>
            <p className="text-xs text-ink-secondary">{e.level} · {e.major ?? '—'} · lulus {e.graduation_year ?? '—'}{e.is_graduating ? ' (kelas akhir)' : ''}</p>
            {e.report_scores.length > 0 && <p className="text-xs text-ink-tertiary">Nilai rapor: {new Set(e.report_scores.map((s) => s.semester)).size} semester</p>}
          </div>
          <div className="flex flex-wrap gap-1">
            {onSelect && selectedId !== e.id && <Button size="sm" onClick={() => onSelect(e.id)}>Pilih</Button>}
            {e.level === 'secondary' && <Button size="sm" variant="outline" onClick={() => setScoresFor(e)}>Nilai rapor</Button>}
            <Button size="sm" variant="ghost" aria-label="Ubah" onClick={() => setEditing(e)}><Pencil className="size-4" /></Button>
            <Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus riwayat pendidikan ini?') && action.run(() => pmbApplicant.destroyEducation(e.id), 'Dihapus.').then(list.reload)}><Trash2 className="size-4" /></Button>
          </div>
        </div>
      ))}
      <div><Button variant="outline" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Tambah riwayat pendidikan</Button></div>
      <FormDialog
        open={editing !== null}
        title={editing === 'new' ? 'Tambah pendidikan' : 'Ubah pendidikan'}
        fields={EDUCATION_FIELDS}
        initial={editing && editing !== 'new' ? (editing as unknown as Record<string, unknown>) : { level: 'secondary' }}
        onClose={() => setEditing(null)}
        onSubmit={async (values) => {
          if (editing === 'new') await pmbApplicant.storeEducation(values)
          else if (editing) await pmbApplicant.updateEducation(editing.id, values)
          list.reload()
        }}
      />
      {scoresFor && <ReportScoresEditor education={scoresFor} onClose={() => setScoresFor(null)} onSaved={list.reload} />}
    </div>
  )
}

const DEFAULT_SUBJECTS = [['MTK', 'Matematika'], ['BIN', 'Bahasa Indonesia'], ['BIG', 'Bahasa Inggris']]

function ReportScoresEditor({ education, onClose, onSaved }: { education: Education; onClose: () => void; onSaved: () => void }) {
  const initialSubjects = education.report_scores.length > 0 ? [...new Map(education.report_scores.map((s) => [s.subject_code, s.subject_name ?? s.subject_code])).entries()] : DEFAULT_SUBJECTS.map(([c, n]) => [c, n] as [string, string])
  const [subjects, setSubjects] = useState<Array<[string, string]>>(initialSubjects as Array<[string, string]>)
  const [scores, setScores] = useState<Record<string, string>>(Object.fromEntries(education.report_scores.map((s) => [`${s.semester}|${s.subject_code}`, String(Number(s.score))])))
  const [newSubject, setNewSubject] = useState('')
  const action = useAction()
  const semesters = [1, 2, 3, 4, 5]

  const save = () => action.run(async () => {
    const rows = subjects.flatMap(([code, name]) => semesters.filter((sem) => scores[`${sem}|${code}`] !== undefined && scores[`${sem}|${code}`] !== '').map((sem) => ({ semester: sem, subject_code: code, subject_name: name, score: Number(scores[`${sem}|${code}`]) })))
    await pmbApplicant.reportScores(education.id, rows)
    onSaved()
    onClose()
  })

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <Card title={`Nilai rapor — ${education.institution_name}`} className="max-h-[90vh] w-full max-w-3xl overflow-y-auto">
        <Feedback state={action} />
        <p className="mb-3 text-sm text-ink-secondary">Isi nilai semester 1–5 (skala 0–100) sesuai rapor.</p>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead><tr><th className="p-1 text-left">Mata pelajaran</th>{semesters.map((s) => <th key={s} className="p-1">Smt {s}</th>)}<th /></tr></thead>
            <tbody>
              {subjects.map(([code, name]) => (
                <tr key={code}>
                  <td className="p-1">{name}</td>
                  {semesters.map((sem) => <td key={sem} className="p-1"><input type="number" min={0} max={100} step="0.01" className="w-16 rounded border border-border bg-surface px-1 py-0.5" value={scores[`${sem}|${code}`] ?? ''} onChange={(e) => setScores({ ...scores, [`${sem}|${code}`]: e.target.value })} /></td>)}
                  <td><button type="button" aria-label="Hapus" onClick={() => setSubjects(subjects.filter(([c]) => c !== code))}><Trash2 className="size-4 text-ink-tertiary" /></button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="mt-3 flex items-end gap-2">
          <Input label="Tambah mata pelajaran" value={newSubject} onChange={(e) => setNewSubject(e.target.value)} />
          <Button variant="outline" disabled={!newSubject.trim()} onClick={() => { setSubjects([...subjects, [newSubject.trim().toUpperCase().replace(/[^A-Z0-9]+/g, '_').slice(0, 30), newSubject.trim()]]); setNewSubject('') }}>Tambah</Button>
        </div>
        <div className="mt-4 flex justify-end gap-2"><Button variant="outline" onClick={onClose}>Batal</Button><Button onClick={save} isLoading={action.busy}>Simpan nilai</Button></div>
      </Card>
    </div>
  )
}

const GUARDIAN_FIELDS: FieldDef[] = [
  { name: 'relationship', label: 'Hubungan', type: 'select', required: true, options: opts([['father', 'Ayah'], ['mother', 'Ibu'], ['guardian', 'Wali'], ['emergency_contact', 'Kontak darurat']]) },
  { name: 'full_name', label: 'Nama lengkap', required: true },
  { name: 'phone', label: 'Nomor HP' },
  { name: 'email', label: 'Email', type: 'email' },
  { name: 'nik', label: 'NIK (opsional)' },
  { name: 'birth_date', label: 'Tanggal lahir', type: 'date' },
  { name: 'education_level', label: 'Pendidikan terakhir' },
  { name: 'occupation', label: 'Pekerjaan' },
  { name: 'income_range', label: 'Penghasilan per bulan', type: 'select', options: opts([['none', 'Tidak berpenghasilan'], ['lt_1m', '< Rp1 juta'], ['1m_3m', 'Rp1–3 juta'], ['3m_5m', 'Rp3–5 juta'], ['5m_10m', 'Rp5–10 juta'], ['gt_10m', '> Rp10 juta']]) },
  { name: 'is_deceased', label: 'Sudah meninggal', type: 'checkbox' },
]

export function GuardianManager() {
  const list = useLoad(() => pmbApplicant.guardians(), [])
  const action = useAction()
  const [editing, setEditing] = useState<Guardian | 'new' | null>(null)
  const label: Record<string, string> = { father: 'Ayah', mother: 'Ibu', guardian: 'Wali', emergency_contact: 'Kontak darurat' }

  return (
    <div className="flex flex-col gap-3">
      <Feedback state={action} />
      {(list.data ?? []).map((g) => (
        <div key={g.id} className="flex items-start justify-between gap-3 rounded-lg border border-border p-3">
          <div><p className="text-sm font-medium text-ink-primary">{g.full_name} <Badge>{label[g.relationship] ?? g.relationship}</Badge></p><p className="text-xs text-ink-secondary">{g.phone ?? 'tanpa nomor HP'} · {g.occupation ?? '—'}</p></div>
          <div className="flex gap-1">
            <Button size="sm" variant="ghost" aria-label="Ubah" onClick={() => setEditing(g)}><Pencil className="size-4" /></Button>
            <Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus data ini?') && action.run(() => pmbApplicant.destroyGuardian(g.id), 'Dihapus.').then(list.reload)}><Trash2 className="size-4" /></Button>
          </div>
        </div>
      ))}
      <div><Button variant="outline" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Tambah orang tua/wali</Button></div>
      <FormDialog
        open={editing !== null}
        title="Orang tua / wali"
        fields={GUARDIAN_FIELDS.map((f) => (f.name === 'nik' && editing !== 'new' ? { ...f, hint: 'Kosongkan bila tidak diubah.' } : f))}
        initial={editing && editing !== 'new' ? { ...editing, nik: '' } as unknown as Record<string, unknown> : undefined}
        onClose={() => setEditing(null)}
        onSubmit={async (values) => {
          const body = { ...values }
          if (!body.nik) delete body.nik
          if (editing === 'new') await pmbApplicant.storeGuardian(body)
          else if (editing) await pmbApplicant.updateGuardian(editing.id, body)
          list.reload()
        }}
      />
    </div>
  )
}

const ACHIEVEMENT_FIELDS: FieldDef[] = [
  { name: 'name', label: 'Nama prestasi/lomba', required: true },
  { name: 'category', label: 'Kategori', type: 'select', required: true, options: opts([['academic', 'Akademik'], ['non_academic', 'Non-akademik']]) },
  { name: 'level', label: 'Tingkat', type: 'select', required: true, options: opts([['school', 'Sekolah'], ['district', 'Kabupaten/Kota'], ['province', 'Provinsi'], ['national', 'Nasional'], ['international', 'Internasional']]) },
  { name: 'rank', label: 'Peringkat', type: 'select', required: true, options: opts([['1', 'Juara 1'], ['2', 'Juara 2'], ['3', 'Juara 3'], ['finalist', 'Finalis'], ['participant', 'Peserta']]) },
  { name: 'year', label: 'Tahun', type: 'number', required: true },
  { name: 'organizer', label: 'Penyelenggara' },
]

export function AchievementManager() {
  const list = useLoad(() => pmbApplicant.achievements(), [])
  const action = useAction()
  const [editing, setEditing] = useState<Achievement | 'new' | null>(null)

  return (
    <div className="flex flex-col gap-3">
      <Feedback state={action} />
      <p className="text-xs text-ink-secondary">Unggah sertifikat setiap prestasi pada langkah Dokumen formulir pendaftaran.</p>
      {(list.data ?? []).map((a) => (
        <div key={a.id} className="flex items-start justify-between gap-3 rounded-lg border border-border p-3">
          <div><p className="text-sm font-medium text-ink-primary">{a.name}</p><p className="text-xs text-ink-secondary">{a.level} · {a.rank} · {a.year}{a.document ? ` · sertifikat ${a.document.status}` : ''}</p></div>
          <div className="flex gap-1">
            <Button size="sm" variant="ghost" aria-label="Ubah" onClick={() => setEditing(a)}><Pencil className="size-4" /></Button>
            <Button size="sm" variant="ghost" aria-label="Hapus" onClick={() => window.confirm('Hapus prestasi ini?') && action.run(() => pmbApplicant.destroyAchievement(a.id), 'Dihapus.').then(list.reload)}><Trash2 className="size-4" /></Button>
          </div>
        </div>
      ))}
      <div><Button variant="outline" leftIcon={<Plus className="size-4" />} onClick={() => setEditing('new')}>Tambah prestasi</Button></div>
      <FormDialog open={editing !== null} title="Prestasi" fields={ACHIEVEMENT_FIELDS} initial={editing && editing !== 'new' ? (editing as unknown as Record<string, unknown>) : { year: new Date().getFullYear() }} onClose={() => setEditing(null)} onSubmit={async (values) => {
        if (editing === 'new') await pmbApplicant.storeAchievement(values)
        else if (editing) await pmbApplicant.updateAchievement(editing.id, values)
        list.reload()
      }} />
    </div>
  )
}

