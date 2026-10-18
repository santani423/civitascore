import 'flavor.dart';

/// Build-time configuration (`--dart-define`). The app is built per
/// university: the backend has no public endpoint to discover universities,
/// so the university code is baked in and sent with NIM logins
/// (`university_code`). The university ULID is learned after login from
/// `GET /tenant/profile` and stored for the `X-University-ID` header.
abstract final class Env {
  static late final Flavor flavor;
  static late final String apiBaseUrl;
  static late final String universityCode;
  static late final String universityId;
  static late final String apiPinnedCertSha256;

  static void init(Flavor f) {
    flavor = f;
    apiBaseUrl = const String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: 'http://10.0.2.2:8000/api/v1',
    );
    universityCode = const String.fromEnvironment('UNIVERSITY_CODE');
    universityId = const String.fromEnvironment('UNIVERSITY_ID');
    apiPinnedCertSha256 = const String.fromEnvironment(
      'API_PINNED_CERT_SHA256',
    );
  }

  static String get appName => switch (flavor) {
    Flavor.dev => 'Civitas One Dev',
    Flavor.staging => 'Civitas One Staging',
    Flavor.prod => 'Civitas One',
  };

  static bool get isProduction => flavor == Flavor.prod;
}
