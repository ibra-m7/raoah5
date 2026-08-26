import '../../domain/entities/category.dart';

List<T> jsonMapList<T>(
  dynamic raw,
  T Function(Map<String, dynamic> json) mapper,
) {
  if (raw is! List) return <T>[];
  return raw
      .whereType<Map>()
      .map((item) => mapper(Map<String, dynamic>.from(item)))
      .toList();
}

class CategoryModel extends Category {
  final String? parentId;
  final String imageUrl;
  final String backgroundImageUrl;
  final String? color;
  final int productsCount;
  final List<CategoryModel> children;

  const CategoryModel({
    required super.id,
    required super.name,
    required super.iconUrl,
    this.parentId,
    this.imageUrl = '',
    this.backgroundImageUrl = '',
    this.color,
    this.productsCount = 0,
    this.children = const [],
  });

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    return CategoryModel(
      id: '${json['id']}',
      name: (json['name'] as String?) ?? '',
      iconUrl: (json['icon_url'] as String?) ?? '',
      parentId: json['parent_id']?.toString(),
      imageUrl: (json['image_url'] as String?) ?? '',
      backgroundImageUrl: (json['background_image_url'] as String?) ?? '',
      color: json['color'] as String?,
      productsCount: (json['products_count'] as num?)?.toInt() ?? 0,
      children: jsonMapList(json['children'], CategoryModel.fromJson),
    );
  }

  String get displayImage =>
      imageUrl.isNotEmpty ? imageUrl : iconUrl;

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'icon_url': iconUrl,
      'image_url': imageUrl,
      'background_image_url': backgroundImageUrl,
      'parent_id': parentId,
      'color': color,
      'products_count': productsCount,
      'children': children.map((c) => c.toJson()).toList(),
    };
  }
}
