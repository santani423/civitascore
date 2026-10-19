import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class KrsStrings {
  const KrsStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  String get title => _p('KRS', 'Study plan (KRS)', '选课单 (KRS)');
  String get offeringsTitle =>
      _p('Pilih mata kuliah', 'Choose courses', '选择课程');
  String get historyTitle => _p('Riwayat KRS', 'KRS history', 'KRS 历史');
  String get history => _p('Riwayat', 'History', '历史');
  String get viewHistory =>
      _p('Lihat riwayat KRS', 'View KRS history', '查看 KRS 历史');
  String get pdfTooltip =>
      _p('Unduh KRS (PDF)', 'Download KRS (PDF)', '下载 KRS (PDF)');
  String get downloadPdf =>
      _p('Unduh KRS (PDF)', 'Download KRS (PDF)', '下载 KRS (PDF)');

  // Status ------------------------------------------------------------------
  String statusLabel(String status, String? serverLabel) => switch (status) {
    'no_active_term' => _p(
      'Belum ada semester aktif',
      'No active term',
      '暂无进行中的学期',
    ),
    'not_started' => _p('Belum dibuka', 'Not open yet', '尚未开放'),
    'draft' => _p('Draf', 'Draft', '草稿'),
    'submitted' => _p('Menunggu persetujuan', 'Awaiting approval', '等待审批'),
    'pending' => _p('Menunggu persetujuan', 'Awaiting approval', '等待审批'),
    'approved' => _p('Disetujui', 'Approved', '已批准'),
    'enrolled' => _p('Terdaftar', 'Enrolled', '已选'),
    'rejected' => _p('Ditolak', 'Rejected', '已驳回'),
    'dropped' => _p('Dibatalkan', 'Dropped', '已退课'),
    _ => serverLabel ?? status,
  };

  // Overview ------------------------------------------------------------------
  String get krsPeriod => _p('Periode KRS', 'KRS period', 'KRS 期间');
  String get periodOpen => _p('Dibuka', 'Open', '开放');
  String get periodClosed => _p('Ditutup', 'Closed', '关闭');
  String get periodUnset => _p('Belum ditetapkan', 'Not set', '未设定');
  String get term => _p('Semester', 'Term', '学期');
  String get credits => _p('SKS', 'Credits', '学分');
  String get creditsUsed => _p('SKS terpakai', 'Credits used', '已用学分');
  String creditsOf(String used, String? max) => max == null
      ? _p('$used SKS', '$used credits', '$used 学分')
      : _p('$used dari $max SKS', '$used of $max credits', '$used / $max 学分');
  String get creditsLimitHint => _p(
    'Batas SKS ditentukan dari IPS semester sebelumnya.',
    'The credit limit is derived from your previous-term GPA (IPS).',
    '学分上限由上学期 IPS 决定。',
  );
  String creditsRemaining(String n) =>
      _p('Sisa $n SKS', '$n credits left', '剩余 $n 学分');
  String get overLimit =>
      _p('Melebihi batas SKS', 'Over the credit limit', '超出学分上限');
  String get advisor => _p('Dosen wali', 'Academic advisor', '学术导师');
  String get decision => _p('Keputusan dosen wali', 'Advisor decision', '导师决定');
  String get decidedBy => _p('Diputuskan oleh', 'Decided by', '决定人');
  String get decidedAt => _p('Tanggal keputusan', 'Decision date', '决定日期');
  String get submittedAt => _p('Diajukan', 'Submitted', '提交时间');
  String get advisorNote => _p('Catatan dosen wali', 'Advisor note', '导师备注');
  String get rejectedHint => _p(
    'KRS ditolak. Perbaiki mata kuliah sesuai catatan, lalu ajukan kembali.',
    'KRS was rejected. Adjust your courses per the note and submit again.',
    'KRS 已被驳回。请按备注调整课程后重新提交。',
  );
  String get approvedHint => _p(
    'KRS telah disetujui dan terkunci.',
    'KRS has been approved and is locked.',
    'KRS 已批准并锁定。',
  );
  String get submittedHint => _p(
    'KRS menunggu persetujuan dosen wali. Tarik kembali untuk mengubah.',
    'KRS is awaiting advisor approval. Withdraw it to make changes.',
    'KRS 正在等待导师审批。撤回后方可修改。',
  );
  String get notices =>
      _p('Tidak dapat mengubah KRS', 'KRS cannot be edited', '无法修改 KRS');
  String get selectedCourses =>
      _p('Mata kuliah dipilih', 'Selected courses', '已选课程');
  String get emptyItemsTitle =>
      _p('Belum ada mata kuliah', 'No courses yet', '尚未选课');
  String get emptyItemsBody => _p(
    'Tambahkan mata kuliah dari daftar penawaran kelas.',
    'Add courses from the class offerings.',
    '请从开课列表中添加课程。',
  );
  String get addCourses => _p('Tambah mata kuliah', 'Add courses', '添加课程');
  String get noTermTitle =>
      _p('Belum ada semester aktif', 'No active term', '暂无进行中的学期');
  String get noTermBody => _p(
    'KRS akan tersedia ketika semester aktif ditetapkan.',
    'KRS will be available once an active term is set.',
    '设定进行中学期后即可使用 KRS。',
  );
  String get remove => _p('Hapus', 'Remove', '移除');
  String get removeTitle => _p('Hapus mata kuliah?', 'Remove course?', '移除课程？');
  String removeBody(String name) => _p(
    '$name akan dihapus dari KRS Anda.',
    '$name will be removed from your KRS.',
    '$name 将从您的 KRS 中移除。',
  );
  String get removed => _p(
    'Mata kuliah dihapus dari KRS.',
    'Course removed from KRS.',
    '课程已从 KRS 移除。',
  );
  String get added => _p(
    'Mata kuliah ditambahkan ke KRS.',
    'Course added to KRS.',
    '课程已添加到 KRS。',
  );
  String get submit => _p('Ajukan KRS', 'Submit KRS', '提交 KRS');
  String get submitTitle => _p(
    'Ajukan KRS ke dosen wali?',
    'Submit KRS to your advisor?',
    '向导师提交 KRS？',
  );
  String submitBody(String credits, int courses) => _p(
    'Anda mengajukan $courses mata kuliah ($credits SKS). Setelah diajukan, KRS tidak dapat diubah sampai ditarik kembali atau diputuskan.',
    'You are submitting $courses courses ($credits credits). Once submitted, KRS cannot be changed until withdrawn or decided.',
    '您将提交 $courses 门课程（$credits 学分）。提交后在撤回或审批前不可修改。',
  );
  String get submitted => _p(
    'KRS berhasil diajukan dan menunggu persetujuan dosen wali.',
    'KRS submitted and awaiting advisor approval.',
    'KRS 已提交，等待导师审批。',
  );
  String get cancelSubmission =>
      _p('Tarik pengajuan', 'Withdraw submission', '撤回提交');
  String get cancelTitle =>
      _p('Tarik pengajuan KRS?', 'Withdraw KRS submission?', '撤回 KRS 提交？');
  String get cancelBody => _p(
    'KRS kembali menjadi draf dan dapat diubah lagi.',
    'KRS goes back to draft and can be edited again.',
    'KRS 将恢复为草稿并可再次修改。',
  );
  String get cancelled => _p(
    'Pengajuan KRS ditarik kembali.',
    'KRS submission withdrawn.',
    'KRS 提交已撤回。',
  );

  // Offerings ---------------------------------------------------------------
  String get searchHint => _p(
    'Cari kode atau nama mata kuliah',
    'Search course code or name',
    '搜索课程代码或名称',
  );
  String get filterAll => _p('Semua', 'All', '全部');
  String get filterAvailable => _p('Tersedia', 'Available', '可选');
  String get filterSelected => _p('Dipilih', 'Selected', '已选');
  String get emptyOfferingsTitle =>
      _p('Tidak ada penawaran kelas', 'No class offerings', '暂无开课');
  String get emptyOfferingsBody => _p(
    'Tidak ada mata kuliah yang cocok atau belum ada kelas dibuka.',
    'No matching courses, or no classes are open yet.',
    '没有匹配的课程，或尚未开课。',
  );
  String semesterLevel(int n) => _p('Semester $n', 'Semester $n', '第 $n 学期');
  String get prerequisites => _p('Prasyarat', 'Prerequisites', '先修课');
  String prerequisiteLine(String name, String grade) =>
      _p('$name (min. $grade)', '$name (min. $grade)', '$name（最低 $grade）');
  String get retake => _p('Mengulang', 'Retake', '重修');
  String bestGrade(String g) =>
      _p('Nilai terbaik $g', 'Best grade $g', '最佳成绩 $g');
  String get selected => _p('Dipilih', 'Selected', '已选');
  String get full => _p('Penuh', 'Full', '已满');
  String seats(int left, int capacity) => _p(
    'Sisa $left dari $capacity kursi',
    '$left of $capacity seats left',
    '剩余 $left / $capacity 个名额',
  );
  String get add => _p('Ambil', 'Add', '选择');
  String get scheduleConflict =>
      _p('Bentrok jadwal', 'Schedule conflict', '时间冲突');
  String get noSchedule =>
      _p('Jadwal belum ditetapkan', 'No schedule yet', '尚未排课');
  String get lecturer => _p('Dosen', 'Lecturer', '教师');
  String get editLocked => _p(
    'KRS tidak dapat diubah saat ini, sehingga mata kuliah tidak dapat ditambahkan.',
    'KRS cannot be edited right now, so courses cannot be added.',
    '当前无法修改 KRS，因此不能添加课程。',
  );
  String get blockedTitle => _p(
    'Pengisian KRS diblokir (cekal)',
    'KRS blocked (academic hold)',
    'KRS 被封锁（学籍限制）',
  );
  String get blockedAction =>
      _p('Lihat status cekal', 'View hold status', '查看限制状态');
  String get conflictTitle => _p('Jadwal bentrok', 'Schedule conflict', '时间冲突');
  String get conflictWith => _p('Bentrok dengan:', 'Clashes with:', '与以下课程冲突：');

  // History -----------------------------------------------------------------
  String get historyEmptyTitle =>
      _p('Belum ada riwayat KRS', 'No KRS history', '暂无 KRS 历史');
  String get historyEmptyBody => _p(
    'Riwayat akan muncul setelah Anda mengisi KRS.',
    'History appears once you have filed a KRS.',
    '填写 KRS 后将显示历史记录。',
  );
  String courseCount(int n) => _p('$n mata kuliah', '$n courses', '$n 门课程');
  String get grade => _p('Nilai', 'Grade', '成绩');
}

extension KrsStringsX on BuildContext {
  KrsStrings get krs => KrsStrings(this);
}
