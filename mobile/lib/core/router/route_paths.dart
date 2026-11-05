/// Every route of the app. Feature modules register their `GoRoute`s in
/// `features/<feature>/<feature>_routes.dart` using these paths; screens
/// navigate with `context.push(RoutePaths.x)` / `context.go(...)`.
///
/// Student routes live under `/s/…`, lecturer & advisor routes under `/l/…`.
/// Detail routes are pushed on the root navigator (above the bottom bar).
abstract final class RoutePaths {
  // Public / session -------------------------------------------------------
  static const splash = '/splash';
  static const login = '/login';
  static const forgotPassword = '/forgot-password';
  static const resetPassword = '/reset-password';
  static const changePassword = '/change-password';
  static const lock = '/lock';
  static const noAccess = '/no-access';

  // Shared ----------------------------------------------------------------
  static const notifications = '/notifications';
  static const notificationPreferences = '/notifications/preferences';
  static const settings = '/settings';
  static const securitySessions = '/settings/security';
  static const pendingFeaturesStudent = '/pending/student';
  static const pendingFeaturesLecturer = '/pending/lecturer';
  static const pendingFeaturesAdvisor = '/pending/advisor';
  static String pendingFeature(String key) => '/pending/feature/$key';

  // Student shell tabs ------------------------------------------------------
  static const studentHome = '/s/home';
  // Menu Utama: pushed di atas bottom bar; `?kategori=` memilih chip awal.
  static const studentMenu = '/s/menu';
  static const studentAcademic = '/s/academic';
  static const studentLearning = '/s/learning';
  static const studentFinance = '/s/finance';
  static const studentAccount = '/s/account';

  // Student: profile & academic records (feature academic_records)
  static const studentProfile = '/s/profile';
  static const studentAcademicHistory = '/s/academic-history';
  static const studentGrades = '/s/grades';
  static const studentKhs = '/s/khs';
  static const studentTranscript = '/s/transcript';
  static const studentDocuments = '/s/documents';

  // Student: schedule, calendar, attendance (feature schedule_attendance)
  static const studentSchedule = '/s/schedule';
  static const studentCalendar = '/s/calendar';
  static const studentAttendance = '/s/attendance';
  static const studentAttendanceCheckIn = '/s/attendance/check-in';
  static String studentAttendanceDetail(String krsItemId) =>
      '/s/attendance/$krsItemId';

  // Student: KRS (feature krs)
  static const studentKrs = '/s/krs';
  static const studentKrsOfferings = '/s/krs/offerings';
  static const studentKrsHistory = '/s/krs/history';

  // Student: learning (feature learning)
  static const studentCourses = '/s/courses';
  static String studentCourse(String classSectionId) =>
      '/s/courses/$classSectionId';
  static const studentMaterials = '/s/materials';
  static const studentAssignments = '/s/assignments';
  static String studentAssignment(String id) => '/s/assignments/$id';

  // Student: exams (feature exam_student)
  static const studentExams = '/s/exams';
  static String studentExam(String examId) => '/s/exams/$examId';
  static String studentExamAttempt(String attemptId) =>
      '/s/exam-attempts/$attemptId';
  static String studentExamResult(String attemptId) =>
      '/s/exam-attempts/$attemptId/result';

  // Student: finance (feature finance; tab root = studentFinance)
  static const studentInvoices = '/s/finance/invoices';
  static String studentInvoice(String id) => '/s/finance/invoices/$id';
  static String studentInvoicePay(String id) => '/s/finance/invoices/$id/pay';
  static const studentTransactions = '/s/finance/transactions';
  static const studentDeposit = '/s/finance/deposit';
  static const studentStatement = '/s/finance/statement';
  static const studentUkt = '/s/finance/ukt';
  static const studentAwards = '/s/finance/awards';
  static const studentClearance = '/s/finance/clearance';

  // Student: administrative requests & letters (feature student_services)
  static const studentRequests = '/s/requests';
  static const studentRequestNew = '/s/requests/new';
  static String studentRequest(String id) => '/s/requests/$id';

  // Student: announcements (feature announcements)
  static const studentAnnouncements = '/s/announcements';
  static String studentAnnouncement(String id) => '/s/announcements/$id';

  // Lecturer shell tabs -----------------------------------------------------
  static const lecturerHome = '/l/home';
  static const lecturerTeaching = '/l/teaching';
  static const lecturerSchedule = '/l/schedule';
  static const lecturerAdvising = '/l/advising';
  static const lecturerAccount = '/l/account';

  // Lecturer: classes & LMS (feature teaching)
  static String lecturerClass(String classSectionId) =>
      '/l/classes/$classSectionId';
  static String lecturerClassStudents(String classSectionId) =>
      '/l/classes/$classSectionId/students';
  static String lecturerClassMaterials(String classSectionId) =>
      '/l/classes/$classSectionId/materials';
  static String lecturerClassAssignments(String classSectionId) =>
      '/l/classes/$classSectionId/assignments';
  static String lecturerAssignmentSubmissions(String assignmentId) =>
      '/l/assignments/$assignmentId/submissions';
  static const lecturerCalendar = '/l/calendar';
  static String lecturerStudent(String studentId) => '/l/students/$studentId';

  // Lecturer: attendance & grades (feature lecturer_assessment)
  static String lecturerClassAttendance(String classSectionId) =>
      '/l/classes/$classSectionId/attendance';
  static String lecturerClassGrades(String classSectionId) =>
      '/l/classes/$classSectionId/grades';

  // Lecturer: exams & question bank (feature exam)
  static const lecturerExams = '/l/exams';
  static const lecturerExamNew = '/l/exams/new';
  static String lecturerExam(String examId) => '/l/exams/$examId';
  static String lecturerExamEdit(String examId) => '/l/exams/$examId/edit';
  static String lecturerExamQuestions(String examId) =>
      '/l/exams/$examId/questions';
  static String lecturerExamPreview(String examId) =>
      '/l/exams/$examId/preview';
  static String lecturerExamAttemptReport(String examId, String attemptId) =>
      '/l/exams/$examId/attempts/$attemptId';
  static const lecturerQuestionBank = '/l/question-bank';

  // Lecturer: PMB exam duties — proctor/examiner (feature pmb_duty)
  static const lecturerPmbSessions = '/l/pmb/sessions';
  static String lecturerPmbSession(String sessionId) =>
      '/l/pmb/sessions/$sessionId';
  static String lecturerPmbSessionScores(String sessionId) =>
      '/l/pmb/sessions/$sessionId/scores';

  // Advisor: KRS approvals (feature advising)
  static String lecturerKrsSubmission(String id) => '/l/advising/krs/$id';

  // Lecturer: profile & HR self-service (feature hr)
  static const lecturerProfile = '/l/profile';
  static const hrProfile = '/l/hr/profile';
  static const hrLeaveRequests = '/l/hr/leave';
  static const hrLeaveRequestNew = '/l/hr/leave/new';
  static const hrRequests = '/l/hr/requests';
  static const hrRequestNew = '/l/hr/requests/new';
}
