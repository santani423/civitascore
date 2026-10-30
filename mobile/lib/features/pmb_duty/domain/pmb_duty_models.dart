import '../../../core/network/api_envelope.dart';

/// Attendance of a PMB exam participant (`AttendanceStatus` enum backend).
enum PmbAttendance {
  registered('registered'),
  present('present'),
  absent('absent'),
  excused('excused'),
  disqualified('disqualified');

  const PmbAttendance(this.value);
  final String value;

  static PmbAttendance parse(Object? v) => PmbAttendance.values.firstWhere(
    (e) => e.value == v,
    orElse: () => PmbAttendance.registered,
  );

  /// Absent/excused/disqualified participants are skipped by
  /// `ScoreService::inputSessionScores`; registered ones become present.
  bool get scorable => this == registered || this == present;
}

/// Role of the signed-in lecturer on a session (`exam_session_examiners`).
enum PmbExaminerRole {
  proctor('proctor'),
  examiner('examiner'),
  interviewer('interviewer');

  const PmbExaminerRole(this.value);
  final String value;

  static PmbExaminerRole? tryParse(Object? v) {
    for (final r in PmbExaminerRole.values) {
      if (r.value == v) return r;
    }
    return null;
  }
}

Set<PmbExaminerRole> _roles(Object? list) => {
  for (final r in asStringList(list)) ?PmbExaminerRole.tryParse(r),
};

/// Only examiners/interviewers may input scores; proctors only take
/// attendance (`ExamService::assertSessionStaff`).
bool canScoreWith(Set<PmbExaminerRole> roles) =>
    roles.contains(PmbExaminerRole.examiner) ||
    roles.contains(PmbExaminerRole.interviewer);

final _cardToken = RegExp(r'^[^.\s]+\.[^.\s]+\.[0-9a-f]{32}$');

/// Exam card QR payload `participantId.sessionId.hmac` (`ExamService::cardToken`);
/// rejects unrelated QR codes before calling the API.
bool looksLikeCardToken(String value) => _cardToken.hasMatch(value.trim());

/// Attendance changes more than 2 hours after the session ends are reserved
/// for the selection committee (`pmb_scores.import`), see `ExamService::checkIn`.
bool attendanceClosedAt(DateTime? endsAt, DateTime now) =>
    endsAt != null && now.isAfter(endsAt.add(const Duration(hours: 2)));

/// Item of `GET /pmb/admin/examiner/sessions`.
class DutySession {
  const DutySession({
    required this.id,
    required this.code,
    this.startsAt,
    this.endsAt,
    this.schedule,
    this.mode,
    this.component,
    this.room,
    this.participantsCount = 0,
    this.scoresLocked = false,
    this.roles = const {},
  });

  factory DutySession.fromJson(Map<String, dynamic> j) => DutySession(
    id: asString(j['id']) ?? '',
    code: asString(j['code']) ?? '',
    startsAt: asDateTime(j['starts_at']),
    endsAt: asDateTime(j['ends_at']),
    schedule: asString(j['schedule']),
    mode: asString(j['mode']),
    component: asString(j['component']),
    room: asString(j['room']),
    participantsCount: asInt(j['participants_count']) ?? 0,
    scoresLocked: asBool(j['scores_locked']),
    roles: _roles(j['roles']),
  );

  final String id;
  final String code;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final String? schedule;
  final String? mode;
  final String? component;
  final String? room;
  final int participantsCount;
  final bool scoresLocked;
  final Set<PmbExaminerRole> roles;

  bool get canScore => canScoreWith(roles);

  /// Not finished yet (ongoing or upcoming).
  bool isActiveAt(DateTime now) => endsAt == null || !now.isAfter(endsAt!);

  bool isOngoingAt(DateTime now) =>
      startsAt != null && !now.isBefore(startsAt!) && isActiveAt(now);
}

/// Splits sessions into active (soonest first) and finished (latest first).
({List<DutySession> active, List<DutySession> finished}) splitSessions(
  List<DutySession> sessions,
  DateTime now,
) {
  final active = sessions.where((s) => s.isActiveAt(now)).toList()
    ..sort((a, b) => _cmp(a.startsAt, b.startsAt));
  final finished = sessions.where((s) => !s.isActiveAt(now)).toList()
    ..sort((a, b) => _cmp(b.startsAt, a.startsAt));
  return (active: active, finished: finished);
}

int _cmp(DateTime? a, DateTime? b) {
  if (a == null && b == null) return 0;
  if (a == null) return 1;
  if (b == null) return -1;
  return a.compareTo(b);
}

class SessionParticipant {
  const SessionParticipant({
    required this.id,
    required this.number,
    this.name,
    required this.attendance,
    this.checkedInAt,
    this.note,
  });

  factory SessionParticipant.fromJson(Map<String, dynamic> j) =>
      SessionParticipant(
        id: asString(j['id']) ?? '',
        number: asString(j['participant_number']) ?? '',
        name: asString(j['name']),
        attendance: PmbAttendance.parse(j['attendance_status']),
        checkedInAt: asDateTime(j['checked_in_at']),
        note: asString(j['note']),
      );

  final String id;
  final String number;
  final String? name;
  final PmbAttendance attendance;
  final DateTime? checkedInAt;
  final String? note;

  String get displayName => name ?? number;

  bool matches(String query) {
    final q = query.trim().toLowerCase();
    if (q.isEmpty) return true;
    return number.toLowerCase().contains(q) ||
        (name?.toLowerCase().contains(q) ?? false);
  }
}

/// `GET /pmb/admin/exam-sessions/{id}` (proctor view). Applicant data is
/// deliberately minimal: number + name only.
class SessionDetail {
  const SessionDetail({
    required this.id,
    required this.code,
    this.startsAt,
    this.endsAt,
    this.checkinOpensAt,
    this.capacity = 0,
    this.scheduleTitle,
    this.mode,
    this.roomName,
    this.venueName,
    this.onlineUrl,
    this.scoresLockedAt,
    this.examiners = const [],
    this.participants = const [],
  });

  factory SessionDetail.fromJson(Map<String, dynamic> j) {
    final schedule = asMap(j['schedule']);
    final room = asMap(j['room']);
    return SessionDetail(
      id: asString(j['id']) ?? '',
      code: asString(j['code']) ?? '',
      startsAt: asDateTime(j['starts_at']),
      endsAt: asDateTime(j['ends_at']),
      checkinOpensAt: asDateTime(j['checkin_opens_at']),
      capacity: asInt(j['capacity']) ?? 0,
      scheduleTitle: asString(schedule['title']),
      mode: asString(schedule['mode']),
      roomName: asString(room['name']),
      venueName: asString(asMap(room['venue'])['name']),
      onlineUrl: asString(j['online_url']),
      scoresLockedAt: asDateTime(j['scores_locked_at']),
      examiners: asMapList(j['examiners']).map(SessionStaff.fromJson).toList(),
      participants: asMapList(
        j['participants'],
      ).map(SessionParticipant.fromJson).toList(),
    );
  }

  final String id;
  final String code;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final DateTime? checkinOpensAt;
  final int capacity;
  final String? scheduleTitle;
  final String? mode;
  final String? roomName;
  final String? venueName;
  final String? onlineUrl;
  final DateTime? scoresLockedAt;
  final List<SessionStaff> examiners;
  final List<SessionParticipant> participants;

  String? get roomLabel =>
      roomName == null ? null : [?venueName, roomName!].join(' — ');

  Set<PmbExaminerRole> rolesOf(String userId) => {
    for (final e in examiners)
      if (e.userId == userId) ?e.role,
  };

  int count(PmbAttendance a) =>
      participants.where((p) => p.attendance == a).length;

  SessionDetail withParticipant(SessionParticipant updated) => SessionDetail(
    id: id,
    code: code,
    startsAt: startsAt,
    endsAt: endsAt,
    checkinOpensAt: checkinOpensAt,
    capacity: capacity,
    scheduleTitle: scheduleTitle,
    mode: mode,
    roomName: roomName,
    venueName: venueName,
    onlineUrl: onlineUrl,
    scoresLockedAt: scoresLockedAt,
    examiners: examiners,
    participants: [
      for (final p in participants) p.id == updated.id ? updated : p,
    ],
  );
}

class SessionStaff {
  const SessionStaff({required this.userId, this.name, this.role});

  factory SessionStaff.fromJson(Map<String, dynamic> j) => SessionStaff(
    userId: asString(j['user_id']) ?? '',
    name: asString(asMap(j['user'])['name']),
    role: PmbExaminerRole.tryParse(j['role']),
  );

  final String userId;
  final String? name;
  final PmbExaminerRole? role;
}

/// Rubric criterion of a selection component (`rubric.criteria[]`).
class RubricCriterion {
  const RubricCriterion({
    required this.key,
    this.label,
    required this.weight,
    this.max = 100,
  });

  factory RubricCriterion.fromJson(Map<String, dynamic> j) => RubricCriterion(
    key: asString(j['key']) ?? '',
    label: asString(j['label']),
    weight: asDouble(j['weight']) ?? 0,
    max: asDouble(j['max']) ?? 100,
  );

  final String key;
  final String? label;
  final double weight;
  final double max;

  String get title => label ?? key;
}

class ScoreComponent {
  const ScoreComponent({
    required this.name,
    this.code,
    this.source,
    this.scoreType,
    this.rawMin,
    this.rawMax,
    this.criteria = const [],
  });

  factory ScoreComponent.fromJson(Map<String, dynamic> j) => ScoreComponent(
    name: asString(j['name']) ?? '',
    code: asString(j['code']),
    source: asString(j['source']),
    scoreType: asString(j['score_type']),
    rawMin: asDouble(j['raw_min']),
    rawMax: asDouble(j['raw_max']),
    criteria: asMapList(
      asMap(j['rubric'])['criteria'],
    ).map(RubricCriterion.fromJson).where((c) => c.key.isNotEmpty).toList(),
  );

  final String name;
  final String? code;
  final String? source;
  final String? scoreType;
  final double? rawMin;
  final double? rawMax;
  final List<RubricCriterion> criteria;

  bool get isPassFail => scoreType == 'pass_fail';
  bool get hasRubric => criteria.isNotEmpty;

  /// Interview/portfolio scores are kept per examiner; other components
  /// share one row (`examiner_user_id` null), see `ScoreService`.
  bool get isMultiExaminer => source == 'interview' || source == 'portfolio';

  /// Same formula as `SelectionCalculator::rubricRaw` for one examiner:
  /// weighted mean of every criterion, rounded to 4 decimals like the
  /// backend (otherwise e.g. 100.00000000000001 fails the range check);
  /// null until all are filled.
  double? rubricRaw(Map<String, double> scores) {
    var sum = 0.0;
    var weights = 0.0;
    for (final c in criteria) {
      final v = scores[c.key];
      if (v == null) return null;
      sum += c.weight * v;
      weights += c.weight;
    }
    if (weights <= 0) return null;
    return (sum / weights * 10000).roundToDouble() / 10000;
  }

  double get minScore => rawMin ?? 0;
  double get maxScore => rawMax ?? 100;

  /// Range check of `ScoreService::assertRange` (numeric components only).
  bool inRange(double raw) =>
      scoreType != 'numeric' || (raw >= minScore && raw <= maxScore);
}

/// The examiner's own latest score for a participant.
class ExistingScore {
  const ExistingScore({
    this.rawScore,
    this.passed,
    this.rubricScores = const {},
    this.note,
  });

  factory ExistingScore.fromJson(Map<String, dynamic> j) => ExistingScore(
    rawScore: asDouble(j['raw_score']),
    passed: j['passed'] == null ? null : asBool(j['passed']),
    rubricScores: {
      for (final e in asMap(j['rubric_scores']).entries)
        if (asDouble(e.value) != null) e.key: asDouble(e.value)!,
    },
    note: asString(j['note']),
  );

  final double? rawScore;
  final bool? passed;
  final Map<String, double> rubricScores;
  final String? note;

  bool get isEmpty =>
      rawScore == null && passed == null && rubricScores.isEmpty;
}

class ScoreRow {
  const ScoreRow({
    required this.participantId,
    required this.number,
    this.name,
    required this.attendance,
    this.score,
  });

  /// [userId] picks the caller's own score: users with `pmb_exams.update`
  /// receive every examiner's rows, and a shared (null-examiner) row from an
  /// import must not count as an interviewer's own score.
  factory ScoreRow.fromJson(
    Map<String, dynamic> j, {
    required String userId,
    required bool multiExaminer,
  }) {
    Map<String, dynamic>? own;
    Map<String, dynamic>? shared;
    for (final s in asMapList(j['scores'])) {
      final examiner = asString(s['examiner_user_id']);
      if (examiner == userId) own = s;
      if (examiner == null) shared = s;
    }
    final mine = multiExaminer ? own : (shared ?? own);
    return ScoreRow(
      participantId: asString(j['participant_id']) ?? '',
      number: asString(j['participant_number']) ?? '',
      name: asString(j['name']),
      attendance: PmbAttendance.parse(j['attendance_status']),
      score: mine == null ? null : ExistingScore.fromJson(mine),
    );
  }

  final String participantId;
  final String number;
  final String? name;
  final PmbAttendance attendance;
  final ExistingScore? score;

  String get displayName => name ?? number;
  bool get scored => score != null && !score!.isEmpty;
}

/// `GET /pmb/admin/exam-sessions/{id}/scores`.
class ScoreSheet {
  const ScoreSheet({
    required this.sessionId,
    required this.code,
    this.startsAt,
    this.scoresLockedAt,
    required this.component,
    this.rows = const [],
  });

  factory ScoreSheet.fromJson(
    Map<String, dynamic> j, {
    required String userId,
  }) {
    final session = asMap(j['session']);
    final component = ScoreComponent.fromJson(asMap(j['component']));
    return ScoreSheet(
      sessionId: asString(session['id']) ?? '',
      code: asString(session['code']) ?? '',
      startsAt: asDateTime(session['starts_at']),
      scoresLockedAt: asDateTime(session['scores_locked_at']),
      component: component,
      rows: [
        for (final r in asMapList(j['rows']))
          ScoreRow.fromJson(
            r,
            userId: userId,
            multiExaminer: component.isMultiExaminer,
          ),
      ],
    );
  }

  final String sessionId;
  final String code;
  final DateTime? startsAt;
  final DateTime? scoresLockedAt;
  final ScoreComponent component;
  final List<ScoreRow> rows;

  bool get locked => scoresLockedAt != null;

  /// Scored rows among the scorable ones (a score on a participant who was
  /// later marked absent/disqualified no longer counts).
  int get scoredCount =>
      rows.where((r) => r.attendance.scorable && r.scored).length;
}

/// One row of `PUT /pmb/admin/exam-sessions/{id}/scores`.
class ScoreInput {
  const ScoreInput({
    required this.participantId,
    this.rawScore,
    this.rubricScores,
    this.passed,
    this.note,
  });

  final String participantId;
  final double? rawScore;
  final Map<String, double>? rubricScores;
  final bool? passed;
  final String? note;

  Map<String, dynamic> toJson() => {
    'participant_id': participantId,
    'raw_score': rawScore,
    'rubric_scores': rubricScores,
    'passed': passed,
    'note': (note == null || note!.trim().isEmpty) ? null : note!.trim(),
  };
}
