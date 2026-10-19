import '../../../core/network/api_envelope.dart';

/// Student status of an exam (`ExamService::computeStudentStatus`).
enum StudentExamStatus {
  upcoming,
  available,
  inProgress,
  completed,
  expired,
  unknown;

  static StudentExamStatus parse(String? v) => switch (v) {
    'upcoming' => upcoming,
    'available' => available,
    'in_progress' => inProgress,
    'completed' => completed,
    'expired' => expired,
    _ => unknown,
  };
}

/// Violation types accepted by `POST .../violations`.
enum ExamViolationType {
  tabSwitch('tab_switch'),
  windowBlur('window_blur'),
  fullscreenExit('fullscreen_exit'),
  copyAttempt('copy_attempt'),
  pasteAttempt('paste_attempt'),
  contextMenu('context_menu');

  const ExamViolationType(this.wire);
  final String wire;
}

/// `StudentExamResource`.
class StudentExam {
  const StudentExam({
    required this.id,
    required this.title,
    this.courseName,
    this.lecturerName,
    this.durationMinutes = 0,
    this.startsAt,
    this.endsAt,
    this.questionsPerParticipant,
    this.allowBackNavigation = true,
    this.maxAttempts = 1,
    this.attemptsUsed = 0,
    this.status = StudentExamStatus.unknown,
    this.rawStatus,
    this.resultVisible = false,
    this.score,
    this.latestAttemptId,
  });

  factory StudentExam.fromJson(Map<String, dynamic> j) => StudentExam(
    id: asString(j['id']) ?? '',
    title: asString(j['title']) ?? '',
    courseName: asString(j['course_name']),
    lecturerName: asString(j['lecturer_name']),
    durationMinutes: asInt(j['duration_minutes']) ?? 0,
    startsAt: asDateTime(j['starts_at']),
    endsAt: asDateTime(j['ends_at']),
    questionsPerParticipant: asInt(j['questions_per_participant']),
    allowBackNavigation: asBool(j['allow_back_navigation'], fallback: true),
    maxAttempts: asInt(j['max_attempts']) ?? 1,
    attemptsUsed: asInt(j['attempts_used']) ?? 0,
    status: StudentExamStatus.parse(asString(j['status'])),
    rawStatus: asString(j['status']),
    resultVisible: asBool(j['result_visible']),
    score: asString(j['score']),
    latestAttemptId: asString(j['latest_attempt_id']),
  );

  final String id;
  final String title;
  final String? courseName;
  final String? lecturerName;
  final int durationMinutes;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final int? questionsPerParticipant;
  final bool allowBackNavigation;
  final int maxAttempts;
  final int attemptsUsed;
  final StudentExamStatus status;
  final String? rawStatus;
  final bool resultVisible;
  final String? score;
  final String? latestAttemptId;

  bool get canStart =>
      status == StudentExamStatus.available ||
      status == StudentExamStatus.inProgress;
  bool get isResuming => status == StudentExamStatus.inProgress;
  bool get hasFinishedAttempt =>
      latestAttemptId != null && status != StudentExamStatus.inProgress;
}

class AttemptOption {
  const AttemptOption({required this.id, this.text});
  factory AttemptOption.fromJson(Map<String, dynamic> j) => AttemptOption(
    id: asString(j['id']) ?? '',
    text: asString(j['option_text']),
  );
  final String id;
  final String? text;
}

class AttemptQuestion {
  const AttemptQuestion({
    required this.id,
    this.text,
    this.points,
    this.options = const [],
    this.selectedOptionId,
  });
  factory AttemptQuestion.fromJson(Map<String, dynamic> j) => AttemptQuestion(
    id: asString(j['id']) ?? '',
    text: asString(j['question_text']),
    points: asString(j['points']),
    options: asMapList(j['options']).map(AttemptOption.fromJson).toList(),
    selectedOptionId: asString(j['selected_option_id']),
  );
  final String id;
  final String? text;
  final String? points;
  final List<AttemptOption> options;
  final String? selectedOptionId;
}

/// `StudentExamAttemptResource`.
class ExamAttempt {
  const ExamAttempt({
    required this.id,
    required this.examId,
    this.attemptNumber = 1,
    this.submitted = false,
    this.startedAt,
    this.submittedAt,
    this.score,
    this.penaltyScore,
    this.violationCount = 0,
    this.grade,
    this.resultVisible = false,
    this.questions = const [],
  });

  factory ExamAttempt.fromJson(Map<String, dynamic> j) => ExamAttempt(
    id: asString(j['id']) ?? '',
    examId: asString(j['exam_id']) ?? '',
    attemptNumber: asInt(j['attempt_number']) ?? 1,
    submitted: asString(j['status']) == 'submitted',
    startedAt: asDateTime(j['started_at']),
    submittedAt: asDateTime(j['submitted_at']),
    score: asString(j['score']),
    penaltyScore: asString(j['penalty_score']),
    violationCount: asInt(j['violation_count']) ?? 0,
    grade: asString(j['grade']),
    resultVisible: asBool(j['result_visible']),
    questions: asMapList(j['questions']).map(AttemptQuestion.fromJson).toList(),
  );

  final String id;
  final String examId;
  final int attemptNumber;
  final bool submitted;
  final DateTime? startedAt;
  final DateTime? submittedAt;
  final String? score;
  final String? penaltyScore;
  final int violationCount;
  final String? grade;
  final bool resultVisible;
  final List<AttemptQuestion> questions;

  int get answeredCount =>
      questions.where((q) => q.selectedOptionId != null).length;
}

/// Effective deadline: earlier of `started_at + duration` and `exam.ends_at`
/// (same rule as `ExamService::attemptDeadline`). The attempt payload has no
/// deadline field, so it is derived client-side.
DateTime? computeAttemptDeadline(ExamAttempt attempt, StudentExam exam) {
  final started = attempt.startedAt;
  if (started == null) return null;
  final byDuration = started.add(Duration(minutes: exam.durationMinutes));
  final ends = exam.endsAt;
  return ends != null && ends.isBefore(byDuration) ? ends : byDuration;
}

class ResultQuestion {
  const ResultQuestion({
    required this.number,
    this.text,
    this.options = const [],
    this.selectedOptionId,
    this.correctOptionId,
    this.isCorrect = false,
    this.explanation,
  });
  factory ResultQuestion.fromJson(Map<String, dynamic> j) => ResultQuestion(
    number: asInt(j['number']) ?? 0,
    text: asString(j['question_text']),
    options: asMapList(j['options']).map(AttemptOption.fromJson).toList(),
    selectedOptionId: asString(j['selected_option_id']),
    correctOptionId: asString(j['correct_option_id']),
    isCorrect: asBool(j['is_correct']),
    explanation: asString(j['explanation']),
  );
  final int number;
  final String? text;
  final List<AttemptOption> options;
  final String? selectedOptionId;
  final String? correctOptionId;
  final bool isCorrect;
  final String? explanation;
}

/// `ExamService::buildAttemptResult`.
class ExamResult {
  const ExamResult({
    required this.attemptId,
    this.attemptNumber = 1,
    this.startedAt,
    this.submittedAt,
    this.durationSeconds,
    this.examTitle = '',
    this.courseName,
    this.totalQuestions = 0,
    this.correctAnswers = 0,
    this.wrongAnswers = 0,
    this.score,
    this.rawScore,
    this.penaltyScore,
    this.violationCount = 0,
    this.grade,
    this.weightedScore,
    this.questions = const [],
  });

  factory ExamResult.fromJson(Map<String, dynamic> j) {
    final a = asMap(j['attempt']);
    final s = asMap(j['summary']);
    final e = asMap(j['exam']);
    return ExamResult(
      attemptId: asString(a['id']) ?? '',
      attemptNumber: asInt(a['attempt_number']) ?? 1,
      startedAt: asDateTime(a['started_at']),
      submittedAt: asDateTime(a['submitted_at']),
      durationSeconds: asInt(a['duration_seconds']),
      examTitle: asString(e['title']) ?? '',
      courseName: asString(e['course_name']),
      totalQuestions: asInt(s['total_questions']) ?? 0,
      correctAnswers: asInt(s['correct_answers']) ?? 0,
      wrongAnswers: asInt(s['wrong_answers']) ?? 0,
      score: asString(s['score']),
      rawScore: asString(s['raw_score']),
      penaltyScore: asString(s['penalty_score']),
      violationCount: asInt(s['violation_count']) ?? 0,
      grade: asString(s['grade']),
      weightedScore: asString(s['weighted_score']),
      questions: asMapList(
        j['questions'],
      ).map(ResultQuestion.fromJson).toList(),
    );
  }

  final String attemptId;
  final int attemptNumber;
  final DateTime? startedAt;
  final DateTime? submittedAt;
  final int? durationSeconds;
  final String examTitle;
  final String? courseName;
  final int totalQuestions;
  final int correctAnswers;
  final int wrongAnswers;
  final String? score;
  final String? rawScore;
  final String? penaltyScore;
  final int violationCount;
  final String? grade;
  final String? weightedScore;
  final List<ResultQuestion> questions;
}
