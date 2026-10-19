import '../../../core/network/api_envelope.dart';

String _money(Object? v) => asString(v) ?? '0';

double moneyValue(Object? v) => asDouble(v) ?? 0;

class FinanceStudent {
  const FinanceStudent({
    required this.id,
    this.nim,
    this.name,
    this.studyProgram,
    this.status,
    this.statusLabel,
  });

  factory FinanceStudent.fromJson(Map<String, dynamic> j) => FinanceStudent(
    id: asString(j['id']) ?? '',
    nim: asString(j['nim']),
    name: asString(j['name']),
    studyProgram: asString(j['study_program']),
    status: asString(j['status']),
    statusLabel: asString(j['status_label']),
  );

  final String id;
  final String? nim;
  final String? name;
  final String? studyProgram;
  final String? status;
  final String? statusLabel;
}

class NextDue {
  const NextDue({this.date, required this.amount, this.invoiceId, this.seq});

  factory NextDue.fromJson(Map<String, dynamic> j) => NextDue(
    date: asDateTime(j['date']),
    amount: _money(j['amount']),
    invoiceId: asString(j['invoice_id']),
    seq: asInt(j['installment_seq']),
  );

  final DateTime? date;
  final String amount;
  final String? invoiceId;
  final int? seq;
}

class ServiceFlag {
  const ServiceFlag({
    required this.service,
    required this.label,
    required this.blocked,
  });

  factory ServiceFlag.fromJson(Map<String, dynamic> j) => ServiceFlag(
    service: asString(j['service']) ?? '',
    label: asString(j['label']) ?? '',
    blocked: asBool(j['blocked']),
  );

  final String service;
  final String label;
  final bool blocked;
}

class ClearanceStatus {
  const ClearanceStatus({
    required this.clear,
    required this.outstanding,
    required this.accruingPenalty,
    required this.pendingVerification,
  });

  factory ClearanceStatus.fromJson(Map<String, dynamic> j) => ClearanceStatus(
    clear: asBool(j['clear']),
    outstanding: _money(j['outstanding']),
    accruingPenalty: _money(j['accruing_penalty']),
    pendingVerification: asInt(j['pending_verification']) ?? 0,
  );

  final bool clear;
  final String outstanding;
  final String accruingPenalty;
  final int pendingVerification;
}

class Exemption {
  const Exemption({
    required this.id,
    required this.service,
    this.validUntil,
    this.reason,
    this.promiseAmount,
    this.promiseDate,
    required this.status,
    this.statusLabel,
    this.decisionNote,
  });

  factory Exemption.fromJson(Map<String, dynamic> j) => Exemption(
    id: asString(j['id']) ?? '',
    service: asString(j['service']) ?? '',
    validUntil: asDateTime(j['valid_until']),
    reason: asString(j['reason']),
    promiseAmount: asString(j['promise_amount']),
    promiseDate: asDateTime(j['promise_date']),
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    decisionNote: asString(j['decision_note']),
  );

  final String id;
  final String service;
  final DateTime? validUntil;
  final String? reason;
  final String? promiseAmount;
  final DateTime? promiseDate;
  final String status;
  final String? statusLabel;
  final String? decisionNote;
}

class AcademicBlock {
  const AcademicBlock({
    required this.id,
    this.services = const [],
    this.reason,
    required this.shortfall,
    required this.status,
    this.statusLabel,
    this.effectiveFrom,
    this.exemptions = const [],
  });

  factory AcademicBlock.fromJson(Map<String, dynamic> j) => AcademicBlock(
    id: asString(j['id']) ?? '',
    services: asStringList(j['services']),
    reason: asString(j['reason']),
    shortfall: _money(j['shortfall']),
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    effectiveFrom: asDateTime(j['effective_from']),
    exemptions: asMapList(j['exemptions']).map(Exemption.fromJson).toList(),
  );

  final String id;
  final List<String> services;
  final String? reason;
  final String shortfall;
  final String status;
  final String? statusLabel;
  final DateTime? effectiveFrom;
  final List<Exemption> exemptions;

  bool get isActive => status == 'active' || status == 'exempted';
}

class FinanceSummary {
  const FinanceSummary({
    required this.student,
    required this.outstanding,
    required this.arrears,
    required this.openInvoices,
    this.nextDue,
    required this.depositAvailable,
    required this.pendingVerification,
    this.services = const [],
    this.clearance,
    this.blocks = const [],
  });

  factory FinanceSummary.fromJson(Map<String, dynamic> j) => FinanceSummary(
    student: FinanceStudent.fromJson(asMap(j['student'])),
    outstanding: _money(j['outstanding']),
    arrears: _money(j['arrears']),
    openInvoices: asInt(j['open_invoices']) ?? 0,
    nextDue: j['next_due'] is Map
        ? NextDue.fromJson(asMap(j['next_due']))
        : null,
    depositAvailable: _money(j['deposit_available']),
    pendingVerification: asInt(j['pending_verification']) ?? 0,
    services: asMapList(j['services']).map(ServiceFlag.fromJson).toList(),
    clearance: j['clearance'] is Map
        ? ClearanceStatus.fromJson(asMap(j['clearance']))
        : null,
    blocks: asMapList(j['blocks']).map(AcademicBlock.fromJson).toList(),
  );

  final FinanceStudent student;
  final String outstanding;
  final String arrears;
  final int openInvoices;
  final NextDue? nextDue;
  final String depositAvailable;
  final int pendingVerification;
  final List<ServiceFlag> services;
  final ClearanceStatus? clearance;
  final List<AcademicBlock> blocks;

  bool get hasArrears => moneyValue(arrears) > 0;
  List<AcademicBlock> get activeBlocks =>
      blocks.where((b) => b.isActive).toList();
}

// ---------------------------------------------------------------------------
// Invoices
// ---------------------------------------------------------------------------

class InvoiceItem {
  const InvoiceItem({
    required this.id,
    required this.lineType,
    this.lineTypeLabel,
    required this.description,
    required this.amount,
  });

  factory InvoiceItem.fromJson(Map<String, dynamic> j) => InvoiceItem(
    id: asString(j['id']) ?? '',
    lineType: asString(j['line_type']) ?? '',
    lineTypeLabel: asString(j['line_type_label']),
    description: asString(j['description']) ?? '',
    amount: _money(j['amount']),
  );

  final String id;
  final String lineType;
  final String? lineTypeLabel;
  final String description;
  final String amount;

  bool get isDeduction => const {
    'aid',
    'discount',
    'subsidy',
    'voucher',
    'waiver',
  }.contains(lineType);
}

class Installment {
  const Installment({
    required this.id,
    required this.seq,
    required this.amount,
    required this.paidAmount,
    required this.remaining,
    this.dueDate,
    required this.status,
    this.statusLabel,
    required this.isOverdue,
  });

  factory Installment.fromJson(Map<String, dynamic> j) => Installment(
    id: asString(j['id']) ?? '',
    seq: asInt(j['seq']) ?? 0,
    amount: _money(j['amount']),
    paidAmount: _money(j['paid_amount']),
    remaining: _money(j['remaining']),
    dueDate: asDateTime(j['due_date']),
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    isOverdue: asBool(j['is_overdue']),
  );

  final String id;
  final int seq;
  final String amount;
  final String paidAmount;
  final String remaining;
  final DateTime? dueDate;
  final String status;
  final String? statusLabel;
  final bool isOverdue;

  bool get isPayable =>
      (status == 'scheduled' || status == 'partial') &&
      moneyValue(remaining) > 0;
}

class Allocation {
  const Allocation({
    required this.id,
    required this.amount,
    this.status,
    this.paidAt,
    this.method,
    this.reference,
    this.reversalReason,
  });

  factory Allocation.fromJson(Map<String, dynamic> j) => Allocation(
    id: asString(j['id']) ?? '',
    amount: _money(j['amount']),
    status: asString(j['status']),
    paidAt: asDateTime(j['paid_at']),
    method: asString(j['method']),
    reference: asString(j['reference']),
    reversalReason: asString(j['reversal_reason']),
  );

  final String id;
  final String amount;
  final String? status;
  final DateTime? paidAt;
  final String? method;
  final String? reference;
  final String? reversalReason;
}

class Invoice {
  const Invoice({
    required this.id,
    required this.invoiceNumber,
    this.period,
    this.billGroupLabel,
    required this.grossAmount,
    required this.discountAmount,
    required this.amount,
    required this.paidAmount,
    required this.outstanding,
    required this.status,
    this.statusLabel,
    required this.isOverdue,
    this.dueDate,
    this.items = const [],
    this.installments = const [],
    this.allocations = const [],
    this.penaltyInvoices = const [],
    this.pendingTransactions = const [],
    this.cancelReason,
  });

  factory Invoice.fromJson(Map<String, dynamic> j) => Invoice(
    id: asString(j['id']) ?? '',
    invoiceNumber: asString(j['invoice_number']) ?? '',
    period: asString(j['period']),
    billGroupLabel: asString(j['bill_group_label']),
    grossAmount: _money(j['gross_amount']),
    discountAmount: _money(j['discount_amount']),
    amount: _money(j['amount']),
    paidAmount: _money(j['paid_amount']),
    outstanding: _money(j['outstanding']),
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    isOverdue: asBool(j['is_overdue']),
    dueDate: asDateTime(j['due_date']),
    items: asMapList(j['items']).map(InvoiceItem.fromJson).toList(),
    installments: asMapList(
      j['installments'],
    ).map(Installment.fromJson).toList(),
    allocations: asMapList(j['payments']).map(Allocation.fromJson).toList(),
    penaltyInvoices: asMapList(
      j['penalty_invoices'],
    ).map(Invoice.fromJson).toList(),
    pendingTransactions: asMapList(
      j['pending_transactions'],
    ).map(FinanceTransaction.fromJson).toList(),
    cancelReason: asString(j['cancel_reason']),
  );

  final String id;
  final String invoiceNumber;
  final String? period;
  final String? billGroupLabel;
  final String grossAmount;
  final String discountAmount;
  final String amount;
  final String paidAmount;
  final String outstanding;
  final String status;
  final String? statusLabel;
  final bool isOverdue;
  final DateTime? dueDate;
  final List<InvoiceItem> items;
  final List<Installment> installments;
  final List<Allocation> allocations;
  final List<Invoice> penaltyInvoices;
  final List<FinanceTransaction> pendingTransactions;
  final String? cancelReason;

  bool get isOpen => status == 'unpaid' || status == 'partial';
  bool get canPay => isOpen && moneyValue(outstanding) > 0;

  List<Installment> get activeInstallments =>
      installments.where((i) => i.status != 'superseded').toList();

  Installment? get nextInstallment {
    for (final i in activeInstallments) {
      if (i.isPayable) return i;
    }
    return null;
  }
}

class InstallmentPlan {
  const InstallmentPlan({
    required this.id,
    required this.code,
    required this.name,
    this.terms = const [],
    required this.autoApprove,
  });

  factory InstallmentPlan.fromJson(Map<String, dynamic> j) => InstallmentPlan(
    id: asString(j['id']) ?? '',
    code: asString(j['code']) ?? '',
    name: asString(j['name']) ?? '',
    terms: asMapList(j['terms']),
    autoApprove: asBool(j['auto_approve']),
  );

  final String id;
  final String code;
  final String name;
  final List<Map<String, dynamic>> terms;
  final bool autoApprove;
}

// ---------------------------------------------------------------------------
// Channels & transactions
// ---------------------------------------------------------------------------

class OnlineChannel {
  const OnlineChannel({
    required this.id,
    required this.code,
    required this.name,
    required this.method,
    required this.feeAmount,
    this.feeBearer,
  });

  factory OnlineChannel.fromJson(Map<String, dynamic> j) => OnlineChannel(
    id: asString(j['id']) ?? '',
    code: asString(j['code']) ?? '',
    name: asString(j['name']) ?? '',
    method: asString(j['method']) ?? '',
    feeAmount: _money(j['fee_amount']),
    feeBearer: asString(j['fee_bearer']),
  );

  final String id;
  final String code;
  final String name;
  final String method;
  final String feeAmount;
  final String? feeBearer;

  bool get studentPaysFee =>
      feeBearer == 'student' && moneyValue(feeAmount) > 0;
}

/// Bank accounts are free-form JSON from campus settings; read common keys.
class BankAccount {
  const BankAccount(this.raw);

  factory BankAccount.from(Object? v) =>
      v is Map ? BankAccount(asMap(v)) : BankAccount({'text': '$v'});

  final Map<String, dynamic> raw;

  String? _first(List<String> keys) {
    for (final k in keys) {
      final v = asString(raw[k]);
      if (v != null && v.isNotEmpty) return v;
    }
    return null;
  }

  String? get bank => _first(['bank', 'bank_name', 'name', 'bankName']);
  String? get number => _first([
    'account_number',
    'number',
    'account_no',
    'accountNumber',
    'va_number',
  ]);
  String? get holder =>
      _first(['account_name', 'holder', 'owner', 'atas_nama', 'accountName']);
  String? get text => _first(['text']);
}

List<BankAccount> parseBankAccounts(Object? v) =>
    v is List ? v.map(BankAccount.from).toList() : const [];

class ManualTransferInfo {
  const ManualTransferInfo({
    required this.enabled,
    this.bankAccounts = const [],
    required this.uniqueCode,
  });

  factory ManualTransferInfo.fromJson(Map<String, dynamic> j) =>
      ManualTransferInfo(
        enabled: asBool(j['enabled']),
        bankAccounts: parseBankAccounts(j['bank_accounts']),
        uniqueCode: asBool(j['unique_code']),
      );

  final bool enabled;
  final List<BankAccount> bankAccounts;
  final bool uniqueCode;
}

class PaymentChannels {
  const PaymentChannels({this.online = const [], this.manual});

  factory PaymentChannels.fromJson(Map<String, dynamic> j) => PaymentChannels(
    online: asMapList(j['online']).map(OnlineChannel.fromJson).toList(),
    manual: j['manual_transfer'] is Map
        ? ManualTransferInfo.fromJson(asMap(j['manual_transfer']))
        : null,
  );

  final List<OnlineChannel> online;
  final ManualTransferInfo? manual;

  bool get manualEnabled => manual?.enabled ?? false;
}

class PaymentProof {
  const PaymentProof({
    required this.id,
    required this.status,
    this.statusLabel,
    this.senderBank,
    this.senderName,
    this.transferDate,
    this.transferredAmount,
    this.reasonCode,
    this.note,
    this.duplicateWarning = false,
  });

  factory PaymentProof.fromJson(Map<String, dynamic> j) => PaymentProof(
    id: asString(j['id']) ?? '',
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    senderBank: asString(j['sender_bank']),
    senderName: asString(j['sender_name']),
    transferDate: asDateTime(j['transfer_date']),
    transferredAmount: asString(j['transferred_amount']),
    reasonCode: asString(j['reason_code']),
    note: asString(j['note']),
    duplicateWarning: asBool(j['duplicate_warning']),
  );

  final String id;
  final String status;
  final String? statusLabel;
  final String? senderBank;
  final String? senderName;
  final DateTime? transferDate;
  final String? transferredAmount;
  final String? reasonCode;
  final String? note;
  final bool duplicateWarning;
}

class PaymentStepGroup {
  const PaymentStepGroup({required this.title, required this.steps});
  final String title;
  final List<String> steps;
}

class FinanceTransaction {
  const FinanceTransaction({
    required this.id,
    required this.transactionNumber,
    this.receiptNumber,
    this.channelName,
    required this.method,
    this.methodLabel,
    this.targetType,
    required this.amount,
    required this.channelFee,
    required this.status,
    this.statusLabel,
    this.vaNumber,
    this.redirectUrl,
    this.uniqueCode,
    this.steps = const [],
    this.bankAccounts = const [],
    this.expiresAt,
    this.paidAt,
    this.failureReason,
    this.proof,
    this.createdAt,
  });

  factory FinanceTransaction.fromJson(Map<String, dynamic> j) {
    final instructions = asMap(j['instructions']);
    final steps = <PaymentStepGroup>[];
    final rawSteps = instructions['steps'];
    if (rawSteps is List) {
      for (final s in rawSteps) {
        if (s is Map) {
          steps.add(
            PaymentStepGroup(
              title: asString(s['title']) ?? '',
              steps: asStringList(s['steps']),
            ),
          );
        } else if (s is String) {
          steps.add(PaymentStepGroup(title: '', steps: [s]));
        }
      }
    }
    return FinanceTransaction(
      id: asString(j['id']) ?? '',
      transactionNumber: asString(j['transaction_number']) ?? '',
      receiptNumber: asString(j['receipt_number']),
      channelName: j['channel'] is Map
          ? asString(asMap(j['channel'])['name'])
          : null,
      method: asString(j['method']) ?? '',
      methodLabel: asString(j['method_label']),
      targetType: asString(j['target_type']),
      amount: _money(j['amount']),
      channelFee: _money(j['channel_fee']),
      status: asString(j['status']) ?? '',
      statusLabel: asString(j['status_label']),
      vaNumber: asString(j['va_number']),
      redirectUrl: asString(j['redirect_url']),
      uniqueCode: asString(j['unique_code']),
      steps: steps,
      bankAccounts: parseBankAccounts(instructions['bank_accounts']),
      expiresAt: asDateTime(j['expires_at']),
      paidAt: asDateTime(j['paid_at']),
      failureReason: asString(j['failure_reason']),
      proof: j['proof'] is Map
          ? PaymentProof.fromJson(asMap(j['proof']))
          : null,
      createdAt: asDateTime(j['created_at']),
    );
  }

  final String id;
  final String transactionNumber;
  final String? receiptNumber;
  final String? channelName;
  final String method;
  final String? methodLabel;
  final String? targetType;
  final String amount;
  final String channelFee;
  final String status;
  final String? statusLabel;
  final String? vaNumber;
  final String? redirectUrl;
  final String? uniqueCode;
  final List<PaymentStepGroup> steps;
  final List<BankAccount> bankAccounts;
  final DateTime? expiresAt;
  final DateTime? paidAt;
  final String? failureReason;
  final PaymentProof? proof;
  final DateTime? createdAt;

  bool get isOnline => method == 'online';
  bool get isManual => method == 'manual_transfer';
  bool get isCancellable => status == 'created' || status == 'pending';
  bool get canUploadProof =>
      isManual && (status == 'pending' || status == 'failed');
  bool get canRefresh => isOnline && status == 'pending';
  bool get hasReceipt =>
      (status == 'success' || status == 'refunded') &&
      (receiptNumber?.isNotEmpty ?? false);
}

// ---------------------------------------------------------------------------
// Deposit, statement, UKT, awards
// ---------------------------------------------------------------------------

class DepositEntry {
  const DepositEntry({
    required this.id,
    required this.type,
    this.typeLabel,
    required this.amount,
    required this.balanceAfter,
    this.note,
    this.createdAt,
  });

  factory DepositEntry.fromJson(Map<String, dynamic> j) => DepositEntry(
    id: asString(j['id']) ?? '',
    type: asString(j['type']) ?? '',
    typeLabel: asString(j['type_label']),
    amount: _money(j['amount']),
    balanceAfter: _money(j['balance_after']),
    note: asString(j['note']),
    createdAt: asDateTime(j['created_at']),
  );

  final String id;
  final String type;
  final String? typeLabel;
  final String amount;
  final String balanceAfter;
  final String? note;
  final DateTime? createdAt;
}

class Refund {
  const Refund({
    required this.id,
    required this.refundNumber,
    required this.amount,
    this.reasonCode,
    this.beneficiaryBank,
    this.beneficiaryAccount,
    this.beneficiaryName,
    required this.status,
    this.statusLabel,
    this.decisionNote,
    this.failureReason,
    this.createdAt,
  });

  factory Refund.fromJson(Map<String, dynamic> j) => Refund(
    id: asString(j['id']) ?? '',
    refundNumber: asString(j['refund_number']) ?? '',
    amount: _money(j['amount']),
    reasonCode: asString(j['reason_code']),
    beneficiaryBank: asString(j['beneficiary_bank']),
    beneficiaryAccount: asString(j['beneficiary_account']),
    beneficiaryName: asString(j['beneficiary_name']),
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    decisionNote: asString(j['decision_note']),
    failureReason: asString(j['failure_reason']),
    createdAt: asDateTime(j['created_at']),
  );

  final String id;
  final String refundNumber;
  final String amount;
  final String? reasonCode;
  final String? beneficiaryBank;
  final String? beneficiaryAccount;
  final String? beneficiaryName;
  final String status;
  final String? statusLabel;
  final String? decisionNote;
  final String? failureReason;
  final DateTime? createdAt;
}

class DepositInfo {
  const DepositInfo({
    required this.balance,
    required this.heldAmount,
    required this.available,
    this.entries = const [],
    this.refunds = const [],
  });

  factory DepositInfo.fromJson(Map<String, dynamic> j) => DepositInfo(
    balance: _money(j['balance']),
    heldAmount: _money(j['held_amount']),
    available: _money(j['available']),
    entries: asMapList(j['entries']).map(DepositEntry.fromJson).toList(),
    refunds: asMapList(j['refunds']).map(Refund.fromJson).toList(),
  );

  final String balance;
  final String heldAmount;
  final String available;
  final List<DepositEntry> entries;
  final List<Refund> refunds;
}

class StatementEntry {
  const StatementEntry({
    this.date,
    this.invoiceNumber,
    required this.type,
    this.description,
    required this.amount,
    required this.balance,
  });

  factory StatementEntry.fromJson(Map<String, dynamic> j) => StatementEntry(
    date: asDateTime(j['date']),
    invoiceNumber: asString(j['invoice_number']),
    type: asString(j['type']) ?? '',
    description: asString(j['description']),
    amount: _money(j['amount']),
    balance: _money(j['balance']),
  );

  final DateTime? date;
  final String? invoiceNumber;
  final String type;
  final String? description;
  final String amount;
  final String balance;
}

class Statement {
  const Statement({this.entries = const [], required this.balance});

  factory Statement.fromJson(Map<String, dynamic> j) => Statement(
    entries: asMapList(j['entries']).map(StatementEntry.fromJson).toList(),
    balance: _money(j['balance']),
  );

  final List<StatementEntry> entries;
  final String balance;
}

class UktGroup {
  const UktGroup({required this.id, required this.code, required this.name});

  factory UktGroup.fromJson(Object? v) {
    final j = asMap(v);
    return UktGroup(
      id: asString(j['id']) ?? '',
      code: asString(j['code']) ?? '',
      name: asString(j['name']) ?? '',
    );
  }

  final String id;
  final String code;
  final String name;
}

class UktCurrent {
  const UktCurrent({
    required this.group,
    this.basis,
    this.decreeNumber,
    this.effectiveFrom,
  });

  factory UktCurrent.fromJson(Map<String, dynamic> j) => UktCurrent(
    group: UktGroup.fromJson(j['group']),
    basis: asString(j['basis']),
    decreeNumber: asString(j['decree_number']),
    effectiveFrom: asDateTime(j['effective_from']),
  );

  final UktGroup group;
  final String? basis;
  final String? decreeNumber;
  final DateTime? effectiveFrom;
}

class UktRequest {
  const UktRequest({
    required this.id,
    this.requestedGroup,
    this.decidedGroup,
    this.reason,
    required this.status,
    this.statusLabel,
    this.decisionNote,
    this.createdAt,
  });

  factory UktRequest.fromJson(Map<String, dynamic> j) => UktRequest(
    id: asString(j['id']) ?? '',
    requestedGroup: j['requested_group'] is Map
        ? UktGroup.fromJson(j['requested_group'])
        : null,
    decidedGroup: j['decided_group'] is Map
        ? UktGroup.fromJson(j['decided_group'])
        : null,
    reason: asString(j['reason']),
    status: asString(j['status']) ?? '',
    statusLabel: asString(j['status_label']),
    decisionNote: asString(j['decision_note']),
    createdAt: asDateTime(j['created_at']),
  );

  final String id;
  final UktGroup? requestedGroup;
  final UktGroup? decidedGroup;
  final String? reason;
  final String status;
  final String? statusLabel;
  final String? decisionNote;
  final DateTime? createdAt;
}

class UktInfo {
  const UktInfo({
    this.current,
    this.groups = const [],
    this.requests = const [],
  });

  factory UktInfo.fromJson(Map<String, dynamic> j) => UktInfo(
    current: j['current'] is Map
        ? UktCurrent.fromJson(asMap(j['current']))
        : null,
    groups: asMapList(j['groups']).map(UktGroup.fromJson).toList(),
    requests: asMapList(j['requests']).map(UktRequest.fromJson).toList(),
  );

  final UktCurrent? current;
  final List<UktGroup> groups;
  final List<UktRequest> requests;

  bool get hasPendingRequest => requests.any((r) => r.status == 'submitted');
}

class Award {
  const Award({
    required this.id,
    this.policyName,
    this.kind,
    this.kindLabel,
    this.calcTypeLabel,
    this.value,
    this.maxAmount,
    this.startTerm,
    this.endTerm,
    required this.status,
    this.statusLabel,
    this.decisionNote,
    this.revokeReason,
  });

  factory Award.fromJson(Map<String, dynamic> j) {
    final policy = asMap(j['policy']);
    return Award(
      id: asString(j['id']) ?? '',
      policyName: asString(policy['name']),
      kind: asString(policy['kind']),
      kindLabel: asString(policy['kind_label']),
      calcTypeLabel: asString(j['calc_type_label']),
      value: asString(j['value']),
      maxAmount: asString(j['max_amount']),
      startTerm: asString(j['start_term']),
      endTerm: asString(j['end_term']),
      status: asString(j['status']) ?? '',
      statusLabel: asString(j['status_label']),
      decisionNote: asString(j['decision_note']),
      revokeReason: asString(j['revoke_reason']),
    );
  }

  final String id;
  final String? policyName;
  final String? kind;
  final String? kindLabel;
  final String? calcTypeLabel;
  final String? value;
  final String? maxAmount;
  final String? startTerm;
  final String? endTerm;
  final String status;
  final String? statusLabel;
  final String? decisionNote;
  final String? revokeReason;
}
