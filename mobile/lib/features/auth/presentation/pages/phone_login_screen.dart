import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/error/exceptions.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/router/app_router.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../data/services/phone_auth_api.dart';
import '../auth_flow.dart';
import '../widgets/auth_widgets.dart';
import 'otp_verify_screen.dart';

class PhoneLoginScreen extends StatefulWidget {
  static const routeName = '/phone-login';

  const PhoneLoginScreen({super.key});

  @override
  State<PhoneLoginScreen> createState() => _PhoneLoginScreenState();
}

class _PhoneLoginScreenState extends State<PhoneLoginScreen>
    with SingleTickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();
  final _phoneCtrl = TextEditingController();
  bool _isLoading = false;

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
    _phoneCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);
    try {
      final phone = _phoneCtrl.text.trim();
      final result = await PhoneAuthApi.instance.requestOtp(phone);
      if (!mounted) return;
      if (!result.otpRequired && result.user != null) {
        await AuthFlow.afterLogin(context, result.user!);
        return;
      }
      Navigator.of(context).pushNamed(
        AppRouter.otpVerify,
        arguments: OtpVerifyArgs(
          phone: result.phone,
          fromPhone: result.fromPhone ?? AppStrings.companyWhatsapp,
          resendIn: result.resendIn,
          debugCode: result.debugCode,
        ),
      );
    } on ApiException catch (e) {
      if (mounted) _showError(e.message);
    } on NetworkException catch (e) {
      if (mounted) _showError(e.message);
    } on ServerException catch (e) {
      if (mounted) _showError(e.message);
    } catch (_) {
      if (mounted) _showError(AppStrings.errorUnknown);
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  bool _isAllowedLoginPhone(String raw) {
    var digits = raw.replaceAll(RegExp(r'\D'), '');
    if (digits.startsWith('00')) {
      digits = digits.substring(2);
    }
    if (digits.startsWith('0')) {
      digits = digits.substring(1);
    }
    if ((digits.startsWith('967') || digits.startsWith('966')) &&
        digits.length >= 12) {
      digits = digits.substring(3);
    }
    if (RegExp(r'^5\d{8}$').hasMatch(digits)) {
      return true;
    }
    return digits == '778396448' || digits == '777234341';
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

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (didPop) return;
        AuthFlow.leaveAuth(context);
      },
      child: Scaffold(
        body: Stack(
          fit: StackFit.expand,
          children: [
            const AuthGradientBackground(),
            SafeArea(
              child: FadeTransition(
                opacity: _fadeAnim,
                child: SlideTransition(
                  position: _slideAnim,
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      return SingleChildScrollView(
                        padding: const EdgeInsets.fromLTRB(22, 8, 22, 24),
                        child: ConstrainedBox(
                          constraints: BoxConstraints(
                            minHeight: constraints.maxHeight - 32,
                          ),
                          child: Column(
                            children: [
                              Align(
                                alignment: AlignmentDirectional.centerStart,
                                child: AuthCloseButton(
                                  onPressed: () => AuthFlow.leaveAuth(context),
                                  tooltip: AppStrings.guestBrowse,
                                ),
                              ),
                              const SizedBox(height: 12),
                              const Hero(
                                tag: 'app_logo',
                                child: BrandLogoMark(size: 132),
                              ),
                              const SizedBox(height: 18),
                              AuthScreenHeader(
                                title: AppStrings.phoneLoginTitle,
                                subtitle: AppStrings.appTaglineShort,
                              ),
                              const SizedBox(height: 22),
                              AuthGlassCard(
                                child: Form(
                                  key: _formKey,
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.stretch,
                                    children: [
                                      AuthGlassField(
                                        controller: _phoneCtrl,
                                        label: AppStrings.fieldPhone,
                                        hintText: AppStrings.fieldPhoneHint,
                                        icon: Icons.phone_iphone_rounded,
                                        keyboardType: TextInputType.phone,
                                        textInputAction: TextInputAction.done,
                                        textDirection: TextDirection.ltr,
                                        maxLength: 12,
                                        inputFormatters: [
                                          FilteringTextInputFormatter.digitsOnly,
                                        ],
                                        validator: (v) {
                                          final value = v?.trim() ?? '';
                                          if (value.isEmpty) {
                                            return AppStrings.fieldRequired;
                                          }
                                          if (!_isAllowedLoginPhone(value)) {
                                            return AppStrings.fieldPhoneInvalid;
                                          }
                                          return null;
                                        },
                                      ),
                                      const SizedBox(height: 10),
                                      Text(
                                        'سنرسل لك رمز تحقق عبر واتساب',
                                        textAlign: TextAlign.center,
                                        style: TextStyle(
                                          fontSize: 11,
                                          height: 1.35,
                                          color: AppTheme.mutedText
                                              .withValues(alpha: 0.95),
                                        ),
                                      ),
                                      const SizedBox(height: 16),
                                      AuthPrimaryButton(
                                        label: AppStrings.phoneLoginButton,
                                        isLoading: false,
                                        onPressed: () {
                                          if (_isLoading) return;
                                          _submit();
                                        },
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                              const SizedBox(height: 14),
                              const AuthTrustRow(),
                              const SizedBox(height: 18),
                              AuthTextLink(
                                label: AppStrings.guestBrowse,
                                onTap: () => AuthFlow.leaveAuth(context),
                                underline: true,
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ),
            ),
            if (_isLoading)
              const AuthLoadingOverlay(message: 'جاري التحقق...'),
          ],
        ),
      ),
    );
  }
}
