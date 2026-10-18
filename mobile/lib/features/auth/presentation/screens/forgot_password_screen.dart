import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../l10n/auth_strings.dart';
import '../controllers/auth_controller.dart';

class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() =>
      _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  bool _busy = false;
  bool _sent = false;
  Object? _error;

  @override
  void dispose() {
    _email.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(authApiProvider).forgotPassword(_email.text);
      if (mounted) setState(() => _sent = true);
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
      appBar: AppBar(title: Text(a.forgotTitle)),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppSpacing.xl2),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  a.forgotBody,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: AppSpacing.lg),
                TextFormField(
                  controller: _email,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    labelText: a.email,
                    prefixIcon: const Icon(Icons.alternate_email),
                  ),
                  validator: Validators.compose([
                    Validators.required(s),
                    Validators.email(s),
                  ]),
                ),
                const SizedBox(height: AppSpacing.lg),
                if (_sent) ...[
                  AppBanner(message: a.linkSent, tone: StatusTone.success),
                  const SizedBox(height: AppSpacing.md),
                ],
                if (_error != null) ...[
                  AppBanner(
                    message: s.errorFor(_error!),
                    tone: StatusTone.danger,
                  ),
                  const SizedBox(height: AppSpacing.md),
                ],
                FilledButton(
                  onPressed: _busy ? null : _submit,
                  child: _busy
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(strokeWidth: 2.4),
                        )
                      : Text(a.sendLink),
                ),
                const SizedBox(height: AppSpacing.sm),
                TextButton(
                  onPressed: () => context.push(RoutePaths.resetPassword),
                  child: Text(a.resetTitle),
                ),
                const SizedBox(height: AppSpacing.lg),
                AppBanner(message: a.forgotNimNote, tone: StatusTone.info),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
