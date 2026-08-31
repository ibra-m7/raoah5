import 'package:flutter/material.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_scale.dart';
import '../../../../core/theme/app_theme.dart';
import '../../data/models/bundle_model.dart';
import 'auto_scroll_horizontal_list.dart';
import 'bundle_card.dart';
import 'home_section_shell.dart';

/// قسم عرض السلات على الصفحة الرئيسية — نفس هيكل الأقسام المنحنية.
class BundleBannerSection extends StatelessWidget {
  final String title;
  final String? subtitle;
  final List<BundleModel> bundles;
  final List<Color> gradientColors;
  final String? backgroundImageUrl;
  final Color? titleColor;
  final Color? subtitleColor;
  final bool curveTop;
  final bool curveBottom;
  final bool autoScrollCards;
  final VoidCallback? onViewAll;

  const BundleBannerSection({
    super.key,
    required this.title,
    this.subtitle,
    required this.bundles,
    required this.gradientColors,
    this.backgroundImageUrl,
    this.titleColor,
    this.subtitleColor,
    this.curveTop = false,
    this.curveBottom = true,
    this.autoScrollCards = false,
    this.onViewAll,
  });

  @override
  Widget build(BuildContext context) {
    if (bundles.isEmpty) return const SizedBox.shrink();

    final scale = AppScale.of(context);
    final uniqueBundles = <String, BundleModel>{};
    for (final bundle in bundles) {
      uniqueBundles.putIfAbsent(bundle.id, () => bundle);
    }
    final items = uniqueBundles.values.toList(growable: false);
    final cardW = scale.productCardWidth * 1.08;
    final listHeight = cardW * 1.48;
    final useAutoScroll = autoScrollCards && items.length >= 2;

    return HomeSectionShell(
      gradientColors: gradientColors,
      backgroundImageUrl: backgroundImageUrl,
      curveTop: curveTop,
      curveBottom: curveBottom,
      child: Padding(
        padding: EdgeInsets.only(top: scale.s(20), bottom: scale.s(18)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: EdgeInsets.symmetric(horizontal: scale.pagePad),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                                fontSize: scale.s(16),
                                fontWeight: FontWeight.w900,
                                color: titleColor ?? AppTheme.primaryDark,
                              ),
                        ),
                        if (subtitle != null && subtitle!.isNotEmpty) ...[
                          const SizedBox(height: 3),
                          Text(
                            subtitle!,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                                  fontSize: 11,
                                  color: subtitleColor ?? AppTheme.mutedText,
                                  fontWeight: FontWeight.w600,
                                ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  if (onViewAll != null)
                    TextButton(
                      onPressed: onViewAll,
                      style: TextButton.styleFrom(
                        foregroundColor: titleColor ?? AppTheme.primaryDark,
                        minimumSize: Size.zero,
                        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            AppStrings.homeShowAll,
                            style: Theme.of(context).textTheme.labelLarge?.copyWith(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w700,
                                  color: AppTheme.primary,
                                ),
                          ),
                          const Icon(
                            Icons.chevron_right_rounded,
                            size: 15,
                            color: AppTheme.primary,
                          ),
                        ],
                      ),
                    ),
                ],
              ),
            ),
            SizedBox(height: scale.s(16)),
            if (useAutoScroll)
              AutoScrollHorizontalList(
                height: listHeight,
                itemWidth: cardW,
                gap: scale.s(10),
                padding: EdgeInsets.symmetric(horizontal: scale.pagePad),
                itemCount: items.length,
                itemBuilder: (_, i) => BundleCard(
                  bundle: items[i],
                  width: cardW,
                ),
              )
            else
              SizedBox(
                height: listHeight,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  physics: const BouncingScrollPhysics(),
                  padding: EdgeInsets.symmetric(horizontal: scale.pagePad),
                  itemCount: items.length,
                  separatorBuilder: (_, _) => SizedBox(width: scale.s(10)),
                  itemBuilder: (_, i) => SizedBox(
                    height: listHeight,
                    child: BundleCard(
                      bundle: items[i],
                      width: cardW,
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
