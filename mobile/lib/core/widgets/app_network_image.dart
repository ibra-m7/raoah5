import 'package:flutter/material.dart';

import '../theme/app_theme.dart';

/// صورة من الشبكة مع placeholder عند التحميل أو الخطأ.
class AppNetworkImage extends StatelessWidget {
  final String url;
  final BoxFit fit;
  final double? width;
  final double? height;
  final Widget? placeholder;
  final Widget? error;

  const AppNetworkImage(
    this.url, {
    super.key,
    this.fit = BoxFit.cover,
    this.width,
    this.height,
    this.placeholder,
    this.error,
  });

  static const _headers = {
    'User-Agent':
        'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36',
    'Accept': 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
  };

  /// Unsplash يقطع اتصال HttpClient الخاص بفلاتر؛ الوكيل يمرّر الصورة كـ JPEG.
  static String resolveUrl(String raw) {
    final url = raw.trim();
    if (url.isEmpty) return url;
    final uri = Uri.tryParse(url);
    if (uri == null || !uri.hasScheme) return url;
    final host = uri.host.toLowerCase();
    if (host == 'images.unsplash.com' || host == 'unsplash.com') {
      return Uri.https('wsrv.nl', '/', {
        'url': url,
        'w': '800',
        'q': '80',
        'output': 'jpg',
      }).toString();
    }
    return url;
  }

  @override
  Widget build(BuildContext context) {
    if (url.trim().isEmpty) {
      return error ?? _defaultError(width, height);
    }

    return Image.network(
      resolveUrl(url),
      fit: fit,
      width: width,
      height: height,
      headers: _headers,
      gaplessPlayback: true,
      filterQuality: FilterQuality.medium,
      loadingBuilder: (context, child, progress) {
        if (progress == null) return child;
        return placeholder ?? _defaultPlaceholder(width, height);
      },
      errorBuilder: (_, _, _) => error ?? _defaultError(width, height),
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
