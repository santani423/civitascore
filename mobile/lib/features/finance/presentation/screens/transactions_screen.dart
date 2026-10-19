import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/paged_list.dart';
import '../../../../core/widgets/state_views.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import '../widgets/finance_widgets.dart';
import '../widgets/transaction_sheet.dart';

/// "Riwayat transaksi": list with status chips; tap opens the transaction.
class TransactionsScreen extends ConsumerStatefulWidget {
  const TransactionsScreen({super.key});

  @override
  ConsumerState<TransactionsScreen> createState() => _TransactionsScreenState();
}

class _TransactionsScreenState extends ConsumerState<TransactionsScreen> {
  String? status;

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final raw = ref.watch(transactionsProvider);
    final notifier = ref.read(transactionsProvider.notifier);
    // Client-side filter over the loaded pages (the endpoint has no filter).
    final state = status == null
        ? raw
        : raw.whenData(
            (s) => s.copyWith(
              items: s.items.where((t) => t.status == status).toList(),
            ),
          );
    final present = <String>{
      for (final t in raw.valueOrNull?.items ?? const <FinanceTransaction>[])
        t.status,
    };
    return Scaffold(
      appBar: AppBar(title: Text(f.transactions)),
      body: FinanceBody(
        child: Column(
          children: [
            if (present.isNotEmpty)
              SizedBox(
                height: 52,
                child: SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.lg,
                    vertical: AppSpacing.sm,
                  ),
                  child: Row(
                    children: [
                      Padding(
                        padding: const EdgeInsets.only(right: AppSpacing.sm),
                        child: ChoiceChip(
                          label: Text(context.l10n.all),
                          selected: status == null,
                          onSelected: (_) => setState(() => status = null),
                        ),
                      ),
                      for (final st in present)
                        Padding(
                          padding: const EdgeInsets.only(right: AppSpacing.sm),
                          child: ChoiceChip(
                            label: Text(f.txStatus(st, null)),
                            selected: status == st,
                            onSelected: (_) => setState(() => status = st),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            Expanded(
              child: PagedListView(
                state: state,
                onRefresh: notifier.refresh,
                onLoadMore: notifier.loadMore,
                padding: const EdgeInsets.symmetric(
                  horizontal: AppSpacing.lg,
                  vertical: AppSpacing.sm,
                ),
                empty: EmptyView(
                  icon: Icons.history,
                  title: f.noTransactions,
                  message: status == null
                      ? f.noTransactionsBody
                      : f.noMatchingTx,
                ),
                itemBuilder: (context, tx) => TransactionTile(
                  tx: tx,
                  onTap: () => showTransactionSheet(context, tx),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
