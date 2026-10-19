import 'package:flutter/material.dart';

import '../../../core/network/api_envelope.dart';
import '../../../core/theme/app_palette.dart';

/// Request types known to the backend (`StudentRequestType`).
enum RequestType {
  leave('leave', Icons.pause_circle_outline),
  reactivation('reactivation', Icons.play_circle_outline),
  dataChange('data_change', Icons.edit_note_outlined),
  letter('letter', Icons.description_outlined),
  other('other', Icons.help_outline);

  const RequestType(this.value, this.icon);
  final String value;
  final IconData icon;

  static RequestType? tryParse(String? value) {
    for (final t in values) {
      if (t.value == value) return t;
    }
    return null;
  }
}

/// Request statuses (`StudentRequestStatus`). The backend has no
/// "returned"/resubmit state (matrix MOB-SRV-11 stays pending).
enum RequestStatus {
  draft('draft', StatusTone.neutral, Icons.edit_outlined),
  submitted('submitted', StatusTone.info, Icons.hourglass_top_rounded),
  approved('approved', StatusTone.success, Icons.check_circle_outline),
  rejected('rejected', StatusTone.danger, Icons.cancel_outlined),
  cancelled('cancelled', StatusTone.neutral, Icons.block);

  const RequestStatus(this.value, this.tone, this.icon);
  final String value;
  final StatusTone tone;
  final IconData icon;

  static RequestStatus? tryParse(String? value) {
    for (final s in values) {
      if (s.value == value) return s;
    }
    return null;
  }
}

class RequestAttachment {
  const RequestAttachment({required this.id, this.name, this.sizeBytes});

  factory RequestAttachment.fromJson(Map<String, dynamic> json) =>
      RequestAttachment(
        id: asString(json['id']) ?? '',
        name: asString(json['name']),
        sizeBytes: asInt(json['size_bytes']),
      );

  final String id;
  final String? name;
  final int? sizeBytes;
}

class RequestHistoryEntry {
  const RequestHistoryEntry({this.event, this.description, this.createdAt});

  factory RequestHistoryEntry.fromJson(Map<String, dynamic> json) =>
      RequestHistoryEntry(
        event: asString(json['event']),
        description: asString(json['description']),
        createdAt: asDateTime(json['created_at']),
      );

  final String? event;
  final String? description;
  final DateTime? createdAt;
}

class StudentRequest {
  const StudentRequest({
    required this.id,
    required this.typeValue,
    required this.typeLabel,
    required this.statusValue,
    required this.statusLabel,
    required this.title,
    this.description,
    this.payload = const {},
    this.letterTypeLabel,
    this.attachment,
    this.termLabel,
    this.submittedAt,
    this.decidedAt,
    this.reviewer,
    this.decisionNote,
    this.cancelledAt,
    this.currentStepName,
    this.history = const [],
    this.hasLetter = false,
    this.createdAt,
  });

  factory StudentRequest.fromJson(Map<String, dynamic> json) {
    final step = asMap(json['current_step']);
    final attachment = asMap(json['attachment']);
    return StudentRequest(
      id: asString(json['id']) ?? '',
      typeValue: asString(json['type']) ?? '',
      typeLabel: asString(json['type_label']),
      statusValue: asString(json['status']) ?? '',
      statusLabel: asString(json['status_label']),
      title: asString(json['title']) ?? '',
      description: asString(json['description']),
      payload: asMap(json['payload']),
      letterTypeLabel: asString(json['letter_type_label']),
      attachment: attachment.isEmpty
          ? null
          : RequestAttachment.fromJson(attachment),
      termLabel: asString(asMap(json['term'])['label']),
      submittedAt: asDateTime(json['submitted_at']),
      decidedAt: asDateTime(json['decided_at']),
      reviewer: asString(json['reviewer']),
      decisionNote: asString(json['decision_note']),
      cancelledAt: asDateTime(json['cancelled_at']),
      currentStepName: step.isEmpty ? null : asString(step['name']),
      history: asMapList(
        json['history'],
      ).map(RequestHistoryEntry.fromJson).toList(),
      hasLetter: asBool(json['has_letter']),
      createdAt: asDateTime(json['created_at']),
    );
  }

  final String id;
  final String typeValue;
  final String? typeLabel;
  final String statusValue;
  final String? statusLabel;
  final String title;
  final String? description;
  final Map<String, dynamic> payload;
  final String? letterTypeLabel;
  final RequestAttachment? attachment;
  final String? termLabel;
  final DateTime? submittedAt;
  final DateTime? decidedAt;
  final String? reviewer;
  final String? decisionNote;
  final DateTime? cancelledAt;
  final String? currentStepName;
  final List<RequestHistoryEntry> history;
  final bool hasLetter;
  final DateTime? createdAt;

  RequestType? get type => RequestType.tryParse(typeValue);
  RequestStatus? get status => RequestStatus.tryParse(statusValue);
  bool get isDraft => status == RequestStatus.draft;
  bool get isSubmitted => status == RequestStatus.submitted;
  bool get canCancel => isDraft || isSubmitted;

  /// `payload.changes` of a data-change request.
  Map<String, dynamic> get changes => asMap(payload['changes']);
}

class LetterTypeOption {
  const LetterTypeOption({required this.value, required this.label});

  factory LetterTypeOption.fromJson(Map<String, dynamic> json) =>
      LetterTypeOption(
        value: asString(json['value']) ?? '',
        label: asString(json['label']) ?? '',
      );

  final String value;
  final String label;
}

/// `GET /student/requests/options`.
class RequestOptions {
  const RequestOptions({
    this.letterTypes = const [],
    this.dataChangeFields = const [],
  });

  factory RequestOptions.fromJson(dynamic data) {
    final m = asMap(data);
    return RequestOptions(
      letterTypes: asMapList(
        m['letter_types'],
      ).map(LetterTypeOption.fromJson).toList(),
      dataChangeFields: asStringList(m['data_change_fields']),
    );
  }

  final List<LetterTypeOption> letterTypes;
  final List<String> dataChangeFields;
}
