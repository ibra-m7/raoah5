import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../data/services/auth_service.dart';
import '../widgets/auth_widgets.dart';

class RegisterScreen extends StatefulWidget {
  static const routeName = '/register';
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen>
    with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _passwordCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();

  bool _obscurePassword = true;
  bool _obscureConfirm = true;
  bool _isLoading = false;
  bool _isGoogleLoading = false;
  bool _agreedToTerms = false;

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
    _nameCtrl.dispose();
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }

  // ── Navigation ────────────────────────────────────────────────────────────
  void _goToHome() {
    Navigator.of(context).pushNamedAndRemoveUntil(
      AppRouter.main,
      (_) => false,
    );
  }

  void _goToLogin() => Navigator.of(context).pop();

  // ── Auth ──────────────────────────────────────────────────────────────────
  Future<void> _register() async {
    if (!_formKey.currentState!.validate()) return;
    if (!_agreedToTerms) {
      _showError('يجب الموافقة على الشروط والأحكام للمتابعة.');
      return;
    }
    setState(() => _isLoading = true);
    try {
      await AuthService.instance.registerWithEmail(
        email: _emailCtrl.text,
        password: _passwordCtrl.text,
        displayName: _nameCtrl.text,
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

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg, textAlign: TextAlign.center),
      backgroundColor: Colors.red.shade700,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.all(16),
    ));
  }

  // ── قوة كلمة المرور ───────────────────────────────────────────────────────
  double _passwordStrength(String p) {
    if (p.isEmpty) return 0;
    double s = 0;
    if (p.length >= 6) s += 0.25;
    if (p.length >= 10) s += 0.25;
    if (p.contains(RegExp(r'[A-Z]'))) s += 0.25;
    if (p.contains(RegExp(r'[0-9!@#$%^&*]'))) s += 0.25;
    return s;
  }

  Color _strengthColor(double s) {
    if (s <= 0.25) return Colors.red;
    if (s <= 0.5) return Colors.orange;
    if (s <= 0.75) return Colors.yellow;
    return const Color(0xFF4CAF50);
  }

  String _strengthLabel(double s) {
    if (s <= 0.25) return 'ضعيفة جداً';
    if (s <= 0.5) return 'ضعيفة';
    if (s <= 0.75) return 'متوسطة';
    return 'قوية';
  }

  bool get _anyLoading => _isLoading || _isGoogleLoading;

  @override
  Widget build(BuildContext context) {
    final strength = _passwordStrength(_passwordCtrl.text);

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
                      const SizedBox(height: 28),

                      // زر الرجوع
                      Align(
                        alignment: AlignmentDirectional.centerStart,
                        child: AuthBackButton(onPressed: _goToLogin),
                      ),

                      const SizedBox(height: 16),

                      // الشعار
                      const Hero(
                        tag: 'app_logo',
                        child: BrandLogoMark(size: 200),
                      ),
                      const SizedBox(height: 12),

                      const Text(
                        'إنشاء حساب جديد',
                        style: TextStyle(
                          fontSize: 26,
                          fontWeight: FontWeight.w900,
                          color: Colors.white,
                        ),
                      ),
                      const SizedBox(height: 5),
                      Text(
                        'انضم إلى روعة الخمسة واستمتع بالتسوق الذكي',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.white.withValues(alpha: 0.7),
                        ),
                        textAlign: TextAlign.center,
                      ),

                      const SizedBox(height: 24),

                      AuthGlassCard(
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              // الاسم
                              AuthGlassField(
                                controller: _nameCtrl,
                                label: 'الاسم الكامل',
                                icon: Icons.person_outline_rounded,
                                textInputAction: TextInputAction.next,
                                validator: (v) {
                                  if (v == null || v.trim().isEmpty) {
                                    return 'أدخل اسمك الكامل';
                                  }
                                  if (v.trim().length < 3) {
                                    return 'الاسم قصير جداً';
                                  }
                                  return null;
                                },
                              ),

                              const SizedBox(height: 14),

                              // البريد
                              AuthGlassField(
                                controller: _emailCtrl,
                                label: 'البريد الإلكتروني',
                                icon: Icons.email_outlined,
                                keyboardType: TextInputType.emailAddress,
                                textInputAction: TextInputAction.next,
                                validator: (v) {
                                  if (v == null || v.trim().isEmpty) {
                                    return 'أدخل البريد الإلكتروني';
                                  }
                                  if (!v.contains('@') || !v.contains('.')) {
                                    return 'صيغة البريد غير صحيحة';
                                  }
                                  return null;
                                },
                              ),

                              const SizedBox(height: 14),

                              // كلمة المرور
                              AuthGlassField(
                                controller: _passwordCtrl,
                                label: 'كلمة المرور',
                                icon: Icons.lock_outline_rounded,
                                obscureText: _obscurePassword,
                                textInputAction: TextInputAction.next,
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
                                    return 'أدخل كلمة المرور';
                                  }
                                  if (v.length < 6) {
                                    return 'كلمة المرور قصيرة — 6 أحرف على الأقل';
                                  }
                                  return null;
                                },
                              ),

                              // مؤشر قوة كلمة المرور
                              if (_passwordCtrl.text.isNotEmpty) ...[
                                const SizedBox(height: 10),
                                _PasswordStrengthBar(
                                  strength: strength,
                                  color: _strengthColor(strength),
                                  label: _strengthLabel(strength),
                                ),
                              ],

                              const SizedBox(height: 14),

                              // تأكيد كلمة المرور
                              AuthGlassField(
                                controller: _confirmCtrl,
                                label: 'تأكيد كلمة المرور',
                                icon: Icons.lock_reset_rounded,
                                obscureText: _obscureConfirm,
                                textInputAction: TextInputAction.done,
                                suffixIcon: IconButton(
                                  onPressed: () => setState(() =>
                                      _obscureConfirm = !_obscureConfirm),
                                  icon: Icon(
                                    _obscureConfirm
                                        ? Icons.visibility_off_outlined
                                        : Icons.visibility_outlined,
                                    color: Colors.white70,
                                    size: 20,
                                  ),
                                ),
                                validator: (v) {
                                  if (v == null || v.isEmpty) {
                                    return 'أدخل تأكيد كلمة المرور';
                                  }
                                  if (v != _passwordCtrl.text) {
                                    return 'كلمتا المرور غير متطابقتين';
                                  }
                                  return null;
                                },
                              ),

                              const SizedBox(height: 18),

                              // الموافقة على الشروط
                              _TermsCheckbox(
                                value: _agreedToTerms,
                                onChanged: (v) =>
                                    setState(() => _agreedToTerms = v ?? false),
                              ),

                              const SizedBox(height: 18),

                              AuthPrimaryButton(
                                label: 'إنشاء الحساب',
                                isLoading: _isLoading,
                                onPressed: _register,
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

                      const SizedBox(height: 20),

                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            'لديك حساب بالفعل؟  ',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.7),
                              fontSize: 14,
                            ),
                          ),
                          GestureDetector(
                            onTap: _goToLogin,
                            child: const Text(
                              'سجّل دخولك',
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
              message: _isGoogleLoading ? 'جاري الاتصال بـ Google...' : 'جاري إنشاء الحساب...',
            ),
        ],
      ),
    );
  }
}

// ── مؤشر قوة كلمة المرور ─────────────────────────────────────────────────────
class _PasswordStrengthBar extends StatelessWidget {
  final double strength;
  final Color color;
  final String label;

  const _PasswordStrengthBar({
    required this.strength,
    required this.color,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(4),
          child: LinearProgressIndicator(
            value: strength,
            backgroundColor: Colors.white.withValues(alpha: 0.1),
            valueColor: AlwaysStoppedAnimation(color),
            minHeight: 5,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'قوة كلمة المرور: $label',
          style: TextStyle(fontSize: 11, color: color),
        ),
      ],
    );
  }
}

// ── مربع الموافقة على الشروط ─────────────────────────────────────────────────
class _TermsCheckbox extends StatelessWidget {
  final bool value;
  final ValueChanged<bool?> onChanged;

  const _TermsCheckbox({required this.value, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => onChanged(!value),
      child: Row(
        children: [
          SizedBox(
            width: 24,
            height: 24,
            child: Checkbox(
              value: value,
              onChanged: onChanged,
              fillColor: WidgetStateProperty.resolveWith(
                (states) => states.contains(WidgetState.selected)
                    ? const Color(0xFF4CAF50)
                    : Colors.transparent,
              ),
              side: BorderSide(
                color: Colors.white.withValues(alpha: 0.4),
                width: 1.5,
              ),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(5),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: RichText(
              text: TextSpan(
                style: TextStyle(
                  color: Colors.white.withValues(alpha: 0.75),
                  fontSize: 13,
                  height: 1.5,
                ),
                children: const [
                  TextSpan(text: 'أوافق على '),
                  TextSpan(
                    text: 'شروط الاستخدام',
                    style: TextStyle(
                      color: Color(0xFFA5D6A7),
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  TextSpan(text: ' و'),
                  TextSpan(
                    text: 'سياسة الخصوصية',
                    style: TextStyle(
                      color: Color(0xFFA5D6A7),
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
