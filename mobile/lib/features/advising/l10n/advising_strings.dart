import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';
import '../../../core/theme/app_palette.dart';

class AdvisingStrings {
  const AdvisingStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  String get title => _p('Perwalian', 'Advising', '学业指导');
  String get searchHint => _p(
    'Cari nama atau NIM mahasiswa',
    'Search student name or ID',
    '搜索学生姓名或学号',
  );
  String get emptyTitle =>
      _p('Tidak ada pengajuan KRS', 'No KRS submissions', '没有选课提交');
  String get emptyBody => _p(
    'Belum ada pengajuan KRS dengan filter ini.',
    'No KRS submissions match this filter.',
    '没有符合此筛选的选课提交。',
  );
  String get detailTitle => _p('Pengajuan KRS', 'KRS submission', '选课提交');
  String get student => _p('Mahasiswa', 'Student', '学生');
  String get nim => _p('NIM', 'Student ID', '学号');
  String get studyProgram => _p('Program studi', 'Study program', '专业');
  String get admissionYear => _p('Angkatan', 'Class of', '入学年份');
  String get term => _p('Semester', 'Term', '学期');
  String get submittedAt => _p('Diajukan', 'Submitted', '提交时间');
  String get decidedAt => _p('Diputuskan', 'Decided', '决定时间');
  String get decidedBy => _p('Diputuskan oleh', 'Decided by', '决定人');
  String get decisionNote => _p('Catatan keputusan', 'Decision note', '决定备注');
  String get totalCredits => _p('Total SKS', 'Total credits', '总学分');
  String get ips => _p('IPS', 'GPA (term)', '学期绩点');
  String get ipk => _p('IPK', 'Cumulative GPA', '累计绩点');
  String creditsOfLimit(int total, int? max) => max == null
      ? _p('$total SKS', '$total credits', '$total 学分')
      : _p('$total / $max SKS', '$total / $max credits', '$total / $max 学分');
  String credits(int n) => _p('$n SKS', '$n credits', '$n 学分');
  String get overLimit => _p(
    'Total SKS melebihi batas maksimum.',
    'Total credits exceed the maximum limit.',
    '总学分超过上限。',
  );
  String get courses => _p('Mata kuliah diajukan', 'Requested courses', '申请课程');
  String get noItems => _p('Tidak ada mata kuliah.', 'No courses.', '没有课程。');
  String get lecturer => _p('Dosen', 'Lecturer', '教师');
  String get openAcademicRecord =>
      _p('Lihat rekam akademik', 'View academic record', '查看学业记录');
  String get approve => _p('Setujui', 'Approve', '批准');
  String get reject => _p('Tolak / kembalikan', 'Reject / return', '驳回/退回');
  String get approveConfirmTitle =>
      _p('Setujui KRS ini?', 'Approve this KRS?', '批准此选课？');
  String get approveConfirmBody => _p(
    'Mata kuliah akan didaftarkan untuk mahasiswa.',
    'The courses will be enrolled for the student.',
    '课程将为学生注册。',
  );
  String get rejectTitle =>
      _p('Alasan penolakan', 'Reason for rejection', '驳回原因');
  String get rejectLabel => _p(
    'Catatan untuk mahasiswa (min. 5 karakter)',
    'Note for the student (min. 5 characters)',
    '给学生的备注（至少 5 个字符）',
  );
  String get approved => _p('KRS disetujui.', 'KRS approved.', '选课已批准。');
  String get rejected => _p(
    'KRS dikembalikan ke mahasiswa.',
    'KRS returned to the student.',
    '选课已退回给学生。',
  );
  String get notePending => _p(
    'Menunggu keputusan Anda.',
    'Awaiting your decision.',
    '等待您的决定。',
  );

  String statusLabel(String status, [String? fallback]) => switch (status) {
    'all' => _p('Semua', 'All', '全部'),
    'draft' => _p('Draf', 'Draft', '草稿'),
    'submitted' => _p('Diajukan', 'Submitted', '已提交'),
    'approved' => _p('Disetujui', 'Approved', '已批准'),
    'rejected' => _p('Ditolak', 'Rejected', '已驳回'),
    _ => fallback ?? status,
  };

  StatusTone statusTone(String status) => switch (status) {
    'submitted' => StatusTone.warning,
    'approved' => StatusTone.success,
    'rejected' => StatusTone.danger,
    _ => StatusTone.neutral,
  };
}

extension AdvisingStringsX on BuildContext {
  AdvisingStrings get advisingL10n => AdvisingStrings(this);
}
