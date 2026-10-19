import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

extension TeachingStringsX on BuildContext {
  TeachingStrings get tr => TeachingStrings(this);
}

class TeachingStrings {
  const TeachingStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // Home
  String get homeTitle => _p('Mengajar', 'Teaching', '教学');
  String get myClasses => _p('Kelas saya', 'My classes', '我的班级');
  String get noClassesTitle => _p('Belum ada kelas', 'No classes yet', '暂无班级');
  String get noClassesBody => _p(
    'Tidak ada kelas yang Anda ampu pada semester berjalan. Jika akun Anda belum tertaut ke data dosen, hubungi Bagian SDM.',
    'You are not teaching any class this term. If your account is not linked to a lecturer record, contact HR.',
    '本学期您没有授课班级。如果您的账户尚未关联教师资料，请联系人事部门。',
  );
  String get examsTile => _p('Ujian', 'Exams', '考试');
  String get examsSubtitle =>
      _p('Kelola ujian CBT kelas Anda', 'Manage your CBT exams', '管理您的机考');
  String get questionBankTile => _p('Bank soal', 'Question bank', '题库');
  String get questionBankSubtitle =>
      _p('Kumpulan soal per mata kuliah', 'Questions per course', '按课程整理的试题');
  String get moreFeatures => _p('Segera hadir', 'Coming soon', '即将推出');
  String students(int n) => _p('$n mahasiswa', '$n students', '$n 名学生');
  String get noScheduleYet =>
      _p('Jadwal belum diatur', 'Schedule not set', '尚未安排时间');
  String get termLabel => _p('Semester', 'Term', '学期');
  String get studyProgram => _p('Program studi', 'Study program', '专业');
  String get capacity => _p('Kapasitas', 'Capacity', '容量');
  String get enrolled => _p('Terdaftar', 'Enrolled', '已注册');
  String get lecturer => _p('Dosen pengampu', 'Lecturer', '授课教师');
  String get schedule => _p('Jadwal', 'Schedule', '课表');
  String materialsCount(int n) => _p('$n materi', '$n materials', '$n 份资料');
  String assignmentsCount(int n) => _p('$n tugas', '$n assignments', '$n 项作业');

  // Hub
  String get classTitle => _p('Detail kelas', 'Class detail', '班级详情');
  String get classManagement => _p('Kelola kelas', 'Manage class', '班级管理');
  String get participants => _p('Peserta', 'Participants', '学生名单');
  String get participantsSubtitle =>
      _p('Daftar mahasiswa terdaftar', 'Enrolled students', '已注册的学生');
  String get materials => _p('Materi', 'Materials', '教学资料');
  String get materialsSubtitle => _p(
    'Berkas, tautan, video, dan teks per pertemuan',
    'Files, links, videos and text per meeting',
    '按课次的文件、链接、视频和文本',
  );
  String get assignments => _p('Tugas', 'Assignments', '作业');
  String get assignmentsSubtitle => _p(
    'Buat tugas dan nilai pengumpulan',
    'Create assignments and grade submissions',
    '布置作业并批改提交',
  );
  String get attendance => _p('Presensi', 'Attendance', '考勤');
  String get attendanceSubtitle => _p(
    'Rekam kehadiran per pertemuan',
    'Record attendance per meeting',
    '按课次记录出勤',
  );
  String get grades => _p('Nilai', 'Grades', '成绩');
  String get gradesSubtitle =>
      _p('Input nilai akhir mahasiswa', 'Enter final grades', '录入期末成绩');

  // Roster
  String get rosterTitle => _p('Peserta kelas', 'Class roster', '班级名单');
  String get searchStudents =>
      _p('Cari nama atau NIM', 'Search name or student ID', '搜索姓名或学号');
  String get noStudentsTitle =>
      _p('Belum ada peserta', 'No participants', '暂无学生');
  String get noStudentsBody => _p(
    'Belum ada mahasiswa dengan KRS disetujui pada kelas ini.',
    'No students with an approved KRS in this class yet.',
    '该班级暂无已批准选课的学生。',
  );
  String get noMatches => _p('Tidak ada hasil', 'No results', '没有结果');
  String studentCount(int n) =>
      _p('$n mahasiswa terdaftar', '$n students enrolled', '共 $n 名学生');

  // Materials
  String get materialsTitle => _p('Materi kelas', 'Class materials', '班级资料');
  String get noMaterialsTitle =>
      _p('Belum ada materi', 'No materials yet', '暂无资料');
  String get noMaterialsBody => _p(
    'Tambahkan materi agar mahasiswa dapat mengaksesnya.',
    'Add materials so students can access them.',
    '添加资料，学生即可查看。',
  );
  String get addMaterial => _p('Tambah materi', 'Add material', '添加资料');
  String get editMaterial => _p('Ubah materi', 'Edit material', '编辑资料');
  String get materialSaved => _p('Materi disimpan', 'Material saved', '资料已保存');
  String get deleteMaterialTitle =>
      _p('Hapus materi?', 'Delete material?', '删除资料？');
  String get deleteMaterialBody => _p(
    'Materi akan hilang dari daftar mahasiswa dan tidak dapat dikembalikan.',
    'The material will disappear for students and cannot be restored.',
    '资料将从学生端消失且无法恢复。',
  );
  String get materialDeleted =>
      _p('Materi dihapus', 'Material deleted', '资料已删除');
  String meeting(int n) => _p('Pertemuan $n', 'Meeting $n', '第 $n 次课');
  String get typeFile => _p('Berkas', 'File', '文件');
  String get typeLink => _p('Tautan', 'Link', '链接');
  String get typeVideo => _p('Video', 'Video', '视频');
  String get typeText => _p('Teks', 'Text', '文本');
  String typeLabel(String type, String? serverLabel) => switch (type) {
    'file' => typeFile,
    'link' => typeLink,
    'video' => typeVideo,
    'text' => typeText,
    _ => serverLabel ?? type,
  };
  String get published => _p('Terbit', 'Published', '已发布');
  String get draft => _p('Draf', 'Draft', '草稿');
  String get publish => _p('Terbitkan', 'Publish', '发布');
  String get unpublish => _p('Tarik dari mahasiswa', 'Unpublish', '撤回');
  String get publishedNow => _p('Dipublikasikan', 'Published', '已发布');
  String get unpublishedNow =>
      _p('Ditarik dari mahasiswa', 'Hidden from students', '已对学生隐藏');
  String get fieldTitle => _p('Judul', 'Title', '标题');
  String get fieldType => _p('Jenis materi', 'Material type', '资料类型');
  String get fieldMeeting =>
      _p('Nomor pertemuan (1–32)', 'Meeting number (1–32)', '课次编号（1–32）');
  String get fieldDescription => _p('Deskripsi', 'Description', '说明');
  String get fieldText => _p('Isi teks materi', 'Material text', '资料文本');
  String get fieldUrl =>
      _p('Tautan (http/https)', 'Link (http/https)', '链接（http/https）');
  String get fieldFile => _p('Berkas', 'File', '文件');
  String get pickFile => _p('Pilih berkas', 'Choose file', '选择文件');
  String get replaceFile => _p('Ganti berkas', 'Replace file', '更换文件');
  String get fileRequired =>
      _p('Unggah berkas materi', 'Upload a file for this material', '请上传资料文件');
  String get invalidUrl => _p(
    'Tautan harus diawali http:// atau https://',
    'The link must start with http:// or https://',
    '链接必须以 http:// 或 https:// 开头',
  );
  String get fieldPublished =>
      _p('Terbitkan ke mahasiswa', 'Publish to students', '向学生发布');
  String get uploading =>
      _p('Mengunggah berkas…', 'Uploading file…', '正在上传文件…');
  String get openLink => _p('Buka tautan', 'Open link', '打开链接');
  String get openFile => _p('Buka berkas', 'Open file', '打开文件');
  String get cannotOpenLink =>
      _p('Tautan tidak dapat dibuka', 'Could not open the link', '无法打开链接');

  // Assignments
  String get assignmentsTitle => _p('Tugas kelas', 'Class assignments', '班级作业');
  String get noAssignmentsTitle =>
      _p('Belum ada tugas', 'No assignments yet', '暂无作业');
  String get noAssignmentsBody => _p(
    'Buat tugas untuk mahasiswa kelas ini.',
    'Create an assignment for this class.',
    '为本班学生布置作业。',
  );
  String get addAssignment => _p('Buat tugas', 'New assignment', '新建作业');
  String get editAssignment => _p('Ubah tugas', 'Edit assignment', '编辑作业');
  String get assignmentSaved =>
      _p('Tugas disimpan', 'Assignment saved', '作业已保存');
  String get deleteAssignmentTitle =>
      _p('Hapus tugas?', 'Delete assignment?', '删除作业？');
  String get deleteAssignmentBody => _p(
    'Tugas yang sudah memiliki pengumpulan tidak dapat dihapus; tarik publikasinya sebagai gantinya.',
    'Assignments that already have submissions cannot be deleted; unpublish them instead.',
    '已有提交的作业无法删除，请改为撤回发布。',
  );
  String get assignmentDeleted =>
      _p('Tugas dihapus', 'Assignment deleted', '作业已删除');
  String dueAt(String when) => _p('Tenggat $when', 'Due $when', '截止 $when');
  String submittedOf(int submitted, int graded) => _p(
    '$submitted terkumpul · $graded dinilai',
    '$submitted submitted · $graded graded',
    '已提交 $submitted · 已批改 $graded',
  );
  String maxScore(String v) => _p('Skor maks. $v', 'Max score $v', '满分 $v');
  String get fieldDueDate => _p('Tenggat pengumpulan', 'Due date', '截止时间');
  String get pickDueDate =>
      _p('Pilih tanggal & jam', 'Pick date & time', '选择日期和时间');
  String get dueRequired =>
      _p('Tenggat wajib diisi', 'The due date is required', '截止时间必填');
  String get allowLate =>
      _p('Izinkan terlambat', 'Allow late submission', '允许迟交');
  String get allowResubmit =>
      _p('Izinkan kumpul ulang', 'Allow resubmission', '允许重新提交');
  String get fieldMaxFileSize => _p(
    'Ukuran berkas maks. (MB, 1–50)',
    'Max file size (MB, 1–50)',
    '文件大小上限（MB，1–50）',
  );
  String get fieldMaxScore =>
      _p('Skor maksimal (1–1000)', 'Max score (1–1000)', '满分（1–1000）');
  String get allowedExtensions =>
      _p('Format berkas yang diizinkan', 'Allowed file formats', '允许的文件格式');
  String get attachment => _p('Lampiran tugas', 'Attachment', '作业附件');
  String get removeAttachment =>
      _p('Hapus lampiran', 'Remove attachment', '移除附件');
  String get noAttachment => _p('Tanpa lampiran', 'No attachment', '无附件');
  String get late => _p('Terlambat', 'Late', '迟交');

  // Submissions
  String get submissionsTitle => _p('Pengumpulan tugas', 'Submissions', '作业提交');
  String statusLabel(String status) => switch (status) {
    'not_submitted' => _p('Belum mengumpulkan', 'Not submitted', '未提交'),
    'submitted' => _p('Terkumpul', 'Submitted', '已提交'),
    'late' => _p('Terlambat', 'Late', '迟交'),
    'graded' => _p('Dinilai', 'Graded', '已批改'),
    'missed' => _p('Tidak mengumpulkan', 'Missed', '缺交'),
    _ => status,
  };
  String get submittedAt => _p('Dikumpulkan', 'Submitted at', '提交时间');
  String get submissionCount =>
      _p('Jumlah pengumpulan', 'Submission count', '提交次数');
  String get studentNotes => _p('Catatan mahasiswa', 'Student notes', '学生备注');
  String get submittedFile =>
      _p('Berkas pengumpulan', 'Submitted file', '提交文件');
  String get noFile => _p('Tidak ada berkas', 'No file', '无文件');
  String get score => _p('Nilai', 'Score', '分数');
  String get feedback => _p('Umpan balik', 'Feedback', '反馈');
  String get gradeSubmission =>
      _p('Nilai pengumpulan', 'Grade submission', '批改提交');
  String get gradeSaved => _p('Nilai tugas disimpan', 'Grade saved', '成绩已保存');
  String fieldScore(String max) =>
      _p('Nilai (0–$max)', 'Score (0–$max)', '分数（0–$max）');
  String get gradedBy => _p('Dinilai oleh', 'Graded by', '批改人');
  String get notSubmittedYet => _p(
    'Mahasiswa belum mengumpulkan tugas ini.',
    'This student has not submitted yet.',
    '该学生尚未提交。',
  );
  String get downloadFile => _p('Unduh berkas', 'Download file', '下载文件');

  // Schedule
  String get scheduleTitle =>
      _p('Jadwal mengajar', 'Teaching schedule', '教学课表');
  String get academicCalendar =>
      _p('Kalender akademik', 'Academic calendar', '校历');
  String get academicCalendarSubtitle => _p(
    'Kegiatan akademik per bulan',
    'Academic events per month',
    '按月查看学术活动',
  );
  String get noSlotsTitle => _p('Tidak ada jadwal', 'No sessions', '没有课程');
  String noSlotsBody(String day) => _p(
    'Tidak ada kelas pada hari $day.',
    'No classes on $day.',
    '$day 没有课程。',
  );
  String get todayBadge => _p('Hari ini', 'Today', '今天');
  String get room => _p('Ruang', 'Room', '教室');
  String get weeklyNote => _p(
    'Jadwal berulang setiap minggu. Libur dan perubahan ruang belum tercatat; lihat kalender akademik.',
    'Recurring weekly. Holidays and room changes are not recorded; see the academic calendar.',
    '每周重复。假期与教室变更未记录，请查看校历。',
  );

  // Calendar
  String get noEventsTitle => _p('Tidak ada kegiatan', 'No events', '没有活动');
  String get noEventsBody => _p(
    'Tidak ada kegiatan akademik pada bulan ini.',
    'No academic events this month.',
    '本月没有学术活动。',
  );
  String get previousMonth => _p('Bulan sebelumnya', 'Previous month', '上个月');
  String get nextMonth => _p('Bulan berikutnya', 'Next month', '下个月');

  // Student record
  String get studentTitle => _p('Data mahasiswa', 'Student record', '学生档案');
  String get nim => _p('NIM', 'Student ID', '学号');
  String get email => _p('Email', 'Email', '邮箱');
  String get admissionYear => _p('Angkatan', 'Admission year', '入学年份');
  String get status => _p('Status', 'Status', '状态');
  String get transcript =>
      _p('Transkrip ringkas', 'Transcript summary', '成绩单摘要');
  String get gpa => _p('IPK', 'GPA', '累计绩点');
  String get totalSks => _p('Total SKS', 'Total credits', '总学分');
  String get passedSks => _p('SKS lulus', 'Credits passed', '已修学分');
  String get ip => _p('IP', 'GPA', '学期绩点');
  String get termsTitle => _p('Per semester', 'Per term', '各学期');
  String get noTranscript =>
      _p('Belum ada nilai tercatat.', 'No grades recorded yet.', '暂无成绩记录。');
  String studentStatus(String s) => switch (s) {
    'active' => _p('Aktif', 'Active', '在读'),
    'on_leave' => _p('Cuti', 'On leave', '休学'),
    'graduated' => _p('Lulus', 'Graduated', '已毕业'),
    'dropped_out' => _p('Keluar', 'Dropped out', '退学'),
    'inactive' => _p('Tidak aktif', 'Inactive', '未在读'),
    _ => s,
  };
}
