import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../core/l10n/core_strings.dart';
import '../../../../core/network/api_failure.dart';
import '../../../../core/router/route_paths.dart';
import '../../../../core/storage/local_prefs.dart';
import '../../../../core/storage/storage_keys.dart';
import '../../../../core/theme/app_dimensions.dart';
import '../../../../core/utils/validators.dart';
import '../../../../core/widgets/language_menu_button.dart';
import '../../../../core/widgets/ui_kit.dart';
import '../../l10n/auth_strings.dart';
import '../controllers/auth_controller.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _identifier = TextEditingController();
  final _password = TextEditingController();
  late bool _byNim;
  bool _obscure = true;
  bool _submitting = false;
  ApiFailure? _failure;

  @override
  void initState() {
    super.initState();
    _byNim =
        ref
            .read(sharedPreferencesProvider)
            .getString(StorageKeys.lastLoginIdentifier) !=
        'email';
  }

  @override
  void dispose() {
    _identifier.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _failure = null);
    if (!_formKey.currentState!.validate()) return;
    setState(() => _submitting = true);
    try {
      await ref
          .read(authControllerProvider.notifier)
          .login(
            identifier: _identifier.text,
            byNim: _byNim,
            password: _password.text,
          );
      await ref
          .read(sharedPreferencesProvider)
          .setString(StorageKeys.lastLoginIdentifier, _byNim ? 'nim' : 'email');
    } on ApiFailure catch (f) {
      if (mounted) setState(() => _failure = f);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = context.l10n;
    final a = context.authL10n;
    final text = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final fieldError =
        _failure?.fieldError(_byNim ? 'nim' : 'email') ??
        _failure?.fieldError('password');
    final generalError = _failure == null
        ? null
        : (fieldError ?? s.errorFor(_failure!));

    return Scaffold(
      appBar: AppBar(actions: const [LanguageMenuButton()]),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(AppSpacing.xl2),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 440),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Center(
                      child: Container(
                        width: 72,
                        height: 72,
                        decoration: BoxDecoration(
                          color: scheme.primary,
                          borderRadius: BorderRadius.circular(AppRadius.xl),
                        ),
                        child: Icon(
                          Icons.school_rounded,
                          size: 40,
                          color: scheme.onPrimary,
                        ),
                      ),
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    Text(
                      s.appName,
                      style: text.headlineMedium,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      a.loginSubtitle,
                      style: text.bodyMedium,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: AppSpacing.xl2),
                    SegmentedButton<bool>(
                      segments: [
                        ButtonSegment(
                          value: true,
                          label: Text(a.asStudent),
                          icon: const Icon(Icons.person_outline),
                        ),
                        ButtonSegment(
                          value: false,
                          label: Text(a.asLecturer),
                          icon: const Icon(Icons.co_present_outlined),
                        ),
                      ],
                      selected: {_byNim},
                      onSelectionChanged: (v) => setState(() {
                        _byNim = v.first;
                        _failure = null;
                      }),
                    ),
                    const SizedBox(height: AppSpacing.lg),
                    TextFormField(
                      key: const Key('login-identifier'),
                      controller: _identifier,
                      keyboardType: _byNim
                          ? TextInputType.text
                          : TextInputType.emailAddress,
                      textInputAction: TextInputAction.next,
                      autofillHints: [
                        _byNim ? AutofillHints.username : AutofillHints.email,
                      ],
                      decoration: InputDecoration(
                        labelText: _byNim ? a.nim : a.email,
                        hintText: _byNim ? a.nimHint : a.emailHint,
                        prefixIcon: Icon(
                          _byNim ? Icons.badge_outlined : Icons.alternate_email,
                        ),
                      ),
                      validator: Validators.compose([
                        Validators.required(s),
                        if (!_byNim) Validators.email(s),
                        Validators.maxLength(s, _byNim ? 50 : 191),
                      ]),
                    ),
                    const SizedBox(height: AppSpacing.md),
                    TextFormField(
                      key: const Key('login-password'),
                      controller: _password,
                      obscureText: _obscure,
                      textInputAction: TextInputAction.done,
                      autofillHints: const [AutofillHints.password],
                      onFieldSubmitted: (_) => _submit(),
                      decoration: InputDecoration(
                        labelText: a.password,
                        prefixIcon: const Icon(Icons.lock_outline),
                        suffixIcon: IconButton(
                          tooltip: _obscure ? a.showPassword : a.hidePassword,
                          icon: Icon(
                            _obscure
                                ? Icons.visibility_outlined
                                : Icons.visibility_off_outlined,
                          ),
                          onPressed: () => setState(() => _obscure = !_obscure),
                        ),
                      ),
                      validator: Validators.required(s),
                    ),
                    Align(
                      alignment: Alignment.centerRight,
                      child: TextButton(
                        onPressed: () =>
                            context.push(RoutePaths.forgotPassword),
                        child: Text(a.forgotPassword),
                      ),
                    ),
                    if (generalError != null) ...[
                      AppBanner(message: generalError, tone: StatusTone.danger),
                      const SizedBox(height: AppSpacing.md),
                    ],
                    FilledButton(
                      key: const Key('login-submit'),
                      onPressed: _submitting ? null : _submit,
                      child: _submitting
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(
                                strokeWidth: 2.4,
                              ),
                            )
                          : Text(a.signIn),
                    ),
                    if (_byNim) ...[
                      const SizedBox(height: AppSpacing.lg),
                      Text(
                        a.studentPasswordHint,
                        style: text.bodySmall,
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
