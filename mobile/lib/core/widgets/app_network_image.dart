import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import 'brand_logo.dart';

/// صورة من الشبكة مع كاش على القرص وذاكرة مضغوطة للتمرير السلس.
class AppNetworkImage extends StatelessWidget {
  final String url;
  final BoxFit fit;
  final double? width;
  final double? height;
  final Widget? placeholder;
  final Widget? error;

  /// من إعدادات المتجر: تظهر إذا المنتج بلا صورة أو رابط صورته فشل.
  static String fallbackUrl = '';

  const AppNetworkImage(
    this.url, {
    super.key,
    this.fit = BoxFit.cover,
    this.width,
    this.height,
    this.placeholder,
    this.error,
  });

  static const headers = {
    'User-Agent':
        'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36',
    'Accept': 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
  };

  /// Unsplash يحتاج ترويسة متصفح. تخزين Laravel المحلي يرفضها أحياناً بـ 403.
  static Map<String, String>? headersFor(String url) {
    final host = Uri.tryParse(url)?.host.toLowerCase() ?? '';
    if (host.contains('unsplash.com') || host.contains('wsrv.nl')) {
      return headers;
    }
    return null;
  }

  /// Unsplash وصور التخزين الكبيرة تُمرَّر كـ JPEG حتى يفكّها أندرويد بدون خطأ.
  static String resolveUrl(String raw) {
    final url = raw.trim();
    if (url.isEmpty) return url;
    final uri = Uri.tryParse(url);
    if (uri == null || !uri.hasScheme) return url;
    final host = uri.host.toLowerCase();
    final path = uri.path.toLowerCase();
    final isUnsplash = host == 'images.unsplash.com' || host == 'unsplash.com';
    final isRenderStorage =
        host.contains('onrender.com') && path.contains('/storage/');
    if (isUnsplash || isRenderStorage) {
      return Uri.https('wsrv.nl', '/', {
        'url': url,
        'w': '1200',
        'q': '82',
        'output': 'jpg',
      }).toString();
    }
    return url;
  }

  int? get _memCacheWidth {
    if (width == null) return 600;
    final dpr = WidgetsBinding.instance.platformDispatcher.views.isEmpty
        ? 2.0
        : WidgetsBinding.instance.platformDispatcher.views.first.devicePixelRatio;
    return (width! * dpr).round().clamp(48, 1200);
  }

  @override
  Widget build(BuildContext context) {
    final primary = url.trim();
    final fallback = fallbackUrl.trim();
    final first = primary.isNotEmpty ? primary : fallback;
    if (first.isEmpty) {
      return _localFallback();
    }

    return _network(
      first,
      onError: fallback.isNotEmpty && fallback != first
          ? () => _network(fallback, onError: _localFallback)
          : _localFallback,
    );
  }

  Widget _localFallback() {
    return SizedBox(
      width: width,
      height: height,
      child: ColoredBox(
        color: AppTheme.primarySurface,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Image.asset(
            BrandLogoMark.assetPath,
            fit: BoxFit.contain,
            errorBuilder: (_, _, _) =>
                error ?? _defaultError(width, height),
          ),
        ),
      ),
    );
  }

  Widget _network(String raw, {Widget Function()? onError}) {
    final resolved = resolveUrl(raw);
    return CachedNetworkImage(
      imageUrl: resolved,
      httpHeaders: headersFor(resolved),
      fit: fit,
      width: width,
      height: height,
      fadeInDuration: const Duration(milliseconds: 120),
      memCacheWidth: _memCacheWidth,
      maxWidthDiskCache: 1400,
      maxHeightDiskCache: 1400,
      filterQuality: FilterQuality.low,
      placeholder: (_, _) => placeholder ?? _defaultPlaceholder(width, height),
      errorWidget: (_, _, _) =>
          onError?.call() ?? error ?? _defaultError(width, height),
    );
  }

  static Widget _defaultPlaceholder(double? width, double? height) {
    return Container(
      width: width,
      height: height,
      color: AppTheme.primarySurface,
    );
  }

  static Widget _defaultError(double? width, double? height) {
    return Container(
      width: width,
      height: height,
      color: AppTheme.primarySurface,
      alignment: Alignment.center,
      child: Icon(
        Icons.image_not_supported_outlined,
        color: AppTheme.mutedText,
        size: (width != null && width < 48) ? 18 : 28,
      ),
    );
  }
}
