# One Atma Talent — Review dan Publikasi Hasil

> **Per:** 5 November 2026. **Terkait:** [03](03-admin-management.md), [05](05-versioning-and-approval.md).

## 1. Alur Hasil

```
Sesi submitted / expired (dengan skor)
        │
        ▼
Review ──► (opsional) tandai perlu review ──► selesaikan review
        │
        ▼
Checklist kelengkapan terpenuhi ──► Terbitkan ──► Peserta melihat hasil
```

Hasil hanya tampil ke peserta setelah sesi berstatus `published`. Sebelum itu, endpoint hasil peserta mengembalikan `409 RESULT_NOT_PUBLISHED`.

## 2. Status Review

| Status | Arti | Efek pada publikasi |
|---|---|---|
| `none` | Belum direview | Tidak menahan. |
| `flagged` | Ditandai perlu review | **Menahan** publikasi (`REVIEW_REQUIRED`). |
| `reviewed` | Review selesai | Tidak menahan. |

Penandaan dan penyelesaian review dicatat di `pmb_assessment_review_notes`. Catatan bersifat append-only dan tidak pernah diubah atau dihapus. Setiap entri menyimpan jenis (`note`, `flag`, `unflag`, `review_completed`), penulis, isi, dan waktu.

Review tidak dapat dilakukan pada sesi `invalidated` atau `published` (`SESSION_INVALIDATED`, `ALREADY_PUBLISHED`).

## 3. Checklist Publikasi

Ruang review (`GET assessment-sessions/{session}/review`) menampilkan empat item. Semua harus terpenuhi agar `can_publish` bernilai `true`:

| Kunci | Syarat |
|---|---|
| `status` | Sesi `submitted` atau `expired`. |
| `scores` | Skor tersedia. Sesi `expired` tanpa jawaban lengkap tidak punya skor. |
| `review` | Tidak ditandai `flagged`. |
| `not_invalidated` | Sesi tidak diinvalidasi. |

Publikasi juga ditolak bila tanpa skor (`NO_SCORES`). Pemeriksaan ini dijalankan ulang di service, tidak hanya di UI.

## 4. Publikasi

- Izin: `pmb_assessment_results.publish`, dimiliki pejabat pengesah.
- Transisi: `submitted` atau `expired` → `published`, dicatat dengan `StateTransition`.
- Event `published` menyimpan `version_id`, `scoring_engine`, dan `holland_code`.
- Riwayat publikasi di ruang review membaca event ini, sehingga setiap hasil terbit dapat ditelusuri ke sesi, versi instrumen, versi scoring, dan kejadian publikasi.

Tes `tanda perlu review menahan publikasi hingga review selesai` memeriksa bahwa `publication_history` berisi satu entri dengan `version_id` yang benar dan `scoring_engine = riasec-mean-v1`.

## 5. Invalidasi

Invalidasi adalah keputusan admin dengan alasan. Izin: `pmb_assessment_sessions.reject`.

- Alasan wajib, minimal 10 karakter.
- Status menjadi `invalidated`. Alasan, waktu, dan pelaku disimpan di sesi dan di audit.
- Respons tetap disimpan untuk audit. Sesi tidak lagi dihitung dan hasilnya tidak tampil.
- Sesi yang sudah `published` tidak dapat diinvalidasi (`INVALID_TRANSITION`, 422). Untuk membatalkan hasil terbit, dibutuhkan proses koreksi yang belum dibuat.
- Peserta melihat keterangan "Asesmen dibatalkan panitia" dan diarahkan menghubungi panitia.

Tidak ada tombol untuk mengubah jawaban peserta, memperpanjang waktu, atau membuka ulang sesi. Ini disengaja (prompt Fase 2, bagian 8).

## 6. Distribusi Hasil di Dashboard

Distribusi Holland Code hanya ditampilkan ke pemegang `pmb_assessment_results.read`, dan hanya bila jumlah sesi terbit minimal 10. Di bawah itu, dashboard menampilkan pesan bahwa distribusi ditahan. Label dimensi (Realistic, Investigative, dan seterusnya) adalah nama tetap untuk pengelompokan, bukan interpretasi psikometrik.

## 7. Aturan yang Tidak Berubah

- Algoritma `riasec-mean-v1` tidak diubah di fase ini. Tes regresi scorer (6 tes unit) dan alur peserta (6 tes feature) tetap lulus.
- Tidak ada norma atau kategori interpretasi baru. Interpretasi per dimensi tetap ditulis tim asesmen di kolom `interpretation`.

## 8. Belum Selesai

- Penerbitan hasil massal. Saat ini per sesi.
- Pemberitahuan kepada peserta saat hasil terbit.
- Persetujuan dua langkah untuk penerbitan hasil. Izin sudah dipisahkan (penelaah menandai, pejabat pengesah menerbitkan), tetapi belum lewat approval engine.
