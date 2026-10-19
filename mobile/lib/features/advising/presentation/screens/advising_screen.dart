import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/pending/pending_feature_screen.dart';
import '../../../../core/pending/pending_features.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/widgets/notification_bell.dart';
import '../../../../core/widgets/paged_list.dart';
import '../../../../core/widgets/state_views.dart';
import '../../l10n/advising_strings.dart';
import '../providers.dart';
import '../widgets/krs_card.dart';

/// Advisor tab: KRS submissions to review (`krs_advising.read`).
class AdvisingScreen extends ConsumerStatefulWidget {
  const AdvisingScreen({super.key});

  @override
  ConsumerState<AdvisingScreen> createState() => _AdvisingScreenState();
}

class _AdvisingScreenState extends ConsumerState<AdvisingScreen> {
  final _search = TextEditingController();

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => PermissionGate(
    permission: 'krs_advising.read,krs.approve',
    child: _body(context),
  );

  Widget _body(BuildContext context) {
    final s = context.advisingL10n;
    final status = ref.watch(advisingStatusProvider);
    final state = ref.watch(krsListProvider);
    final notifier = ref.read(krsListProvider.notifier);
    return Scaffold(
      appBar: AppBar(
        title: Text(s.title),
        actions: [
          IconButton(
            tooltip: pendingFeatureByKey('advisees')!.title(context),
            icon: const Icon(Icons.groups_2_outlined),
            onPressed: () => showModalBottomSheet<void>(
              context: context,
              showDragHandle: true,
              builder: (_) => SafeArea(
                child: Padding(
                  padding: const EdgeInsets.only(bottom: AppSpacing.lg),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      PendingFeatureTile(
                        feature: pendingFeatureByKey('advisees')!,
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
          const NotificationBellButton(),
        ],
      ),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 720),
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(
                  AppSpacing.lg,
                  AppSpacing.md,
                  AppSpacing.lg,
                  AppSpacing.sm,
                ),
                child: TextField(
                  controller: _search,
                  textInputAction: TextInputAction.search,
                  decoration: InputDecoration(
                    hintText: s.searchHint,
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _search.text.isEmpty
                        ? null
                        : IconButton(
                            tooltip: s.clear,
                            icon: const Icon(Icons.close),
                            onPressed: () {
                              _search.clear();
                              ref.read(advisingSearchProvider.notifier).state =
                                  '';
                              setState(() {});
                            },
                          ),
                  ),
                  onChanged: (_) => setState(() {}),
                  onSubmitted: (v) =>
                      ref.read(advisingSearchProvider.notifier).state = v
                          .trim(),
                ),
              ),
              SizedBox(
                height: 48,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(
                    horizontal: AppSpacing.lg,
                  ),
                  children: [
                    for (final st in advisingStatuses)
                      Padding(
                        padding: const EdgeInsets.only(right: AppSpacing.sm),
                        child: ChoiceChip(
                          label: Text(s.statusLabel(st)),
                          selected: status == st,
                          onSelected: (_) =>
                              ref.read(advisingStatusProvider.notifier).state =
                                  st,
                        ),
                      ),
                  ],
                ),
              ),
              Expanded(
                child: PagedListView(
                  state: state,
                  onRefresh: notifier.refresh,
                  onLoadMore: notifier.loadMore,
                  empty: EmptyView(title: s.emptyTitle, message: s.emptyBody),
                  itemBuilder: (context, item) => KrsCard(
                    item: item,
                    onTap: () =>
                        context.push(RoutePaths.lecturerKrsSubmission(item.id)),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
