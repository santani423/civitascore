import { useState } from 'react'
import { cn } from '@/utils/cn'
import { PILLAR_IMAGE_SIZE, pillarNumber, type Pillar } from '../../data/pillars'

interface PillarImageProps {
  pillar: Pillar
  /** Empty when the image sits next to the pillar's name already (decorative). */
  alt: string
  /** Size of the image box — the fallback takes exactly the same box. */
  className?: string
  size?: 'lg' | 'sm'
}

/**
 * A pillar's official icon, loaded from myjourney-api.atmajaya.ac.id. The
 * source PNG has a transparent margin around its orange tile; if that server
 * can't deliver the file, an orange tile with the pillar number is drawn at
 * the same size instead of a broken image.
 */
export function PillarImage({ pillar, alt, className, size = 'lg' }: PillarImageProps) {
  const [failed, setFailed] = useState(false)

  if (failed) {
    return (
      <span
        role={alt ? 'img' : undefined}
        aria-label={alt || undefined}
        aria-hidden={alt ? undefined : true}
        className={cn('flex aspect-square shrink-0 items-center justify-center', className)}
      >
        <span
          className={cn(
            'lp-tabular flex aspect-square h-[60%] items-center justify-center rounded-[18%] bg-gradient-to-br from-lp-gold to-lp-accent font-display font-extrabold text-white',
            size === 'lg' ? 'text-[2.5rem] sm:text-[3.5rem]' : 'text-[0.875rem]',
          )}
        >
          {pillarNumber(pillar)}
        </span>
      </span>
    )
  }

  return (
    <img
      src={pillar.image}
      alt={alt}
      width={PILLAR_IMAGE_SIZE}
      height={PILLAR_IMAGE_SIZE}
      loading="lazy"
      decoding="async"
      draggable={false}
      onError={() => setFailed(true)}
      className={cn('shrink-0 select-none', className)}
    />
  )
}
