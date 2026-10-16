// Helper bersama untuk naskah video PMB.
// Koordinat selalu dalam piksel screenshot asli (docs/manual-book/pmb/assets, lebar 1440).

const AR = 494 / 842; // rasio tinggi/lebar viewport peramban di video

/** Kotak sorotan: dari sudut kiri-atas (x1,y1) ke kanan-bawah (x2,y2). */
export const R = (x1, y1, x2, y2) => [x1, y1, x2 - x1, y2 - y1];

/** Kamera yang memuat area (x1,y1)-(x2,y2) dengan margin; lebar minimum 700 px agar tidak terlalu zoom. */
export const Z = (x1, y1, x2, y2, pad = 50, minW = 700) => {
  let w = x2 - x1 + pad * 2;
  const h = y2 - y1 + pad * 2;
  if (h / w > AR) w = h / AR;
  w = Math.min(1440, Math.max(minW, w));
  const cx = (x1 + x2) / 2, cy = (y1 + y2) / 2;
  return [Math.round(cx - w / 2), Math.round(cy - (w * AR) / 2), Math.round(w)];
};

/** Kamera lebar penuh mulai dari y tertentu. */
export const TOP = (y = 0) => [0, y, 1440];

/** Tengah sebuah kotak — titik klik kursor. */
export const C = (r) => [Math.round(r[0] + r[2] / 2), Math.round(r[1] + r[3] / 2)];

// Ejaan untuk mesin suara (teks takarir tetap memakai ejaan aslinya).
export const SAY = [
  [/\bUU PDP\b/g, 'undang-undang pe de pe'],
  [/\bPDDikti\b/g, 'pe de dikti'],
  [/\bPMB\b/g, 'pe em be'],
  [/\bPDF\b/g, 'pe de ef'],
  [/\bCBT\b/g, 'si bi ti'],
  [/\bKTP\b/g, 'ka te pe'],
  [/\bNIK\b/g, 'nik'],
  [/\bNIM\b/g, 'nim'],
  [/\bNISN\b/g, 'en i es en'],
  [/\bNPSN\b/g, 'en pe es en'],
  [/\bOTP\b/g, 'o te pe'],
  [/\bSMS\b/g, 'es em es'],
  [/\bQRIS\b/g, 'kyu-ris'],
  [/\bQR\b/g, 'kyu ar'],
  [/\bSKL\b/g, 'es ka el'],
  [/\bKIP\b/g, 'kip'],
  [/\bLoA\b/g, 'el o a'],
  [/\bHP\b/g, 'ha pe'],
  [/\bODS\b/g, 'o de es'],
  [/\bSPP\b/g, 'es pe pe'],
  [/\bSIAKAD\b/g, 'siakad'],
  [/\bCAPTCHA\b/g, 'kepcha'],
  [/\bCSV\b/g, 'si es vi'],
  [/\bXLSX\b/g, 'eksel'],
  [/\bWNI\b/g, 'warga negara Indonesia'],
  [/\bWNA\b/g, 'warga negara asing'],
  [/\bWA\b/g, 'wotsap'],
  [/\bWhatsApp\b/g, 'wotsap'],
  [/\bID\b/g, 'ai di'],
  [/\bJPG\b/g, 'je pe ge'],
  [/\bPNG\b/g, 'pe en ge'],
  [/\bMB\b/g, 'mega bait'],
  [/\bDekan\/Kaprodi\b/g, 'dekan dan kaprodi'],
  [/\bAdmin\b/g, 'admin'],
  [/\bdashboard\b/gi, 'dasbor'],
  [/\bCivitas One\b/g, 'sivitas wan'],
  [/\bSelectionRun\b/g, 'selection ran'],
  [/\bXendit\b/g, 'zendit'],
  [/\b[Rr]un\b/g, 'ran'],
  [/\bbatch\b/g, 'bets'],
  [/\bBroadcast\b/g, 'brodkas'],
  [/\bchat\b/g, 'cet'],
  [/\bonline\b/g, 'onlain'],
  [/\bauditor\b/gi, 'auditor'],
  [/ · /g, ', '],
  [/ — /g, ', '],
  [/→/g, ' lalu '],
  [/\//g, ' atau '],
];

export function speak(text) {
  let s = text;
  for (const [re, rep] of SAY) s = s.replace(re, rep);
  return s;
}

/** Lengkapi setiap baris dengan teks suara bila belum ada. */
export function finalize(script) {
  for (const sc of script.scenes) for (const ln of sc.lines) if (!ln.tts) ln.tts = speak(ln.cap);
  return script;
}
