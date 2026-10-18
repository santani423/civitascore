import 'package:civitasone/core/network/api_envelope.dart';
import 'package:civitasone/core/network/api_failure.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';

void main() {
  group('ApiClient', () {
    test('decodes data and pagination meta', () async {
      final api = FakeApi()
        ..get(
          '/items',
          [
            {'id': '1'},
            {'id': '2'},
          ],
          meta: {'current_page': 1, 'per_page': 2, 'total': 3, 'last_page': 2},
        );
      final page = await api.client().getPage(
        '/items',
        item: (j) => asString(j['id']),
      );
      expect(page.items, ['1', '2']);
      expect(page.hasNextPage, isTrue);
      expect(page.meta.total, 3);
    });

    test('tolerates PHP empty-array meta', () async {
      final api = FakeApi()..get('/items', <dynamic>[]);
      final page = await api.client().getPage('/items', item: (j) => j);
      expect(page.items, isEmpty);
      expect(page.hasNextPage, isFalse);
    });

    test('maps 409 business code and 422 field errors', () async {
      final api = FakeApi()
        ..on(
          'POST',
          '/student/krs/items',
          (_) => FakeResponse.error(
            409,
            'Jadwal bentrok.',
            errors: {'code': 'SCHEDULE_CONFLICT'},
          ),
        )
        ..on(
          'POST',
          '/login',
          (_) => FakeResponse.error(
            422,
            'Validasi data gagal.',
            errors: {
              'nim': ['NIM atau password tidak sesuai.'],
            },
          ),
        );
      final client = api.client();

      final conflict = await client
          .post('/student/krs/items')
          .then<ApiFailure?>((_) => null, onError: (e) => e as ApiFailure);
      expect(conflict!.kind, ApiFailureKind.conflict);
      expect(conflict.code, 'SCHEDULE_CONFLICT');
      expect(conflict.serverDetail, 'Jadwal bentrok.');

      final invalid = await client
          .post('/login')
          .then<ApiFailure?>((_) => null, onError: (e) => e as ApiFailure);
      expect(invalid!.kind, ApiFailureKind.validation);
      expect(invalid.fieldError('nim'), 'NIM atau password tidak sesuai.');
    });

    test('records request payloads for assertions', () async {
      final api = FakeApi()
        ..on(
          'PUT',
          '/krs-items/{id}/grade',
          (r) => FakeResponse.ok({'id': 'g1'}),
        );
      await api.client().put('/krs-items/abc/grade', data: {'score': '85'});
      expect(api.requests.single.method, 'PUT');
      expect(api.requests.single.body, {'score': '85'});
    });
  });

  test('asX readers coerce mixed types', () {
    expect(asInt('85.00'), 85);
    expect(asDouble('3.75'), 3.75);
    expect(asBool('1'), isTrue);
    expect(asMap(<dynamic>[]), isEmpty);
    expect(asStringList([1, null, 'a']), ['1', 'a']);
  });
}
