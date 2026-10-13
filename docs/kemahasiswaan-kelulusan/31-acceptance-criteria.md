# 31 — Acceptance Criteria

> **Status:** rancangan · Disusun 2026-10-13 (commit `47043ca`) · Kembali ke [README](README.md)
> Test case: [30](30-skenario-pengujian.md). Aturan: [29](29-business-rules-dan-validasi.md).

## 1. Prinsip

- Setiap kriteria **terukur**: menyebut kondisi awal, tindakan, dan hasil yang dapat diperiksa (kode HTTP, nilai kolom, jumlah baris, waktu).
- Setiap kriteria ditautkan ke test case. Kriteria dianggap terpenuhi bila seluruh TC terkait lulus di CI (U/I/S) atau diterima pengguna (A).
- Angka kinerja diukur di lingkungan **staging acuan** (spesifikasi ditetapkan tim infrastruktur sebelum pengujian kinerja) ([KK-A-14](33-glossary-dan-keputusan-bisnis.md#4-asumsi-kk-a)).

## 2. Katalog Acceptance Criteria

### 2.1 Kemahasiswaan

| ID | Kriteria | Verifikasi | TC |
|---|---|---|---|
| KEM-AC-01 | Poin masuk ledger hanya setelah `approved`. Pengajuan `submitted` tidak menambah saldo | Feature test | KEM-TC-001, 002 |
| KEM-AC-02 | Rebuild ringkasan dari 1.000 entri = ringkasan tersimpan untuk 100% mahasiswa | Feature test | KEM-TC-015 |
| KEM-AC-03 | Override di luar rentang tanpa persetujuan → 409. Dengan persetujuan → entri ber-`approval_request_id` | Feature test | KEM-TC-006 |
| KEM-AC-04 | Perubahan aturan aktif tidak mengubah `awarded_points` entri lama | Feature test | KEM-TC-017 |
| KEM-AC-05 | Koreksi menambah baris. Baris lama tidak berubah (checksum sebelum = sesudah) | Feature test | KEM-TC-008 |
| KEM-AC-06 | Pengajuan tanpa bukti wajib → 422 untuk seluruh kategori uji | Feature test | KEM-TC-020 |
| KEM-AC-07 | Berkas identik: mahasiswa sama 409, mahasiswa lain flag `shared_evidence` | Feature test | KEM-TC-019, 023 |
| KEM-AC-08 | Verifikator tidak dapat menyetujui pengajuannya sendiri (403) | Security test | KEM-TC-028 |
| KEM-AC-09 | Setiap `approved` punya tepat 1 `award` aktif, dan setiap transisi punya baris `activity_verifications` | Uji invarian DB | KEM-TC-001, 020 |
| KEM-AC-10 | Impor 2.000 peserta < 60 detik dengan laporan baris gagal | Kinerja | KEM-TC-026 (+ uji kinerja) |
| KEM-AC-11 | Laporan/kasus tanpa keputusan final tidak memengaruhi `DisciplinaryRecordReader` dan eligibility | Feature test | KEM-TC-030 |
| KEM-AC-12 | Keputusan tidak `final` tanpa seluruh approval dan catatan pemeriksaan | Feature test | KEM-TC-032, 033 |
| KEM-AC-13 | Ubah sanksi → keputusan baru; lama `superseded` dapat dibaca auditor | Feature test | KEM-TC-034 |
| KEM-AC-14 | Pengguna `students.read` tanpa `violation_cases.read` → 403 di semua endpoint kasus | Security test | KEM-TC-037 |
| KEM-AC-15 | Batch yang membuat penerima > kuota ditolak tanpa perubahan data | Konkurensi | KEM-TC-051 |
| KEM-AC-16 | Event aktivasi diulang tidak membuat potongan ganda (bersama KEU) | Integrasi | KEM-TC-053 |
| KEM-AC-17 | Penghentian efektif T tidak mengubah tagihan < T | Integrasi | KEM-TC-060 |
| KEM-AC-18 | Tanpa `scholarship_applications.read` tidak dapat membaca data ekonomi | Security test | KEM-TC-056 |
| KEM-AC-19 | 1.000 penerbitan surat paralel → 1.000 nomor unik | Konkurensi | KEL-TC-061 |
| KEM-AC-20 | Ubah nama setelah terbit: unduhan ulang hash sama | Feature test | KEM-TC-072 |
| KEM-AC-21 | Surat `revoked` tampil "DIBATALKAN" di verifikasi publik | E2E | KEM-TC-073 |
| KEM-AC-22 | Mode `wet_signature`: PDF tanpa frasa keabsahan elektronik | Unit | KEM-TC-077 |

### 2.2 Yudisium & Dokumen

| ID | Kriteria | Verifikasi | TC |
|---|---|---|---|
| KEL-AC-01 | Tidak ada ambang numerik di kode checker. Parameter kosong → gagal simpan | Unit + statis | KEL-TC-005 |
| KEL-AC-02 | Versi `published` menolak semua perubahan (409) | Feature | KEL-TC-002 |
| KEL-AC-03 | 20 kombinasi selector uji teresolusi sesuai tabel spesifisitas | Unit | KEL-TC-003 |
| KEL-AC-04 | Simulasi tidak membuat baris hasil resmi | Feature | KEL-TC-008 |
| KEL-AC-05 | Setiap hasil memuat versi aturan, dan setiap item memuat `source.as_of` | Uji invarian | KEL-TC-020 |
| KEL-AC-06 | Perubahan nilai → stale ≤ 1 menit, recheck ≤ debounce + 2 menit | Integrasi | KEL-TC-026 |
| KEL-AC-07 | Verifikasi/penetapan dengan hasil stale memakai hasil baru | Feature | KEL-TC-032 |
| KEL-AC-08 | KEU tidak tersedia → `manual_review`, bukan `eligible` | Feature | KEL-TC-027 |
| KEL-AC-09 | Pemeriksaan massal 5.000 mahasiswa ≤ 15 menit | Kinerja | (uji kinerja) |
| KEL-AC-10 | Satu-satunya jalur `students.status → graduated` adalah finalisasi (uji pencarian kode + API) | Statis + feature | KEL-TC-048, 053 |
| KEL-AC-11 | `graduated` ditolak bila eligibility pra-penetapan bukan eligible/exempted | Feature | KEL-TC-045 |
| KEL-AC-12 | Predikat tersimpan = hasil evaluasi aturan untuk 100% data uji (override tercatat terpisah) | Unit | KEL-TC-046 |
| KEL-AC-13 | Koreksi → keputusan baru, lama `superseded`, antrean terbit ulang | Feature | KEL-TC-049 |
| KEL-AC-14 | Finalisasi berulang dengan kunci sama tidak membuat histori ganda | Feature | KEL-TC-048 |
| KEL-AC-15 | 1.000 issue paralel → 1.000 nomor unik | Konkurensi | KEL-TC-061 |
| KEL-AC-16 | Ubah nama setelah ijazah terbit tidak mengubah hash & snapshot | Feature | KEL-TC-066 |
| KEL-AC-17 | Dokumen `revoked`/`superseded` tetap di register & verifikasi | Feature | KEL-TC-065 |
| KEL-AC-18 | Petugas tanpa penugasan jenis `diploma` → 403 | Security | (UMUM-TC-030 varian) |
| KEL-AC-19 | Impor PIN menolak per baris tanpa menggagalkan berkas | Feature | KEL-TC-067 |

### 2.3 Wisuda

| ID | Kriteria | Verifikasi | TC |
|---|---|---|---|
| WIS-AC-01 | Tanpa keputusan final `graduated` tidak dapat mendaftar (mode default) | Feature | WIS-TC-002 |
| WIS-AC-02 | 200 penempatan paralel ke kapasitas 150 → 150 sukses, 50 `SESSION_FULL` | Konkurensi | WIS-TC-003 |
| WIS-AC-03 | QR yang sama 3× → 1 `checked_in` + 2 `duplicate_scan` | Feature | WIS-TC-005 |
| WIS-AC-04 | Edisi published immutable. Revisi = edisi baru dengan diff | Feature | WIS-TC-012 |
| WIS-AC-05 | Tanpa persetujuan publikasi foto, foto tidak tampil | Feature | WIS-TC-013 |

### 2.4 SKPI

| ID | Kriteria | Verifikasi | TC |
|---|---|---|---|
| SKPI-AC-01 | Draf dengan capaian belum terverifikasi tidak dapat diajukan (409) | Feature | SKPI-TC-003 |
| SKPI-AC-02 | Aktivitas approved menjadi kandidat ≤ 1 menit tanpa verifikasi ulang | Integrasi | SKPI-TC-001 |
| SKPI-AC-03 | PDF dua bahasa, istilah baku sesuai glosarium | Feature + review | SKPI-TC-005, 012 |
| SKPI-AC-04 | Tidak dapat terbit tanpa keputusan final | Feature | SKPI-TC-011 |
| SKPI-AC-05 | Verifikasi publik hanya data minimal tanpa capaian | Security | SKPI-TC-007 |

### 2.5 Tracer

| ID | Kriteria | Verifikasi | TC |
|---|---|---|---|
| TRACER-AC-01 | Bangun target 2× tanpa duplikat | Feature | TRACER-TC-001 |
| TRACER-AC-02 | Alumni tanpa catatan = "Belum diketahui", tidak terhitung "Belum bekerja" | Feature | TRACER-TC-006 |
| TRACER-AC-03 | Jawaban tidak mengubah `alumni` tanpa consent | Feature | TRACER-TC-005 |
| TRACER-AC-04 | Target dikecualikan tercatat beralasan | Feature | TRACER-TC-002 |
| TRACER-AC-05 | Versi published tidak dapat diubah (409) | Feature | TRACER-TC-017 |
| TRACER-AC-06 | Jawaban v1 tetap memakai teks v1 setelah v2 terbit | Feature | TRACER-TC-010 |
| TRACER-AC-07 | Kirim dua kali → 409, satu respons | Feature | TRACER-TC-013 |
| TRACER-AC-08 | Jawaban pertanyaan tersembunyi tidak tersimpan | Feature | TRACER-TC-012 |
| TRACER-AC-09 | Dua kampanye paralel tidak melampaui batas | Konkurensi | TRACER-TC-027 |
| TRACER-AC-10 | Pengingat & kirim ulang tidak menambah pemakaian | Feature | TRACER-TC-023, 030 |
| TRACER-AC-11 | Gagal sebelum diterima tidak mengurangi sisa | Feature | TRACER-TC-021 |
| TRACER-AC-12 | Retry 3× → 1 email, 1 `consume` | Feature | TRACER-TC-028 |
| TRACER-AC-13 | "Terkirim" dan "Selesai" dihitung terpisah | Feature | TRACER-TC-031 |
| TRACER-AC-14 | RR_ELG 40% & RR_INV 50% pada data uji, berlabel denominator | Unit | TRACER-TC-040 |
| TRACER-AC-15 | "Belum bekerja" 10 dan "Tidak menjawab" 15 terpisah | Unit | TRACER-TC-041 |
| TRACER-AC-16 | Ekspor tanpa izin identitas tanpa NIM/nama/email | Security | TRACER-TC-042 |
| TRACER-AC-17 | Agregat pimpinan menyupresi n < 5 | Unit | TRACER-TC-044 |

### 2.6 Career Center

| ID | Kriteria | Verifikasi | TC |
|---|---|---|---|
| KARIR-AC-01 | Paket tanpa whitelabel: tema default, pengaturan tetap tersimpan dan aktif setelah upgrade | E2E | KARIR-TC-001, 002 |
| KARIR-AC-02 | Profil baru `private` dan tidak ditemukan perusahaan | Feature | KARIR-TC-004 |
| KARIR-AC-03 | Staf U-A tidak melihat pelamar U-B pada lowongan marketplace yang sama | Security | KARIR-TC-005 |
| KARIR-AC-04 | Pemilik melihat setiap unduhan CV (waktu, perusahaan, dasar) | Feature | UMUM-TC-003 |
| KARIR-AC-05 | Publikasi tracer n < 5 tidak dapat diajukan | Feature | KARIR-TC-007 |
| KARIR-AC-06 | Lowongan tidak tampil sebelum disetujui moderator berwenang | Feature | KARIR-TC-010 |
| KARIR-AC-07 | Lamaran kedua → 409 | Feature | KARIR-TC-012 |
| KARIR-AC-08 | Perusahaan lain tidak dapat mengunduh CV (403, tercatat) | Security | KARIR-TC-015 |
| KARIR-AC-09 | Perubahan status lamaran memicu notifikasi ≤ 1 menit | Integrasi | KARIR-TC-013 |
| KARIR-AC-10 | Lowongan lewat deadline `closed` pada job berikutnya | Feature | KARIR-TC-027 |
| KARIR-AC-11 | Perusahaan `pending_verification` tidak dapat posting publik | Feature | KARIR-TC-026 |
| KARIR-AC-12 | Perekrut `suspended` tidak dapat mengubah status lamaran | Security | KARIR-TC-023 |
| KARIR-AC-13 | Token undangan terpakai/kedaluwarsa ditolak | Feature | KARIR-TC-022 |
| KARIR-AC-14 | CRM tenant A tidak terlihat tenant B & perusahaan | Security | KARIR-TC-024 |
| KARIR-AC-15 | Tidak ada endpoint perusahaan yang mengembalikan kandidat tanpa consent `granted` aktif | Security | KARIR-TC-031 |
| KARIR-AC-16 | Tanpa `recommendation_opt_in` tidak muncul di pencarian | Feature | KARIR-TC-030 |
| KARIR-AC-17 | Setiap kandidat `shared` punya `recommended_by`, `approved_by`, `criteria_snapshot`, `consent_id` | Uji invarian | KARIR-TC-030 |
| KARIR-AC-18 | Tenant Business → 403 `FEATURE_NOT_IN_PLAN` di endpoint rekomendasi | Security | KARIR-TC-034 |

### 2.7 Lintas modul

| ID | Kriteria | Verifikasi | TC |
|---|---|---|---|
| UMUM-AC-01 | Uji isolasi otomatis untuk setiap endpoint daftar baru | Security | UMUM-TC-001 |
| UMUM-AC-02 | Setiap aksi sensitif menghasilkan audit dengan aktor, waktu, alasan | Uji invarian | UMUM-TC-030 |
| UMUM-AC-03 | Tidak ada kolom token publik plaintext | Uji skema | TRACER-TC-032 |
| UMUM-AC-04 | Admin prodi A → 404 untuk data prodi B | Security | SKPI-TC-008, KEL-TC-044 |
| UMUM-AC-05 | Event sama 3× → 1 notifikasi per kanal | Integrasi | UMUM-TC-010 |
| UMUM-AC-06 | Undangan tracer ke alamat tanpa akun terkirim & tercatat | Integrasi | TRACER-TC-020 |
| UMUM-AC-07 | Template `en` dipakai sesuai preferensi, fallback `id` | Unit | UMUM-TC-012 |
| UMUM-AC-08 | Setiap item menu baru memiliki `permission` (dan `feature` bila berfitur) | Uji snapshot menu | — (frontend) |
| UMUM-AC-09 | Halaman daftar memiliki state loading/empty/error teruji | Uji komponen | — (frontend) |
| UMUM-AC-10 | Halaman publik tidak memuat bundle menu internal | Uji build | — (frontend) |
| UMUM-AC-11 | Check-in & survei lolos audit aksesibilitas otomatis di 375 px | Uji a11y | — (frontend) |

## 3. Keterlacakan Fitur → Acceptance Criteria

Setiap fitur di ruang lingkup kebutuhan memiliki minimal satu AC.

| Modul | Fitur kebutuhan | Dokumen | AC |
|---|---|---|---|
| Poin | Pengajuan aktivitas & prestasi | [06](06-rancangan-aktivitas-dan-prestasi.md) | KEM-AC-06, 07, 09 |
| Poin | Verifikasi & pemberian poin | [05](05-rancangan-poin-kemahasiswaan.md), [06](06-rancangan-aktivitas-dan-prestasi.md) | KEM-AC-01, 03, 08 |
| Poin | Master kategori & poin | [05](05-rancangan-poin-kemahasiswaan.md) | KEM-AC-04, KEL-AC-01 (pola) |
| Poin | Sertifikat & bukti | [06](06-rancangan-aktivitas-dan-prestasi.md) | KEM-AC-06, 07 |
| Poin | Riwayat poin | [05](05-rancangan-poin-kemahasiswaan.md) | KEM-AC-02, 05 |
| Poin | Pelanggaran & sanksi, masa berlaku | [07](07-rancangan-pelanggaran-dan-sanksi.md) | KEM-AC-11–14 |
| Poin | Beasiswa | [08](08-rancangan-beasiswa-mahasiswa.md) | KEM-AC-15–18 |
| Poin | Surat keterangan aktif | [09](09-rancangan-surat-keterangan-aktif.md) | KEM-AC-19–22 |
| Poin | Laporan aktivitas & prestasi | [06 §16](06-rancangan-aktivitas-dan-prestasi.md#16-laporan) | KEM-AC-09 (sumber data); laporan diuji melalui ekspor UMUM-AC-02 |
| Poin | Integrasi poin–yudisium | [05](05-rancangan-poin-kemahasiswaan.md), [11](11-rancangan-eligibilitas-yudisium.md) | KEL-AC-06 (KEL-TC-025) |
| Yudisium | Periode, konfigurasi persyaratan | [10](10-rancangan-periode-dan-syarat-yudisium.md) | KEL-AC-01–04 |
| Yudisium | Pemeriksaan kelayakan otomatis | [11](11-rancangan-eligibilitas-yudisium.md) | KEL-AC-05–09 |
| Yudisium | Verifikasi fakultas & prodi, calon peserta | [12](12-rancangan-peserta-dan-penetapan-kelulusan.md) | KEL-AC-07, 11 |
| Yudisium | Penetapan kelulusan, predikat | [12](12-rancangan-peserta-dan-penetapan-kelulusan.md) | KEL-AC-10–14 |
| Yudisium | SK yudisium, penomoran, ijazah & transkrip | [13](13-rancangan-penomoran-dokumen-kelulusan.md) | KEL-AC-15–19 |
| Wisuda | Periode, pendaftaran, pembayaran, kehadiran, pendamping, sesi | [14](14-rancangan-wisuda-dan-buku-wisuda.md) | WIS-AC-01–03 |
| Wisuda | Buku wisuda, laporan | [14](14-rancangan-wisuda-dan-buku-wisuda.md) | WIS-AC-04, 05 |
| SKPI | Pengumpulan, verifikasi, pemetaan capaian | [15](15-rancangan-skpi.md) | SKPI-AC-01, 02 |
| SKPI | Template dua bahasa, PDF, nomor | [15](15-rancangan-skpi.md) | SKPI-AC-03, 04 |
| SKPI | Validasi dokumen | [15](15-rancangan-skpi.md) | SKPI-AC-05 |
| Tracer | Periode & target | [16](16-rancangan-tracer-study.md) | TRACER-AC-01–04 |
| Tracer | Kuesioner, template, versi | [17](17-rancangan-kuesioner-dan-survei.md) | TRACER-AC-05–08 |
| Tracer | Distribusi, reminder, token, status, kuota | [18](18-rancangan-distribusi-dan-reminder-tracer.md) | TRACER-AC-09–13 |
| Tracer | Dashboard, ekspor, APT/APS | [19](19-rancangan-dashboard-dan-laporan-tracer.md) | TRACER-AC-14–17 |
| Karier | Marketplace, portal, whitelabel | [20](20-rancangan-career-center-karirlink.md) | KARIR-AC-01, 03 |
| Karier | Profil karier & CV, unduh CV | [20](20-rancangan-career-center-karirlink.md), [21](21-rancangan-lowongan-dan-lamaran-kerja.md) | KARIR-AC-02, 04, 08 |
| Karier | Lowongan, moderasi, lamaran, status, monitoring | [21](21-rancangan-lowongan-dan-lamaran-kerja.md) | KARIR-AC-06, 07, 09, 10 |
| Karier | Perusahaan mitra, undangan, CRM | [22](22-rancangan-mitra-perusahaan-dan-crm.md) | KARIR-AC-11–14 |
| Karier | Rekomendasi lulusan | [23](23-rancangan-rekomendasi-lulusan.md) | KARIR-AC-15–18 |
| Karier | Publikasi tracer, artikel, dashboard | [20](20-rancangan-career-center-karirlink.md) | KARIR-AC-05, UMUM-TC-020 |
| Lintas | Paket & entitlement | [34](34-matriks-paket-dan-entitlement.md) | UMUM-TC-020–025, KARIR-AC-18 |
| Lintas | Keamanan, notifikasi, UI | [27](27-rancangan-notifikasi-dan-email.md), [28](28-keamanan-privasi-dan-audit.md), [24](24-rancangan-ui-ux-dan-struktur-menu.md) | UMUM-AC-01–11 |

## 4. Definition of Done per Fitur

1. Migration dengan `down()` teruji, model memakai `TenantScoped`, dan FK sesuai [25](25-rancangan-database-dan-erd.md).
2. Endpoint terdaftar dengan `tenant.access`, `feature:`, `permission:`, serta policy scope.
3. Seluruh BR terkait memiliki test yang lulus. Uji isolasi tenant untuk endpoint daftar.
4. Audit & histori tercatat untuk aksi sensitif.
5. Notifikasi memakai `dedupe_key`.
6. Halaman frontend memenuhi pola [24 §4](24-rancangan-ui-ux-dan-struktur-menu.md#4-pola-halaman-standar).
7. Larastan & Pint lulus (keduanya Ada di `composer.json` dev dependencies).
8. Dokumentasi API (Scramble, Ada di `composer.json`) ter-generate.
