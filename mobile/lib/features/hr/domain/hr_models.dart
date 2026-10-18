import '../../../core/network/api_envelope.dart';
import '../../../core/theme/app_palette.dart';

/// Enum values exactly as the backend (StoreLeaveRequest / StoreHrRequest).
const kLeaveTypes = ['annual', 'sick', 'maternity', 'special', 'permission'];
const kHrRequestTypes = [
  'transfer',
  'data_change',
  'promotion',
  'document',
  'other',
];

StatusTone leaveStatusTone(String status) => switch (status) {
  'approved' => StatusTone.success,
  'submitted' || 'pending' => StatusTone.info,
  'returned' => StatusTone.warning,
  'rejected' => StatusTone.danger,
  _ => StatusTone.neutral,
};

class LecturerProfile {
  const LecturerProfile({
    required this.id,
    required this.name,
    this.nidn,
    this.nip,
    this.email,
    this.phone,
    this.facultyName,
    this.employmentStatus,
    this.functionalRank,
    this.highestEducation,
    this.hiredAt,
    this.isActive = true,
  });

  final String id;
  final String name;
  final String? nidn;
  final String? nip;
  final String? email;
  final String? phone;
  final String? facultyName;
  final String? employmentStatus;
  final String? functionalRank;
  final String? highestEducation;
  final DateTime? hiredAt;
  final bool isActive;

  factory LecturerProfile.fromJson(Map<String, dynamic> j) => LecturerProfile(
    id: asString(j['id']) ?? '',
    name: asString(j['name']) ?? '',
    nidn: asString(j['nidn']),
    nip: asString(j['nip']),
    email: asString(j['email']),
    phone: asString(j['phone']),
    facultyName: asString(j['faculty_name']),
    employmentStatus: asString(j['employment_status']),
    functionalRank: asString(j['functional_rank']),
    highestEducation: asString(j['highest_education']),
    hiredAt: asDateTime(j['hired_at']),
    isActive: asBool(j['is_active'], fallback: true),
  );
}

class LeaveQuota {
  const LeaveQuota({
    required this.year,
    required this.quota,
    required this.used,
    required this.remaining,
  });
  final int year;
  final double quota;
  final double used;
  final double remaining;

  factory LeaveQuota.fromJson(Map<String, dynamic> j) => LeaveQuota(
    year: asInt(j['year']) ?? DateTime.now().year,
    quota: asDouble(j['quota']) ?? 0,
    used: asDouble(j['used']) ?? 0,
    remaining: asDouble(j['remaining']) ?? 0,
  );
}

class HrProfile {
  const HrProfile({
    required this.id,
    required this.name,
    this.employeeType,
    this.nip,
    this.nidn,
    this.position,
    this.unit,
    this.facultyName,
    this.studyProgramName,
    this.academicRank,
    this.rankName,
    this.rankGrade,
    this.serdosNumber,
    this.expertise,
    this.lecturerStatus,
    this.highestEducation,
    this.supervisorName,
    this.joinedAt,
    this.quota,
  });

  final String id;
  final String name;
  final String? employeeType;
  final String? nip;
  final String? nidn;
  final String? position;
  final String? unit;
  final String? facultyName;
  final String? studyProgramName;
  final String? academicRank;
  final String? rankName;
  final String? rankGrade;
  final String? serdosNumber;
  final String? expertise;
  final String? lecturerStatus;
  final String? highestEducation;
  final String? supervisorName;
  final DateTime? joinedAt;
  final LeaveQuota? quota;

  factory HrProfile.fromJson(Map<String, dynamic> json) {
    final e = asMap(json['employee']);
    final lecturer = asMap(e['lecturer']);
    final rank = asMap(e['rank']);
    final quotaMap = json['leave_quota'];
    String? label(String key) => asString(e['${key}_label']) ?? asString(e[key]);
    return HrProfile(
      id: asString(e['id']) ?? '',
      name: asString(e['name']) ?? '',
      employeeType: label('employee_type'),
      nip: asString(e['nip']),
      nidn: asString(e['nidn']) ?? asString(lecturer['nidn']),
      position: asString(e['position']),
      unit: asString(e['work_unit_name']) ?? asString(e['unit_kerja']),
      facultyName: asString(e['faculty_name']),
      studyProgramName: asString(e['study_program_name']),
      academicRank:
          asString(e['academic_rank_label']) ??
          asString(lecturer['academic_rank_label']) ??
          asString(e['academic_rank']) ??
          asString(lecturer['academic_rank']),
      rankName: asString(rank['name']),
      rankGrade: asString(rank['grade']),
      serdosNumber: asString(lecturer['serdos_number']),
      expertise: asString(lecturer['expertise']),
      lecturerStatus:
          asString(lecturer['lecturer_status_label']) ??
          asString(lecturer['lecturer_status']),
      highestEducation: label('highest_education'),
      supervisorName: asString(asMap(e['supervisor'])['name']),
      joinedAt: asDateTime(e['joined_at']),
      quota: quotaMap is Map
          ? LeaveQuota.fromJson(asMap(quotaMap))
          : null,
    );
  }
}

class LeaveRequest {
  const LeaveRequest({
    required this.id,
    required this.leaveType,
    required this.leaveTypeLabel,
    required this.status,
    required this.statusLabel,
    this.startDate,
    this.endDate,
    this.days,
    this.reason,
    this.attachmentFileId,
    this.approvalNote,
    this.submittedAt,
  });

  final String id;
  final String leaveType;
  final String leaveTypeLabel;
  final String status;
  final String statusLabel;
  final DateTime? startDate;
  final DateTime? endDate;
  final double? days;
  final String? reason;
  final String? attachmentFileId;
  final String? approvalNote;
  final DateTime? submittedAt;

  bool get canSubmit => status == 'draft';
  bool get canCancel => status == 'draft' || status == 'submitted';

  factory LeaveRequest.fromJson(Map<String, dynamic> j) => LeaveRequest(
    id: asString(j['id']) ?? '',
    leaveType: asString(j['leave_type']) ?? '',
    leaveTypeLabel:
        asString(j['leave_type_label']) ?? asString(j['leave_type']) ?? '',
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']) ?? asString(j['status']) ?? '',
    startDate: asDateTime(j['start_date']),
    endDate: asDateTime(j['end_date']),
    days: asDouble(j['days']),
    reason: asString(j['reason']),
    attachmentFileId: asString(j['attachment_file_id']),
    approvalNote: asString(j['approval_note']),
    submittedAt: asDateTime(j['submitted_at']),
  );
}

class HrRequest {
  const HrRequest({
    required this.id,
    required this.type,
    required this.typeLabel,
    required this.title,
    required this.status,
    required this.statusLabel,
    this.description,
    this.approvalNote,
    this.createdAt,
    this.processedAt,
    this.requiresProcessing = false,
  });

  final String id;
  final String type;
  final String typeLabel;
  final String title;
  final String status;
  final String statusLabel;
  final String? description;
  final String? approvalNote;
  final DateTime? createdAt;
  final DateTime? processedAt;
  final bool requiresProcessing;

  bool get canCancel => status == 'pending' || status == 'returned';

  factory HrRequest.fromJson(Map<String, dynamic> j) => HrRequest(
    id: asString(j['id']) ?? '',
    type: asString(j['type']) ?? '',
    typeLabel: asString(j['type_label']) ?? asString(j['type']) ?? '',
    title: asString(j['title']) ?? '',
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']) ?? asString(j['status']) ?? '',
    description: asString(j['description']),
    approvalNote: asString(j['approval_note']),
    createdAt: asDateTime(j['created_at']),
    processedAt: asDateTime(j['processed_at']),
    requiresProcessing: asBool(j['requires_processing']),
  );
}
