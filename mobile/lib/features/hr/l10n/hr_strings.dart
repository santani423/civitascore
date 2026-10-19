import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class HrStrings {
  const HrStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // Lecturer profile ---------------------------------------------------------
  String get lecturerProfileTitle =>
      _p('Profil dosen', 'Lecturer profile', '教师资料');
  String get readOnlyNote => _p(
    'Data ini dikelola Bagian SDM. Anda hanya dapat mengubah nomor telepon.',
    'This data is managed by HR. You can only change your phone number.',
    '这些数据由人事部门管理，您只能修改电话号码。',
  );
  String get nidn => 'NIDN';
  String get nip => 'NIP';
  String get name => _p('Nama', 'Name', '姓名');
  String get email => 'Email';
  String get phone => _p('Nomor telepon', 'Phone number', '电话号码');
  String get faculty => _p('Fakultas', 'Faculty', '学院');
  String get studyProgram => _p('Program studi', 'Study program', '专业');
  String get employmentStatus =>
      _p('Status kepegawaian', 'Employment status', '雇佣状态');
  String get functionalRank =>
      _p('Jabatan fungsional', 'Functional rank', '职称');
  String get highestEducation =>
      _p('Pendidikan terakhir', 'Highest education', '最高学历');
  String get hiredAt => _p('Tanggal bergabung', 'Hired on', '入职日期');
  String get activeStatus => _p('Status', 'Status', '状态');
  String get active => _p('Aktif', 'Active', '在职');
  String get inactive => _p('Nonaktif', 'Inactive', '停用');
  String get editPhone => _p('Ubah nomor telepon', 'Edit phone number', '修改电话');
  String get phoneHint => _p(
    'Kosongkan untuk menghapus nomor',
    'Leave empty to remove the number',
    '留空以删除号码',
  );
  String get phoneSaved => _p('Profil diperbarui', 'Profile updated', '资料已更新');
  String get lecturerNotLinkedTitle => _p(
    'Akun belum tertaut ke data dosen',
    'Account not linked to lecturer data',
    '账号尚未关联教师数据',
  );
  String get lecturerNotLinkedBody => _p(
    'Hubungi Bagian SDM agar akun Anda ditautkan ke data dosen.',
    'Contact HR so your account can be linked to your lecturer record.',
    '请联系人事部门将您的账号关联到教师记录。',
  );
  String get hrProfileLink => _p('Profil kepegawaian', 'HR profile', '人事资料');

  String employmentLabel(String? v) => switch (v) {
    'permanent' => _p('Tetap', 'Permanent', '正式'),
    'contract' => _p('Kontrak', 'Contract', '合同'),
    'honorary' => _p('Honorer', 'Honorary', '荣誉/兼职'),
    _ => v ?? '—',
  };

  String rankLabel(String? v) => switch (v) {
    'none' || 'tenaga_pengajar' => _p('Tenaga Pengajar', 'Instructor', '助教'),
    'asisten_ahli' => _p('Asisten Ahli', 'Assistant Professor', '助理教授'),
    'lektor' => _p('Lektor', 'Associate Lecturer', '讲师'),
    'lektor_kepala' => _p('Lektor Kepala', 'Senior Lecturer', '高级讲师'),
    'guru_besar' || 'profesor' => _p('Guru Besar', 'Professor', '教授'),
    _ => v ?? '—',
  };

  // HR profile ---------------------------------------------------------------
  String get hrTitle => _p('Layanan kepegawaian', 'HR self-service', '人事自助');
  String get hrNotLinkedTitle => _p(
    'Akun belum tertaut ke data pegawai',
    'Account not linked to an employee record',
    '账号尚未关联员工记录',
  );
  String get hrNotLinkedBody => _p(
    'Akun Anda belum tertaut ke data pegawai sehingga profil SDM, cuti, dan pengajuan belum dapat digunakan. Hubungi Bagian SDM untuk menautkannya.',
    'Your account is not linked to an employee record yet, so the HR profile, leave and requests are unavailable. Contact HR to link it.',
    '您的账号尚未关联员工记录，因此无法使用人事资料、请假和申请功能。请联系人事部门。',
  );
  String get employeeSnapshot => _p('Data pegawai', 'Employee data', '员工数据');
  String get academicRank => _p('Jabatan akademik', 'Academic rank', '学术职称');
  String get rank => _p('Pangkat/golongan', 'Civil rank/grade', '职级');
  String get serdos =>
      _p('No. sertifikasi dosen', 'Lecturer certification no.', '教师资格证号');
  String get expertise => _p('Bidang keahlian', 'Expertise', '专业领域');
  String get lecturerStatus => _p('Status dosen', 'Lecturer status', '教师状态');
  String get position => _p('Jabatan', 'Position', '职位');
  String get unit => _p('Unit kerja', 'Work unit', '工作单位');
  String get supervisor => _p('Atasan', 'Supervisor', '上级');
  String get employeeType => _p('Jenis pegawai', 'Employee type', '员工类型');
  String get joinedAt => _p('Tanggal bergabung', 'Joined on', '入职日期');
  String get menuLeave => _p('Cuti & izin', 'Leave', '请假');
  String get menuLeaveSub =>
      _p('Ajukan dan pantau cuti', 'Request and track leave', '申请并跟踪请假');
  String get menuRequests => _p('Pengajuan kepegawaian', 'HR requests', '人事申请');
  String get menuRequestsSub => _p(
    'Mutasi, perubahan data, kenaikan pangkat, dokumen',
    'Transfer, data change, promotion, documents',
    '调动、资料变更、晋升、文件',
  );
  String get leaveQuota =>
      _p('Kuota cuti tahunan', 'Annual leave quota', '年假额度');
  String quotaLine(int year, num remaining, num quota) => _p(
    '$year: sisa $remaining dari $quota hari',
    '$year: $remaining of $quota days remaining',
    '$year年：剩余 $remaining / $quota 天',
  );
  String quotaUsed(num used) =>
      _p('Terpakai $used hari', '$used days used', '已用 $used 天');

  // Leave --------------------------------------------------------------------
  String get leaveTitle => _p('Cuti & izin', 'Leave', '请假');
  String get leaveEmptyTitle =>
      _p('Belum ada pengajuan cuti', 'No leave requests', '暂无请假申请');
  String get leaveEmptyBody => _p(
    'Ketuk tombol di bawah untuk mengajukan cuti.',
    'Tap the button below to request leave.',
    '点击下方按钮申请请假。',
  );
  String get newLeave => _p('Ajukan cuti', 'Request leave', '申请请假');
  String get leaveType => _p('Jenis cuti', 'Leave type', '请假类型');
  String get startDate => _p('Tanggal mulai', 'Start date', '开始日期');
  String get endDate => _p('Tanggal selesai', 'End date', '结束日期');
  String get pickDate => _p('Pilih tanggal', 'Pick a date', '选择日期');
  String get reason => _p('Alasan', 'Reason', '原因');
  String get attachment => _p('Lampiran', 'Attachment', '附件');
  String get attachmentSickRequired => _p(
    'Cuti sakit wajib melampirkan surat keterangan dokter saat diajukan.',
    'Sick leave requires a doctor\'s note when submitting.',
    '病假提交时必须附上医生证明。',
  );
  String get attachmentUploaded =>
      _p('Lampiran terunggah', 'Attachment uploaded', '附件已上传');
  String get removeAttachment =>
      _p('Hapus lampiran', 'Remove attachment', '移除附件');
  String get saveDraft => _p('Simpan draf', 'Save draft', '保存草稿');
  String get submitLeave => _p('Ajukan', 'Submit', '提交');
  String get submitDraft => _p('Ajukan draf', 'Submit draft', '提交草稿');
  String get cancelRequest =>
      _p('Batalkan pengajuan', 'Cancel request', '取消申请');
  String get cancelConfirm =>
      _p('Batalkan pengajuan ini?', 'Cancel this request?', '确定取消此申请？');
  String get keepIt => _p('Tidak', 'No', '否');
  String get submitConfirm => _p(
    'Ajukan cuti ini untuk persetujuan?',
    'Submit this leave for approval?',
    '提交此请假申请以供审批？',
  );
  String get cancelled =>
      _p('Pengajuan dibatalkan', 'Request cancelled', '申请已取消');
  String get submitted => _p('Pengajuan dikirim', 'Request submitted', '申请已提交');
  String get quotaNote => _p(
    'Kuota cuti tahunan dihitung per tahun dari hari kerja (Senin–Jumat). Jika tanggal bertumpang tindih atau kuota tidak cukup, pengajuan akan ditolak sistem.',
    'The annual quota is counted per year in working days (Mon–Fri). Overlapping dates or insufficient quota will be rejected by the system.',
    '年假额度按年度以工作日（周一至周五）计算。日期重叠或额度不足时系统将拒绝申请。',
  );
  String days(num n) => _p('$n hari', '$n days', '$n 天');
  String period(String a, String b) => '$a – $b';
  String get approvalNote => _p('Catatan', 'Note', '备注');
  String get submittedAt => _p('Diajukan', 'Submitted', '提交时间');

  String leaveTypeLabel(String v) => switch (v) {
    'annual' => _p('Cuti tahunan', 'Annual leave', '年假'),
    'sick' => _p('Cuti sakit', 'Sick leave', '病假'),
    'maternity' => _p('Cuti melahirkan', 'Maternity leave', '产假'),
    'special' => _p('Cuti khusus', 'Special leave', '特殊假'),
    'permission' => _p('Izin', 'Permission', '事假'),
    _ => v,
  };

  String leaveStatusLabel(String v) => switch (v) {
    'draft' => _p('Draf', 'Draft', '草稿'),
    'submitted' => _p('Menunggu persetujuan', 'Awaiting approval', '待审批'),
    'returned' => _p('Dikembalikan', 'Returned', '已退回'),
    'approved' => _p('Disetujui', 'Approved', '已批准'),
    'rejected' => _p('Ditolak', 'Rejected', '已拒绝'),
    'cancelled' => _p('Dibatalkan', 'Cancelled', '已取消'),
    _ => v,
  };

  // Requests -----------------------------------------------------------------
  String get requestsTitle =>
      _p('Pengajuan kepegawaian', 'HR requests', '人事申请');
  String get requestsEmptyTitle =>
      _p('Belum ada pengajuan', 'No requests yet', '暂无申请');
  String get requestsEmptyBody => _p(
    'Ketuk tombol di bawah untuk membuat pengajuan.',
    'Tap the button below to create a request.',
    '点击下方按钮创建申请。',
  );
  String get newRequest => _p('Buat pengajuan', 'New request', '新建申请');
  String get requestType => _p('Jenis pengajuan', 'Request type', '申请类型');
  String get title => _p('Judul', 'Title', '标题');
  String get description => _p('Keterangan', 'Description', '说明');
  String get submitRequest => _p('Kirim pengajuan', 'Send request', '提交申请');
  String get changesSection =>
      _p('Data yang diubah', 'Data to change', '待变更数据');
  String get changesHint => _p(
    'Isi minimal satu data yang ingin diperbarui.',
    'Fill in at least one field you want to update.',
    '请至少填写一项要更新的数据。',
  );
  String get changesRequired => _p(
    'Isi minimal satu data yang diubah',
    'Fill in at least one field',
    '请至少填写一项',
  );
  String get birthPlace => _p('Tempat lahir', 'Birth place', '出生地');
  String get address => _p('Alamat', 'Address', '地址');
  String get requestSent => _p('Pengajuan dikirim', 'Request sent', '申请已发送');
  String get uploadAttachment =>
      _p('Unggah lampiran', 'Upload attachment', '上传附件');
  String get uploading => _p('Mengunggah…', 'Uploading…', '上传中…');

  String requestTypeLabel(String v) => switch (v) {
    'transfer' => _p('Mutasi', 'Transfer', '调动'),
    'data_change' => _p('Perubahan data', 'Data change', '资料变更'),
    'promotion' => _p('Kenaikan pangkat/jabatan', 'Promotion', '晋升'),
    'document' => _p('Dokumen', 'Document', '文件'),
    'other' => _p('Lainnya', 'Other', '其他'),
    'leave' => _p('Cuti', 'Leave', '请假'),
    _ => v,
  };

  String requestStatusLabel(String v) => switch (v) {
    'pending' => _p('Menunggu', 'Pending', '待处理'),
    'returned' => _p('Dikembalikan', 'Returned', '已退回'),
    'approved' => _p('Disetujui', 'Approved', '已批准'),
    'rejected' => _p('Ditolak', 'Rejected', '已拒绝'),
    'cancelled' => _p('Dibatalkan', 'Cancelled', '已取消'),
    _ => v,
  };
}

extension HrStringsX on BuildContext {
  HrStrings get hrL10n => HrStrings(this);
}
