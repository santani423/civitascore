import 'package:flutter/material.dart';

import '../l10n/app_language.dart';

enum PendingAudience { student, lecturer, advisor }

/// A documented student/lecturer feature whose backend does not exist yet
/// (matrix scope "build_contract_pending_backend", see
/// `docs/mobile/02-matriks-fitur.md`). Shown as an explicitly labelled entry —
/// never with sample data.
class PendingFeature {
  const PendingFeature({
    required this.key,
    required this.icon,
    required this.audience,
    required this.titleId,
    required this.titleEn,
    required this.titleZh,
    required this.descId,
    required this.descEn,
    required this.descZh,
    required this.endpoints,
    required this.sources,
    required this.matrixIds,
  });

  final String key;
  final IconData icon;
  final Set<PendingAudience> audience;
  final String titleId, titleEn, titleZh;
  final String descId, descEn, descZh;
  final List<String> endpoints;
  final List<String> sources;
  final List<String> matrixIds;

  String title(BuildContext c) =>
      pick(c, id: titleId, en: titleEn, zh: titleZh);
  String description(BuildContext c) =>
      pick(c, id: descId, en: descEn, zh: descZh);
}

const _s = {PendingAudience.student};
const _l = {PendingAudience.lecturer};
const _a = {PendingAudience.advisor};

const kPendingFeatures = <PendingFeature>[
  PendingFeature(
    key: 'edom',
    icon: Icons.rate_review_outlined,
    audience: _s,
    titleId: 'Evaluasi Dosen (EDOM)',
    titleEn: 'Lecturer evaluation (EDOM)',
    titleZh: '教师评价（EDOM）',
    descId: 'Isi kuesioner evaluasi dosen secara anonim per kelas.',
    descEn: 'Fill in anonymous lecturer evaluations per class.',
    descZh: '按班级匿名填写教师评价问卷。',
    endpoints: [
      'GET /api/v1/student/edom',
      'GET /api/v1/student/edom/targets/{targetId}',
      'POST /api/v1/student/edom/targets/{targetId}/submit',
    ],
    sources: [
      'docs/academic-learning/05-edom.md',
      'docs/academic-learning/08-api-specification.md',
    ],
    matrixIds: ['MOB-EDOM-01', 'MOB-EDOM-02', 'MOB-EDOM-03', 'MOB-EDOM-05'],
  ),
  PendingFeature(
    key: 'edom_reports',
    icon: Icons.insights_outlined,
    audience: _l,
    titleId: 'Hasil EDOM saya',
    titleEn: 'My EDOM results',
    titleZh: '我的教学评价结果',
    descId: 'Rekap evaluasi mahasiswa untuk kelas yang Anda ampu.',
    descEn: 'Student evaluation summary for your classes.',
    descZh: '您所授课程的学生评价汇总。',
    endpoints: ['GET /api/v1/teaching/edom/reports'],
    sources: ['docs/academic-learning/05-edom.md'],
    matrixIds: ['MOB-EDOM-06'],
  ),
  PendingFeature(
    key: 'short_semester',
    icon: Icons.wb_sunny_outlined,
    audience: _s,
    titleId: 'Semester Pendek',
    titleEn: 'Short semester',
    titleZh: '短学期',
    descId: 'Pendaftaran kelas semester pendek, pembayaran, dan nilai.',
    descEn: 'Short-semester class registration, payment and grades.',
    descZh: '短学期选课、缴费与成绩。',
    endpoints: [
      'GET /api/v1/student/academic-terms',
      'GET /api/v1/student/offering',
      'POST /api/v1/student/krs-items',
    ],
    sources: [
      'docs/semester-pendek/desain-api.md',
      'docs/semester-pendek/frontend-dan-mobile.md',
    ],
    matrixIds: [
      'MOB-SP-01',
      'MOB-SP-02',
      'MOB-SP-03',
      'MOB-SP-06',
      'MOB-KRS-18',
      'MOB-KRS-19',
    ],
  ),
  PendingFeature(
    key: 'qr_attendance',
    icon: Icons.qr_code_scanner,
    audience: _s,
    titleId: 'Presensi QR & izin',
    titleEn: 'QR attendance & leave',
    titleZh: '扫码签到与请假',
    descId:
        'Presensi mandiri dengan memindai QR dosen, serta pengajuan izin/sakit.',
    descEn:
        'Self check-in by scanning the lecturer\'s QR, and sick/leave requests.',
    descZh: '扫描教师二维码自助签到，以及病假/事假申请。',
    endpoints: [
      'POST /api/v1/student/attendance/qr-scan',
      'POST /api/v1/student/attendance/permissions',
    ],
    sources: [
      'docs/academic-learning/02-edlink-lms.md',
      'docs/academic-learning/08-api-specification.md',
    ],
    matrixIds: ['MOB-ATT-06', 'MOB-ATT-08', 'MOB-ATT-09'],
  ),
  PendingFeature(
    key: 'teaching_journal',
    icon: Icons.menu_book_outlined,
    audience: _l,
    titleId: 'Jurnal mengajar & sesi QR',
    titleEn: 'Teaching journal & QR sessions',
    titleZh: '教学日志与扫码签到',
    descId: 'Berita acara perkuliahan per pertemuan dan sesi presensi QR.',
    descEn: 'Per-meeting lecture records and QR attendance sessions.',
    descZh: '每次课的教学记录与扫码签到场次。',
    endpoints: [
      'POST /api/v1/teaching/class-sections/{id}/sessions',
      'POST /api/v1/teaching/sessions/{id}/qr-token',
    ],
    sources: [
      'docs/academic-learning/01-siakad.md#A.5',
      'docs/academic-learning/02-edlink-lms.md',
    ],
    matrixIds: ['MOB-ATT-15', 'MOB-ATT-16', 'MOB-ATT-17'],
  ),
  PendingFeature(
    key: 'grade_components',
    icon: Icons.fact_check_outlined,
    audience: _l,
    titleId: 'Komponen & publikasi nilai',
    titleEn: 'Grade components & publishing',
    titleZh: '成绩构成与发布',
    descId:
        'Bobot komponen nilai, lembar nilai per komponen, finalisasi dan revisi nilai.',
    descEn:
        'Component weights, per-component grade sheets, finalisation and revisions.',
    descZh: '成绩构成权重、分项成绩表、成绩定稿与更正。',
    endpoints: [
      'GET /api/v1/class-sections/{id}/assessment-components',
      'GET /api/v1/class-sections/{id}/grade-sheet',
      'POST /api/v1/class-sections/{id}/grade-sheet/submit',
    ],
    sources: [
      'docs/academic-learning/01-siakad.md#A.6',
      'docs/academic-learning/08-api-specification.md',
    ],
    matrixIds: ['MOB-GRADE-05', 'MOB-GRADE-06', 'MOB-GRADE-08', 'MOB-GRADE-10'],
  ),
  PendingFeature(
    key: 'advisees',
    icon: Icons.groups_2_outlined,
    audience: _a,
    titleId: 'Mahasiswa bimbingan',
    titleEn: 'My advisees',
    titleZh: '我的指导学生',
    descId:
        'Daftar mahasiswa perwalian, progres studi, mahasiswa berisiko, dan catatan bimbingan.',
    descEn:
        'Advisee list, study progress, at-risk students and advising notes.',
    descZh: '指导学生名单、学业进度、风险预警与指导记录。',
    endpoints: [
      'GET /api/v1/advising/students',
      'GET /api/v1/advising/at-risk-students',
      'POST /api/v1/advising/students/{student}/records',
    ],
    sources: [
      'docs/academic-learning/01-siakad.md#A.7',
      'docs/academic-learning/08-api-specification.md',
    ],
    matrixIds: [
      'MOB-KRS-26',
      'MOB-KRS-28',
      'MOB-KRS-29',
      'MOB-ATT-14',
      'MOB-FIN-37',
    ],
  ),
  PendingFeature(
    key: 'lecturer_announcements',
    icon: Icons.campaign_outlined,
    audience: {PendingAudience.lecturer, PendingAudience.advisor},
    titleId: 'Pengumuman untuk dosen',
    titleEn: 'Announcements for lecturers',
    titleZh: '教师公告',
    descId: 'Feed pengumuman kampus untuk dosen dan pengumuman kelas.',
    descEn: 'Campus announcement feed for lecturers and class announcements.',
    descZh: '面向教师的校园公告与班级公告。',
    endpoints: [
      'GET /api/v1/lecturer/announcements (izin announcements.read belum diberikan ke dosen)',
    ],
    sources: ['docs/RANCANGAN-APLIKASI.md#5', 'docs/STATUS-IMPLEMENTASI.md#10'],
    matrixIds: ['MOB-ANN-04', 'MOB-ANN-05', 'MOB-DASH-17'],
  ),
  PendingFeature(
    key: 'student_affairs',
    icon: Icons.emoji_events_outlined,
    audience: _s,
    titleId: 'Poin & prestasi kemahasiswaan',
    titleEn: 'Student points & achievements',
    titleZh: '学生积分与成就',
    descId:
        'Poin kemahasiswaan, pengajuan kegiatan/prestasi, dan kasus disiplin.',
    descEn:
        'Student points, activity/achievement submissions and discipline cases.',
    descZh: '学生积分、活动/成就申报与纪律事项。',
    endpoints: [
      'GET /api/v1/student/affairs/points',
      'GET|POST /api/v1/student/affairs/activity-submissions',
      'GET /api/v1/student/affairs/discipline',
    ],
    sources: [
      'docs/kemahasiswaan-kelulusan/05-rancangan-poin-kemahasiswaan.md',
      'docs/kemahasiswaan-kelulusan/26-rancangan-api-dan-integrasi.md',
    ],
    matrixIds: ['MOB-KMHS-01', 'MOB-KMHS-02', 'MOB-KMHS-03', 'MOB-KMHS-06'],
  ),
  PendingFeature(
    key: 'scholarships',
    icon: Icons.workspace_premium_outlined,
    audience: _s,
    titleId: 'Pendaftaran beasiswa',
    titleEn: 'Scholarship applications',
    titleZh: '奖学金申请',
    descId: 'Jelajahi program beasiswa, ajukan, dan lacak seleksi.',
    descEn: 'Browse scholarship programs, apply and track selection.',
    descZh: '浏览奖学金项目、提交申请并跟踪评选。',
    endpoints: [
      'GET /api/v1/student/affairs/scholarships',
      'POST /api/v1/student/affairs/scholarships/{scholarship}/applications',
    ],
    sources: [
      'docs/kemahasiswaan-kelulusan/08-rancangan-beasiswa-mahasiswa.md',
    ],
    matrixIds: ['MOB-KMHS-10', 'MOB-KMHS-11'],
  ),
  PendingFeature(
    key: 'graduation',
    icon: Icons.school_outlined,
    audience: _s,
    titleId: 'Yudisium & wisuda',
    titleEn: 'Graduation & ceremony',
    titleZh: '毕业审核与典礼',
    descId:
        'Syarat & kelayakan yudisium, pendaftaran wisuda, dan dokumen kelulusan.',
    descEn:
        'Graduation requirements & eligibility, ceremony registration and documents.',
    descZh: '毕业要求与资格、典礼报名及毕业文件。',
    endpoints: [
      'GET /api/v1/student/graduation/requirements',
      'GET /api/v1/student/graduation/eligibility',
      'GET|POST /api/v1/student/graduation/registrations',
    ],
    sources: [
      'docs/kemahasiswaan-kelulusan/10-rancangan-periode-dan-syarat-yudisium.md',
      'docs/kemahasiswaan-kelulusan/14-rancangan-wisuda-dan-buku-wisuda.md',
    ],
    matrixIds: [
      'MOB-GRAD-01',
      'MOB-GRAD-02',
      'MOB-GRAD-04',
      'MOB-GRAD-08',
      'MOB-GRAD-12',
    ],
  ),
  PendingFeature(
    key: 'thesis',
    icon: Icons.history_edu_outlined,
    audience: _s,
    titleId: 'Skripsi / tugas akhir',
    titleEn: 'Thesis',
    titleZh: '毕业论文',
    descId:
        'Pengajuan judul, pembimbing, logbook bimbingan, seminar dan sidang.',
    descEn:
        'Title proposal, supervisors, supervision logbook, seminar and defence.',
    descZh: '选题申请、导师、指导日志、开题与答辩。',
    endpoints: [
      'POST /api/v1/student/thesis/proposals',
      'GET|POST /api/v1/student/thesis/supervisions',
    ],
    sources: [
      'docs/RANCANGAN-APLIKASI.md#4.20',
      'docs/academic-learning/01-siakad.md#A.9.3',
    ],
    matrixIds: ['MOB-THES-01', 'MOB-THES-02', 'MOB-THES-04', 'MOB-THES-06'],
  ),
  PendingFeature(
    key: 'thesis_supervision',
    icon: Icons.supervisor_account_outlined,
    audience: _l,
    titleId: 'Bimbingan skripsi',
    titleEn: 'Thesis supervision',
    titleZh: '论文指导',
    descId: 'Daftar mahasiswa bimbingan tugas akhir dan verifikasi logbook.',
    descEn: 'Thesis supervisees and logbook verification.',
    descZh: '论文指导学生与日志审核。',
    endpoints: ['GET /api/v1/teaching/thesis-supervisions'],
    sources: ['docs/RANCANGAN-AKUN-DOSEN.md', 'docs/STATUS-IMPLEMENTASI.md#7'],
    matrixIds: ['MOB-THES-03', 'MOB-THES-05'],
  ),
  PendingFeature(
    key: 'lms_extras',
    icon: Icons.forum_outlined,
    audience: {PendingAudience.student, PendingAudience.lecturer},
    titleId: 'Forum, kuis & progres LMS',
    titleEn: 'LMS forums, quizzes & progress',
    titleZh: '课程论坛、测验与学习进度',
    descId:
        'Forum diskusi kelas, kuis LMS, pengumuman kelas, dan progres materi.',
    descEn:
        'Class discussion forums, LMS quizzes, class announcements and material progress.',
    descZh: '班级讨论区、课程测验、班级公告与学习进度。',
    endpoints: [
      'GET|POST /api/v1/lms/classes/{id}/forums',
      'POST /api/v1/lms/materials/{id}/progress',
    ],
    sources: ['docs/academic-learning/02-edlink-lms.md#B.2'],
    matrixIds: ['MOB-LMS-20', 'MOB-LMS-22', 'MOB-LMS-23', 'MOB-LMS-25'],
  ),
  PendingFeature(
    key: 'push_notifications',
    icon: Icons.notifications_active_outlined,
    audience: {
      PendingAudience.student,
      PendingAudience.lecturer,
      PendingAudience.advisor,
    },
    titleId: 'Notifikasi push',
    titleEn: 'Push notifications',
    titleZh: '推送通知',
    descId:
        'Notifikasi di luar aplikasi. Saat ini notifikasi tersedia di pusat notifikasi dalam aplikasi.',
    descEn:
        'Notifications outside the app. Notifications are currently available in the in-app notification centre.',
    descZh: '应用外推送。目前可在应用内通知中心查看通知。',
    endpoints: [
      'POST /api/v1/devices/push-token',
      'DELETE /api/v1/devices/push-token',
      'Kanal push pada modul Notification',
    ],
    sources: [
      'docs/RANCANGAN_FLUTTER_CIVITAS_ONE.md#8.3',
      'docs/STATUS-IMPLEMENTASI.md#1',
    ],
    matrixIds: ['MOB-PLAT-14', 'MOB-ANN-10'],
  ),
  PendingFeature(
    key: 'employee_attendance',
    icon: Icons.badge_outlined,
    audience: _l,
    titleId: 'Kehadiran pegawai',
    titleEn: 'Staff attendance',
    titleZh: '员工考勤',
    descId: 'Presensi kerja harian dosen sebagai pegawai.',
    descEn: 'Daily work attendance for lecturers as employees.',
    descZh: '教师作为员工的每日考勤。',
    endpoints: ['(belum dirancang di API) modul SDM #11 Kehadiran pegawai'],
    sources: ['docs/RANCANGAN-AKUN-SDM.md', 'docs/STATUS-IMPLEMENTASI.md#9'],
    matrixIds: ['MOB-HR-18'],
  ),
];

PendingFeature? pendingFeatureByKey(String key) {
  for (final f in kPendingFeatures) {
    if (f.key == key) return f;
  }
  return null;
}
