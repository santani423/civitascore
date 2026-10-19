import '../../../core/network/api_envelope.dart';

/// Nilai huruf yang diterima backend (`LetterGrade`).
const kLetterGrades = ['A', 'AB', 'B', 'BC', 'C', 'D', 'E'];

/// Rentang default backend (`LetterGrade::fromScore`) — hanya nilai awal
/// editor bila ujian belum punya rentang kustom.
const kDefaultGradeRanges = <(String, double, double)>[
  ('E', 0, 34.99),
  ('D', 35, 44.99),
  ('C', 45, 54.99),
  ('BC', 55, 64.99),
  ('B', 65, 74.99),
  ('AB', 75, 84.99),
  ('A', 85, 100),
];

class GradeRange {
  const GradeRange({
    required this.grade,
    required this.minScore,
    required this.maxScore,
  });

  factory GradeRange.fromJson(Map<String, dynamic> json) => GradeRange(
    grade: asString(json['grade']) ?? '',
    minScore: asDouble(json['min_score']) ?? 0,
    maxScore: asDouble(json['max_score']) ?? 0,
  );

  final String grade;
  final double minScore;
  final double maxScore;

  Map<String, dynamic> toJson() => {
    'grade': grade,
    'min_score': minScore,
    'max_score': maxScore,
  };
}

/// Pemeriksaan klien rentang nilai (cermin UpsertExamGradeRangesRequest;
/// backend tetap sumber kebenaran).
enum GradeRangeIssue { empty, duplicateGrade, invalidBounds, overlap, gap }

abstract final class GradeRangeValidation {
  static GradeRangeIssue? check(List<GradeRange> ranges) {
    if (ranges.isEmpty) return GradeRangeIssue.empty;
    final grades = ranges.map((r) => r.grade).toList();
    if (grades.toSet().length != grades.length) {
      return GradeRangeIssue.duplicateGrade;
    }
    for (final r in ranges) {
      if (r.minScore < 0 || r.maxScore > 100 || r.maxScore < r.minScore) {
        return GradeRangeIssue.invalidBounds;
      }
    }
    final sorted = [...ranges]
      ..sort((a, b) => a.minScore.compareTo(b.minScore));
    for (var i = 1; i < sorted.length; i++) {
      final prev = sorted[i - 1];
      final cur = sorted[i];
      if (cur.minScore <= prev.maxScore) return GradeRangeIssue.overlap;
      // Tulis 79,99 bukan 79: celah > 0,01 berarti ada skor tak terjangkau.
      if (cur.minScore - prev.maxScore > 0.011) return GradeRangeIssue.gap;
    }
    return null;
  }
}
