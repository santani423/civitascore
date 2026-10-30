import { useState } from 'react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { PHOTOS, type PhotoKey } from '../../data/photos'
import type { Org } from '../../data/student-affairs'
import { TONE, orgStyle, type CategoryStyle } from './styles'

interface CoverArtProps {
  style: CategoryStyle
  /** A campus photo of the venue; when missing or broken, the category motif is drawn instead. */
  photo?: PhotoKey
  /** Size hint for the photo's srcset. */
  sizes?: string
  size?: 'card' | 'hero'
  /** Above the fold (detail hero): load eagerly. */
  priority?: boolean
  className?: string
}

/** Cover for activity and organization cards and banners. The motif is decorative; a photo gets its dictionary alt text. */
export function CoverArt({ style, photo, sizes = '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw', size = 'card', priority, className }: CoverArtProps) {
  const { m } = useLandingI18n()
  const [failed, setFailed] = useState(false)
  const tone = TONE[style.tone]
  const Icon = style.icon
  const image = photo && !failed ? { ...PHOTOS[photo], alt: m.photos[photo] } : null

  return (
    <div className={cn('relative isolate overflow-hidden', tone.surface, className)}>
      {image ? (
        <img
          src={image.src}
          srcSet={image.srcSet}
          sizes={sizes}
          width={image.width}
          height={image.height}
          alt={image.alt}
          loading={priority ? 'eager' : 'lazy'}
          fetchPriority={priority ? 'high' : undefined}
          decoding="async"
          onError={() => setFailed(true)}
          className="absolute inset-0 h-full w-full object-cover"
        />
      ) : (
        <>
          <span aria-hidden data-lp-decor className={cn('absolute -right-10 -top-16 h-48 w-48 rounded-full border-[18px]', tone.ring)} />
          <span aria-hidden data-lp-decor className={cn('absolute -bottom-20 -left-12 h-44 w-44 rounded-full border-[14px]', tone.ring)} />
          <Icon
            aria-hidden
            strokeWidth={1.4}
            className={cn('absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 opacity-90', tone.ink, size === 'hero' ? 'h-24 w-24' : 'h-14 w-14')}
          />
        </>
      )}
    </div>
  )
}

/**
 * An organization's logo; the sample entries have none, so its short name is
 * drawn as a monogram (one word per line). Decorative — the name is always
 * printed next to it.
 */
export function OrgLogo({ org, size = 'md', className }: { org: Org; size?: 'md' | 'lg'; className?: string }) {
  const [failed, setFailed] = useState(false)
  const tone = TONE[orgStyle(org).tone]
  const box = size === 'lg' ? 'h-24 w-24 rounded-lp-lg sm:h-28 sm:w-28' : 'h-14 w-14 rounded-lp'

  if (org.logo && !failed) {
    return <img src={org.logo} alt="" loading="lazy" decoding="async" onError={() => setFailed(true)} className={cn('shrink-0 bg-lp-surface object-contain p-1.5 ring-1 ring-lp-line', box, className)} />
  }

  const words = org.short.split(' ')
  return (
    <span
      aria-hidden
      className={cn('flex shrink-0 flex-col items-center justify-center gap-0.5 font-display font-extrabold leading-none tracking-tight shadow-lp-sm', box, tone.surface, tone.ink, className)}
    >
      {words.map((word) => (
        <span key={word} className={size === 'lg' ? (word.length > 4 ? 'text-[1.125rem] sm:text-[1.25rem]' : 'text-[1.5rem] sm:text-[1.75rem]') : word.length > 4 ? 'text-[0.75rem]' : 'text-[0.9375rem]'}>
          {word}
        </span>
      ))}
    </span>
  )
}
