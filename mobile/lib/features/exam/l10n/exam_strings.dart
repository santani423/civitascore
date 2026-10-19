import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';
import '../domain/exam_monitoring.dart';
import '../domain/exam_validation.dart';
import '../domain/grade_range.dart';
import '../domain/question_selection_mode.dart';

/// Teks fitur ujian dosen (ID default, EN, ZH Simplified).
class ExamStrings {
  const ExamStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // Daftar ----------------------------------------------------------------
  String get title => _p('Ujian', 'Exams', '考试');
  String get newExam => _p('Ujian baru', 'New exam', '新建考试');
  String get editExam => _p('Ubah ujian', 'Edit exam', '编辑考试');
  String get draft => _p('Draft', 'Draft', '草稿');
  String get published => _p('Dipublikasikan', 'Published', '已发布');
  String get emptyListTitle => _p('Belum ada ujian', 'No exams yet', '暂无考试');
  String get emptyListBody => _p(
    'Buat ujian pertama untuk salah satu kelas Anda.',
    'Create your first exam for one of your classes.',
    '为您的某个班级创建第一场考试。',
  );
  String get questionBank => _p('Bank soal', 'Question bank', '题库');
  String perParticipantInfo(int qpp, int pool) => _p(
    '$qpp soal per peserta • $pool soal tersedia',
    '$qpp questions per participant • $pool in pool',
    '每位考生 $qpp 题 • 题池 $pool 题',
  );
  String durationMinutes(int n) => _p('$n menit', '$n min', '$n 分钟');

  // Form ------------------------------------------------------------------
  String get stepInfo => _p('Informasi', 'Information', '基本信息');
  String get stepQuestions => _p('Soal', 'Questions', '题目');
  String get stepPublish =>
      _p('Preview & Publish', 'Preview & publish', '预览与发布');
  String get classLabel => _p('Kelas', 'Class', '班级');
  String get classLockedHelp => _p(
    'Kelas tidak dapat diubah setelah ujian dibuat.',
    'The class cannot be changed after the exam is created.',
    '考试创建后无法更改班级。',
  );
  String get noClasses => _p(
    'Anda belum memiliki kelas ajar.',
    'You have no teaching classes yet.',
    '您还没有授课班级。',
  );
  String get titleLabel => _p('Judul ujian', 'Exam title', '考试标题');
  String get durationLabel =>
      _p('Durasi (menit)', 'Duration (minutes)', '时长（分钟）');
  String get attemptsLabel => _p('Batas percobaan', 'Attempt limit', '尝试次数上限');
  String get startsAt =>
      _p('Mulai (opsional)', 'Starts (optional)', '开始时间（可选）');
  String get endsAt => _p('Selesai (opsional)', 'Ends (optional)', '结束时间（可选）');
  String get notSet => _p('Belum diatur', 'Not set', '未设置');
  String get clear => _p('Hapus', 'Clear', '清除');
  String get endBeforeStart => _p(
    'Waktu selesai harus setelah waktu mulai.',
    'The end time must be after the start time.',
    '结束时间必须晚于开始时间。',
  );
  String get weightLabel =>
      _p('Bobot nilai % (opsional)', 'Grade weight % (optional)', '成绩权重 %（可选）');
  String get qppLabel =>
      _p('Jumlah soal per peserta', 'Questions per participant', '每位考生题数');
  String get qppHelp => _p(
    'Berapa soal yang dikerjakan setiap peserta dari seluruh soal yang disiapkan.',
    'How many questions each participant answers out of the whole pool.',
    '每位考生从题池中作答的题目数量。',
  );
  String get questionSettings =>
      _p('Pengaturan soal', 'Question settings', '题目设置');
  String poolInfo(int n) =>
      _p('Question pool: $n soal', 'Question pool: $n questions', '题池：$n 题');
  String get manageQuestions => _p('Kelola soal', 'Manage questions', '管理题目');
  String get saveFirst => _p(
    'Simpan informasi ujian terlebih dahulu untuk menambah soal.',
    'Save the exam information first to add questions.',
    '请先保存考试信息再添加题目。',
  );
  String get selectionTitle =>
      _p('Pemilihan soal', 'Question selection', '选题方式');
  String get selectionHelp => _p(
    'Tentukan bagaimana soal dipilih untuk setiap peserta.',
    'Choose how questions are picked for each participant.',
    '选择每位考生的题目选取方式。',
  );
  String selectionModeLabel(QuestionSelectionMode m) => switch (m) {
    QuestionSelectionMode.all => _p('Semua soal', 'All questions', '全部题目'),
    QuestionSelectionMode.random => _p(
      'Acak dari question pool',
      'Random from the pool',
      '从题池随机抽取',
    ),
    QuestionSelectionMode.manual => _p(
      'Pilih soal secara manual',
      'Pick questions manually',
      '手动选择题目',
    ),
  };
  String get shuffleTitle => _p('Pengacakan', 'Shuffling', '随机化');
  String get shuffleQuestions => _p('Urutan soal', 'Question order', '题目顺序');
  String get shuffleQuestionsHelp => _p(
    'Posisi soal berbeda untuk setiap peserta.',
    'Question positions differ for every participant.',
    '每位考生的题目顺序不同。',
  );
  String get shuffleOptions => _p('Pilihan jawaban', 'Answer options', '答案选项');
  String get shuffleOptionsHelp => _p(
    'Opsi A, B, C, D diacak.',
    'Options A, B, C, D are shuffled.',
    '选项 A、B、C、D 随机排列。',
  );
  String get advanced => _p('Pengaturan lanjutan', 'Advanced settings', '高级设置');
  String get allowBack => _p(
    'Izinkan kembali ke soal sebelumnya',
    'Allow going back to previous questions',
    '允许返回上一题',
  );
  String get showResult => _p(
    'Tampilkan nilai setelah ujian',
    'Show the result after the exam',
    '考试后显示成绩',
  );
  String get distributionTitle =>
      _p('Distribusi soal', 'Question distribution', '题目分配');
  String get poolRow => 'Question pool';
  String get perParticipantRow =>
      _p('Soal per peserta', 'Per participant', '每位考生题数');
  String get selectionRow => _p('Pemilihan', 'Selection', '选题方式');
  String get shuffled => _p('Diacak', 'Shuffled', '已随机');
  String get inOrder => _p('Sesuai urutan', 'In order', '按顺序');
  String get previewAsStudent =>
      _p('Preview sebagai mahasiswa', 'Preview as student', '以学生视角预览');
  String get publishExam => _p('Publish ujian', 'Publish exam', '发布考试');
  String get alreadyPublished =>
      _p('Sudah dipublikasikan', 'Already published', '已发布');
  String get publishedOk => _p(
    'Ujian berhasil dipublikasikan.',
    'Exam published successfully.',
    '考试发布成功。',
  );
  String get publishNoPermission => _p(
    'Anda tidak memiliki izin mempublikasikan ujian.',
    'You are not allowed to publish exams.',
    '您没有发布考试的权限。',
  );
  String get publishErrorsTitle => _p(
    'Ujian belum dapat dipublikasikan',
    'The exam cannot be published yet',
    '考试暂无法发布',
  );
  String get savedOk => _p('Ujian disimpan.', 'Exam saved.', '考试已保存。');
  String get validationPositive =>
      _p('Harus lebih dari 0', 'Must be greater than 0', '必须大于 0');

  String issueText(ExamIssue i) => switch (i.type) {
    ExamIssueType.participantCount => _p(
      'Jumlah soal harus lebih dari 0.',
      'The number of questions must be greater than 0.',
      '题目数量必须大于 0。',
    ),
    ExamIssueType.poolEmpty => _p(
      'Question pool masih kosong. Tambahkan soal terlebih dahulu.',
      'The question pool is empty. Add questions first.',
      '题池为空，请先添加题目。',
    ),
    ExamIssueType.exceedsPool => _p(
      'Jumlah soal per peserta tidak boleh melebihi jumlah soal dalam question pool.',
      'Questions per participant cannot exceed the number of questions in the pool.',
      '每位考生题数不能超过题池中的题目数。',
    ),
    ExamIssueType.manualShort => _p(
      'Soal belum mencukupi. Anda membutuhkan ${i.required} soal, tetapi baru memilih ${i.selected} soal.',
      'Not enough questions. You need ${i.required} but only selected ${i.selected}.',
      '题目不足。需要 ${i.required} 题，但仅选择了 ${i.selected} 题。',
    ),
  };

  String distributionSummary({
    required int pool,
    required int perParticipant,
    required QuestionSelectionMode mode,
    required bool shuffleQ,
    required bool shuffleO,
  }) {
    final sel = switch (mode) {
      QuestionSelectionMode.all => _p(
        'seluruh $pool soal yang tersedia',
        'all $pool available questions',
        '全部 $pool 道题',
      ),
      QuestionSelectionMode.random => _p(
        '$perParticipant soal yang dipilih secara acak dari $pool soal',
        '$perParticipant questions picked at random from $pool',
        '从 $pool 题中随机抽取 $perParticipant 题',
      ),
      QuestionSelectionMode.manual => _p(
        '$perParticipant soal yang telah dipilih secara manual',
        '$perParticipant manually selected questions',
        '手动选定的 $perParticipant 题',
      ),
    };
    final base = _p(
      'Setiap peserta akan mendapatkan $sel.',
      'Every participant will get $sel.',
      '每位考生将获得$sel。',
    );
    if (!shuffleQ && !shuffleO) return base;
    final both = shuffleQ && shuffleO;
    final tail = switch ((shuffleQ, both)) {
      (_, true) => _p(
        'Urutan soal dan pilihan jawaban akan diacak.',
        'Question order and answer options will be shuffled.',
        '题目顺序和答案选项将被打乱。',
      ),
      (true, _) => _p(
        'Urutan soal akan diacak.',
        'Question order will be shuffled.',
        '题目顺序将被打乱。',
      ),
      _ => _p(
        'Pilihan jawaban akan diacak.',
        'Answer options will be shuffled.',
        '答案选项将被打乱。',
      ),
    };
    return '$base $tail';
  }

  // Detail ----------------------------------------------------------------
  String get tabOverview => _p('Ringkasan', 'Overview', '概览');
  String get tabMonitor => _p('Pantau', 'Monitor', '监考');
  String get tabRecap => _p('Rekap', 'Recap', '成绩汇总');
  String get tabGrades => _p('Nilai huruf', 'Grades', '等级');
  String get statusSection => _p('Status', 'Status', '状态');
  String get scheduleSection => _p('Jadwal', 'Schedule', '时间安排');
  String get scheduleStart => _p('Mulai', 'Starts', '开始');
  String get scheduleEnd => _p('Selesai', 'Ends', '结束');
  String get publishedAtLabel => _p('Dipublikasikan', 'Published at', '发布时间');
  String get settingsSection => _p('Pengaturan', 'Settings', '设置');
  String get classRow => _p('Kelas', 'Class', '班级');
  String get durationRow => _p('Durasi', 'Duration', '时长');
  String get attemptsRow => _p('Batas percobaan', 'Attempt limit', '尝试次数');
  String get weightRow => _p('Bobot nilai', 'Grade weight', '成绩权重');
  String get backNavRow =>
      _p('Kembali ke soal sebelumnya', 'Go back to questions', '返回上一题');
  String get showResultRow => _p('Tampilkan nilai', 'Show result', '显示成绩');
  String get yes => _p('Ya', 'Yes', '是');
  String get no => _p('Tidak', 'No', '否');
  String get actionsSection => _p('Aksi', 'Actions', '操作');
  String get questionsAction => _p('Soal ujian', 'Exam questions', '考试题目');
  String get downloadPaper =>
      _p('Unduh naskah ujian', 'Download exam paper', '下载试卷');
  String get downloadPaperKey => _p(
    'Unduh naskah + kunci jawaban',
    'Download paper + answer key',
    '下载试卷与答案',
  );
  String get deleteExam => _p('Hapus ujian', 'Delete exam', '删除考试');
  String get deleteExamTitle =>
      _p('Hapus ujian ini?', 'Delete this exam?', '删除此考试？');
  String get deleteExamBody => _p(
    'Ujian yang sudah memiliki percobaan peserta tidak dapat dihapus.',
    'Exams that already have participant attempts cannot be deleted.',
    '已有考生作答记录的考试无法删除。',
  );
  String get deleted => _p('Ujian dihapus.', 'Exam deleted.', '考试已删除。');
  String get accessLinkTitle => _p('Tautan akses', 'Access link', '访问链接');
  String get accessLinkNone => _p(
    'Belum ada tautan akses. Buat tautan untuk dibagikan ke peserta.',
    'No access link yet. Generate one to share with participants.',
    '尚无访问链接，请生成后分享给考生。',
  );
  String get accessLinkActive => _p('Aktif', 'Active', '有效');
  String get generateLink => _p('Buat tautan', 'Generate link', '生成链接');
  String get regenerateLink =>
      _p('Buat ulang tautan', 'Regenerate link', '重新生成链接');
  String get regenerateTitle =>
      _p('Buat ulang tautan?', 'Regenerate the link?', '重新生成链接？');
  String get regenerateBody => _p(
    'Tautan lama langsung tidak berlaku. Peserta yang sedang mengerjakan tidak terpengaruh.',
    'The old link stops working immediately. Participants currently taking the exam are not affected.',
    '旧链接将立即失效，正在作答的考生不受影响。',
  );
  String get linkCopied => _p('Tautan disalin.', 'Link copied.', '链接已复制。');
  String get shareLink => _p('Bagikan', 'Share', '分享');
  String get copyLink => _p('Salin', 'Copy', '复制');
  String get qrSemantics =>
      _p('Kode QR tautan ujian', 'Exam link QR code', '考试链接二维码');
  String shareMessage(String title, String url) =>
      _p('Ujian "$title": $url', 'Exam "$title": $url', '考试“$title”：$url');

  // Pantau ------------------------------------------------------------------
  String get autoRefresh => _p(
    'Diperbarui otomatis tiap 15 detik',
    'Auto-refreshes every 15 seconds',
    '每 15 秒自动刷新',
  );
  String get alertsTitle =>
      _p('Pelanggaran terbaru', 'Recent violations', '最新违规');
  String get noAlerts =>
      _p('Belum ada pelanggaran.', 'No violations yet.', '暂无违规。');
  String violationAlert(ExamViolation v) => _p(
    '${v.studentName} (${v.studentNim}): ${v.label ?? v.type} - Pelanggaran #${v.sequenceNumber}',
    '${v.studentName} (${v.studentNim}): ${v.label ?? v.type} - Violation #${v.sequenceNumber}',
    '${v.studentName}（${v.studentNim}）：${v.label ?? v.type} - 第 ${v.sequenceNumber} 次违规',
  );
  String get participantsTitle => _p('Peserta', 'Participants', '考生');
  String get noParticipants => _p(
    'Belum ada peserta terdaftar di kelas ini.',
    'No participants are enrolled in this class.',
    '该班级暂无考生。',
  );
  String participantStatus(ParticipantStatus s) => switch (s) {
    ParticipantStatus.notStarted => _p(
      'Belum mengerjakan',
      'Not started',
      '未开始',
    ),
    ParticipantStatus.inProgress => _p(
      'Sedang mengerjakan',
      'In progress',
      '作答中',
    ),
    ParticipantStatus.completed => _p('Selesai', 'Completed', '已完成'),
  };
  String violationsCount(int n) =>
      _p('$n pelanggaran', '$n violations', '$n 次违规');
  String get rawScore => _p('Raw', 'Raw', '原始分');
  String get penalty => _p('Penalti', 'Penalty', '扣分');
  String get finalScore => _p('Akhir', 'Final', '最终分');
  String get gradeLabel => _p('Nilai huruf', 'Grade', '等级');
  String get weightedScore => _p('Berbobot', 'Weighted', '加权分');
  String get violationTimeline =>
      _p('Linimasa pelanggaran', 'Violation timeline', '违规时间线');
  String get penaltyPoints => _p('Poin penalti', 'Penalty points', '扣分');

  // Rekap ---------------------------------------------------------------------
  String get statTotal => _p('Peserta', 'Participants', '考生');
  String get statCompleted => _p('Selesai', 'Completed', '已完成');
  String get statInProgress => _p('Mengerjakan', 'In progress', '作答中');
  String get statNotStarted => _p('Belum', 'Not started', '未开始');
  String get statAverage => _p('Rata-rata', 'Average', '平均分');
  String get statHighest => _p('Tertinggi', 'Highest', '最高分');
  String get statLowest => _p('Terendah', 'Lowest', '最低分');
  String get searchHint =>
      _p('Cari nama atau NIM', 'Search name or student ID', '搜索姓名或学号');
  String get exportCsv => _p('Ekspor CSV', 'Export CSV', '导出 CSV');
  String get exportPdf => _p('Ekspor PDF', 'Export PDF', '导出 PDF');
  String get noMatches => _p(
    'Tidak ada peserta yang cocok.',
    'No matching participants.',
    '没有匹配的考生。',
  );

  // Rentang nilai --------------------------------------------------------------
  String get gradeRangesTitle =>
      _p('Rentang nilai huruf', 'Letter grade ranges', '等级分数段');
  String get gradeRangesHelp => _p(
    'Rentang tidak boleh tumpang-tindih atau berlubang (tulis 79,99 bukan 79). Bila belum diatur, standar berlaku (A ≥ 85 … E < 35).',
    'Ranges must not overlap or leave gaps (write 79.99, not 79). If unset, the defaults apply (A ≥ 85 … E < 35).',
    '分数段不得重叠或留空（写 79.99 而不是 79）。未设置时使用默认值（A ≥ 85 … E < 35）。',
  );
  String get minScore => _p('Min', 'Min', '最低');
  String get maxScore => _p('Maks', 'Max', '最高');
  String get addRange => _p('Tambah rentang', 'Add range', '添加分数段');
  String get useDefaults => _p('Pakai standar', 'Use defaults', '使用默认值');
  String get saveRanges => _p('Simpan rentang', 'Save ranges', '保存分数段');
  String get rangesSaved =>
      _p('Rentang nilai disimpan.', 'Grade ranges saved.', '分数段已保存。');
  String get weightFormulaHelp => _p(
    'Nilai akhir = raw dikurangi penalti (1 poin per pelanggaran, minimum 0); nilai berbobot = nilai akhir × bobot%.',
    'Final = raw minus penalty (1 point per violation, minimum 0); weighted = final × weight%.',
    '最终分 = 原始分 - 扣分（每次违规 1 分，最低 0）；加权分 = 最终分 × 权重%。',
  );
  String rangeIssue(GradeRangeIssue i) => switch (i) {
    GradeRangeIssue.empty => _p(
      'Tambahkan minimal satu rentang.',
      'Add at least one range.',
      '请至少添加一个分数段。',
    ),
    GradeRangeIssue.duplicateGrade => _p(
      'Setiap nilai huruf hanya boleh muncul satu kali.',
      'Each letter grade may appear only once.',
      '每个等级只能出现一次。',
    ),
    GradeRangeIssue.invalidBounds => _p(
      'Batas nilai harus 0-100 dan maks ≥ min.',
      'Bounds must be 0-100 and max ≥ min.',
      '分数范围须为 0-100，且最高 ≥ 最低。',
    ),
    GradeRangeIssue.overlap => _p(
      'Rentang nilai saling tumpang tindih.',
      'Ranges overlap.',
      '分数段相互重叠。',
    ),
    GradeRangeIssue.gap => _p(
      'Ada skor yang tidak tercakup rentang mana pun.',
      'Some scores are not covered by any range.',
      '部分分数未被任何分数段覆盖。',
    ),
  };

  // Soal ujian ---------------------------------------------------------------
  String questionsTitle(int n) =>
      _p('Soal ujian ($n)', 'Exam questions ($n)', '考试题目（$n）');
  String get emptyQuestionsTitle =>
      _p('Belum ada soal', 'No questions yet', '暂无题目');
  String get emptyQuestionsBody => _p(
    'Tambahkan soal baru atau terapkan dari bank soal.',
    'Add a new question or apply some from the question bank.',
    '添加新题目或从题库应用。',
  );
  String get addQuestion => _p('Tambah soal', 'Add question', '添加题目');
  String get editQuestion => _p('Ubah soal', 'Edit question', '编辑题目');
  String get applyFromBank =>
      _p('Terapkan dari bank soal', 'Apply from question bank', '从题库应用');
  String get questionLabel => _p('Pertanyaan', 'Question', '题干');
  String get pointsLabel => _p('Poin', 'Points', '分值');
  String get optionsLabel => _p('Opsi jawaban', 'Answer options', '答案选项');
  String optionN(int n) => _p('Opsi $n', 'Option $n', '选项 $n');
  String get addOption => _p('Tambah opsi', 'Add option', '添加选项');
  String get removeOption => _p('Hapus opsi', 'Remove option', '删除选项');
  String get markCorrect =>
      _p('Tandai sebagai jawaban benar', 'Mark as correct answer', '标为正确答案');
  String get minTwoOptions => _p(
    'Minimal 2 opsi jawaban.',
    'At least 2 answer options.',
    '至少需要 2 个选项。',
  );
  String get exactlyOneCorrect => _p(
    'Setiap soal harus memiliki tepat satu jawaban benar.',
    'Each question must have exactly one correct answer.',
    '每道题必须有且仅有一个正确答案。',
  );
  String get optionsFilled => _p(
    'Semua opsi harus diisi.',
    'All options must be filled in.',
    '所有选项都必须填写。',
  );
  String questionSummary(int opts, int correct) => _p(
    '$opts opsi • $correct jawaban benar',
    '$opts options • $correct correct',
    '$opts 个选项 • $correct 个正确答案',
  );
  String get notSelectedManual =>
      _p('tidak dipilih (manual)', 'not selected (manual)', '未选中（手动）');
  String get selectedToggle => _p(
    'Sertakan dalam pilihan manual',
    'Include in manual selection',
    '纳入手动选择',
  );
  String get deleteQuestionTitle =>
      _p('Hapus soal ini?', 'Delete this question?', '删除此题？');
  String get lockedBanner => _p(
    'Ujian sudah dipublikasikan, soal terkunci dan tidak dapat diubah.',
    'The exam is published; questions are locked and cannot be changed.',
    '考试已发布，题目已锁定，无法修改。',
  );

  // Preview ---------------------------------------------------------------------
  String get previewTitle => _p('Preview ujian', 'Exam preview', '考试预览');
  String get previewEmpty => _p(
    'Belum ada soal untuk dipratinjau.',
    'No questions to preview yet.',
    '暂无可预览的题目。',
  );
  String questionOf(int i, int n) =>
      _p('Soal $i dari $n', 'Question $i of $n', '第 $i / $n 题');

  // Bank soal -----------------------------------------------------------------
  String get bankTitle => _p('Bank soal', 'Question bank', '题库');
  String get bankSearchHint => _p('Cari soal', 'Search questions', '搜索题目');
  String get allCourses => _p('Semua mata kuliah', 'All courses', '全部课程');
  String get courseLabel => _p('Mata kuliah', 'Course', '课程');
  String get noCourse => _p('Tanpa mata kuliah', 'No course', '不指定课程');
  String get bankEmptyTitle =>
      _p('Bank soal kosong', 'Question bank is empty', '题库为空');
  String get bankEmptyBody => _p(
    'Tambahkan butir soal untuk dipakai ulang di berbagai ujian.',
    'Add questions to reuse across exams.',
    '添加题目以便在多场考试中复用。',
  );
  String usageCount(int n) =>
      _p('Dipakai $n kali', 'Used $n times', '已使用 $n 次');
  String get addBankItem => _p('Tambah ke bank', 'Add to bank', '加入题库');
  String get editBankItem => _p('Ubah butir soal', 'Edit question', '编辑题目');
  String get deleteBankTitle =>
      _p('Hapus soal dari bank?', 'Delete from the bank?', '从题库中删除？');
  String get bankSaved =>
      _p('Butir soal disimpan.', 'Question saved.', '题目已保存。');
  String get bankDeleted =>
      _p('Butir soal dihapus.', 'Question deleted.', '题目已删除。');
  String get pickExamTitle =>
      _p('Pilih soal untuk diterapkan', 'Pick questions to apply', '选择要应用的题目');
  String applySelected(int n) =>
      _p('Terapkan $n soal', 'Apply $n questions', '应用 $n 道题');
  String get applied => _p(
    'Soal diterapkan ke ujian.',
    'Questions applied to the exam.',
    '题目已应用到考试。',
  );
}

extension ExamStringsX on BuildContext {
  ExamStrings get examL10n => ExamStrings(this);
}
