import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/network/api_client.dart';
import '../../../core/network/api_envelope.dart';
import '../../../core/utils/file_actions.dart';
import '../../../core/widgets/paged_list.dart';
import 'package:flutter/widgets.dart' show BuildContext;
import '../data/finance_api.dart';
import '../domain/finance_models.dart';

final financeSummaryProvider = FutureProvider.autoDispose<FinanceSummary>(
  (ref) => ref.watch(financeApiProvider).summary(),
);

/// Comma-separated status filter for the invoice list (`null` = all).
final invoiceStatusFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

class InvoicesNotifier extends PagedNotifier<Invoice> {
  @override
  Future<PagedState<Invoice>> build() {
    ref.watch(invoiceStatusFilterProvider);
    return super.build();
  }

  @override
  Future<Paginated<Invoice>> fetchPage(int page) => ref
      .read(financeApiProvider)
      .invoices(status: ref.read(invoiceStatusFilterProvider), page: page);
}

final invoicesProvider =
    AsyncNotifierProvider.autoDispose<InvoicesNotifier, PagedState<Invoice>>(
      InvoicesNotifier.new,
    );

final invoiceDetailProvider = FutureProvider.autoDispose
    .family<Invoice, String>(
      (ref, id) => ref.watch(financeApiProvider).invoice(id),
    );

final installmentPlansProvider = FutureProvider.autoDispose
    .family<List<InstallmentPlan>, String>(
      (ref, id) => ref.watch(financeApiProvider).installmentPlans(id),
    );

final paymentChannelsProvider = FutureProvider.autoDispose<PaymentChannels>(
  (ref) => ref.watch(financeApiProvider).channels(),
);

class TransactionsNotifier extends PagedNotifier<FinanceTransaction> {
  @override
  Future<Paginated<FinanceTransaction>> fetchPage(int page) =>
      ref.read(financeApiProvider).transactions(page: page);
}

final transactionsProvider =
    AsyncNotifierProvider.autoDispose<
      TransactionsNotifier,
      PagedState<FinanceTransaction>
    >(TransactionsNotifier.new);

final depositProvider = FutureProvider.autoDispose<DepositInfo>(
  (ref) => ref.watch(financeApiProvider).deposit(),
);

final statementProvider = FutureProvider.autoDispose<Statement>(
  (ref) => ref.watch(financeApiProvider).statement(),
);

final uktProvider = FutureProvider.autoDispose<UktInfo>(
  (ref) => ref.watch(financeApiProvider).ukt(),
);

final awardsProvider = FutureProvider.autoDispose<List<Award>>(
  (ref) => ref.watch(financeApiProvider).awards(),
);

/// Opens an external URL; overridable in tests.
final externalLauncherProvider = Provider<Future<bool> Function(Uri)>(
  (ref) =>
      (uri) => launchUrl(uri, mode: LaunchMode.externalApplication),
);

/// File picker for proofs/evidence (jpg, png, pdf); overridable in tests.
typedef UploadPicker =
    Future<PickedUpload?> Function(BuildContext context, {required int maxMb});

final uploadPickerProvider = Provider<UploadPicker>(
  (ref) =>
      (context, {required maxMb}) => pickUpload(
        context,
        extensions: const ['jpg', 'jpeg', 'png', 'pdf'],
        maxMb: maxMb,
      ),
);

/// Step 1 of two-step uploads (`POST /file-uploads`): returns the file id.
/// Overridable in tests (the real implementation reads the file from disk).
final fileUploaderProvider = Provider<Future<String> Function(PickedUpload)>(
  (ref) =>
      (file) => ref
          .read(apiClientProvider)
          .uploadFile(file.path, filename: file.name),
);
