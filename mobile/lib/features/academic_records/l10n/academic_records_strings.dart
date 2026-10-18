import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class AcademicRecordsStrings {
  const AcademicRecordsStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // Hub
  String get hubTitle => _p('Akademik', 'Academic', '学业');
  String get summaryTitle => _p('Ringkasan akademik', 'Academic summary', '学业概览');
  String get menuTitle => _p('Layanan akademik', 'Academic services', '学业服务');
  String get pendingSection =>
      _p('Menunggu backend', 'Awaiting backend', '等待后端');
  String get krs => _p('KRS', 'Course enrolment (KRS)', '选课 (KRS)');
  String get krsSub => _p(
    'Rencana studi semester berjalan',
    'Study plan for the current term',
    '本学期学习计划',
  );
  String get schedule => _p('Jadwal kuliah', 'Class schedule', '课程表');
  String get scheduleSub => _p(
    'Jadwal mingguan dan kelas berikutnya',
    'Weekly timetable and next class',
    '每周课表与下节课',
  );
  String get calendar => _p('Kalender akademik', 'Academic calendar', '学术日历');
  String get calendarSub => _p(
    'Perkuliahan, ujian, dan tenggat tugas',
    'Classes, exams and deadlines',
    '课程、考试与截止日期',
  );
  String get attendance => _p('Presensi', 'Attendance', '考勤');
  String get attendanceSub => _p(
    'Kehadiran per mata kuliah',
    'Attendance per course',
    '各课程出勤',
  );
  String get grades => _p('Nilai', 'Grades', '成绩');
  String get gradesSub =>
      _p('Nilai per semester', 'Grades per semester', '按学期成绩');
  String get khs => _p('KHS', 'Grade report (KHS)', '成绩单 (KHS)');
  String get khsSub => _p(
    'Kartu hasil studi dan IPS',
    'Semester report and GPA',
    '学期成绩与学期绩点',
  );
  String get transcript => _p('Transkrip', 'Transcript', '成绩总表');
  String get transcriptSub => _p(
    'Seluruh nilai dan IPK',
    'All grades and cumulative GPA',
    '全部成绩与累计绩点',
  );
  String get documents => _p('Dokumen', 'Documents', '文件');
  String get documentsSub => _p(
    'Unduh KRS, KHS, dan transkrip (PDF)',
    'Download KRS, KHS and transcript (PDF)',
    '下载 KRS、KHS 与成绩总表 (PDF)',
  );
  String get history => _p('Riwayat akademik', 'Academic history', '学业历程');
  String get historySub => _p(
    'Perjalanan studi dan perubahan status',
    'Study journey and status changes',
    '学习历程与状态变更',
  );

  // Summary labels
  String get ipk => _p('IPK', 'Cumulative GPA', '累计绩点');
  String get ips => _p('IPS', 'Semester GPA', '学期绩点');
  String get sks => _p('SKS', 'Credits', '学分');
  String get totalCredits => _p('Total SKS', 'Total credits', '总学分');
  String get passedCredits => _p('SKS lulus', 'Passed credits', '已通过学分');
  String get remainingCredits =>
      _p('Sisa SKS', 'Remaining credits', '剩余学分');
  String get currentTermCredits =>
      _p('SKS semester ini', 'Credits this term', '本学期学分');
  String get maxCredits => _p('Batas SKS', 'Credit limit', '学分上限');
  String get semester => _p('Semester', 'Semester', '学期');
  String get studyProgram => _p('Program studi', 'Study program', '专业');
  String get faculty => _p('Fakultas', 'Faculty', '学院');
  String get admissionYear => _p('Angkatan', 'Admission year', '入学年份');
  String get status => _p('Status', 'Status', '状态');
  String get advisor => _p('Dosen PA', 'Academic advisor', '学业导师');
  String get curriculum => _p('Kurikulum', 'Curriculum', '课程方案');
  String get currentTerm => _p('Semester berjalan', 'Current term', '当前学期');
  String get lastIps => _p('IPS terakhir', 'Last semester GPA', '上学期绩点');
  String get enrolledAt => _p('Tanggal masuk', 'Enrolled on', '入学日期');
  String get graduatedAt => _p('Tanggal lulus', 'Graduated on', '毕业日期');
  String semesterN(int n) => _p('Semester $n', 'Semester $n', '第 $n 学期');
  String get seeProfile => _p('Lihat profil', 'View profile', '查看资料');

  // Grades
  String get gradesTitle => _p('Nilai', 'Grades', '成绩');
  String get gradesEmpty => _p(
    'Belum ada nilai yang tercatat.',
    'No grades recorded yet.',
    '暂无成绩记录。',
  );
  String get notGraded => _p('Belum dinilai', 'Not graded', '未评分');
  String get score => _p('Nilai angka', 'Score', '分数');
  String get letter => _p('Huruf', 'Grade', '等级');
  String get weight => _p('Bobot', 'Weight', '绩点权重');
  String get qualityPoints => _p('Mutu', 'Quality pts', '质量分');
  String get passing => _p('Lulus', 'Passed', '通过');
  String get failing => _p('Tidak lulus', 'Not passed', '未通过');
  String creditsN(Object n) => _p('$n SKS', '$n credits', '$n 学分');
  String get gradedCredits => _p('SKS dinilai', 'Graded credits', '已评分学分');
  String get totalSemesters => _p('Semester', 'Semesters', '学期数');

  // KHS
  String get khsTitle => _p('Kartu Hasil Studi', 'Semester grade report', '学期成绩单');
  String get chooseTerm => _p('Pilih semester', 'Choose term', '选择学期');
  String get khsEmpty => _p(
    'Belum ada KRS yang disetujui sehingga KHS belum tersedia.',
    'No approved enrolment yet, so no grade report is available.',
    '暂无已批准的选课，因此没有成绩单。',
  );
  String get termCredits => _p('SKS semester', 'Term credits', '学期学分');
  String get takenCredits => _p('SKS diambil', 'Credits taken', '已修学分');
  String get cumulativeCredits =>
      _p('SKS kumulatif', 'Cumulative credits', '累计学分');
  String get notAllGraded => _p(
    'Sebagian nilai semester ini belum keluar, IPS dapat berubah.',
    'Some grades for this term are still pending; GPA may change.',
    '本学期部分成绩尚未公布，绩点可能变化。',
  );
  String get downloadPdf => _p('Unduh PDF', 'Download PDF', '下载 PDF');

  // Transcript
  String get transcriptTitle => _p('Transkrip nilai', 'Transcript', '成绩总表');
  String get transcriptEmpty => _p(
    'Transkrip belum tersedia karena belum ada nilai.',
    'Transcript unavailable: no grades yet.',
    '尚无成绩，成绩总表不可用。',
  );
  String get courseCount => _p('Mata kuliah', 'Courses', '课程数');
  String get ipPerTerm => _p('Indeks per semester', 'GPA per term', '各学期绩点');
  String get courses => _p('Daftar mata kuliah', 'Courses', '课程列表');
  String get studentData => _p('Data mahasiswa', 'Student details', '学生信息');
  String get nim => _p('NIM', 'Student ID', '学号');
  String get name => _p('Nama', 'Name', '姓名');
  String attemptsN(int n) =>
      _p('Diulang, $n kali ambil', 'Repeated, taken $n times', '重修，共修 $n 次');
  String get bestAttemptNote => _p(
    'Untuk mata kuliah yang diulang, yang tampil adalah nilai terbaik.',
    'For repeated courses the best attempt is shown.',
    '重修课程显示最佳成绩。',
  );

  // Documents
  String get documentsTitle => _p('Dokumen akademik', 'Academic documents', '学业文件');
  String get documentsEmpty => _p(
    'Belum ada dokumen yang dapat diunduh.',
    'No documents available yet.',
    '暂无可下载的文件。',
  );
  String get typeKrs => _p('KRS', 'KRS', 'KRS');
  String get typeKhs => _p('KHS', 'KHS', 'KHS');
  String get typeTranscript => _p('Transkrip', 'Transcript', '成绩总表');
  String get typeLetter => _p('Surat', 'Letter', '证明信');

  // History
  String get historyTitle => _p('Riwayat akademik', 'Academic history', '学业历程');
  String get historyEmpty => _p(
    'Belum ada riwayat akademik.',
    'No academic history yet.',
    '暂无学业历程。',
  );
  String get typeAdmission => _p('Penerimaan', 'Admission', '入学');
  String get typeTerm => _p('Semester', 'Term', '学期');
  String get typeStatus => _p('Status', 'Status', '状态');
  String get typeGraduation => _p('Kelulusan', 'Graduation', '毕业');

  // Profile
  String get profileTitle => _p('Profil mahasiswa', 'Student profile', '学生资料');
  String get personal => _p('Data pribadi', 'Personal details', '个人信息');
  String get contact => _p('Kontak', 'Contact', '联系方式');
  String get academicData => _p('Data akademik', 'Academic data', '学业信息');
  String get birthPlace => _p('Tempat lahir', 'Place of birth', '出生地');
  String get birthDate => _p('Tanggal lahir', 'Date of birth', '出生日期');
  String get gender => _p('Jenis kelamin', 'Gender', '性别');
  String get email => _p('Email', 'Email', '电子邮件');
  String get phone => _p('Nomor telepon', 'Phone number', '电话号码');
  String get address => _p('Alamat', 'Address', '地址');
  String get editContact => _p('Ubah kontak', 'Edit contact', '编辑联系方式');
  String get readOnlyNote => _p(
    'Nama, email, tempat/tanggal lahir, dan jenis kelamin tidak dapat diubah di sini. Ajukan perubahan data melalui layanan pengajuan.',
    'Name, email, birth details and gender cannot be changed here. Submit a data change request instead.',
    '姓名、邮箱、出生信息和性别无法在此修改，请通过申请服务提交数据变更。',
  );
  String get pendingChange => _p(
    'Ada pengajuan perubahan data yang sedang diproses.',
    'A data change request is being processed.',
    '有一项数据变更申请正在处理中。',
  );
  String get phoneInvalid => _p(
    'Nomor telepon tidak valid',
    'Invalid phone number',
    '电话号码无效',
  );
  String get addressTooLong =>
      _p('Maksimal 500 karakter', 'Maximum 500 characters', '最多 500 个字符');
  String get profileSaved =>
      _p('Profil berhasil diperbarui.', 'Profile updated.', '资料已更新。');
  String get requestsLink =>
      _p('Buka layanan pengajuan', 'Open requests', '打开申请服务');
  String get notSet => _p('Belum diisi', 'Not set', '未填写');
  String get male => _p('Laki-laki', 'Male', '男');
  String get female => _p('Perempuan', 'Female', '女');

  // Student status
  String statusLabel(String value, String fallback) => switch (value) {
    'active' => _p('Aktif', 'Active', '在读'),
    'leave' => _p('Cuti', 'On leave', '休学'),
    'graduated' => _p('Lulus', 'Graduated', '已毕业'),
    'inactive' => _p('Nonaktif', 'Inactive', '未激活'),
    'dropped_out' => _p('Drop Out', 'Dropped out', '退学'),
    'resigned' => _p('Mengundurkan diri', 'Resigned', '自愿退学'),
    _ => fallback,
  };
}

extension AcademicRecordsStringsX on BuildContext {
  AcademicRecordsStrings get acadL10n => AcademicRecordsStrings(this);
}
