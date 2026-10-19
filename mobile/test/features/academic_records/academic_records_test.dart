import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/core/network/api_failure.dart';
import 'package:civitasone/features/academic_records/data/academic_records_api.dart';
import 'package:civitasone/features/academic_records/domain/academic_models.dart';
import 'package:civitasone/features/academic_records/academic_records_routes.dart';
import 'package:civitasone/features/academic_records/presentation/screens/documents_screen.dart';
import 'package:civitasone/features/academic_records/presentation/screens/grades_screen.dart';
import 'package:civitasone/features/academic_records/presentation/screens/history_screen.dart';
import 'package:civitasone/features/academic_records/presentation/screens/khs_screen.dart';
import 'package:civitasone/features/academic_records/presentation/screens/profile_screen.dart';
import 'package:civitasone/features/academic_records/presentation/screens/transcript_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

const _term = {
  'id': 'T1',
  'academic_year': '2026/2027',
  'semester': 'ganjil',
  'label': '2026/2027 Ganjil',
  'is_current': true,
};

const _summary = {
  'nim': '2101001',
  'name': 'Andi Pratama',
  'study_program': {'id': 'P1', 'name': 'Informatika', 'degree_level': 'S1'},
  'faculty': {'id': 'F1', 'name': 'Teknik'},
  'admission_year': 2021,
  'semester': 5,
  'status': {'value': 'active', 'label': 'Aktif'},
  'academic_advisor': {'id': 'L1', 'name': 'Dr. Maria', 'email': null},
  'curriculum': {'id': 'C1', 'name': 'Kurikulum 2021', 'academic_year': '2021'},
  'current_term': _term,
  'ipk': 3.456,
  'last_ips': {
    'academic_term_id': 'T0',
    'label': '2025/2026 Genap',
    'ips': 3.5,
  },
  'total_credits': 90,
  'passed_credits': 84,
  'remaining_credits': 54,
  'current_term_credits': 20,
  'max_credits': 24,
};

Map<String, dynamic> _row(String letter, {String? score = '85.00'}) => {
  'krs_item_id': 'K$letter',
  'course_code': 'IF-$letter',
  'course_name': 'Mata Kuliah $letter',
  'class_code': 'A',
  'credits': 3,
  'score': score,
  'letter_grade': score == null ? null : letter,
  'weight': 4.0,
  'quality_points': 12.0,
  'is_passing': score == null ? null : letter != 'E',
  'is_graded': score != null,
  'graded_at': null,
};

final _grades = {
  'terms': [
    {
      'term': _term,
      'semester_number': 5,
      'ips': 3.5,
      'graded_credits': 3,
      'total_credits': 6,
      'rows': [_row('A'), _row('E', score: null)],
    },
  ],
  'ipk': 3.456,
  'total_credits': 6,
  'passed_credits': 3,
};

final _khs = {
  'terms': [
    _term,
    {...(_term), 'id': 'T0', 'label': '2025/2026 Genap', 'is_current': false},
  ],
  'term': _term,
  'semester_number': 5,
  'rows': [_row('B')],
  'summary': {
    'term_credits': 3,
    'taken_credits': 3,
    'ips': 3.0,
    'ipk': 3.4,
    'cumulative_credits': 90,
    'all_graded': false,
  },
};

final _transcript = {
  'student': {
    'name': 'Andi Pratama',
    'nim': '2101001',
    'study_program': 'Informatika',
    'degree_level': 'S1',
    'faculty': 'Teknik',
    'admission_year': 2021,
    'status': 'Aktif',
    'university': 'Univ',
  },
  'rows': [
    {
      'course_id': 'c1',
      'course_code': 'IF-1',
      'course_name': 'Algoritma',
      'credits': 3,
      'semester_level': 1,
      'term_label': '2021/2022 Ganjil',
      'term_start': '2021-09-01',
      'score': '78.50',
      'letter_grade': 'B',
      'weight': 3.0,
      'quality_points': 9.0,
      'is_passing': true,
      'attempts': 2,
    },
  ],
  'summary': {
    'total_credits': 3,
    'passed_credits': 3,
    'ipk': 3.0,
    'course_count': 1,
  },
  'terms': [
    {
      'academic_term_id': 'T1',
      'label': '2021/2022 Ganjil',
      'sks': 3,
      'ip': 3.0,
    },
  ],
};

final _profile = {
  'id': 'S1',
  'personal': {
    'name': 'Andi Pratama',
    'nim': '2101001',
    'birth_place': 'Bandung',
    'birth_date': '2002-03-04',
    'gender': 'male',
    'gender_label': 'Laki-laki',
  },
  'contact': {'email': 'andi@student.test', 'phone': '0812345678'},
  'address': 'Jl. Merdeka 1',
  'photo': null,
  'academic': _summary,
  'editable_fields': ['phone', 'address', 'photo'],
  'request_only_fields': ['name', 'email'],
  'pending_data_change': <dynamic>[],
};

FakeApi _api() => FakeApi()
  ..get('/notifications/unread-count', {'unread_count': 0})
  ..get('/student/academic-summary', _summary)
  ..get('/student/academic-history', [
    {
      'date': '2021-08-20',
      'type': 'admission',
      'title': 'Diterima',
      'description': null,
    },
  ])
  ..get('/student/grades', _grades)
  ..get('/student/khs', _khs)
  ..get('/student/transcript', _transcript)
  ..get('/student/profile', _profile)
  ..get('/student/documents', [
    {
      'type': 'khs',
      'title': 'KHS 2026/2027 Ganjil',
      'description': 'Kartu hasil studi',
      'url': '/student/documents/khs/T1',
      'filename': 'khs.pdf',
    },
    {
      'type': 'krs',
      'title': 'KRS',
      'description': null,
      'url': '/student/documents/krs?academic_term_id=T1',
      'filename': 'krs.pdf',
    },
  ]);

void main() {
  group('models', () {
    test('parse grades, decimal strings and nulls', () {
      final g = GradesOverview.fromJson(_grades);
      expect(g.terms.single.rows.first.score, 85.0);
      expect(g.terms.single.rows.last.letterGrade, isNull);
      expect(g.terms.single.rows.last.isGraded, isFalse);
      expect(letterTone('A'), letterTone('AB'));
      expect(letterTone('E').name, 'danger');
    });

    test('empty khs and document url split', () {
      final k = KhsData.fromJson({
        'terms': <dynamic>[],
        'term': null,
        'semester_number': null,
        'rows': <dynamic>[],
        'summary': null,
      });
      expect(k.term, isNull);
      expect(k.summary, isNull);
      final d = StudentDocument.fromJson({
        'type': 'krs',
        'title': 'KRS',
        'url': '/student/documents/krs?academic_term_id=T1',
        'filename': 'a.pdf',
      });
      expect(d.path, '/student/documents/krs');
      expect(d.query, {'academic_term_id': 'T1'});
    });
  });

  group('api', () {
    test('khs passes academic_term_id and profile PATCH payload', () async {
      final fake = _api()
        ..on(
          'PATCH',
          '/student/profile',
          (_) => FakeResponse.ok(_profile, message: 'ok'),
        );
      final api = AcademicRecordsApi(fake.client());
      await api.khs(termId: 'T0');
      expect(fake.requests.last.query['academic_term_id'], 'T0');
      final p = await api.updateProfile(phone: '0811', address: null);
      expect(p.name, 'Andi Pratama');
      final req = fake.requests.last;
      expect(req.method, 'PATCH');
      expect(req.body, {'phone': '0811', 'address': null});
    });
  });

  group('screens', () {
    testWidgets('hub shows summary, menu and pending section', (t) async {
      await pumpApp(t, const AcademicHubScreen(), api: _api());
      await t.pumpAndSettle();
      expect(find.text('Andi Pratama'), findsOneWidget);
      expect(find.text('3,46'), findsOneWidget);
      expect(find.text('KHS'), findsOneWidget);
      await t.drag(find.byType(ListView), const Offset(0, -3000));
      await t.pumpAndSettle();
      expect(find.text('Menunggu backend'), findsWidgets);
    });

    testWidgets('hub navigates to KRS', (t) async {
      await pumpApp(t, const AcademicHubScreen(), api: _api());
      await t.pumpAndSettle();
      await t.tap(find.text('Kalender akademik'));
      await t.pumpAndSettle();
      expect(find.text('route:/s/calendar'), findsOneWidget);
    });

    testWidgets('grades grouped by semester with letters', (t) async {
      await pumpApp(t, const GradesScreen(), api: _api());
      await t.pumpAndSettle();
      expect(find.text('2026/2027 Ganjil'), findsOneWidget);
      expect(find.text('A'), findsOneWidget);
      expect(find.text('Belum dinilai'), findsOneWidget);
    });

    testWidgets('khs semester picker reloads with academic_term_id', (t) async {
      final api = _api();
      await pumpApp(t, const KhsScreen(), api: api);
      await t.pumpAndSettle();
      expect(find.textContaining('IPS'), findsWidgets);
      expect(find.textContaining('IPS dapat berubah'), findsOneWidget);
      await t.tap(find.byType(DropdownButtonFormField<String>));
      await t.pumpAndSettle();
      await t.tap(find.text('2025/2026 Genap').last);
      await t.pumpAndSettle();
      expect(
        api.requests.any((r) => r.query['academic_term_id'] == 'T0'),
        isTrue,
      );
    });

    testWidgets('transcript flags repeated course (EN)', (t) async {
      await pumpApp(
        t,
        const TranscriptScreen(),
        api: _api(),
        language: AppLanguage.en,
      );
      await t.pumpAndSettle();
      expect(find.text('Algoritma'), findsOneWidget);
      expect(find.text('Repeated, taken 2 times'), findsOneWidget);
    });

    testWidgets('transcript empty and error states', (t) async {
      final empty = _api()
        ..get('/student/transcript', {
          'student': <String, dynamic>{},
          'rows': <dynamic>[],
          'summary': <String, dynamic>{},
          'terms': <dynamic>[],
        });
      await pumpApp(t, const TranscriptScreen(), api: empty);
      await t.pumpAndSettle();
      expect(find.textContaining('Transkrip belum tersedia'), findsOneWidget);

      final err = _api()
        ..on('GET', '/student/grades', (_) => FakeResponse.error(500, 'Gagal'));
      await pumpApp(t, const GradesScreen(), api: err);
      await t.pumpAndSettle();
      expect(find.text('Coba lagi'), findsOneWidget);
    });

    testWidgets('documents list in dark mode', (t) async {
      await pumpApp(
        t,
        const DocumentsScreen(),
        api: _api(),
        themeMode: ThemeMode.dark,
      );
      await t.pumpAndSettle();
      expect(find.text('KHS 2026/2027 Ganjil'), findsOneWidget);
      expect(find.text('KRS'), findsWidgets);
    });

    testWidgets('history renders on small phone without overflow', (t) async {
      await pumpApp(
        t,
        const AcademicHistoryScreen(),
        api: _api(),
        size: TestScreens.smallPhone,
      );
      await t.pumpAndSettle();
      await t.scrollUntilVisible(find.text('Diterima'), 300);
      expect(find.text('Diterima'), findsOneWidget);
      expect(t.takeException(), isNull);
    });

    testWidgets('profile small phone, read-only + edit validation + save', (
      t,
    ) async {
      final api = _api()
        ..on('PATCH', '/student/profile', (_) => FakeResponse.ok(_profile));
      await pumpApp(
        t,
        const StudentProfileScreen(),
        api: api,
        size: TestScreens.smallPhone,
      );
      await t.pumpAndSettle();
      expect(find.text('Bandung'), findsOneWidget);
      expect(t.takeException(), isNull);
      await t.tap(find.byTooltip('Ubah kontak'));
      await t.pumpAndSettle();
      await t.enterText(find.byType(TextFormField).first, 'abc');
      await t.tap(find.text('Simpan'));
      await t.pumpAndSettle();
      expect(find.text('Nomor telepon tidak valid'), findsOneWidget);
      await t.enterText(find.byType(TextFormField).first, '0899123456');
      await t.tap(find.text('Simpan'));
      await t.pumpAndSettle();
      final patch = api.requests.firstWhere((r) => r.method == 'PATCH');
      expect((patch.body as Map)['phone'], '0899123456');
    });

    test('routes exported', () {
      expect(academicRecordsRoutes.length, 6);
      expect(ApiFailure, isNotNull);
    });
  });
}
