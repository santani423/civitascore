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

const PLACEHOLDER_ROUTES: Array<{ path: string; title: string }> = [
  { path: ROUTES.akademik.kurikulum, title: 'Kurikulum' },
  { path: ROUTES.akademik.mataKuliah, title: 'Mata Kuliah' },
  { path: ROUTES.akademik.krs, title: 'KRS' },
  { path: ROUTES.akademik.absensi, title: 'Absensi' },
  { path: ROUTES.akademik.penilaian, title: 'Penilaian' },
  { path: ROUTES.keuangan.beasiswa, title: 'Beasiswa' },
  { path: ROUTES.skripsi, title: 'Skripsi' },
  { path: ROUTES.magangMbkm, title: 'Magang dan MBKM' },
  { path: ROUTES.perpustakaan, title: 'Perpustakaan' },
  { path: ROUTES.alumni, title: 'Alumni' },
  { path: ROUTES.pengumuman, title: 'Pengumuman' },
  { path: ROUTES.laporan, title: 'Laporan' },
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
            <Route path={ROUTES.dashboard} element={<DashboardRoute />} />

            <Route path={ROUTES.platform.universities} element={<UniversitiesPage />} />
            <Route path={ROUTES.platform.security} element={<SupportSessionsPage />} />

            <Route path={ROUTES.mahasiswa} element={<StudentsPage />} />
            <Route path={ROUTES.mahasiswaDetail} element={<StudentDetailPage />} />
            <Route path={ROUTES.dosen} element={<LecturersPage />} />
            <Route path={ROUTES.dosenDetail} element={<LecturerDetailPage />} />
            <Route path={ROUTES.pegawai} element={<EmployeesPage />} />
            <Route path={ROUTES.pegawaiDetail} element={<EmployeeDetailPage />} />
            <Route path={ROUTES.akademik.programStudi} element={<StudyProgramsPage />} />
            <Route path={ROUTES.akademik.programStudiDetail} element={<StudyProgramDetailPage />} />
            <Route path={ROUTES.akademik.kelasJadwal} element={<ClassSectionsPage />} />
            <Route path={ROUTES.akademik.kelasJadwalDetail} element={<ClassSectionDetailPage />} />
            <Route path={ROUTES.keuangan.tagihan} element={<InvoicesPage />} />
            <Route path={ROUTES.keuangan.tagihanDetail} element={<InvoiceDetailPage />} />
            <Route path={ROUTES.keuangan.pembayaran} element={<PaymentsPage />} />

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
