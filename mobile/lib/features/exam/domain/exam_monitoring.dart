import '../../../core/network/api_envelope.dart';

enum ParticipantStatus {
  notStarted('not_started'),
  inProgress('in_progress'),
  completed('completed');

  const ParticipantStatus(this.apiValue);
  final String apiValue;

  static ParticipantStatus fromApi(String? v) => values.firstWhere(
    (e) => e.apiValue == v,
    orElse: () => ParticipantStatus.notStarted,
  );
}

/// `ExamParticipantResource`. Scores are decimal strings or null; the backend
/// is the single source of truth (never recomputed on the client).
class ExamParticipant {
  const ExamParticipant({
    required this.krsItemId,
    required this.studentName,
    required this.studentNim,
    required this.attemptsUsed,
    required this.status,
    this.score,
    this.rawScore,
    this.penaltyScore,
    this.grade,
    this.weightedScore,
    required this.violationCount,
    this.latestAttemptId,
    this.submittedAt,
  });

  factory ExamParticipant.fromJson(Map<String, dynamic> json) =>
      ExamParticipant(
        krsItemId: asString(json['krs_item_id']) ?? '',
        studentName: asString(json['student_name']) ?? '',
        studentNim: asString(json['student_nim']) ?? '',
        attemptsUsed: asInt(json['attempts_used']) ?? 0,
        status: ParticipantStatus.fromApi(asString(json['status'])),
        score: asDouble(json['score']),
        rawScore: asDouble(json['raw_score']),
        penaltyScore: asDouble(json['penalty_score']),
        grade: asString(json['grade']),
        weightedScore: asDouble(json['weighted_score']),
        violationCount: asInt(json['violation_count']) ?? 0,
        latestAttemptId: asString(json['latest_attempt_id']),
        submittedAt: asDateTime(json['submitted_at']),
      );

  final String krsItemId;
  final String studentName;
  final String studentNim;
  final int attemptsUsed;
  final ParticipantStatus status;
  final double? score;
  final double? rawScore;
  final double? penaltyScore;
  final String? grade;
  final double? weightedScore;
  final int violationCount;
  final String? latestAttemptId;
  final DateTime? submittedAt;
}

/// `ExamViolationResource`.
class ExamViolation {
  const ExamViolation({
    required this.id,
    required this.attemptId,
    required this.sequenceNumber,
    required this.type,
    this.label,
    required this.penaltyPoints,
    this.occurredAt,
    this.studentName,
    this.studentNim,
  });

  factory ExamViolation.fromJson(Map<String, dynamic> json) => ExamViolation(
    id: asString(json['id']) ?? '',
    attemptId: asString(json['exam_attempt_id']) ?? '',
    sequenceNumber: asInt(json['sequence_number']) ?? 0,
    type: asString(json['violation_type']) ?? '',
    label: asString(json['violation_label']),
    penaltyPoints: asDouble(json['penalty_points']) ?? 0,
    occurredAt: asDateTime(json['occurred_at']),
    studentName: asString(json['student_name']),
    studentNim: asString(json['student_nim']),
  );

  final String id;
  final String attemptId;
  final int sequenceNumber;
  final String type;
  final String? label;
  final double penaltyPoints;
  final DateTime? occurredAt;
  final String? studentName;
  final String? studentNim;
}

/// `GET /exam-attempts/{id}/violations`.
class AttemptViolations {
  const AttemptViolations({
    required this.studentName,
    required this.studentNim,
    this.rawScore,
    this.penaltyScore,
    this.score,
    this.grade,
    this.weightedScore,
    required this.violations,
  });

  factory AttemptViolations.fromJson(Map<String, dynamic> json) {
    final student = asMap(json['student']);
    return AttemptViolations(
      studentName: asString(student['name']) ?? '',
      studentNim: asString(student['nim']) ?? '',
      rawScore: asDouble(json['raw_score']),
      penaltyScore: asDouble(json['penalty_score']),
      score: asDouble(json['score']),
      grade: asString(json['grade']),
      weightedScore: asDouble(json['weighted_score']),
      violations: asMapList(
        json['violations'],
      ).map(ExamViolation.fromJson).toList(),
    );
  }

  final String studentName;
  final String studentNim;
  final double? rawScore;
  final double? penaltyScore;
  final double? score;
  final String? grade;
  final double? weightedScore;
  final List<ExamViolation> violations;
}

class RecapSummary {
  const RecapSummary({
    required this.totalParticipants,
    required this.completed,
    required this.inProgress,
    required this.notStarted,
    this.averageScore,
    this.highestScore,
    this.lowestScore,
  });

  factory RecapSummary.fromJson(Map<String, dynamic> json) => RecapSummary(
    totalParticipants: asInt(json['total_participants']) ?? 0,
    completed: asInt(json['completed']) ?? 0,
    inProgress: asInt(json['in_progress']) ?? 0,
    notStarted: asInt(json['not_started']) ?? 0,
    averageScore: asDouble(json['average_score']),
    highestScore: asDouble(json['highest_score']),
    lowestScore: asDouble(json['lowest_score']),
  );

  final int totalParticipants;
  final int completed;
  final int inProgress;
  final int notStarted;
  final double? averageScore;
  final double? highestScore;
  final double? lowestScore;
}

/// `GET /exams/{id}/recap` — `{summary, participants[]}`.
class ExamRecap {
  const ExamRecap({required this.summary, required this.participants});

  factory ExamRecap.fromJson(Map<String, dynamic> json) => ExamRecap(
    summary: RecapSummary.fromJson(asMap(json['summary'])),
    participants: asMapList(
      json['participants'],
    ).map(ExamParticipant.fromJson).toList(),
  );

  final RecapSummary summary;
  final List<ExamParticipant> participants;
}
