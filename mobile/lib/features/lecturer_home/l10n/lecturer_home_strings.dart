import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class LecturerHomeStrings {
  const LecturerHomeStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  /// Kicker hero beranda dosen, mengikuti jam seperti beranda mahasiswa.
  String heroGreeting(int hour) {
    if (hour < 11) return _p('Selamat pagi', 'Good morning', '早上好');
    if (hour < 15) return _p('Selamat siang', 'Good afternoon', '中午好');
    if (hour < 18) return _p('Selamat sore', 'Good evening', '下午好');
    return _p('Selamat malam', 'Good night', '晚上好');
  }

  String get heroWelcome =>
      _p('Selamat datang di One Atma', 'Welcome to One Atma', '欢迎使用 One Atma');
  String get todayTeaching => _p('Mengajar hari ini', 'Teaching today', '今日授课');
  String get noClassToday => _p(
    'Tidak ada jadwal mengajar hari ini.',
    'No classes scheduled today.',
    '今天没有课程。',
  );
  String get nextClass => _p('Berikutnya', 'Next', '下一节');
  String get classesTaught => _p('Kelas diampu', 'Classes taught', '授课班级');
  String get todaySessions => _p('Sesi hari ini', 'Sessions today', '今日课次');
  String get quickActions => _p('Akses cepat', 'Quick actions', '快捷入口');
  String get myClasses => _p('Kelas saya', 'My classes', '我的班级');
  String get schedule => _p('Jadwal', 'Schedule', '课表');
  String get exams => _p('Ujian', 'Exams', '考试');
  String get questionBank => _p('Bank soal', 'Question bank', '题库');
  String get calendar => _p('Kalender', 'Calendar', '日历');
  String get krsApprovals => _p('Persetujuan KRS', 'KRS approvals', '选课审批');
  String pendingKrs(int n) => _p(
    '$n pengajuan menunggu persetujuan',
    '$n submissions awaiting approval',
    '$n 份提交待审批',
  );
  String get noPendingKrs => _p(
    'Tidak ada pengajuan KRS yang menunggu.',
    'No KRS submissions waiting.',
    '没有待审批的选课。',
  );
  String get unreadNotifications =>
      _p('Notifikasi belum dibaca', 'Unread notifications', '未读通知');
  String get sectionFailed => _p(
    'Bagian ini gagal dimuat.',
    'This section failed to load.',
    '此部分加载失败。',
  );
}

extension LecturerHomeStringsX on BuildContext {
  LecturerHomeStrings get homeL10n => LecturerHomeStrings(this);
}
