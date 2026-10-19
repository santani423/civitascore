import '../../helpers/fake_api.dart';

Map<String, dynamic> examJson({
  String id = 'e1',
  String title = 'UTS Algoritma',
  bool published = false,
  int pool = 12,
  int qpp = 10,
  String mode = 'random',
  String? token,
  String? weight = '30.00',
  String classId = 'c1',
}) => {
  'id': id,
  'class_section_id': classId,
  'class_code': 'TI-A',
  'course_name': 'Algoritma',
  'title': title,
  'duration_minutes': 90,
  'starts_at': '2026-11-02T01:00:00+00:00',
  'ends_at': null,
  'creator_name': 'Dr. Maria',
  'question_pool_size': pool,
  'questions_per_participant': qpp,
  'question_selection_mode': mode,
  'randomize_questions': true,
  'randomize_options': false,
  'allow_back_navigation': true,
  'show_result_after_submission': true,
  'max_attempts': 1,
  'is_published': published,
  'published_at': published ? '2026-10-20T01:00:00+00:00' : null,
  'access_token': token,
  'access_token_generated_at': null,
  'weight_percentage': weight,
  'created_at': '2026-10-19T01:00:00+00:00',
};

Map<String, dynamic> questionJson(
  String id, {
  bool selected = true,
  String text = 'Apa itu rekursi?',
}) => {
  'id': id,
  'exam_id': 'e1',
  'question_bank_item_id': null,
  'question_text': text,
  'points': '1.00',
  'order_index': 0,
  'is_selected': selected,
  'options': [
    {
      'id': 'o1',
      'option_text': 'Fungsi memanggil dirinya',
      'is_correct': true,
      'order_index': 0,
    },
    {
      'id': 'o2',
      'option_text': 'Perulangan',
      'is_correct': false,
      'order_index': 1,
    },
  ],
};

Map<String, dynamic> participantJson(
  String nim,
  String name,
  String status, {
  String? attemptId,
  int violations = 0,
}) => {
  'krs_item_id': 'k$nim',
  'student_id': 's$nim',
  'student_name': name,
  'student_nim': nim,
  'attempts_used': status == 'not_started' ? 0 : 1,
  'status': status,
  'score': status == 'completed' ? '78.00' : null,
  'raw_score': status == 'completed' ? '80.00' : null,
  'penalty_score': status == 'completed' ? '2.00' : null,
  'grade': status == 'completed' ? 'AB' : null,
  'weighted_score': status == 'completed' ? '23.40' : null,
  'violation_count': violations,
  'latest_attempt_id': attemptId,
  'submitted_at': null,
};

Map<String, dynamic> recapJson() => {
  'summary': {
    'exam_title': 'UTS Algoritma',
    'course_name': 'Algoritma',
    'class_code': 'TI-A',
    'semester_label': '2026/2027',
    'total_participants': 3,
    'completed': 1,
    'in_progress': 1,
    'not_started': 1,
    'average_score': 78,
    'highest_score': '78.00',
    'lowest_score': 78,
  },
  'participants': [
    participantJson(
      '2101001',
      'Andi Pratama',
      'completed',
      attemptId: 'a1',
      violations: 2,
    ),
    participantJson('2101002', 'Budi Santoso', 'in_progress', attemptId: 'a2'),
    participantJson('2101003', 'Citra Dewi', 'not_started'),
  ],
};

Map<String, dynamic> classJson() => {
  'id': 'c1',
  'class_code': 'TI-A',
  'course_id': 'crs1',
  'course_name': 'Algoritma',
  'course_code': 'TI101',
  'academic_term_label': '2026/2027 Ganjil',
  'lecturer_id': 'lec1',
};

/// Rute dasar: profil dosen + satu kelas milik dosen.
void ownClassRoutes(FakeApi api) {
  api
    ..get('/lecturer-profile', {'id': 'lec1', 'name': 'Dr. Maria'})
    ..get('/class-sections', [classJson()]);
}
