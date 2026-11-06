# 17 — Daftar & Masuk dengan Google (Portal Pendaftar PMB)

Calon mahasiswa dapat membuat akun dan masuk ke portal PMB dengan **email + kata sandi** (alur lama, tidak berubah) atau dengan **akun Google** (OAuth 2.0 authorization code + PKCE, OpenID Connect). Kedua metode memakai akun yang sama: tabel `pmb_applicant_accounts` dan token Sanctum ber-ability `applicant`.

## 1. Hasil audit sistem akun PMB (sebelum fitur ini)

| Aspek | Temuan |
|---|---|
| Akun | `pmb_applicant_accounts` (model `ApplicantAccount`), **terpisah dari `users` staf**, ter-scope tenant: email & HP unik **per universitas** (`unique(university_id, email)`). |
| Registrasi | `POST /pmb/applicant/auth/register` → `AccountService::register()`. Wajib persetujuan Kebijakan Privasi & Ketentuan (dicatat di `pmb_account_consents` beserta versi dokumen), CAPTCHA Turnstile di production, cek sandi bocor (HIBP) di production. Respons **identik** untuk email baru maupun yang sudah terdaftar (anti-enumerasi); pemilik email lama dikirimi email `registration_attempt`. |
| Verifikasi | Tautan email sekali pakai (`pmb_otp_challenges`, hanya hash yang disimpan, 24 jam) atau OTP SMS/WA. Status `unverified → active`. Membuat aplikasi pendaftaran mewajibkan akun terverifikasi (`pmb.applicant:verified`). |
| Login | `POST /pmb/applicant/auth/login` (email atau HP). Pesan generik, waktu respons seragam, 5 gagal/15 menit → `locked` dengan durasi eksponensial. Token Sanctum 12 jam *sliding*, maksimal 5 token aktif. |
| Sesi | SPA menyimpan token di `localStorage` (`civitasone-pmb-applicant`, zustand), dikirim sebagai `Authorization: Bearer`. Tidak ada cookie sesi di API. |
| Middleware | `pmb.public` (tenant dari domain / `X-University-Code`), `auth:sanctum` + `pmb.applicant` (token milik `ApplicantAccount`, tenant diikat dari akun, akun suspended/deactivated ditolak). |
| Relasi pendaftaran | `pmb_applications.applicant_account_id` → akun. Fitur Google **tidak mengubah** tabel/alur pendaftaran akademik. |

## 2. Alur

```
SPA (tab A)                         Backend API                                 Google
-----------                         -----------                                 ------
verifier acak → sessionStorage
GET  auth/google/redirect ───────►  state, nonce, PKCE verifier → cache 10 mnt
     ?client_challenge=S256(v)      (sekali pakai, terikat tenant & challenge)
◄─── { authorization_url } ───────
window.location = URL  ─────────────────────────────────────────────────────►  pilih akun, setujui
                                    GET auth/google/callback?state&code  ◄────  (redirect URI terdaftar)
                                    state diambil-dan-dihapus, code ditukar
                                    (client secret + code_verifier), ID token
                                    divalidasi → tiket acak (cache)
◄── 302 {PMB_FRONTEND_URL}/pmb/masuk/google#ticket=…   (atau #error=kode)
POST auth/google/session { ticket, client_verifier=v }
     → authenticated (token)  |  link_required  |  registration_required
POST auth/google/link     { ticket, client_verifier, password }        → token
POST auth/google/register { ticket, client_verifier, name, consents… } → token
```

### Kebijakan akun

| Kondisi setelah Google memverifikasi identitas | Hasil |
|---|---|
| `google_id` (klaim `sub`) sudah tertaut ke akun | Langsung masuk. |
| Email Google sama dengan akun lama yang **belum** tertaut | **Tidak** otomatis ditautkan. Pendaftar memasukkan kata sandi akun lama sekali (bukti memiliki kedua identitas); salah sandi dihitung seperti login biasa (penguncian). Sandi lama **tidak** diubah. Bila akun lama belum terverifikasi, kini ditandai terverifikasi (Google memverifikasi email + pemilik sandi terbukti). Email peringatan keamanan dikirim. |
| Email belum terdaftar | Pendaftar melengkapi **nama sesuai ijazah**, HP (opsional), sumber info, dan **persetujuan wajib** → akun dibuat `active`, email terverifikasi, `auth_provider = google`, sandi acak (`has_password = false`). Sandi bisa dibuat kapan saja lewat "Lupa kata sandi". |
| `email_verified = false` dari Google | Ditolak (`#error=email_unverified`), tidak ada akun dibuat/ditautkan. |
| Akun suspended/deactivated/anonim | Ditolak (`#error=account_inactive`). |
| Email sama tetapi sudah tertaut ke akun Google **lain** | Ditolak (`#error=account_conflict`). |

Akun email/sandi lama tetap bisa login seperti biasa; akun yang sudah ditautkan dapat memakai **kedua** metode.

## 3. Keamanan

- **ID token** divalidasi di backend (`Integrations/Google/GoogleIdTokenVerifier`): tanda tangan RS256 terhadap JWKS Google (di-cache sesuai `Cache-Control`, diambil ulang sekali saat rotasi `kid`), `iss` ∈ {`https://accounts.google.com`, `accounts.google.com`}, `aud` = client ID (dan `azp` bila `aud` jamak), `exp`/`iat` wajib dan berlaku (leeway 60 dtk), `nonce` cocok. Algoritme diambil dari JWKS, bukan header token.
- **state** (anti-CSRF) dan **PKCE S256** ke Google; keduanya sekali pakai, disimpan di cache sisi server karena API tidak memakai sesi cookie.
- **Pengikatan tab**: tiket hanya bisa ditukar dengan `client_verifier` yang tersimpan di `sessionStorage` tab yang memulai alur → tautan callback/tiket milik orang lain tidak bisa dipakai untuk *login CSRF*. Tiket juga terikat tenant.
- **Tanpa open redirect**: callback selalu mengarah ke `PMB_FRONTEND_URL` + `/pmb/masuk/google`; tujuan setelah masuk (`next`) disimpan di SPA dan hanya boleh `/pmb/portal…`. SPA juga menolak `authorization_url` yang bukan `https://accounts.google.com/`.
- **Token sesi tidak pernah di URL**: URL hanya membawa tiket acak berumur pendek (login 2 menit, tautkan/daftar 15 menit) di fragmen `#` (tidak terkirim ke server/log, dihapus dari riwayat oleh SPA). Callback mengirim `Referrer-Policy: no-referrer` dan `Cache-Control: no-store`.
- **Client secret** hanya dipakai backend saat menukar code; frontend hanya menerima flag `site.auth.google`. Access/refresh token Google **tidak disimpan**.
- `prompt=select_account` selalu menampilkan pemilih akun (penting di komputer bersama/lab sekolah).
- Batas laju: `pmb-login` (10/menit/IP) untuk endpoint JSON, `pmb-public` untuk callback.
- Retensi/anonimisasi (`RetentionService::anonymize`) menghapus `google_id`, `google_linked_at`, `avatar_url`.
- Production wajib HTTPS (Google menolak redirect URI `http://` selain `localhost`).

## 4. Kode

| Lapisan | Berkas |
|---|---|
| Migrasi | `Admission/Database/Migrations/2026_11_06_200000_add_google_sign_in_to_pmb_applicant_accounts.php` — kolom `google_id` (unik per tenant), `auth_provider`, `has_password`, `google_linked_at`, `avatar_url`. Hanya menambah kolom. |
| Kontrak & integrasi | `Contracts/GoogleIdentityProvider`, `Integrations/Google/{SocialiteGoogleIdentityProvider, GoogleIdTokenVerifier, GoogleIdentity, GoogleAuthException}` |
| Layanan | `Services/Account/GoogleAuthService` (alur & kebijakan); `AccountService::assertPassword()` / `startSession()` dipisah dari `login()` agar dipakai bersama (perilaku login lama tidak berubah). |
| HTTP | `Controllers/Applicant/GoogleAuthController`, rute di `Routes/api.php` |
| Frontend | `pmb/lib/googleAuth.ts` (PKCE & penyimpanan alur), `pmb/components/GoogleSignInButton.tsx` (tombol resmi + pemisah "ATAU"), `pmb/pages/auth/AuthPages.tsx` (`GoogleCallbackPage`, form tautkan & lengkapi pendaftaran) |
| Tes | `Tests/Feature/GoogleSignInTest.php`, `Tests/Unit/GoogleIdTokenVerifierTest.php`, `Tests/Support/FakeGoogleIdentityProvider.php` |

### Endpoint (prefix `/api/v1/pmb/applicant/auth/google`)

| Metode | Path | Keterangan |
|---|---|---|
| GET | `redirect?client_challenge=` | Header kampus wajib. → `{ authorization_url }`; 503 `GOOGLE_NOT_CONFIGURED` bila kredensial kosong. |
| GET | `callback` | **Redirect URI** Google. Selalu 302 ke frontend. |
| POST | `session` | `{ ticket, client_verifier }` → `authenticated` / `link_required` / `registration_required`. |
| POST | `link` | `+ password` → `authenticated`. |
| POST | `register` | `+ name, phone?, info_source?, consents{privacy_policy, terms, marketing_optional?}` → 201 `authenticated`. |

Respons `authenticated` sama dengan `POST /auth/login`: `{ token, expires_at, account }`. `account` kini juga memuat `auth_provider`, `google_linked`, `has_password`, `avatar_url`.

## 5. Konfigurasi

Backend `.env` (lihat `config/services.php` → `google`):

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/api/v1/pmb/applicant/auth/google/callback"
PMB_FRONTEND_URL="${FRONTEND_URL}"   # tujuan setelah callback: {PMB_FRONTEND_URL}/pmb/masuk/google
```

Kosong = tombol Google tidak tampil. Callback berada di bawah `/api/v1/pmb/...` (bukan `/auth/google/callback`) agar ikut grup rute, batas laju, dan penanganan tenant modul PMB — sama seperti URL webhook Xendit `${APP_URL}/api/v1/webhooks/xendit`. Setelah mengubah `.env` di server yang memakai cache konfigurasi, jalankan `php artisan config:cache`.

PHP harus bisa memverifikasi sertifikat HTTPS Google: pastikan `curl.cainfo` dan `openssl.cafile` di `php.ini` menunjuk ke CA bundle (mis. `cacert.pem` dari https://curl.se/ca/cacert.pem). Tanpa itu callback berakhir `#error=google_failed` dan log berisi `cURL error 60`.

## 6. Google Cloud Console (manual)

1. Buka https://console.cloud.google.com → pilih/buat **project** (mis. "CivitasOne PMB").
2. **APIs & Services → OAuth consent screen** (Google Auth Platform → Branding / Audience / Data Access):
   - User type **External**; isi nama aplikasi, email dukungan, logo (opsional), domain aplikasi, tautan kebijakan privasi & ketentuan (halaman `/pmb/legal/...`), email developer.
   - Scope: cukup `openid`, `.../auth/userinfo.email`, `.../auth/userinfo.profile` (non-sensitif → tidak perlu verifikasi aplikasi Google).
   - Selama status **Testing**, hanya akun di daftar *Test users* yang bisa masuk. Untuk pendaftar umum, klik **Publish app** (In production).
   - Tidak perlu mengaktifkan API tambahan; Sign-In memakai endpoint OpenID Connect standar.
3. **APIs & Services → Credentials → Create credentials → OAuth client ID**, tipe **Web application**:
   - **Authorized redirect URIs** (harus persis sama dengan `GOOGLE_REDIRECT_URI`, termasuk skema, port, tanpa garis miring di akhir):
     - lokal: `http://localhost:8000/api/v1/pmb/applicant/auth/google/callback`
     - production: `https://<domain-backend>/api/v1/pmb/applicant/auth/google/callback` (mis. `https://beoneatma.santani.dev/api/v1/pmb/applicant/auth/google/callback`)
   - **Authorized JavaScript origins**: tidak diperlukan untuk alur ini (frontend tidak memuat SDK Google). Boleh diisi origin frontend bila nanti memakai Google One Tap.
4. Salin **Client ID** dan **Client secret** ke `.env` backend. Secret tidak pernah dimasukkan ke `frontend/.env`.
5. Perubahan redirect URI kadang butuh beberapa menit sebelum berlaku. Gejala salah konfigurasi: halaman Google "Error 400: redirect_uri_mismatch".

## 7. Pengujian

`php artisan test app/Modules/Admission/Tests/Feature/GoogleSignInTest.php app/Modules/Admission/Tests/Unit/GoogleIdTokenVerifierTest.php` — Google diganti fake di tes fitur; validasi ID token & penukaran code via Socialite diuji dengan kunci RSA uji, JWKS palsu (`Http::fake`) dan Guzzle `MockHandler`. Mencakup: registrasi & login email lama, registrasi baru via Google, login ulang via Google, email yang sudah terdaftar (wajib sandi, tanpa duplikat, sandi lama utuh), pembatalan, email Google belum terverifikasi, kegagalan callback/state palsu/state dipakai ulang, tiket dari tab/kampus lain, akun dibekukan, konflik akun Google, kredensial tidak dikonfigurasi, secret tidak bocor, dan anonimisasi.

Uji manual (setelah Console dikonfigurasi): buka `/pmb/daftar` → "Daftar dengan Google" → pilih akun → lengkapi data → masuk ke `/pmb/portal`. Keluar, lalu `/pmb/masuk` → "Lanjutkan dengan Google" → langsung masuk.

## 8. Batasan & lanjutan

- Satu OAuth client untuk seluruh tenant; layar persetujuan menampilkan nama aplikasi platform, bukan nama tiap kampus.
- Belum ada fitur memutus tautan Google dari halaman akun.
- Alamat Gmail dengan variasi titik (`nama.saya@` vs `namasaya@`) dianggap email berbeda, sama seperti registrasi email.
