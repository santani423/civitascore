import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class NotificationsStrings {
  const NotificationsStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  String get title => _p('Notifikasi', 'Notifications', '通知');
  String get markAllRead =>
      _p('Tandai semua dibaca', 'Mark all as read', '全部标为已读');
  String get unreadOnly =>
      _p('Hanya belum dibaca', 'Unread only', '仅显示未读');
  String get preferences =>
      _p('Preferensi notifikasi', 'Notification preferences', '通知偏好');
  String get emptyTitle =>
      _p('Belum ada notifikasi', 'No notifications yet', '暂无通知');
  String get emptyUnread => _p(
    'Semua notifikasi sudah dibaca.',
    'All notifications have been read.',
    '所有通知均已读。',
  );
  String get emptyBody => _p(
    'Notifikasi akademik, keuangan, dan layanan akan muncul di sini.',
    'Academic, finance and service notifications will appear here.',
    '学业、财务和服务通知将显示在这里。',
  );
  String get fallbackMessage =>
      _p('Notifikasi baru', 'New notification', '新通知');
  String get legacyMessage => _p(
    'Pemberitahuan sistem (tanpa rincian)',
    'System notice (no details)',
    '系统通知（无详情）',
  );
  String get allMarkedRead => _p(
    'Semua notifikasi ditandai dibaca.',
    'All notifications marked as read.',
    '所有通知已标为已读。',
  );
  String get unreadBadge => _p('Belum dibaca', 'Unread', '未读');

  // Preferences
  String get prefsTitle =>
      _p('Preferensi notifikasi', 'Notification preferences', '通知偏好');
  String get prefsIntro => _p(
    'Pilih kanal yang ingin Anda terima. Pengaturan berlaku untuk semua jenis notifikasi.',
    'Choose the channels you want to receive. Settings apply to all notification types.',
    '选择要接收的渠道。设置适用于所有通知类型。',
  );
  String get prefsPerEventNote => _p(
    'Preferensi per jenis peristiwa belum berfungsi di server; hanya pengaturan per kanal yang berlaku.',
    'Per-event preferences are not effective on the server yet; only per-channel settings apply.',
    '服务器尚不支持按事件设置偏好；仅按渠道设置有效。',
  );
  String get channelDatabase =>
      _p('Notifikasi di aplikasi', 'In-app notifications', '应用内通知');
  String get channelMail => _p('Email', 'Email', '电子邮件');
  String get channelPush => _p('Push', 'Push', '推送');
  String get channelWhatsapp => _p('WhatsApp', 'WhatsApp', 'WhatsApp');
  String get channelSms => _p('SMS', 'SMS', '短信');
  String get channelUnavailable => _p(
    'Kanal ini belum tersedia.',
    'This channel is not available yet.',
    '该渠道暂不可用。',
  );
  String get disableInAppTitle => _p(
    'Matikan notifikasi di aplikasi?',
    'Turn off in-app notifications?',
    '关闭应用内通知？',
  );
  String get disableInAppBody => _p(
    'Anda tidak akan menerima notifikasi baru di pusat notifikasi.',
    'You will no longer receive new notifications in the notification center.',
    '您将不再在通知中心收到新通知。',
  );
  String get disable => _p('Matikan', 'Turn off', '关闭');
  String get saved =>
      _p('Preferensi disimpan.', 'Preferences saved.', '偏好已保存。');

  String channelLabel(String channel) => switch (channel) {
    'database' => channelDatabase,
    'mail' => channelMail,
    'push' => channelPush,
    'whatsapp' => channelWhatsapp,
    'sms' => channelSms,
    _ => channel,
  };
}

extension NotificationsStringsX on BuildContext {
  NotificationsStrings get nt => NotificationsStrings(this);
}
