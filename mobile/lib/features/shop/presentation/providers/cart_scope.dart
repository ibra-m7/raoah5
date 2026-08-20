import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import '../manager/cart_cubit.dart';

/// **مزود السلة = [CartCubit] عبر [BlocProvider] في `main.dart`**
///
/// لا يوجد `CartProvider` منفصل؛ نفس الدور يؤديه [BlocProvider<CartCubit>].
/// استخدم [cart] للقراءة بدون إعادة بناء، وـ [BlocBuilder]/[BlocSelector] للتحديث الفوري.
extension CartScope on BuildContext {
  /// مكافئ `read<CartProvider>()` — يستدعي إجراءات السلة فقط.
  CartCubit get cart => read<CartCubit>();

  /// مكافئ الاستماع لـ Provider — يعيد بناء الـ widget عند كل تحديث للحالة.
  CartState get watchCart => watch<CartCubit>().state;
}
