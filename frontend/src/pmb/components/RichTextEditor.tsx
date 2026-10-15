import { useEffect, useRef, useState, type ClipboardEvent, type ComponentType } from 'react'
import { Bold, CodeXml, Italic, Link as LinkIcon, List, ListOrdered, Minus, Quote, Redo2, RemoveFormatting, Strikethrough, Underline, Undo2, Unlink } from 'lucide-react'
import { RICH_TEXT_CLASS } from '@/pmb/components/Section'
import { cn } from '@/utils/cn'

/**
 * Cermin allowlist HtmlSanitizer (server) agar yang tampil di editor sama
 * dengan yang tersimpan. Keamanan tetap ditegakkan server; di sini hanya
 * merapikan hasil editor & tempelan (Word/web) yang gayanya pasti dibuang.
 */
const ALLOWED: Record<string, string[]> = {
  p: [], br: [], strong: [], b: [], em: [], i: [], u: [], s: [],
  ul: [], ol: [], li: [], h2: [], h3: [], h4: [], blockquote: [], hr: [],
  a: ['href', 'title', 'target'], img: ['src', 'alt', 'width', 'height'], div: [],
  table: [], thead: [], tbody: [], tr: [], th: ['colspan', 'rowspan'], td: ['colspan', 'rowspan'],
  figure: [], figcaption: [], code: [], pre: [],
}
const DROP_WITH_CONTENT = new Set(['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta', 'base', 'title'])
const RENAME: Record<string, string> = { strike: 's', del: 's', ins: 'u', h1: 'h2', h5: 'h4', h6: 'h4' }

function cleanHtml(html: string): string {
  const body = new DOMParser().parseFromString(html, 'text/html').body
  walk(body)

  return body.textContent?.trim() || body.querySelector('img, hr') ? body.innerHTML : ''
}

function walk(node: Node) {
  for (const child of Array.from(node.childNodes)) {
    if (child.nodeType === Node.COMMENT_NODE) {
      child.remove()
      continue
    }
    if (!(child instanceof Element)) continue

    let tag = child.tagName.toLowerCase()
    if (DROP_WITH_CONTENT.has(tag)) {
      child.remove()
      continue
    }

    walk(child)
    let element: Element = child
    if (RENAME[tag]) {
      tag = RENAME[tag]
      element = child.ownerDocument.createElement(tag)
      element.append(...Array.from(child.childNodes))
      child.replaceWith(element)
    }
    if (!ALLOWED[tag]) {
      element.replaceWith(...Array.from(element.childNodes))
      continue
    }
    for (const attr of Array.from(element.attributes)) {
      if (!ALLOWED[tag].includes(attr.name)) element.removeAttribute(attr.name)
    }
  }
}

const STATE_COMMANDS = ['bold', 'italic', 'underline', 'strikeThrough', 'insertUnorderedList', 'insertOrderedList'] as const

const BLOCKS = [
  { value: 'p', label: 'Paragraf' },
  { value: 'h2', label: 'Judul 1' },
  { value: 'h3', label: 'Judul 2' },
  { value: 'h4', label: 'Judul 3' },
]

export interface RichTextEditorProps {
  label?: string
  hint?: string
  error?: string
  value: string
  onChange: (html: string) => void
}

/**
 * Editor teks kaya (WYSIWYG) tanpa dependensi — contentEditable + execCommand.
 * Hanya menawarkan format yang lolos HtmlSanitizer: perataan, warna, dan
 * ukuran huruf sengaja tidak ada karena atribut style dibuang server.
 */
export function RichTextEditor({ label, hint, error, value, onChange }: RichTextEditorProps) {
  const editor = useRef<HTMLDivElement>(null)
  const emitted = useRef<string | null>(null)
  const range = useRef<Range | null>(null)
  const [source, setSource] = useState(false)
  const [active, setActive] = useState<Record<string, boolean>>({})
  const [block, setBlock] = useState('p')

  // Isi DOM hanya bila nilai berubah dari luar (bukan dari ketikan sendiri) agar kursor tidak lompat.
  useEffect(() => {
    const el = editor.current
    if (!el || value === emitted.current) return
    el.innerHTML = cleanHtml(value)
    emitted.current = value
    range.current = null
  }, [value, source])

  useEffect(() => {
    const update = () => {
      const el = editor.current
      const selection = document.getSelection()
      if (!el || !selection?.rangeCount || !el.contains(selection.anchorNode)) return
      range.current = selection.getRangeAt(0).cloneRange()
      setActive(Object.fromEntries(STATE_COMMANDS.map((c) => [c, document.queryCommandState(c)])))
      setBlock(document.queryCommandValue('formatBlock').toLowerCase() || 'p')
    }

    document.addEventListener('selectionchange', update)

    return () => document.removeEventListener('selectionchange', update)
  }, [])

  const emit = () => {
    const html = cleanHtml(editor.current?.innerHTML ?? '')
    emitted.current = html
    onChange(html)
  }

  /** Kembalikan seleksi terakhir (hilang saat fokus pindah ke dropdown/prompt), lalu jalankan perintah berurutan. */
  const run = (commands: Array<[command: string, arg?: string]>) => {
    const el = editor.current
    if (!el) return
    el.focus()
    if (range.current) {
      const selection = document.getSelection()
      selection?.removeAllRanges()
      selection?.addRange(range.current)
    }
    document.execCommand('styleWithCSS', false, 'false')
    document.execCommand('defaultParagraphSeparator', false, 'p')
    for (const [command, arg] of commands) document.execCommand(command, false, arg)
    emit()
  }

  const exec = (command: string, arg?: string) => run([[command, arg]])

  const addLink = () => {
    const url = window.prompt('Alamat tautan (https://..., mailto:..., atau /halaman):', 'https://')?.trim()
    if (url && url !== 'https://') exec('createLink', url)
  }

  const onPaste = (event: ClipboardEvent<HTMLDivElement>) => {
    event.preventDefault()
    const html = event.clipboardData.getData('text/html')
    if (html) exec('insertHTML', cleanHtml(html))
    else exec('insertText', event.clipboardData.getData('text/plain'))
  }

  const toggleSource = () => {
    emitted.current = null
    setSource((current) => !current)
  }

  return (
    <div className="flex flex-col gap-1.5">
      {label && <span className="text-sm font-medium text-ink-primary">{label}</span>}
      <div className={cn('overflow-hidden rounded-lg border bg-surface focus-within:ring-2', error ? 'border-danger focus-within:ring-danger' : 'border-border-strong focus-within:border-primary focus-within:ring-primary')}>
        <div className="flex flex-wrap items-center gap-0.5 border-b border-border bg-surface px-1.5 py-1">
          {source ? (
            <span className="px-1.5 text-xs text-ink-tertiary">Mode HTML</span>
          ) : (
            <>
              <Tool icon={Undo2} label="Batalkan (Ctrl+Z)" onClick={() => exec('undo')} />
              <Tool icon={Redo2} label="Ulangi (Ctrl+Y)" onClick={() => exec('redo')} />
              <Divider />
              <select
                aria-label="Gaya paragraf"
                value={BLOCKS.some((b) => b.value === block) ? block : 'p'}
                onChange={(e) => exec('formatBlock', `<${e.target.value}>`)}
                className="h-7 rounded border border-border bg-surface px-1.5 text-xs text-ink-primary focus:outline-none"
              >
                {BLOCKS.map((b) => <option key={b.value} value={b.value}>{b.label}</option>)}
              </select>
              <Divider />
              <Tool icon={Bold} label="Tebal (Ctrl+B)" active={active.bold} onClick={() => exec('bold')} />
              <Tool icon={Italic} label="Miring (Ctrl+I)" active={active.italic} onClick={() => exec('italic')} />
              <Tool icon={Underline} label="Garis bawah (Ctrl+U)" active={active.underline} onClick={() => exec('underline')} />
              <Tool icon={Strikethrough} label="Coret" active={active.strikeThrough} onClick={() => exec('strikeThrough')} />
              <Divider />
              <Tool icon={List} label="Daftar berpoin" active={active.insertUnorderedList} onClick={() => exec('insertUnorderedList')} />
              <Tool icon={ListOrdered} label="Daftar bernomor" active={active.insertOrderedList} onClick={() => exec('insertOrderedList')} />
              <Tool icon={Quote} label="Kutipan" active={block === 'blockquote'} onClick={() => exec('formatBlock', block === 'blockquote' ? '<p>' : '<blockquote>')} />
              <Divider />
              <Tool icon={LinkIcon} label="Sisipkan tautan" onClick={addLink} />
              <Tool icon={Unlink} label="Hapus tautan" onClick={() => exec('unlink')} />
              <Tool icon={Minus} label="Garis pemisah" onClick={() => exec('insertHorizontalRule')} />
              <Tool icon={RemoveFormatting} label="Hapus format" onClick={() => run([['removeFormat'], ['formatBlock', '<p>']])} />
            </>
          )}
          <span className="ml-auto" />
          <Tool icon={CodeXml} label={source ? 'Kembali ke editor' : 'Lihat/ubah HTML'} active={source} onClick={toggleSource} />
        </div>
        {source ? (
          <textarea
            value={value}
            onChange={(e) => onChange(e.target.value)}
            rows={10}
            className="block w-full resize-y bg-surface px-3 py-2 font-mono text-xs text-ink-primary focus:outline-none"
          />
        ) : (
          <div
            ref={editor}
            role="textbox"
            aria-multiline="true"
            aria-label={label}
            contentEditable
            suppressContentEditableWarning
            onFocus={() => document.execCommand('defaultParagraphSeparator', false, 'p')}
            onInput={emit}
            onPaste={onPaste}
            className={cn(RICH_TEXT_CLASS, 'max-h-96 min-h-48 overflow-y-auto px-3 py-2 focus:outline-none')}
          />
        )}
      </div>
      {error ? <p className="text-xs text-danger">{error}</p> : hint ? <p className="text-xs text-ink-tertiary">{hint}</p> : null}
    </div>
  )
}

function Tool({ icon: Icon, label, active, onClick }: { icon: ComponentType<{ className?: string }>; label: string; active?: boolean; onClick: () => void }) {
  return (
    <button
      type="button"
      title={label}
      aria-label={label}
      aria-pressed={active}
      onMouseDown={(e) => e.preventDefault()}
      onClick={onClick}
      className={cn('rounded p-1.5 transition-colors', active ? 'bg-primary-50 text-primary' : 'text-ink-secondary hover:bg-surface-hover hover:text-ink-primary')}
    >
      <Icon className="size-4" />
    </button>
  )
}

function Divider() {
  return <span className="mx-0.5 h-5 w-px bg-border" aria-hidden />
}
