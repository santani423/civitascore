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

  /// Public web (SPA) origin, e.g. `https://civitas.atmajaya.ac.id`, used to
  /// build links that open in the web app (exam access links `/exam/{token}`).
  /// Falls back to the API origin when not provided.
  static late final String webBaseUrl;

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
    const web = String.fromEnvironment('WEB_BASE_URL');
    webBaseUrl = web.isNotEmpty ? web : Uri.parse(apiBaseUrl).origin;
  }

  static String get appName => switch (flavor) {
    Flavor.dev => 'Civitas One Dev',
    Flavor.staging => 'Civitas One Staging',
    Flavor.prod => 'Civitas One',
  };

  static bool get isProduction => flavor == Flavor.prod;
}
