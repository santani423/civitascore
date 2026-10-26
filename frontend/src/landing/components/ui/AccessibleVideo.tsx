export interface CaptionTrack {
  /** URL of a real .vtt file. Never ship a placeholder track. */
  src: string
  /** BCP 47 code, e.g. "id" or "en". */
  lang: string
  /** Name shown in the player's captions menu, e.g. "Bahasa Indonesia". */
  label: string
  default?: boolean
}

interface AccessibleVideoProps {
  src: string
  title: string
  poster?: string
  /** Subtitles for any spoken content — required whenever the video has dialogue. */
  captions: CaptionTrack[]
  /** Text alternative of the audio/video content, shown in a disclosure below the player. */
  transcript: string
  transcriptLabel: string
}

/**
 * Pattern for future landing videos (none exist yet, so this is unused):
 * native controls (play, pause, volume, mute, keyboard operable), no autoplay,
 * subtitles via <track>, and a transcript for people who cannot hear or watch.
 */
export function AccessibleVideo({ src, title, poster, captions, transcript, transcriptLabel }: AccessibleVideoProps) {
  return (
    <figure>
      <video controls preload="metadata" poster={poster} aria-label={title} className="w-full rounded-lp bg-lp-band">
        <source src={src} />
        {captions.map((track) => (
          <track key={track.lang} kind="captions" src={track.src} srcLang={track.lang} label={track.label} default={track.default} />
        ))}
      </video>
      <figcaption className="mt-3">
        <details className="rounded-lp border border-lp-line bg-lp-surface px-4 py-3">
          <summary className="min-h-11 cursor-pointer py-2 text-[0.9063rem] font-semibold text-lp-fg">{transcriptLabel}</summary>
          <p className="lp-body mt-2 whitespace-pre-line text-lp-muted">{transcript}</p>
        </details>
      </figcaption>
    </figure>
  )
}
