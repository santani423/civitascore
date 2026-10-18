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
  String get quickActions => _p('Menu cepat', 'Quick actions', '快捷入口');
  String get qaKrs => _p('KRS', 'KRS', '选课');
  String get qaSchedule => _p('Jadwal', 'Schedule', '课表');
  String get qaGrades => _p('Nilai/KHS', 'Grades', '成绩');
  String get qaAssignments => _p('Tugas', 'Assignments', '作业');
  String get qaExams => _p('Ujian', 'Exams', '考试');
  String get qaInvoices => _p('Tagihan', 'Invoices', '账单');
  String get qaRequests => _p('Pengajuan', 'Requests', '申请');
  String get qaCalendar => _p('Kalender', 'Calendar', '日历');

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
