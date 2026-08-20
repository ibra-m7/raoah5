class AppConstants {
  AppConstants._();

  // Gemini API
  static const String geminiBaseUrl =
      'https://generativelanguage.googleapis.com/v1beta';
  static const String geminiModel = 'gemini-1.5-pro';

  // Cache Keys
  static const String cachedProductsKey = 'CACHED_PRODUCTS';
  static const String cachedCategoriesKey = 'CACHED_CATEGORIES';

  // Timeouts
  static const Duration apiTimeout = Duration(seconds: 30);
  static const Duration geminiTimeout = Duration(seconds: 60);

  // Pagination
  static const int defaultPageSize = 20;
}
