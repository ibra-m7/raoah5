part of 'favorite_cubit.dart';

/// قائمة معرفات المنتجات المفضلة بالترتيب الذي أُضيفت به.
class FavoriteState extends Equatable {
  const FavoriteState({this.orderedIds = const []});

  final List<String> orderedIds;

  bool contains(String productId) => orderedIds.contains(productId);

  int get length => orderedIds.length;

  FavoriteState copyWith({List<String>? orderedIds}) {
    return FavoriteState(orderedIds: orderedIds ?? this.orderedIds);
  }

  @override
  List<Object?> get props => [orderedIds];
}
