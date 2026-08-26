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
import { CurriculumsPage } from '@/pages/academic/CurriculumsPage'
import { CurriculumDetailPage } from '@/pages/academic/CurriculumDetailPage'
import { CoursesPage } from '@/pages/academic/CoursesPage'
import { CourseDetailPage } from '@/pages/academic/CourseDetailPage'
import { KrsPage } from '@/pages/academic/KrsPage'
import { GradesPage } from '@/pages/academic/GradesPage'
import { AttendancePage } from '@/pages/academic/AttendancePage'
import { ExamsPage } from '@/pages/academic/ExamsPage'
import { ExamDetailPage } from '@/pages/academic/ExamDetailPage'
import { QuestionBankPage } from '@/pages/academic/QuestionBankPage'
import { InvoicesPage } from '@/pages/finance/InvoicesPage'
import { InvoiceDetailPage } from '@/pages/finance/InvoiceDetailPage'
import { PaymentsPage } from '@/pages/finance/PaymentsPage'
import { ScholarshipsPage } from '@/pages/scholarship/ScholarshipsPage'
import { ScholarshipDetailPage } from '@/pages/scholarship/ScholarshipDetailPage'
import { ThesesPage } from '@/pages/thesis/ThesesPage'
import { ThesisDetailPage } from '@/pages/thesis/ThesisDetailPage'
import { InternshipsPage } from '@/pages/internship/InternshipsPage'
import { InternshipDetailPage } from '@/pages/internship/InternshipDetailPage'
import { BooksPage } from '@/pages/library/BooksPage'
import { BookDetailPage } from '@/pages/library/BookDetailPage'
import { AlumniPage } from '@/pages/alumni/AlumniPage'
import { AlumniDetailPage } from '@/pages/alumni/AlumniDetailPage'
import { AnnouncementsPage } from '@/pages/announcement/AnnouncementsPage'
import { AnnouncementDetailPage } from '@/pages/announcement/AnnouncementDetailPage'
import { ReportsPage } from '@/pages/report/ReportsPage'
import { PortalDashboardPage } from '@/pages/portal/PortalDashboardPage'
import { PortalProfilePage } from '@/pages/portal/PortalProfilePage'
import { PortalKrsPage } from '@/pages/portal/PortalKrsPage'
import { PortalSchedulePage } from '@/pages/portal/PortalSchedulePage'
import { PortalKhsPage } from '@/pages/portal/PortalKhsPage'
import { PortalTranscriptPage } from '@/pages/portal/PortalTranscriptPage'
import { PortalGradesPage } from '@/pages/portal/PortalGradesPage'
import { PortalAttendancePage } from '@/pages/portal/PortalAttendancePage'
import { PortalAssignmentsPage } from '@/pages/portal/PortalAssignmentsPage'
import { PortalQuizzesPage } from '@/pages/portal/PortalQuizzesPage'
import { PortalLeaveRequestPage } from '@/pages/portal/PortalLeaveRequestPage'
import { PortalLetterRequestPage } from '@/pages/portal/PortalLetterRequestPage'
import { PortalScholarshipPage } from '@/pages/portal/PortalScholarshipPage'
import { PortalInvoicesPage } from '@/pages/portal/PortalInvoicesPage'
import { PortalAcademicAdvisingPage } from '@/pages/portal/PortalAcademicAdvisingPage'
import { PortalThesisAdvisingPage } from '@/pages/portal/PortalThesisAdvisingPage'
import { PortalAnnouncementsPage } from '@/pages/portal/PortalAnnouncementsPage'
import { PortalLecturerEvaluationPage } from '@/pages/portal/PortalLecturerEvaluationPage'
import { PortalGraduationPage } from '@/pages/portal/PortalGraduationPage'
import { useIsSuperAdmin } from '@/hooks/useIsSuperAdmin'
import { useIsStudent } from '@/hooks/useIsStudent'
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
  { path: ROUTES.akademik.kurikulum, element: <CurriculumsPage /> },
  { path: ROUTES.akademik.kurikulumDetail, element: <CurriculumDetailPage /> },
  { path: ROUTES.akademik.mataKuliah, element: <CoursesPage /> },
  { path: ROUTES.akademik.mataKuliahDetail, element: <CourseDetailPage /> },
  { path: ROUTES.akademik.krs, element: <KrsPage /> },
  { path: ROUTES.akademik.absensi, element: <AttendancePage /> },
  { path: ROUTES.akademik.penilaian, element: <GradesPage /> },
  { path: ROUTES.akademik.ujian, element: <ExamsPage /> },
  { path: ROUTES.akademik.ujianDetail, element: <ExamDetailPage /> },
  { path: ROUTES.akademik.bankSoal, element: <QuestionBankPage /> },
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

  { path: ROUTES.keuangan.beasiswa, element: <ScholarshipsPage /> },
  { path: ROUTES.keuangan.beasiswaDetail, element: <ScholarshipDetailPage /> },
  { path: ROUTES.skripsi, element: <ThesesPage /> },
  { path: ROUTES.skripsiDetail, element: <ThesisDetailPage /> },
  { path: ROUTES.magangMbkm, element: <InternshipsPage /> },
  { path: ROUTES.magangMbkmDetail, element: <InternshipDetailPage /> },
  { path: ROUTES.perpustakaan, element: <BooksPage /> },
  { path: ROUTES.perpustakaanDetail, element: <BookDetailPage /> },
  { path: ROUTES.alumni, element: <AlumniPage /> },
  { path: ROUTES.alumniDetail, element: <AlumniDetailPage /> },
  { path: ROUTES.pengumuman, element: <AnnouncementsPage /> },
  { path: ROUTES.pengumumanDetail, element: <AnnouncementDetailPage /> },
  { path: ROUTES.laporan, element: <ReportsPage /> },

  { path: ROUTES.portal.profil, element: <PortalProfilePage /> },
  { path: ROUTES.portal.krs, element: <PortalKrsPage /> },
  { path: ROUTES.portal.jadwal, element: <PortalSchedulePage /> },
  { path: ROUTES.portal.khs, element: <PortalKhsPage /> },
  { path: ROUTES.portal.transkrip, element: <PortalTranscriptPage /> },
  { path: ROUTES.portal.nilai, element: <PortalGradesPage /> },
  { path: ROUTES.portal.absensi, element: <PortalAttendancePage /> },
  { path: ROUTES.portal.tugas, element: <PortalAssignmentsPage /> },
  { path: ROUTES.portal.kuis, element: <PortalQuizzesPage /> },
  { path: ROUTES.portal.cuti, element: <PortalLeaveRequestPage /> },
  { path: ROUTES.portal.surat, element: <PortalLetterRequestPage /> },
  { path: ROUTES.portal.beasiswa, element: <PortalScholarshipPage /> },
  { path: ROUTES.portal.tagihan, element: <PortalInvoicesPage /> },
  { path: ROUTES.portal.bimbinganAkademik, element: <PortalAcademicAdvisingPage /> },
  { path: ROUTES.portal.bimbinganSkripsi, element: <PortalThesisAdvisingPage /> },
  { path: ROUTES.portal.pengumuman, element: <PortalAnnouncementsPage /> },
  { path: ROUTES.portal.evaluasiDosen, element: <PortalLecturerEvaluationPage /> },
  { path: ROUTES.portal.wisuda, element: <PortalGraduationPage /> },
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
  const isStudent = useIsStudent()
  const tenantSelected = useTenantStore((state) => state.selectedUniversity !== null)

  if (isSuperAdmin && !tenantSelected) return <PlatformDashboardPage />
  if (isStudent) return <PortalDashboardPage />
  return <DashboardPage />
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
