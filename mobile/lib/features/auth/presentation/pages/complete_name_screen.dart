import 'package:flutter/material.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/error/exceptions.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../data/services/phone_auth_api.dart';
import '../auth_flow.dart';
import '../widgets/auth_widgets.dart';

class CompleteNameScreen extends StatefulWidget {
  static const routeName = '/complete-name';

  const CompleteNameScreen({super.key});

  @override
  State<CompleteNameScreen> createState() => _CompleteNameScreenState();
}

class _CompleteNameScreenState extends State<CompleteNameScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  bool _isLoading = false;

  @override
  void dispose() {
    _nameCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _isLoading = true);
    try {
      await PhoneAuthApi.instance.updateName(_nameCtrl.text.trim());
      if (!mounted) return;
      AuthFlow.returnToMain(context);
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

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg, textAlign: TextAlign.center),
      backgroundColor: Colors.red.shade700,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Column(
                children: [
                  Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: TextButton(
                      onPressed: () => AuthFlow.leaveAuth(context),
                      child: const Text(
                        AppStrings.completeNameSkip,
                        style: TextStyle(
                          color: Colors.white70,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Hero(
                    tag: 'app_logo',
                    child: BrandLogoMark(size: 200),
                  ),
                  const SizedBox(height: 18),
                  const Text(
                    AppStrings.completeNameTitle,
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    AppStrings.completeNameSubtitle,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 15,
                      color: Colors.white.withValues(alpha: 0.75),
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 32),
                  AuthGlassCard(
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          AuthGlassField(
                            controller: _nameCtrl,
                            label: AppStrings.fieldFullName,
                            icon: Icons.person_rounded,
                            textInputAction: TextInputAction.done,
                            keyboardType: TextInputType.name,
                            validator: (v) {
                              final value = (v ?? '').trim();
                              if (value.isEmpty) return AppStrings.fieldRequired;
                              if (value == 'عميل') {
                                return AppStrings.fieldFullNameReal;
                              }
                              if (value.split(RegExp(r'\s+')).length < 2) {
                                return AppStrings.fieldFullNameInvalid;
                              }
                              return null;
                            },
                          ),
                          const SizedBox(height: 22),
                          AuthPrimaryButton(
                            label: AppStrings.completeNameButton,
                            isLoading: _isLoading,
                            onPressed: _submit,
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (_isLoading)
            const AuthLoadingOverlay(message: 'جاري حفظ الاسم...'),
        ],
      ),
    ),
    );
  }
}
