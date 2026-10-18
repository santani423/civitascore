import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_feedback.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../l10n/auth_strings.dart';
import '../controllers/auth_controller.dart';

/// Reset form. The backend emails a web link (`/reset-password?token&email`);
/// the same parameters are accepted here (deep link) or typed manually.
class ResetPasswordScreen extends ConsumerStatefulWidget {
  const ResetPasswordScreen({super.key, this.token, this.email});
  final String? token;
  final String? email;

  @override
  ConsumerState<ResetPasswordScreen> createState() =>
      _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends ConsumerState<ResetPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  late final _token = TextEditingController(text: widget.token);
  late final _email = TextEditingController(text: widget.email);
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _busy = false;
  ApiFailure? _error;

  @override
  void dispose() {
    for (final c in [_token, _email, _password, _confirm]) {
      c.dispose();
    }
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
          .resetPassword(
            token: _token.text.trim(),
            email: _email.text,
            password: _password.text,
            confirmation: _confirm.text,
          );
      if (!mounted) return;
      showAppSnack(context, context.authL10n.resetDone);
      context.go(RoutePaths.login);
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
    return Scaffold(
      appBar: AppBar(title: Text(a.resetTitle)),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppSpacing.xl2),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextFormField(
                  controller: _token,
                  decoration: InputDecoration(
                    labelText: a.resetToken,
                    helperText: a.resetTokenHelp,
                  ),
                  validator: Validators.required(s),
                ),
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  controller: _email,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(labelText: a.email),
                  validator: Validators.compose([
                    Validators.required(s),
                    Validators.email(s),
                  ]),
                ),
                const SizedBox(height: AppSpacing.md),
                TextFormField(
                  controller: _password,
                  obscureText: true,
                  decoration: InputDecoration(
                    labelText: a.newPassword,
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
                if (_error != null) ...[
                  AppBanner(
                    message: s.errorFor(_error!),
                    tone: StatusTone.danger,
                  ),
                  const SizedBox(height: AppSpacing.md),
                ],
                FilledButton(
                  onPressed: _busy ? null : _submit,
                  child: Text(a.resetTitle),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
