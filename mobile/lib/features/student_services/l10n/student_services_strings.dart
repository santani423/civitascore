import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';
import '../domain/student_request.dart';

class StudentServicesStrings {
  const StudentServicesStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // List
  String get listTitle => _p('Pengajuan saya', 'My requests', '我的申请');
  String get newRequest => _p('Pengajuan baru', 'New request', '新建申请');
  String get emptyTitle => _p('Belum ada pengajuan', 'No requests yet', '暂无申请');
  String get emptyBody => _p(
    'Ajukan cuti, aktif kembali, perubahan data, atau surat keterangan.',
    'Request leave, reactivation, data changes or certificates.',
    '可申请休学、复学、资料变更或证明信。',
  );
  String get emptyFilteredTitle =>
      _p('Tidak ada pengajuan', 'No matching requests', '没有符合条件的申请');
  String get emptyFilteredBody => _p(
    'Tidak ada pengajuan dengan status ini.',
    'No requests have this status.',
    '没有该状态的申请。',
  );
  String get created => _p('Dibuat', 'Created', '创建于');
  String get submitted => _p('Dikirim', 'Submitted', '提交于');

  // Types
  String typeLabel(String value, String? fallback) =>
      switch (RequestType.tryParse(value)) {
        RequestType.leave => _p('Cuti akademik', 'Academic leave', '学业休学'),
        RequestType.reactivation => _p('Aktif kembali', 'Reactivation', '复学'),
        RequestType.dataChange => _p('Perubahan data', 'Data change', '资料变更'),
        RequestType.letter => _p('Surat keterangan', 'Certificate', '证明信'),
        RequestType.other => _p('Pengajuan lainnya', 'Other request', '其他申请'),
        null => fallback ?? value,
      };

  String typeHint(RequestType t) => switch (t) {
    RequestType.leave => _p(
      'Hanya untuk mahasiswa berstatus Aktif. Semester cuti mengikuti semester berjalan.',
      'Only for students with Active status. The leave term is the current term.',
      '仅限在读（活跃）学生。休学学期为当前学期。',
    ),
    RequestType.reactivation => _p(
      'Hanya untuk mahasiswa berstatus Cuti atau Nonaktif.',
      'Only for students on Leave or Inactive.',
      '仅限休学或非活跃学生。',
    ),
    RequestType.dataChange => _p(
      'Pilih data induk yang ingin diubah. Perubahan diverifikasi Bagian Akademik.',
      'Choose the master data to change. Changes are verified by the Academic Office.',
      '选择要修改的主数据，修改需教务处核实。',
    ),
    RequestType.letter => _p(
      'Surat disetujui akan tersedia untuk diunduh sebagai PDF.',
      'Once approved, the letter can be downloaded as a PDF.',
      '获批后可下载PDF证明信。',
    ),
    RequestType.other => _p(
      'Jelaskan kebutuhan Anda pada deskripsi.',
      'Describe your request in the description.',
      '请在说明中描述您的需求。',
    ),
  };

  // Statuses
  String statusLabel(String value, String? fallback) =>
      switch (RequestStatus.tryParse(value)) {
        RequestStatus.draft => _p('Draft', 'Draft', '草稿'),
        RequestStatus.submitted => _p('Sedang ditinjau', 'Under review', '审核中'),
        RequestStatus.approved => _p('Disetujui', 'Approved', '已批准'),
        RequestStatus.rejected => _p('Ditolak', 'Rejected', '已驳回'),
        RequestStatus.cancelled => _p('Dibatalkan', 'Cancelled', '已取消'),
        null => fallback ?? value,
      };

  // Letter types
  String letterTypeLabel(String value, String? fallback) => switch (value) {
    'active_student' => _p(
      'Surat Keterangan Aktif Kuliah',
      'Certificate of Active Enrollment',
      '在读证明',
    ),
    'good_conduct' => _p(
      'Surat Keterangan Berkelakuan Baik',
      'Good Conduct Certificate',
      '品行良好证明',
    ),
    'recommendation' => _p('Surat Rekomendasi', 'Recommendation Letter', '推荐信'),
    'other' => _p('Surat Keterangan Lainnya', 'Other Certificate', '其他证明'),
    _ => fallback ?? value,
  };

  // Data change fields
  String fieldLabel(String key) => switch (key) {
    'name' => _p('Nama', 'Name', '姓名'),
    'email' => _p('Email', 'Email', '电子邮件'),
    'birth_place' => _p('Tempat lahir', 'Place of birth', '出生地'),
    'tanggal_lahir' => _p('Tanggal lahir', 'Date of birth', '出生日期'),
    'gender' => _p('Jenis kelamin', 'Gender', '性别'),
    _ => key,
  };
  String get male => _p('Laki-laki', 'Male', '男');
  String get female => _p('Perempuan', 'Female', '女');
  String genderLabel(String v) => v == 'male'
      ? male
      : v == 'female'
      ? female
      : v;

  // Form
  String get formTitleNew => _p('Pengajuan baru', 'New request', '新建申请');
  String get formTitleEdit => _p('Ubah draft', 'Edit draft', '编辑草稿');
  String get requestType => _p('Jenis pengajuan', 'Request type', '申请类型');
  String get title => _p('Judul', 'Title', '标题');
  String get description => _p('Deskripsi', 'Description', '说明');
  String get letterType => _p('Jenis surat', 'Letter type', '证明类型');
  String get purpose => _p('Keperluan', 'Purpose', '用途');
  String get changesSection =>
      _p('Data yang diubah', 'Data to change', '待修改数据');
  String get selectAtLeastOneChange => _p(
    'Pilih dan isi minimal satu data yang ingin diubah.',
    'Select and fill in at least one field to change.',
    '请至少选择并填写一项要修改的数据。',
  );
  String get pickDate => _p('Pilih tanggal', 'Pick a date', '选择日期');
  String get attachment => _p('Lampiran', 'Attachment', '附件');
  String get addAttachment => _p('Tambah lampiran', 'Add attachment', '添加附件');
  String get replaceAttachment =>
      _p('Ganti lampiran', 'Replace attachment', '更换附件');
  String get removeAttachment =>
      _p('Hapus lampiran', 'Remove attachment', '移除附件');
  String get uploading => _p('Mengunggah…', 'Uploading…', '正在上传…');
  String get saveDraft => _p('Simpan draft', 'Save draft', '保存草稿');
  String get submitRequest => _p('Kirim pengajuan', 'Submit request', '提交申请');
  String get titleMin =>
      _p('Minimal 5 karakter', 'At least 5 characters', '至少5个字符');
  String get draftSaved => _p('Draft disimpan', 'Draft saved', '草稿已保存');
  String get submittedOk =>
      _p('Pengajuan dikirim', 'Request submitted', '申请已提交');
  String get savedButNotSubmitted => _p(
    'Draft tersimpan, tetapi belum terkirim.',
    'Draft saved but not submitted.',
    '草稿已保存，但尚未提交。',
  );

  // Detail
  String get detailTitle => _p('Detail pengajuan', 'Request details', '申请详情');
  String get status => _p('Status', 'Status', '状态');
  String get type => _p('Jenis', 'Type', '类型');
  String get term => _p('Semester', 'Term', '学期');
  String get currentStep => _p('Tahap saat ini', 'Current step', '当前环节');
  String get reviewer => _p('Peninjau', 'Reviewer', '审核人');
  String get decidedAt => _p('Diputuskan', 'Decided', '决定时间');
  String get decisionNote => _p('Catatan keputusan', 'Decision note', '决定备注');
  String get cancelledAt => _p('Dibatalkan', 'Cancelled', '取消于');
  String get details => _p('Rincian', 'Details', '明细');
  String get timeline => _p('Riwayat persetujuan', 'Approval history', '审批记录');
  String get noHistory => _p('Belum ada riwayat.', 'No history yet.', '暂无记录。');
  String get editDraft => _p('Ubah draft', 'Edit draft', '编辑草稿');
  String get submitNow => _p('Kirim sekarang', 'Submit now', '立即提交');
  String get cancelRequest =>
      _p('Batalkan pengajuan', 'Cancel request', '取消申请');
  String get cancelConfirmTitle =>
      _p('Batalkan pengajuan?', 'Cancel this request?', '取消该申请？');
  String get cancelConfirmBody => _p(
    'Pengajuan yang dibatalkan tidak dapat dilanjutkan.',
    'A cancelled request cannot be resumed.',
    '取消后的申请无法恢复。',
  );
  String get cancelConfirmAction => _p('Ya, batalkan', 'Yes, cancel', '是，取消');
  String get cancelled =>
      _p('Pengajuan dibatalkan', 'Request cancelled', '申请已取消');
  String get downloadLetter =>
      _p('Unduh surat (PDF)', 'Download letter (PDF)', '下载证明信（PDF）');
  String get resubmitPendingTitle =>
      _p('Kirim ulang pengajuan', 'Resubmit request', '重新提交申请');
  String get resubmitPendingBody => _p(
    'Pengajuan yang dikembalikan untuk diperbaiki belum didukung layanan backend. Buat pengajuan baru bila ditolak.',
    'Returning a request for revision is not yet supported by the backend. Create a new request if rejected.',
    '后端暂不支持退回修改。若被驳回，请新建申请。',
  );
  String historyEvent(String? event) => switch (event) {
    'submitted' => _p('Dikirim', 'Submitted', '已提交'),
    'approved' => _p('Disetujui', 'Approved', '已批准'),
    'rejected' => _p('Ditolak', 'Rejected', '已驳回'),
    'cancelled' => _p('Dibatalkan', 'Cancelled', '已取消'),
    null || '' => '',
    _ => event.replaceAll('_', ' '),
  };
}

extension StudentServicesStringsX on BuildContext {
  StudentServicesStrings get sv => StudentServicesStrings(this);
}
