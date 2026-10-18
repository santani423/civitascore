import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../l10n/core_strings.dart';
import '../network/api_envelope.dart';
import '../theme/app_dimensions.dart';
import 'state_views.dart';

/// State of an incrementally loaded list.
class PagedState<T> {
  const PagedState({
    this.items = const [],
    this.page = 0,
    this.hasMore = true,
    this.loadingMore = false,
    this.error,
  });
  final List<T> items;
  final int page;
  final bool hasMore;
  final bool loadingMore;
  final Object? error;

  PagedState<T> copyWith({
    List<T>? items,
    int? page,
    bool? hasMore,
    bool? loadingMore,
    Object? error,
    bool clearError = false,
  }) => PagedState(
    items: items ?? this.items,
    page: page ?? this.page,
    hasMore: hasMore ?? this.hasMore,
    loadingMore: loadingMore ?? this.loadingMore,
    error: clearError ? null : (error ?? this.error),
  );
}

/// Base notifier for paginated endpoints. Subclasses implement [fetchPage].
/// `build()` loads page 1; [loadMore] appends; [refresh] restarts.
abstract class PagedNotifier<T>
    extends AutoDisposeAsyncNotifier<PagedState<T>> {
  Future<Paginated<T>> fetchPage(int page);

  @override
  Future<PagedState<T>> build() async {
    final first = await fetchPage(1);
    return PagedState(items: first.items, page: 1, hasMore: first.hasNextPage);
  }

  Future<void> loadMore() async {
    final current = state.valueOrNull;
    if (current == null || !current.hasMore || current.loadingMore) return;
    state = AsyncData(current.copyWith(loadingMore: true, clearError: true));
    try {
      final next = await fetchPage(current.page + 1);
      state = AsyncData(
        current.copyWith(
          items: [...current.items, ...next.items],
          page: current.page + 1,
          hasMore: next.hasNextPage,
          loadingMore: false,
        ),
      );
    } catch (e) {
      state = AsyncData(current.copyWith(loadingMore: false, error: e));
    }
  }

  Future<void> refresh() async {
    ref.invalidateSelf();
    await future;
  }
}

/// Pull-to-refresh list with infinite scroll for a [PagedNotifier].
class PagedListView<T> extends StatelessWidget {
  const PagedListView({
    super.key,
    required this.state,
    required this.itemBuilder,
    required this.onRefresh,
    required this.onLoadMore,
    this.empty,
    this.separator,
    this.padding,
    this.header,
  });

  final AsyncValue<PagedState<T>> state;
  final Widget Function(BuildContext context, T item) itemBuilder;
  final Future<void> Function() onRefresh;
  final VoidCallback onLoadMore;
  final Widget? empty;
  final Widget? separator;
  final EdgeInsets? padding;
  final Widget? header;

  @override
  Widget build(BuildContext context) {
    return state.when(
      skipLoadingOnRefresh: true,
      loading: () => const SkeletonList(),
      error: (e, _) => ErrorView(error: e, onRetry: onRefresh),
      data: (s) {
        if (s.items.isEmpty) {
          return RefreshIndicator(
            onRefresh: onRefresh,
            child: LayoutBuilder(
              builder: (context, c) => SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: SizedBox(
                  height: c.maxHeight,
                  child: Column(
                    children: [
                      ?header,
                      Expanded(child: empty ?? const EmptyView()),
                    ],
                  ),
                ),
              ),
            ),
          );
        }
        final extra = (header != null ? 1 : 0);
        return NotificationListener<ScrollNotification>(
          onNotification: (n) {
            if (n.metrics.pixels >= n.metrics.maxScrollExtent - 300) {
              onLoadMore();
            }
            return false;
          },
          child: RefreshIndicator(
            onRefresh: onRefresh,
            child: ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding:
                  padding ??
                  const EdgeInsets.symmetric(vertical: AppSpacing.sm),
              itemCount: s.items.length + extra + 1,
              separatorBuilder: (_, i) => (header != null && i == 0)
                  ? const SizedBox.shrink()
                  : (separator ?? const SizedBox(height: AppSpacing.sm)),
              itemBuilder: (context, i) {
                if (header != null && i == 0) return header!;
                final index = i - extra;
                if (index == s.items.length) {
                  if (s.loadingMore) {
                    return const Padding(
                      padding: EdgeInsets.all(AppSpacing.lg),
                      child: Center(child: CircularProgressIndicator()),
                    );
                  }
                  if (s.error != null) {
                    return Center(
                      child: TextButton(
                        onPressed: onLoadMore,
                        child: Text(context.l10n.retry),
                      ),
                    );
                  }
                  return const SizedBox(height: AppSpacing.xl2);
                }
                return itemBuilder(context, s.items[index]);
              },
            ),
          ),
        );
      },
    );
  }
}
