import 'package:civitasone/core/router/route_paths.dart';
import 'package:civitasone/core/session/app_experience.dart';
import 'package:civitasone/features/notifications/domain/notification_link_mapper.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  const st = AppExperience.student;
  const lc = AppExperience.lecturer;

  test('student links map to mobile routes', () {
    expect(mapNotificationLink('/portal/krs', st), RoutePaths.studentKrs);
    expect(
      mapNotificationLink('/portal/jadwal-kuliah', st),
      RoutePaths.studentSchedule,
    );
    expect(
      mapNotificationLink('/portal/keuangan', st),
      RoutePaths.studentFinance,
    );
    expect(
      mapNotificationLink('/portal/keuangan/tagihan/01ABC', st),
      RoutePaths.studentInvoice('01ABC'),
    );
    expect(
      mapNotificationLink('/portal/tugas/9', st),
      RoutePaths.studentAssignment('9'),
    );
    expect(mapNotificationLink('/portal/ujian/', st), RoutePaths.studentExams);
    expect(
      mapNotificationLink('/portal/pengajuan?x=1', st),
      RoutePaths.studentRequests,
    );
    expect(
      mapNotificationLink('/portal/pengumuman', st),
      RoutePaths.studentAnnouncements,
    );
  });

  test('lecturer approval link goes to advising', () {
    expect(
      mapNotificationLink('/akademik/persetujuan-krs', lc),
      RoutePaths.lecturerAdvising,
    );
    expect(mapNotificationLink('/portal/krs', lc), isNull);
    expect(mapNotificationLink('/akademik/persetujuan-krs', st), isNull);
  });

  test('unknown or empty links return null', () {
    expect(mapNotificationLink(null, st), isNull);
    expect(mapNotificationLink('', st), isNull);
    expect(mapNotificationLink('/foo/bar', st), isNull);
    expect(mapNotificationLink('/portal/keuangan/zzz/1', st), isNull);
    expect(mapNotificationLink('/portal/krs', null), isNull);
  });

  test('tab routes detected', () {
    expect(isTabRoute(RoutePaths.studentFinance), isTrue);
    expect(isTabRoute(RoutePaths.studentKrs), isFalse);
  });
}
