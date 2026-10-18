import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../l10n/auth_strings.dart';
import '../controllers/auth_controller.dart';

/// `POST /change-password`. When the account has `must_change_password`
/// the router forces this screen (no back) until the change succeeds.
class ChangePasswordScreen extends ConsumerStatefulWidget {
  const ChangePasswordScreen({super.key});

  @override
  ConsumerState<ChangePasswordScreen> createState() =>
      _ChangePasswordScreenState();
}

class _ChangePasswordScreenState extends ConsumerState<ChangePasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _current = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;
  ApiFailure? _error;

  @override
  void dispose() {
    _current.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref
          .read(authApiProvider)
          .changePassword(
            current: _current.text,
            password: _password.text,
            confirmation: _confirm.text,
          );
      ref.read(authControllerProvider.notifier).markPasswordChanged();
      if (!mounted) return;
      showAppSnack(context, context.authL10n.changeDone);
      if (context.canPop()) context.pop();
    } on ApiFailure catch (e) {
      if (mounted) setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.l10n;
    final a = context.authL10n;
    final forced = ref.watch(currentUserProvider)?.mustChangePassword ?? false;
    return PopScope(
      canPop: !forced,
      child: Scaffold(
        appBar: AppBar(
          title: Text(a.changeTitle),
          automaticallyImplyLeading: !forced,
          actions: [
            if (forced)
              TextButton(
                onPressed: () =>
                    ref.read(authControllerProvider.notifier).logout(),
                child: Text(s.logout),
              ),
          ],
        ),
        body: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(AppSpacing.xl2),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (forced) ...[
                    AppBanner(
                      message: a.forcedChangeBody,
                      tone: StatusTone.warning,
                    ),
                    const SizedBox(height: AppSpacing.lg),
                  ],
                  TextFormField(
                    controller: _current,
                    obscureText: true,
                    decoration: InputDecoration(
                      labelText: a.currentPassword,
                      errorText: _error?.fieldError('current_password'),
                    ),
                    validator: Validators.required(s),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  TextFormField(
                    controller: _password,
                    obscureText: true,
                    decoration: InputDecoration(
                      labelText: a.newPassword,
                      helperText: a.passwordRule,
                      errorText: _error?.fieldError('password'),
                    ),
                    validator: Validators.compose([
                      Validators.required(s),
                      Validators.minLength(s, 8),
                    ]),
                  ),
                  const SizedBox(height: AppSpacing.md),
                  TextFormField(
                    controller: _confirm,
                    obscureText: true,
                    decoration: InputDecoration(labelText: a.confirmPassword),
                    validator: Validators.compose([
                      Validators.required(s),
                      Validators.matches(s, () => _password.text),
                    ]),
                  ),
                  const SizedBox(height: AppSpacing.lg),
                  if (_error != null && _error!.fieldErrors.isEmpty) ...[
                    AppBanner(
                      message: s.errorFor(_error!),
                      tone: StatusTone.danger,
                    ),
                    const SizedBox(height: AppSpacing.md),
                  ],
                  FilledButton(
                    onPressed: _busy ? null : _submit,
                    child: Text(s.save),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
