import 'package:equatable/equatable.dart';

class Product extends Equatable {
  // ── الحقول الأساسية ────────────────────────────────────────────────────
  final String id;
  final String name;
  final String description;
  final double price;
  final double? discountPrice;
  final String imageUrl;
  final List<String> imageUrls;
  final String categoryId;
  final int stock;
  final String quantityLabel;
  final int soldCount;
  final double rating;
  final int reviewCount;

  // ── حقول الذكاء الاصطناعي ─────────────────────────────────────────────
  /// فوائد المنتج — يستخدمها Gemini لصياغة ردود مقنعة
  final List<String> benefits;

  /// كلمات دلالية للبحث الطبيعي والـ Function Calling
  final List<String> keywords;

  /// طريقة الاستخدام — يشرحها المساعد عند الطلب
  final String usageInstructions;

  const Product({
    required this.id,
    required this.name,
    required this.description,
    required this.price,
    this.discountPrice,
    required this.imageUrl,
    this.imageUrls = const [],
    required this.categoryId,
    required this.stock,
    this.quantityLabel = '',
    this.soldCount = 0,
    this.rating = 0.0,
    this.reviewCount = 0,
    this.benefits = const [],
    this.keywords = const [],
    this.usageInstructions = '',
  });

  bool get isAvailable => stock > 0;
  bool get hasDiscount => discountPrice != null && discountPrice! < price;
  double get effectivePrice => discountPrice ?? price;
  double get discountPercentage =>
      hasDiscount ? ((price - discountPrice!) / price * 100).roundToDouble() : 0;

  String get soldLabel {
    if (soldCount <= 0) return '';
    if (soldCount >= 1000) {
      final k = soldCount / 1000;
      final text = k == k.roundToDouble()
          ? k.toInt().toString()
          : k.toStringAsFixed(1).replaceAll(RegExp(r'\.0$'), '');
      return 'اشتراه +${text}K عميل';
    }
    return 'اشتراه +$soldCount عميل';
  }

  @override
  List<Object?> get props => [
        id,
        name,
        price,
        discountPrice,
        categoryId,
        stock,
        quantityLabel,
        soldCount,
        rating,
        benefits,
        keywords,
      ];
}
