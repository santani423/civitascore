# CivitasOne — Frontend

Aplikasi frontend React.js untuk Sistem Manajemen Akademik Universitas. Berdiri sendiri, terpisah sepenuhnya dari backend Laravel di `../app` — tidak memakai Blade, Inertia, atau Livewire. Komunikasi ke backend nantinya murni lewat REST API.

## Menjalankan

```bash
cd frontend
npm install
cp .env.example .env   # sesuaikan VITE_API_BASE_URL bila backend tidak di localhost:8000
npm run dev
```

Buka `http://localhost:5173`. Login dengan akun demo:

```
Email: admin@demo.test
Password: password
```

Build produksi:

```bash
npm run build   # tsc -b && vite build -> dist/
npm run preview # menjalankan hasil build secara lokal
```

## Status fase ini

Fokus fase ini adalah fondasi frontend + dua halaman (Login, Dashboard), **bukan** integrasi API penuh:

- Autentikasi disimulasikan secara lokal (`src/services/authService.ts`) — sesi disimpan di localStorage lewat Zustand (`src/stores/authStore.ts`). Setiap fungsi service sudah berbentuk `async` dan melempar bentuk error yang sama dengan interceptor Axios (`src/services/api.ts`), supaya nanti tinggal mengganti isi fungsi dengan pemanggilan `apiClient` — pemanggil (halaman, store) tidak perlu berubah.
- Seluruh menu sidebar selain Dashboard mengarah ke `PlaceholderPage` yang jujur menyatakan modul belum tersedia (bukan data palsu yang terlihat nyata).
- Seluruh data dashboard adalah mock data di `src/data/mock/*.ts`, dibentuk menyerupai response API asli (termasuk field yang akan datang dari backend) agar penggantian ke data nyata nanti minim perubahan struktur.

## Struktur

```text
src/
├── components/
│   ├── ui/          # Design system: Button, Input, Card, Modal, DataTable, dst — reusable, tidak tahu soal bisnis
│   ├── layout/       # Sidebar, Header, ThemeToggle — shell aplikasi
│   ├── dashboard/    # Komposisi khusus dashboard (chart, ringkasan, tabel approval)
│   ├── forms/        # (disiapkan untuk form-form modul mendatang)
│   └── common/       # (disiapkan untuk komponen lintas-modul mendatang)
├── pages/
│   ├── auth/LoginPage.tsx
│   ├── dashboard/DashboardPage.tsx
│   └── placeholders/PlaceholderPage.tsx
├── layouts/          # AuthLayout (dua kolom), DashboardLayout (sidebar + header + outlet)
├── routes/           # ProtectedRoute, PublicOnlyRoute (route guard)
├── services/         # api.ts (axios client + interceptor), authService.ts
├── stores/           # authStore, themeStore (Zustand + persist ke localStorage)
├── types/            # Kontrak TypeScript, termasuk bentuk envelope API backend
├── constants/        # routes.ts, nav.ts (struktur menu sidebar), app.ts
├── data/mock/         # Mock data dashboard, aktivitas, notifikasi, agenda, approval
├── utils/            # cn() (classname merge), formatters (Rupiah, tanggal, angka)
└── styles/tokens.css  # Design token warna (lihat di bawah)
```

## Design token warna

Diturunkan dari logo Universitas Katolik Atma Jaya (`../app/docs/Logo_unika-atmajaya.gif`, disalin ke `src/assets/images/`): hijau tua dari perisai sebagai primary, emas/oranye dari burung merpati dan sinar sebagai accent. Semua warna disimpan sebagai CSS variable di `src/styles/tokens.css` (light di `:root`, dark di `.dark`) dan dipetakan ke Tailwind di `tailwind.config.ts` — komponen memakai kelas seperti `bg-primary`, `text-ink-secondary`, `border-border`, bukan hex langsung, supaya ganti tema/warna cukup di satu tempat.

Dark mode: toggle di header, preferensi disimpan di localStorage, default mengikuti preferensi sistem (`prefers-color-scheme`) saat pertama kali dibuka.

## Konvensi yang dipakai

- Alias import `@/*` → `src/*` (lihat `vite.config.ts` dan `tsconfig.app.json`).
- Tidak ada URL API atau warna hex yang ditulis langsung di komponen halaman — selalu lewat `services/api.ts` dan `styles/tokens.css`.
- Setiap komponen `ui/` mendukung state disabled/loading/error yang relevan dan dapat dipakai ulang lintas modul.
