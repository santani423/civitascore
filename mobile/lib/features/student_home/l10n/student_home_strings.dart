import 'package:flutter/widgets.dart';
import '../../../core/l10n/app_language.dart';

class StudentHomeStrings {
  const StudentHomeStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);
  String get title => _p('Beranda', 'Home', '首页');
  String get greetingMorning => _p('Selamat pagi', 'Good morning', '早上好');
  String get greetingNoon => _p('Selamat siang', 'Good afternoon', '中午好');
  String get greetingAfternoon => _p('Selamat sore', 'Good evening', '下午好');
  String get greetingNight => _p('Selamat malam', 'Good night', '晚上好');
  String get nim => _p('NIM', 'Student ID', '学号');
  String get semester => _p('Semester', 'Semester', '学期');
  String get statusLabel => _p('Status', 'Status', '状态');
  String get academicSummary =>
      _p('Ringkasan akademik', 'Academic summary', '学业概览');
  String get activeSemester => _p('Semester aktif', 'Active semester', '当前学期');
  String get academicProgress =>
      _p('Progres akademik', 'Academic progress', '学业进度');
  String get ipk => _p('IPK', 'GPA', '累计GPA');
  String get ips => _p('IPS', 'Term GPA', '学期GPA');
  String get credits => _p('SKS', 'Credits', '学分');
  String get creditsPassed => _p('SKS lulus', 'Credits passed', '已通过学分');
  String get creditsThisTerm =>
      _p('SKS semester ini', 'Credits this term', '本学期学分');
  String get advisor => _p('Dosen wali', 'Advisor', '导师');
  String get krsStatus => _p('Status KRS', 'KRS status', '选课状态');
  String get alerts => _p('Perlu perhatian', 'Needs attention', '需要关注');
  String get todayClasses => _p('Kelas hari ini', "Today's classes", '今日课程');
  String get nextClass => _p('Kelas berikutnya', 'Next class', '下一节课');
  String get noClassToday =>
      _p('Tidak ada kelas hari ini.', 'No classes today.', '今天没有课程。');
  String get statusUpcoming => _p('Akan datang', 'Upcoming', '即将开始');
  String get statusOngoing => _p('Berlangsung', 'Ongoing', '进行中');
  String get statusFinished => _p('Selesai', 'Finished', '已结束');
  String get pendingApproval =>
      _p('Menunggu persetujuan', 'Pending approval', '待审批');
  String get attendance => _p('Kehadiran', 'Attendance', '出勤');
  String get attendanceOverall =>
      _p('Kehadiran keseluruhan', 'Overall attendance', '总体出勤');
  String get attendanceNoData =>
      _p('Belum ada data kehadiran.', 'No attendance data yet.', '暂无出勤数据。');
  String get attendanceAllGood => _p(
    'Semua mata kuliah di atas batas minimum.',
    'All courses are above the minimum.',
    '所有课程均达到最低要求。',
  );
  String get upcomingExams =>
      _p('Ujian mendatang', 'Upcoming exams', '即将到来的考试');
  String get noExams =>
      _p('Tidak ada ujian mendatang.', 'No upcoming exams.', '没有即将到来的考试。');
  String get examOpen => _p('Dibuka', 'Open', '进行中');
  String get examInProgress => _p('Sedang dikerjakan', 'In progress', '作答中');
  String get examSoon => _p('Segera', 'Soon', '即将开始');
  String get assignmentsDue =>
      _p('Tugas segera berakhir', 'Assignments due soon', '即将截止的作业');
  String get noAssignments => _p(
    'Tidak ada tugas yang mendekati tenggat.',
    'No assignments due soon.',
    '没有即将截止的作业。',
  );
  String get due => _p('Tenggat', 'Due', '截止');
  String get recentGrades => _p('Nilai terbaru', 'Recent grades', '最新成绩');
  String get announcements => _p('Pengumuman', 'Announcements', '公告');
  String get announcementsLatest =>
      _p('Pengumuman terbaru', 'Latest announcements', '最新公告');
  String get noAnnouncements =>
      _p('Belum ada pengumuman.', 'No announcements yet.', '暂无公告。');
  String get seeAll => _p('Lihat semua', 'See all', '查看全部');
  String get pinned => _p('Disematkan', 'Pinned', '置顶');
  String get finance => _p('Keuangan', 'Finance', '财务');
  String get outstanding => _p('Tagihan belum dibayar', 'Outstanding', '未付账单');
  String get arrears => _p('Tunggakan', 'Arrears', '欠费');
  String get nextDue => _p('Jatuh tempo terdekat', 'Next due', '最近到期');
  String get openInvoices => _p('Tagihan terbuka', 'Open invoices', '未结账单');
  String get financeAllPaid => _p(
    'Tidak ada tagihan yang belum dibayar.',
    'No outstanding invoices.',
    '没有未付账单。',
  );
  String get pendingVerification =>
      _p('menunggu verifikasi', 'awaiting verification', '待核实');
  String get blockedServices =>
      _p('Layanan terkunci', 'Blocked services', '受限服务');
  String get deposit => _p('Deposit', 'Deposit', '押金');
  String get financeLoadFailed => _p(
    'Ringkasan keuangan tidak dapat dimuat.',
    'Finance summary could not be loaded.',
    '无法加载财务概览。',
  );
  String get sectionLoadFailed => _p(
    'Bagian ini tidak dapat ditampilkan.',
    'This section could not be shown.',
    '无法显示此部分。',
  );
  String get heroWelcome =>
      _p('Selamat datang di One Atma', 'Welcome to One Atma', '欢迎使用 One Atma');
  String activeUntil(String date) =>
      _p('Aktif hingga $date', 'Active until $date', '有效至 $date');
  String get quickActions => _p('Menu cepat', 'Quick actions', '快捷入口');
  String get qaAcademic => _p('Akademik', 'Academic', '学业');
  String get qaFinance => _p('Keuangan', 'Finance', '财务');
  String get qaStudentAffairs => _p('Kemahasiswaan', 'Student affairs', '学生事务');
  String get qaAdminServices =>
      _p('Layanan Administrasi', 'Admin services', '行政服务');
  String get qaSchedule => _p('Jadwal', 'Schedule', '课表');
  String get qaMore => _p('Lainnya', 'More', '更多');

  String get menuTitle => _p('Menu Utama', 'Main Menu', '主菜单');
  String get menuSearch => _p('Cari layanan', 'Search services', '搜索服务');
  String get menuSearchTooltip => _p('Cari', 'Search', '搜索');
  String get menuCategoryAll => _p('Semua', 'All', '全部');
  String get menuCategoryServices => _p('Layanan', 'Services', '服务');
  String get menuNoResults =>
      _p('Layanan tidak ditemukan.', 'No services found.', '未找到服务。');
  String get menuComingSoon => _p('Segera hadir', 'Coming soon', '即将推出');
  String get menuKrsTitle => _p('KRS Online', 'Online KRS', '在线选课');
  String get menuKrsDesc =>
      _p('Pengisian Kartu Rencana Studi', 'Course registration', '课程选修登记');
  String get menuKhsTitle => _p('KHS', 'Study result card', '学习成绩单');
  String get menuKhsDesc => _p(
    'Kartu hasil studi per semester',
    'Study results per semester',
    '每学期学习成绩',
  );
  String get menuTranscriptTitle => _p('Transkrip', 'Transcript', '成绩单');
  String get menuTranscriptDesc => _p(
    'Transkrip sementara dan IPK',
    'Unofficial transcript and GPA',
    '临时成绩单与GPA',
  );
  String get menuGradesTitle => _p('Nilai', 'Grades', '成绩');
  String get menuGradesDesc =>
      _p('Nilai per mata kuliah', 'Grades per course', '各课程成绩');
  String get menuScheduleTitle => _p('Jadwal Kuliah', 'Class schedule', '课程表');
  String get menuScheduleDesc =>
      _p('Jadwal kelas dan ruangan', 'Class times and rooms', '上课时间与教室');
  String get menuAttendanceTitle => _p('Presensi', 'Attendance', '出勤');
  String get menuAttendanceDesc =>
      _p('Kehadiran per mata kuliah', 'Attendance per course', '各课程出勤情况');
  String get menuCalendarTitle =>
      _p('Kalender Akademik', 'Academic calendar', '学术日历');
  String get menuCalendarDesc =>
      _p('Tanggal penting semester', 'Important semester dates', '学期重要日期');
  String get menuCoursesTitle => _p('Mata Kuliah', 'Courses', '课程');
  String get menuCoursesDesc =>
      _p('Kelas yang sedang diikuti', 'Classes you are enrolled in', '在读课程');
  String get menuMaterialsTitle => _p('Materi', 'Materials', '学习资料');
  String get menuMaterialsDesc =>
      _p('Materi perkuliahan', 'Course materials', '课程资料');
  String get menuAssignmentsTitle => _p('Tugas', 'Assignments', '作业');
  String get menuAssignmentsDesc =>
      _p('Tugas dan tenggatnya', 'Assignments and deadlines', '作业与截止日期');
  String get menuExamsTitle => _p('Ujian', 'Exams', '考试');
  String get menuExamsDesc =>
      _p('Jadwal dan hasil ujian', 'Exam schedule and results', '考试安排与结果');
  String get menuDocumentsTitle =>
      _p('Dokumen Akademik', 'Academic documents', '学术文件');
  String get menuDocumentsDesc =>
      _p('Unduh KHS dan transkrip', 'Download KHS and transcript', '下载成绩单与成绩表');
  String get menuFinanceTitle => _p('Pembayaran', 'Payments', '缴费');
  String get menuFinanceDesc => _p(
    'Tagihan dan riwayat pembayaran',
    'Invoices and payment history',
    '账单与缴费记录',
  );
  String get menuAwardsTitle => _p('Beasiswa', 'Scholarships', '奖学金');
  String get menuAwardsDesc => _p(
    'Beasiswa dan potongan biaya',
    'Scholarships and fee discounts',
    '奖学金与费用减免',
  );
  String get menuActivitiesTitle =>
      _p('Kegiatan Kemahasiswaan', 'Student activities', '学生活动');
  String get menuActivitiesDesc => _p(
    'Organisasi, kegiatan, dan poin',
    'Organizations, activities, and points',
    '社团、活动与积分',
  );
  String get menuRequestsTitle =>
      _p('Layanan Administrasi', 'Admin services', '行政服务');
  String get menuRequestsDesc => _p(
    'Surat dan pengajuan administrasi',
    'Letters and administrative requests',
    '证明信与行政申请',
  );
  String get menuInfoTitle =>
      _p('Pusat Informasi', 'Information center', '信息中心');
  String get menuInfoDesc => _p(
    'Berita dan pengumuman kampus',
    'Campus news and announcements',
    '校园新闻与公告',
  );
  String get menuNotificationsTitle => _p('Notifikasi', 'Notifications', '通知');
  String get menuNotificationsDesc =>
      _p('Pemberitahuan terbaru', 'Latest notices', '最新通知');

  String lastTermIps(String? label) => label == null ? ips : '$ips · $label';
  String creditsOf(int? used, int? max) =>
      max == null ? '${used ?? 0}' : '${used ?? 0}/$max';
  String minimum(String percent) =>
      _p('Minimum $percent%', 'Minimum $percent%', '最低 $percent%');
  String unread(int n) => _p('$n belum dibaca', '$n unread', '$n 条未读');
  String moreItems(int n) => _p('+$n lainnya', '+$n more', '另外 $n 项');
}

extension StudentHomeL10n on BuildContext {
  StudentHomeStrings get homeStr => StudentHomeStrings(this);
}
