import { formatFileSize } from '@/utils/formatters'

/**
 * Kompresi otomatis di sisi klien untuk berkas yang melebihi batas unggah.
 *
 * Hanya gambar (JPG/PNG/WebP) yang bisa dikompres di browser: gambar
 * diperkecil kualitasnya dulu, lalu resolusinya, sampai muat dalam batas.
 * PDF dan dokumen lain dikembalikan apa adanya bila muat, atau ditolak
 * dengan pesan yang jelas bila tidak.
 */

const COMPRESSIBLE = new Set(['image/jpeg', 'image/png', 'image/webp'])
const EXTENSIONS: Record<string, string[]> = { 'image/jpeg': ['jpg', 'jpeg'], 'image/png': ['png'], 'image/webp': ['webp'] }

/** Sisi terpanjang awal; foto kamera ponsel (4000px+) langsung diperkecil ke sini. */
const MAX_DIMENSION = 2560
/** Sisi terpendek tidak dikecilkan di bawah ini agar teks dokumen tetap terbaca (foto pas PMB minimal 400×600). */
const MIN_SHORT_SIDE = 800
const QUALITIES = [0.85, 0.75, 0.65, 0.55]
const SCALE_STEP = 0.8

export class FileTooLargeError extends Error {
  readonly status = 422

  constructor(message: string) {
    super(message)
    this.name = 'FileTooLargeError'
  }
}

export interface FitFileOptions {
  /** Tipe yang diterima tujuan unggah: MIME (`image/jpeg`) atau ekstensi (`.jpg`, `jpg`), sebagai daftar atau string berkoma seperti atribut `accept`. Kosong = bebas. */
  accept?: string | string[] | null
}

/**
 * Mengembalikan berkas yang muat dalam `maxBytes`. Berkas yang sudah muat
 * dikembalikan apa adanya; gambar yang terlalu besar dikompres otomatis.
 * Melempar {@link FileTooLargeError} bila berkas tetap tidak muat.
 */
export async function fitFileToLimit(file: File, maxBytes: number, options: FitFileOptions = {}): Promise<File> {
  if (file.size <= maxBytes) return file

  const limit = formatFileSize(maxBytes)
  const type = outputType(file, options.accept)

  if (!type) {
    const hint = file.type === 'application/pdf' ? 'Kompres PDF terlebih dahulu' : 'Perkecil berkas terlebih dahulu'

    throw new FileTooLargeError(`Ukuran berkas ${file.name} (${formatFileSize(file.size)}) melebihi batas ${limit}. ${hint}, lalu unggah ulang.`)
  }

  const compressed = await compressImage(file, maxBytes, type).catch(() => null)

  if (!compressed) {
    throw new FileTooLargeError(`Gambar ${file.name} (${formatFileSize(file.size)}) tetap melebihi batas ${limit} setelah dikompres otomatis. Gunakan gambar dengan resolusi lebih kecil.`)
  }

  return compressed
}

/** Keterangan singkat untuk pesan sukses, mis. " (dikompres otomatis 5.2 MB → 1.4 MB)"; kosong bila tidak dikompres. */
export function compressionNote(original: File, result: File): string {
  return original === result ? '' : ` (dikompres otomatis ${formatFileSize(original.size)} → ${formatFileSize(result.size)})`
}

/** JPEG bila tujuan menerimanya (paling efisien); PNG tetap PNG bila hanya PNG yang diterima. */
function outputType(file: File, accept: FitFileOptions['accept']): 'image/jpeg' | 'image/png' | null {
  if (!COMPRESSIBLE.has(file.type)) return null
  if (accepts(accept, 'image/jpeg')) return 'image/jpeg'
  if (file.type === 'image/png' && accepts(accept, 'image/png')) return 'image/png'

  return null
}

function accepts(accept: FitFileOptions['accept'], mime: string): boolean {
  const list = (Array.isArray(accept) ? accept : (accept ?? '').split(',')).map((item) => item.trim().toLowerCase()).filter(Boolean)

  if (list.length === 0) return true

  return list.some((item) => item === mime || item === 'image/*' || EXTENSIONS[mime]?.includes(item.replace(/^\./, '')))
}

async function compressImage(file: File, maxBytes: number, type: 'image/jpeg' | 'image/png'): Promise<File | null> {
  const image = await decode(file)

  try {
    const canvas = document.createElement('canvas')
    const context = canvas.getContext('2d')
    if (!context) return null

    const minScale = Math.min(1, MIN_SHORT_SIDE / Math.min(image.width, image.height))
    let scale = Math.min(1, MAX_DIMENSION / Math.max(image.width, image.height))

    for (;;) {
      canvas.width = Math.max(1, Math.round(image.width * scale))
      canvas.height = Math.max(1, Math.round(image.height * scale))

      // JPEG tidak punya transparansi — latar putih supaya area transparan PNG tidak jadi hitam.
      if (type === 'image/jpeg') {
        context.fillStyle = '#ffffff'
        context.fillRect(0, 0, canvas.width, canvas.height)
      }

      context.imageSmoothingQuality = 'high'
      context.drawImage(image.source, 0, 0, canvas.width, canvas.height)

      // PNG lossless: hanya resolusi yang bisa diturunkan.
      for (const quality of type === 'image/png' ? [undefined] : QUALITIES) {
        const blob = await toBlob(canvas, type, quality)

        if (blob && blob.size <= maxBytes) {
          return new File([blob], renameExtension(file.name, type), { type, lastModified: Date.now() })
        }
      }

      if (scale <= minScale) return null
      scale = Math.max(minScale, scale * SCALE_STEP)
    }
  } finally {
    image.release()
  }
}

interface DecodedImage {
  source: CanvasImageSource
  width: number
  height: number
  release: () => void
}

/** Decode dengan orientasi EXIF diterapkan, supaya foto ponsel tidak terputar setelah dikompres. */
async function decode(file: File): Promise<DecodedImage> {
  if (typeof createImageBitmap === 'function') {
    try {
      const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' })

      return { source: bitmap, width: bitmap.width, height: bitmap.height, release: () => bitmap.close() }
    } catch {
      // Browser lama/format tak didukung createImageBitmap — coba lewat <img>.
    }
  }

  const url = URL.createObjectURL(file)

  try {
    const img = new Image()
    img.src = url
    await img.decode()

    return { source: img, width: img.naturalWidth, height: img.naturalHeight, release: () => URL.revokeObjectURL(url) }
  } catch (err) {
    URL.revokeObjectURL(url)
    throw err
  }
}

function toBlob(canvas: HTMLCanvasElement, type: string, quality?: number): Promise<Blob | null> {
  return new Promise((resolve) => canvas.toBlob(resolve, type, quality))
}

function renameExtension(name: string, type: 'image/jpeg' | 'image/png'): string {
  const base = name.replace(/\.[^./\\]+$/, '') || 'gambar'

  return `${base}.${type === 'image/jpeg' ? 'jpg' : 'png'}`
}
