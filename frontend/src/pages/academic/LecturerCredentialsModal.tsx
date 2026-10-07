import { useState } from 'react'
import { Copy, Check } from 'lucide-react'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import type { LecturerAccountCredentials } from '@/types/academic'

/**
 * Menampilkan kredensial login dosen yang baru dibuat / direset. Password
 * hanya dikembalikan backend sekali — tidak bisa dilihat lagi setelah modal
 * ditutup (hanya bisa direset).
 */
export function LecturerCredentialsModal({
  credentials,
  onClose,
}: {
  credentials: LecturerAccountCredentials
  onClose: () => void
}) {
  const [copied, setCopied] = useState(false)

  const copy = async () => {
    const text = `Email: ${credentials.email}\nPassword: ${credentials.password ?? ''}`
    try {
      await navigator.clipboard.writeText(text)
      setCopied(true)
    } catch {
      setCopied(false)
    }
  }

  return (
    <Modal open onClose={onClose} title="Akun Login Dosen" className="max-w-md">
      {credentials.password ? (
        <div className="flex flex-col gap-4">
          <Alert variant="warning">
            Catat dan serahkan kredensial ini kepada dosen. Password tidak dapat ditampilkan lagi setelah jendela ini
            ditutup. Dosen wajib mengganti password saat login pertama.
          </Alert>
          <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 rounded-lg border border-border bg-surface-hover p-4 text-sm">
            <dt className="text-ink-tertiary">Email</dt>
            <dd className="break-all font-medium text-ink-primary">{credentials.email}</dd>
            <dt className="text-ink-tertiary">Password</dt>
            <dd className="break-all font-mono font-medium text-ink-primary">{credentials.password}</dd>
          </dl>
          <div className="flex justify-end gap-2">
            <Button
              variant="outline"
              leftIcon={copied ? <Check className="size-4" /> : <Copy className="size-4" />}
              onClick={copy}
            >
              {copied ? 'Tersalin' : 'Salin'}
            </Button>
            <Button onClick={onClose}>Selesai</Button>
          </div>
        </div>
      ) : (
        <div className="flex flex-col gap-4">
          <Alert variant="info">
            Email <span className="font-medium">{credentials.email}</span> sudah terdaftar sebagai akun pengguna. Dosen
            ditautkan ke akun tersebut dan diberi role Dosen — password lamanya tetap berlaku.
          </Alert>
          <div className="flex justify-end">
            <Button onClick={onClose}>Selesai</Button>
          </div>
        </div>
      )}
    </Modal>
  )
}
