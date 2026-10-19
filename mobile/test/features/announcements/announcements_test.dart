import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/features/announcements/data/announcements_api.dart';
import 'package:civitasone/features/announcements/domain/announcement.dart';
import 'package:civitasone/features/announcements/presentation/screens/announcement_detail_screen.dart';
import 'package:civitasone/features/announcements/presentation/screens/announcements_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

Map<String, dynamic> _a(
  String id,
  String title, {
  bool pinned = false,
  bool read = false,
  Object? attachment,
  String body = 'Isi pengumuman',
}) => {
  'id': id,
  'title': title,
  'body': body,
  'target_scope': 'universitas',
  'target_id': null,
  'audience': 'all',
  'target_admission_year': null,
  'target_semester': null,
  'is_pinned': pinned,
  'published_at': '2026-10-01T08:00:00+07:00',
  'creator_name': 'Admin Akademik',
  'attachment': attachment,
  'is_read': read,
  'created_at': '2026-10-01T08:00:00+07:00',
};

const _meta = {
  'current_page': 1,
  'per_page': 15,
  'total': 2,
  'last_page': 1,
  'unread_count': 1,
};

FakeApi _api({List<Object?>? items}) => FakeApi()
  ..get(
    '/student/announcements',
    items ??
        [
          _a('a1', 'Libur Semester', pinned: true),
          _a('a2', 'Info UKT', read: true),
        ],
    meta: _meta,
  )
  ..get(
    '/student/announcements/a1',
    _a(
      'a1',
      'Libur Semester',
      body: '<p>Halo <b>Semua</b></p><script>x()</script>',
      attachment: {
        'id': 'f1',
        'name': 'surat.pdf',
        'size_bytes': 2048,
        'mime_type': 'application/pdf',
      },
    ),
  )
  ..on(
    'POST',
    '/student/announcements/{id}/read',
    (_) => FakeResponse.ok({'id': 'a1', 'is_read': true, 'unread_count': 0}),
  );

void main() {
  test('plainTextFromBody strips markup safely', () {
    expect(
      plainTextFromBody('<p>A &amp; B</p><ul><li>x</li></ul>line<br/>two'),
      'A & B\n• x\nline\ntwo',
    );
  });

  test('model parses nulls and attachment', () {
    final a = Announcement.fromJson(_a('1', 't'));
    expect(a.attachment, isNull);
    final b = Announcement.fromJson(
      _a(
        '1',
        't',
        attachment: {'id': 'f', 'name': 'n.pdf', 'size_bytes': '10'},
      ),
    );
    expect(b.attachment!.sizeBytes, 10);
  });

  test('api list query + unread count from total', () async {
    final fake = _api();
    final api = AnnouncementsApi(fake.client());
    await api.list(page: 2, filter: 'faculty', search: ' ukt ');
    final q = fake.requests.last.query;
    expect(q['filter'], 'faculty');
    expect(q['search'], 'ukt');
    expect(q['page'], '2');
    expect(await api.unreadCount(), 2);
    expect(fake.requests.last.query['filter'], 'unread');
  });

  testWidgets('feed renders pinned and unread, opens detail', (t) async {
    await pumpApp(t, const AnnouncementsScreen(), api: _api());
    expect(find.text('Libur Semester'), findsOneWidget);
    expect(find.text('Disematkan'), findsOneWidget);
    await t.tap(find.text('Info UKT'));
    await t.pumpAndSettle();
    expect(find.text('route:/s/announcements/a2'), findsOneWidget);
  });

  testWidgets('filter chip sends filter param', (t) async {
    final api = _api();
    await pumpApp(t, const AnnouncementsScreen(), api: api);
    await t.tap(find.widgetWithText(ChoiceChip, 'Belum dibaca'));
    await t.pumpAndSettle();
    expect(
      api.requests
          .where((r) => r.path == '/student/announcements')
          .any((r) => r.query['filter'] == 'unread'),
      isTrue,
    );
  });

  testWidgets('search submits', (t) async {
    final api = _api();
    await pumpApp(t, const AnnouncementsScreen(), api: api);
    await t.enterText(find.byType(TextField), 'ukt');
    await t.testTextInput.receiveAction(TextInputAction.search);
    await t.pumpAndSettle();
    expect(api.requests.any((r) => r.query['search'] == 'ukt'), isTrue);
  });

  testWidgets('empty state', (t) async {
    await pumpApp(t, const AnnouncementsScreen(), api: _api(items: []));
    expect(find.text('Belum ada pengumuman'), findsOneWidget);
  });

  testWidgets('error state', (t) async {
    final api = FakeApi()
      ..on(
        'GET',
        '/student/announcements',
        (_) => FakeResponse.error(500, 'x'),
      );
    await pumpApp(t, const AnnouncementsScreen(), api: api);
    expect(find.text('Coba lagi'), findsWidgets);
  });

  testWidgets('detail shows text, attachment and posts read', (t) async {
    final api = _api();
    await pumpApp(t, const AnnouncementDetailScreen(id: 'a1'), api: api);
    expect(find.textContaining('Halo Semua'), findsOneWidget);
    expect(find.text('surat.pdf'), findsOneWidget);
    expect(
      api.requests.where(
        (r) => r.method == 'POST' && r.path == '/student/announcements/a1/read',
      ),
      hasLength(1),
    );
  });

  testWidgets('English dark small phone no overflow', (t) async {
    await pumpApp(
      t,
      const AnnouncementsScreen(),
      api: _api(),
      language: AppLanguage.en,
      themeMode: ThemeMode.dark,
      size: TestScreens.smallPhone,
    );
    expect(find.text('Announcements'), findsOneWidget);
    expect(t.takeException(), isNull);
  });
}
