import type { ReactNode } from 'react'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { useThemeSync } from '@/hooks/useThemeSync'
import { ProtectedRoute } from '@/routes/ProtectedRoute'
import { PublicOnlyRoute } from '@/routes/PublicOnlyRoute'
import { RequirePermission } from '@/routes/RequirePermission'
import { getRoutePermission } from '@/routes/routePermissions'
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
import { StudentsPage } from '@/pages/academic/StudentsPage'
import { StudentDetailPage } from '@/pages/academic/StudentDetailPage'
import { LecturersPage } from '@/pages/academic/LecturersPage'
import { LecturerDetailPage } from '@/pages/academic/LecturerDetailPage'
import { EmployeesPage } from '@/pages/academic/EmployeesPage'
import { EmployeeDetailPage } from '@/pages/academic/EmployeeDetailPage'
import { StudyProgramsPage } from '@/pages/academic/StudyProgramsPage'
import { StudyProgramDetailPage } from '@/pages/academic/StudyProgramDetailPage'
import { ClassSectionsPage } from '@/pages/academic/ClassSectionsPage'
import { ClassSectionDetailPage } from '@/pages/academic/ClassSectionDetailPage'
import { InvoicesPage } from '@/pages/finance/InvoicesPage'
import { InvoiceDetailPage } from '@/pages/finance/InvoiceDetailPage'
import { PaymentsPage } from '@/pages/finance/PaymentsPage'
import { useIsSuperAdmin } from '@/hooks/useIsSuperAdmin'
import { useTenantStore } from '@/stores/tenantStore'
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

/**
 * Satu tabel tunggal path -> halaman, dipakai sebagai daftar Route DAN
 * sebagai artefak audit siapa-boleh-akses-apa (lihat routes/routePermissions.ts
 * untuk permission tiap path — diturunkan dari constants/nav.ts, bukan
 * diduplikasi di sini). `/persetujuan(/:id)` dan rute tanpa permission
 * (Dashboard, Keamanan/self-service, placeholder tanpa data nyata) memang
 * sengaja tidak muncul di routePermissions — terbuka untuk semua yang login.
 */
const APP_ROUTES: Array<{ path: string; element: ReactNode }> = [
  { path: ROUTES.dashboard, element: <DashboardRoute /> },

  { path: ROUTES.platform.universities, element: <UniversitiesPage /> },
  { path: ROUTES.platform.security, element: <SupportSessionsPage /> },

  { path: ROUTES.mahasiswa, element: <StudentsPage /> },
  { path: ROUTES.mahasiswaDetail, element: <StudentDetailPage /> },
  { path: ROUTES.dosen, element: <LecturersPage /> },
  { path: ROUTES.dosenDetail, element: <LecturerDetailPage /> },
  { path: ROUTES.pegawai, element: <EmployeesPage /> },
  { path: ROUTES.pegawaiDetail, element: <EmployeeDetailPage /> },
  { path: ROUTES.akademik.programStudi, element: <StudyProgramsPage /> },
  { path: ROUTES.akademik.programStudiDetail, element: <StudyProgramDetailPage /> },
  { path: ROUTES.akademik.kelasJadwal, element: <ClassSectionsPage /> },
  { path: ROUTES.akademik.kelasJadwalDetail, element: <ClassSectionDetailPage /> },
  { path: ROUTES.keuangan.tagihan, element: <InvoicesPage /> },
  { path: ROUTES.keuangan.tagihanDetail, element: <InvoiceDetailPage /> },
  { path: ROUTES.keuangan.pembayaran, element: <PaymentsPage /> },

  { path: ROUTES.pengaturan.roles, element: <RolesPage /> },
  { path: ROUTES.pengaturan.permissions, element: <PermissionsPage /> },
  { path: ROUTES.pengaturan.userRoles, element: <UserRolesPage /> },
  { path: ROUTES.pengaturan.systemSettings, element: <SystemSettingsPage /> },
  { path: ROUTES.pengaturan.featureFlags, element: <FeatureFlagsPage /> },
  { path: ROUTES.pengaturan.notifications, element: <NotificationsPage /> },
  { path: ROUTES.pengaturan.auditLog, element: <AuditLogPage /> },
  { path: ROUTES.pengaturan.security, element: <SecuritySessionsPage /> },

  { path: ROUTES.persetujuanWorkflow, element: <ApprovalWorkflowsPage /> },
  { path: ROUTES.persetujuanDetail, element: <ApprovalRequestDetailPage /> },
  { path: ROUTES.persetujuan, element: <ApprovalRequestsPage /> },

  { path: ROUTES.akademik.kurikulum, element: <PlaceholderPage title="Kurikulum" /> },
  { path: ROUTES.akademik.mataKuliah, element: <PlaceholderPage title="Mata Kuliah" /> },
  { path: ROUTES.akademik.krs, element: <PlaceholderPage title="KRS" /> },
  { path: ROUTES.akademik.absensi, element: <PlaceholderPage title="Absensi" /> },
  { path: ROUTES.akademik.penilaian, element: <PlaceholderPage title="Penilaian" /> },
  { path: ROUTES.keuangan.beasiswa, element: <PlaceholderPage title="Beasiswa" /> },
  { path: ROUTES.skripsi, element: <PlaceholderPage title="Skripsi" /> },
  { path: ROUTES.magangMbkm, element: <PlaceholderPage title="Magang dan MBKM" /> },
  { path: ROUTES.perpustakaan, element: <PlaceholderPage title="Perpustakaan" /> },
  { path: ROUTES.alumni, element: <PlaceholderPage title="Alumni" /> },
  { path: ROUTES.pengumuman, element: <PlaceholderPage title="Pengumuman" /> },
  { path: ROUTES.laporan, element: <PlaceholderPage title="Laporan" /> },
]

/**
 * Super Admin tanpa universitas dipilih lihat Dashboard Platform (statistik
 * lintas-universitas); begitu memilih universitas lewat Tenant Switcher
 * (sama seperti Sidebar menyisipkan menu bisnis tenant), atau untuk
 * pengguna tenant biasa, tampilkan Dashboard operasional dengan data
 * universitas yang aktif — lihat docs/RANCANGAN-SUPER-ADMIN.md §1-2.
 */
function DashboardRoute() {
  const isSuperAdmin = useIsSuperAdmin()
  const tenantSelected = useTenantStore((state) => state.selectedUniversity !== null)

  return isSuperAdmin && !tenantSelected ? <PlatformDashboardPage /> : <DashboardPage />
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
            {APP_ROUTES.map((route) => (
              <Route
                key={route.path}
                path={route.path}
                element={<RequirePermission permission={getRoutePermission(route.path)}>{route.element}</RequirePermission>}
              />
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
