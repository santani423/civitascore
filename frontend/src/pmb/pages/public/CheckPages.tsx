import { useState, type FormEvent } from 'react'
import { Link, useParams } from 'react-router-dom'
import { CheckCircle2, XCircle } from 'lucide-react'
import { pmbPublic } from '@/pmb/api/public'
import { useLoad } from '@/pmb/components/useLoad'
import { Section } from '@/pmb/components/Section'
import { Captcha } from '@/pmb/components/Captcha'
import { Card } from '@/components/ui/Card'
import { Input } from '@/components/ui/Input'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { KeyValue } from '@/pmb/components/KeyValue'
import { DECISION_LABELS } from '@/pmb/lib/labels'
import { date } from '@/pmb/lib/format'
import { PMB } from '@/pmb/routes'
import type { NormalizedApiError } from '@/services/api'

/** PUB-10 Cek status & hasil tanpa login (nomor pendaftaran + tanggal lahir). */
export function StatusCheckPage() {
  const [number, setNumber] = useState('')
  const [birthDate, setBirthDate] = useState('')
  const [captcha, setCaptcha] = useState<string | null>(null)
  const [result, setResult] = useState<Awaited<ReturnType<typeof pmbPublic.statusCheck>> | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  async function submit(event: FormEvent) {
    event.preventDefault()
    setBusy(true)
    setError(null)
    setResult(null)

    try {
      setResult(await pmbPublic.statusCheck({ registration_number: number, birth_date: birthDate, captcha_token: captcha }))
    } catch (err) {
      const e = err as NormalizedApiError
      setError(e.status === 429 ? 'Terlalu banyak percobaan. Coba lagi dalam 1 menit.' : e.status === 404 ? 'Data tidak ditemukan. Periksa nomor pendaftaran dan tanggal lahir.' : e.message)
    } finally {
      setBusy(false)
    }
  }

  return (
    <Section title="Cek Status & Hasil Seleksi" description="Masukkan nomor pendaftaran dan tanggal lahir sesuai data pendaftaran.">
      <div className="grid gap-6 md:grid-cols-2">
        <Card>
          <form onSubmit={submit} className="flex flex-col gap-4">
            <Input label="Nomor pendaftaran" required value={number} onChange={(e) => setNumber(e.target.value.toUpperCase())} placeholder="PMB27-G1-REG-00001" />
            <Input label="Tanggal lahir" type="date" required value={birthDate} onChange={(e) => setBirthDate(e.target.value)} />
            <Captcha onToken={setCaptcha} />
            <Button type="submit" isLoading={busy}>Cek</Button>
          </form>
        </Card>
        <div>
          {error && <Alert variant="danger">{error}</Alert>}
          {result && (
            <Card title="Status pendaftaran">
              <KeyValue columns={1} items={[
                ['Tahap saat ini', result.stage_label],
                ['Hasil seleksi', result.decision_summary ? DECISION_LABELS[result.decision_summary.decision] ?? result.decision_summary.decision : 'Belum diumumkan'],
                ...(result.decision_summary?.program ? [['Program studi', `${result.decision_summary.program} (${result.decision_summary.study_system})`] as [string, string]] : []),
              ]} />
              <Link to={PMB.login} className="mt-4 inline-block text-sm text-primary">Masuk portal untuk detail & langkah berikutnya</Link>
            </Card>
          )}
        </div>
      </div>
    </Section>
  )
}

/** PUB-13 Verifikasi keaslian surat penerimaan (LoA) dari QR. */
export function LoaVerifyPage() {
  const { code = '' } = useParams()
  const { data, error, loading } = useLoad(() => pmbPublic.verifyLetter(code), [code])

  return (
    <Section title="Verifikasi Surat Penerimaan">
      {loading && <p className="text-sm text-ink-secondary">Memeriksa...</p>}
      {error && <Alert variant="danger">Kode verifikasi tidak dikenal. Surat ini tidak diterbitkan oleh sistem PMB kami.</Alert>}
      {data && (
        <Card>
          <div className="mb-4 flex items-center gap-2">
            {data.valid ? <CheckCircle2 className="size-6 text-primary" /> : <XCircle className="size-6 text-danger" />}
            <p className="font-semibold text-ink-primary">{data.valid ? 'Surat asli dan berlaku' : data.revoked ? 'Surat telah DICABUT' : 'Surat tidak berlaku'}</p>
          </div>
          <KeyValue items={[['Nomor surat', data.letter_number], ['Nama', data.name_masked], ['Program studi', data.program], ['Sistem kuliah', data.study_system], ['Tanggal terbit', date(data.issued_at)], ...(data.revoked_at ? [['Dicabut', date(data.revoked_at)] as [string, string]] : [])]} />
        </Card>
      )}
    </Section>
  )
}
