import 'package:civitasone/features/teaching/data/teaching_api.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';

const _cls = {
  'id': '01CLS',
  'class_code': 'A',
  'course': {
    'id': 'c1',
    'code': 'IF101',
    'name': 'Algoritma',
    'credits': 3,
    'semester_level': 1,
  },
  'lecturer': {'id': 'l1', 'name': 'Dr. Maria', 'email': null},
  'schedules': [
    {
      'id': 's1',
      'day_of_week': 1,
      'day_label': 'Senin',
      'start_time': '08:00',
      'end_time': '10:00',
      'room': null,
    },
  ],
  'term': {'id': 't1', 'label': '2026/2027 Ganjil'},
  'capacity': 40,
  'enrolled_count': 2,
  'materials_count': 1,
  'assignments_count': 0,
};

void main() {
  test('classes parse with nulls', () async {
    final f = FakeApi()
      ..get(
        '/teaching/classes',
        [_cls],
        meta: {'current_page': 1, 'per_page': 100, 'total': 1, 'last_page': 1},
      );
    final api = TeachingApi(f.client());
    final list = await api.classes();
    expect(list.single.courseName, 'Algoritma');
    expect(list.single.schedules.single.room, isNull);
    expect(list.single.enrolledCount, 2);
    expect(f.requests.single.query['per_page'], '100');
  });

  test('roster filters enrolled krs items of the class', () async {
    final f = FakeApi()
      ..get('/krs-items', [
        {
          'id': 'k2',
          'student_id': 's2',
          'student_name': 'Budi',
          'student_nim': '2102',
          'letter_grade': null,
        },
        {
          'id': 'k1',
          'student_id': 's1',
          'student_name': 'Ani',
          'student_nim': '2101',
        },
      ]);
    final r = await TeachingApi(f.client()).roster('01CLS');
    expect(r.first.nim, '2101');
    expect(r.first.studentId, 's1');
    final q = f.requests.single.query;
    expect(q['filter[class_section_id]'], '01CLS');
    expect(q['filter[status]'], 'enrolled');
  });

  test('material and assignment mutations hit the right endpoints', () async {
    final f = FakeApi()
      ..on(
        'POST',
        '/class-sections/{id}/materials',
        (_) => FakeResponse.ok({'id': 'm1', 'title': 'T', 'type': 'link'}),
      )
      ..on(
        'PUT',
        '/course-materials/{id}',
        (_) => FakeResponse.ok({'id': 'm1', 'title': 'T', 'type': 'link'}),
      )
      ..on('DELETE', '/course-materials/{id}', (_) => FakeResponse.ok(null))
      ..on(
        'POST',
        '/class-sections/{id}/assignments',
        (_) => FakeResponse.ok({
          'id': 'a1',
          'title': 'x',
          'max_score': '100.00',
          'due_at': '2026-12-01T10:00:00+07:00',
        }),
      )
      ..on('DELETE', '/assignments/{id}', (_) => FakeResponse.ok(null))
      ..on(
        'PUT',
        '/assignment-submissions/{id}/grade',
        (_) => FakeResponse.ok({
          'id': 'sub1',
          'score': '80.50',
          'is_late': false,
          'submission_count': 1,
        }),
      );
    final api = TeachingApi(f.client());
    await api.createMaterial('01CLS', {'title': 'T', 'type': 'link'});
    await api.updateMaterial('m1', {'is_published': false});
    await api.deleteMaterial('m1');
    final a = await api.createAssignment('01CLS', {'title': 'x'});
    expect(a.maxScore, 100);
    await api.deleteAssignment('a1');
    final g = await api.gradeSubmission('sub1', score: 80.5, feedback: 'ok');
    expect(g.score, 80.5);
    expect(f.requests.map((r) => '${r.method} ${r.path}').toList(), [
      'POST /class-sections/01CLS/materials',
      'PUT /course-materials/m1',
      'DELETE /course-materials/m1',
      'POST /class-sections/01CLS/assignments',
      'DELETE /assignments/a1',
      'PUT /assignment-submissions/sub1/grade',
    ]);
    expect(f.requests[1].body, {'is_published': false});
    expect(f.requests.last.body, {'score': 80.5, 'feedback': 'ok'});
  });

  test('transcript parses decimal strings', () async {
    final f = FakeApi()
      ..get('/students/s1/transcript', {
        'terms': [
          {
            'academic_term_id': 't',
            'label': '2025 Ganjil',
            'sks': 20,
            'ip': '3.50',
          },
        ],
        'ipk': 3.5,
        'total_sks': 20,
        'passed_sks': 18,
      });
    final t = await TeachingApi(f.client()).transcript('s1');
    expect(t.terms.single.ip, 3.5);
    expect(t.passedSks, 18);
  });
}
