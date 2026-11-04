/// Payload QR presensi dari dosen: `{ID sesi}.{token}`. Token berganti setiap
/// 30 detik di sisi server, jadi klien hanya meneruskannya tanpa mengubah isinya.
final _payloadPattern = RegExp(r'^[0-9a-hjkmnp-tv-z]{26}\.[0-9a-f]{32}$');

/// Mengembalikan payload bersih bila pindaian adalah QR presensi CivitasOne;
/// selain itu null, dan pindaian diabaikan tanpa memanggil server.
String? parseCheckInPayload(String raw) {
  final value = raw.trim();
  return _payloadPattern.hasMatch(value) ? value : null;
}
