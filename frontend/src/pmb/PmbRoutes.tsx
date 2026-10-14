import { lazy, Suspense, type ReactNode } from 'react'
import { Navigate, Route, useLocation } from 'react-router-dom'
import { useApplicantStore } from '@/pmb/stores/applicantStore'
import { PMB } from '@/pmb/routes'

const named = <T extends Record<string, unknown>>(loader: () => Promise<T>, name: keyof T) =>
  lazy(() => loader().then((m) => ({ default: m[name] as React.ComponentType })))

const pub = () => import('@/pmb/pages/public/CatalogPages')
const content = () => import('@/pmb/pages/public/ContentPages')
const check = () => import('@/pmb/pages/public/CheckPages')
const auth = () => import('@/pmb/pages/auth/AuthPages')
const portal = () => import('@/pmb/pages/portal/PortalPages')
const stage = () => import('@/pmb/pages/portal/StagePages')

const PublicLayout = named(() => import('@/pmb/layouts/PublicLayout'), 'PublicLayout')
const PortalLayout = named(() => import('@/pmb/layouts/PortalLayout'), 'PortalLayout')
const HomePage = named(() => import('@/pmb/pages/public/HomePage'), 'HomePage')
const ProgramsPage = named(pub, 'ProgramsPage')
const ProgramDetailPage = named(pub, 'ProgramDetailPage')
const PathsPage = named(pub, 'PathsPage')
const PathDetailPage = named(pub, 'PathDetailPage')
const FeesPage = named(pub, 'FeesPage')
const SchedulePage = named(pub, 'SchedulePage')
const LegalPage = named(content, 'LegalPage')
const ReRegistrationInfoPage = named(content, 'ReRegistrationInfoPage')
const ContactPage = named(content, 'ContactPage')
const StatusCheckPage = named(check, 'StatusCheckPage')
const LoaVerifyPage = named(check, 'LoaVerifyPage')
const RegisterPage = named(auth, 'RegisterPage')
const LoginPage = named(auth, 'LoginPage')
const VerifyPage = named(auth, 'VerifyPage')
const ForgotPasswordPage = named(auth, 'ForgotPasswordPage')
const ResetPasswordPage = named(auth, 'ResetPasswordPage')
const RecoveryPage = named(auth, 'RecoveryPage')
const PortalHomePage = named(portal, 'PortalHomePage')
const NewApplicationPage = named(portal, 'NewApplicationPage')
const NotificationsPage = named(portal, 'NotificationsPage')
const MessagesPage = named(portal, 'MessagesPage')
const AccountPage = named(portal, 'AccountPage')
const ProfilePage = named(() => import('@/pmb/pages/portal/ProfilePage'), 'ProfilePage')
const ApplicationPage = named(() => import('@/pmb/pages/portal/ApplicationPage'), 'ApplicationPage')
const ExamsPage = named(stage, 'ExamsPage')
const CbtPage = named(stage, 'CbtPage')
const ResultPage = named(stage, 'ResultPage')
const ReRegistrationPage = named(stage, 'ReRegistrationPage')

// Komponen dengan prop tetap (ContentListPage/ContentDetailPage) dibungkus.
const ContentList = lazy(() => content().then((m) => ({ default: m.ContentListPage })))
const ContentDetail = lazy(() => content().then((m) => ({ default: m.ContentDetailPage })))

function S({ children }: { children: ReactNode }) {
  return <Suspense fallback={<p className="p-6 text-sm text-ink-secondary">Memuat...</p>}>{children}</Suspense>
}

/** CBT layar penuh di luar PortalLayout, tetap wajib sesi pendaftar. */
function ApplicantOnly({ children }: { children: ReactNode }) {
  const token = useApplicantStore((s) => s.token)
  const location = useLocation()

  return token ? <>{children}</> : <Navigate to={`${PMB.login}?next=${encodeURIComponent(location.pathname)}`} replace />
}

/** Rute website publik, autentikasi & portal pendaftar PMB (di luar sesi staf). */
export function pmbPublicRoutes() {
  return (
    <>
      <Route element={<S><PublicLayout /></S>}>
        <Route path={PMB.home} element={<S><HomePage /></S>} />
        <Route path={PMB.programs} element={<S><ProgramsPage /></S>} />
        <Route path={PMB.program} element={<S><ProgramDetailPage /></S>} />
        <Route path={PMB.paths} element={<S><PathsPage /></S>} />
        <Route path={PMB.path} element={<S><PathDetailPage /></S>} />
        <Route path={PMB.fees} element={<S><FeesPage /></S>} />
        <Route path={PMB.schedule} element={<S><SchedulePage /></S>} />
        <Route path={PMB.guide} element={<S><ContentList type="guide" /></S>} />
        <Route path={PMB.faq} element={<S><ContentList type="faq" /></S>} />
        <Route path={PMB.news} element={<S><ContentList type="news" /></S>} />
        <Route path={PMB.newsDetail} element={<S><ContentDetail type="news" /></S>} />
        <Route path={PMB.page} element={<S><ContentDetail /></S>} />
        <Route path={PMB.legal} element={<S><LegalPage /></S>} />
        <Route path={PMB.statusCheck} element={<S><StatusCheckPage /></S>} />
        <Route path={PMB.reRegistrationInfo} element={<S><ReRegistrationInfoPage /></S>} />
        <Route path={PMB.contact} element={<S><ContactPage /></S>} />
        <Route path={PMB.loa} element={<S><LoaVerifyPage /></S>} />
        <Route path={PMB.register} element={<S><RegisterPage /></S>} />
        <Route path={PMB.login} element={<S><LoginPage /></S>} />
        <Route path={PMB.verify} element={<S><VerifyPage /></S>} />
        <Route path={PMB.forgot} element={<S><ForgotPasswordPage /></S>} />
        <Route path={PMB.resetPassword} element={<S><ResetPasswordPage /></S>} />
        <Route path={PMB.recovery} element={<S><RecoveryPage /></S>} />
      </Route>

      <Route path={PMB.portal.cbt} element={<ApplicantOnly><S><CbtPage /></S></ApplicantOnly>} />

      <Route element={<S><PortalLayout /></S>}>
        <Route path={PMB.portal.home} element={<S><PortalHomePage /></S>} />
        <Route path={PMB.portal.profile} element={<S><ProfilePage /></S>} />
        <Route path={PMB.portal.newApplication} element={<S><NewApplicationPage /></S>} />
        <Route path={PMB.portal.application} element={<S><ApplicationPage /></S>} />
        <Route path={PMB.portal.exams} element={<S><ExamsPage /></S>} />
        <Route path={PMB.portal.result} element={<S><ResultPage /></S>} />
        <Route path={PMB.portal.reRegistration} element={<S><ReRegistrationPage /></S>} />
        <Route path={PMB.portal.messages} element={<S><MessagesPage /></S>} />
        <Route path={PMB.portal.notifications} element={<S><NotificationsPage /></S>} />
        <Route path={PMB.portal.account} element={<S><AccountPage /></S>} />
      </Route>
    </>
  )
}
