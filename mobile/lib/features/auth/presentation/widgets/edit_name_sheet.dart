import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/theme/app_theme.dart';
import '../../data/services/phone_auth_api.dart';
import 'profile_luxe.dart';

/// شيت تعديل الاسم — يظهر من الأسفل ويعرض الاسم والجوال (الجوال للقراءة فقط).
class EditNameSheet {
  static Future<bool> show(
    BuildContext context, {
    required String currentName,
    required String phone,
  }) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      useRootNavigator: true,
      isScrollControlled: true,
      showDragHandle: false,
      backgroundColor: Colors.transparent,
      barrierColor: const Color(0xB3062015),
      sheetAnimationStyle: const AnimationStyle(
        duration: Duration(milliseconds: 460),
        curve: Curves.easeOutCubic,
        reverseDuration: Duration(milliseconds: 280),
        reverseCurve: Curves.easeInCubic,
      ),
      builder: (_) => _EditNameSheetBody(
        currentName: currentName,
        phone: phone,
      ),
    );
    return saved == true;
  }
}

class _EditNameSheetBody extends StatefulWidget {
  final String currentName;
  final String phone;

  const _EditNameSheetBody({
    required this.currentName,
    required this.phone,
  });

  @override
  State<_EditNameSheetBody> createState() => _EditNameSheetBodyState();
}

class _EditNameSheetBodyState extends State<_EditNameSheetBody> {
  late final TextEditingController _ctrl;
  late final FocusNode _nameFocus;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _ctrl = TextEditingController(text: widget.currentName);
    _nameFocus = FocusNode()..addListener(_onFocusChanged);
  }

  void _onFocusChanged() => setState(() {});

  @override
  void dispose() {
    _nameFocus
      ..removeListener(_onFocusChanged)
      ..dispose();
    _ctrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final name = _ctrl.text.trim().replaceAll(RegExp(r'\s+'), ' ');
    if (name.split(' ').length < 2) {
      setState(() => _error = AppStrings.fieldFullNameInvalid);
      return;
    }
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      await PhoneAuthApi.instance.updateName(name);
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } catch (_) {
      if (mounted) setState(() => _error = AppStrings.errorUnknown);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _close() {
    if (_saving) return;
    Navigator.of(context).pop(false);
  }

  void _focusNameField() {
    _nameFocus.requestFocus();
  }

  bool get _nameActive =>
      _nameFocus.hasFocus || _ctrl.text.trim().isNotEmpty;

  @override
  Widget build(BuildContext context) {
    final keyboard = MediaQuery.viewInsetsOf(context).bottom;

    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnimatedPadding(
        duration: const Duration(milliseconds: 220),
        curve: Curves.easeOutCubic,
        padding: EdgeInsets.only(bottom: keyboard),
        child: Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            boxShadow: [
              BoxShadow(
                color: Color(0x33000000),
                blurRadius: 32,
                offset: Offset(0, -6),
              ),
            ],
          ),
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 10, 20, 20),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Align(
                    alignment: AlignmentDirectional.centerEnd,
                    child: IconButton(
                      onPressed: _close,
                      visualDensity: VisualDensity.compact,
                      padding: EdgeInsets.zero,
                      constraints: const BoxConstraints(
                        minWidth: 36,
                        minHeight: 36,
                      ),
                      icon: Icon(
                        Icons.close_rounded,
                        size: 22,
                        color: AppTheme.mutedText.withValues(alpha: 0.85),
                      ),
                    ),
                  ),
                  const Text(
                    AppStrings.editNameTitle,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w900,
                      color: AppTheme.darkText,
                    ),
                  ),
                  const SizedBox(height: 18),
                  _ProfileFieldRow(
                    label: AppStrings.fieldFullName,
                    icon: Icons.badge_outlined,
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: _ctrl,
                            focusNode: _nameFocus,
                            enabled: !_saving,
                            autofocus: false,
                            textInputAction: TextInputAction.done,
                            textCapitalization: TextCapitalization.words,
                            inputFormatters: [
                              LengthLimitingTextInputFormatter(40),
                            ],
                            onChanged: (_) {
                              if (_error != null) {
                                setState(() => _error = null);
                              } else {
                                setState(() {});
                              }
                            },
                            onSubmitted: (_) => _save(),
                            style: const TextStyle(
                              fontSize: 15.5,
                              fontWeight: FontWeight.w700,
                              color: AppTheme.darkText,
                            ),
                            decoration: InputDecoration(
                              isDense: true,
                              hintText: AppStrings.fieldFullName,
                              hintStyle: TextStyle(
                                fontSize: 14.5,
                                fontWeight: FontWeight.w500,
                                color: AppTheme.mutedText.withValues(alpha: 0.55),
                              ),
                              border: InputBorder.none,
                              contentPadding: EdgeInsets.zero,
                            ),
                          ),
                        ),
                        Material(
                          color: Colors.transparent,
                          child: InkWell(
                            onTap: _saving ? null : _focusNameField,
                            borderRadius: BorderRadius.circular(10),
                            child: Padding(
                              padding: const EdgeInsets.all(6),
                              child: Icon(
                                Icons.edit_outlined,
                                size: 18,
                                color: _nameActive
                                    ? AppTheme.primaryDark
                                    : AppTheme.mutedText.withValues(alpha: 0.75),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    active: _nameActive,
                    hasError: _error != null,
                  ),
                  const SizedBox(height: 12),
                  _ProfileFieldRow(
                    label: AppStrings.profilePhone,
                    icon: Icons.phone_iphone_rounded,
                    locked: true,
                    child: Text(
                      widget.phone.isEmpty ? '—' : widget.phone,
                      textDirection: TextDirection.ltr,
                      textAlign: TextAlign.right,
                      style: TextStyle(
                        fontSize: 15.5,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.mutedText.withValues(alpha: 0.85),
                      ),
                    ),
                  ),
                  AnimatedSize(
                    duration: const Duration(milliseconds: 220),
                    curve: Curves.easeOutCubic,
                    child: _error == null
                        ? const SizedBox(width: double.infinity)
                        : Padding(
                            padding: const EdgeInsets.only(top: 10),
                            child: Row(
                              children: [
                                const Icon(
                                  Icons.error_outline_rounded,
                                  size: 15,
                                  color: Color(0xFFD9534F),
                                ),
                                const SizedBox(width: 6),
                                Expanded(
                                  child: Text(
                                    _error!,
                                    style: const TextStyle(
                                      fontSize: 12.5,
                                      fontWeight: FontWeight.w600,
                                      color: Color(0xFFD9534F),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                  ),
                  const SizedBox(height: 20),
                  Center(
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(
                        maxWidth: 220,
                        minWidth: 160,
                      ),
                      child: SizedBox(
                        height: 44,
                        width: double.infinity,
                        child: OutlinedButton(
                          onPressed: _saving ? null : _save,
                          style: OutlinedButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: AppTheme.primaryDark,
                            side: const BorderSide(
                              color: AppTheme.primaryDark,
                              width: 1.4,
                            ),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                            textStyle: const TextStyle(
                              fontSize: 14.5,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          child: _saving
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2.2,
                                    color: AppTheme.primaryDark,
                                  ),
                                )
                              : const Text(AppStrings.editNameSave),
                        ),
                      ),
                    ),
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

class _ProfileFieldRow extends StatelessWidget {
  final String label;
  final IconData icon;
  final Widget child;
  final bool active;
  final bool hasError;
  final bool locked;

  const _ProfileFieldRow({
    required this.label,
    required this.icon,
    required this.child,
    this.active = false,
    this.hasError = false,
    this.locked = false,
  });

  @override
  Widget build(BuildContext context) {
    final borderColor = hasError
        ? const Color(0xFFF0C4C2)
        : active
            ? AppTheme.primaryDark
            : const Color(0xFFE0E6E2);
    final fillColor = locked
        ? AppTheme.primarySurface.withValues(alpha: 0.45)
        : active
            ? AppTheme.primarySurface.withValues(alpha: 0.55)
            : Colors.white;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(right: 2, bottom: 6),
          child: Text(
            label,
            style: TextStyle(
              fontSize: 11.5,
              fontWeight: FontWeight.w800,
              color: active
                  ? AppTheme.primaryDark
                  : AppTheme.mutedText.withValues(alpha: 0.9),
            ),
          ),
        ),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            color: fillColor,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: borderColor,
              width: active ? 1.4 : 1.1,
            ),
          ),
          child: Row(
            children: [
              Icon(
                icon,
                size: 20,
                color: active ? AppTheme.primaryDark : kLuxeDeepB,
              ),
              const SizedBox(width: 10),
              Expanded(child: child),
              if (locked)
                Icon(
                  Icons.lock_outline_rounded,
                  size: 16,
                  color: AppTheme.mutedText.withValues(alpha: 0.55),
                ),
            ],
          ),
        ),
      ],
    );
  }
}
