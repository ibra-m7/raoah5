import 'package:cloud_firestore/cloud_firestore.dart';
import '../../domain/entities/product.dart';

class ProductModel extends Product {
  const ProductModel({
    required super.id,
    required super.name,
    required super.description,
    required super.price,
    super.discountPrice,
    required super.imageUrl,
    super.imageUrls,
    required super.categoryId,
    required super.stock,
    super.quantityLabel,
    super.soldCount,
    super.rating,
    super.reviewCount,
    super.benefits,
    super.keywords,
    super.usageInstructions,
  });

  // ── Firestore ─────────────────────────────────────────────────────────────

  /// تحويل من DocumentSnapshot مباشرةً (مع id تلقائي)
  factory ProductModel.fromFirestore(DocumentSnapshot<Map<String, dynamic>> doc) {
    final data = doc.data()!;
    return ProductModel(
      id: doc.id,
      name: data['name'] as String,
      description: data['description'] as String,
      price: (data['price'] as num).toDouble(),
      discountPrice: data['discount_price'] != null
          ? (data['discount_price'] as num).toDouble()
          : null,
      imageUrl: data['image_url'] as String? ?? '',
      imageUrls: _toStringList(data['image_urls']),
      categoryId: data['category_id'] as String,
      stock: (data['stock'] as num).toInt(),
      quantityLabel: data['quantity_label'] as String? ?? '',
      soldCount: (data['sold_count'] as num?)?.toInt() ?? 0,
      rating: (data['rating'] as num?)?.toDouble() ?? 0.0,
      reviewCount: (data['review_count'] as num?)?.toInt() ?? 0,
      benefits: _toStringList(data['benefits']),
      keywords: _toStringList(data['keywords']),
      usageInstructions: data['usage_instructions'] as String? ?? '',
    );
  }

  /// تحويل إلى Map لرفعه لـ Firestore (بدون id — يُدار بواسطة Firestore)
  Map<String, dynamic> toFirestore() {
    return {
      'name': name,
      'description': description,
      'price': price,
      if (discountPrice != null) 'discount_price': discountPrice,
      'image_url': imageUrl,
      'image_urls': imageUrls,
      'category_id': categoryId,
      'stock': stock,
      'quantity_label': quantityLabel,
      'sold_count': soldCount,
      'rating': rating,
      'review_count': reviewCount,
      // حقول الذكاء الاصطناعي
      'benefits': benefits,
      'keywords': keywords,
      'usage_instructions': usageInstructions,
      'updated_at': FieldValue.serverTimestamp(),
    };
  }

  // ── JSON (للاستخدام المحلي / Mock) ────────────────────────────────────────

  factory ProductModel.fromJson(Map<String, dynamic> json) {
    final images = _toStringList(json['image_urls']);
    final imageUrl = (json['image_url'] as String?) ??
        (images.isNotEmpty ? images.first : '');
    return ProductModel(
      id: '${json['id']}',
      name: (json['name'] as String?) ?? '',
      description: (json['description'] as String?) ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0,
      discountPrice: json['discount_price'] != null
          ? (json['discount_price'] as num).toDouble()
          : null,
      imageUrl: imageUrl,
      imageUrls: images,
      categoryId: '${json['category_id'] ?? ''}',
      stock: (json['stock'] as num?)?.toInt() ?? 0,
      quantityLabel: (json['quantity_label'] as String?) ?? '',
      soldCount: (json['sold_count'] as num?)?.toInt() ?? 0,
      rating: (json['rating'] as num?)?.toDouble() ?? 0.0,
      reviewCount: (json['review_count'] as num?)?.toInt() ?? 0,
      benefits: _toStringList(json['benefits']),
      keywords: _toStringList(json['keywords']),
      usageInstructions: json['usage_instructions'] as String? ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'price': price,
      'discount_price': discountPrice,
      'image_url': imageUrl,
      'image_urls': imageUrls,
      'category_id': categoryId,
      'stock': stock,
      'quantity_label': quantityLabel,
      'sold_count': soldCount,
      'rating': rating,
      'review_count': reviewCount,
      'benefits': benefits,
      'keywords': keywords,
      'usage_instructions': usageInstructions,
    };
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  factory ProductModel.fromEntity(Product product) {
    return ProductModel(
      id: product.id,
      name: product.name,
      description: product.description,
      price: product.price,
      discountPrice: product.discountPrice,
      imageUrl: product.imageUrl,
      imageUrls: product.imageUrls,
      categoryId: product.categoryId,
      stock: product.stock,
      quantityLabel: product.quantityLabel,
      soldCount: product.soldCount,
      rating: product.rating,
      reviewCount: product.reviewCount,
      benefits: product.benefits,
      keywords: product.keywords,
      usageInstructions: product.usageInstructions,
    );
  }

  ProductModel copyWith({
    String? id,
    String? name,
    String? description,
    double? price,
    double? discountPrice,
    String? imageUrl,
    List<String>? imageUrls,
    String? categoryId,
    int? stock,
    String? quantityLabel,
    int? soldCount,
    double? rating,
    int? reviewCount,
    List<String>? benefits,
    List<String>? keywords,
    String? usageInstructions,
  }) {
    return ProductModel(
      id: id ?? this.id,
      name: name ?? this.name,
      description: description ?? this.description,
      price: price ?? this.price,
      discountPrice: discountPrice ?? this.discountPrice,
      imageUrl: imageUrl ?? this.imageUrl,
      imageUrls: imageUrls ?? this.imageUrls,
      categoryId: categoryId ?? this.categoryId,
      stock: stock ?? this.stock,
      quantityLabel: quantityLabel ?? this.quantityLabel,
      soldCount: soldCount ?? this.soldCount,
      rating: rating ?? this.rating,
      reviewCount: reviewCount ?? this.reviewCount,
      benefits: benefits ?? this.benefits,
      keywords: keywords ?? this.keywords,
      usageInstructions: usageInstructions ?? this.usageInstructions,
    );
  }

  static List<String> _toStringList(dynamic value) {
    if (value == null) return [];
    if (value is List) return value.map((e) => e.toString()).toList();
    return [];
  }
}
