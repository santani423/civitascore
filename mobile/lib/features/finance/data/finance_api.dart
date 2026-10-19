import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../domain/finance_models.dart';

const _base = '/student/finance';

final financeApiProvider = Provider<FinanceApi>(
  (ref) => FinanceApi(ref.watch(apiClientProvider)),
);

/// Student finance endpoints (`/api/v1/student/finance/*`).
class FinanceApi {
  FinanceApi(this._api);
  final ApiClient _api;

  Future<FinanceSummary> summary() => _api.get(
    '$_base/summary',
    cache: true,
    decode: (d) => FinanceSummary.fromJson(asMap(d)),
  );

  Future<Paginated<Invoice>> invoices({String? status, int page = 1}) =>
      _api.getPage(
        '$_base/invoices',
        query: {
          'page': page,
          'per_page': 20,
          if (status != null && status.isNotEmpty) 'status': status,
        },
        item: Invoice.fromJson,
        cache: true,
      );

  Future<Invoice> invoice(String id) => _api.get(
    '$_base/invoices/$id',
    cache: true,
    decode: (d) => Invoice.fromJson(asMap(d)),
  );

  Future<List<InstallmentPlan>> installmentPlans(String invoiceId) =>
      _api.getList(
        '$_base/invoices/$invoiceId/installment-plans',
        item: InstallmentPlan.fromJson,
      );

  Future<PaymentChannels> channels() => _api.get(
    '$_base/channels',
    decode: (d) => PaymentChannels.fromJson(asMap(d)),
  );

  Future<Paginated<FinanceTransaction>> transactions({int page = 1}) =>
      _api.getPage(
        '$_base/transactions',
        query: {'page': page, 'per_page': 20},
        item: FinanceTransaction.fromJson,
        cache: true,
      );

  Future<DepositInfo> deposit() => _api.get(
    '$_base/deposit',
    cache: true,
    decode: (d) => DepositInfo.fromJson(asMap(d)),
  );

  Future<Statement> statement() => _api.get(
    '$_base/statement',
    cache: true,
    decode: (d) => Statement.fromJson(asMap(d)),
  );

  Future<UktInfo> ukt() => _api.get(
    '$_base/ukt',
    cache: true,
    decode: (d) => UktInfo.fromJson(asMap(d)),
  );

  Future<List<Award>> awards() =>
      _api.getList('$_base/awards', item: Award.fromJson, cache: true);

  // Writes -----------------------------------------------------------------

  Future<FinanceTransaction> payOnline({
    required String channelId,
    required String targetType,
    required String targetId,
    required String idempotencyKey,
  }) => _api.post(
    '$_base/payments/online',
    headers: {'Idempotency-Key': idempotencyKey},
    data: {
      'payment_channel_id': channelId,
      'target_type': targetType,
      'target_id': targetId,
    },
    decode: (d) => FinanceTransaction.fromJson(asMap(d)),
  );

  Future<FinanceTransaction> payManual({
    required String targetType,
    String? targetId,
    String? amount,
  }) => _api.post(
    '$_base/payments/manual',
    data: {
      'target_type': targetType,
      'target_id': ?targetId,
      'amount': ?amount,
    },
    decode: (d) => FinanceTransaction.fromJson(asMap(d)),
  );

  Future<FinanceTransaction> refresh(String transactionId) => _api.post(
    '$_base/transactions/$transactionId/refresh',
    decode: (d) => FinanceTransaction.fromJson(asMap(d)),
  );

  Future<FinanceTransaction> cancel(String transactionId) => _api.post(
    '$_base/transactions/$transactionId/cancel',
    decode: (d) => FinanceTransaction.fromJson(asMap(d)),
  );

  Future<FinanceTransaction> submitProof(
    String transactionId, {
    required String fileUploadId,
    required String senderBank,
    required String senderName,
    required String transferDate,
    required String transferredAmount,
  }) => _api.post(
    '$_base/transactions/$transactionId/proof',
    data: {
      'file_upload_id': fileUploadId,
      'sender_bank': senderBank,
      'sender_name': senderName,
      'transfer_date': transferDate,
      'transferred_amount': transferredAmount,
    },
    decode: (d) => FinanceTransaction.fromJson(asMap(d)),
  );

  Future<String?> applyDeposit(
    String invoiceId, {
    String? amount,
    String? installmentId,
  }) => _api.postForMessage(
    '$_base/invoices/$invoiceId/deposit',
    data: {'amount': ?amount, 'installment_id': ?installmentId},
  );

  Future<String?> redeemVoucher(String invoiceId, String code) =>
      _api.postForMessage(
        '$_base/invoices/$invoiceId/voucher',
        data: {'code': code},
      );

  Future<String?> requestInstallment(
    String invoiceId, {
    String? planId,
    List<Map<String, dynamic>>? terms,
    String? reason,
    String kind = 'new',
  }) => _api.postForMessage(
    '$_base/invoices/$invoiceId/installments',
    data: {
      'installment_plan_id': ?planId,
      'terms': ?terms,
      if (reason != null && reason.isNotEmpty) 'reason': reason,
      'kind': kind,
    },
  );

  Future<String?> requestRefund({
    required String amount,
    required String reasonCode,
    required String reasonNote,
    required String bank,
    required String account,
    required String name,
    required String relation,
  }) => _api.postForMessage(
    '$_base/refunds',
    data: {
      'amount': amount,
      'reason_code': reasonCode,
      'reason_note': reasonNote,
      'beneficiary_bank': bank,
      'beneficiary_account': account,
      'beneficiary_name': name,
      'beneficiary_relation': relation,
    },
  );

  Future<String?> requestUktChange({
    String? requestedGroupId,
    required String reason,
    required String evidenceFileId,
  }) => _api.postForMessage(
    '$_base/ukt-requests',
    data: {
      'requested_group_id': ?requestedGroupId,
      'reason': reason,
      'evidence_file_id': evidenceFileId,
    },
  );

  Future<String?> requestDispensation(
    String blockId, {
    required String service,
    required String validUntil,
    required String reason,
    String? promiseAmount,
    String? promiseDate,
  }) => _api.postForMessage(
    '$_base/blocks/$blockId/dispensations',
    data: {
      'service': service,
      'valid_until': validUntil,
      'reason': reason,
      'promise_amount': ?promiseAmount,
      'promise_date': ?promiseDate,
    },
  );
}
