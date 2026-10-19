import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/formatters.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/state_views.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../data/academic_records_api.dart';
import '../../domain/academic_models.dart';
import '../../l10n/academic_records_strings.dart';
import '../providers.dart';
import '../widgets/academic_summary_card.dart';
import '../widgets/common.dart';

class StudentProfileScreen extends ConsumerWidget {
  const StudentProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final a = context.acadL10n;
    final value = ref.watch(studentProfileProvider);
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final canEdit = value.valueOrNull?.canEdit('phone') ?? false;
    return Scaffold(
      appBar: AppBar(
        title: Text(a.profileTitle),
        actions: [
          if (canEdit)
            IconButton(
              tooltip: a.editContact,
              icon: const Icon(Icons.edit_outlined),
              onPressed: () => _edit(context, ref, value.value!),
            ),
        ],
      ),
      body: AsyncValueView<StudentProfile>(
        value: value,
        onRetry: () => ref.invalidate(studentProfileProvider),
        data: (p) {
          String orDash(String? v) => (v == null || v.isEmpty) ? '—' : v;
          return RefreshList(
            onRefresh: () async => ref.refresh(studentProfileProvider.future),
            children: [
              if (p.hasPendingChange)
                Padding(
                  padding: const EdgeInsets.only(bottom: AppSpacing.md),
                  child: AppBanner(
                    message: a.pendingChange,
                    tone: StatusTone.info,
                  ),
                ),
              AppSurfaceCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(a.personal, style: text.titleSmall),
                    const SizedBox(height: AppSpacing.sm),
                    InfoRow(label: a.name, value: p.name),
                    InfoRow(label: a.nim, value: p.nim),
                    InfoRow(label: a.birthPlace, value: orDash(p.birthPlace)),
                    InfoRow(
                      label: a.birthDate,
                      value: context.formatDate(p.birthDate),
                    ),
                    InfoRow(
                      label: a.gender,
                      value: switch (p.gender) {
                        'male' => a.male,
                        'female' => a.female,
                        _ => orDash(p.genderLabel),
                      },
                    ),
                    InfoRow(label: a.email, value: orDash(p.email)),
                    Padding(
                      padding: const EdgeInsets.only(top: AppSpacing.sm),
                      child: Text(
                        a.readOnlyNote,
                        style: text.bodySmall?.copyWith(
                          color: scheme.onSurfaceVariant,
                        ),
                      ),
                    ),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: TextButton(
                        onPressed: () =>
                            context.push(RoutePaths.studentRequests),
                        child: Text(a.requestsLink),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: AppSpacing.md),
              AppSurfaceCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(a.contact, style: text.titleSmall),
                    const SizedBox(height: AppSpacing.sm),
                    InfoRow(label: a.phone, value: p.phone ?? a.notSet),
                    InfoRow(label: a.address, value: p.address ?? a.notSet),
                  ],
                ),
              ),
              SectionHeader(
                title: a.academicData,
                padding: const EdgeInsets.only(top: AppSpacing.lg),
              ),
              AcademicSummaryCard(summary: p.academic, detailed: true),
            ],
          );
        },
      ),
    );
  }

  Future<void> _edit(
    BuildContext context,
    WidgetRef ref,
    StudentProfile p,
  ) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (_) => _EditContactSheet(profile: p),
    );
    if (saved == true) ref.invalidate(studentProfileProvider);
  }
}

class _EditContactSheet extends ConsumerStatefulWidget {
  const _EditContactSheet({required this.profile});
  final StudentProfile profile;
  @override
  ConsumerState<_EditContactSheet> createState() => _EditContactSheetState();
}

class _EditContactSheetState extends ConsumerState<_EditContactSheet> {
  final _form = GlobalKey<FormState>();
  late final _phone = TextEditingController(text: widget.profile.phone ?? '');
  late final _address = TextEditingController(
    text: widget.profile.address ?? '',
  );
  bool _busy = false;
  ApiFailure? _failure;

  @override
  void dispose() {
    _phone.dispose();
    _address.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _failure = null;
    });
    final a = context.acadL10n;
    try {
      await ref
          .read(academicRecordsApiProvider)
          .updateProfile(
            phone: _phone.text.trim().isEmpty ? null : _phone.text.trim(),
            address: _address.text.trim().isEmpty ? null : _address.text.trim(),
          );
      if (!mounted) return;
      showAppSnack(context, a.profileSaved);
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _failure = e is ApiFailure ? e : null;
      });
      if (e is! ApiFailure || e.fieldErrors.isEmpty) {
        showAppSnack(context, context.l10n.errorFor(e), isError: true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final a = context.acadL10n;
    final s = context.l10n;
    return Padding(
      padding: EdgeInsets.only(
        left: AppSpacing.lg,
        right: AppSpacing.lg,
        top: AppSpacing.lg,
        bottom: MediaQuery.of(context).viewInsets.bottom + AppSpacing.lg,
      ),
      child: Form(
        key: _form,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                a.editContact,
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: AppSpacing.lg),
              TextFormField(
                controller: _phone,
                keyboardType: TextInputType.phone,
                decoration: InputDecoration(
                  labelText: a.phone,
                  errorText: _failure?.fieldError('phone'),
                ),
                validator: (v) {
                  final t = (v ?? '').trim();
                  if (t.isEmpty) return null;
                  return RegExp(r'^\+?[0-9\s\-()]{6,30}$').hasMatch(t)
                      ? null
                      : a.phoneInvalid;
                },
              ),
              const SizedBox(height: AppSpacing.md),
              TextFormField(
                controller: _address,
                maxLines: 3,
                decoration: InputDecoration(
                  labelText: a.address,
                  errorText: _failure?.fieldError('address'),
                ),
                validator: (v) =>
                    (v ?? '').length > 500 ? a.addressTooLong : null,
              ),
              const SizedBox(height: AppSpacing.lg),
              FilledButton(
                onPressed: _busy ? null : _save,
                child: _busy
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : Text(s.save),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
