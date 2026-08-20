import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import '../../../../core/constants/app_strings.dart';
import '../../../../core/router/app_router.dart';
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
      duration: const Duration(milliseconds: 900),
    );
    _fadeAnim = CurvedAnimation(parent: _animCtrl, curve: Curves.easeOut);
    _slideAnim = Tween<Offset>(
      begin: const Offset(0, 0.08),
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

  // ── Navigation ────────────────────────────────────────────────────────────
  void _goToHome() {
    Navigator.of(context).pushNamedAndRemoveUntil(
      AppRouter.main,
      (_) => false,
    );
  }

  void _goToRegister() {
    Navigator.of(context).pushNamed(AppRouter.register);
  }

  // ── Auth ──────────────────────────────────────────────────────────────────
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
    } catch (_) {
      // المستخدم أغلق نافذة Google
    } finally {
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
      if (mounted) {
        _showSuccess(AppStrings.loginPasswordResetSent);
      }
    } on FirebaseAuthException catch (e) {
      if (mounted) _showError(AuthService.arabicError(e));
    }
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg, textAlign: TextAlign.center),
      backgroundColor: Colors.red.shade700,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.all(16),
    ));
  }

  void _showSuccess(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg, textAlign: TextAlign.center),
      backgroundColor: const Color(0xFF2E7D32),
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child: Column(
                    children: [
                      const SizedBox(height: 44),

                      // الشعار
                      Hero(
                        tag: 'app_logo',
                        child: BrandLogoMark(size: 220),
                      ),
                      const SizedBox(height: 18),

                      const Text(
                        AppStrings.loginTitle,
                        style: TextStyle(
                          fontSize: 30,
                          fontWeight: FontWeight.w900,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        AppStrings.loginSubtitle,
                        style: TextStyle(
                          fontSize: 15,
                          color: Colors.white.withValues(alpha: 0.75),
                        ),
                      ),

                      const SizedBox(height: 36),

                      // بطاقة الـ Glass
                      AuthGlassCard(
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              // البريد
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

                              const SizedBox(height: 16),

                              // كلمة المرور
                              AuthGlassField(
                                controller: _passwordCtrl,
                                label: AppStrings.fieldPassword,
                                icon: Icons.lock_outline_rounded,
                                obscureText: _obscurePassword,
                                textInputAction: TextInputAction.done,
                                suffixIcon: IconButton(
                                  onPressed: () => setState(() =>
                                      _obscurePassword = !_obscurePassword),
                                  icon: Icon(
                                    _obscurePassword
                                        ? Icons.visibility_off_outlined
                                        : Icons.visibility_outlined,
                                    color: Colors.white70,
                                    size: 20,
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

                              // نسيت كلمة المرور
                              Align(
                                alignment: AlignmentDirectional.centerEnd,
                                child: TextButton(
                                  onPressed: _forgotPassword,
                                  style: TextButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 4,
                                      vertical: 8,
                                    ),
                                  ),
                                  child: const Text(
                                    AppStrings.loginForgotPassword,
                                    style: TextStyle(
                                      color: Color(0xFFA5D6A7),
                                      fontSize: 13,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                              ),

                              const SizedBox(height: 8),

                              AuthPrimaryButton(
                                label: AppStrings.loginButton,
                                isLoading: _isLoading,
                                onPressed: _signIn,
                              ),

                              const SizedBox(height: 20),
                              const AuthOrDivider(),
                              const SizedBox(height: 20),

                              AuthGoogleButton(
                                isLoading: _isGoogleLoading,
                                onPressed: _signInWithGoogle,
                              ),
                            ],
                          ),
                        ),
                      ),

                      const SizedBox(height: 28),

                      // رابط التسجيل
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            '${AppStrings.loginNoAccount}  ',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.7),
                              fontSize: 14,
                            ),
                          ),
                          GestureDetector(
                            onTap: _goToRegister,
                            child: const Text(
                              AppStrings.loginCreateAccount,
                              style: TextStyle(
                                color: Color(0xFFA5D6A7),
                                fontSize: 14,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ),
                        ],
                      ),

                      const SizedBox(height: 32),
                    ],
                  ),
                ),
              ),
            ),
          ),

          // مؤشر التحميل — يغلق الشاشة بالكامل أثناء العملية
          if (_anyLoading)
            AuthLoadingOverlay(
              message: _isGoogleLoading ? 'جاري الاتصال بـ Google...' : 'جاري تسجيل الدخول...',
            ),
        ],
      ),
    );
  }
}
