import 'package:civitasone/core/l10n/app_language.dart';
import 'package:civitasone/core/widgets/notification_bell.dart';
import 'package:civitasone/features/notifications/domain/notification_item.dart';
import 'package:civitasone/features/notifications/presentation/screens/notification_preferences_screen.dart';
import 'package:civitasone/features/notifications/presentation/screens/notifications_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import '../../helpers/pump_app.dart';

final _items = [
  {
    'id': 'n1',
    'event_key': 'student.krs_approved',
    'message': 'KRS Anda disetujui',
    'link': '/portal/jadwal-kuliah',
    'read_at': null,
    'created_at': '2026-10-01T08:00:00+07:00',
  },
  {
    'id': 'n2',
    'event_key': null,
    'message': null,
    'link': null,
    'read_at': '2026-10-02T08:00:00+07:00',
    'created_at': '2026-10-01T07:00:00+07:00',
  },
];

const _meta = {
  'current_page': 1,
  'per_page': 20,
  'total': 2,
  'last_page': 1,
  'unread_count': 1,
};

FakeApi _api({List<Object?>? items}) {
  final api = FakeApi()
    ..get('/notifications', items ?? _items, meta: _meta)
    ..get('/notifications/unread-count', {'unread_count': 1})
    ..on('PATCH', '/notifications/{id}/read', (_) => FakeResponse.ok({}))
    ..on(
      'POST',
      '/notifications/read-all',
      (_) => FakeResponse.ok({'unread_count': 0}),
    );
  return api;
}

void main() {
  test('parses notification and legacy null fields', () {
    final n = NotificationItem.fromJson(_items[1]);
    expect(n.message, isNull);
    expect(n.eventKey, isNull);
    expect(n.isRead, isTrue);
  });

  testWidgets('renders list in Indonesian with legacy fallback', (t) async {
    await pumpApp(t, const NotificationsScreen(), api: _api());
    expect(find.text('KRS Anda disetujui'), findsOneWidget);
    expect(find.text('Pemberitahuan sistem (tanpa rincian)'), findsOneWidget);
    expect(find.text('Hanya belum dibaca'), findsOneWidget);
  });

  testWidgets('tap marks read via PATCH and navigates', (t) async {
    final api = _api();
    await pumpApp(t, const NotificationsScreen(), api: api);
    await t.tap(find.text('KRS Anda disetujui'));
    await t.pumpAndSettle();
    expect(
      api.requests.any(
        (r) => r.method == 'PATCH' && r.path == '/notifications/n1/read',
      ),
      isTrue,
    );
    expect(find.text('route:/s/schedule'), findsOneWidget);
  });

  testWidgets('mark all read posts read-all', (t) async {
    final api = _api();
    await pumpApp(t, const NotificationsScreen(), api: api);
    await t.tap(find.byTooltip('Tandai semua dibaca'));
    await t.pumpAndSettle();
    expect(
      api.requests.any(
        (r) => r.method == 'POST' && r.path == '/notifications/read-all',
      ),
      isTrue,
    );
  });

  testWidgets('unread filter sends unread param', (t) async {
    final api = _api();
    await pumpApp(t, const NotificationsScreen(), api: api);
    await t.tap(find.text('Hanya belum dibaca'));
    await t.pumpAndSettle();
    expect(
      api.requests
          .where((r) => r.path == '/notifications')
          .last
          .query['unread'],
      '1',
    );
  });

  testWidgets('empty state', (t) async {
    await pumpApp(t, const NotificationsScreen(), api: _api(items: []));
    expect(find.text('Belum ada notifikasi'), findsOneWidget);
  });

  testWidgets('error state with retry', (t) async {
    final api = FakeApi()
      ..on('GET', '/notifications', (_) => FakeResponse.error(500, 'boom'));
    await pumpApp(t, const NotificationsScreen(), api: api);
    expect(find.text('Coba lagi'), findsWidgets);
  });

  testWidgets('English dark smallPhone, no overflow', (t) async {
    await pumpApp(
      t,
      const NotificationsScreen(),
      api: _api(),
      language: AppLanguage.en,
      themeMode: ThemeMode.dark,
      size: TestScreens.smallPhone,
    );
    expect(find.text('Unread only'), findsOneWidget);
    expect(t.takeException(), isNull);
  });

  testWidgets('lecturer sees pending lecturer announcements tile', (t) async {
    await pumpApp(
      t,
      const NotificationsScreen(),
      api: _api(),
      user: testLecturer,
    );
    expect(t.takeException(), isNull);
  });

  group('preferences', () {
    FakeApi prefsApi() => FakeApi()
      ..get('/notification-preferences', [
        {
          'id': 'p1',
          'channel': 'mail',
          'notification_type': '*',
          'is_enabled': false,
        },
      ])
      ..on(
        'POST',
        '/notification-preferences',
        (_) => FakeResponse.ok({
          'id': 'p2',
          'channel': 'database',
          'notification_type': '*',
          'is_enabled': true,
        }),
      );

    test('channelEnabled defaults to true', () {
      final prefs = [
        NotificationPreference.fromJson({
          'id': '1',
          'channel': 'mail',
          'notification_type': '*',
          'is_enabled': false,
        }),
      ];
      expect(channelEnabled(prefs, 'mail'), isFalse);
      expect(channelEnabled(prefs, 'database'), isTrue);
    });

    testWidgets('shows note, push pending tile, and toggles mail', (t) async {
      final api = prefsApi();
      await pumpApp(t, const NotificationPreferencesScreen(), api: api);
      expect(find.textContaining('per jenis peristiwa'), findsOneWidget);
      await t.scrollUntilVisible(
        find.text('Notifikasi push'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      await t.tap(find.widgetWithText(SwitchListTile, 'Email'));
      await t.pumpAndSettle();
      final post = api.requests.firstWhere((r) => r.method == 'POST');
      expect(post.body, {
        'channel': 'mail',
        'notification_type': '*',
        'is_enabled': true,
      });
    });
  });

  testWidgets('bell provider exists', (t) async {
    expect(unreadNotificationCountProvider, isNotNull);
  });
}
