import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../data/services/auth_service.dart';
import '../widgets/auth_widgets.dart';

class LoginScreen extends StatefulWidget {
  static const routeName = '/login';
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen>
    with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _emailCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();

  bool _obscurePassword = true;
  bool _isLoading = false;
  bool _isGoogleLoading = false;

  late final AnimationController _animCtrl;
  late final Animation<double> _fadeAnim;
  late final Animation<Offset> _slideAnim;

  @override
  void initState() {
    super.initState();
    _animCtrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 780),
    );
    _fadeAnim = CurvedAnimation(parent: _animCtrl, curve: Curves.easeOut);
    _slideAnim = Tween<Offset>(
      begin: const Offset(0, 0.05),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _animCtrl, curve: Curves.easeOutCubic));
    _animCtrl.forward();
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

  void _goToHome() {
    Navigator.of(context).pushNamedAndRemoveUntil(
      AppRouter.main,
      (_) => false,
    );
  }

  void _goToRegister() {
    Navigator.of(context).pushNamed(AppRouter.register);
  }

  Future<void> _signIn() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);
    try {
      await AuthService.instance.signInWithEmail(
        email: _emailCtrl.text,
        password: _passwordCtrl.text,
      );
      if (mounted) _goToHome();
    } on FirebaseAuthException catch (e) {
      if (mounted) _showError(AuthService.arabicError(e));
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _signInWithGoogle() async {
    setState(() => _isGoogleLoading = true);
    try {
      final cred = await AuthService.instance.signInWithGoogle();
      if (cred != null && mounted) _goToHome();
    } on FirebaseAuthException catch (e) {
      if (mounted) _showError(AuthService.arabicError(e));
    } catch (_) {}
    finally {
      if (mounted) setState(() => _isGoogleLoading = false);
    }
  }

  Future<void> _forgotPassword() async {
    if (_emailCtrl.text.trim().isEmpty) {
      _showError('أدخل بريدك الإلكتروني أولاً لإعادة تعيين كلمة المرور.');
      return;
    }
    try {
      await AuthService.instance.sendPasswordResetEmail(_emailCtrl.text);
      if (mounted) _showSuccess(AppStrings.loginPasswordResetSent);
    } on FirebaseAuthException catch (e) {
      if (mounted) _showError(AuthService.arabicError(e));
    }
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(
        msg,
        textAlign: TextAlign.center,
        style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
      ),
      backgroundColor: Colors.red.shade700,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      margin: const EdgeInsets.all(16),
    ));
  }

  void _showSuccess(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(
        msg,
        textAlign: TextAlign.center,
        style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600),
      ),
      backgroundColor: AppTheme.primaryDark,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      margin: const EdgeInsets.all(16),
    ));
  }

  bool get _anyLoading => _isLoading || _isGoogleLoading;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          const AuthGradientBackground(),
          SafeArea(
            child: FadeTransition(
              opacity: _fadeAnim,
              child: SlideTransition(
                position: _slideAnim,
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(22, 12, 22, 28),
                  child: Column(
                    children: [
                      const Hero(
                        tag: 'app_logo',
                        child: BrandLogoMark(size: 124),
                      ),
                      const SizedBox(height: 16),
                      AuthScreenHeader(
                        title: AppStrings.loginTitle,
                        subtitle: AppStrings.loginSubtitle,
                      ),
                      const SizedBox(height: 22),
                      AuthGlassCard(
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              AuthGlassField(
                                controller: _emailCtrl,
                                label: AppStrings.fieldEmail,
                                icon: Icons.email_outlined,
                                keyboardType: TextInputType.emailAddress,
                                textInputAction: TextInputAction.next,
                                validator: (v) {
                                  if (v == null || v.trim().isEmpty) {
                                    return AppStrings.fieldRequired;
                                  }
                                  if (!v.contains('@') || !v.contains('.')) {
                                    return AppStrings.fieldEmailInvalid;
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 14),
                              AuthGlassField(
                                controller: _passwordCtrl,
                                label: AppStrings.fieldPassword,
                                icon: Icons.lock_outline_rounded,
                                obscureText: _obscurePassword,
                                textInputAction: TextInputAction.done,
                                suffixIcon: IconButton(
                                  onPressed: () => setState(
                                    () => _obscurePassword = !_obscurePassword,
                                  ),
                                  icon: Icon(
                                    _obscurePassword
                                        ? Icons.visibility_off_outlined
                                        : Icons.visibility_outlined,
                                    color: AppTheme.mutedText,
                                    size: 18,
                                  ),
                                ),
                                validator: (v) {
                                  if (v == null || v.isEmpty) {
                                    return AppStrings.fieldRequired;
                                  }
                                  if (v.length < 6) {
                                    return AppStrings.fieldPasswordShort;
                                  }
                                  return null;
                                },
                              ),
                              Align(
                                alignment: AlignmentDirectional.centerEnd,
                                child: TextButton(
                                  onPressed: _forgotPassword,
                                  style: TextButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 2,
                                      vertical: 4,
                                    ),
                                    minimumSize: Size.zero,
                                    tapTargetSize:
                                        MaterialTapTargetSize.shrinkWrap,
                                  ),
                                  child: const Text(
                                    AppStrings.loginForgotPassword,
                                    style: TextStyle(
                                      color: AppTheme.primaryDark,
                                      fontSize: 11.5,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(height: 6),
                              AuthPrimaryButton(
                                label: AppStrings.loginButton,
                                isLoading: _isLoading,
                                onPressed: _signIn,
                              ),
                              const SizedBox(height: 16),
                              const AuthOrDivider(),
                              const SizedBox(height: 16),
                              AuthGoogleButton(
                                isLoading: _isGoogleLoading,
                                onPressed: _signInWithGoogle,
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            '${AppStrings.loginNoAccount} ',
                            style: TextStyle(
                              color: AppTheme.mutedText.withValues(alpha: 0.95),
                              fontSize: 12,
                            ),
                          ),
                          AuthTextLink(
                            label: AppStrings.loginCreateAccount,
                            onTap: _goToRegister,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
          if (_anyLoading)
            AuthLoadingOverlay(
              message: _isGoogleLoading
                  ? 'جاري الاتصال بـ Google...'
                  : 'جاري تسجيل الدخول...',
            ),
        ],
      ),
    );
  }
}
