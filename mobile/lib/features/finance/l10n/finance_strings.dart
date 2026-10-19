import 'package:flutter/widgets.dart';

import '../../../core/l10n/app_language.dart';

class FinanceStrings {
  const FinanceStrings(this._c);
  final BuildContext _c;
  String _p(String id, String en, String zh) =>
      pick(_c, id: id, en: en, zh: zh);

  // Home --------------------------------------------------------------------
  String get title => _p('Keuangan', 'Finance', '财务');
  String get outstanding => _p('Total tagihan', 'Total outstanding', '待缴总额');
  String get arrears => _p('Tunggakan', 'Overdue', '逾期欠款');
  String get nextDue => _p('Jatuh tempo berikutnya', 'Next due', '下次到期');
  String nextDueInstallment(int seq) =>
      _p('Termin ke-$seq', 'Installment #$seq', '第$seq期');
  String get depositBalance => _p('Saldo deposit', 'Deposit balance', '押金余额');
  String openInvoices(int n) =>
      _p('$n tagihan terbuka', '$n open invoices', '$n 张未结账单');
  String pendingVerification(int n) => _p(
    '$n pembayaran menunggu verifikasi',
    '$n payments awaiting verification',
    '$n 笔付款等待核实',
  );
  String get allClear =>
      _p('Tidak ada tagihan terbuka.', 'No open invoices.', '没有未结账单。');
  String get shortcuts => _p('Menu keuangan', 'Finance menu', '财务菜单');
  String get invoices => _p('Tagihan', 'Invoices', '账单');
  String get invoicesSub => _p(
    'Tagihan, cicilan, dan pembayaran',
    'Bills, installments and payments',
    '账单、分期与付款',
  );
  String get transactions => _p('Riwayat transaksi', 'Transactions', '交易记录');
  String get transactionsSub => _p(
    'Status pembayaran dan kuitansi',
    'Payment status and receipts',
    '付款状态与收据',
  );
  String get deposit => _p('Deposit', 'Deposit', '押金');
  String get depositSub => _p(
    'Saldo, mutasi, dan pengembalian dana',
    'Balance, entries and refunds',
    '余额、流水与退款',
  );
  String get statement => _p('Rekening koran', 'Account statement', '账目明细');
  String get statementSub =>
      _p('Kartu piutang mahasiswa', 'Student ledger', '学生账务明细');
  String get ukt => _p('UKT', 'Tuition group (UKT)', '学费组别 (UKT)');
  String get uktSub =>
      _p('Kelompok UKT dan peninjauan', 'Group and review requests', '组别与复核申请');
  String get awards =>
      _p('Beasiswa & potongan', 'Scholarships & discounts', '奖学金与减免');
  String get awardsSub =>
      _p('Bantuan dan potongan biaya', 'Aid and fee discounts', '资助与费用减免');
  String get clearance => _p('Status cekal', 'Clearance status', '限制状态');
  String get clearanceSub => _p(
    'Pembatasan layanan & dispensasi',
    'Service restrictions & dispensation',
    '服务限制与豁免',
  );
  String get blockedBanner => _p(
    'Layanan akademik Anda dibatasi karena kewajiban keuangan.',
    'Your academic services are restricted due to unpaid obligations.',
    '由于财务欠款，您的学业服务受到限制。',
  );
  String get seeDetails => _p('Lihat rincian', 'See details', '查看详情');

  // Invoices ------------------------------------------------------------------
  String get filterAll => _p('Semua', 'All', '全部');
  String get filterOpen => _p('Belum lunas', 'Unpaid', '未结清');
  String get filterPaid => _p('Lunas', 'Paid', '已结清');
  String get filterOther => _p('Lainnya', 'Other', '其他');
  String get noInvoices => _p('Belum ada tagihan', 'No invoices yet', '暂无账单');
  String get noInvoicesBody => _p(
    'Tagihan Anda akan muncul di sini.',
    'Your invoices will appear here.',
    '您的账单将显示在这里。',
  );
  String dueOn(String date) => _p('Jatuh tempo $date', 'Due $date', '到期 $date');
  String get overdue => _p('Terlambat', 'Overdue', '已逾期');
  String get dueDateLabel => _p('Jatuh tempo', 'Due date', '到期日');
  String get invoiceDetail => _p('Detail tagihan', 'Invoice details', '账单详情');
  String get period => _p('Periode', 'Period', '期间');
  String get billGroup => _p('Jenis tagihan', 'Bill type', '账单类型');
  String get grossAmount => _p('Jumlah kotor', 'Gross amount', '总额');
  String get discount => _p('Potongan', 'Discount', '减免');
  String get totalAmount => _p('Total tagihan', 'Total', '应付合计');
  String get paidAmount => _p('Sudah dibayar', 'Paid', '已付');
  String get remaining => _p('Sisa tagihan', 'Outstanding', '未付余额');
  String get components => _p('Rincian komponen', 'Components', '费用明细');
  String get installmentsTitle =>
      _p('Jadwal cicilan', 'Installment schedule', '分期计划');
  String get allocationsTitle =>
      _p('Pembayaran diterima', 'Payments received', '已收款项');
  String get penaltiesTitle => _p('Denda keterlambatan', 'Late fees', '滞纳金');
  String get pendingTxTitle =>
      _p('Transaksi tertunda', 'Pending transactions', '待处理交易');
  String get payNow => _p('Bayar sekarang', 'Pay now', '立即支付');
  String get payInstallment =>
      _p('Bayar termin berikutnya', 'Pay next installment', '支付下一期');
  String get useDeposit =>
      _p('Pakai saldo deposit', 'Use deposit balance', '使用押金余额');
  String get useVoucher => _p('Gunakan voucher', 'Redeem voucher', '使用优惠券');
  String get requestInstallment =>
      _p('Ajukan cicilan', 'Request installments', '申请分期');
  String get cancelledReason =>
      _p('Alasan pembatalan', 'Cancellation reason', '取消原因');
  String installmentLabel(int seq) =>
      _p('Termin $seq', 'Installment $seq', '第$seq期');
  String get voucherLabel => _p('Kode voucher', 'Voucher code', '优惠码');
  String get voucherHint =>
      _p('4 sampai 30 karakter', '4 to 30 characters', '4至30个字符');
  String get apply => _p('Terapkan', 'Apply', '应用');
  String get depositAmountLabel => _p(
    'Nominal (kosongkan untuk maksimal)',
    'Amount (leave empty for maximum)',
    '金额（留空为最大可用）',
  );
  String get depositConfirm => _p(
    'Saldo deposit akan langsung dipakai untuk tagihan ini.',
    'Your deposit balance will be applied to this invoice immediately.',
    '押金余额将立即用于此账单。',
  );
  String get chooseScheme =>
      _p('Pilih skema cicilan', 'Choose a plan', '选择分期方案');
  String get customScheme => _p('Skema khusus', 'Custom schedule', '自定义方案');
  String get noPlans => _p(
    'Tidak ada skema standar untuk tagihan ini. Gunakan skema khusus.',
    'No standard plans for this invoice. Use a custom schedule.',
    '此账单没有标准方案，请使用自定义方案。',
  );
  String get termsLabel =>
      _p('Jumlah termin (2-12)', 'Number of terms (2-12)', '期数（2-12）');
  String get reasonLabel => _p('Alasan', 'Reason', '原因');
  String get reasonRequiredCustom => _p(
    'Alasan wajib untuk skema khusus.',
    'A reason is required for a custom schedule.',
    '自定义方案必须填写原因。',
  );
  String termPreview(int n, String each) => _p(
    '$n termin sama rata, ±$each per termin; jatuh tempo tiap 30 hari.',
    '$n equal terms, about $each each; due every 30 days.',
    '$n 期等额，每期约 $each，每 30 天到期。',
  );
  String get planAuto => _p('Disetujui otomatis', 'Auto-approved', '自动批准');

  // Pay ----------------------------------------------------------------------
  String get payTitle => _p('Bayar tagihan', 'Pay invoice', '支付账单');
  String get payTarget => _p('Yang dibayar', 'Paying for', '支付对象');
  String get payWholeInvoice =>
      _p('Seluruh sisa tagihan', 'Entire outstanding amount', '全部未付余额');
  String get onlineTitle => _p('Pembayaran online', 'Online payment', '在线支付');
  String get onlineHint => _p(
    'Anda akan diarahkan ke halaman pembayaran penyedia di browser.',
    'You will be taken to the provider payment page in your browser.',
    '您将在浏览器中打开支付服务商页面。',
  );
  String get methodVa => _p('Virtual account', 'Virtual account', '虚拟账户');
  String get methodQris => _p('QRIS', 'QRIS', 'QRIS');
  String get methodEwallet => _p('E-wallet', 'E-wallet', '电子钱包');
  String get methodRetail => _p('Gerai ritel', 'Retail outlet', '零售店');
  String feeInfo(String fee) =>
      _p('Biaya layanan $fee', 'Service fee $fee', '手续费 $fee');
  String get noOnlineChannels => _p(
    'Pembayaran online belum aktif di kampus ini. Gunakan transfer manual.',
    'Online payment is not active at this campus. Please use manual transfer.',
    '本校尚未开通在线支付，请使用手动转账。',
  );
  String get channelNotConnected => _p(
    'Kanal ini belum aktif atau belum terhubung ke penyedia pembayaran. Pilih kanal lain atau gunakan transfer manual.',
    'This channel is not active or not connected to the payment provider. Pick another channel or use manual transfer.',
    '该渠道尚未启用或未连接支付服务商，请选择其他渠道或使用手动转账。',
  );
  String get manualTitle =>
      _p('Transfer bank manual', 'Manual bank transfer', '手动银行转账');
  String get manualHint => _p(
    'Transfer sesuai nominal (termasuk kode unik bila ada), lalu unggah bukti.',
    'Transfer the exact amount (including the unique code, if any), then upload proof.',
    '请按准确金额（含唯一码）转账，然后上传凭证。',
  );
  String get manualAmountLabel => _p(
    'Nominal (opsional, kosong = seluruhnya)',
    'Amount (optional, empty = full)',
    '金额（可选，留空为全额）',
  );
  String get createInstruction =>
      _p('Buat instruksi transfer', 'Create transfer instructions', '生成转账说明');
  String get manualDisabled => _p(
    'Transfer manual tidak tersedia.',
    'Manual transfer is not available.',
    '手动转账不可用。',
  );
  String get nothingToPay => _p(
    'Tagihan ini tidak memiliki sisa untuk dibayar.',
    'This invoice has nothing left to pay.',
    '此账单没有待付金额。',
  );
  String get openPaymentPage =>
      _p('Buka halaman pembayaran', 'Open payment page', '打开支付页面');
  String get cannotOpenLink => _p(
    'Tidak dapat membuka tautan pembayaran.',
    'Could not open the payment link.',
    '无法打开支付链接。',
  );

  // Transaction sheet ---------------------------------------------------------
  String get transactionTitle => _p('Transaksi', 'Transaction', '交易');
  String get number => _p('Nomor', 'Number', '编号');
  String get method => _p('Metode', 'Method', '方式');
  String get channel => _p('Kanal', 'Channel', '渠道');
  String get amountToPay =>
      _p('Nominal yang harus dibayar', 'Amount to pay', '应付金额');
  String get uniqueCodeNote => _p(
    'Nominal sudah termasuk kode unik; transfer harus persis sama.',
    'The amount includes a unique code; transfer the exact amount.',
    '金额含唯一码，请按准确金额转账。',
  );
  String get deadline => _p('Batas waktu', 'Deadline', '截止时间');
  String get vaNumber => _p('Nomor VA', 'VA number', 'VA 号码');
  String get bankAccounts =>
      _p('Rekening tujuan', 'Destination accounts', '收款账户');
  String get accountHolder => _p('a.n.', 'Account name', '户名');
  String get howToPay => _p('Cara pembayaran', 'How to pay', '支付步骤');
  String get checkStatus =>
      _p('Cek status pembayaran', 'Check payment status', '查询付款状态');
  String get statusUnchanged => _p(
    'Status belum berubah. Coba lagi beberapa saat setelah membayar.',
    'Status has not changed yet. Try again shortly after paying.',
    '状态尚未更新，付款后请稍后再试。',
  );
  String get cancelTx => _p('Batalkan transaksi', 'Cancel transaction', '取消交易');
  String get cancelTxConfirm => _p(
    'Batalkan instruksi pembayaran ini? Anda dapat membuat yang baru.',
    'Cancel this payment instruction? You can create a new one.',
    '取消此付款说明？您可以重新生成。',
  );
  String get cancelTxDone =>
      _p('Transaksi dibatalkan.', 'Transaction cancelled.', '交易已取消。');
  String get receipt => _p('Kuitansi', 'Receipt', '收据');
  String get uploadProof =>
      _p('Unggah bukti transfer', 'Upload transfer proof', '上传转账凭证');
  String get reuploadProof =>
      _p('Unggah ulang bukti', 'Re-upload proof', '重新上传凭证');
  String get proofTitle => _p('Bukti transfer', 'Transfer proof', '转账凭证');
  String get senderBank => _p('Bank pengirim', 'Sender bank', '汇款银行');
  String get senderName => _p('Nama pengirim', 'Sender name', '汇款人');
  String get transferDate => _p('Tanggal transfer', 'Transfer date', '转账日期');
  String get transferredAmount =>
      _p('Nominal ditransfer', 'Amount transferred', '转账金额');
  String get proofFile => _p(
    'Berkas bukti (jpg/png/pdf, maks 2 MB)',
    'Proof file (jpg/png/pdf, max 2 MB)',
    '凭证文件（jpg/png/pdf，最大 2 MB）',
  );
  String get proofFileRequired => _p(
    'Pilih berkas bukti terlebih dahulu.',
    'Pick a proof file first.',
    '请先选择凭证文件。',
  );
  String get proofSent => _p(
    'Bukti terkirim dan menunggu verifikasi.',
    'Proof sent and awaiting verification.',
    '凭证已提交，等待核实。',
  );
  String get duplicateWarning => _p(
    'Berkas ini terdeteksi pernah dipakai pada transaksi lain.',
    'This file appears to have been used on another transaction.',
    '该文件似乎已用于其他交易。',
  );
  String get rejectedReason =>
      _p('Alasan penolakan', 'Rejection reason', '拒绝原因');
  String proofReason(String code) => switch (code) {
    'NOT_FOUND_IN_STATEMENT' => _p(
      'Transfer tidak ditemukan di mutasi rekening',
      'Transfer not found in bank statement',
      '银行流水中未找到该转账',
    ),
    'AMOUNT_MISMATCH' => _p(
      'Nominal tidak sesuai',
      'Amount does not match',
      '金额不符',
    ),
    'UNREADABLE' => _p('Bukti tidak terbaca', 'Proof is unreadable', '凭证无法识别'),
    'DUPLICATE_PROOF' => _p('Bukti duplikat', 'Duplicate proof', '重复凭证'),
    'WRONG_ACCOUNT' => _p(
      'Rekening tujuan salah',
      'Wrong destination account',
      '收款账户错误',
    ),
    'OTHER' => _p('Alasan lain', 'Other reason', '其他原因'),
    _ => code,
  };
  String get copyAmount => _p('Salin nominal', 'Copy amount', '复制金额');
  String get copyNumber => _p('Salin nomor', 'Copy number', '复制号码');
  String get copyDeadline => _p('Salin batas waktu', 'Copy deadline', '复制截止时间');
  String get copiedToClipboard =>
      _p('Disalin ke papan klip', 'Copied to clipboard', '已复制到剪贴板');
  String get failureReason => _p('Alasan kegagalan', 'Failure reason', '失败原因');

  // Transactions list -----------------------------------------------------------
  String get noTransactions =>
      _p('Belum ada transaksi', 'No transactions yet', '暂无交易');
  String get noTransactionsBody => _p(
    'Riwayat pembayaran Anda akan muncul di sini.',
    'Your payment history will appear here.',
    '您的付款记录将显示在这里。',
  );
  String get noMatchingTx => _p(
    'Tidak ada transaksi dengan status ini pada data yang dimuat.',
    'No loaded transactions with this status.',
    '已加载的数据中没有此状态的交易。',
  );

  String txStatus(String status, String? fallback) => switch (status) {
    'created' => _p('Dibuat', 'Created', '已创建'),
    'pending' => _p('Menunggu pembayaran', 'Awaiting payment', '待付款'),
    'awaiting_verification' => _p(
      'Menunggu verifikasi',
      'Awaiting verification',
      '待核实',
    ),
    'success' => _p('Berhasil', 'Successful', '成功'),
    'failed' => _p('Gagal', 'Failed', '失败'),
    'expired' => _p('Kedaluwarsa', 'Expired', '已过期'),
    'cancelled' => _p('Dibatalkan', 'Cancelled', '已取消'),
    'reversed' => _p('Dibalik', 'Reversed', '已冲正'),
    'refunded' => _p('Dikembalikan', 'Refunded', '已退款'),
    _ => fallback ?? status,
  };
  String txMethod(String method, String? fallback) => switch (method) {
    'online' => _p('Online', 'Online', '在线'),
    'manual_transfer' => _p('Transfer manual', 'Manual transfer', '手动转账'),
    'cash' => _p('Tunai (loket)', 'Cash (counter)', '现金（柜台）'),
    'deposit' => _p('Saldo deposit', 'Deposit balance', '押金余额'),
    _ => fallback ?? method,
  };
  String invoiceStatus(String status, String? fallback) => switch (status) {
    'unpaid' => _p('Belum dibayar', 'Unpaid', '未付'),
    'partial' => _p('Dibayar sebagian', 'Partially paid', '部分已付'),
    'paid' => _p('Lunas', 'Paid', '已结清'),
    'cancelled' => _p('Dibatalkan', 'Cancelled', '已取消'),
    'waived' => _p('Dibebaskan', 'Waived', '已豁免'),
    'draft' => _p('Draf', 'Draft', '草稿'),
    _ => fallback ?? status,
  };
  String installmentStatus(String status, bool overdue, String? fallback) {
    if (overdue) return this.overdue;
    return switch (status) {
      'scheduled' => _p('Terjadwal', 'Scheduled', '已排期'),
      'partial' => _p('Dibayar sebagian', 'Partially paid', '部分已付'),
      'paid' => _p('Lunas', 'Paid', '已结清'),
      'superseded' => _p('Diganti jadwal baru', 'Superseded', '已被替换'),
      _ => fallback ?? status,
    };
  }

  String proofStatus(String status, String? fallback) => switch (status) {
    'submitted' => _p('Menunggu verifikasi', 'Awaiting verification', '待核实'),
    'approved' => _p('Disetujui', 'Approved', '已通过'),
    'rejected' => _p('Ditolak', 'Rejected', '已拒绝'),
    _ => fallback ?? status,
  };

  // Deposit / refund --------------------------------------------------------------
  String get depositHeld => _p('Ditahan', 'On hold', '冻结');
  String get depositAvailable => _p('Tersedia', 'Available', '可用');
  String get depositEntries => _p('Mutasi deposit', 'Deposit entries', '押金流水');
  String get noDepositEntries =>
      _p('Belum ada mutasi.', 'No entries yet.', '暂无流水。');
  String get refundsTitle =>
      _p('Pengajuan pengembalian dana', 'Refund requests', '退款申请');
  String get requestRefund =>
      _p('Ajukan pengembalian dana', 'Request refund', '申请退款');
  String get topUp =>
      _p('Titip deposit (transfer)', 'Top up deposit (transfer)', '充值押金（转账）');
  String get topUpAmount => _p('Nominal titipan', 'Top-up amount', '充值金额');
  String get refundAmount =>
      _p('Nominal pengembalian', 'Refund amount', '退款金额');
  String get refundReason => _p('Alasan pengembalian', 'Refund reason', '退款原因');
  String get refundNote => _p(
    'Keterangan (min. 10 karakter)',
    'Note (min. 10 characters)',
    '说明（至少10个字符）',
  );
  String get beneficiaryBank => _p('Bank tujuan', 'Beneficiary bank', '收款银行');
  String get beneficiaryAccount => _p(
    'Nomor rekening (6-20 digit)',
    'Account number (6-20 digits)',
    '账号（6-20位数字）',
  );
  String get beneficiaryName =>
      _p('Nama pemilik rekening', 'Account holder name', '账户持有人');
  String get beneficiaryRelation =>
      _p('Hubungan pemilik rekening', 'Account holder relation', '账户持有人关系');
  String get relationSelf => _p('Diri sendiri', 'Myself', '本人');
  String get relationParent => _p('Orang tua', 'Parent', '父母');
  String get refundHoldNote => _p(
    'Saldo akan ditahan sampai pengajuan diputuskan.',
    'The amount will be held until the request is decided.',
    '申请审批前该金额将被冻结。',
  );
  String get refundSent => _p(
    'Pengajuan pengembalian dana dikirim.',
    'Refund request sent.',
    '退款申请已提交。',
  );
  String refundReasonLabel(String code) => switch (code) {
    'OVERPAYMENT' => _p('Kelebihan bayar', 'Overpayment', '多付'),
    'DUPLICATE_PAYMENT' => _p('Pembayaran ganda', 'Duplicate payment', '重复付款'),
    'CANCELLED_INVOICE' => _p(
      'Tagihan dibatalkan',
      'Cancelled invoice',
      '账单取消',
    ),
    'WITHDRAWAL' => _p('Mengundurkan diri', 'Withdrawal', '退学'),
    'LEAVE' => _p('Cuti', 'Leave of absence', '休学'),
    'DECEASED' => _p('Meninggal dunia', 'Deceased', '身故'),
    'GRADUATED_BALANCE' => _p(
      'Sisa saldo lulus',
      'Balance after graduation',
      '毕业余额',
    ),
    'OTHER' => _p('Lainnya', 'Other', '其他'),
    _ => code,
  };
  String refundStatus(String status, String? fallback) => switch (status) {
    'pending_approval' => _p('Menunggu persetujuan', 'Pending approval', '待审批'),
    'approved' => _p('Disetujui', 'Approved', '已批准'),
    'rejected' => _p('Ditolak', 'Rejected', '已拒绝'),
    'completed' => _p('Selesai', 'Completed', '已完成'),
    'failed' => _p('Gagal', 'Failed', '失败'),
    'cancelled' => _p('Dibatalkan', 'Cancelled', '已取消'),
    _ => fallback ?? status,
  };
  String depositType(String type, String? fallback) => switch (type) {
    'overpayment' => _p('Kelebihan bayar', 'Overpayment', '多付'),
    'top_up' => _p('Titipan', 'Top-up', '充值'),
    'adjustment_credit' => _p(
      'Kredit penyesuaian',
      'Adjustment credit',
      '调整入账',
    ),
    'allocation' => _p('Dipakai untuk tagihan', 'Applied to invoice', '用于账单'),
    'refund_hold' => _p('Ditahan untuk refund', 'Held for refund', '退款冻结'),
    'refund_release' => _p('Penahanan dilepas', 'Hold released', '冻结解除'),
    'refund_payout' => _p('Dikembalikan', 'Paid out', '已退还'),
    'reversal' => _p('Pembalikan', 'Reversal', '冲正'),
    _ => fallback ?? type,
  };

  // Statement / UKT / awards ----------------------------------------------------------
  String get noStatement =>
      _p('Belum ada mutasi tagihan', 'No ledger entries', '暂无账务记录');
  String get statementBalance => _p('Saldo akhir', 'Closing balance', '期末余额');
  String get uktCurrent => _p('UKT saat ini', 'Current UKT', '当前 UKT');
  String get noUktCurrent =>
      _p('Belum ada penetapan UKT.', 'No UKT assignment yet.', '尚未确定 UKT。');
  String get decreeNumber => _p('No. SK', 'Decree no.', '文号');
  String get effectiveFrom => _p('Berlaku sejak', 'Effective from', '生效日期');
  String get uktRequests =>
      _p('Pengajuan peninjauan', 'Review requests', '复核申请');
  String get noUktRequests =>
      _p('Belum ada pengajuan.', 'No requests yet.', '暂无申请。');
  String get requestUkt =>
      _p('Ajukan peninjauan UKT', 'Request UKT review', '申请 UKT 复核');
  String get uktPendingNote => _p(
    'Masih ada pengajuan yang sedang diproses.',
    'A request is still being processed.',
    '仍有申请正在处理中。',
  );
  String get requestedGroup => _p(
    'Kelompok diminta (opsional)',
    'Requested group (optional)',
    '申请组别（可选）',
  );
  String get decidedGroup => _p('Kelompok ditetapkan', 'Decided group', '批准组别');
  String get uktReason => _p(
    'Alasan (min. 20 karakter)',
    'Reason (min. 20 characters)',
    '原因（至少20个字符）',
  );
  String get evidence => _p(
    'Bukti pendukung (jpg/png/pdf, maks 5 MB)',
    'Supporting evidence (jpg/png/pdf, max 5 MB)',
    '证明材料（jpg/png/pdf，最大 5 MB）',
  );
  String get evidenceRequired => _p(
    'Lampirkan bukti pendukung.',
    'Attach supporting evidence.',
    '请附上证明材料。',
  );
  String get uktSent => _p(
    'Pengajuan peninjauan UKT dikirim.',
    'UKT review request sent.',
    'UKT 复核申请已提交。',
  );
  String uktStatus(String s, String? fallback) => switch (s) {
    'submitted' => _p('Diajukan', 'Submitted', '已提交'),
    'approved' => _p('Disetujui', 'Approved', '已批准'),
    'rejected' => _p('Ditolak', 'Rejected', '已拒绝'),
    'cancelled' => _p('Dibatalkan', 'Cancelled', '已取消'),
    _ => fallback ?? s,
  };
  String get noAwards => _p(
    'Belum ada beasiswa atau potongan',
    'No scholarships or discounts',
    '暂无奖学金或减免',
  );
  String get noAwardsBody => _p(
    'Bantuan biaya yang Anda terima akan tampil di sini.',
    'Fee aid you receive will appear here.',
    '您获得的费用资助将显示在这里。',
  );
  String awardStatus(String s, String? fallback) => switch (s) {
    'active' => _p('Aktif', 'Active', '有效'),
    'revoked' => _p('Dicabut', 'Revoked', '已撤销'),
    'pending_approval' => _p('Menunggu persetujuan', 'Pending approval', '待审批'),
    'rejected' => _p('Ditolak', 'Rejected', '已拒绝'),
    _ => fallback ?? s,
  };
  String get awardValue => _p('Nilai', 'Value', '数值');
  String get awardMax => _p('Maksimal', 'Maximum', '上限');
  String get awardTerms => _p('Masa berlaku', 'Validity', '有效期');
  String get revokeReason =>
      _p('Alasan pencabutan', 'Revocation reason', '撤销原因');
  String get decisionNote => _p('Catatan keputusan', 'Decision note', '决定备注');

  // Clearance -------------------------------------------------------------------------
  String get clearanceStatusTitle =>
      _p('Bebas tanggungan keuangan', 'Financial clearance', '财务结清');
  String get clearYes => _p('Bebas tanggungan', 'Cleared', '已结清');
  String get clearNo => _p('Belum bebas tanggungan', 'Not cleared', '未结清');
  String get accruingPenalty =>
      _p('Denda berjalan', 'Accruing penalty', '累计滞纳金');
  String get servicesTitle =>
      _p('Layanan akademik', 'Academic services', '学业服务');
  String get serviceBlocked => _p('Diblokir', 'Blocked', '已限制');
  String get serviceOpen => _p('Dapat diakses', 'Accessible', '可使用');
  String serviceName(String service, String fallback) => switch (service) {
    'krs' => _p('Pengisian KRS', 'KRS enrollment', '选课 (KRS)'),
    'exam' => _p('Ujian', 'Exams', '考试'),
    'view_grades' => _p('Lihat nilai', 'View grades', '查看成绩'),
    'transcript' => _p('Transkrip', 'Transcript', '成绩单'),
    'yudisium' => _p('Yudisium', 'Yudisium', '毕业评定'),
    'graduation' => _p('Wisuda', 'Graduation', '毕业典礼'),
    _ => fallback.isEmpty ? service : fallback,
  };
  String get activeBlocks => _p('Cekal aktif', 'Active restrictions', '当前限制');
  String get noBlocks => _p(
    'Tidak ada cekal keuangan. Semua layanan dapat diakses.',
    'No financial restrictions. All services are accessible.',
    '没有财务限制，所有服务均可使用。',
  );
  String get shortfall => _p('Kekurangan', 'Shortfall', '欠缺金额');
  String get since => _p('Sejak', 'Since', '起始');
  String get restrictedServices =>
      _p('Layanan dibatasi', 'Restricted services', '受限服务');
  String get resolveHint => _p(
    'Selesaikan pembayaran atau ajukan dispensasi ke Bagian Keuangan.',
    'Settle the payment or request a dispensation from the Finance Office.',
    '请结清款项，或向财务处申请豁免。',
  );
  String get goToInvoices => _p('Lihat tagihan', 'View invoices', '查看账单');
  String get blockExempted => _p('Berdispensasi', 'Exempted', '已豁免');
  String get blockActive => _p('Aktif', 'Active', '生效中');
  String get dispensations => _p('Dispensasi', 'Dispensations', '豁免申请');
  String get requestDispensation =>
      _p('Ajukan dispensasi', 'Request dispensation', '申请豁免');
  String get dispService => _p('Layanan', 'Service', '服务');
  String get validUntil => _p(
    'Berlaku sampai (maks 30 hari)',
    'Valid until (max 30 days)',
    '有效期至（最长30天）',
  );
  String get dispReason => _p(
    'Alasan (min. 20 karakter)',
    'Reason (min. 20 characters)',
    '原因（至少20个字符）',
  );
  String get promiseAmount => _p(
    'Janji bayar - nominal (opsional)',
    'Promised amount (optional)',
    '承诺金额（可选）',
  );
  String get promiseDate => _p(
    'Janji bayar - tanggal (opsional)',
    'Promised date (optional)',
    '承诺日期（可选）',
  );
  String get dispSent => _p(
    'Pengajuan dispensasi dikirim.',
    'Dispensation request sent.',
    '豁免申请已提交。',
  );
  String dispStatus(String s, String? fallback) => switch (s) {
    'pending' => _p('Menunggu keputusan', 'Pending', '待决定'),
    'active' => _p('Aktif', 'Active', '生效中'),
    'rejected' => _p('Ditolak', 'Rejected', '已拒绝'),
    'revoked' => _p('Dicabut', 'Revoked', '已撤销'),
    'expired' => _p('Kedaluwarsa', 'Expired', '已过期'),
    _ => fallback ?? s,
  };

  // Common ----------------------------------------------------------------------------
  String get amountLabel => _p('Nominal', 'Amount', '金额');
  String get invalidAmount => _p(
    'Nominal harus lebih dari nol.',
    'Amount must be greater than zero.',
    '金额必须大于零。',
  );
  String get invalidAccount => _p(
    'Nomor rekening 6-20 digit angka.',
    'Account number must be 6-20 digits.',
    '账号须为6-20位数字。',
  );
  String get minChars10 =>
      _p('Minimal 10 karakter.', 'At least 10 characters.', '至少10个字符。');
  String get minChars20 =>
      _p('Minimal 20 karakter.', 'At least 20 characters.', '至少20个字符。');
  String get pickDate => _p('Pilih tanggal', 'Pick a date', '选择日期');
  String get attach => _p('Lampirkan berkas', 'Attach file', '添加文件');
  String get dateRequired =>
      _p('Tanggal wajib diisi.', 'Date is required.', '日期必填。');
  String get submitting => _p('Mengirim…', 'Sending…', '提交中…');
}

extension FinanceStringsX on BuildContext {
  FinanceStrings get fin => FinanceStrings(this);
}
