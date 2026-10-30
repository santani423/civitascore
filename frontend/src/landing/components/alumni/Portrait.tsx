import { useId, useState, type ReactNode } from 'react'
import { cn } from '@/utils/cn'
import type { AlumniLook, Alumnus, Attire, Backdrop, Cloth, HairStyle } from '../../data/alumni'

/** Fixed ink: like a photo, a portrait looks the same in every theme. */
const SKIN: Record<AlumniLook['skin'], { base: string; shade: string }> = {
  0: { base: '#F4D6BC', shade: '#E5BE9D' },
  1: { base: '#E6B892', shade: '#D2A079' },
  2: { base: '#C78F66', shade: '#AE7750' },
  3: { base: '#96663F', shade: '#7D5333' },
}
const HAIR = { black: '#231E1C', brown: '#4A3226', grey: '#ADA7A2' }
const BACKDROP: Record<Backdrop, [string, string]> = {
  ember: ['#B23D0B', '#EE6E2C'],
  charcoal: ['#262321', '#544C47'],
  gold: ['#AD7211', '#E5AB4B'],
  sand: ['#D8CABA', '#F3ECE3'],
  clay: ['#7A3216', '#BB6035'],
  dusk: ['#3A3331', '#8C5B44'],
}
const CLOTH: Record<Cloth, string> = {
  navy: '#1E2A44',
  ink: '#27272C',
  burgundy: '#6B2232',
  forest: '#1F4A38',
  cream: '#EFE6D6',
  terracotta: '#B4532A',
  slate: '#48505C',
}

const HEAD = 'M100 56C124 56 136 76 135 100C134 126 118 146 100 146C82 146 66 126 65 100C64 76 76 56 100 56Z'
const TORSO = 'M10 250C12 208 42 188 80 176L120 176C158 188 188 208 190 250Z'
/** Left lapel; the right one is its mirror. */
const LAPEL = 'M82 174L64 200L80 210L72 220L98 250L100 218Z'
const MIRROR = 'matrix(-1 0 0 1 200 0)'

function BackHair({ hair, color }: { hair: HairStyle; color: string }) {
  if (hair === 'bob') return <path d="M56 100C50 44 150 44 144 100C144 126 150 148 141 160L59 160C50 148 56 126 56 100Z" fill={color} />
  if (hair === 'long') return <path d="M55 100C48 42 152 42 145 100C145 142 158 178 152 204L48 204C42 178 55 142 55 100Z" fill={color} />
  if (hair === 'bun') return <circle cx="100" cy="42" r="17" fill={color} />
  if (hair === 'hijab') return <path d="M100 40C58 40 48 84 50 114C52 152 42 180 30 204L170 204C158 180 148 152 150 114C152 84 142 40 100 40Z" fill={color} />
  return null
}

function FrontHair({ hair, color, hijab }: { hair: HairStyle; color: string; hijab: string }) {
  switch (hair) {
    case 'short':
      return <path d="M63 102C56 62 78 42 104 42C131 42 144 66 137 102C135 90 131 80 125 74C110 79 87 79 75 74C69 80 65 90 63 102Z" fill={color} />
    case 'side':
      return <path d="M63 104C55 60 81 40 107 42C136 44 146 68 137 104C135 92 132 83 127 77C107 83 92 75 85 66C79 77 67 85 63 104Z" fill={color} />
    case 'curly':
      return (
        <g fill={color}>
          {[
            [68, 90, 10],
            [72, 72, 12],
            [85, 58, 12],
            [101, 52, 13],
            [117, 57, 12],
            [129, 71, 12],
            [133, 90, 10],
          ].map(([cx, cy, r]) => (
            <circle key={cx} cx={cx} cy={cy} r={r} />
          ))}
        </g>
      )
    case 'receding':
      return (
        <g fill={color}>
          <path d="M64 100C60 78 68 60 84 54C78 68 72 84 69 104Z" />
          <path d="M136 100C140 78 132 60 116 54C122 68 128 84 131 104Z" />
          <path d="M84 54C94 49 106 49 116 54C106 58 94 58 84 54Z" />
        </g>
      )
    case 'bob':
    case 'bun':
      return <path d="M64 100C66 70 84 54 104 54C124 54 134 72 136 100C126 88 114 78 102 72C92 84 78 92 64 100Z" fill={color} />
    case 'long':
      return <path d="M64 102C62 72 80 54 100 56C120 54 138 72 136 102C128 86 114 72 100 64C86 72 72 86 64 102Z" fill={color} />
    case 'hijab':
      return (
        <g fill={hijab}>
          <path d="M64 104C64 72 84 54 100 54C116 54 136 72 136 104C127 84 114 74 100 74C86 74 73 84 64 104Z" />
          {/* The drape under the chin, over the neck and shoulders. */}
          <path d="M65 112C70 146 88 160 100 160C112 160 130 146 135 112C141 150 152 180 164 204C140 222 60 222 36 204C48 180 59 150 65 112Z" />
          <path d="M72 158C86 170 114 170 128 158" fill="none" stroke="#000" strokeOpacity=".14" strokeWidth="2.5" strokeLinecap="round" />
        </g>
      )
  }
}

function Clothes({ attire, color, tie, patternId }: { attire: Attire; color: string; tie?: boolean; patternId: string }) {
  const lapels = (fill: string, opacity: number) => (
    <g fill={fill} fillOpacity={opacity}>
      <path d={LAPEL} />
      <path d={LAPEL} transform={MIRROR} />
    </g>
  )
  switch (attire) {
    case 'suit':
    case 'blazer':
      return (
        <>
          <path d={TORSO} fill={color} />
          <path d="M80 174L100 226L120 174Z" fill={attire === 'suit' ? '#FBFAF8' : '#EFE6D6'} />
          {tie && <path d="M95 182H105L108 222L100 238L92 222Z" fill="#B23D0B" />}
          {lapels('#fff', 0.14)}
        </>
      )
    case 'coat':
      return (
        <>
          <path d={TORSO} fill="#F7F5F1" />
          <path d="M80 174L100 226L120 174Z" fill={color} />
          {lapels('#000', 0.09)}
          {/* Stethoscope. */}
          <path d="M84 182C74 208 78 228 94 234M116 182C126 208 122 228 106 234" fill="none" stroke="#3A3D45" strokeWidth="3" strokeLinecap="round" />
          <circle cx="100" cy="236" r="6.5" fill="#3A3D45" />
        </>
      )
    case 'shirt':
      return (
        <>
          <path d={TORSO} fill={color} />
          <path d="M82 170L100 192L118 170L128 188L100 208L72 188Z" fill="#fff" fillOpacity=".22" />
          <path d="M100 208V250" stroke="#000" strokeOpacity=".16" strokeWidth="2" />
        </>
      )
    case 'batik':
      return (
        <>
          <path d={TORSO} fill={color} />
          <path d={TORSO} fill={`url(#${patternId})`} />
          <path d="M82 170L100 192L118 170L128 188L100 208L72 188Z" fill={color} />
          <path d="M82 170L100 192L118 170L128 188L100 208L72 188Z" fill="#000" fillOpacity=".12" />
        </>
      )
    case 'turtleneck':
      return (
        <>
          <path d={TORSO} fill={color} />
          <path d="M82 148H118L121 182C110 190 90 190 79 182Z" fill={color} />
          <path d="M82 158C92 164 108 164 118 158" fill="none" stroke="#fff" strokeOpacity=".18" strokeWidth="2.5" strokeLinecap="round" />
        </>
      )
  }
}

/** Head-and-shoulders drawing, top-aligned, so any crop keeps the face. Decorative: the name is always printed next to it. */
export function AlumniIllustration({ look, className }: { look: AlumniLook; className?: string }) {
  const id = useId().replace(/[^a-zA-Z0-9]/g, '')
  const skin = SKIN[look.skin]
  const hair = HAIR[look.hairTone]
  const cloth = CLOTH[look.cloth]
  const [from, to] = BACKDROP[look.backdrop]
  const isHijab = look.hair === 'hijab'

  return (
    <svg viewBox="0 0 200 250" preserveAspectRatio="xMidYMin slice" aria-hidden className={cn('block h-full w-full', className)}>
      <defs>
        <linearGradient id={`${id}-bg`} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stopColor={from} />
          <stop offset="1" stopColor={to} />
        </linearGradient>
        <clipPath id={`${id}-face`}>
          <path d={HEAD} />
        </clipPath>
        <clipPath id={`${id}-arch`}>
          <path d="M28 250V100a72 72 0 0 1 144 0v150z" />
        </clipPath>
        <pattern id={`${id}-batik`} width="22" height="22" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
          <path d="M0 11H22" stroke="#fff" strokeOpacity=".28" strokeWidth="3" />
          <circle cx="11" cy="4" r="2.2" fill="#fff" fillOpacity=".34" />
          <circle cx="11" cy="18" r="2.2" fill="#000" fillOpacity=".2" />
        </pattern>
      </defs>

      <rect width="200" height="250" fill={`url(#${id}-bg)`} />
      {/* The arch and its fluting: the campus colonnade behind the figure. */}
      <path d="M28 250V100a72 72 0 0 1 144 0v150z" fill="#fff" fillOpacity=".13" />
      <g clipPath={`url(#${id}-arch)`} stroke="#fff" strokeOpacity=".12" strokeWidth="1.5">
        {[46, 64, 82, 100, 118, 136, 154].map((x) => (
          <path key={x} d={`M${x} 20V250`} />
        ))}
      </g>

      <BackHair hair={look.hair} color={isHijab ? cloth : hair} />
      <Clothes attire={look.attire} color={cloth} tie={look.tie} patternId={`${id}-batik`} />

      {/* Neck, with the chin's shadow. */}
      {!isHijab && look.attire !== 'turtleneck' && <path d="M86 130H114V174C114 186 86 186 86 174Z" fill={skin.base} />}
      {!isHijab && look.attire === 'turtleneck' && <path d="M86 130H114V152H86Z" fill={skin.base} />}
      {!isHijab && <path d="M86 136C92 156 108 156 114 136V150C108 166 92 166 86 150Z" fill={skin.shade} />}

      <path d={HEAD} fill={skin.base} />
      <g clipPath={`url(#${id}-face)`}>
        <path d="M110 50C124 90 124 122 106 150H150V50Z" fill={skin.shade} fillOpacity=".65" />
      </g>

      <g fill="none" strokeLinecap="round">
        <path d="M79 91Q86 87 93 91M107 91Q114 87 121 91" stroke={look.hairTone === 'grey' ? '#6E6965' : hair} strokeWidth="2.6" />
        <path d="M101 104Q97 114 102 117" stroke={skin.shade} strokeWidth="2.4" />
        <path d="M90 125Q100 132 110 125" stroke="#7E3A2A" strokeWidth="2.5" />
      </g>
      <ellipse cx="86" cy="101" rx="2.7" ry="3.1" fill="#231E1C" />
      <ellipse cx="114" cy="101" rx="2.7" ry="3.1" fill="#231E1C" />

      <FrontHair hair={look.hair} color={hair} hijab={cloth} />

      {look.glasses && (
        <g fill="#fff" fillOpacity=".1" stroke="#231E1C" strokeWidth="2">
          <rect x="75" y="93" width="22" height="16" rx="6" />
          <rect x="103" y="93" width="22" height="16" rx="6" />
          <path d="M97 99Q100 96 103 99" fill="none" />
        </g>
      )}
    </svg>
  )
}

/**
 * An alumnus's portrait: the real `photo` when there is one, otherwise (and if
 * it fails to load) the illustration. Sized and shaped by the caller.
 */
export function Portrait({ person, className, children, imageClassName }: { person: Pick<Alumnus, 'photo' | 'look'>; className?: string; imageClassName?: string; children?: ReactNode }) {
  const [failed, setFailed] = useState(false)
  return (
    <div className={cn('relative isolate overflow-hidden bg-lp-sunken', className)}>
      {person.photo && !failed ? (
        <img src={person.photo} alt="" loading="lazy" decoding="async" onError={() => setFailed(true)} className={cn('h-full w-full object-cover object-top', imageClassName)} />
      ) : (
        <AlumniIllustration look={person.look} className={imageClassName} />
      )}
      {children}
    </div>
  )
}
