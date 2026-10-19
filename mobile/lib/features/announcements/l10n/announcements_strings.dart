import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class AnnouncementsStrings {
  const AnnouncementsStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  String get title => _p('Pengumuman', 'Announcements', '公告');
  String get detailTitle => _p('Detail pengumuman', 'Announcement', '公告详情');
  String get searchHint =>
      _p('Cari pengumuman', 'Search announcements', '搜索公告');
  String get clearSearch => _p('Hapus pencarian', 'Clear search', '清除搜索');
  String get pinned => _p('Disematkan', 'Pinned', '置顶');
  String get unread => _p('Belum dibaca', 'Unread', '未读');
  String unreadCount(int n) => _p('$n belum dibaca', '$n unread', '$n 条未读');
  String get emptyTitle =>
      _p('Belum ada pengumuman', 'No announcements', '暂无公告');
  String get emptyBody => _p(
    'Pengumuman kampus untuk Anda akan tampil di sini.',
    'Campus announcements for you will appear here.',
    '面向您的校园公告将显示在这里。',
  );
  String get attachment => _p('Lampiran', 'Attachment', '附件');
  String get download => _p('Unduh lampiran', 'Download attachment', '下载附件');
  String get by => _p('Oleh', 'By', '发布者');
  String get published => _p('Dipublikasikan', 'Published', '发布时间');

  String filterLabel(String f) => switch (f) {
    'all' => _p('Semua', 'All', '全部'),
    'unread' => unread,
    'study_program' => _p('Program studi', 'Study program', '专业'),
    'faculty' => _p('Fakultas', 'Faculty', '学院'),
    'admission_year' => _p('Angkatan', 'Admission year', '入学年份'),
    'semester' => _p('Semester', 'Semester', '学期'),
    'students' => _p('Mahasiswa', 'Students', '学生'),
    _ => f,
  };
}

extension AnnouncementsStringsX on BuildContext {
  AnnouncementsStrings get an => AnnouncementsStrings(this);
}
