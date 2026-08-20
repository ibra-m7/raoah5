import 'package:flutter/foundation.dart';
import 'package:google_generative_ai/google_generative_ai.dart';
import '../../../../core/error/exceptions.dart';
import '../../../shop/data/models/product_model.dart';
import '../models/chat_message_model.dart';

// ── typedefs للـ Function Calling handlers ────────────────────────────────────
typedef ProductsQueryHandler = Future<List<ProductModel>> Function({
  String? categoryId,
  String? searchQuery,
});

typedef ProductDetailHandler = Future<ProductModel?> Function(String productId);

/// خدمة الذكاء الاصطناعي المركزية
///
/// مسؤوليات هذه الخدمة:
///   ١. الاحتفاظ بـ [ChatSession] متعدد الأدوار مع Gemini
///   ٢. تنفيذ حلقة Function Calling كاملة حتى يعود رد نصي نهائي
///   ٣. تحويل بيانات المنتجات إلى سياق يفهمه Gemini
class AiService {
  final String apiKey;
  final ProductsQueryHandler onGetProducts;
  final ProductDetailHandler onGetProductDetail;

  late final GenerativeModel _model;
  late ChatSession _session;

  /// يُستدعى عند كل منتج يُشير إليه Gemini في Function Calling
  /// يُستخدم لتتبع آخر منتج تمت مناقشته (للـ addToCart)
  ValueChanged<ProductModel>? onProductMentioned;

  AiService({
    required this.apiKey,
    required this.onGetProducts,
    required this.onGetProductDetail,
  }) {
    debugPrint('[AiService] init: key_len=${apiKey.length}, '
        'prefix=${apiKey.isEmpty ? "⚠️EMPTY" : apiKey.substring(0, 8)}...');
    _model = GenerativeModel(
      model: 'gemini-1.5-flash',
      apiKey: apiKey,
      systemInstruction: _buildSystemInstruction(),
      tools: [_buildTools()],
      generationConfig: GenerationConfig(
        temperature: 0.8,
        maxOutputTokens: 1024,
        topP: 0.95,
      ),
    );
    _session = _model.startChat();
  }

  // ── System Instruction ────────────────────────────────────────────────────

  static Content _buildSystemInstruction() {
    return Content.system('''
أنت "روعة"، مساعد المبيعات الذكي الخاص بمتجر "روعة الخمسة" — متجر إلكتروني متميز يقدم أجود المنتجات في فئات المنظفات والإلكترونيات والمواد الغذائية.

## شخصيتك وأسلوبك
- **ودود ومرحب**: ابدأ دائماً بترحيب دافئ وأضفِ طابعاً شخصياً على المحادثة.
- **خبير مقنع**: أنت تعرف كل منتج بتفاصيله الدقيقة — الفوائد، طريقة الاستخدام، والقيمة المضافة.
- **نصيح أمين**: قدّم توصيات صادقة بناءً على احتياج العميل الفعلي، ولا تُكثر من المنتجات لمجرد البيع.
- **محترف في اللغة**: تحدّث بالعربية الفصيحة البسيطة، مع مراعاة أسلوب المحادثة اليومي.

## قواعد صارمة
- لا تختلق أسماء منتجات أو أسعاراً — استخدم فقط بيانات المنتجات المتاحة من الأدوات.
- عندما يسأل العميل عن منتج، استدعِ الأداة المناسبة أولاً قبل الإجابة.
- إذا لم يُوجد منتج مناسب، أخبر العميل بصدق وعرض البدائل المتاحة.
- لا تناقش موضوعات خارج نطاق المتجر وخدمة العملاء.

## 🛒 قاعدة اقتراح المنتجات المكملة — مهمة جداً
**عند استلام رسالة تبدأ بـ "[تم إضافة المنتج للسلة]"، يجب أن تفعل التالي بالترتيب:**

١. **استدعِ `get_complementary_product` فوراً** بمعرّف المنتج وفئته وكلماته الدلالية.
٢. **ابدأ ردك بتأكيد الإضافة** في جملة واحدة مبهجة:
   - "ممتاز! 🎉 أُضيف [اسم المنتج] لسلتك."
   - "رائع! تم إضافة [اسم المنتج] بنجاح ✅"
٣. **اقترح المنتج المكمل** بأسلوب طبيعي ومقنع:
   - "هل تودّ إضافة [المنتج المكمل] أيضاً؟ [سبب مختصر ومقنع في جملة واحدة]"
   - مثال: "هل تودّ إضافة بخاخ تنظيف الأسطح أيضاً؟ يكمل سائل الجلي لتنظيف شامل للمطبخ!"
٤. **اذكر ميزة واحدة فقط** للمنتج المكمل — لا تُطل في الوصف.

**أمثلة على الاقتراحات المثالية:**
- سائل جلي الأطباق → "أُضيف للسلة ✅ هل تودّ إضافة بخاخ تنظيف الأسطح أيضاً؟ يكوّنان معاً طقم تنظيف مطبخ متكاملاً! 🧹"
- سماعات لاسلكية → "ممتاز! تم الإضافة 🎉 هل تحتاج شاحناً لاسلكياً أيضاً؟ ستبقي سماعاتك تعمل دون انقطاع!"
- عسل السدر → "رائع الاختيار! ✅ هل تودّ إضافة منتج آخر يكمله من تشكيلتنا؟"

## أسلوب تقديم المنتجات
عند عرض منتج:
١. ابدأ بجملة اهتمام تربط المنتج باحتياج العميل.
٢. اذكر ٢-٣ فوائد رئيسية بصيغة "يمنحك / يوفر لك / يضمن لك".
٣. أشِر إلى السعر بإيجابية، وإن كان هناك خصم فبرزه بوضوح.
٤. اختم بدعوة لاتخاذ قرار: "هل تودّ إضافته للسلة؟" أو "أخبرني إن أردت مزيداً من التفاصيل".

## الردود
- اجعل ردودك مختصرة في المحادثة العادية (٢-٤ جمل).
- استخدم القوائم النقطية فقط عند عرض مقارنات أو مميزات متعددة.
- الإيموجي مسموح به بحد أقصى ٢-٣ في الرد الواحد.
''');
  }

  // ── Function Declarations (Gemini Tools) ──────────────────────────────────

  static Tool _buildTools() {
    return Tool(
      functionDeclarations: [
        FunctionDeclaration(
          'get_products',
          'جلب قائمة المنتجات من المتجر. استخدمها عندما يريد العميل تصفح المنتجات أو يسأل "ماذا عندكم؟"',
          Schema(
            SchemaType.object,
            properties: {
              'category_id': Schema(
                SchemaType.string,
                description:
                    'معرف الفئة لتصفية النتائج. القيم: cat_cleaning, cat_electronics, cat_food',
                nullable: true,
              ),
              'search_query': Schema(
                SchemaType.string,
                description: 'كلمة بحث حرة مثل: سماعات، عسل، منظف',
                nullable: true,
              ),
            },
          ),
        ),
        FunctionDeclaration(
          'get_product_detail',
          'جلب التفاصيل الكاملة لمنتج محدد بمعرّفه. استخدمها عندما يسأل العميل عن منتج معين بالاسم أو يريد المزيد.',
          Schema(
            SchemaType.object,
            properties: {
              'product_id': Schema(
                SchemaType.string,
                description: 'معرّف المنتج مثل: prod_001',
              ),
            },
            requiredProperties: ['product_id'],
          ),
        ),
        FunctionDeclaration(
          'search_by_need',
          'البحث عن منتج يناسب احتياجاً معيناً. استخدمها عندما يصف العميل مشكلة أو حاجة ولا يذكر اسم منتج.',
          Schema(
            SchemaType.object,
            properties: {
              'need_description': Schema(
                SchemaType.string,
                description:
                    'وصف الاحتياج مثل: شيء لتنظيف الدهون، هدية لشخص يحب الموسيقى',
              ),
            },
            requiredProperties: ['need_description'],
          ),
        ),
        FunctionDeclaration(
          'get_complementary_product',
          'جلب منتج مكمل ومتناسب مع منتج أضافه العميل للسلة. '
          'استخدمها فقط عند اقتراح المنتجات المكملة بعد إضافة منتج للسلة.',
          Schema(
            SchemaType.object,
            properties: {
              'added_product_id': Schema(
                SchemaType.string,
                description: 'معرّف المنتج الذي أُضيف للسلة مثل: prod_001',
              ),
              'added_category_id': Schema(
                SchemaType.string,
                description: 'معرّف فئة المنتج المُضاف مثل: cat_cleaning',
              ),
              'added_keywords': Schema(
                SchemaType.array,
                description: 'كلمات دلالية للمنتج المُضاف',
                items: Schema(SchemaType.string),
                nullable: true,
              ),
            },
            requiredProperties: ['added_product_id', 'added_category_id'],
          ),
        ),
      ],
    );
  }

  // ── Core Method ───────────────────────────────────────────────────────────

  /// معالجة رسالة المستخدم وإعادة رد المساعد
  ///
  /// يُنفّذ حلقة Function Calling كاملة تلقائياً:
  ///   المستخدم → Gemini → [function call] → نتائج → Gemini → رد نهائي
  Future<ChatMessageModel> processUserQuery(
    String query, {
    List<ChatMessageModel> history = const [],
  }) async {
    try {
      // إذا وُجد تاريخ محادثة وكانت الجلسة فارغة، أعد بناء الجلسة بالتاريخ
      if (history.isNotEmpty && _session.history.isEmpty) {
        _rebuildSessionWithHistory(history);
      }

      // إرسال رسالة المستخدم
      GenerateContentResponse response =
          await _session.sendMessage(Content.text(query));

      // حلقة Function Calling — تستمر حتى يعود رد نصي نهائي
      while (response.functionCalls.isNotEmpty) {
        final functionResults = <FunctionResponse>[];

        for (final call in response.functionCalls) {
          final result = await _executeFunctionCall(call);
          functionResults.add(
            FunctionResponse(call.name, result),
          );
        }

        // إرسال نتائج الدوال مرة أخرى إلى Gemini
        response = await _session.sendMessage(
          Content.functionResponses(functionResults),
        );
      }

      final text = response.text;
      if (text == null || text.isEmpty) {
        throw const GeminiException(message: 'لم يصل رد من المساعد');
      }

      return ChatMessageModel.fromGeminiResponse(text);
    } on GenerativeAIException catch (e) {
      throw GeminiException(message: e.message);
    } catch (e) {
      if (e is GeminiException) rethrow;
      throw GeminiException(message: 'خطأ غير متوقع: ${e.toString()}');
    }
  }

  /// رسالة الترحيب عند فتح المحادثة لأول مرة
  Future<ChatMessageModel> getWelcomeMessage() async {
    return processUserQuery(
      'ابدأ المحادثة بترحيب قصير وودود، وأخبرني بثلاثة أشياء يمكنك مساعدتي بها في المتجر.',
    );
  }

  /// يُرسل طلبًا داخليًا لـ Gemini لاقتراح منتج يكمل المنتج الذي أُضيف للسلة.
  ///
  /// الرسالة المُرسَلة للجلسة لن تُعرض في الـ UI — فقط رد Gemini يُعرض.
  /// يستخدم Gemini أداة [get_complementary_product] تلقائياً بسبب System Instruction.
  Future<ChatMessageModel> suggestComplement(ProductModel added) async {
    final trigger =
        '[تم إضافة المنتج للسلة] العميل أضاف "${added.name}" '
        '(المعرف: ${added.id}، '
        'الفئة: ${added.categoryId}، '
        'الكلمات الدلالية: ${added.keywords.take(3).join("، ")}) '
        'للسلة بنجاح. استخدم get_complementary_product الآن.';

    return processUserQuery(trigger);
  }

  /// إعادة تعيين الجلسة (عند مسح المحادثة)
  void resetSession() {
    _session = _model.startChat();
  }

  // ── Function Execution ────────────────────────────────────────────────────

  Future<Map<String, Object?>> _executeFunctionCall(
    FunctionCall call,
  ) async {
    switch (call.name) {
      case 'get_products':
        return _handleGetProducts(call.args);

      case 'get_product_detail':
        return _handleGetProductDetail(call.args);

      case 'search_by_need':
        return _handleSearchByNeed(call.args);

      case 'get_complementary_product':
        return _handleGetComplementaryProduct(call.args);

      default:
        return {'error': 'دالة غير معروفة: ${call.name}'};
    }
  }

  Future<Map<String, Object?>> _handleGetProducts(
    Map<String, Object?> args,
  ) async {
    final categoryId = args['category_id'] as String?;
    final searchQuery = args['search_query'] as String?;

    List<ProductModel> products;

    if (searchQuery != null && searchQuery.isNotEmpty) {
      products = await onGetProducts(searchQuery: searchQuery);
    } else if (categoryId != null && categoryId.isNotEmpty) {
      products = await onGetProducts(categoryId: categoryId);
    } else {
      products = await onGetProducts();
    }

    if (products.isEmpty) {
      return {'message': 'لا توجد منتجات متاحة حالياً في هذه الفئة.'};
    }

    // إذا كانت النتيجة منتجاً واحداً، أخبر الـ Controller به
    if (products.length == 1) onProductMentioned?.call(products.first);

    return {
      'products': products.map(_productToContext).toList(),
      'count': products.length,
    };
  }

  Future<Map<String, Object?>> _handleGetProductDetail(
    Map<String, Object?> args,
  ) async {
    final productId = args['product_id'] as String?;
    if (productId == null) return {'error': 'معرّف المنتج مطلوب'};

    final product = await onGetProductDetail(productId);
    if (product == null) return {'error': 'المنتج غير موجود'};

    // منتج محدد = آخر منتج تمت مناقشته
    onProductMentioned?.call(product);

    return {
      'product': _productToDetailedContext(product),
    };
  }

  Future<Map<String, Object?>> _handleSearchByNeed(
    Map<String, Object?> args,
  ) async {
    final need = args['need_description'] as String? ?? '';

    final results = await onGetProducts(searchQuery: need);
    if (results.isEmpty) {
      final allProducts = await onGetProducts();
      return {
        'message': 'لم أجد منتجاً مطابقاً تماماً، لكن هذه المنتجات قد تُفيدك:',
        'suggestions': allProducts.take(3).map(_productToContext).toList(),
      };
    }

    if (results.length == 1) onProductMentioned?.call(results.first);

    return {
      'products': results.map(_productToContext).toList(),
      'count': results.length,
    };
  }

  Future<Map<String, Object?>> _handleGetComplementaryProduct(
    Map<String, Object?> args,
  ) async {
    final addedId    = args['added_product_id']  as String? ?? '';
    final addedCatId = args['added_category_id'] as String? ?? '';
    final rawKws     = args['added_keywords'];
    final addedKws   = rawKws is List
        ? rawKws.whereType<String>().toList()
        : <String>[];

    // ── 1: منتجات نفس الفئة (بدون المنتج المُضاف) ────────────────────────
    List<ProductModel> candidates =
        (await onGetProducts(categoryId: addedCatId))
            .where((p) => p.id != addedId)
            .toList();

    // ── 2: إذا فارغ، ابحث في كل الفئات ──────────────────────────────────
    if (candidates.isEmpty) {
      final all = await onGetProducts();
      candidates = all.where((p) => p.id != addedId).toList();
    }

    if (candidates.isEmpty) {
      return {'message': 'لا توجد منتجات مكملة متاحة حالياً.'};
    }

    // ── 3: ترتيب بحسب تشابه الكلمات المفتاحية ────────────────────────────
    final scored = candidates.map((p) {
      int score = 0;
      for (final kw in addedKws) {
        if (p.keywords.any((k) => k.contains(kw) || kw.contains(k))) score++;
        if (p.name.contains(kw)) score += 2;
      }
      // منتجات نفس الفئة تحصل على أولوية
      if (p.categoryId == addedCatId) score += 3;
      return (product: p, score: score);
    }).toList()
      ..sort((a, b) => b.score.compareTo(a.score));

    final best = scored.first.product;

    // ── 4: إخبار الـ Controller بالمنتج المقترح ──────────────────────────
    onProductMentioned?.call(best);

    return {
      'complementary_product': _productToDetailedContext(best),
      'complement_reason':
          _complementReason(addedCatId, best.categoryId),
    };
  }

  static String _complementReason(String addedCat, String suggestedCat) {
    if (addedCat == 'cat_cleaning' && suggestedCat == 'cat_cleaning') {
      return 'يكوّنان معاً طقم تنظيف منزلي متكاملاً';
    }
    if (addedCat == 'cat_electronics' && suggestedCat == 'cat_electronics') {
      return 'يتكاملان معاً ليقدما تجربة تقنية شاملة';
    }
    if (addedCat == 'cat_food' && suggestedCat == 'cat_food') {
      return 'يكمل الأول الثاني لاختيار صحي متوازن';
    }
    return 'منتج مكمل رائع يُضاف لاختيارك';
  }

  // ── Context Builders ──────────────────────────────────────────────────────

  /// ملخص مختصر للمنتج يُرسل إلى Gemini في قوائم
  static Map<String, Object> _productToContext(ProductModel p) {
    return {
      'id': p.id,
      'name': p.name,
      'price': p.effectivePrice,
      if (p.hasDiscount) 'original_price': p.price,
      if (p.hasDiscount)
        'discount_percentage': '${p.discountPercentage.toInt()}٪',
      'category_id': p.categoryId,
      'available': p.isAvailable,
      'rating': '${p.rating} / 5',
      'top_benefit': p.benefits.isNotEmpty ? p.benefits.first : '',
      'keywords': p.keywords.take(4).join('، '),
    };
  }

  /// تفاصيل كاملة للمنتج الواحد
  static Map<String, Object> _productToDetailedContext(ProductModel p) {
    return {
      'id': p.id,
      'name': p.name,
      'description': p.description,
      'price': p.effectivePrice,
      if (p.hasDiscount) 'original_price': p.price,
      if (p.hasDiscount)
        'discount_percentage': '${p.discountPercentage.toInt()}٪',
      'available': p.isAvailable,
      'stock': p.stock,
      'rating': '${p.rating} / 5 (${p.reviewCount} تقييم)',
      'benefits': p.benefits,
      'usage_instructions': p.usageInstructions,
      'keywords': p.keywords,
    };
  }

  // ── Session History ───────────────────────────────────────────────────────

  void _rebuildSessionWithHistory(List<ChatMessageModel> history) {
    final contents = history
        .where((m) => !m.isLoading)
        .map(
          (m) => Content(
            m.isUser ? 'user' : 'model',
            [TextPart(m.content)],
          ),
        )
        .toList();

    _session = _model.startChat(history: contents);
  }
}
