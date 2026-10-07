import { Route, Routes } from 'react-router-dom'
import { OneLayout } from './components/layout/OneLayout'
import { DashboardPage } from './pages/Dashboard'
import { AcademicPage, CourseDetailPage, CoursesPage } from './pages/Academic'
import { AssignmentsPage, AttendancePage, ExamsPage, GradesPage, SchedulePage } from './pages/Learning'
import { FinancePage } from './pages/Finance'
import { CampusLifePage, EventsPage, FacilitiesPage, OrganizationsPage } from './pages/Campus'
import { CareerPage, LibraryPage } from './pages/LibraryCareer'
import { AdmissionPage, FacultiesPage, FacultyDetailPage, InternationalPage, ProgramDetailPage, ResearchPage, ScholarshipsPage } from './pages/University'
import { NewsDetailPage, NewsPage } from './pages/News'
import { NotificationsPage, ProfilePage, SettingsPage } from './pages/Account'
import { HelpPage, NotFoundPage, ServicesPage } from './pages/Support'

/**
 * Civitas One — UI-only "digital university super app".
 * Everything under /one runs on mock data and frontend state; it never
 * calls the Laravel API, so it can't affect the production admin app.
 */
export default function OneApp() {
  return (
    <Routes>
      <Route element={<OneLayout />}>
        <Route index element={<DashboardPage />} />
        <Route path="academic" element={<AcademicPage />} />
        <Route path="courses" element={<CoursesPage />} />
        <Route path="courses/:id" element={<CourseDetailPage />} />
        <Route path="schedule" element={<SchedulePage />} />
        <Route path="assignments" element={<AssignmentsPage />} />
        <Route path="exams" element={<ExamsPage />} />
        <Route path="grades" element={<GradesPage />} />
        <Route path="attendance" element={<AttendancePage />} />
        <Route path="finance" element={<FinancePage />} />
        <Route path="campus" element={<CampusLifePage />} />
        <Route path="campus/organizations" element={<OrganizationsPage />} />
        <Route path="campus/events" element={<EventsPage />} />
        <Route path="campus/facilities" element={<FacilitiesPage />} />
        <Route path="library" element={<LibraryPage />} />
        <Route path="career" element={<CareerPage />} />
        <Route path="admission" element={<AdmissionPage />} />
        <Route path="faculties" element={<FacultiesPage />} />
        <Route path="faculties/:id" element={<FacultyDetailPage />} />
        <Route path="programs/:id" element={<ProgramDetailPage />} />
        <Route path="news" element={<NewsPage />} />
        <Route path="news/:id" element={<NewsDetailPage />} />
        <Route path="research" element={<ResearchPage />} />
        <Route path="international" element={<InternationalPage />} />
        <Route path="scholarships" element={<ScholarshipsPage />} />
        <Route path="notifications" element={<NotificationsPage />} />
        <Route path="profile" element={<ProfilePage />} />
        <Route path="settings" element={<SettingsPage />} />
        <Route path="services" element={<ServicesPage />} />
        <Route path="help" element={<HelpPage />} />
        <Route path="*" element={<NotFoundPage />} />
      </Route>
    </Routes>
  )
}
