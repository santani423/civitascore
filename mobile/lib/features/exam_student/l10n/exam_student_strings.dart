import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';
import '../../../core/theme/app_palette.dart';
import '../domain/exam_models.dart';

class ExamStudentStrings {
  const ExamStudentStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // List / detail ---------------------------------------------------------
  String get title => _p('Ujian saya', 'My exams', '我的考试');
  String get emptyTitle => _p('Belum ada ujian', 'No exams yet', '暂无考试');
  String get emptyBody => _p(
    'Ujian yang dipublikasikan dosen untuk kelas Anda akan tampil di sini.',
    'Exams published by your lecturers for your classes will appear here.',
    '讲师为您班级发布的考试将显示在这里。',
  );
  String get detailTitle => _p('Detail ujian', 'Exam details', '考试详情');
  String get course => _p('Mata kuliah', 'Course', '课程');
  String get lecturer => _p('Dosen', 'Lecturer', '讲师');
  String get duration => _p('Durasi', 'Duration', '时长');
  String minutes(int n) => _p('$n menit', '$n min', '$n 分钟');
  String get window => _p('Jadwal', 'Schedule', '时间安排');
  String get opens => _p('Dibuka', 'Opens', '开始');
  String get closes => _p('Ditutup', 'Closes', '截止');
  String get anytime => _p('Tanpa batas waktu', 'No time limit', '不限时间');
  String get questions => _p('Jumlah soal', 'Questions', '题数');
  String get attempts => _p('Percobaan', 'Attempts', '尝试次数');
  String attemptsOf(int used, int max) =>
      _p('$used dari $max terpakai', '$used of $max used', '已用 $used / $max');
  String get backNavigation =>
      _p('Kembali ke soal sebelumnya', 'Go back to earlier questions', '返回上一题');
  String get allowed => _p('Diizinkan', 'Allowed', '允许');
  String get notAllowed => _p('Tidak diizinkan', 'Not allowed', '不允许');
  String get score => _p('Nilai', 'Score', '成绩');
  String get rulesTitle => _p('Aturan ujian', 'Exam rules', '考试规则');
  String get ruleSecure => _p(
    'Tangkapan layar dan perekaman layar dinonaktifkan selama ujian.',
    'Screenshots and screen recording are disabled during the exam.',
    '考试期间禁用截屏和录屏。',
  );
  String get ruleAutosave => _p(
    'Jawaban tersimpan otomatis setiap kali Anda memilih opsi.',
    'Answers are saved automatically each time you pick an option.',
    '每次选择选项时答案会自动保存。',
  );
  String get ruleAutoSubmit => _p(
    'Ujian dikumpulkan otomatis saat waktu habis.',
    'The exam is submitted automatically when time runs out.',
    '时间用完后将自动交卷。',
  );
  String get ruleViolation => _p(
    'Meninggalkan aplikasi (pindah aplikasi / layar kunci) dicatat sebagai pelanggaran dan mengurangi nilai 1 poin per kejadian.',
    'Leaving the app (switching apps / locking the screen) is recorded as a violation and deducts 1 point each time.',
    '离开应用（切换应用/锁屏）将被记为违规，每次扣 1 分。',
  );
  String get ruleNoBack => _p(
    'Soal yang sudah dilewati tidak dapat dikunjungi kembali.',
    'Questions you have moved past cannot be revisited.',
    '已跳过的题目无法再返回。',
  );
  String get ruleBackOk => _p(
    'Anda boleh kembali ke soal sebelumnya.',
    'You may go back to earlier questions.',
    '您可以返回上一题。',
  );
  String get start => _p('Mulai mengerjakan', 'Start exam', '开始考试');
  String get resume => _p('Lanjutkan mengerjakan', 'Resume exam', '继续考试');
  String get viewResult => _p('Lihat hasil', 'View result', '查看成绩');
  String get waitingResult => _p(
    'Menunggu hasil dipublikasikan oleh dosen.',
    'Waiting for the lecturer to publish the result.',
    '等待讲师公布成绩。',
  );
  String get confirmStartTitle =>
      _p('Mulai ujian sekarang?', 'Start the exam now?', '现在开始考试？');
  String confirmStartBody(int minutes) => _p(
    'Waktu $minutes menit langsung berjalan dan tidak dapat dijeda. Pastikan koneksi stabil dan jangan meninggalkan aplikasi.',
    'The $minutes-minute timer starts immediately and cannot be paused. Make sure your connection is stable and do not leave the app.',
    '$minutes 分钟计时立即开始且无法暂停。请确保网络稳定，并且不要离开应用。',
  );
  String get confirmResumeTitle =>
      _p('Lanjutkan ujian?', 'Resume the exam?', '继续考试？');
  String get confirmResumeBody => _p(
    'Waktu tetap berjalan sejak Anda memulai. Jawaban yang sudah tersimpan tetap aman.',
    'The timer has kept running since you started. Your saved answers are safe.',
    '计时自开始起一直在进行。已保存的答案是安全的。',
  );
  String get blockedTitle =>
      _p('Akses ujian dicekal', 'Exam access blocked', '考试权限被限制');
  String get blockedBody => _p(
    'Anda tidak dapat memulai ujian karena ada cekal akademik aktif.',
    'You cannot start the exam because an academic block is active.',
    '由于存在学业限制，您无法开始考试。',
  );
  String get openClearance =>
      _p('Buka status cekal & keuangan', 'Open clearance & finance', '查看限制与财务');

  String statusLabel(StudentExam e) => switch (e.status) {
    StudentExamStatus.upcoming => _p('Akan datang', 'Upcoming', '即将开始'),
    StudentExamStatus.available => _p('Dibuka', 'Open', '开放中'),
    StudentExamStatus.inProgress => _p(
      'Sedang dikerjakan',
      'In progress',
      '进行中',
    ),
    StudentExamStatus.completed => _p('Selesai', 'Finished', '已完成'),
    StudentExamStatus.expired => _p('Berakhir', 'Expired', '已结束'),
    StudentExamStatus.unknown => e.rawStatus ?? '—',
  };

  StatusTone statusTone(StudentExamStatus s) => switch (s) {
    StudentExamStatus.upcoming => StatusTone.info,
    StudentExamStatus.available => StatusTone.success,
    StudentExamStatus.inProgress => StatusTone.warning,
    StudentExamStatus.completed => StatusTone.primary,
    StudentExamStatus.expired => StatusTone.neutral,
    StudentExamStatus.unknown => StatusTone.neutral,
  };

  String get notYetOpen => _p(
    'Ujian ini baru dapat dikerjakan sesuai jadwal di atas.',
    'This exam can only be taken during the schedule above.',
    '此考试仅可在上述时间内进行。',
  );
  String get expiredMsg => _p(
    'Waktu pengerjaan ujian ini sudah berakhir.',
    'The time to take this exam has ended.',
    '此考试的作答时间已结束。',
  );
  String get completedMsg => _p(
    'Anda sudah menyelesaikan ujian ini.',
    'You have completed this exam.',
    '您已完成此考试。',
  );

  // Taking ----------------------------------------------------------------
  String get taking => _p('Mengerjakan ujian', 'Taking exam', '正在考试');
  String questionOf(int n, int total) =>
      _p('Soal $n dari $total', 'Question $n of $total', '第 $n / $total 题');
  String answeredOf(int a, int total) => _p(
    '$a dari $total soal terjawab',
    '$a of $total answered',
    '已答 $a / $total 题',
  );
  String get questionList => _p('Daftar soal', 'Question list', '题目列表');
  String get saved => _p('Tersimpan', 'Saved', '已保存');
  String get saving => _p('Menyimpan…', 'Saving…', '保存中…');
  String get unsaved =>
      _p('Belum tersimpan — mencoba lagi', 'Not saved — retrying', '未保存，正在重试');
  String get retryNow => _p('Coba sekarang', 'Retry now', '立即重试');
  String get notAnswered => _p('Belum dijawab', 'Not answered', '未作答');
  String get lockedQuestion => _p(
    'Soal ini sudah dilewati dan tidak dapat dibuka lagi.',
    'This question was skipped and can no longer be opened.',
    '此题已跳过，无法再打开。',
  );
  String get noQuestionText => _p('(soal kosong)', '(empty question)', '（空题）');
  String points(String p) => _p('$p poin', '$p pts', '$p 分');
  String get submitExam => _p('Kumpulkan', 'Submit', '交卷');
  String get submitTitle => _p('Kumpulkan ujian?', 'Submit the exam?', '确认交卷？');
  String get submitAllAnswered => _p(
    'Semua soal sudah dijawab. Setelah dikumpulkan, jawaban tidak dapat diubah.',
    'All questions are answered. Answers cannot be changed after submitting.',
    '所有题目均已作答。交卷后无法修改答案。',
  );
  String submitUnanswered(int n) => _p(
    '$n soal belum dijawab. Setelah dikumpulkan, jawaban tidak dapat diubah.',
    '$n question(s) are unanswered. Answers cannot be changed after submitting.',
    '还有 $n 题未作答。交卷后无法修改答案。',
  );
  String get unansweredNumbers =>
      _p('Soal belum dijawab:', 'Unanswered questions:', '未作答题号：');
  String get submitting => _p('Mengumpulkan…', 'Submitting…', '正在交卷…');
  String get submitFailed =>
      _p('Gagal mengumpulkan ujian.', 'Failed to submit the exam.', '交卷失败。');
  String get leaveTitle => _p('Tinggalkan ujian?', 'Leave the exam?', '离开考试？');
  String get leaveBody => _p(
    'Ujian belum selesai. Jawaban yang sudah tersimpan tetap aman, tetapi waktu terus berjalan dan akan dikumpulkan otomatis saat habis.',
    'The exam is not finished. Saved answers stay safe, but the timer keeps running and the exam is submitted automatically when it ends.',
    '考试尚未结束。已保存的答案是安全的，但计时仍在继续，时间到后将自动交卷。',
  );
  String get leave => _p('Keluar', 'Leave', '离开');
  String get timeLow =>
      _p('Waktu ujian hampir habis.', 'Time is almost up.', '考试时间即将结束。');
  String get timeCritical => _p(
    'Waktu hampir habis! Ujian akan dikumpulkan otomatis.',
    'Almost out of time! The exam will be submitted automatically.',
    '时间即将用完！将自动交卷。',
  );
  String get timeUp => _p(
    'Waktu habis. Mengumpulkan ujian…',
    'Time is up. Submitting the exam…',
    '时间到，正在交卷…',
  );
  String get clockNote => _p(
    'Hitung mundur dihitung dari waktu mulai + durasi (disesuaikan dengan jam server). Server tetap menjadi penentu akhir.',
    'The countdown is derived from start time + duration (adjusted to the server clock). The server remains the final authority.',
    '倒计时由开始时间 + 时长推算（已按服务器时钟校正），最终以服务器为准。',
  );
  String get violations => _p('Pelanggaran', 'Violations', '违规');
  String violationWarning(int n) => _p(
    'Pelanggaran terdeteksi. Nilai dikurangi 1 poin. Aktivitas ini tercatat (ke-$n).',
    'Violation detected. 1 point will be deducted. This activity is recorded (#$n).',
    '检测到违规，将扣 1 分。该行为已记录（第 $n 次）。',
  );
  String totalPenalty(String p) => _p(
    'Total pengurangan nilai: $p poin.',
    'Total deduction so far: $p points.',
    '目前累计扣分：$p 分。',
  );
  String get alreadySubmitted => _p(
    'Ujian ini sudah dikumpulkan.',
    'This exam has already been submitted.',
    '此考试已交卷。',
  );
  String get answerSaveFailed =>
      _p('Gagal menyimpan jawaban.', 'Failed to save the answer.', '保存答案失败。');

  // Result ----------------------------------------------------------------
  String get resultTitle => _p('Hasil ujian', 'Exam result', '考试成绩');
  String get finalScore => _p('Nilai akhir', 'Final score', '最终成绩');
  String get grade => _p('Grade', 'Grade', '等级');
  String get rawScore => _p('Nilai awal', 'Raw score', '原始分');
  String get penalty => _p('Pengurangan', 'Penalty', '扣分');
  String get correct => _p('Benar', 'Correct', '正确');
  String get wrong => _p('Salah', 'Wrong', '错误');
  String get timeSpent => _p('Waktu pengerjaan', 'Time spent', '用时');
  String get submittedAt => _p('Dikumpulkan', 'Submitted', '交卷时间');
  String get attemptNo => _p('Percobaan ke', 'Attempt no.', '第几次');
  String get review => _p('Review jawaban', 'Answer review', '答案回顾');
  String get yourAnswer => _p('Jawaban Anda', 'Your answer', '您的答案');
  String get correctAnswer => _p('Jawaban benar', 'Correct answer', '正确答案');
  String get explanation => _p('Pembahasan', 'Explanation', '解析');
  String get downloadPdf => _p('Unduh PDF', 'Download PDF', '下载 PDF');
  String get resultPending =>
      _p('Hasil belum tersedia', 'Result not available yet', '成绩暂不可用');
  String get backToExams =>
      _p('Kembali ke daftar ujian', 'Back to exams', '返回考试列表');
  String get skipped => _p('Tidak dijawab', 'Skipped', '未作答');
  String durationLabel(int seconds) {
    final m = seconds ~/ 60;
    final s = seconds % 60;
    return _p('$m mnt $s dtk', '${m}m ${s}s', '$m 分 $s 秒');
  }
}

extension ExamStudentStringsX on BuildContext {
  ExamStudentStrings get examL10n => ExamStudentStrings(this);
}
