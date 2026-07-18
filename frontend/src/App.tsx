import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { useThemeSync } from '@/hooks/useThemeSync'
import { ProtectedRoute } from '@/routes/ProtectedRoute'
import { PublicOnlyRoute } from '@/routes/PublicOnlyRoute'
import { AuthLayout } from '@/layouts/AuthLayout'
import { DashboardLayout } from '@/layouts/DashboardLayout'
import { LoginPage } from '@/pages/auth/LoginPage'
import { DashboardPage } from '@/pages/dashboard/DashboardPage'
import { PlaceholderPage } from '@/pages/placeholders/PlaceholderPage'
import { ROUTES } from '@/constants/routes'

const PLACEHOLDER_ROUTES: Array<{ path: string; title: string }> = [
  { path: ROUTES.akademik.kurikulum, title: 'Kurikulum' },
  { path: ROUTES.akademik.mataKuliah, title: 'Mata Kuliah' },
  { path: ROUTES.akademik.kelasJadwal, title: 'Kelas dan Jadwal' },
  { path: ROUTES.akademik.krs, title: 'KRS' },
  { path: ROUTES.akademik.absensi, title: 'Absensi' },
  { path: ROUTES.akademik.penilaian, title: 'Penilaian' },
  { path: ROUTES.mahasiswa, title: 'Mahasiswa' },
  { path: ROUTES.dosen, title: 'Dosen' },
  { path: ROUTES.pegawai, title: 'Pegawai' },
  { path: ROUTES.keuangan.tagihan, title: 'Tagihan' },
  { path: ROUTES.keuangan.beasiswa, title: 'Beasiswa' },
  { path: ROUTES.skripsi, title: 'Skripsi' },
  { path: ROUTES.magangMbkm, title: 'Magang dan MBKM' },
  { path: ROUTES.perpustakaan, title: 'Perpustakaan' },
  { path: ROUTES.alumni, title: 'Alumni' },
  { path: ROUTES.pengumuman, title: 'Pengumuman' },
  { path: ROUTES.laporan, title: 'Laporan' },
  { path: ROUTES.pengaturan, title: 'Pengaturan' },
]

function App() {
  useThemeSync()

  return (
    <BrowserRouter>
      <Routes>
        <Route element={<PublicOnlyRoute />}>
          <Route element={<AuthLayout />}>
            <Route path={ROUTES.login} element={<LoginPage />} />
          </Route>
        </Route>

        <Route element={<ProtectedRoute />}>
          <Route element={<DashboardLayout />}>
            <Route path={ROUTES.dashboard} element={<DashboardPage />} />
            {PLACEHOLDER_ROUTES.map((route) => (
              <Route key={route.path} path={route.path} element={<PlaceholderPage title={route.title} />} />
            ))}
          </Route>
        </Route>

        <Route path="/" element={<Navigate to={ROUTES.dashboard} replace />} />
        <Route path="*" element={<Navigate to={ROUTES.dashboard} replace />} />
      </Routes>
    </BrowserRouter>
  )
}

export default App
