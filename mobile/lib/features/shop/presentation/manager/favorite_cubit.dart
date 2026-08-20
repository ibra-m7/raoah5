import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:shared_preferences/shared_preferences.dart';

part 'favorite_state.dart';

const _kFavoriteIdsKey = 'favorite_product_ids';

/// إدارة المنتجات المفضلة مع حفظ محلي عبر [SharedPreferences].
class FavoriteCubit extends Cubit<FavoriteState> {
  FavoriteCubit() : super(const FavoriteState());

  /// استدعاؤها بعد إنشاء الـ Cubit لتحميل البيانات المحفوظة.
  Future<void> restorePersisted() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getStringList(_kFavoriteIdsKey);
    if (raw == null || raw.isEmpty) return;
    emit(FavoriteState(orderedIds: List<String>.from(raw)));
  }

  Future<void> toggle(String productId) async {
    final list = List<String>.from(state.orderedIds);
    final idx = list.indexOf(productId);
    if (idx >= 0) {
      list.removeAt(idx);
    } else {
      list.add(productId);
    }
    emit(state.copyWith(orderedIds: list));
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(_kFavoriteIdsKey, list);
  }

  Future<void> remove(String productId) async {
    if (!state.contains(productId)) return;
    final list = state.orderedIds.where((id) => id != productId).toList();
    emit(state.copyWith(orderedIds: list));
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(_kFavoriteIdsKey, list);
  }

  /// يحذف المفضلات التي لم تعد موجودة في الكتالوج حتى لا يظهر رقم وهمي.
  Future<void> retainExisting(Set<String> existingIds) async {
    if (state.orderedIds.isEmpty || existingIds.isEmpty) return;
    final kept = state.orderedIds.where(existingIds.contains).toList();
    if (kept.length == state.orderedIds.length) return;
    emit(state.copyWith(orderedIds: kept));
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(_kFavoriteIdsKey, kept);
  }
}
