import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/permission/permission_gate.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/hr_api.dart';
import '../../domain/hr_models.dart';
import '../../l10n/hr_strings.dart';
import '../providers.dart';
import '../widgets/hr_common.dart';

/// GET /lecturer-profile (read-only) + PUT /lecturer-profile (phone only).
class LecturerProfileScreen extends ConsumerWidget {
  const LecturerProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final h = context.hrL10n;
    final value = ref.watch(lecturerProfileProvider);
    return PermissionGate(
      permission: 'lecturer_profile.read',
      child: Scaffold(
        appBar: AppBar(title: Text(h.lecturerProfileTitle)),
        body: value.when(
          skipLoadingOnRefresh: true,
          loading: () => const SkeletonList(),
          error: (e, _) => isNotLinked(e)
              ? NotLinkedView(
                  title: h.lecturerNotLinkedTitle,
                  body: h.lecturerNotLinkedBody,
                  onRetry: () => ref.invalidate(lecturerProfileProvider),
                )
              : ErrorView(
                  error: e,
                  onRetry: () => ref.invalidate(lecturerProfileProvider),
                ),
          data: (p) => RefreshIndicator(
            onRefresh: () => ref.refresh(lecturerProfileProvider.future),
            child: HrBody(child: _Content(profile: p)),
          ),
        ),
      ),
    );
  }
}

class _Content extends ConsumerStatefulWidget {
  const _Content({required this.profile});
  final LecturerProfile profile;

  @override
  ConsumerState<_Content> createState() => _ContentState();
}

class _ContentState extends ConsumerState<_Content> {
  final _formKey = GlobalKey<FormState>();
  late final _phone = TextEditingController(text: widget.profile.phone ?? '');
  bool _busy = false;
  String? _serverError;

  @override
  void dispose() {
    _phone.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _serverError = null;
    });
    final text = _phone.text.trim();
    try {
      await ref.read(hrApiProvider).updatePhone(text.isEmpty ? null : text);
      ref.invalidate(lecturerProfileProvider);
      if (mounted) showAppSnack(context, context.hrL10n.phoneSaved);
    } catch (e) {
      if (!mounted) return;
      if (e is ApiFailure && e.fieldError('phone') != null) {
        setState(() => _serverError = e.fieldError('phone'));
      } else {
        showErrorSnack(context, e);
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final h = context.hrL10n;
    final s = context.l10n;
    final p = widget.profile;
    final canEdit = PermissionGate.allows(ref, 'lecturer_profile.update');
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        AppBanner(message: h.readOnlyNote),
        const SizedBox(height: AppSpacing.md),
        AppSurfaceCard(
          child: Column(
            children: [
              InfoRow(label: h.name, value: p.name),
              InfoRow(label: h.nidn, value: p.nidn ?? '—'),
              InfoRow(label: h.nip, value: p.nip ?? '—'),
              InfoRow(label: h.email, value: p.email ?? '—'),
              InfoRow(label: h.faculty, value: p.facultyName ?? '—'),
              InfoRow(
                label: h.employmentStatus,
                value: h.employmentLabel(p.employmentStatus),
              ),
              InfoRow(
                label: h.functionalRank,
                value: h.rankLabel(p.functionalRank),
              ),
              InfoRow(
                label: h.highestEducation,
                value: p.highestEducation ?? '—',
              ),
              InfoRow(label: h.hiredAt, value: context.formatDate(p.hiredAt)),
              InfoRow(
                label: h.activeStatus,
                value: p.isActive ? h.active : h.inactive,
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        AppSurfaceCard(
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(h.phone, style: Theme.of(context).textTheme.titleSmall),
                const SizedBox(height: AppSpacing.sm),
                if (canEdit) ...[
                  TextFormField(
                    controller: _phone,
                    enabled: !_busy,
                    keyboardType: TextInputType.phone,
                    decoration: InputDecoration(
                      labelText: h.phone,
                      helperText: h.phoneHint,
                      errorText: _serverError,
                    ),
                    validator: Validators.maxLength(s, 30),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  FilledButton(
                    onPressed: _busy ? null : _save,
                    child: Text(s.save),
                  ),
                ] else
                  Text(p.phone ?? '—'),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        MenuTile(
          icon: Icons.badge_outlined,
          title: h.hrProfileLink,
          onTap: () => context.push(RoutePaths.hrProfile),
        ),
      ],
    );
  }
}
