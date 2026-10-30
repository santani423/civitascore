import { useId, useState } from 'react'
import { ChevronLeft, ChevronRight, Expand } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { PHOTOS, type PhotoKey } from '../../data/photos'
import { Button } from '../ui/Button'
import { Dialog } from '../ui/Overlay'

/** Photo grid; each photo opens larger in a dialog with previous/next. */
/** `columns` 3 lays the grid out in three columns on wide screens (for a set of three). */
export function PhotoGallery({ photos, caption, columns = 2 }: { photos: PhotoKey[]; caption?: string; columns?: 2 | 3 }) {
  const { m } = useLandingI18n()
  const g = m.affairs.gallery
  const captionId = useId()
  const [index, setIndex] = useState<number | null>(null)
  const current = index === null ? null : photos[index]

  return (
    <>
      {caption && <p className="lp-small mb-4 text-lp-subtle">{caption}</p>}
      <ul className={columns === 3 ? 'grid gap-4 sm:grid-cols-2 lg:grid-cols-3' : 'grid gap-4 sm:grid-cols-2'}>
        {photos.map((key, i) => {
          const photo = PHOTOS[key]
          return (
            <li key={key}>
              <figure>
                <button
                  type="button"
                  onClick={() => setIndex(i)}
                  aria-describedby={`${captionId}-${i}`}
                  className="group relative block aspect-[4/3] w-full overflow-hidden rounded-lp-lg border border-lp-line bg-lp-sunken"
                >
                  <img
                    src={photo.src}
                    srcSet={photo.srcSet}
                    sizes="(min-width: 1024px) 30vw, (min-width: 640px) 50vw, 100vw"
                    width={photo.width}
                    height={photo.height}
                    alt=""
                    loading="lazy"
                    decoding="async"
                    className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03]"
                  />
                  <span aria-hidden className="absolute bottom-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-lp-band/70 text-lp-band-fg backdrop-blur">
                    <Expand size={16} />
                  </span>
                  <span className="sr-only">{g.open}</span>
                </button>
                <figcaption id={`${captionId}-${i}`} className="mt-2 text-[0.8125rem] leading-snug text-lp-subtle">{m.photos[key]}</figcaption>
              </figure>
            </li>
          )
        })}
      </ul>

      <Dialog
        open={current !== null}
        onClose={() => setIndex(null)}
        size="lg"
        title={index === null ? '' : fill(g.counter, { n: index + 1, total: photos.length })}
        description={current ? m.photos[current] : undefined}
        footer={
          photos.length > 1 && index !== null ? (
            <>
              <Button variant="secondary" onClick={() => setIndex((index - 1 + photos.length) % photos.length)}>
                <ChevronLeft size={17} aria-hidden />
                {g.previous}
              </Button>
              <Button variant="secondary" onClick={() => setIndex((index + 1) % photos.length)}>
                {g.next}
                <ChevronRight size={17} aria-hidden />
              </Button>
            </>
          ) : undefined
        }
      >
        {current && (
          <img
            src={PHOTOS[current].src}
            srcSet={PHOTOS[current].srcSet}
            sizes="(min-width: 640px) 42rem, 100vw"
            width={PHOTOS[current].width}
            height={PHOTOS[current].height}
            alt=""
            className="max-h-[60dvh] w-full rounded-lp object-contain"
          />
        )}
      </Dialog>
    </>
  )
}
