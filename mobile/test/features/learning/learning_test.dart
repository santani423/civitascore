import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/learning/data/learning_api.dart';
import 'package:civitasone/features/learning/learning_routes.dart';
import 'package:civitasone/features/learning/presentation/screens/assignment_detail_screen.dart';
import 'package:civitasone/features/learning/presentation/screens/assignments_screen.dart';
import 'package:civitasone/features/learning/presentation/screens/materials_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

Map<String, dynamic> _cls() => {
  'id': 'CS1',
  'class_code': 'A',
  'course': {
    'id': 'C1',
    'code': 'IF101',
    'name': 'Algoritma',
    'credits': 3,
    'semester_level': 1,
  },
  'lecturer': {'id': 'L1', 'name': 'Dr. Budi', 'email': null},
  'schedules': [
    {
      'id': 'S1',
      'day_of_week': 1,
      'day_label': 'Senin',
      'start_time': '08:00',
      'end_time': '09:40',
      'room': 'R.201',
    },
  ],
};

Map<String, dynamic> _assignment({
  String status = 'not_submitted',
  bool canSubmit = true,
  Map<String, dynamic>? submission,
  bool pastDue = false,
}) => {
  'id': 'A1',
  'class_section_id': 'CS1',
  'title': 'Tugas Rekursi',
  'description': 'Kerjakan soal 1-5',
  'due_at': '2026-10-25T10:00:00+07:00',
  'allow_late_submission': true,
  'allow_resubmission': true,
  'max_file_size_mb': 5,
  'allowed_extensions': ['pdf', 'docx'],
  'max_score': '100.00',
  'is_published': true,
  'attachment': {'id': 'F1', 'name': 'soal.pdf', 'size_bytes': 2048},
  'course': {
    'code': 'IF101',
    'name': 'Algoritma',
    'class_code': 'A',
    'lecturer': 'Dr. Budi',
  },
  'status': status,
  'status_label': 'x',
  'is_past_due': pastDue,
  'can_submit': canSubmit,
  'submission': submission,
};

Map<String, dynamic> _material(String type, {String? url}) => {
  'id': 'M-$type',
  'meeting_number': 1,
  'title': 'Materi $type',
  'description': 'Isi teks',
  'type': type,
  'type_label': type,
  'url': url,
  'file': type == 'file'
      ? {
          'id': 'F9',
          'name': 'slide.pdf',
          'size_bytes': 1048576,
          'mime_type': 'application/pdf',
        }
      : null,
  'is_published': true,
};

FakeApi _api() => FakeApi()
  ..get('/student/courses', {
    'term': {'id': 'T1', 'label': 'Ganjil 2026/2027'},
    'courses': [
      {
        ..._cls(),
        'krs_item_id': 'K1',
        'materials_count': 4,
        'assignments_count': 2,
        'pending_assignments_count': 1,
        'attendance': {
          'total_meetings': 4,
          'present': 3,
          'permitted': 0,
          'sick': 0,
          'absent': 1,
          'percentage': 75.0,
        },
      },
    ],
  })
  ..get('/student/assignments', {
    'term': null,
    'assignments': [_assignment()],
    'counts': {
      'all': 1,
      'not_submitted': 1,
      'submitted': 0,
      'late': 0,
      'graded': 0,
      'missed': 0,
    },
  })
  ..get('/student/materials', {
    'term': null,
    'courses': [
      {
        ..._cls(),
        'materials': [_material('file'), _material('text')],
      },
    ],
  });

void main() {
  test('parses assignment with graded submission and decimal strings', () {
    final api = FakeApi()
      ..get(
        '/student/assignments/A1',
        _assignment(
          status: 'graded',
          canSubmit: false,
          submission: {
            'id': 'SUB',
            'notes': null,
            'submitted_at': '2026-10-20T10:00:00+07:00',
            'is_late': false,
            'submission_count': 2,
            'score': '90.00',
            'feedback': 'Bagus',
            'graded_at': '2026-10-21T10:00:00+07:00',
            'graded_by': 'Dr. Budi',
            'file': {'id': 'F2', 'name': 'jawab.pdf', 'size_bytes': 10},
          },
        ),
      );
    return LearningApi(api.client()).assignment('A1').then((a) {
      expect(a.isGraded, isTrue);
      expect(a.submission!.score, '90.00');
      expect(a.submission!.file!.downloadPath, '/file-uploads/F2/download');
      expect(a.allowedExtensions, ['pdf', 'docx']);
    });
  });

  test('submit posts JSON body with file id and notes', () async {
    final api = FakeApi()
      ..on(
        'POST',
        '/student/assignments/{id}/submission',
        (_) => FakeResponse.ok(_assignment(status: 'submitted')),
      );
    final a = await LearningApi(
      api.client(),
    ).submit('A1', fileUploadId: 'F5', notes: ' halo ');
    expect(a.status!.value, 'submitted');
    final req = api.requests.single;
    expect(req.path, '/student/assignments/A1/submission');
    expect(req.body, {'file_upload_id': 'F5', 'notes': 'halo'});
  });

  test('assignments status filter is sent as query', () async {
    final api = _api();
    await LearningApi(api.client()).assignments(status: 'late');
    expect(api.requests.single.query, {'status': 'late'});
  });

  testWidgets('hub shows courses, due assignment and shortcuts', (
    tester,
  ) async {
    await pumpApp(tester, const LearningHubScreen(), api: _api());
    expect(find.text('Kuliah'), findsWidgets);
    expect(find.text('Algoritma'), findsWidgets);
    expect(find.text('Tugas Rekursi'), findsOneWidget);
    expect(find.text('Materi'), findsOneWidget);
    expect(find.text('Ujian'), findsOneWidget);
    expect(find.text('Forum, kuis & progres LMS'), findsOneWidget);
  });

  testWidgets('hub navigates to materials', (tester) async {
    await pumpApp(tester, const LearningHubScreen(), api: _api());
    await tester.tap(find.text('Materi'));
    await tester.pumpAndSettle();
    expect(find.text('route:/s/materials'), findsOneWidget);
  });

  testWidgets('hub in English dark at small phone has no overflow', (
    tester,
  ) async {
    await pumpApp(
      tester,
      const LearningHubScreen(),
      api: _api(),
      language: AppLanguage.en,
      themeMode: ThemeMode.dark,
      size: TestScreens.smallPhone,
    );
    expect(find.text('Courses this term'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('hub shows error with retry', (tester) async {
    await pumpApp(tester, const LearningHubScreen(), api: FakeApi());
    expect(find.text('Coba lagi'), findsWidgets);
  });

  testWidgets('materials lists items and shows empty state', (tester) async {
    await pumpApp(tester, const MaterialsScreen(), api: _api());
    expect(find.text('Materi file'), findsOneWidget);
    expect(find.text('Materi text'), findsOneWidget);
    final empty = FakeApi()
      ..get('/student/materials', {'term': null, 'courses': []});
    await pumpApp(tester, const MaterialsScreen(), api: empty);
    expect(find.text('Belum ada materi'), findsOneWidget);
  });

  testWidgets('text material opens in-app sheet', (tester) async {
    await pumpApp(tester, const MaterialsScreen(), api: _api());
    await tester.tap(find.text('Materi text'));
    await tester.pumpAndSettle();
    expect(find.text('Isi teks'), findsWidgets);
  });

  testWidgets('assignments list with filter chips and counts', (tester) async {
    await pumpApp(tester, const AssignmentsScreen(), api: _api());
    expect(find.text('Tugas Rekursi'), findsOneWidget);
    expect(find.textContaining('Belum dikerjakan (1)'), findsOneWidget);
    await tester.tap(find.textContaining('Belum dikerjakan ('));
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
  });

  testWidgets('detail: submit validates, then posts notes', (tester) async {
    final api = _api()
      ..get('/student/assignments/A1', _assignment())
      ..on(
        'POST',
        '/student/assignments/A1/submission',
        (_) => FakeResponse.ok(_assignment(status: 'submitted')),
      );
    await pumpApp(
      tester,
      const AssignmentDetailScreen(assignmentId: 'A1'),
      api: api,
      size: TestScreens.smallPhone,
    );
    expect(find.text('Kerjakan soal 1-5'), findsOneWidget);
    await tester.scrollUntilVisible(
      find.text('Kirim tugas'),
      300,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.drag(find.byType(Scrollable).first, const Offset(0, -300));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Kirim tugas'));
    await tester.pumpAndSettle();
    expect(
      find.text('Unggah berkas tugas atau tuliskan jawaban.'),
      findsOneWidget,
    );
    await tester.enterText(find.byType(TextField), 'jawaban saya');
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.text('Kirim tugas'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Kirim tugas'));
    await tester.pumpAndSettle();
    final post = api.requests.where((r) => r.method == 'POST').single;
    expect(post.body, {'notes': 'jawaban saya'});
  });

  testWidgets('detail: graded assignment shows grade and no form', (
    tester,
  ) async {
    final api = FakeApi()
      ..get(
        '/student/assignments/A1',
        _assignment(
          status: 'graded',
          canSubmit: false,
          submission: {
            'id': 'SUB',
            'submitted_at': '2026-10-20T10:00:00+07:00',
            'is_late': false,
            'submission_count': 1,
            'score': '90.00',
            'feedback': 'Bagus sekali',
            'graded_at': '2026-10-21T10:00:00+07:00',
            'graded_by': 'Dr. Budi',
          },
        ),
      );
    await pumpApp(
      tester,
      const AssignmentDetailScreen(assignmentId: 'A1'),
      api: api,
      themeMode: ThemeMode.dark,
      language: AppLanguage.en,
    );
    await tester.scrollUntilVisible(
      find.text('Bagus sekali'),
      300,
      scrollable: find.byType(Scrollable).first,
    );
    expect(find.text('Bagus sekali'), findsOneWidget);
    expect(find.text('Submit assignment'), findsNothing);
  });
}
