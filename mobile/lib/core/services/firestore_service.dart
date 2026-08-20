import 'package:cloud_firestore/cloud_firestore.dart';
import '../../features/shop/data/models/product_model.dart';
import '../../features/shop/data/models/category_model.dart';
import '../error/exceptions.dart';

/// خدمة مركزية للتعامل مع Firestore
/// ترجع Models مباشرةً باستخدام fromFirestore — تحويل واحد فقط في كل المسار
class FirestoreService {
  final FirebaseFirestore _db;

  FirestoreService({FirebaseFirestore? firestore})
      : _db = firestore ?? FirebaseFirestore.instance;

  // ── Collections ───────────────────────────────────────────────────────────

  CollectionReference<Map<String, dynamic>> get _products =>
      _db.collection('products');

  CollectionReference<Map<String, dynamic>> get _categories =>
      _db.collection('categories');

  // ── Products ──────────────────────────────────────────────────────────────

  /// جلب صفحة من المنتجات مع Pagination عبر startAfter
  Future<List<ProductModel>> getProducts({
    int limit = 20,
    DocumentSnapshot? lastDocument,
  }) async {
    try {
      Query<Map<String, dynamic>> query =
          _products.orderBy('created_at', descending: true).limit(limit);

      if (lastDocument != null) {
        query = query.startAfterDocument(lastDocument);
      }

      final snapshot = await query.get();
      return snapshot.docs.map(ProductModel.fromFirestore).toList();
    } on FirebaseException catch (e) {
      throw ServerException(message: e.message ?? 'خطأ في جلب المنتجات');
    }
  }

  /// جلب منتجات فئة معينة
  Future<List<ProductModel>> getProductsByCategory(
    String categoryId, {
    int limit = 20,
  }) async {
    try {
      final snapshot = await _products
          .where('category_id', isEqualTo: categoryId)
          .limit(limit)
          .get();

      return snapshot.docs.map(ProductModel.fromFirestore).toList();
    } on FirebaseException catch (e) {
      throw ServerException(message: e.message ?? 'خطأ في فلترة المنتجات');
    }
  }

  /// جلب منتج واحد بالمعرّف
  Future<ProductModel?> getProductById(String productId) async {
    try {
      final doc = await _products.doc(productId).get();
      if (!doc.exists || doc.data() == null) return null;
      return ProductModel.fromFirestore(doc);
    } on FirebaseException catch (e) {
      throw ServerException(message: e.message ?? 'خطأ في جلب المنتج');
    }
  }

  /// البحث باستخدام حقل keywords (arrayContains)
  Future<List<ProductModel>> searchProducts(String query) async {
    try {
      final lowerQuery = query.toLowerCase();
      final snapshot = await _products
          .where('keywords', arrayContains: lowerQuery)
          .limit(30)
          .get();

      return snapshot.docs.map(ProductModel.fromFirestore).toList();
    } on FirebaseException catch (e) {
      throw ServerException(message: e.message ?? 'خطأ في البحث');
    }
  }

  /// رفع منتج جديد إلى Firestore (يُستخدم في لوحة الإدارة)
  Future<String> addProduct(ProductModel product) async {
    try {
      final ref = await _products.add(product.toFirestore());
      return ref.id;
    } on FirebaseException catch (e) {
      throw ServerException(message: e.message ?? 'خطأ في إضافة المنتج');
    }
  }

  // ── Categories ────────────────────────────────────────────────────────────

  Future<List<CategoryModel>> getCategories() async {
    try {
      final snapshot = await _categories.orderBy('sort_order').get();
      return snapshot.docs
          .map((doc) => CategoryModel.fromJson({'id': doc.id, ...doc.data()}))
          .toList();
    } on FirebaseException catch (e) {
      throw ServerException(message: e.message ?? 'خطأ في جلب الفئات');
    }
  }

  // ── Real-time Streams ─────────────────────────────────────────────────────

  /// Stream للمنتجات المميزة (للصفحة الرئيسية)
  Stream<List<ProductModel>> watchFeaturedProducts({int limit = 10}) {
    return _products
        .where('is_featured', isEqualTo: true)
        .limit(limit)
        .snapshots()
        .map(
          (snapshot) =>
              snapshot.docs.map(ProductModel.fromFirestore).toList(),
        );
  }
}
