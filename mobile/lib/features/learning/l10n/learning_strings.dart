import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';
import '../domain/learning_models.dart';

class LearningStrings {
  const LearningStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  String get hubTitle => _p('Kuliah', 'Courses', '课程');
  String get shortcutMaterials => _p('Materi', 'Materials', '教材');
  String get shortcutMaterialsSub => _p(
    'Berkas, tautan, dan video dari dosen',
    'Files, links and videos from lecturers',
    '教师提供的文件、链接和视频',
  );
  String get shortcutAssignments => _p('Tugas', 'Assignments', '作业');
  String get shortcutAssignmentsSub => _p(
    'Kumpulkan tugas dan lihat nilai',
    'Submit work and see grades',
    '提交作业并查看成绩',
  );
  String get shortcutExams => _p('Ujian', 'Exams', '考试');
  String get shortcutExamsSub =>
      _p('Jadwal dan hasil ujian', 'Exam schedule and results', '考试安排与结果');
  String get myCourses =>
      _p('Mata kuliah semester ini', 'Courses this term', '本学期课程');
  String get dueSoon =>
      _p('Tugas mendekati tenggat', 'Assignments due soon', '即将截止的作业');
  String get seeAll => _p('Lihat semua', 'See all', '查看全部');
  String get noCourses => _p('Belum ada mata kuliah', 'No courses yet', '暂无课程');
  String get noCoursesBody => _p(
    'Mata kuliah muncul setelah KRS Anda disetujui.',
    'Courses appear once your KRS is approved.',
    'KRS 获批后将显示课程。',
  );
  String get noDueAssignments => _p(
    'Tidak ada tugas yang menunggu dikumpulkan.',
    'No assignments waiting for submission.',
    '没有待提交的作业。',
  );
  String get coursesTitle => _p('Mata kuliah', 'Courses', '课程');
  String get courseDetailTitle => _p('Ruang kuliah', 'Course room', '课堂');
  String get lecturer => _p('Dosen', 'Lecturer', '教师');
  String get schedule => _p('Jadwal', 'Schedule', '课表');
  String get credits => _p('SKS', 'Credits', '学分');
  String get classCode => _p('Kelas', 'Class', '班级');
  String get term => _p('Semester', 'Term', '学期');
  String get attendance => _p('Kehadiran', 'Attendance', '出勤');
  String attendanceSummary(int present, int total) => _p(
    'Hadir $present dari $total pertemuan',
    'Present $present of $total meetings',
    '出席 $present/$total 次',
  );
  String materialsCount(int n) => _p('$n materi', '$n materials', '$n 份教材');
  String assignmentsCount(int n) => _p('$n tugas', '$n assignments', '$n 项作业');
  String pendingCount(int n) =>
      _p('$n belum dikumpulkan', '$n pending', '$n 项待交');
  String get materialsSection =>
      _p('Materi per pertemuan', 'Materials by meeting', '按课次的教材');
  String get assignmentsSection => _p('Tugas', 'Assignments', '作业');
  String get examsSection => _p('Ujian terjadwal', 'Scheduled exams', '已安排的考试');
  String get noMaterials => _p('Belum ada materi', 'No materials yet', '暂无教材');
  String get noMaterialsBody => _p(
    'Dosen belum mempublikasikan materi.',
    'Lecturers have not published materials yet.',
    '教师尚未发布教材。',
  );
  String get materialsTitle => _p('Materi kuliah', 'Course materials', '课程教材');
  String get allCourses => _p('Semua mata kuliah', 'All courses', '全部课程');
  String get invalidLink => _p('Tautan tidak valid.', 'Invalid link.', '链接无效。');
  String get cannotOpenLink =>
      _p('Tidak dapat membuka tautan.', 'Could not open the link.', '无法打开链接。');
  String get noContent => _p('Tidak ada konten.', 'No content.', '没有内容。');
  String get generalMaterial => _p('Materi umum', 'General materials', '通用教材');
  String materialTypeLabel(LmsMaterialType t) => switch (t) {
    LmsMaterialType.file => _p('Berkas', 'File', '文件'),
    LmsMaterialType.link => _p('Tautan', 'Link', '链接'),
    LmsMaterialType.video => _p('Video', 'Video', '视频'),
    LmsMaterialType.text => _p('Teks', 'Text', '文本'),
  };

  String get assignmentsTitle => _p('Tugas', 'Assignments', '作业');
  String get assignmentTitle => _p('Detail tugas', 'Assignment', '作业详情');
  String get noAssignments => _p('Tidak ada tugas', 'No assignments', '暂无作业');
  String get noAssignmentsBody => _p(
    'Tidak ada tugas untuk filter ini.',
    'No assignments match this filter.',
    '没有符合筛选条件的作业。',
  );
  String statusLabel(AssignmentItem a) => switch (a.status) {
    AssignmentStatus.notSubmitted => _p(
      'Belum dikerjakan',
      'Not submitted',
      '未提交',
    ),
    AssignmentStatus.submitted => _p('Sudah dikumpulkan', 'Submitted', '已提交'),
    AssignmentStatus.late => _p('Terlambat', 'Late', '迟交'),
    AssignmentStatus.graded => _p('Dinilai', 'Graded', '已评分'),
    AssignmentStatus.missed => _p('Tidak dikumpulkan', 'Missed', '未交'),
    null => a.statusLabel,
  };
  String filterLabel(String key) => switch (key) {
    'not_submitted' => _p('Belum dikerjakan', 'Not submitted', '未提交'),
    'submitted' => _p('Dikumpulkan', 'Submitted', '已提交'),
    'late' => _p('Terlambat', 'Late', '迟交'),
    'graded' => _p('Dinilai', 'Graded', '已评分'),
    'missed' => _p('Tidak dikumpulkan', 'Missed', '未交'),
    _ => _p('Semua', 'All', '全部'),
  };
  String get due => _p('Tenggat', 'Due', '截止');
  String get instructions => _p('Instruksi', 'Instructions', '说明');
  String get noInstructions =>
      _p('Tidak ada instruksi.', 'No instructions.', '没有说明。');
  String get attachment => _p('Lampiran dosen', 'Lecturer attachment', '教师附件');
  String get rules => _p('Aturan pengumpulan', 'Submission rules', '提交规则');
  String get allowedFiles => _p('Format berkas', 'File types', '文件格式');
  String get maxFileSize => _p('Ukuran maks.', 'Max size', '最大大小');
  String get maxScore => _p('Nilai maksimum', 'Max score', '满分');
  String get lateAllowed =>
      _p('Terlambat diizinkan', 'Late submission allowed', '允许迟交');
  String get lateNotAllowed =>
      _p('Terlambat tidak diizinkan', 'No late submission', '不允许迟交');
  String get resubmitAllowed =>
      _p('Boleh kumpul ulang', 'Resubmission allowed', '允许重新提交');
  String get resubmitNotAllowed =>
      _p('Tidak boleh kumpul ulang', 'No resubmission', '不允许重新提交');
  String get mySubmission => _p('Pengumpulan saya', 'My submission', '我的提交');
  String get submittedAt => _p('Dikumpulkan', 'Submitted', '提交时间');
  String get submissionCount => _p('Jumlah pengiriman', 'Submissions', '提交次数');
  String get notes => _p('Catatan / jawaban', 'Notes / answer', '备注/答案');
  String get file => _p('Berkas', 'File', '文件');
  String get lateBadge => _p('Terlambat', 'Late', '迟交');
  String get grade => _p('Nilai & umpan balik', 'Grade & feedback', '成绩与反馈');
  String get score => _p('Nilai', 'Score', '分数');
  String get feedback => _p('Umpan balik dosen', 'Lecturer feedback', '教师反馈');
  String get gradedAt => _p('Dinilai pada', 'Graded at', '评分时间');
  String get gradedBy => _p('Dinilai oleh', 'Graded by', '评分人');
  String get notGradedYet => _p('Belum dinilai.', 'Not graded yet.', '尚未评分。');
  String get submitSection => _p('Kumpulkan tugas', 'Submit work', '提交作业');
  String get resubmitSection => _p('Kumpulkan ulang', 'Resubmit', '重新提交');
  String get chooseFileBtn => _p('Pilih berkas', 'Choose file', '选择文件');
  String get replaceFileBtn => _p('Ganti berkas', 'Replace file', '更换文件');
  String get removeFile => _p('Hapus berkas', 'Remove file', '移除文件');
  String get notesHint => _p(
    'Tulis jawaban atau catatan (opsional jika ada berkas)',
    'Write an answer or note (optional with a file)',
    '填写答案或备注（有文件时可选）',
  );
  String get submitBtn => _p('Kirim tugas', 'Submit assignment', '提交作业');
  String get resubmitBtn => _p('Kirim ulang', 'Resubmit', '重新提交');
  String get needFileOrNotes => _p(
    'Unggah berkas tugas atau tuliskan jawaban.',
    'Upload a file or write an answer.',
    '请上传文件或填写答案。',
  );
  String get submittedMsg =>
      _p('Tugas berhasil dikumpulkan.', 'Assignment submitted.', '作业已提交。');
  String get lateWarning => _p(
    'Tenggat sudah lewat. Pengumpulan akan ditandai terlambat.',
    'The deadline has passed. Your submission will be marked late.',
    '已超过截止时间，提交将被标记为迟交。',
  );
  String get confirmLateTitle =>
      _p('Kumpulkan terlambat?', 'Submit late?', '确认迟交？');
  String get confirmResubmitTitle =>
      _p('Kirim ulang tugas?', 'Resubmit?', '确认重新提交？');
  String get confirmResubmitBody => _p(
    'Pengumpulan sebelumnya akan diganti.',
    'Your previous submission will be replaced.',
    '之前的提交将被替换。',
  );
  String cannotSubmitReason(AssignmentItem a) {
    if (a.isGraded) {
      return _p(
        'Tugas sudah dinilai sehingga tidak dapat dikumpulkan ulang.',
        'Graded work cannot be resubmitted.',
        '已评分的作业无法重新提交。',
      );
    }
    if (a.isPastDue && !a.allowLate) {
      return _p(
        'Batas waktu pengumpulan tugas sudah lewat.',
        'The submission deadline has passed.',
        '提交截止时间已过。',
      );
    }
    if (a.hasSubmission && !a.allowResubmission) {
      return _p(
        'Tugas ini tidak mengizinkan pengumpulan ulang.',
        'This assignment does not allow resubmission.',
        '此作业不允许重新提交。',
      );
    }
    return _p(
      'Tugas tidak dapat dikumpulkan saat ini.',
      'This assignment cannot be submitted right now.',
      '当前无法提交此作业。',
    );
  }
}

extension LearningStringsX on BuildContext {
  LearningStrings get learningL10n => LearningStrings(this);
}
