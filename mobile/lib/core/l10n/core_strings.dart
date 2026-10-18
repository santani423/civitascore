import 'package:flutter/widgets.dart';

import '../network/api_failure.dart';
import 'app_language.dart';

/// Shared UI strings (actions, states, errors, navigation) in ID/EN/ZH.
/// Feature-specific strings live in `features/<feature>/l10n/`.
class CoreStrings {
  const CoreStrings(this._c);
  final BuildContext _c;

  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // App -------------------------------------------------------------------
  String get appName => 'Civitas One';
  String get appTagline => _p(
    'Layanan kampus dalam genggaman',
    'Your campus services, in your pocket',
    '掌上校园服务',
  );

  // Actions ---------------------------------------------------------------
  String get save => _p('Simpan', 'Save', '保存');
  String get cancel => _p('Batal', 'Cancel', '取消');
  String get retry => _p('Coba lagi', 'Try again', '重试');
  String get submit => _p('Kirim', 'Submit', '提交');
  String get delete => _p('Hapus', 'Delete', '删除');
  String get edit => _p('Ubah', 'Edit', '编辑');
  String get add => _p('Tambah', 'Add', '添加');
  String get close => _p('Tutup', 'Close', '关闭');
  String get confirm => _p('Konfirmasi', 'Confirm', '确认');
  String get search => _p('Cari', 'Search', '搜索');
  String get filter => _p('Filter', 'Filter', '筛选');
  String get refresh => _p('Muat ulang', 'Refresh', '刷新');
  String get download => _p('Unduh', 'Download', '下载');
  String get upload => _p('Unggah', 'Upload', '上传');
  String get share => _p('Bagikan', 'Share', '分享');
  String get open => _p('Buka', 'Open', '打开');
  String get back => _p('Kembali', 'Back', '返回');
  String get next => _p('Berikutnya', 'Next', '下一步');
  String get previous => _p('Sebelumnya', 'Previous', '上一步');
  String get done => _p('Selesai', 'Done', '完成');
  String get yes => _p('Ya', 'Yes', '是');
  String get no => _p('Tidak', 'No', '否');
  String get ok => _p('OK', 'OK', '确定');
  String get seeAll => _p('Lihat semua', 'See all', '查看全部');
  String get details => _p('Detail', 'Details', '详情');
  String get loadMore => _p('Muat lebih banyak', 'Load more', '加载更多');
  String get choose => _p('Pilih', 'Choose', '选择');
  String get chooseFile => _p('Pilih berkas', 'Choose file', '选择文件');
  String get takePhoto => _p('Ambil foto', 'Take photo', '拍照');
  String get copy => _p('Salin', 'Copy', '复制');
  String get copied =>
      _p('Disalin ke papan klip', 'Copied to clipboard', '已复制到剪贴板');
  String get all => _p('Semua', 'All', '全部');
  String get none => _p('Tidak ada', 'None', '无');
  String get optional => _p('opsional', 'optional', '可选');

  // States ----------------------------------------------------------------
  String get loading => _p('Memuat…', 'Loading…', '加载中…');
  String get emptyTitle => _p('Belum ada data', 'Nothing here yet', '暂无数据');
  String get emptyBody => _p(
    'Data akan muncul di sini setelah tersedia.',
    'Data will appear here once available.',
    '数据可用后将显示在此处。',
  );
  String get errorTitle =>
      _p('Gagal memuat data', 'Couldn\'t load data', '数据加载失败');
  String get offlineBanner => _p(
    'Anda sedang offline. Menampilkan data tersimpan terakhir.',
    'You are offline. Showing the last saved data.',
    '您处于离线状态，正在显示上次保存的数据。',
  );
  String get offlineActionBlocked => _p(
    'Butuh koneksi internet untuk melakukan aksi ini.',
    'An internet connection is required for this action.',
    '此操作需要网络连接。',
  );
  String get savedSuccessfully =>
      _p('Berhasil disimpan', 'Saved successfully', '保存成功');
  String get downloading => _p('Mengunduh…', 'Downloading…', '正在下载…');
  String get downloadFailed =>
      _p('Gagal mengunduh berkas', 'Download failed', '文件下载失败');
  String get openFileFailed => _p(
    'Tidak ada aplikasi untuk membuka berkas ini',
    'No app can open this file',
    '没有可打开此文件的应用',
  );
  String get uploadFailed =>
      _p('Gagal mengunggah berkas', 'Upload failed', '文件上传失败');
  String fileTooLarge(int maxMb) => _p(
    'Ukuran berkas maksimal $maxMb MB',
    'Maximum file size is $maxMb MB',
    '文件大小不能超过 $maxMb MB',
  );
  String get lastUpdated => _p('Terakhir diperbarui', 'Last updated', '最后更新');

  // Pending backend (explicitly labelled, no mock data) -------------------
  String get pendingBackendBadge =>
      _p('Menunggu backend', 'Awaiting backend', '等待后端');
  String get pendingBackendTitle =>
      _p('Fitur belum tersedia', 'Feature not available yet', '功能暂不可用');
  String get pendingBackendBody => _p(
    'Fitur ini sudah dirancang dalam dokumentasi Civitas One, tetapi layanan backend-nya belum tersedia. Aplikasi tidak menampilkan data contoh. Fitur akan aktif setelah endpoint berikut disediakan:',
    'This feature is specified in the Civitas One documentation, but its backend service is not available yet. The app does not show sample data. It will be enabled once these endpoints exist:',
    '此功能已在 Civitas One 文档中设计，但其后端服务尚未提供。应用不会显示示例数据。以下接口就绪后即可启用：',
  );
  String get pendingFeaturesTitle =>
      _p('Fitur menunggu backend', 'Features awaiting backend', '等待后端的功能');
  String get documentationSource =>
      _p('Sumber dokumentasi', 'Documentation source', '文档来源');

  // Errors ----------------------------------------------------------------
  String errorFor(Object error) {
    if (error is ApiFailure) {
      switch (error.kind) {
        case ApiFailureKind.network:
          return _p(
            'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.',
            'Can\'t reach the server. Check your internet connection.',
            '无法连接服务器，请检查网络连接。',
          );
        case ApiFailureKind.timeout:
          return _p(
            'Waktu koneksi habis. Silakan coba lagi.',
            'The connection timed out. Please try again.',
            '连接超时，请重试。',
          );
        case ApiFailureKind.server:
          return _p(
            'Server sedang bermasalah. Coba beberapa saat lagi.',
            'The server is having problems. Please try again later.',
            '服务器出现问题，请稍后再试。',
          );
        case ApiFailureKind.unauthorized:
          return _p(
            'Sesi Anda berakhir. Silakan masuk kembali.',
            'Your session has ended. Please sign in again.',
            '会话已结束，请重新登录。',
          );
        case ApiFailureKind.forbidden:
          return error.message ??
              _p(
                'Anda tidak memiliki akses.',
                'You don\'t have access.',
                '您没有访问权限。',
              );
        case ApiFailureKind.notFound:
          return error.message ??
              _p('Data tidak ditemukan.', 'Not found.', '未找到数据。');
        case ApiFailureKind.rateLimited:
          return error.message ??
              _p(
                'Terlalu banyak percobaan. Coba lagi nanti.',
                'Too many attempts. Try again later.',
                '尝试次数过多，请稍后再试。',
              );
        case ApiFailureKind.validation:
        case ApiFailureKind.conflict:
          return error.serverDetail ??
              _p(
                'Permintaan tidak dapat diproses.',
                'The request couldn\'t be processed.',
                '无法处理该请求。',
              );
        case ApiFailureKind.unknown:
          return error.serverDetail ??
              _p(
                'Terjadi kesalahan. Silakan coba lagi.',
                'Something went wrong. Please try again.',
                '发生错误，请重试。',
              );
      }
    }
    return _p(
      'Terjadi kesalahan. Silakan coba lagi.',
      'Something went wrong. Please try again.',
      '发生错误，请重试。',
    );
  }

  /// Backend business messages are Indonesian-only; note shown under them
  /// for EN/ZH users so they understand why the text is not translated.
  String get serverMessageNote => _p(
    '',
    'Message from the campus server (Indonesian).',
    '来自校园服务器的消息（印尼语）。',
  );

  // Access ----------------------------------------------------------------
  String get accessDeniedTitle => _p('Akses ditolak', 'Access denied', '拒绝访问');
  String get accessDeniedBody => _p(
    'Anda tidak memiliki izin untuk membuka halaman ini.',
    'You don\'t have permission to open this page.',
    '您无权打开此页面。',
  );
  String get noExperienceTitle =>
      _p('Akun belum didukung', 'Account not supported', '账户暂不支持');
  String get noExperienceBody => _p(
    'Aplikasi ini untuk mahasiswa dan dosen. Akun Anda tidak memiliki peran mahasiswa atau dosen pada universitas ini. Gunakan aplikasi web Civitas One.',
    'This app is for students and lecturers. Your account has no student or lecturer role at this university. Please use the Civitas One web app.',
    '本应用面向学生和教师。您的账户在本校没有学生或教师角色，请使用 Civitas One 网页版。',
  );

  // Roles -----------------------------------------------------------------
  String get roleStudent => _p('Mahasiswa', 'Student', '学生');
  String get roleLecturer => _p('Dosen', 'Lecturer', '教师');
  String get roleAdvisor => _p('Dosen PA', 'Academic advisor', '学业导师');

  // Navigation ------------------------------------------------------------
  String get navHome => _p('Beranda', 'Home', '首页');
  String get navAcademic => _p('Akademik', 'Academic', '学业');
  String get navLearning => _p('Kuliah', 'Courses', '课程');
  String get navFinance => _p('Keuangan', 'Finance', '财务');
  String get navAccount => _p('Akun', 'Account', '账户');
  String get navTeaching => _p('Mengajar', 'Teaching', '教学');
  String get navSchedule => _p('Jadwal', 'Schedule', '课表');
  String get navAdvising => _p('Perwalian', 'Advising', '学业指导');
  String get settings => _p('Pengaturan', 'Settings', '设置');
  String get logout => _p('Keluar', 'Sign out', '退出登录');
  String get logoutConfirm => _p(
    'Keluar dari akun ini di perangkat ini?',
    'Sign out of this account on this device?',
    '确定在本设备上退出此账户？',
  );
  String get notifications => _p('Notifikasi', 'Notifications', '通知');
  String get announcements => _p('Pengumuman', 'Announcements', '公告');

  // Time ------------------------------------------------------------------
  String get today => _p('Hari ini', 'Today', '今天');
  String get tomorrow => _p('Besok', 'Tomorrow', '明天');
  String get yesterday => _p('Kemarin', 'Yesterday', '昨天');
  String dayName(int isoWeekday) => switch (isoWeekday) {
    1 => _p('Senin', 'Monday', '星期一'),
    2 => _p('Selasa', 'Tuesday', '星期二'),
    3 => _p('Rabu', 'Wednesday', '星期三'),
    4 => _p('Kamis', 'Thursday', '星期四'),
    5 => _p('Jumat', 'Friday', '星期五'),
    6 => _p('Sabtu', 'Saturday', '星期六'),
    _ => _p('Minggu', 'Sunday', '星期日'),
  };
  String dayShort(int isoWeekday) => switch (isoWeekday) {
    1 => _p('Sen', 'Mon', '周一'),
    2 => _p('Sel', 'Tue', '周二'),
    3 => _p('Rab', 'Wed', '周三'),
    4 => _p('Kam', 'Thu', '周四'),
    5 => _p('Jum', 'Fri', '周五'),
    6 => _p('Sab', 'Sat', '周六'),
    _ => _p('Min', 'Sun', '周日'),
  };
  String semester(String label) =>
      _p('Semester $label', 'Semester $label', '第 $label 学期');
  String sks(num value) => _p('$value SKS', '$value credits', '$value 学分');

  // Validation ------------------------------------------------------------
  String get validationRequired => _p('Wajib diisi', 'Required', '必填');
  String get validationEmail =>
      _p('Format email tidak valid', 'Invalid email address', '邮箱格式无效');
  String validationMinLength(int n) =>
      _p('Minimal $n karakter', 'At least $n characters', '至少 $n 个字符');
  String validationMaxLength(int n) =>
      _p('Maksimal $n karakter', 'At most $n characters', '最多 $n 个字符');
  String get validationNumber =>
      _p('Masukkan angka yang valid', 'Enter a valid number', '请输入有效数字');
  String validationRange(num min, num max) => _p(
    'Nilai harus $min–$max',
    'Value must be $min–$max',
    '数值须在 $min–$max 之间',
  );
  String get validationPasswordMismatch => _p(
    'Konfirmasi kata sandi tidak sama',
    'Passwords don\'t match',
    '两次输入的密码不一致',
  );
  String get validationDateOrder => _p(
    'Tanggal selesai harus setelah tanggal mulai',
    'End date must be after start date',
    '结束日期须晚于开始日期',
  );
}

extension CoreStringsX on BuildContext {
  CoreStrings get l10n => CoreStrings(this);
}
