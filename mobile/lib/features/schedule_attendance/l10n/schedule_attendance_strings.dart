import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class ScheduleAttendanceStrings {
  const ScheduleAttendanceStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  String get scheduleTitle => _p('Jadwal kuliah', 'Class schedule', '课程表');
  String get calendarTitle =>
      _p('Kalender akademik', 'Academic calendar', '校历');
  String get attendanceTitle => _p('Presensi', 'Attendance', '出勤');
  String get todayBadge => _p('Hari ini', 'Today', '今天');
  String get nextClass => _p('Kelas berikutnya', 'Next class', '下一节课');
  String get todayClasses => _p('Kelas hari ini', 'Today\'s classes', '今日课程');
  String get noClassToday =>
      _p('Tidak ada kelas hari ini', 'No classes today', '今天没有课');
  String get noClassesThisDay =>
      _p('Tidak ada kelas', 'No classes', '没有课程');
  String get scheduleEmptyTitle =>
      _p('Belum ada jadwal', 'No schedule yet', '暂无课表');
  String get scheduleEmptyBody => _p(
    'Jadwal muncul setelah KRS semester berjalan terisi.',
    'Your schedule appears once the current term KRS is filled.',
    '本学期选课完成后将显示课表。',
  );
  String get room => _p('Ruang', 'Room', '教室');
  String get pendingApproval =>
      _p('Menunggu persetujuan', 'Pending approval', '待审批');
  String get statusUpcoming => _p('Akan datang', 'Upcoming', '即将开始');
  String get statusOngoing => _p('Berlangsung', 'Ongoing', '进行中');
  String get statusFinished => _p('Selesai', 'Finished', '已结束');
  String classCredits(int c) => _p('$c SKS', '$c credits', '$c 学分');
  String get lecturer => _p('Dosen', 'Lecturer', '教师');
  String get previousMonth => _p('Bulan sebelumnya', 'Previous month', '上个月');
  String get nextMonth => _p('Bulan berikutnya', 'Next month', '下个月');
  String get calendarEmpty =>
      _p('Tidak ada agenda bulan ini', 'No events this month', '本月没有活动');
  String get allDay => _p('Sepanjang hari', 'All day', '全天');
  String get upcoming => _p('Mendatang', 'Upcoming', '即将到来');
  String get past => _p('Sudah lewat', 'Past', '已过');

  String categoryLabel(String category, String? serverLabel) =>
      switch (category) {
        'registration' => _p('Registrasi', 'Registration', '注册'),
        'krs' => 'KRS',
        'lecture' => _p('Perkuliahan', 'Lectures', '授课'),
        'midterm_exam' => _p('UTS', 'Midterm exam', '期中考试'),
        'final_exam' => _p('UAS', 'Final exam', '期末考试'),
        'holiday' => _p('Libur', 'Holiday', '假期'),
        'graduation' => _p('Wisuda', 'Graduation', '毕业典礼'),
        'semester_start' => _p('Awal semester', 'Term start', '学期开始'),
        'semester_end' => _p('Akhir semester', 'Term end', '学期结束'),
        'exam' => _p('Ujian', 'Exam', '考试'),
        'assignment_deadline' => _p('Tenggat tugas', 'Assignment due', '作业截止'),
        'other' => _p('Lainnya', 'Other', '其他'),
        _ => serverLabel ?? category,
      };

  String get overall => _p('Kehadiran keseluruhan', 'Overall attendance', '总出勤');
  String get present => _p('Hadir', 'Present', '出席');
  String get sick => _p('Sakit', 'Sick', '病假');
  String get permitted => _p('Izin', 'Permit', '请假');
  String get absent => _p('Alpa', 'Absent', '缺席');
  String meetings(int n) => _p('$n pertemuan', '$n meetings', '$n 次课');
  String get term => _p('Semester', 'Term', '学期');
  String get attendanceEmptyTitle =>
      _p('Belum ada data presensi', 'No attendance data', '暂无出勤数据');
  String get attendanceEmptyBody => _p(
    'Presensi tampil setelah ada mata kuliah terdaftar.',
    'Attendance appears once you have enrolled courses.',
    '有已选课程后将显示出勤。',
  );
  String belowMinimumBanner(String min, int count) => _p(
    '$count mata kuliah di bawah batas minimum kehadiran $min%.',
    '$count course(s) below the minimum attendance of $min%.',
    '$count 门课程低于最低出勤要求 $min%。',
  );
  String get belowMinimum => _p('Di bawah minimum', 'Below minimum', '低于最低要求');
  String minimumNote(String min) =>
      _p('Batas minimum: $min%', 'Minimum: $min%', '最低要求：$min%');
  String get attendanceDetailTitle =>
      _p('Detail presensi', 'Attendance detail', '出勤详情');
  String get meetingsList => _p('Daftar pertemuan', 'Meetings', '课次列表');
  String get noMeetings =>
      _p('Belum ada pertemuan tercatat', 'No meetings recorded', '暂无课次记录');
  String meetingNo(int? n) =>
      n == null ? _p('Pertemuan', 'Meeting', '课次') : _p('Pertemuan $n', 'Meeting $n', '第 $n 次');

  String meetingStatus(String status, String? serverLabel) =>
      switch (status) {
        'present' => present,
        'permitted' => permitted,
        'sick' => sick,
        'absent' => absent,
        _ => serverLabel ?? status,
      };
}

extension ScheduleAttendanceStringsX on BuildContext {
  ScheduleAttendanceStrings get sa => ScheduleAttendanceStrings(this);
}
