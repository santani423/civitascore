import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class AuthStrings {
  const AuthStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  String get welcome => _p('Selamat datang', 'Welcome', '欢迎');
  String get loginSubtitle => _p(
    'Masuk untuk mengakses layanan akademik Anda.',
    'Sign in to access your academic services.',
    '登录以使用您的学业服务。',
  );
  String get asStudent => _p('Mahasiswa', 'Student', '学生');
  String get asLecturer => _p('Dosen', 'Lecturer', '教师');
  String get nim => _p('NIM', 'Student ID (NIM)', '学号 (NIM)');
  String get nimHint =>
      _p('Masukkan NIM Anda', 'Enter your student ID', '请输入学号');
  String get email => _p('Email', 'Email', '邮箱');
  String get emailHint =>
      _p('nama@kampus.ac.id', 'name@campus.ac.id', 'name@campus.ac.id');
  String get password => _p('Kata sandi', 'Password', '密码');
  String get showPassword =>
      _p('Tampilkan kata sandi', 'Show password', '显示密码');
  String get hidePassword =>
      _p('Sembunyikan kata sandi', 'Hide password', '隐藏密码');
  String get signIn => _p('Masuk', 'Sign in', '登录');
  String get forgotPassword =>
      _p('Lupa kata sandi?', 'Forgot password?', '忘记密码？');
  String get studentPasswordHint => _p(
    'Mahasiswa baru: kata sandi awal biasanya tanggal lahir (DDMMYYYY) dan wajib diganti setelah masuk.',
    'New students: the initial password is usually your birth date (DDMMYYYY) and must be changed after signing in.',
    '新生：初始密码通常为出生日期（DDMMYYYY），登录后须修改。',
  );

  String get forgotTitle => _p('Lupa kata sandi', 'Forgot password', '忘记密码');
  String get forgotBody => _p(
    'Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi.',
    'Enter your account email. We will send a link to reset your password.',
    '请输入账户邮箱，我们将发送重置密码的链接。',
  );
  String get sendLink => _p('Kirim tautan', 'Send link', '发送链接');
  String get linkSent => _p(
    'Tautan reset kata sandi telah dikirim. Periksa email Anda.',
    'A reset link has been sent. Check your email.',
    '重置链接已发送，请查收邮件。',
  );
  String get forgotNimNote => _p(
    'Tidak punya email terdaftar? Hubungi Bagian Akademik untuk mengatur ulang kata sandi.',
    'No registered email? Contact the Academic Office to reset your password.',
    '没有登记邮箱？请联系教务处重置密码。',
  );

  String get resetTitle =>
      _p('Atur ulang kata sandi', 'Reset password', '重置密码');
  String get resetToken => _p('Token reset', 'Reset token', '重置令牌');
  String get resetTokenHelp => _p(
    'Salin token dari tautan di email Anda.',
    'Copy the token from the link in your email.',
    '从邮件链接中复制令牌。',
  );
  String get newPassword => _p('Kata sandi baru', 'New password', '新密码');
  String get confirmPassword =>
      _p('Konfirmasi kata sandi baru', 'Confirm new password', '确认新密码');
  String get resetDone => _p(
    'Kata sandi berhasil diatur ulang. Silakan masuk.',
    'Password reset. Please sign in.',
    '密码已重置，请登录。',
  );

  String get changeTitle => _p('Ganti kata sandi', 'Change password', '修改密码');
  String get forcedChangeBody => _p(
    'Demi keamanan, Anda wajib mengganti kata sandi awal sebelum melanjutkan.',
    'For your security, you must change your initial password before continuing.',
    '为保障安全，继续前须修改初始密码。',
  );
  String get currentPassword =>
      _p('Kata sandi saat ini', 'Current password', '当前密码');
  String get passwordRule => _p(
    'Minimal 8 karakter dan berbeda dari kata sandi saat ini.',
    'At least 8 characters and different from the current one.',
    '至少 8 个字符，且不同于当前密码。',
  );
  String get changeDone =>
      _p('Kata sandi berhasil diperbarui.', 'Password updated.', '密码已更新。');

  String get sessionsTitle =>
      _p('Sesi & perangkat', 'Sessions & devices', '会话与设备');
  String get activeSessions => _p('Sesi aktif', 'Active sessions', '活跃会话');
  String get devices => _p('Perangkat', 'Devices', '设备');
  String get revoke => _p('Cabut', 'Revoke', '撤销');
  String get revokeConfirm => _p(
    'Cabut sesi ini? Perangkat tersebut akan keluar.',
    'Revoke this session? That device will be signed out.',
    '撤销此会话？该设备将被登出。',
  );
  String get revoked => _p('Sesi dicabut', 'Session revoked', '会话已撤销');
  String get logoutAll =>
      _p('Keluar dari semua perangkat', 'Sign out of all devices', '登出所有设备');
  String get logoutAllConfirm => _p(
    'Semua sesi termasuk perangkat ini akan diakhiri.',
    'All sessions, including this device, will be ended.',
    '包括本设备在内的所有会话都将结束。',
  );
  String get lastActive => _p('Terakhir aktif', 'Last active', '最近活动');
  String get trusted => _p('Tepercaya', 'Trusted', '可信');
  String get unknownDevice =>
      _p('Perangkat tidak dikenal', 'Unknown device', '未知设备');
  String get revokedLabel => _p('Dicabut', 'Revoked', '已撤销');

  String get lockTitle => _p('Aplikasi terkunci', 'App locked', '应用已锁定');
  String get lockBody => _p(
    'Verifikasi sidik jari, wajah, atau PIN perangkat untuk melanjutkan.',
    'Verify with fingerprint, face, or device PIN to continue.',
    '请使用指纹、面容或设备密码验证以继续。',
  );
  String get unlock => _p('Buka kunci', 'Unlock', '解锁');
  String get unlockReason =>
      _p('Buka Civitas One', 'Unlock Civitas One', '解锁 Civitas One');
  String get useAnotherAccount => _p(
    'Keluar dan gunakan akun lain',
    'Sign out and use another account',
    '登出并使用其他账户',
  );
}

extension AuthStringsX on BuildContext {
  AuthStrings get authL10n => AuthStrings(this);
}
