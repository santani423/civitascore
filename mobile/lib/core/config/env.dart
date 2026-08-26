import 'flavor.dart';

abstract final class Env {
  static late final Flavor flavor;
  static late final String apiBaseUrl;
  static late final String apiPinnedCertSha256;

  static void init(Flavor f) {
    flavor = f;
    apiBaseUrl = const String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: 'http://10.0.2.2:8000/api/v1',
    );
    apiPinnedCertSha256 = const String.fromEnvironment(
      'API_PINNED_CERT_SHA256',
    );
  }

  static String get appName => switch (flavor) {
    Flavor.dev => 'CivitasOne Dev',
    Flavor.staging => 'CivitasOne Staging',
    Flavor.prod => 'CivitasOne',
  };
}
