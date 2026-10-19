import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import '../widgets/finance_widgets.dart';

class StatementScreen extends ConsumerWidget {
  const StatementScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final f = context.fin;
    final value = ref.watch(statementProvider);
    final text = Theme.of(context).textTheme;
    return Scaffold(
      appBar: AppBar(title: Text(f.statement)),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(statementProvider);
          try {
            await ref.read(statementProvider.future);
          } catch (_) {
            // shown by the error view
          }
        },
        child: AsyncValueView<Statement>(
          value: value,
          onRetry: () => ref.invalidate(statementProvider),
          isEmpty: (s) => s.entries.isEmpty,
          empty: EmptyView(icon: Icons.list_alt_outlined, title: f.noStatement),
          data: (s) => FinanceBody(
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(AppSpacing.lg),
              children: [
                AppSurfaceCard(
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(f.statementBalance, style: text.titleSmall),
                      ),
                      Text(
                        context.formatMoney(s.balance),
                        style: text.titleLarge,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: AppSpacing.sm),
                for (final e in s.entries)
                  Padding(
                    padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                    child: AppSurfaceCard(
                      padding: const EdgeInsets.all(AppSpacing.md),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(e.type, style: text.titleSmall),
                                if (e.description != null &&
                                    e.description!.isNotEmpty)
                                  Text(e.description!, style: text.bodyMedium),
                                Text(
                                  [
                                    context.formatDate(e.date),
                                    ?e.invoiceNumber,
                                  ].join(' · '),
                                  style: text.bodySmall,
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: AppSpacing.md),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                context.formatMoney(e.amount),
                                style: text.titleSmall?.copyWith(
                                  color: moneyValue(e.amount) < 0
                                      ? context
                                            .toneColors(StatusTone.success)
                                            .$1
                                      : null,
                                ),
                              ),
                              Text(
                                context.formatMoney(e.balance),
                                style: text.bodySmall,
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
