# 08 — Role dan Hak Akses (RBAC)

[← Kembali ke dokumen utama](../RANCANGAN_SISTEM_PENERIMAAN_MAHASISWA_BARU_PMB.md)

Pemeriksaan memakai `PermissionRegistry` (tenant-aware) + Policy per model. Scope fakultas/prodi memakai kolom `user_roles.scope_type` / `scope_id` yang sudah ada. Pendaftar (`applicant`) bukan role di `roles`; ia adalah guard terpisah dengan aturan kepemilikan.

**Pemetaan ke role yang sudah ada.** CivitasOne sudah memiliki role template global (`university_id = null`, `is_system = true`) di `OrganizationalRoleSeeder`. Role PMB dalam dokumen ini adalah **nama fungsional**; implementasinya memakai ulang role yang ada bila fungsinya sama, dan menambah role template baru hanya untuk fungsi khusus PMB (dengan pola seeder yang sama):

| Role fungsional (dokumen ini) | Implementasi | Catatan |
|---|---|---|
| `super_admin` | `super_admin` (ada) | — |
| `pmb_admin` | **Baru** `admission_administrator` | Ketua/sekretariat panitia |
| `leadership` | `rector`, `vice_rector` (ada) + permission PMB baca | |
| `pmb_approver` | **Baru** `admission_approver` atau permission `pmb_results.approve` pada `rector`/`vice_rector` | Q-ANN-01 |
| `faculty_admin` | `dean` (ada) dengan scope fakultas | |
| `program_admin` | `head_of_study_program` (ada) dengan scope prodi | |
| `pmb_verifier` | **Baru** `admission_verifier` | |
| `finance_officer` | `finance_administrator` (ada) + permission `pmb_payments.*` | Kepala keuangan sebagai approver refund: role yang ditunjuk tenant |
| `selection_committee` | **Baru** `admission_selection_committee` | |
| `examiner` | `lecturer` (ada) / pegawai, ditambah permission `pmb_scores.input` yang dibatasi penugasan sesi | Akses dibatasi policy keanggotaan sesi |
| `re_registration_officer` | **Baru** `admission_registrar`, atau `academic_administrator` (ada) + permission PMB [K] | |
| `auditor` | `auditor` (ada) + permission PMB baca | |
| `applicant` | Guard `applicant` (bukan role) | KD-01 |
| (mahasiswa hasil konversi) | `student` (ada) | Diberikan saat konversi |

Prinsip: **least privilege** (role minimal, scope minimal) dan **separation of duties** (SoD: pengusul ≠ penyetuju ≠ pelaksana untuk tindakan kritis).

---

## 1. Katalog permission PMB

| Permission | Deskripsi |
|---|---|
| `pmb_config.read` | Melihat konfigurasi |
| `pmb_config.manage` | Mengelola periode, gelombang, jalur, wave path, kuota, persyaratan, template formulir |
| `pmb_config.publish` | Memublikasikan periode / template formulir |
| `pmb_fees.manage` | Mengelola biaya pendaftaran & biaya awal |
| `pmb_content.manage` | Mengelola CMS (halaman, FAQ, berita, brosur) |
| `pmb_program_profile.manage` | Mengelola profil prodi (ber-scope) |
| `pmb_applications.read` | Melihat daftar & detail aplikasi (ber-scope) |
| `pmb_applications.correct` | Mengoreksi data aplikasi dengan alasan |
| `pmb_applications.assisted_create` | Membuat akun/aplikasi atas nama pendaftar (loket) |
| `pmb_sensitive.read` | Membuka nilai field/dokumen sensitif (dicatat) |
| `pmb_documents.download` | Mengunduh dokumen pendaftar |
| `pmb_verification.decide` | Memutuskan verifikasi dokumen & aplikasi |
| `pmb_verification.assign` | Membagi tugas verifikasi |
| `pmb_verification.override` | Membuka kembali `verified`/`rejected` |
| `pmb_payments.read` | Melihat tagihan & transaksi |
| `pmb_payments.verify_manual` | Memverifikasi bukti bayar manual & mencatat pembayaran loket |
| `pmb_payments.reconcile` | Menjalankan & menutup rekonsiliasi |
| `pmb_refunds.request` | Mengajukan refund |
| `pmb_refunds.approve` | Menyetujui refund (via approval engine) |
| `pmb_exams.manage` | Mengelola jadwal, sesi, ruang, penempatan peserta & penguji |
| `pmb_exams.attendance` | Mencatat kehadiran sesi yang ditugaskan |
| `pmb_scores.input` | Menginput nilai peserta yang ditugaskan |
| `pmb_scores.import` | Mengimpor nilai massal |
| `pmb_scores.lock` | Mengunci nilai sesi |
| `pmb_selection.manage` | Mengelola skema seleksi & menjalankan run |
| `pmb_selection.lock_scheme` | Mengunci skema seleksi |
| `pmb_selection.submit` | Mengajukan pengesahan hasil |
| `pmb_results.approve` | Menyetujui hasil (langkah approval) |
| `pmb_results.read_unpublished` | Melihat hasil sebelum publikasi |
| `pmb_results.correct` | Mengajukan koreksi hasil setelah publikasi |
| `pmb_waitlist.promote` | Mempromosikan cadangan |
| `pmb_reregistration.read` | Melihat daftar ulang |
| `pmb_reregistration.decide` | Memvalidasi & menyetujui daftar ulang |
| `pmb_reregistration.extend` | Memperpanjang batas daftar ulang individual |
| `pmb_conversion.read` / `pmb_conversion.retry` | Melihat / mengulang konversi mahasiswa |
| `pmb_nim.configure` | Mengatur format NIM |
| `pmb_reports.read` | Melihat dashboard & laporan agregat |
| `pmb_reports.export` | Mengekspor laporan berisi data pribadi |
| `pmb_communication.broadcast` | Mengirim broadcast email/SMS |
| `pmb_communication.chat` | Menjawab quick chat |
| `pmb_audit.read` | Melihat audit log & log akses PMB |

## 2. Matriks role × permission

✓ = diberikan · S = diberikan dengan scope fakultas/prodi · A = hanya yang ditugaskan · — = tidak

| Permission | pmb_admin | leadership | pmb_approver | faculty_admin | program_admin | pmb_verifier | finance_officer | selection_committee | examiner | re_registration_officer | auditor |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| pmb_config.read | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ | ✓ |
| pmb_config.manage | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_config.publish | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_fees.manage | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_content.manage | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_program_profile.manage | ✓ | — | — | S | S | — | — | — | — | — | — |
| pmb_applications.read | ✓ | — | ✓ | S | S | ✓ | ✓ | ✓ | A | ✓ | ✓ |
| pmb_applications.correct | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_applications.assisted_create | ✓ | — | — | — | — | ✓ | — | — | — | — | — |
| pmb_sensitive.read | ✓ | — | — | — | — | ✓ | — | — | — | ✓ | ✓ |
| pmb_documents.download | ✓ | — | — | S | S | ✓ | — | ✓ | — | ✓ | ✓ |
| pmb_verification.decide | ✓ | — | — | — | — | ✓ | — | — | — | — | — |
| pmb_verification.assign | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_verification.override | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_payments.read | ✓ | — | — | — | — | ✓ | ✓ | — | — | ✓ | ✓ |
| pmb_payments.verify_manual | — | — | — | — | — | — | ✓ | — | — | — | — |
| pmb_payments.reconcile | — | — | — | — | — | — | ✓ | — | — | — | — |
| pmb_refunds.request | — | — | — | — | — | — | ✓ | — | — | — | — |
| pmb_refunds.approve | — | — | — | — | — | — | (role kepala keuangan via approval) | — | — | — | — |
| pmb_exams.manage | ✓ | — | — | — | — | — | — | ✓ | — | — | — |
| pmb_exams.attendance | ✓ | — | — | — | — | — | — | ✓ | A | — | — |
| pmb_scores.input | — | — | — | — | — | — | — | — | A | — | — |
| pmb_scores.import | — | — | — | — | — | — | — | ✓ | — | — | — |
| pmb_scores.lock | — | — | — | — | — | — | — | ✓ | — | — | — |
| pmb_selection.manage | — | — | — | — | — | — | — | ✓ | — | — | — |
| pmb_selection.lock_scheme | ✓ | — | — | — | — | — | — | ✓ | — | — | — |
| pmb_selection.submit | — | — | — | — | — | — | — | ✓ | — | — | — |
| pmb_results.approve | — | ✓* | ✓ | — | — | — | — | — | — | — | — |
| pmb_results.read_unpublished | ✓ | ✓* | ✓ | — | — | — | — | ✓ | — | — | ✓ |
| pmb_results.correct | ✓ | — | — | — | — | — | — | ✓ | — | — | — |
| pmb_waitlist.promote | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_reregistration.read | ✓ | — | — | S | S | — | ✓ | — | — | ✓ | ✓ |
| pmb_reregistration.decide | — | — | — | — | — | — | — | — | — | ✓ | — |
| pmb_reregistration.extend | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_conversion.read | ✓ | — | — | — | — | — | — | — | — | ✓ | ✓ |
| pmb_conversion.retry | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_nim.configure | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_reports.read | ✓ | ✓ | ✓ | S | S | ✓ | ✓ | ✓ | — | ✓ | ✓ |
| pmb_reports.export | ✓ | — | — | S | S | — | ✓ (keuangan) | ✓ (seleksi) | — | — | ✓ |
| pmb_communication.broadcast | ✓ | — | — | — | — | — | — | — | — | — | — |
| pmb_communication.chat | ✓ | — | — | — | — | ✓ | ✓ | — | — | ✓ | — |
| pmb_audit.read | ✓ | — | — | — | — | — | — | — | — | — | ✓ |

\* `leadership` mendapat `pmb_results.approve` / `read_unpublished` hanya bila tenant menunjuk pimpinan sebagai pengesah (Q-ANN-01); default diberikan ke `pmb_approver`.

`super_admin`: bypass penuh (perilaku `PermissionRegistry` yang ada). **Rekomendasi:** akses `super_admin` ke data pendaftar hanya melalui *support session* yang tercatat (tabel `support_sessions` sudah ada, MULTI-TENANT §9.4) — ditambahkan sebagai pekerjaan di checklist.

## 3. Rincian per role

| Role | Modul diakses | Lihat | Buat | Perbarui | Hapus | Butuh persetujuan | Batas lintas scope | Ekspor/unduh | Ubah konfigurasi | Data sensitif |
|---|---|---|---|---|---|---|---|---|---|---|
| Super Admin | Platform; aktivasi modul PMB | Semua tenant (via support session) | Tenant, aktivasi modul | Konfigurasi platform | — (tidak menghapus data PMB) | — | Lintas tenant | Tidak dari UI PMB | Platform saja | Hanya via support session tercatat |
| Administrator PMB | Semua modul PMB | Semua data tenant | Konfigurasi, konten, aplikasi loket, promosi cadangan | Konfigurasi, koreksi data, override verifikasi, perpanjangan | Draft konfigurasi yang belum dipakai; konten CMS | Publikasi hasil (tidak dapat sendiri), koreksi hasil, perpanjangan massal | Seluruh tenant | Semua laporan (tercatat) | Ya | Ya (tercatat) |
| Pimpinan PT | Dashboard, laporan | Agregat; detail hasil bila pengesah | — | — | — | — | Seluruh tenant | Agregat PDF/XLSX | Tidak | Tidak |
| Pejabat Pengesah | Approval hasil, laporan seleksi | Ringkasan & detail hasil sebelum publikasi | — | Keputusan approval | — | — (dia penyetuju) | Seluruh tenant | Laporan seleksi (tercatat) | Tidak | Tidak |
| Admin Fakultas | Pendaftar, profil prodi, laporan | Aplikasi yang memilih prodi di fakultasnya | — | Profil prodi fakultasnya | — | Publikasi profil prodi oleh Admin PMB | Hanya fakultasnya | Daftar pendaftar fakultasnya (tercatat) | Tidak | Tidak |
| Admin Prodi | Pendaftar, profil prodi, laporan | Aplikasi yang memilih prodinya | — | Profil prodinya | — | Idem | Hanya prodinya | Idem | Tidak | Tidak |
| Petugas Verifikasi | Verifikasi, chat | Aplikasi di antrean & yang diklaim | Aplikasi loket, catatan | Keputusan verifikasi aplikasi yang diklaim | — | Penolakan dapat dibatalkan Admin PMB | Sesuai filter jalur yang ditugaskan (opsional) | Unduh dokumen yang diperiksa | Tidak | Ya (tercatat) |
| Petugas Keuangan | Pembayaran, rekonsiliasi, refund, laporan keuangan | Tagihan, transaksi, bukti bayar | Pembayaran loket, batch rekonsiliasi, pengajuan refund | Keputusan bukti manual, item rekonsiliasi | — | Refund (disetujui kepala keuangan) | Seluruh tenant (data keuangan) | Laporan keuangan | Tidak (biaya diubah Admin PMB) | Tidak (kecuali rekening refund) |
| Panitia Seleksi | Ujian, seleksi | Aplikasi `verified`, nilai, hasil sebelum publikasi | Jadwal, sesi, skema, run | Nilai (impor), kunci nilai, override hasil sebelum pengajuan | Draft jadwal/skema yang belum dipakai | Pengesahan hasil, koreksi nilai terkunci | Seluruh tenant atau jalur yang ditugaskan | Laporan seleksi | Skema seleksi | Tidak |
| Penguji/Pewawancara | Sesi yang ditugaskan | Nama, no. peserta, foto, data minimum yang relevan (tanpa NIK/kontak) | Nilai & catatan rubrik | Nilai hingga sesi dikunci | — | — | Hanya peserta di sesinya | Tidak | Tidak | Tidak |
| Petugas Daftar Ulang | Daftar ulang, konversi (baca), chat | Pendaftar `accepted` | Catatan, checklist | Keputusan daftar ulang | — | — (SoD dengan verifikasi pembayaran manual) | Seluruh tenant | Unduh dokumen final | Tidak | Ya (tercatat) |
| Auditor | Semua modul, baca-saja | Semua data & log | — | — | — | — | Seluruh tenant | Laporan & log (tercatat) | Tidak | Ya (tercatat) |
| Calon Mahasiswa | Portal | Data & aplikasinya sendiri; keputusan setelah publikasi | Akun, aplikasi, unggahan, transaksi, chat | Draft, revisi yang dibuka, profil | Draft aplikasi miliknya (soft-delete → `void`) | — | Hanya miliknya (policy kepemilikan) | Kartu ujian, LoA, kuitansi, bukti registrasi miliknya | Tidak | Datanya sendiri |

## 4. Separation of duties dan kontrol tindakan kritis

| Tindakan | Kontrol |
|---|---|
| Pengesahan hasil seleksi | Pengaju (`pmb_selection.submit`) ≠ pengesah; workflow minimal 1 langkah (rekomendasi 2); approval engine menolak bila `requested_by` = approver. |
| Publikasi hasil | Hanya job sistem setelah `approved`; tidak ada aksi manual "publikasi". |
| Koreksi hasil & nilai terkunci | Pengajuan + approval; alasan & bukti wajib; notifikasi ke auditor (opsional). |
| Verifikasi pembayaran manual vs refund | Verifikator bukti ≠ pengaju refund ≠ penyetuju refund untuk transaksi yang sama (BR-20). |
| Verifikasi pembayaran manual vs persetujuan daftar ulang | Orang yang memverifikasi bukti bayar biaya awal suatu aplikasi tidak dapat memberi persetujuan final daftar ulang aplikasi tersebut. |
| Perubahan kuota setelah publikasi | Hanya Admin PMB, alasan wajib, tercatat; penambahan kuota untuk koreksi melalui approval koreksi. |
| Override verifikasi | Hanya Admin PMB, alasan wajib. |
| Akses data sensitif | Permission terpisah + pencatatan setiap pembukaan (`pmb_sensitive_access_logs`). |
| Ekspor data pribadi | Permission terpisah + alasan + log (`pmb_export_logs`). |

## 5. Penegakan teknis

1. Route middleware `permission:<slug>` (pola yang ada) untuk gerbang kasar.
2. Policy per model (`PmbApplicationPolicy`, `PmbInvoicePolicy`, ...) untuk scope & kepemilikan: `faculty_admin`/`program_admin` difilter berdasarkan `application_choices.study_program_id` ∈ scope; `examiner` berdasarkan keanggotaan sesi; pendaftar berdasarkan `applicant_account_id`.
3. Query scope `visibleTo($actor)` di repository daftar agar data di luar scope tidak pernah terambil (bukan sekadar disembunyikan di UI).
4. Field sensitif dihapus dari API Resource kecuali actor memiliki `pmb_sensitive.read` dan meminta secara eksplisit (`?reveal=nik`) — permintaan itu dicatat.
5. Test otorisasi wajib per endpoint (lihat [14 §B](14-edge-cases-testing-acceptance.md#b-strategi-testing)).
