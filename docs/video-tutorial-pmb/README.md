# Video Tutorial PMB — Civitas One

Dua video tutorial Modul Penerimaan Mahasiswa Baru (PMB) beserta naskah produksinya. Video memakai screenshot aplikasi yang berjalan (data demo fiktif) dari [`docs/manual-book/pmb/assets`](../manual-book/pmb/assets/), dengan zoom, bingkai sorotan, kursor, kartu teks pendukung, takarir, dan narasi berbahasa Indonesia.

| Video | Untuk | Durasi | Naskah produksi | Manual book pendamping |
|---|---|---:|---|---|
| [`tutorial-pmb-calon-mahasiswa.mp4`](tutorial-pmb-calon-mahasiswa.mp4) | Calon mahasiswa & orang tua/wali | 17:28 | [naskah-video-pmb-calon-mahasiswa.md](naskah-video-pmb-calon-mahasiswa.md) | [Manual Book PMB untuk Calon Mahasiswa](../manual-book/pmb/MANUAL-BOOK-PMB-CALON-MAHASISWA.md) |
| [`tutorial-pmb-penyelenggara.mp4`](tutorial-pmb-penyelenggara.mp4) | Panitia & staf PMB (semua peran) | 18:37 | [naskah-video-pmb-penyelenggara.md](naskah-video-pmb-penyelenggara.md) | [Manual Book PMB untuk Penyelenggara](../manual-book/pmb/MANUAL-BOOK-PMB-PENYELENGGARA.md) |

Spesifikasi: MP4 H.264 1920 × 1080, 25 fps, audio AAC 44,1 kHz (dinormalisasi −16 LUFS), takarir tertanam. Narasi memakai suara sintetis Microsoft Edge `id-ID-GadisNeural`; naskah dapat dibacakan ulang oleh pengisi suara manusia (ejaan pengucapan ada di Lampiran A tiap naskah).

## Struktur sumber

| Berkas | Fungsi |
|---|---|
| `source/calon-mahasiswa.script.mjs`, `source/penyelenggara.script.mjs` | **Data naskah**: adegan, narasi, tindakan layar, teks pendukung, screenshot, area zoom, sorotan, titik klik |
| `source/common.mjs` | Helper koordinat & kamus ejaan pengucapan |
| `source/build_audio.mjs` | Narasi TTS per kalimat (cache di `audio/`) → `<nama>.narration.wav` + `<nama>.timeline.js` |
| `source/build_naskah.mjs` | Membangkitkan naskah produksi Markdown dari timeline (narasi, waktu, tindakan, teks layar selalu sama dengan video) |
| `source/player.html` | Pemutar adegan (`?v=<nama>`) yang digambar frame demi frame |
| `source/render.mjs` | Menangkap frame dengan Microsoft Edge (Playwright) dan menggabungkan dengan narasi (ffmpeg) |

## Membangun ulang

```bash
cd docs/video-tutorial-pmb/source
npm install
node build_audio.mjs calon-mahasiswa
node build_naskah.mjs calon-mahasiswa
node render.mjs calon-mahasiswa snap 30,120          # cuplikan frame ke snaps/ untuk diperiksa
node render.mjs calon-mahasiswa video ../tutorial-pmb-calon-mahasiswa.mp4
# ganti calon-mahasiswa dengan penyelenggara untuk video kedua
```

Pratinjau tanpa render: buka `source/player.html?v=calon-mahasiswa&play` di peramban (tanpa suara). Prasyarat: Node.js 20+, Microsoft Edge (atau `BROWSER=chrome`), koneksi internet untuk TTS. Render penuh ±22–25 menit per video dengan 6 worker.

## Catatan akurasi

- Isi mengikuti rancangan [`docs/pmb`](../pmb/) dan manual per bab [`docs/manual-book/pmb`](../manual-book/pmb/README.md), dicocokkan dengan kode terkini. Perbedaan rancangan–implementasi dan hal yang perlu dikonfirmasi kampus disebutkan di video (adegan *Kendala Umum* dan *Perlu Dikonfirmasi Kampus*) dan dirinci di manual book.
- Screenshot diambil 14 Oktober 2026. Pembaruan sesudahnya (baris filter pada tabel konfigurasi, editor teks kaya profil prodi) disebutkan di narasi/teks pendukung, tetapi belum tampak pada gambar.
- Branding di video netral (*Civitas One*). Untuk kampus tertentu, ganti logo/nama di `source/player.html` dan alamat contoh di skrip naskah, lalu bangun ulang.
