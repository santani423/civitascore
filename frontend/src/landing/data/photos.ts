import gedung900 from '../assets/photos/kampus-semanggi-gedung-900.webp'
import gedung1600 from '../assets/photos/kampus-semanggi-gedung-1600.webp'
import fasad720 from '../assets/photos/kampus-semanggi-fasad-720.webp'
import fasad1280 from '../assets/photos/kampus-semanggi-fasad-1280.webp'
import guaMaria640 from '../assets/photos/kampus-semanggi-gua-maria-640.webp'
import guaMaria1200 from '../assets/photos/kampus-semanggi-gua-maria-1200.webp'

export type PhotoKey = 'campusTower' | 'campusFacade' | 'grotto'

export interface Photo {
  src: string
  srcSet: string
  width: number
  height: number
  /** Wikimedia Commons file page — required attribution for CC BY-SA 4.0. */
  source: string
  author: string
}

/**
 * Real photos of the Semanggi campus from Wikimedia Commons (CC BY-SA 4.0,
 * cropped and re-encoded — see assets/photos/CREDITS.md). Alt text lives in
 * the dictionaries (`photos.*`) so it follows the selected language.
 */
export const PHOTOS: Record<PhotoKey, Photo> = {
  campusTower: {
    src: gedung1600,
    srcSet: `${gedung900} 900w, ${gedung1600} 1600w`,
    width: 1600,
    height: 1600,
    source: 'https://commons.wikimedia.org/wiki/File:Kampus_Unika_Atma_Jaya_Semanggi_(2025)_02.jpg',
    author: 'Medelam',
  },
  campusFacade: {
    src: fasad1280,
    srcSet: `${fasad720} 720w, ${fasad1280} 1280w`,
    width: 1280,
    height: 960,
    source: 'https://commons.wikimedia.org/wiki/File:Kampus_Unika_Atma_Jaya_Semanggi_(2025)_01.jpg',
    author: 'Medelam',
  },
  grotto: {
    src: guaMaria1200,
    srcSet: `${guaMaria640} 640w, ${guaMaria1200} 1200w`,
    width: 1200,
    height: 900,
    source: 'https://commons.wikimedia.org/wiki/File:Gua_Maria_Immaculata_di_Kampus_Unika_Atma_Jaya_Semanggi_(2025).jpg',
    author: 'Medelam',
  },
}

export const PHOTO_LICENSE = { name: 'CC BY-SA 4.0', url: 'https://creativecommons.org/licenses/by-sa/4.0/' }
