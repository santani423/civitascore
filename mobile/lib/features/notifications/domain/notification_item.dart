import '../../../core/network/api_envelope.dart';

/// `GET /notifications` item `{id, event_key, message, link, read_at,
/// created_at}`. Legacy notifications have null `event_key` and `message`.
class NotificationItem {
  const NotificationItem({
    required this.id,
    this.eventKey,
    this.message,
    this.link,
    this.readAt,
    this.createdAt,
  });

  factory NotificationItem.fromJson(Map<String, dynamic> j) => NotificationItem(
    id: asString(j['id']) ?? '',
    eventKey: asString(j['event_key']),
    message: asString(j['message']),
    link: asString(j['link']),
    readAt: asDateTime(j['read_at']),
    createdAt: asDateTime(j['created_at']),
  );

  final String id;
  final String? eventKey;
  final String? message;
  final String? link;
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isRead => readAt != null;

  NotificationItem markedRead() => NotificationItem(
    id: id,
    eventKey: eventKey,
    message: message,
    link: link,
    readAt: readAt ?? DateTime.now(),
    createdAt: createdAt,
  );
}

/// `GET /notification-preferences` row `{id, channel, notification_type,
/// is_enabled}`.
class NotificationPreference {
  const NotificationPreference({
    required this.id,
    required this.channel,
    required this.type,
    required this.enabled,
  });

  factory NotificationPreference.fromJson(Map<String, dynamic> j) =>
      NotificationPreference(
        id: asString(j['id']) ?? '',
        channel: asString(j['channel']) ?? '',
        type: asString(j['notification_type']) ?? '*',
        enabled: asBool(j['is_enabled'], fallback: true),
      );

  final String id;
  final String channel;
  final String type;
  final bool enabled;
}

/// Channels the backend actually delivers (NotificationChannel::isImplemented).
const kImplementedChannels = ['database', 'mail'];
const kPendingChannels = ['push', 'whatsapp', 'sms'];

/// Whether [channel] is on for the general (`*`) preference. No row = enabled.
bool channelEnabled(List<NotificationPreference> prefs, String channel) {
  for (final p in prefs) {
    if (p.channel == channel && p.type == '*') return p.enabled;
  }
  return true;
}
