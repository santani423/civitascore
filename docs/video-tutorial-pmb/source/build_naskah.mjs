// node build_naskah.mjs <nama>  -> ../naskah-video-pmb-<nama>.md
// Naskah produksi dibangkitkan dari <nama>.timeline.js (hasil build_audio.mjs), sehingga narasi,
// urutan adegan, tindakan layar, teks pendukung, dan durasi selalu sama dengan video yang dirender.
import { fileURLToPath } from 'url';
import fs from 'fs';
import path from 'path';
import vm from 'vm';
import { SAY } from './common.mjs';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const NAME = process.argv[2];
if (!NAME) throw new Error('pakai: node build_naskah.mjs <calon-mahasiswa|penyelenggara>');
const ctx = { window: {} };
vm.runInNewContext(fs.readFileSync(path.join(HERE, `${NAME}.timeline.js`), 'utf8'), ctx);
const TL = ctx.window.TL;
const M = TL.meta;

const mmss = (s) => `${Math.floor(s / 60)}:${String(Math.floor(s % 60)).padStart(2, '0')}`;
const dur = (s) => (s >= 60 ? `${Math.floor(s / 60)} mnt ${Math.round(s % 60)} dtk` : `${Math.round(s)} dtk`);
const plain = (h) => String(h)
  .replace(/<li>/g, '• ').replace(/<\/li>/g, ' ')
  .replace(/<code>(.*?)<\/code>/g, '`$1`')
  .replace(/<b>(.*?)<\/b>/g, '**$1**').replace(/<i>(.*?)<\/i>/g, '*$1*')
  .replace(/<[^>]+>/g, '')
  .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/&nbsp;/g, ' ')
  .replace(/\s+/g, ' ').trim();
const cell = (s) => String(s ?? '').replace(/\|/g, '\\|').replace(/\n/g, ' ');
const LABEL = { tip: 'Info', warn: 'Perhatian', ok: 'Berhasil', bad: 'Akibat' };
function cardText(c) {
  if (!c) return '';
  const parts = [];
  if (c.n) parts.push(`[${c.n}]`);
  if (c.t) parts.push(`**${plain(c.t)}**`);
  if (c.b) parts.push(Array.isArray(c.b) ? c.b.map((x) => `• ${plain(x)}`).join(' ') : plain(c.b));
  for (const k of ['tip', 'warn', 'ok', 'bad']) if (c[k]) parts.push(`_${LABEL[k]}:_ ${plain(c[k])}`);
  return parts.join(' — ');
}
const TYPE = { title: 'Kartu judul', flow: 'Diagram alur', cards: 'Kartu informasi', shot: 'Screenshot aplikasi + sorotan', phones: 'Tampilan ponsel', end: 'Ringkasan & penutup' };

const out = [];
const P = (s = '') => out.push(s);
const total = TL.total;
const shots = new Set();
TL.scenes.forEach((s) => { if (s.img) shots.add(s.img); s.lines.forEach((l) => l.img && shots.add(l.img)); });

P(`# Naskah Produksi — ${M.title}`);
P();
P('| | |');
P('|---|---|');
P(`| **Judul** | ${M.title} |`);
P(`| **Sasaran penonton** | ${M.audience} |`);
P(`| **Tujuan** | ${M.goal} |`);
P(`| **Durasi** | ${mmss(total)} (${TL.scenes.length} adegan, ${TL.scenes.reduce((n, s) => n + s.lines.length, 0)} kalimat narasi) |`);
P(`| **Berkas video** | [\`tutorial-pmb-${NAME}.mp4\`](tutorial-pmb-${NAME}.mp4) — MP4 H.264, 1920 × 1080, 25 fps, audio AAC; takarir tertanam |`);
P(`| **Pengisi suara** | Suara sintetis Microsoft Edge \`${TL.voice}\` (Bahasa Indonesia). Dapat diganti suara manusia dengan naskah yang sama. |`);
P(`| **Panduan tertulis pendamping** | [${M.manual.replace('.md', '')}](../manual-book/pmb/${M.manual}) |`);
P(`| **Sumber visual** | ${shots.size} screenshot aplikasi yang berjalan, dari [\`docs/manual-book/pmb/assets\`](../manual-book/pmb/assets/) (data demo fiktif *Universitas Nusantara Digital*) |`);
P(`| **Acuan isi** | Rancangan [\`docs/pmb/01–16\`](../pmb/) dan manual book PMB per bab [\`docs/manual-book/pmb\`](../manual-book/pmb/README.md), dicocokkan dengan kode aplikasi terkini |`);
P(`| **Sumber produksi** | [\`source/${NAME}.script.mjs\`](source/${NAME}.script.mjs) (data naskah), [\`source/player.html\`](source/player.html) (pemutar), cara membangun ulang di bagian akhir |`);
P();
P('> Naskah ini **dibangkitkan otomatis** dari data yang sama dengan video (`build_naskah.mjs`), sehingga narasi, urutan adegan, tindakan layar, teks pendukung, dan waktu selalu sesuai dengan berkas video. Untuk mengubah isi, ubah `source/' + NAME + '.script.mjs`, lalu bangun ulang audio, naskah, dan video.');
P();

P('## Daftar isi');
P();
P('1. [Ringkasan adegan](#ringkasan-adegan)');
P('2. [Pedoman produksi](#pedoman-produksi)');
P('3. [Naskah per adegan](#naskah-per-adegan)');
P('4. [Lampiran A — Ejaan pengucapan untuk pengisi suara](#lampiran-a--ejaan-pengucapan-untuk-pengisi-suara)');
P('5. [Lampiran B — Cara membangun ulang video](#lampiran-b--cara-membangun-ulang-video)');
P();

P('## Ringkasan adegan');
P();
P('| No | Adegan | Mulai | Durasi | Jenis visual |');
P('|---:|---|---:|---:|---|');
TL.scenes.forEach((s, i) => P(`| ${i} | [${cell(s.title)}](#adegan-${i}) | ${mmss(s.start)} | ${dur(s.dur)} | ${TYPE[s.type]} |`));
P();

P('## Pedoman produksi');
P();
P('| Aspek | Ketentuan |');
P('|---|---|');
P('| Tata letak layar | Bilah atas: logo, nama aplikasi, nama panduan, penanda bagian (*Bagian n/N · judul*). Tengah: jendela peramban berisi screenshot (kiri) dan **kartu teks pendukung** (kanan). Bawah: takarir narasi dan bilah progres. |');
P('| Gerak kamera | Kamera bergeser/zoom halus ke area yang dibicarakan pada awal setiap kalimat. Area penting diberi **bingkai sorotan kuning**; tombol yang "diklik" ditunjukkan kursor dengan riak hijau. |');
P('| Teks pendukung | Kartu kanan memuat langkah bernomor, nama tombol/menu persis seperti di aplikasi (tebal), serta kotak *Info* (biru), *Perhatian* (kuning), *Berhasil* (hijau), *Akibat* (merah). |');
P('| Narasi | Bahasa Indonesia baku yang sederhana, menyapa penonton dengan "Anda", satu gagasan per kalimat. Nama tombol dibacakan persis seperti di layar. |');
P('| Takarir | Selalu tampil (aksesibilitas penonton tunarungu & menonton tanpa suara). |');
P('| Data pada layar | Seluruh nama, nomor, dan nominal adalah **data demo fiktif**. Tampilan kampus dapat berbeda karena logo, warna, jalur, biaya, dan pengaturan masing-masing kampus. |');
P('| Rekaman ulang dari aplikasi asli (opsional) | Ikuti baris **Arahan demonstrasi** pada setiap adegan. Gunakan akun demo per peran, resolusi 1440 × 900, zoom peramban 100%, dan sembunyikan data pribadi asli. |');
P();

P('## Naskah per adegan');
P();
TL.scenes.forEach((s, i) => {
  P(`<a id="adegan-${i}"></a>`);
  P(`### Adegan ${i} — ${s.title}`);
  P();
  P(`**Waktu:** ${mmss(s.start)}–${mmss(s.start + s.dur)} (${dur(s.dur)}) · **Jenis:** ${TYPE[s.type]}`);
  P();
  if (s.visual) P(`**Visual:** ${s.visual}`);
  if (s.url) P(`**Alamat di bilah peramban:** \`${s.url}\``);
  if (s.demo) P(`**Arahan demonstrasi (bila merekam aplikasi asli):** ${s.demo}`);
  const imgs = [...new Set([s.img, ...s.lines.map((l) => l.img)].filter(Boolean))];
  if (imgs.length) P(`**Aset:** ${imgs.map((f) => `[\`${f}\`](../manual-book/pmb/assets/${f})`).join(', ')}`);
  if (s.heading) {
    P();
    P(`**Teks layar:** judul "${plain(s.heading)}"${s.lead ? `; subjudul "${plain(s.lead)}"` : ''}${s.kicker ? `; label "${plain(s.kicker)}"` : ''}.`);
  }
  if (s.items && s.items.length) {
    P();
    P('**Butir yang ditampilkan:**');
    P();
    s.items.forEach((it, k) => P(`${k + 1}. **${plain(it.t)}**${it.d ? ` — ${plain(it.d)}` : ''}${it.who ? ` *(${plain(it.who)})*` : ''}`));
  }
  if (s.final) {
    P();
    P(`**Layar penutup:** "${plain(s.final)}" — ${plain(s.finalSub || '')}`);
  }
  P();
  P('| # | Waktu | Narasi (takarir) | Tindakan di layar | Teks pendukung di layar |');
  P('|---:|---:|---|---|---|');
  s.lines.forEach((l, k) => {
    let support = cardText(l.card);
    if (!support && s.items) {
      const shown = s.items.filter((it) => (it.at ?? 0) === k).map((it) => plain(it.t));
      if (shown.length) support = `Muncul: ${shown.join(', ')}`;
    }
    P(`| ${k + 1} | ${mmss(l.start)} | ${cell(l.cap)} | ${cell(l.act || '')} | ${cell(support)} |`);
  });
  P();
});

P('## Lampiran A — Ejaan pengucapan untuk pengisi suara');
P();
P('Takarir memakai ejaan asli; pengisi suara (manusia maupun sintetis) membacakannya seperti berikut.');
P();
P('| Tulisan | Dibaca |');
P('|---|---|');
const used = new Set();
for (const [re, rep] of SAY) {
  const src = re.source.replace(/\\b/g, '').replace(/\\\//g, '/').replace(/\[Rr\]/, 'R');
  if (/^[ ·—→/\\]/.test(src) || src === ' · ' || used.has(src)) continue;
  const all = TL.scenes.flatMap((s) => s.lines.map((l) => l.cap)).join(' ');
  if (!new RegExp(re.source, re.flags.replace('g', '')).test(all)) continue;
  used.add(src);
  P(`| ${src} | ${rep} |`);
}
P();

P('## Lampiran B — Cara membangun ulang video');
P();
P('Prasyarat: Node.js 20+, Microsoft Edge (atau Chrome dengan `BROWSER=chrome`), dan koneksi internet untuk suara sintetis.');
P();
P('```bash');
P('cd docs/video-tutorial-pmb/source');
P('npm install');
P(`node build_audio.mjs ${NAME}        # narasi -> ${NAME}.narration.wav + ${NAME}.timeline.js`);
P(`node build_naskah.mjs ${NAME}       # naskah ini`);
P(`node render.mjs ${NAME} snap 30,90  # cuplikan frame ke snaps/ untuk diperiksa`);
P(`node render.mjs ${NAME} video ../tutorial-pmb-${NAME}.mp4`);
P('```');
P();
P(`Pratinjau langsung di peramban: buka \`source/player.html?v=${NAME}&play\` (tanpa audio) atau \`&t=<detik>\` untuk satu frame. Ganti suara dengan variabel \`VOICE\` (mis. \`id-ID-ArdiNeural\`).`);
P();

const file = path.join(HERE, '..', `naskah-video-pmb-${NAME}.md`);
fs.writeFileSync(file, out.join('\n'));
console.log('ditulis', file, out.length, 'baris');
