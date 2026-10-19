import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/teaching/presentation/screens/assignment_submissions_screen.dart';
import 'package:civitasone/features/teaching/presentation/screens/class_assignments_screen.dart';
import 'package:civitasone/features/teaching/presentation/screens/class_hub_screen.dart';
import 'package:civitasone/features/teaching/presentation/screens/class_materials_screen.dart';
import 'package:civitasone/features/teaching/presentation/screens/class_students_screen.dart';
import 'package:civitasone/features/teaching/presentation/screens/schedule_screens.dart';
import 'package:civitasone/features/teaching/presentation/screens/student_record_screen.dart';
import 'package:civitasone/features/teaching/presentation/screens/teaching_home_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

final _today = DateTime.now().weekday;

Map<String, dynamic> _cls() => {
  'id': '01CLS',
  'class_code': 'A',
  'course': {
    'id': 'c1',
    'code': 'IF101',
    'name': 'Algoritma Dasar',
    'credits': 3,
    'semester_level': 1,
  },
  'lecturer': null,
  'schedules': [
    {
      'id': 's1',
      'day_of_week': _today,
      'day_label': 'x',
      'start_time': '08:00',
      'end_time': '10:00',
      'room': 'R.101',
    },
  ],
  'term': {'id': 't1', 'label': '2026/2027 Ganjil'},
  'capacity': 40,
  'enrolled_count': 2,
  'materials_count': 1,
  'assignments_count': 1,
};

const _meta = {'current_page': 1, 'per_page': 100, 'total': 1, 'last_page': 1};

FakeApi _api() => FakeApi()
  ..get('/notifications/unread-count', {'unread_count': 0})
  ..get('/teaching/classes', [_cls()], meta: _meta);

void main() {
  testWidgets('home lists classes, tiles and navigates', (tester) async {
    await pumpApp(
      tester,
      const TeachingHomeScreen(),
      api: _api(),
      user: testLecturer,
    );
    expect(find.text('Algoritma Dasar'), findsOneWidget);
    expect(find.textContaining('IF101'), findsOneWidget);
    expect(find.text('2 mahasiswa'), findsOneWidget);
    expect(find.text('Ujian'), findsOneWidget);
    await tester.tap(find.text('Algoritma Dasar'));
    await tester.pumpAndSettle();
    expect(find.text('route:/l/classes/01CLS'), findsOneWidget);
  });

  testWidgets('home question bank tile navigates', (tester) async {
    await pumpApp(
      tester,
      const TeachingHomeScreen(),
      api: _api(),
      user: testLecturer,
    );
    await tester.tap(find.text('Bank soal'));
    await tester.pumpAndSettle();
    expect(find.text('route:/l/question-bank'), findsOneWidget);
  });

  testWidgets('home in English, dark mode, small phone', (tester) async {
    await pumpApp(
      tester,
      const TeachingHomeScreen(),
      api: _api(),
      user: testLecturer,
      language: AppLanguage.en,
      themeMode: ThemeMode.dark,
      size: TestScreens.smallPhone,
    );
    expect(find.text('My classes'), findsOneWidget);
    expect(find.text('2 students'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('home empty and error states', (tester) async {
    final empty = FakeApi()
      ..get('/notifications/unread-count', {'unread_count': 0})
      ..get('/teaching/classes', [], meta: _meta);
    await pumpApp(
      tester,
      const TeachingHomeScreen(),
      api: empty,
      user: testLecturer,
    );
    expect(find.text('Belum ada kelas'), findsOneWidget);

    final err = FakeApi()
      ..get('/notifications/unread-count', {'unread_count': 0})
      ..on('GET', '/teaching/classes', (_) => FakeResponse.error(500, 'boom'));
    await pumpApp(
      tester,
      const TeachingHomeScreen(),
      api: err,
      user: testLecturer,
    );
    expect(find.text('Coba lagi'), findsOneWidget);
  });

  testWidgets('class hub shows actions and 403 error', (tester) async {
    final api = FakeApi()
      ..get('/class-sections/01CLS', {
        'id': '01CLS',
        'class_code': 'A',
        'course_code': 'IF101',
        'course_name': 'Algoritma Dasar',
        'credits': 3,
        'study_program_name': 'Informatika',
        'academic_term_label': '2026/2027 Ganjil',
        'capacity': 40,
        'enrolled_count': 2,
        'lecturer': {'id': 'l', 'name': 'Dr. Maria', 'email': null},
        'schedules': [],
      });
    await pumpApp(
      tester,
      const ClassHubScreen(classSectionId: '01CLS'),
      api: api,
      user: testLecturer,
    );
    expect(find.text('Informatika'), findsOneWidget);
    await tester.tap(find.text('Presensi'));
    await tester.pumpAndSettle();
    expect(find.text('route:/l/classes/01CLS/attendance'), findsOneWidget);

    final denied = FakeApi()
      ..on(
        'GET',
        '/class-sections/X',
        (_) => FakeResponse.error(403, 'Anda bukan dosen pengampu kelas ini.'),
      );
    await pumpApp(
      tester,
      const ClassHubScreen(classSectionId: 'X'),
      api: denied,
      user: testLecturer,
    );
    expect(find.byIcon(Icons.lock_outline), findsOneWidget);
  });

  testWidgets('roster lists students, searches and opens record', (
    tester,
  ) async {
    final api = FakeApi()
      ..get('/krs-items', [
        {
          'id': 'k1',
          'student_id': 's1',
          'student_name': 'Ani',
          'student_nim': '2101',
        },
        {
          'id': 'k2',
          'student_id': 's2',
          'student_name': 'Budi',
          'student_nim': '2102',
        },
      ], meta: _meta);
    await pumpApp(
      tester,
      const ClassStudentsScreen(classSectionId: '01CLS'),
      api: api,
      user: testLecturer,
    );
    expect(find.text('Ani'), findsOneWidget);
    await tester.enterText(find.byType(TextField), 'budi');
    await tester.pumpAndSettle();
    expect(find.text('Ani'), findsNothing);
    await tester.tap(find.text('Budi'));
    await tester.pumpAndSettle();
    expect(find.text('route:/l/students/s2'), findsOneWidget);
  });

  testWidgets('materials list and create video material', (tester) async {
    final api = FakeApi()
      ..get('/class-sections/01CLS/materials', [
        {
          'id': 'm1',
          'meeting_number': 1,
          'title': 'Slide 1',
          'description': null,
          'type': 'file',
          'type_label': 'Berkas',
          'url': null,
          'file': {
            'id': 'f1',
            'name': 'slide.pdf',
            'size_bytes': 2048,
            'mime_type': 'application/pdf',
          },
          'is_published': false,
          'published_at': null,
        },
      ])
      ..on(
        'POST',
        '/class-sections/01CLS/materials',
        (_) => FakeResponse.ok({'id': 'm2', 'title': 'Video', 'type': 'video'}),
      );
    await pumpApp(
      tester,
      const ClassMaterialsScreen(classSectionId: '01CLS'),
      api: api,
      user: testLecturer,
      size: TestScreens.smallPhone,
    );
    expect(find.text('Slide 1'), findsOneWidget);
    expect(find.text('Draf'), findsOneWidget);
    await tester.tap(find.text('Tambah materi'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Simpan'));
    await tester.pumpAndSettle();
    expect(find.text('Wajib diisi'), findsOneWidget);
    await tester.enterText(find.byType(TextFormField).first, 'Video');
    await tester.tap(find.byType(DropdownButtonFormField<String>));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Video').last);
    await tester.pumpAndSettle();
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Tautan (http/https)'),
      'ftp://x',
    );
    await tester.tap(find.text('Simpan'));
    await tester.pumpAndSettle();
    expect(
      find.text('Tautan harus diawali http:// atau https://'),
      findsOneWidget,
    );
    await tester.enterText(
      find.widgetWithText(TextFormField, 'Tautan (http/https)'),
      'https://youtu.be/x',
    );
    await tester.tap(find.text('Simpan'));
    await tester.pumpAndSettle();
    final post = api.requests.firstWhere((r) => r.method == 'POST');
    expect(post.body, {
      'title': 'Video',
      'type': 'video',
      'meeting_number': null,
      'description': null,
      'is_published': true,
      'url': 'https://youtu.be/x',
    });
    expect(tester.takeException(), isNull);
  });

  testWidgets('assignments list opens submissions route', (tester) async {
    final api = FakeApi()
      ..get('/class-sections/01CLS/assignments', [
        {
          'id': 'a1',
          'class_section_id': '01CLS',
          'title': 'Tugas 1',
          'description': null,
          'due_at': '2026-12-01T10:00:00+07:00',
          'allow_late_submission': false,
          'allow_resubmission': true,
          'max_file_size_mb': 10,
          'allowed_extensions': ['pdf'],
          'max_score': '100.00',
          'is_published': true,
          'published_at': null,
          'attachment': null,
          'submissions_count': 3,
          'graded_count': 1,
        },
      ]);
    await pumpApp(
      tester,
      const ClassAssignmentsScreen(classSectionId: '01CLS'),
      api: api,
      user: testLecturer,
    );
    expect(find.text('Tugas 1'), findsOneWidget);
    expect(find.text('3 terkumpul · 1 dinilai'), findsOneWidget);
    await tester.tap(find.text('Tugas 1'));
    await tester.pumpAndSettle();
    expect(find.text('route:/l/assignments/a1/submissions'), findsOneWidget);
  });

  testWidgets('submissions: grade with validation', (tester) async {
    final api = FakeApi()
      ..get('/assignments/a1/submissions', {
        'assignment': {
          'id': 'a1',
          'title': 'Tugas 1',
          'due_at': '2026-12-01T10:00:00+07:00',
          'max_score': '100.00',
        },
        'submissions': [
          {
            'krs_item_id': 'k1',
            'student': {'id': 's1', 'nim': '2101', 'name': 'Ani'},
            'status': 'late',
            'submission': {
              'id': 'sub1',
              'notes': 'catatan',
              'submitted_at': '2026-12-02T10:00:00+07:00',
              'is_late': true,
              'submission_count': 1,
              'score': null,
              'feedback': null,
              'graded_at': null,
              'graded_by': null,
              'file': {'id': 'f9', 'name': 'jawaban.pdf', 'size_bytes': 10},
            },
          },
          {
            'krs_item_id': 'k2',
            'student': {'id': 's2', 'nim': '2102', 'name': 'Budi'},
            'status': 'not_submitted',
            'submission': null,
          },
        ],
      })
      ..on(
        'PUT',
        '/assignment-submissions/{id}/grade',
        (r) => FakeResponse.ok({
          'id': 'sub1',
          'score': '90.00',
          'is_late': true,
          'submission_count': 1,
        }),
      );
    await pumpApp(
      tester,
      const AssignmentSubmissionsScreen(assignmentId: 'a1'),
      api: api,
      user: testLecturer,
    );
    expect(find.text('Terlambat'), findsOneWidget);
    expect(find.text('Belum mengumpulkan'), findsOneWidget);
    await tester.tap(find.text('Ani'));
    await tester.pumpAndSettle();
    expect(find.textContaining('jawaban.pdf'), findsOneWidget);
    final scoreField = find.widgetWithText(TextFormField, 'Nilai (0–100)');
    await tester.enterText(scoreField, '150');
    await tester.tap(find.widgetWithText(FilledButton, 'Simpan'));
    await tester.pumpAndSettle();
    expect(find.text('Nilai harus 0–100'), findsOneWidget);
    await tester.enterText(scoreField, '90');
    await tester.tap(find.widgetWithText(FilledButton, 'Simpan'));
    await tester.pumpAndSettle();
    final put = api.requests.firstWhere((r) => r.method == 'PUT');
    expect(put.path, '/assignment-submissions/sub1/grade');
    expect(put.body, {'score': 90.0, 'feedback': null});
  });

  testWidgets('schedule shows today slot, switching days, calendar entry', (
    tester,
  ) async {
    final other = _today == 1 ? 2 : 1;
    await pumpApp(
      tester,
      const LecturerScheduleScreen(),
      api: _api(),
      user: testLecturer,
      size: TestScreens.smallPhone,
    );
    expect(find.text('Algoritma Dasar'), findsOneWidget);
    expect(find.text('Hari ini'), findsOneWidget);
    await tester.tap(find.byKey(ValueKey('day-$other')));
    await tester.pumpAndSettle();
    expect(find.text('Tidak ada jadwal'), findsOneWidget);
    await tester.tap(find.byTooltip('Kalender akademik'));
    await tester.pumpAndSettle();
    expect(find.text('route:/l/calendar'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('calendar lists events and handles empty', (tester) async {
    final api = FakeApi()
      ..get('/academic-calendar-events', [
        {
          'id': 'e1',
          'title': 'UTS',
          'description': null,
          'category': 'exam',
          'category_label': 'Ujian',
          'start_date': '2026-10-20',
          'end_date': '2026-10-25',
          'academic_term': null,
          'study_program': null,
        },
      ]);
    await pumpApp(
      tester,
      const LecturerCalendarScreen(),
      api: api,
      user: testLecturer,
    );
    expect(find.text('UTS'), findsOneWidget);
    expect(find.text('Ujian'), findsOneWidget);
    expect(api.requests.first.query.containsKey('from'), isTrue);

    final empty = FakeApi()..get('/academic-calendar-events', []);
    await pumpApp(
      tester,
      const LecturerCalendarScreen(),
      api: empty,
      user: testLecturer,
    );
    expect(find.text('Tidak ada kegiatan'), findsOneWidget);
  });

  testWidgets('student record with transcript; transcript 403 inline', (
    tester,
  ) async {
    final student = {
      'id': 's1',
      'nim': '2101',
      'name': 'Ani Lestari',
      'email': 'ani@x.id',
      'status': 'active',
      'admission_year': 2021,
      'study_program_name': 'Informatika',
    };
    final api = FakeApi()
      ..get('/students/s1', student)
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
    await pumpApp(
      tester,
      const StudentRecordScreen(studentId: 's1'),
      api: api,
      user: testLecturer,
      language: AppLanguage.en,
    );
    expect(find.text('Ani Lestari'), findsOneWidget);
    expect(find.text('Transcript summary'), findsOneWidget);
    expect(find.text('2025 Ganjil'), findsOneWidget);

    final denied = FakeApi()
      ..get('/students/s1', student)
      ..on(
        'GET',
        '/students/s1/transcript',
        (_) => FakeResponse.error(403, 'Tidak diizinkan'),
      );
    await pumpApp(
      tester,
      const StudentRecordScreen(studentId: 's1'),
      api: denied,
      user: testLecturer,
    );
    expect(find.text('Ani Lestari'), findsOneWidget);
    expect(find.byIcon(Icons.lock_outline), findsOneWidget);
  });
}
