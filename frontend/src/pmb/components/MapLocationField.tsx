import { useEffect, useRef, useState, type KeyboardEvent } from 'react'
import type { Map as LeafletMap, Marker } from 'leaflet'
import { Crosshair, ExternalLink, Search, X } from 'lucide-react'
import { Input } from '@/components/ui/Input'
import { Textarea } from '@/components/ui/Textarea'
import { Button } from '@/components/ui/Button'
import { cn } from '@/utils/cn'

type LatLng = { lat: number; lng: number }
type Mode = 'pick' | 'embed'
type LeafletModule = typeof import('leaflet')

/** Batas kolom `pmb_exam_venues.map_url` (ExamController::validateVenue). */
const MAX_LENGTH = 500
const DEFAULT_VIEW = { lat: -2.5, lng: 118, zoom: 5 } // seluruh Indonesia

/** URL Google Maps satu titik — dapat dibuka sebagai tautan dan dipratinjau sebagai embed. */
function pointUrl({ lat, lng }: LatLng): string {
  return `https://www.google.com/maps?q=${lat.toFixed(6)},${lng.toFixed(6)}`
}

function safeDecode(text: string): string {
  try {
    return decodeURIComponent(text)
  } catch {
    return text
  }
}

function toLatLng(lat: string, lng: string): LatLng | null {
  const point = { lat: Number(lat), lng: Number(lng) }

  return Number.isFinite(point.lat) && Number.isFinite(point.lng) && Math.abs(point.lat) <= 90 && Math.abs(point.lng) <= 180 ? point : null
}

/** Koordinat dari URL Google Maps: `q=lat,lng`, `@lat,lng`, atau embed `!2d<lng>!3d<lat>`. */
function parseCoordinates(url: string): LatLng | null {
  const text = safeDecode(url)
  const query = text.match(/[?&](?:q|query|ll)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/)
  if (query) return toLatLng(query[1], query[2])
  const at = text.match(/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/)
  if (at) return toLatLng(at[1], at[2])
  const embed = text.match(/!2d(-?\d+(?:\.\d+)?)!3d(-?\d+(?:\.\d+)?)/)

  return embed ? toLatLng(embed[2], embed[1]) : null
}

function isGoogleMaps(url: URL): boolean {
  return /^maps\.google\.[a-z.]+$/.test(url.hostname) || (/(^|\.)google\.[a-z.]+$/.test(url.hostname) && url.pathname.startsWith('/maps'))
}

/** Kode embed (`<iframe src="…">`) atau tautan → URL yang disimpan. */
function normalizeMapInput(input: string): { url: string; error?: undefined } | { url?: undefined; error: string } {
  const text = input.trim()
  if (!text) return { url: '' }
  const src = text.startsWith('<') ? text.match(/\bsrc\s*=\s*["']([^"']+)["']/i)?.[1] : text
  if (!src) return { error: 'Atribut src tidak ditemukan pada kode embed.' }

  let parsed: URL

  try {
    parsed = new URL(src.replace(/&amp;/g, '&'))
  } catch {
    return { error: 'Bukan kode embed atau tautan yang valid.' }
  }

  if (parsed.protocol !== 'https:' && parsed.protocol !== 'http:') return { error: 'Tautan harus diawali http:// atau https://.' }
  const url = parsed.toString()
  if (url.length <= MAX_LENGTH) return { url }

  // URL embed yang terlalu panjang dipadatkan menjadi titik koordinatnya.
  const point = parseCoordinates(url)

  return point ? { url: pointUrl(point) } : { error: `Tautan lebih dari ${MAX_LENGTH} karakter.` }
}

/** URL iframe untuk pratinjau; null bila tautan bukan Google Maps. */
function embedUrl(value: string): string | null {
  let parsed: URL

  try {
    parsed = new URL(value)
  } catch {
    return null
  }

  if (!isGoogleMaps(parsed)) return null
  if (parsed.pathname.startsWith('/maps/embed')) return parsed.toString()
  const point = parseCoordinates(value)
  const query = point ? `${point.lat},${point.lng}` : parsed.searchParams.get('q') ?? parsed.searchParams.get('query')

  return query ? `https://maps.google.com/maps?q=${encodeURIComponent(query)}&z=16&output=embed` : null
}

function modeFor(value: string): Mode {
  return value && (value.includes('/maps/embed') || !parseCoordinates(value)) ? 'embed' : 'pick'
}

export interface MapLocationFieldProps {
  label: string
  value: string
  onChange: (url: string) => void
  error?: string
}

/**
 * Isian lokasi peta: pilih titik langsung di peta (OpenStreetMap, tanpa API key)
 * atau tempel kode embed/tautan Google Maps. Keduanya disimpan sebagai satu URL.
 */
export function MapLocationField({ label, value, onChange, error }: MapLocationFieldProps) {
  const [mode, setMode] = useState<Mode>(() => modeFor(value))
  const chosen = useRef(false)
  const [draft, setDraft] = useState<string | null>(null)
  const [draftError, setDraftError] = useState<string | null>(null)
  const point = parseCoordinates(value)
  const preview = embedUrl(value)

  // FormDialog mengisi nilai awal setelah mount — ikuti selama pengguna belum memilih mode.
  useEffect(() => {
    if (!chosen.current) setMode(modeFor(value))
  }, [value])

  const choose = (next: Mode) => {
    chosen.current = true
    setMode(next)
  }

  const changeDraft = (text: string) => {
    setDraft(text)
    const result = normalizeMapInput(text)
    setDraftError(result.error ?? null)
    // Masukan tidak valid tetap diteruskan apa adanya agar validasi server menolaknya.
    onChange(result.url ?? text)
  }

  return (
    <div className="flex flex-col gap-2">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <span className="text-sm font-medium text-ink-primary">{label}</span>
        <div className="inline-flex rounded-lg border border-border-strong p-0.5 text-sm" role="tablist">
          {([['pick', 'Pilih di peta'], ['embed', 'Kode embed Google Maps']] as const).map(([key, text]) => (
            <button key={key} type="button" role="tab" aria-selected={mode === key} onClick={() => choose(key)}
              className={cn('rounded-md px-3 py-1 transition-colors', mode === key ? 'bg-primary text-white' : 'text-ink-secondary hover:bg-surface-hover')}>
              {text}
            </button>
          ))}
        </div>
      </div>

      {mode === 'pick' ? (
        <PickerMap point={point} onPick={(p) => onChange(pointUrl(p))} />
      ) : (
        <>
          <Textarea rows={3} className="font-mono text-xs" value={draft ?? value} onChange={(e) => changeDraft(e.target.value)}
            placeholder={'<iframe src="https://www.google.com/maps/embed?pb=..." width="600" height="450" ...></iframe>'}
            error={draftError ?? undefined} hint="Di Google Maps: Bagikan → Sematkan peta → Salin HTML, lalu tempel di sini. Tautan Google Maps biasa juga diterima." />
          {preview && <iframe title="Pratinjau peta" src={preview} className="h-56 w-full rounded-lg border border-border" loading="lazy" referrerPolicy="no-referrer-when-downgrade" />}
        </>
      )}

      {value && (
        <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-tertiary">
          <span className="min-w-0 truncate">{point && mode === 'pick' ? `Titik: ${point.lat.toFixed(6)}, ${point.lng.toFixed(6)}` : 'Lokasi tersimpan'}</span>
          <a href={value} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-1 text-primary hover:underline">Buka di Google Maps<ExternalLink className="size-3" /></a>
          <button type="button" className="inline-flex items-center gap-1 text-danger hover:underline" onClick={() => { setDraft(null); setDraftError(null); onChange('') }}><X className="size-3" />Hapus lokasi</button>
        </div>
      )}
      {error && <p className="text-xs text-danger">{error}</p>}
    </div>
  )
}

const PIN_HTML = '<svg viewBox="0 0 24 32" width="28" height="36" class="text-primary drop-shadow"><path fill="currentColor" d="M12 0C5.4 0 0 5.4 0 12c0 9 12 20 12 20s12-11 12-20C24 5.4 18.6 0 12 0z"/><circle cx="12" cy="12" r="4.5" fill="#fff"/></svg>'

interface Place { place_id: number; display_name: string; lat: string; lon: string }

/** Peta Leaflet (dimuat saat dibutuhkan): klik/seret penanda, cari tempat, atau pakai lokasi perangkat. */
function PickerMap({ point, onPick }: { point: LatLng | null; onPick: (point: LatLng) => void }) {
  const container = useRef<HTMLDivElement>(null)
  const leaflet = useRef<LeafletModule | null>(null)
  const map = useRef<LeafletMap | null>(null)
  const marker = useRef<Marker | null>(null)
  const onPickRef = useRef(onPick)
  onPickRef.current = onPick
  const initial = useRef(point)
  const [ready, setReady] = useState(false)
  const [loadError, setLoadError] = useState(false)
  const [query, setQuery] = useState('')
  const [places, setPlaces] = useState<Place[]>([])
  const [searching, setSearching] = useState(false)
  const [notice, setNotice] = useState<string | null>(null)

  useEffect(() => {
    let cancelled = false
    let timer: ReturnType<typeof setTimeout> | undefined

    Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')])
      .then(([mod]) => {
        if (cancelled || !container.current) return
        const L = (mod as unknown as { default?: LeafletModule }).default ?? mod
        const start = initial.current
        const instance = L.map(container.current).setView(start ? [start.lat, start.lng] : [DEFAULT_VIEW.lat, DEFAULT_VIEW.lng], start ? 16 : DEFAULT_VIEW.zoom)
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>' }).addTo(instance)
        instance.on('click', (e) => onPickRef.current({ lat: e.latlng.lat, lng: e.latlng.lng }))
        leaflet.current = L
        map.current = instance
        setReady(true)
        // Modal masih beranimasi saat peta dibuat — hitung ulang ukuran kontainer.
        timer = setTimeout(() => instance.invalidateSize(), 250)
      })
      .catch(() => setLoadError(true))

    return () => {
      cancelled = true
      clearTimeout(timer)
      map.current?.remove()
      map.current = null
      marker.current = null
    }
  }, [])

  useEffect(() => {
    const L = leaflet.current
    const instance = map.current
    if (!ready || !L || !instance) return

    if (!point) {
      marker.current?.remove()
      marker.current = null

      return
    }

    if (marker.current) {
      marker.current.setLatLng([point.lat, point.lng])
    } else {
      const created = L.marker([point.lat, point.lng], { draggable: true, icon: L.divIcon({ className: '', html: PIN_HTML, iconSize: [28, 36], iconAnchor: [14, 36] }) }).addTo(instance)
      created.on('dragend', () => {
        const at = created.getLatLng()
        onPickRef.current({ lat: at.lat, lng: at.lng })
      })
      marker.current = created
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [ready, point?.lat, point?.lng])

  const focus = (target: LatLng) => {
    map.current?.setView([target.lat, target.lng], 17)
    onPick(target)
  }

  async function search() {
    const q = query.trim()
    if (!q) return
    setSearching(true)
    setNotice(null)

    try {
      const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&accept-language=id&q=${encodeURIComponent(q)}`)
      if (!response.ok) throw new Error(String(response.status))
      const rows = (await response.json()) as Place[]
      setPlaces(rows)
      if (rows.length === 0) setNotice('Tempat tidak ditemukan. Coba kata kunci lain atau klik langsung di peta.')
    } catch {
      setNotice('Pencarian gagal. Klik langsung di peta untuk menentukan titik.')
    } finally {
      setSearching(false)
    }
  }

  const onSearchKey = (event: KeyboardEvent<HTMLInputElement>) => {
    // Enter di sini jangan mengirim formulir induk.
    if (event.key === 'Enter') {
      event.preventDefault()
      void search()
    }
  }

  const locate = () => {
    if (!navigator.geolocation) return setNotice('Peramban tidak mendukung lokasi perangkat.')
    navigator.geolocation.getCurrentPosition(
      (pos) => focus({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
      () => setNotice('Lokasi perangkat tidak dapat diakses.'),
      { enableHighAccuracy: true, timeout: 10000 },
    )
  }

  if (loadError) return <p className="rounded-lg border border-border p-4 text-sm text-danger">Peta gagal dimuat. Gunakan tab “Kode embed Google Maps”.</p>

  return (
    <div className="flex flex-col gap-2">
      <div className="flex gap-2">
        <div className="min-w-0 flex-1">
          <Input value={query} onChange={(e) => setQuery(e.target.value)} onKeyDown={onSearchKey} placeholder="Cari tempat, mis. Unika Atma Jaya Semanggi" aria-label="Cari tempat" />
        </div>
        <Button type="button" variant="outline" isLoading={searching} leftIcon={<Search className="size-4" />} onClick={() => void search()}>Cari</Button>
        <Button type="button" variant="outline" aria-label="Gunakan lokasi saya" title="Gunakan lokasi saya" onClick={locate}><Crosshair className="size-4" /></Button>
      </div>
      {places.length > 0 && (
        <ul className="max-h-40 overflow-y-auto rounded-lg border border-border text-sm">
          {places.map((place) => (
            <li key={place.place_id}>
              <button type="button" className="w-full px-3 py-2 text-left hover:bg-surface-hover" onClick={() => { focus({ lat: Number(place.lat), lng: Number(place.lon) }); setPlaces([]) }}>{place.display_name}</button>
            </li>
          ))}
        </ul>
      )}
      {notice && <p className="text-xs text-ink-secondary">{notice}</p>}
      <div ref={container} className="h-72 w-full overflow-hidden rounded-lg border border-border bg-surface-hover" />
      <p className="text-xs text-ink-tertiary">Klik peta untuk menaruh penanda, lalu seret penanda bila perlu.</p>
    </div>
  )
}
