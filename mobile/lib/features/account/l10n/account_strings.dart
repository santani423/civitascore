import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class AccountStrings {
  const AccountStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  String get title => _p('Akun', 'Account', '账户');
  String get myProfile => _p('Profil saya', 'My profile', '我的资料');
  String get myProfileSubtitleStudent => _p(
    'Biodata, data akademik, dan kontak',
    'Personal, academic and contact details',
    '个人、学业与联系方式',
  );
  String get myProfileSubtitleLecturer => _p(
    'Data dosen dan nomor telepon',
    'Lecturer details and phone number',
    '教师信息与电话',
  );
  String get services =>
      _p('Layanan & pengajuan', 'Services & requests', '服务与申请');
  String get servicesSubtitle => _p(
    'Cuti, aktif kembali, surat keterangan',
    'Leave, reactivation, certificates',
    '休学、复学、证明信',
  );
  String get hrSelfService =>
      _p('Layanan kepegawaian', 'HR self-service', '人事自助');
  String get hrSubtitle => _p(
    'Profil SDM, cuti/izin, pengajuan',
    'HR profile, leave, requests',
    '人事资料、请假、申请',
  );
  String get pendingSubtitle => _p(
    'Fitur terdokumentasi yang menunggu layanan backend',
    'Documented features awaiting backend services',
    '已设计但等待后端服务的功能',
  );
  String get switchExperience =>
      _p('Ganti tampilan peran', 'Switch role view', '切换角色视图');
  String get preferences => _p('Preferensi', 'Preferences', '偏好设置');
  String get security => _p('Keamanan', 'Security', '安全');
  String get language => _p('Bahasa', 'Language', '语言');
  String get theme => _p('Tema', 'Theme', '主题');
  String get themeSystem => _p('Ikuti sistem', 'System default', '跟随系统');
  String get themeLight => _p('Terang', 'Light', '浅色');
  String get themeDark => _p('Gelap', 'Dark', '深色');
  String get biometric =>
      _p('Kunci dengan biometrik', 'Biometric lock', '生物识别锁');
  String get biometricSubtitle => _p(
    'Minta sidik jari/wajah/PIN saat membuka aplikasi',
    'Ask for fingerprint/face/PIN when opening the app',
    '打开应用时验证指纹/面容/密码',
  );
  String get biometricUnsupported => _p(
    'Perangkat tidak mendukung kunci biometrik atau belum ada kunci layar.',
    'This device has no biometric or screen lock set up.',
    '本设备未设置生物识别或屏幕锁。',
  );
  String get changePassword =>
      _p('Ganti kata sandi', 'Change password', '修改密码');
  String get sessions => _p('Sesi & perangkat', 'Sessions & devices', '会话与设备');
  String get about => _p('Tentang aplikasi', 'About', '关于');
  String get version => _p('Versi', 'Version', '版本');
  String get server => _p('Server', 'Server', '服务器');
  String get offlineProfile => _p(
    'Profil dari penyimpanan lokal (offline)',
    'Profile from local storage (offline)',
    '本地缓存的资料（离线）',
  );
}

extension AccountStringsX on BuildContext {
  AccountStrings get accountL10n => AccountStrings(this);
}
