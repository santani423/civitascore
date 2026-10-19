import '../../../core/network/api_envelope.dart';

const kAnnouncementFilters = [
  'all',
  'unread',
  'study_program',
  'faculty',
  'admission_year',
  'semester',
  'students',
];

const kAnnouncementSearchMax = 100;

class AnnouncementAttachment {
  const AnnouncementAttachment({
    required this.id,
    required this.name,
    this.sizeBytes,
    this.mimeType,
  });

  factory AnnouncementAttachment.fromJson(Map<String, dynamic> j) =>
      AnnouncementAttachment(
        id: asString(j['id']) ?? '',
        name: asString(j['name']) ?? 'attachment',
        sizeBytes: asInt(j['size_bytes']),
        mimeType: asString(j['mime_type']),
      );

  final String id;
  final String name;
  final int? sizeBytes;
  final String? mimeType;
}

class Announcement {
  const Announcement({
    required this.id,
    required this.title,
    required this.body,
    this.targetScope,
    this.audience,
    this.isPinned = false,
    this.publishedAt,
    this.creatorName,
    this.attachment,
    this.isRead = false,
  });

  factory Announcement.fromJson(Map<String, dynamic> j) {
    final att = j['attachment'];
    return Announcement(
      id: asString(j['id']) ?? '',
      title: asString(j['title']) ?? '',
      body: asString(j['body']) ?? '',
      targetScope: asString(j['target_scope']),
      audience: asString(j['audience']),
      isPinned: asBool(j['is_pinned']),
      publishedAt: asDateTime(j['published_at']),
      creatorName: asString(j['creator_name']),
      attachment: att is Map
          ? AnnouncementAttachment.fromJson(asMap(att))
          : null,
      isRead: asBool(j['is_read']),
    );
  }

  final String id;
  final String title;
  final String body;
  final String? targetScope;
  final String? audience;
  final bool isPinned;
  final DateTime? publishedAt;
  final String? creatorName;
  final AnnouncementAttachment? attachment;
  final bool isRead;

  Announcement copyWithRead() => Announcement(
    id: id,
    title: title,
    body: body,
    targetScope: targetScope,
    audience: audience,
    isPinned: isPinned,
    publishedAt: publishedAt,
    creatorName: creatorName,
    attachment: attachment,
    isRead: true,
  );
}

/// Renders a (possibly rich-text) body safely as plain text: line-break tags
/// become newlines, list items bullets, all other tags are stripped and basic
/// entities decoded. Nothing is ever interpreted as markup.
String plainTextFromBody(String body) {
  var t = body.replaceAll('\r\n', '\n');
  t = t.replaceAll(RegExp(r'<\s*br\s*/?>', caseSensitive: false), '\n');
  t = t.replaceAll(RegExp(r'<\s*li[^>]*>', caseSensitive: false), '• ');
  t = t.replaceAll(
    RegExp(r'<\s*/\s*(p|div|li|h[1-6])\s*>', caseSensitive: false),
    '\n',
  );
  t = t.replaceAll(RegExp(r'<[^>]*>'), '');
  t = t
      .replaceAll('&nbsp;', ' ')
      .replaceAll('&lt;', '<')
      .replaceAll('&gt;', '>')
      .replaceAll('&quot;', '"')
      .replaceAll('&#39;', "'")
      .replaceAll('&amp;', '&');
  t = t.replaceAll(RegExp(r'\n{3,}'), '\n\n');
  return t.trim();
}
