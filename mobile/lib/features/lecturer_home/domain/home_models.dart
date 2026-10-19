import '../../../core/network/api_envelope.dart';

class TeachingSlot {
  const TeachingSlot({
    required this.classId,
    required this.courseName,
    required this.classCode,
    required this.dayOfWeek,
    this.startTime,
    this.endTime,
    this.room,
  });
  final String classId;
  final String courseName;
  final String? classCode;
  final int dayOfWeek;
  final String? startTime;
  final String? endTime;
  final String? room;
}

class TeachingClass {
  const TeachingClass({
    required this.id,
    required this.courseName,
    this.classCode,
    this.enrolledCount = 0,
    this.slots = const [],
  });

  factory TeachingClass.fromJson(Map<String, dynamic> j) {
    final id = asString(j['id']) ?? '';
    final name = asString(asMap(j['course'])['name']) ?? '—';
    final code = asString(j['class_code']);
    return TeachingClass(
      id: id,
      courseName: name,
      classCode: code,
      enrolledCount: asInt(j['enrolled_count']) ?? 0,
      slots: asMapList(j['schedules'])
          .map(
            (s) => TeachingSlot(
              classId: id,
              courseName: name,
              classCode: code,
              dayOfWeek: asInt(s['day_of_week']) ?? 0,
              startTime: asString(s['start_time']),
              endTime: asString(s['end_time']),
              room: asString(s['room']),
            ),
          )
          .toList(),
    );
  }

  final String id;
  final String courseName;
  final String? classCode;
  final int enrolledCount;
  final List<TeachingSlot> slots;
}

/// Slots on [isoWeekday] sorted by start time.
List<TeachingSlot> slotsForDay(List<TeachingClass> classes, int isoWeekday) {
  final list = [
    for (final c in classes)
      for (final s in c.slots)
        if (s.dayOfWeek == isoWeekday) s,
  ];
  list.sort((a, b) => (a.startTime ?? '').compareTo(b.startTime ?? ''));
  return list;
}

/// First slot today that has not ended at [now] ("HH:MM" comparison).
TeachingSlot? nextSlot(List<TeachingSlot> today, DateTime now) {
  final hm =
      '${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}';
  for (final s in today) {
    if ((s.endTime ?? '99:99').compareTo(hm) > 0) return s;
  }
  return null;
}

class PendingKrs {
  const PendingKrs({
    required this.id,
    this.studentName,
    this.nim,
    this.credits,
  });
  factory PendingKrs.fromJson(Map<String, dynamic> j) {
    final st = asMap(j['student']);
    return PendingKrs(
      id: asString(j['id']) ?? '',
      studentName: asString(st['name']),
      nim: asString(st['nim']),
      credits: asInt(j['total_credits']),
    );
  }
  final String id;
  final String? studentName;
  final String? nim;
  final int? credits;
}

class PendingKrsSummary {
  const PendingKrsSummary({required this.total, required this.preview});
  final int total;
  final List<PendingKrs> preview;
}
