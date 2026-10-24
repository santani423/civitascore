import '../../../core/network/api_envelope.dart';

enum ReportQuestionStatus {
  correct('correct'),
  wrong('wrong'),
  unanswered('unanswered');

  const ReportQuestionStatus(this.apiValue);
  final String apiValue;

  static ReportQuestionStatus fromApi(String? v) => values.firstWhere(
    (e) => e.apiValue == v,
    orElse: () => ReportQuestionStatus.unanswered,
  );
}

enum TimelineEventType {
  started('started'),
  answered('answered'),
  violation('violation'),
  submitted('submitted');

  const TimelineEventType(this.apiValue);
  final String apiValue;

  static TimelineEventType fromApi(String? v) => values.firstWhere(
    (e) => e.apiValue == v,
    orElse: () => TimelineEventType.answered,
  );
}

/// Opsi sesuai urutan yang dilihat peserta (option_order attempt); label
/// A/B/C… dari urutan itu.
class ReportOption {
  const ReportOption({
    required this.id,
    required this.label,
    this.text,
    required this.isCorrect,
  });

  factory ReportOption.fromJson(Map<String, dynamic> json) => ReportOption(
    id: asString(json['id']) ?? '',
    label: asString(json['label']) ?? '',
    text: asString(json['option_text']),
    isCorrect: asBool(json['is_correct']),
  );

  final String id;
  final String label;
  final String? text;
  final bool isCorrect;
}

class ReportQuestion {
  const ReportQuestion({
    required this.number,
    required this.questionId,
    this.text,
    this.points,
    this.earnedPoints,
    required this.fromQuestionBank,
    required this.options,
    this.selectedOptionId,
    this.selectedOptionLabel,
    this.correctOptionId,
    this.correctOptionLabel,
    required this.status,
    this.answeredAt,
    this.answeredAfterSeconds,
  });

  factory ReportQuestion.fromJson(Map<String, dynamic> json) => ReportQuestion(
    number: asInt(json['number']) ?? 0,
    questionId: asString(json['question_id']) ?? '',
    text: asString(json['question_text']),
    points: asDouble(json['points']),
    earnedPoints: asDouble(json['earned_points']),
    fromQuestionBank: asBool(json['from_question_bank']),
    options: asMapList(json['options']).map(ReportOption.fromJson).toList(),
    selectedOptionId: asString(json['selected_option_id']),
    selectedOptionLabel: asString(json['selected_option_label']),
    correctOptionId: asString(json['correct_option_id']),
    correctOptionLabel: asString(json['correct_option_label']),
    status: ReportQuestionStatus.fromApi(asString(json['status'])),
    answeredAt: asDateTime(json['answered_at']),
    answeredAfterSeconds: asInt(json['answered_after_seconds']),
  );

  final int number;
  final String questionId;
  final String? text;
  final double? points;
  final double? earnedPoints;
  final bool fromQuestionBank;
  final List<ReportOption> options;
  final String? selectedOptionId;
  final String? selectedOptionLabel;
  final String? correctOptionId;
  final String? correctOptionLabel;
  final ReportQuestionStatus status;

  /// Waktu jawaban TERAKHIR disimpan (ditimpa tiap kali peserta mengganti
  /// jawaban), bukan pertama kali soal dijawab.
  final DateTime? answeredAt;
  final int? answeredAfterSeconds;
}

class ReportViolation {
  const ReportViolation({
    required this.id,
    required this.sequenceNumber,
    required this.type,
    this.label,
    required this.penaltyPoints,
    this.occurredAt,
    this.occurredAfterSeconds,
  });

  factory ReportViolation.fromJson(Map<String, dynamic> json) =>
      ReportViolation(
        id: asString(json['id']) ?? '',
        sequenceNumber: asInt(json['sequence_number']) ?? 0,
        type: asString(json['violation_type']) ?? '',
        label: asString(json['violation_label']),
        penaltyPoints: asDouble(json['penalty_points']) ?? 0,
        occurredAt: asDateTime(json['occurred_at']),
        occurredAfterSeconds: asInt(json['occurred_after_seconds']),
      );

  final String id;
  final int sequenceNumber;
  final String type;
  final String? label;
  final double penaltyPoints;
  final DateTime? occurredAt;
  final int? occurredAfterSeconds;
}

class ViolationBreakdown {
  const ViolationBreakdown({
    required this.type,
    this.label,
    required this.count,
    required this.penaltyPoints,
  });

  factory ViolationBreakdown.fromJson(Map<String, dynamic> json) =>
      ViolationBreakdown(
        type: asString(json['violation_type']) ?? '',
        label: asString(json['violation_label']),
        count: asInt(json['count']) ?? 0,
        penaltyPoints: asDouble(json['penalty_points']) ?? 0,
      );

  final String type;
  final String? label;
  final int count;
  final double penaltyPoints;
}

/// `description` dirangkai backend (Bahasa Indonesia), ditampilkan apa adanya
/// seperti label pelanggaran.
class TimelineEvent {
  const TimelineEvent({
    required this.type,
    this.occurredAt,
    required this.elapsedSeconds,
    required this.description,
  });

  factory TimelineEvent.fromJson(Map<String, dynamic> json) => TimelineEvent(
    type: TimelineEventType.fromApi(asString(json['type'])),
    occurredAt: asDateTime(json['occurred_at']),
    elapsedSeconds: asInt(json['elapsed_seconds']) ?? 0,
    description: asString(json['description']) ?? '',
  );

  final TimelineEventType type;
  final DateTime? occurredAt;
  final int elapsedSeconds;
  final String description;
}

/// Ringkas tiap percobaan peserta yang sama — untuk berpindah laporan bila
/// `max_attempts` > 1.
class ReportAttemptRef {
  const ReportAttemptRef({
    required this.id,
    required this.attemptNumber,
    required this.isSubmitted,
    this.score,
  });

  factory ReportAttemptRef.fromJson(Map<String, dynamic> json) =>
      ReportAttemptRef(
        id: asString(json['id']) ?? '',
        attemptNumber: asInt(json['attempt_number']) ?? 0,
        isSubmitted: asString(json['status']) == 'submitted',
        score: asDouble(json['score']),
      );

  final String id;
  final int attemptNumber;
  final bool isSubmitted;
  final double? score;
}

/// `GET /exam-attempts/{id}/report` — laporan lengkap satu percobaan untuk
/// dosen (ExamService::buildAttemptReport). Skor dihitung backend; klien
/// hanya menampilkan.
class AttemptReport {
  const AttemptReport({
    required this.examId,
    required this.examTitle,
    this.courseCode,
    this.courseName,
    this.classCode,
    this.academicTerm,
    this.lecturerName,
    required this.durationMinutes,
    this.examStartsAt,
    this.examEndsAt,
    required this.maxAttempts,
    this.weightPercentage,
    required this.studentName,
    required this.studentNim,
    this.studyProgram,
    required this.attemptId,
    required this.attemptNumber,
    required this.isSubmitted,
    this.startedAt,
    this.submittedAt,
    this.deadlineAt,
    required this.allowedSeconds,
    required this.durationSeconds,
    required this.autoSubmitted,
    required this.totalQuestions,
    required this.answered,
    required this.correctAnswers,
    required this.wrongAnswers,
    required this.unanswered,
    this.totalPoints,
    this.earnedPoints,
    this.rawScore,
    this.penaltyScore,
    this.score,
    this.grade,
    this.weightedScore,
    required this.violationCount,
    required this.questions,
    required this.violations,
    required this.violationBreakdown,
    required this.timeline,
    required this.attempts,
  });

  factory AttemptReport.fromJson(Map<String, dynamic> json) {
    final exam = asMap(json['exam']);
    final student = asMap(json['student']);
    final attempt = asMap(json['attempt']);
    final summary = asMap(json['summary']);
    return AttemptReport(
      examId: asString(exam['id']) ?? '',
      examTitle: asString(exam['title']) ?? '',
      courseCode: asString(exam['course_code']),
      courseName: asString(exam['course_name']),
      classCode: asString(exam['class_code']),
      academicTerm: asString(exam['academic_term']),
      lecturerName: asString(exam['lecturer_name']),
      durationMinutes: asInt(exam['duration_minutes']) ?? 0,
      examStartsAt: asDateTime(exam['starts_at']),
      examEndsAt: asDateTime(exam['ends_at']),
      maxAttempts: asInt(exam['max_attempts']) ?? 1,
      weightPercentage: asDouble(exam['weight_percentage']),
      studentName: asString(student['name']) ?? '',
      studentNim: asString(student['nim']) ?? '',
      studyProgram: asString(student['study_program']),
      attemptId: asString(attempt['id']) ?? '',
      attemptNumber: asInt(attempt['attempt_number']) ?? 1,
      isSubmitted: asString(attempt['status']) == 'submitted',
      startedAt: asDateTime(attempt['started_at']),
      submittedAt: asDateTime(attempt['submitted_at']),
      deadlineAt: asDateTime(attempt['deadline_at']),
      allowedSeconds: asInt(attempt['allowed_seconds']) ?? 0,
      durationSeconds: asInt(attempt['duration_seconds']) ?? 0,
      autoSubmitted: asString(attempt['submission_type']) == 'auto_timeout',
      totalQuestions: asInt(summary['total_questions']) ?? 0,
      answered: asInt(summary['answered']) ?? 0,
      correctAnswers: asInt(summary['correct_answers']) ?? 0,
      wrongAnswers: asInt(summary['wrong_answers']) ?? 0,
      unanswered: asInt(summary['unanswered']) ?? 0,
      totalPoints: asDouble(summary['total_points']),
      earnedPoints: asDouble(summary['earned_points']),
      rawScore: asDouble(summary['raw_score']),
      penaltyScore: asDouble(summary['penalty_score']),
      score: asDouble(summary['score']),
      grade: asString(summary['grade']),
      weightedScore: asDouble(summary['weighted_score']),
      violationCount: asInt(summary['violation_count']) ?? 0,
      questions: asMapList(
        json['questions'],
      ).map(ReportQuestion.fromJson).toList(),
      violations: asMapList(
        json['violations'],
      ).map(ReportViolation.fromJson).toList(),
      violationBreakdown: asMapList(
        json['violation_breakdown'],
      ).map(ViolationBreakdown.fromJson).toList(),
      timeline: asMapList(
        json['timeline'],
      ).map(TimelineEvent.fromJson).toList(),
      attempts: asMapList(
        json['attempts'],
      ).map(ReportAttemptRef.fromJson).toList(),
    );
  }

  // Ujian
  final String examId;
  final String examTitle;
  final String? courseCode;
  final String? courseName;
  final String? classCode;
  final String? academicTerm;
  final String? lecturerName;
  final int durationMinutes;
  final DateTime? examStartsAt;
  final DateTime? examEndsAt;
  final int maxAttempts;
  final double? weightPercentage;

  // Mahasiswa
  final String studentName;
  final String studentNim;
  final String? studyProgram;

  // Percobaan
  final String attemptId;
  final int attemptNumber;
  final bool isSubmitted;
  final DateTime? startedAt;
  final DateTime? submittedAt;
  final DateTime? deadlineAt;
  final int allowedSeconds;

  /// Selama in_progress: waktu berjalan sampai sekarang (dibatasi deadline).
  final int durationSeconds;
  final bool autoSubmitted;

  // Ringkasan
  final int totalQuestions;
  final int answered;
  final int correctAnswers;
  final int wrongAnswers;
  final int unanswered;
  final double? totalPoints;
  final double? earnedPoints;
  final double? rawScore;
  final double? penaltyScore;
  final double? score;
  final String? grade;
  final double? weightedScore;
  final int violationCount;

  final List<ReportQuestion> questions;
  final List<ReportViolation> violations;
  final List<ViolationBreakdown> violationBreakdown;
  final List<TimelineEvent> timeline;
  final List<ReportAttemptRef> attempts;

  /// Pemakaian waktu 0..1 terhadap batas waktu peserta.
  double get timeUsage => allowedSeconds <= 0
      ? 0
      : (durationSeconds / allowedSeconds).clamp(0, 1).toDouble();
}
