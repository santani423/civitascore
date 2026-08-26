import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/app_alert.dart';
import '../../../../core/widgets/app_button.dart';
import '../../../../core/widgets/app_text_field.dart';
import '../controllers/auth_controller.dart';

class ResetPasswordScreen extends ConsumerStatefulWidget {
  const ResetPasswordScreen({
    super.key,
    required this.token,
    required this.email,
  });

  final String? token;
  final String? email;

  @override
  ConsumerState<ResetPasswordScreen> createState() =>
      _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends ConsumerState<ResetPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _passwordController = TextEditingController();
  final _confirmController = TextEditingController();
  bool _isSubmitting = false;
  bool _success = false;
  ApiFailure? _error;

  @override
  void dispose() {
    _passwordController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _isSubmitting = true;
      _error = null;
    });
    try {
      await ref
          .read(authRepositoryProvider)
          .resetPassword(
            token: widget.token!,
            email: widget.email!,
            password: _passwordController.text,
            passwordConfirmation: _confirmController.text,
          );
      setState(() => _success = true);
    } on ApiFailure catch (e) {
      setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isValidLink =
        widget.token != null &&
        widget.token!.isNotEmpty &&
        widget.email != null &&
        widget.email!.isNotEmpty;

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(AppSpacing.xl2),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 400),
              child: !isValidLink
                  ? const AppAlert(
                      message: 'Tautan tidak valid.',
                      variant: AppAlertVariant.danger,
                    )
                  : Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          'Atur Ulang Kata Sandi',
                          textAlign: TextAlign.center,
                          style: Theme.of(context).textTheme.titleLarge,
                        ),
                        const SizedBox(height: AppSpacing.xl2),
                        if (_success) ...[
                          const AppAlert(
                            message:
                                'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru Anda.',
                            variant: AppAlertVariant.success,
                          ),
                          const SizedBox(height: AppSpacing.lg),
                          AppButton(
                            label: 'Ke Halaman Masuk',
                            expand: true,
                            onPressed: () => context.go(RoutePaths.login),
                          ),
                        ] else ...[
                          if (_error != null) ...[
                            AppAlert(
                              message: _error!.message,
                              variant: AppAlertVariant.danger,
                            ),
                            const SizedBox(height: AppSpacing.lg),
                          ],
                          Form(
                            key: _formKey,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                AppPasswordField(
                                  label: 'Kata Sandi Baru',
                                  controller: _passwordController,
                                  validator: Validators.minLength(
                                    8,
                                    fieldName: 'Kata sandi',
                                  ),
                                ),
                                const SizedBox(height: AppSpacing.lg),
                                AppPasswordField(
                                  label: 'Konfirmasi Kata Sandi',
                                  controller: _confirmController,
                                  validator: (value) {
                                    if (value == null || value.isEmpty) {
                                      return 'Konfirmasi kata sandi wajib diisi';
                                    }
                                    if (value != _passwordController.text) {
                                      return 'Konfirmasi kata sandi tidak sama';
                                    }
                                    return null;
                                  },
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: AppSpacing.lg),
                          AppButton(
                            label: 'Simpan Kata Sandi',
                            expand: true,
                            isLoading: _isSubmitting,
                            onPressed: _submit,
                          ),
                        ],
                        const SizedBox(height: AppSpacing.xl),
                        TextButton(
                          onPressed: () => context.go(RoutePaths.login),
                          child: const Text('Kembali ke halaman masuk'),
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
