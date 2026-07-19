import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { useThemeSync } from '@/hooks/useThemeSync'
import { ProtectedRoute } from '@/routes/ProtectedRoute'
import { PublicOnlyRoute } from '@/routes/PublicOnlyRoute'
import { AuthLayout } from '@/layouts/AuthLayout'
import { DashboardLayout } from '@/layouts/DashboardLayout'
import { LoginPage } from '@/pages/auth/LoginPage'
import { ForgotPasswordPage } from '@/pages/auth/ForgotPasswordPage'
import { ResetPasswordPage } from '@/pages/auth/ResetPasswordPage'
import { DashboardPage } from '@/pages/dashboard/DashboardPage'
import { PlatformDashboardPage } from '@/pages/platform/PlatformDashboardPage'
import { UniversitiesPage } from '@/pages/platform/UniversitiesPage'
import { SupportSessionsPage } from '@/pages/platform/SupportSessionsPage'
import { PlaceholderPage } from '@/pages/placeholders/PlaceholderPage'
import { useIsSuperAdmin } from '@/hooks/useIsSuperAdmin'
import { RolesPage } from '@/pages/settings/RolesPage'
import { PermissionsPage } from '@/pages/settings/PermissionsPage'
import { UserRolesPage } from '@/pages/settings/UserRolesPage'
import { SystemSettingsPage } from '@/pages/settings/SystemSettingsPage'
import { FeatureFlagsPage } from '@/pages/settings/FeatureFlagsPage'
import { NotificationsPage } from '@/pages/settings/NotificationsPage'
import { AuditLogPage } from '@/pages/settings/AuditLogPage'
import { SecuritySessionsPage } from '@/pages/settings/SecuritySessionsPage'
import { ApprovalRequestsPage } from '@/pages/approvals/ApprovalRequestsPage'
import { ApprovalRequestDetailPage } from '@/pages/approvals/ApprovalRequestDetailPage'
import { ApprovalWorkflowsPage } from '@/pages/approvals/ApprovalWorkflowsPage'
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
]

/** Super Admin lihat Dashboard Platform (statistik lintas-universitas), pengguna tenant lihat Dashboard operasional biasa — lihat docs/RANCANGAN-SUPER-ADMIN.md §1-2. */
function DashboardRoute() {
  const isSuperAdmin = useIsSuperAdmin()

  return isSuperAdmin ? <PlatformDashboardPage /> : <DashboardPage />
}

function App() {
  useThemeSync()

  return (
    <BrowserRouter>
      <Routes>
        <Route element={<PublicOnlyRoute />}>
          <Route element={<AuthLayout />}>
            <Route path={ROUTES.login} element={<LoginPage />} />
            <Route path={ROUTES.forgotPassword} element={<ForgotPasswordPage />} />
            <Route path={ROUTES.resetPassword} element={<ResetPasswordPage />} />
          </Route>
        </Route>

        <Route element={<ProtectedRoute />}>
          <Route element={<DashboardLayout />}>
            <Route path={ROUTES.dashboard} element={<DashboardRoute />} />

            <Route path={ROUTES.platform.universities} element={<UniversitiesPage />} />
            <Route path={ROUTES.platform.security} element={<SupportSessionsPage />} />

            <Route path={ROUTES.pengaturan.roles} element={<RolesPage />} />
            <Route path={ROUTES.pengaturan.permissions} element={<PermissionsPage />} />
            <Route path={ROUTES.pengaturan.userRoles} element={<UserRolesPage />} />
            <Route path={ROUTES.pengaturan.systemSettings} element={<SystemSettingsPage />} />
            <Route path={ROUTES.pengaturan.featureFlags} element={<FeatureFlagsPage />} />
            <Route path={ROUTES.pengaturan.notifications} element={<NotificationsPage />} />
            <Route path={ROUTES.pengaturan.auditLog} element={<AuditLogPage />} />
            <Route path={ROUTES.pengaturan.security} element={<SecuritySessionsPage />} />

            <Route path={ROUTES.persetujuanWorkflow} element={<ApprovalWorkflowsPage />} />
            <Route path={ROUTES.persetujuanDetail} element={<ApprovalRequestDetailPage />} />
            <Route path={ROUTES.persetujuan} element={<ApprovalRequestsPage />} />

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
