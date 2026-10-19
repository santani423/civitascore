import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/paged_list.dart';
import '../../../../core/widgets/state_views.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import '../widgets/finance_widgets.dart';

/// "Tagihan": paginated invoices with a status filter.
class InvoicesScreen extends ConsumerWidget {
  const InvoicesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final f = context.fin;
    final filter = ref.watch(invoiceStatusFilterProvider);
    final state = ref.watch(invoicesProvider);
    final notifier = ref.read(invoicesProvider.notifier);
    final options = <(String?, String)>[
      (null, f.filterAll),
      ('unpaid,partial', f.filterOpen),
      ('paid', f.filterPaid),
      ('cancelled,waived', f.filterOther),
    ];
    return Scaffold(
      appBar: AppBar(title: Text(f.invoices)),
      body: FinanceBody(
        child: Column(
          children: [
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
                    for (final o in options)
                      Padding(
                        padding: const EdgeInsets.only(right: AppSpacing.sm),
                        child: ChoiceChip(
                          label: Text(o.$2),
                          selected: filter == o.$1,
                          onSelected: (_) =>
                              ref
                                  .read(invoiceStatusFilterProvider.notifier)
                                  .state = o
                                  .$1,
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
                  icon: Icons.receipt_long_outlined,
                  title: f.noInvoices,
                  message: f.noInvoicesBody,
                ),
                itemBuilder: (context, invoice) => InvoiceCard(
                  invoice: invoice,
                  onTap: () =>
                      context.push(RoutePaths.studentInvoice(invoice.id)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
