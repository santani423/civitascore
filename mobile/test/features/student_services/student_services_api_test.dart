import 'package:civitasone/features/student_services/data/student_services_api.dart';
import 'package:civitasone/features/student_services/domain/student_request.dart';
import 'package:flutter_test/flutter_test.dart';

import '../../helpers/fake_api.dart';
import 'fixtures.dart';

void main() {
  test('list parses paginated requests and nulls', () async {
    final api = FakeApi()
      ..get(
        '/student/requests',
        [
          requestJson(),
          requestJson(
            id: '01REQ2',
            type: 'leave',
            status: 'submitted',
            payload: {},
            submittedAt: '2026-10-02T03:00:00+00:00',
            currentStep: {'name': 'Bagian Akademik', 'status': 'pending'},
          ),
        ],
        meta: {'current_page': 1, 'per_page': 20, 'total': 2, 'last_page': 2},
      );
    final page = await StudentServicesApi(api.client()).list(1);
    expect(page.items, hasLength(2));
    expect(page.meta.hasNextPage, isTrue);
    expect(page.items[0].type, RequestType.letter);
    expect(page.items[0].status, RequestStatus.draft);
    expect(page.items[0].attachment, isNull);
    expect(page.items[1].payload, isEmpty);
    expect(page.items[1].currentStepName, 'Bagian Akademik');
    expect(api.requests.single.query['page'], '1');
  });

  test('options and detail parse', () async {
    final api = FakeApi()
      ..get('/student/requests/options', optionsJson)
      ..get(
        '/student/requests/01REQ1',
        requestJson(
          status: 'approved',
          hasLetter: true,
          attachment: {'id': 'F1', 'name': 'ktp.pdf', 'size_bytes': 2048},
          history: [
            {
              'event': 'submitted',
              'description': 'Diajukan',
              'created_at': '2026-10-02T03:00:00+00:00',
            },
          ],
        ),
      );
    final client = StudentServicesApi(api.client());
    final options = await client.options();
    expect(options.letterTypes.first.value, 'active_student');
    expect(options.dataChangeFields, contains('tanggal_lahir'));
    final r = await client.detail('01REQ1');
    expect(r.hasLetter, isTrue);
    expect(r.attachment!.sizeBytes, 2048);
    expect(r.history.single.description, 'Diajukan');
  });

  test('mutations hit the documented paths and payloads', () async {
    final api = FakeApi();
    for (final m in ['POST', 'PUT']) {
      api.on(m, '/student/requests', (_) => FakeResponse.ok(requestJson()));
      api.on(
        m,
        '/student/requests/{id}',
        (_) => FakeResponse.ok(requestJson()),
      );
      api.on(
        m,
        '/student/requests/{id}/submit',
        (_) => FakeResponse.ok(requestJson(status: 'submitted')),
      );
      api.on(
        m,
        '/student/requests/{id}/cancel',
        (_) => FakeResponse.ok(requestJson(status: 'cancelled')),
      );
    }
    final client = StudentServicesApi(api.client());
    await client.create({'type': 'letter', 'title': 'Judul surat'});
    await client.update('01REQ1', {'title': 'Judul baru'});
    final submitted = await client.submit('01REQ1');
    final cancelled = await client.cancel('01REQ1');
    expect(submitted.status, RequestStatus.submitted);
    expect(cancelled.status, RequestStatus.cancelled);
    expect(api.requests.map((r) => '${r.method} ${r.path}').toList(), [
      'POST /student/requests',
      'PUT /student/requests/01REQ1',
      'POST /student/requests/01REQ1/submit',
      'POST /student/requests/01REQ1/cancel',
    ]);
    expect(api.requests[0].body, {'type': 'letter', 'title': 'Judul surat'});
  });
}
