import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';
import '../../../core/l10n/core_strings.dart';
import '../../../core/network/api_failure.dart';
import '../domain/assessment_models.dart';

class AssessmentStrings {
  const AssessmentStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // Attendance
  String get attendanceTitle =>
      _p('Presensi kelas', 'Class attendance', '课堂考勤');
  String get tabMeetings => _p('Pertemuan', 'Meetings', '课次');
  String get tabRecap => _p('Rekap', 'Recap', '汇总');
  String get recordMeeting => _p('Rekam pertemuan', 'Record meeting', '记录课次');
  String get editMeeting => _p('Koreksi pertemuan', 'Edit meeting', '修改课次');
  String meetingLabel(int n) => _p('Pertemuan $n', 'Meeting $n', '第 $n 次课');
  String get noMeetingsTitle =>
      _p('Belum ada pertemuan', 'No meetings yet', '暂无课次');
  String get noMeetingsBody => _p(
    'Rekam presensi pertemuan pertama kelas ini.',
    'Record attendance for the first meeting of this class.',
    '请记录本班第一次课的考勤。',
  );
  String get noStudentsTitle =>
      _p('Belum ada mahasiswa aktif', 'No active students', '暂无在读学生');
  String get noStudentsBody => _p(
    'Hanya mahasiswa dengan KRS disetujui yang tampil.',
    'Only students with an approved KRS are listed.',
    '仅显示选课已获批准的学生。',
  );
  String statusLabel(AttendanceStatus s) => switch (s) {
    AttendanceStatus.present => _p('Hadir', 'Present', '出勤'),
    AttendanceStatus.permitted => _p('Izin', 'Excused', '请假'),
    AttendanceStatus.sick => _p('Sakit', 'Sick', '病假'),
    AttendanceStatus.absent => _p('Alpa', 'Absent', '缺勤'),
  };
  String get markAll => _p('Tandai semua', 'Mark all', '全部标记为');
  String get meetingNumber => _p('Nomor pertemuan', 'Meeting number', '课次编号');
  String get meetingDate => _p('Tanggal pertemuan', 'Meeting date', '上课日期');
  String get students => _p('Mahasiswa', 'Students', '学生');
  String get overwriteNotice => _p(
    'Pertemuan ini sudah direkam. Menyimpan akan menimpa status sebelumnya.',
    'This meeting is already recorded. Saving overwrites previous statuses.',
    '该课次已记录，保存将覆盖之前的状态。',
  );
  String get saveAttendance => _p('Simpan presensi', 'Save attendance', '保存考勤');
  String get integerRequired =>
      _p('Harus bilangan bulat', 'Must be a whole number', '必须为整数');
  String get recapEmpty => _p(
    'Rekap muncul setelah ada pertemuan yang direkam.',
    'The recap appears once meetings are recorded.',
    '记录课次后将显示汇总。',
  );
  String get atRisk => _p('Di bawah 75%', 'Below 75%', '低于 75%');
  String get noRecords => _p('Belum ada data', 'No data', '暂无数据');
  String recapSummary(int p, int i, int s, int a) => _p(
    'Hadir $p · Izin $i · Sakit $s · Alpa $a',
    'Present $p · Excused $i · Sick $s · Absent $a',
    '出勤 $p · 请假 $i · 病假 $s · 缺勤 $a',
  );

  // Grades
  String get gradesTitle => _p('Nilai kelas', 'Class grades', '班级成绩');
  String get notGraded => _p('Belum dinilai', 'Not graded', '未评分');
  String get score => _p('Nilai angka (0–100)', 'Score (0–100)', '分数（0–100）');
  String get letter => _p('Nilai huruf', 'Letter grade', '字母成绩');
  String get letterAuto =>
      _p('Otomatis dari skor', 'Automatic from score', '按分数自动换算');
  String get letterHint => _p(
    'Biarkan otomatis agar huruf dihitung server.',
    'Keep automatic so the server computes the letter.',
    '保持自动则由服务器换算字母。',
  );
  String get editGrade => _p('Ubah nilai', 'Edit grade', '修改成绩');
  String get bulkMode => _p('Isi massal', 'Bulk entry', '批量录入');
  String get exitBulk => _p('Keluar isi massal', 'Exit bulk entry', '退出批量录入');
  String get bulkHint => _p(
    'Isi skor, lalu simpan. Huruf dihitung otomatis oleh server.',
    'Enter scores, then save. The server computes letters automatically.',
    '录入分数后保存，字母由服务器自动换算。',
  );
  String saveAll(int n) => _p('Simpan $n nilai', 'Save $n grades', '保存 $n 条成绩');
  String get nothingToSave =>
      _p('Tidak ada perubahan', 'Nothing to save', '没有可保存的更改');
  String progress(int done, int total) => _p(
    'Menyimpan $done/$total…',
    'Saving $done/$total…',
    '正在保存 $done/$total…',
  );
  String bulkResult(int ok, int failed) => _p(
    '$ok tersimpan, $failed gagal',
    '$ok saved, $failed failed',
    '$ok 条已保存，$failed 条失败',
  );
  String savedLetter(String name, String letter) => _p(
    'Nilai $name tersimpan: $letter',
    'Grade for $name saved: $letter',
    '$name 的成绩已保存：$letter',
  );

  /// Error text that explains 403 (class not owned) in plain words.
  String errorText(Object e) {
    if (e is ApiFailure && e.kind == ApiFailureKind.forbidden) {
      return _p(
        'Anda tidak memiliki akses ke kelas ini (bukan kelas yang Anda ampu).',
        'You have no access to this class (not a class you teach).',
        '您无权访问该班级（非您任教的班级）。',
      );
    }
    return _c.l10n.errorFor(e);
  }
}

extension AssessmentStringsX on BuildContext {
  AssessmentStrings get assess => AssessmentStrings(this);
}
