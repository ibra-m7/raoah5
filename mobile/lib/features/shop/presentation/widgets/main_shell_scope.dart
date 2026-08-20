import 'package:flutter/widgets.dart';

/// يوفّر إجراءات الصدفة الرئيسية للتبويبات داخل [MainScreen]
/// حتى تتمكن الشاشات الفرعية (مثل المحادثة) من فتح السلة بنفس منطق الرجوع والتبويب.
class MainShellScope extends InheritedWidget {
  const MainShellScope({
    super.key,
    required this.openCheckout,
    required this.selectTab,
    required super.child,
  });

  final Future<void> Function() openCheckout;
  final void Function(int index) selectTab;

  static MainShellScope read(BuildContext context) {
    final scope = context
        .getElementForInheritedWidgetOfExactType<MainShellScope>()
        ?.widget;
    assert(
      scope is MainShellScope,
      'MainShellScope غير موجود — استخدمه فقط داخل MainScreen',
    );
    return scope! as MainShellScope;
  }

  @override
  bool updateShouldNotify(MainShellScope oldWidget) => false;
}
