import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';
import '../domain/pmb_duty_models.dart';

class PmbDutyStrings {
  const PmbDutyStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // Sessions ----------------------------------------------------------------
  String get moduleTitle =>
      _p('Tugas ujian PMB', 'Admission exam duties', '招生考试任务');
  String homeSummary(int active, int total) => active > 0
      ? _p(
          '$active sesi aktif dari $total sesi ditugaskan',
          '$active active of $total assigned sessions',
          '共 $total 场，$active 场进行中或待开始',
        )
      : _p(
          '$total sesi ditugaskan',
          '$total assigned sessions',
          '共 $total 场已分配',
        );
  String get activeSessions =>
      _p('Berlangsung & mendatang', 'Ongoing & upcoming', '进行中与即将开始');
  String get finishedSessions => _p('Selesai', 'Finished', '已结束');
  String get noSessionsTitle =>
      _p('Tidak ada sesi yang ditugaskan', 'No assigned sessions', '暂无分配的场次');
  String get noSessionsBody => _p(
    'Sesi ujian seleksi PMB muncul di sini setelah panitia menugaskan Anda sebagai pengawas, penguji, atau pewawancara.',
    'Admission exam sessions appear here once the committee assigns you as proctor, examiner or interviewer.',
    '招生委员会将您指派为监考、考官或面试官后，相关场次会显示在这里。',
  );
  String get ongoing => _p('Berlangsung', 'Ongoing', '进行中');
  String get scoresLocked => _p('Nilai terkunci', 'Scores locked', '成绩已锁定');
  String participants(int n) => _p('$n peserta', '$n participants', '$n 名考生');
  String sessionTitle(String code) =>
      _p('Sesi $code', 'Session $code', '场次 $code');

  String roleLabel(PmbExaminerRole r) => switch (r) {
    PmbExaminerRole.proctor => _p('Pengawas', 'Proctor', '监考'),
    PmbExaminerRole.examiner => _p('Penguji', 'Examiner', '考官'),
    PmbExaminerRole.interviewer => _p('Pewawancara', 'Interviewer', '面试官'),
  };

  String modeLabel(String? mode) => switch (mode) {
    'onsite' => _p('Tatap muka', 'On-site', '现场'),
    'online_proctored' => _p('Daring diawasi', 'Online proctored', '线上监考'),
    'cbt' => 'CBT',
    'interview' => _p('Wawancara', 'Interview', '面试'),
    'portfolio_review' => _p('Telaah portofolio', 'Portfolio review', '作品集评审'),
    _ => mode ?? '—',
  };

  // Session detail / attendance ----------------------------------------------
  String get mode => _p('Bentuk ujian', 'Exam format', '考试形式');
  String get time => _p('Waktu', 'Time', '时间');
  String get room => _p('Ruang', 'Room', '考场');
  String get online => _p('Daring', 'Online', '线上');
  String get capacity => _p('Kapasitas', 'Capacity', '容量');
  String get checkinOpens => _p('Check-in dibuka', 'Check-in opens', '签到开放');
  String get myRole => _p('Peran saya', 'My role', '我的角色');

  String attendanceLabel(PmbAttendance a) => switch (a) {
    PmbAttendance.registered => _p('Terdaftar', 'Registered', '已报名'),
    PmbAttendance.present => _p('Hadir', 'Present', '出席'),
    PmbAttendance.absent => _p('Tidak hadir', 'Absent', '缺席'),
    PmbAttendance.excused => _p('Izin', 'Excused', '请假'),
    PmbAttendance.disqualified => _p('Diskualifikasi', 'Disqualified', '取消资格'),
  };

  String get scanQr => _p('Pindai QR', 'Scan QR', '扫码');
  String get enterCode => _p('Masukkan kode', 'Enter code', '输入代码');
  String get cardToken =>
      _p('Kode QR kartu peserta', 'Participant card QR code', '考生卡二维码内容');
  String get searchHint => _p(
    'Cari nama atau nomor peserta',
    'Search name or participant number',
    '搜索姓名或考生编号',
  );
  String get noParticipantsTitle =>
      _p('Belum ada peserta', 'No participants yet', '暂无考生');
  String get noMatchTitle =>
      _p('Peserta tidak ditemukan', 'No matching participant', '未找到匹配的考生');
  String get changeAttendance =>
      _p('Ubah kehadiran', 'Change attendance', '修改出勤状态');
  String checkedInAt(String time) =>
      _p('Check-in $time', 'Checked in $time', '签到 $time');
  String marked(String name, String status) =>
      _p('$name: $status', '$name: $status', '$name：$status');
  String get attendanceClosed => _p(
    'Sesi telah berakhir lebih dari 2 jam. Perubahan kehadiran hanya dapat dilakukan panitia seleksi.',
    'The session ended more than 2 hours ago. Only the selection committee can change attendance now.',
    '场次结束已超过 2 小时，仅招生委员会可修改出勤状态。',
  );
  String get impactTitle =>
      _p('Ubah status kehadiran?', 'Change attendance?', '确认修改出勤状态？');
  String impactBody(String name, String status) => _p(
    '$name akan ditandai "$status". Status seleksi pendaftar ikut berubah dan pendaftar menerima notifikasi.',
    '$name will be marked "$status". The applicant\'s selection status changes and they will be notified.',
    '$name 将被标记为“$status”。考生的选拔状态会随之改变，并会收到通知。',
  );
  String get attendanceSheet =>
      _p('Daftar hadir (PDF)', 'Attendance sheet (PDF)', '签到表（PDF）');
  String get openLinkFailed => _p(
    'Tautan tidak dapat dibuka.',
    'The link couldn\'t be opened.',
    '无法打开链接。',
  );
  String get privacyNote => _p(
    'Data peserta ditampilkan minimal (nama dan nomor peserta).',
    'Participant data is kept minimal (name and participant number).',
    '仅显示最少的考生信息（姓名与考生编号）。',
  );

  // Scanner -------------------------------------------------------------------
  String get scannerTitle =>
      _p('Pindai kartu peserta', 'Scan participant card', '扫描考生卡');
  String get scannerHint => _p(
    'Arahkan kamera ke kode QR pada kartu ujian. Peserta yang dipindai ditandai hadir.',
    'Point the camera at the QR code on the exam card. Scanned participants are marked present.',
    '将摄像头对准准考证上的二维码，扫描后考生将被标记为出席。',
  );
  String get torch => _p('Senter', 'Torch', '手电筒');
  String get cameraError => _p(
    'Kamera tidak dapat digunakan. Izinkan akses kamera di pengaturan, atau masukkan kode secara manual.',
    'The camera can\'t be used. Allow camera access in settings, or enter the code manually.',
    '无法使用摄像头。请在设置中允许访问摄像头，或手动输入代码。',
  );
  String get invalidQr => _p(
    'Bukan kode QR kartu ujian PMB.',
    'Not an admission exam card QR code.',
    '不是招生考试准考证二维码。',
  );

  // Scores ----------------------------------------------------------------------
  String get scores => _p('Nilai sesi', 'Session scores', '场次成绩');
  String get inputScores => _p('Input nilai', 'Enter scores', '录入成绩');
  String scoredSummary(int done, int total) =>
      _p('$done/$total dinilai', '$done/$total scored', '已评 $done/$total');
  String rangeLabel(String min, String max) =>
      _p('Rentang nilai $min–$max', 'Score range $min–$max', '分数范围 $min–$max');
  String get passFailLabel =>
      _p('Lulus / tidak lulus', 'Pass / fail', '通过 / 不通过');
  String rubricLabel(String criteria) =>
      _p('Rubrik: $criteria', 'Rubric: $criteria', '评分细则：$criteria');
  String get lockedBanner => _p(
    'Nilai sesi sudah dikunci panitia dan hanya dapat dilihat.',
    'Session scores were locked by the committee and are read-only.',
    '本场成绩已被委员会锁定，仅可查看。',
  );
  String get proctorOnly => _p(
    'Anda bertugas sebagai pengawas pada sesi ini. Input nilai hanya untuk penguji atau pewawancara.',
    'You are the proctor of this session. Only examiners or interviewers enter scores.',
    '您是本场的监考，成绩仅由考官或面试官录入。',
  );
  String get registeredHint => _p(
    'Menyimpan nilai peserta berstatus Terdaftar otomatis menandainya Hadir. Peserta tidak hadir, izin, atau diskualifikasi tidak dinilai.',
    'Saving a score for a Registered participant marks them Present. Absent, excused or disqualified participants are not scored.',
    '为“已报名”考生保存成绩会自动将其标记为出席；缺席、请假或取消资格的考生不评分。',
  );
  String get notScored => _p('Belum dinilai', 'Not scored', '未评分');
  String get notScorable =>
      _p('Tidak dinilai', 'Not scored (attendance)', '不评分');
  String get score => _p('Nilai', 'Score', '分数');
  String get result => _p('Hasil', 'Result', '结果');
  String get passed => _p('Lulus', 'Pass', '通过');
  String get failed => _p('Tidak lulus', 'Fail', '不通过');
  String get note => _p('Catatan', 'Note', '备注');
  String outOfRange(String min, String max) => _p(
    'Nilai harus $min–$max',
    'Value must be $min–$max',
    '数值须在 $min–$max 之间',
  );
  String get scoreFormat => _p(
    'Gunakan angka dengan maks. 2 desimal, tanpa pemisah ribuan.',
    'Use a number with at most 2 decimals and no thousands separator.',
    '请输入最多两位小数的数字，不要使用千位分隔符。',
  );
  String get noteCannotBeCleared => _p(
    'Catatan yang tersimpan tidak dapat dikosongkan; ganti dengan teks lain.',
    'A saved note can\'t be emptied; replace it with other text.',
    '已保存的备注不能清空，请改为其他内容。',
  );
  String get chooseResult => _p('Pilih hasil', 'Choose a result', '请选择结果');
  String computedRaw(String raw) =>
      _p('Nilai dihitung: $raw', 'Computed score: $raw', '计算得分：$raw');
  String get saveAndNext => _p('Simpan & berikutnya', 'Save & next', '保存并下一位');
  String savedScore(String name) =>
      _p('Nilai $name disimpan', 'Score for $name saved', '$name 的成绩已保存');
  String get notSaved => _p(
    'Nilai tidak disimpan karena status kehadiran peserta.',
    'Not saved because of the participant\'s attendance status.',
    '因考生出勤状态，成绩未保存。',
  );
}

extension PmbDutyStringsX on BuildContext {
  PmbDutyStrings get pmbDuty => PmbDutyStrings(this);
}
