import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/error/exceptions.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../data/services/phone_auth_api.dart';
import '../auth_flow.dart';
import '../widgets/auth_widgets.dart';

class OtpVerifyArgs {
  final String phone;
  final String fromPhone;
  final int resendIn;
  final String? debugCode;

  const OtpVerifyArgs({
    required this.phone,
    this.fromPhone = AppStrings.companyWhatsapp,
    this.resendIn = 60,
    this.debugCode,
  });
}

class OtpVerifyScreen extends StatefulWidget {
  static const routeName = '/otp-verify';

  final OtpVerifyArgs args;

  const OtpVerifyScreen({super.key, required this.args});

  @override
  State<OtpVerifyScreen> createState() => _OtpVerifyScreenState();
}

class _OtpVerifyScreenState extends State<OtpVerifyScreen> {
  final _codeCtrl = TextEditingController();
  final _focus = FocusNode();
  bool _isLoading = false;
  bool _isResending = false;
  late int _secondsLeft;
  Timer? _timer;
  String? _debugCode;

  @override
  void initState() {
    super.initState();
    _debugCode = widget.args.debugCode;
    _secondsLeft = widget.args.resendIn;
    _startTimer();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _focus.requestFocus();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _codeCtrl.dispose();
    _focus.dispose();
    super.dispose();
  }

  void _startTimer() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_secondsLeft <= 1) {
        timer.cancel();
        setState(() => _secondsLeft = 0);
      } else {
        setState(() => _secondsLeft -= 1);
      }
    });
  }

  Future<void> _verify([String? autoCode]) async {
    final code = autoCode ?? _codeCtrl.text.trim();
    if (code.length != 6 || _isLoading) return;
    setState(() => _isLoading = true);
    try {
      final user = await PhoneAuthApi.instance.verifyOtp(
        phone: widget.args.phone,
        code: code,
      );
      if (!mounted) return;
      await AuthFlow.afterLogin(context, user);
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

  Future<void> _resend() async {
    if (_secondsLeft > 0 || _isResending) return;
    setState(() => _isResending = true);
    try {
      final result = await PhoneAuthApi.instance.requestOtp(widget.args.phone);
      if (!mounted) return;
      setState(() {
        _secondsLeft = result.resendIn;
        _debugCode = result.debugCode;
        _codeCtrl.clear();
      });
      _startTimer();
      _showSuccess(AppStrings.otpResent);
    } on ApiException catch (e) {
      if (mounted) _showError(e.message);
    } on NetworkException catch (e) {
      if (mounted) _showError(e.message);
    } catch (_) {
      if (mounted) _showError(AppStrings.errorUnknown);
    } finally {
      if (mounted) setState(() => _isResending = false);
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

  @override
  Widget build(BuildContext context) {
    final code = _codeCtrl.text;

    return Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          const AuthGradientBackground(),
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Column(
                children: [
                  Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: IconButton(
                      onPressed: () => Navigator.of(context).pop(),
                      icon: const Icon(
                        Icons.arrow_forward_rounded,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Hero(
                    tag: 'app_logo',
                    child: BrandLogoMark(size: 200),
                  ),
                  const SizedBox(height: 18),
                  const Text(
                    AppStrings.otpTitle,
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${AppStrings.otpSubtitle} ${widget.args.phone}',
                    textAlign: TextAlign.center,
                    textDirection: TextDirection.rtl,
                    style: TextStyle(
                      fontSize: 15,
                      color: Colors.white.withValues(alpha: 0.75),
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 32),
                  AuthGlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Directionality(
                          textDirection: TextDirection.ltr,
                          child: Stack(
                            children: [
                              Row(
                                children: List.generate(6, (i) {
                                  final filled = i < code.length;
                                  final active = i == code.length;
                                  return Expanded(
                                    child: AnimatedContainer(
                                      duration:
                                          const Duration(milliseconds: 160),
                                      margin: EdgeInsetsDirectional.only(
                                        end: i == 5 ? 0 : 8,
                                      ),
                                      height: 56,
                                      alignment: Alignment.center,
                                      decoration: BoxDecoration(
                                        color: Colors.white
                                            .withValues(alpha: 0.08),
                                        borderRadius: BorderRadius.circular(14),
                                        border: Border.all(
                                          color: active
                                              ? const Color(0xFF81C784)
                                              : Colors.white
                                                  .withValues(alpha: 0.2),
                                          width: active ? 1.6 : 1,
                                        ),
                                      ),
                                      child: Text(
                                        filled ? code[i] : '',
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 22,
                                          fontWeight: FontWeight.w800,
                                        ),
                                      ),
                                    ),
                                  );
                                }),
                              ),
                              Positioned.fill(
                                child: Opacity(
                                  opacity: 0,
                                  child: TextField(
                                    controller: _codeCtrl,
                                    focusNode: _focus,
                                    keyboardType: TextInputType.number,
                                    maxLength: 6,
                                    inputFormatters: [
                                      FilteringTextInputFormatter.digitsOnly,
                                    ],
                                    onChanged: (value) {
                                      setState(() {});
                                      if (value.length == 6) _verify(value);
                                    },
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        if (_debugCode != null) ...[
                          const SizedBox(height: 14),
                          Text(
                            'رمز التطوير: $_debugCode',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.7),
                              fontSize: 13,
                            ),
                          ),
                        ],
                        const SizedBox(height: 22),
                        AuthPrimaryButton(
                          label: AppStrings.otpVerifyButton,
                          isLoading: _isLoading,
                          onPressed: () => _verify(),
                        ),
                        const SizedBox(height: 16),
                        TextButton(
                          onPressed: _secondsLeft == 0 && !_isResending
                              ? _resend
                              : null,
                          child: Text(
                            _secondsLeft > 0
                                ? '${AppStrings.otpResendIn} $_secondsLeft ث'
                                : AppStrings.otpResend,
                            style: TextStyle(
                              color: _secondsLeft > 0
                                  ? Colors.white.withValues(alpha: 0.5)
                                  : const Color(0xFFA5D6A7),
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  Text(
                    'افتح واتساب وابحث عن رسالة من ${widget.args.fromPhone} ثم أدخل الرمز.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.65),
                      fontSize: 13,
                      height: 1.5,
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (_isLoading)
            const AuthLoadingOverlay(message: 'جاري التحقق...'),
        ],
      ),
    );
  }
}
