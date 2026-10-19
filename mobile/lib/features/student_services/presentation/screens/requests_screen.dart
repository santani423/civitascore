import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/l10n/core_strings.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/paged_list.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../../auth/presentation/controllers/auth_controller.dart';
import '../../domain/student_request.dart';
import '../../l10n/student_services_strings.dart';
import '../providers.dart';
import '../widgets/request_widgets.dart';

class RequestsScreen extends ConsumerWidget {
  const RequestsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final sv = context.sv;
    final user = ref.watch(currentUserProvider);
    final canCreate = user?.hasPermission('student_requests.create') ?? false;
    final filter = ref.watch(requestStatusFilterProvider);
    final state = ref.watch(studentRequestsProvider);
    final notifier = ref.read(studentRequestsProvider.notifier);

    final filtered = state.whenData(
      (s) => filter == null
          ? s
          : s.copyWith(
              items: s.items.where((r) => r.status == filter).toList(),
            ),
    );

    return Scaffold(
      appBar: AppBar(title: Text(sv.listTitle)),
      floatingActionButton: canCreate
          ? FloatingActionButton.extended(
              onPressed: () => context.push(RoutePaths.studentRequestNew),
              icon: const Icon(Icons.add),
              label: Text(sv.newRequest),
            )
          : null,
      body: ConstrainedBody(
        child: Column(
          children: [
            _FilterBar(selected: filter),
            Expanded(
              child: PagedListView<StudentRequest>(
                state: filtered,
                onRefresh: notifier.refresh,
                onLoadMore: notifier.loadMore,
                padding: const EdgeInsets.fromLTRB(
                  AppSpacing.lg,
                  AppSpacing.sm,
                  AppSpacing.lg,
                  88,
                ),
                empty: filter == null
                    ? EmptyView(
                        title: sv.emptyTitle,
                        message: sv.emptyBody,
                        icon: Icons.description_outlined,
                      )
                    : EmptyView(
                        title: sv.emptyFilteredTitle,
                        message: sv.emptyFilteredBody,
                        icon: Icons.filter_alt_off_outlined,
                      ),
                itemBuilder: (context, r) => _RequestCard(request: r),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FilterBar extends ConsumerWidget {
  const _FilterBar({required this.selected});
  final RequestStatus? selected;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return SizedBox(
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
                selected: selected == null,
                onSelected: (_) =>
                    ref.read(requestStatusFilterProvider.notifier).state = null,
              ),
            ),
            for (final s in RequestStatus.values)
              Padding(
                padding: const EdgeInsets.only(right: AppSpacing.sm),
                child: ChoiceChip(
                  label: Text(context.sv.statusLabel(s.value, null)),
                  selected: selected == s,
                  onSelected: (_) =>
                      ref.read(requestStatusFilterProvider.notifier).state = s,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _RequestCard extends StatelessWidget {
  const _RequestCard({required this.request});
  final StudentRequest request;

  @override
  Widget build(BuildContext context) {
    final sv = context.sv;
    final text = Theme.of(context).textTheme;
    final muted = context.palette.mutedText;
    final type = request.type;
    final dates = [
      '${sv.created} ${context.formatDate(request.createdAt)}',
      if (request.submittedAt != null)
        '${sv.submitted} ${context.formatDate(request.submittedAt)}',
    ].join(' · ');
    return AppSurfaceCard(
      onTap: () => context.push(RoutePaths.studentRequest(request.id)),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            backgroundColor: Theme.of(context).colorScheme.primaryContainer,
            foregroundColor: Theme.of(context).colorScheme.onPrimaryContainer,
            child: Icon(type?.icon ?? Icons.help_outline),
          ),
          const SizedBox(width: AppSpacing.md),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  request.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: text.titleSmall,
                ),
                const SizedBox(height: 2),
                Text(
                  sv.typeLabel(request.typeValue, request.typeLabel),
                  style: text.bodySmall?.copyWith(color: muted),
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(dates, style: text.bodySmall?.copyWith(color: muted)),
                const SizedBox(height: AppSpacing.sm),
                RequestStatusChip(request: request),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
