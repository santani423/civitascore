import '../../../core/network/api_envelope.dart';

/// Attendance statuses accepted by the backend (`AttendanceStatus` enum).
enum AttendanceStatus {
  present('present'),
  permitted('permitted'),
  sick('sick'),
  absent('absent');

  const AttendanceStatus(this.value);
  final String value;

  static AttendanceStatus? fromValue(String? v) {
    for (final s in values) {
      if (s.value == v) return s;
    }
    return null;
  }
}

/// Letter grades accepted by the backend (`LetterGrade` enum).
const List<String> kLetterGrades = ['A', 'AB', 'B', 'BC', 'C', 'D', 'E'];

/// Minimum attendance percentage before a student is flagged.
const double kAttendanceThreshold = 75;

/// Active enrolment (KRS item) of a class — the roster row.
class RosterStudent {
  const RosterStudent({
    required this.krsItemId,
    required this.studentId,
    required this.name,
    required this.nim,
    this.letterGrade,
  });

  factory RosterStudent.fromJson(Map<String, dynamic> j) => RosterStudent(
    krsItemId: asString(j['id']) ?? '',
    studentId: asString(j['student_id']) ?? '',
    name: asString(j['student_name']) ?? '—',
    nim: asString(j['student_nim']) ?? '',
    letterGrade: asString(j['letter_grade']),
  );

  final String krsItemId;
  final String studentId;
  final String name;
  final String nim;
  final String? letterGrade;
}

/// One attendance record (student × meeting).
class AttendanceRecord {
  const AttendanceRecord({
    required this.id,
    required this.krsItemId,
    required this.studentName,
    required this.studentNim,
    required this.meetingNumber,
    required this.meetingDate,
    required this.statusValue,
    this.notes,
  });

  factory AttendanceRecord.fromJson(Map<String, dynamic> j) => AttendanceRecord(
    id: asString(j['id']) ?? '',
    krsItemId: asString(j['krs_item_id']) ?? '',
    studentName: asString(j['student_name']) ?? '—',
    studentNim: asString(j['student_nim']) ?? '',
    meetingNumber: asInt(j['meeting_number']) ?? 0,
    meetingDate: asDateTimeLocalDate(j['meeting_date']),
    statusValue: asString(j['status']) ?? '',
    notes: asString(j['notes']),
  );

  final String id;
  final String krsItemId;
  final String studentName;
  final String studentNim;
  final int meetingNumber;
  final DateTime? meetingDate;
  final String statusValue;
  final String? notes;

  AttendanceStatus? get status => AttendanceStatus.fromValue(statusValue);
}

/// Parses a `Y-m-d` date as a local calendar date (no timezone shifting).
DateTime? asDateTimeLocalDate(Object? v) {
  if (v is! String || v.length < 10) return null;
  final y = int.tryParse(v.substring(0, 4));
  final m = int.tryParse(v.substring(5, 7));
  final d = int.tryParse(v.substring(8, 10));
  if (y == null || m == null || d == null) return null;
  return DateTime(y, m, d);
}

String formatApiDate(DateTime d) =>
    '${d.year.toString().padLeft(4, '0')}-'
    '${d.month.toString().padLeft(2, '0')}-'
    '${d.day.toString().padLeft(2, '0')}';

/// A class meeting derived by grouping records on `meeting_number`.
class Meeting {
  const Meeting({
    required this.number,
    required this.date,
    required this.records,
  });

  final int number;
  final DateTime? date;
  final List<AttendanceRecord> records;

  int count(AttendanceStatus s) =>
      records.where((r) => r.status == s).length;

  AttendanceStatus? statusOf(String krsItemId) {
    for (final r in records) {
      if (r.krsItemId == krsItemId) return r.status;
    }
    return null;
  }
}

List<Meeting> groupMeetings(List<AttendanceRecord> records) {
  final byNumber = <int, List<AttendanceRecord>>{};
  for (final r in records) {
    byNumber.putIfAbsent(r.meetingNumber, () => []).add(r);
  }
  final numbers = byNumber.keys.toList()..sort();
  return [
    for (final n in numbers)
      Meeting(
        number: n,
        date: byNumber[n]!
            .map((r) => r.meetingDate)
            .whereType<DateTime>()
            .fold<DateTime?>(
              null,
              (a, b) => a == null || b.isAfter(a) ? b : a,
            ),
        records: byNumber[n]!,
      ),
  ];
}

/// Per-student attendance recap computed client-side.
class StudentRecap {
  const StudentRecap({
    required this.student,
    required this.present,
    required this.permitted,
    required this.sick,
    required this.absent,
  });

  final RosterStudent student;
  final int present;
  final int permitted;
  final int sick;
  final int absent;

  int get total => present + permitted + sick + absent;

  /// Present / recorded meetings × 100 (null when nothing is recorded).
  double? get percentage => total == 0 ? null : present * 100 / total;

  bool get atRisk => percentage != null && percentage! < kAttendanceThreshold;
}

List<StudentRecap> buildRecaps(
  List<RosterStudent> roster,
  List<AttendanceRecord> records,
) {
  final counts = <String, Map<AttendanceStatus, int>>{};
  for (final r in records) {
    final s = r.status;
    if (s == null) continue;
    final m = counts.putIfAbsent(r.krsItemId, () => {});
    m[s] = (m[s] ?? 0) + 1;
  }
  return [
    for (final st in roster)
      StudentRecap(
        student: st,
        present: counts[st.krsItemId]?[AttendanceStatus.present] ?? 0,
        permitted: counts[st.krsItemId]?[AttendanceStatus.permitted] ?? 0,
        sick: counts[st.krsItemId]?[AttendanceStatus.sick] ?? 0,
        absent: counts[st.krsItemId]?[AttendanceStatus.absent] ?? 0,
      ),
  ];
}

/// Data of the attendance screen.
class AttendanceSheet {
  const AttendanceSheet({required this.roster, required this.records});
  final List<RosterStudent> roster;
  final List<AttendanceRecord> records;

  List<Meeting> get meetings => groupMeetings(records);
  List<StudentRecap> get recaps => buildRecaps(roster, records);

  /// Next free meeting number (max + 1, capped to the backend limit 255).
  int get nextMeetingNumber {
    final max = records.fold<int>(
      0,
      (a, r) => r.meetingNumber > a ? r.meetingNumber : a,
    );
    return (max + 1).clamp(1, 255);
  }
}

/// A grade as returned by `GET /grades` / `PUT /krs-items/{id}/grade`.
class GradeEntry {
  const GradeEntry({
    required this.krsItemId,
    required this.score,
    required this.letterGrade,
  });

  factory GradeEntry.fromJson(Map<String, dynamic> j) => GradeEntry(
    krsItemId: asString(j['krs_item_id']) ?? '',
    score: asDouble(j['score']),
    letterGrade: asString(j['letter_grade']),
  );

  final String krsItemId;
  final double? score;
  final String? letterGrade;
}

/// Roster student joined with their grade (if any).
class GradeRow {
  const GradeRow({required this.student, this.score, this.letterGrade});

  final RosterStudent student;
  final double? score;
  final String? letterGrade;

  bool get graded => score != null || letterGrade != null;

  GradeRow withEntry(GradeEntry e) =>
      GradeRow(student: student, score: e.score, letterGrade: e.letterGrade);
}

List<GradeRow> joinGrades(List<RosterStudent> roster, List<GradeEntry> grades) {
  final byKrs = {for (final g in grades) g.krsItemId: g};
  return [
    for (final s in roster)
      GradeRow(
        student: s,
        score: byKrs[s.krsItemId]?.score,
        letterGrade: byKrs[s.krsItemId]?.letterGrade ?? s.letterGrade,
      ),
  ];
}
