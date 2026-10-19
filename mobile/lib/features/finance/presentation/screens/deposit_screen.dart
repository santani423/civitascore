import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../domain/finance_models.dart';
import '../../l10n/finance_strings.dart';
import '../providers.dart';
import '../widgets/finance_forms.dart';
import '../widgets/finance_widgets.dart';

class DepositScreen extends ConsumerWidget {
  const DepositScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final f = context.fin;
    final value = ref.watch(depositProvider);
    return Scaffold(
      appBar: AppBar(title: Text(f.deposit)),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(depositProvider);
          try {
            await ref.read(depositProvider.future);
          } catch (_) {
            // shown by the error view
          }
        },
        child: AsyncValueView<DepositInfo>(
          value: value,
          onRetry: () => ref.invalidate(depositProvider),
          data: (d) => _Body(deposit: d),
        ),
      ),
    );
  }
}

class _Body extends StatelessWidget {
  const _Body({required this.deposit});
  final DepositInfo deposit;

  @override
  Widget build(BuildContext context) {
    final f = context.fin;
    final text = Theme.of(context).textTheme;
    final d = deposit;
    return FinanceBody(
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(AppSpacing.lg),
        children: [
          AppSurfaceCard(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(f.depositBalance, style: text.bodySmall),
                Text(context.formatMoney(d.balance), style: text.headlineSmall),
                const Divider(),
                InfoRow(
                  label: f.depositHeld,
                  value: context.formatMoney(d.heldAmount),
                ),
                InfoRow(
                  label: f.depositAvailable,
                  value: context.formatMoney(d.available),
                  valueStyle: text.titleSmall,
                ),
              ],
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.sm,
            children: [
              FilledButton.icon(
                onPressed: moneyValue(d.available) > 0
                    ? () => showRefundSheet(context, d)
                    : null,
                icon: const Icon(Icons.undo),
                label: Text(f.requestRefund),
              ),
              OutlinedButton.icon(
                onPressed: () => showTopUpSheet(context),
                icon: const Icon(Icons.add_card_outlined),
                label: Text(f.topUp),
              ),
            ],
          ),
          if (d.refunds.isNotEmpty) ...[
            SectionHeader(
              title: f.refundsTitle,
              padding: const EdgeInsets.only(
                top: AppSpacing.xl,
                bottom: AppSpacing.sm,
              ),
            ),
            for (final r in d.refunds)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                child: AppSurfaceCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(r.refundNumber, style: text.titleSmall),
                          ),
                          StatusChip(
                            label: f.refundStatus(r.status, r.statusLabel),
                            tone: genericTone(r.status),
                            dense: true,
                          ),
                        ],
                      ),
                      Text(
                        context.formatMoney(r.amount),
                        style: text.titleMedium,
                      ),
                      Text(
                        [
                          if (r.reasonCode != null)
                            f.refundReasonLabel(r.reasonCode!),
                          context.formatDate(r.createdAt),
                        ].join(' · '),
                        style: text.bodySmall,
                      ),
                      if (r.beneficiaryBank != null)
                        Text(
                          '${r.beneficiaryBank} ${r.beneficiaryAccount ?? ''} · ${r.beneficiaryName ?? ''}',
                          style: text.bodySmall,
                        ),
                      if (r.decisionNote != null) Text(r.decisionNote!),
                      if (r.failureReason != null)
                        Text(r.failureReason!, style: text.bodySmall),
                    ],
                  ),
                ),
              ),
          ],
          SectionHeader(
            title: f.depositEntries,
            padding: const EdgeInsets.only(
              top: AppSpacing.xl,
              bottom: AppSpacing.sm,
            ),
          ),
          if (d.entries.isEmpty)
            Padding(
              padding: const EdgeInsets.all(AppSpacing.lg),
              child: Text(f.noDepositEntries),
            )
          else
            AppSurfaceCard(
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
              child: Column(
                children: [
                  for (final e in d.entries)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      dense: true,
                      title: Text(f.depositType(e.type, e.typeLabel)),
                      subtitle: Text(
                        [
                          context.formatDateTime(e.createdAt),
                          ?e.note,
                        ].join(' · '),
                      ),
                      trailing: Text(
                        context.formatMoney(e.amount),
                        style: text.bodyMedium,
                      ),
                    ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
