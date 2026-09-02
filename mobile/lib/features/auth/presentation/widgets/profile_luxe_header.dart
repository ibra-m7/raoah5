import 'package:flutter/material.dart';

import '../../../../core/theme/app_theme.dart';
import 'profile_luxe.dart';

/// هيدر موحّد لصفحة الحساب والمعلومات الشخصية — أفاتار + موجة.
class ProfileLuxeHeader extends StatelessWidget {
  static const double avatarSize = 82;
  static const double avatarBoxHeight = 86;
  static const double waveHeight = kLuxeProfileWaveHeight;
  static const double waveGap = 0;

  static double contentHeight({
    required double avatarBoxHeight,
    required double waveHeight,
  }) =>
      kLuxeHeaderTopPad +
      avatarBoxHeight +
      kLuxeHeaderAvatarNameGap +
      kLuxeHeaderNameBlock +
      waveHeight;

  final String name;
  final String? phone;
  final String? avatarUrl;
  final Animation<double>? entrance;
  final VoidCallback? onEditTap;
  final bool showBackButton;

  const ProfileLuxeHeader({
    super.key,
    required this.name,
    this.phone,
    this.avatarUrl,
    this.entrance,
    this.onEditTap,
    this.showBackButton = false,
  });

  @override
  Widget build(BuildContext context) {
    final topInset = MediaQuery.paddingOf(context).top;
    final fade = entrance != null
        ? CurvedAnimation(
            parent: entrance!,
            curve: const Interval(0, 0.55, curve: Curves.easeOut),
          )
        : const AlwaysStoppedAnimation(1.0);
    final rise = entrance != null
        ? Tween<double>(begin: 12, end: 0).animate(fade)
        : const AlwaysStoppedAnimation(0.0);

    return SizedBox(
      height: topInset +
          contentHeight(
            avatarBoxHeight: avatarBoxHeight,
            waveHeight: waveHeight,
          ),
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          const Positioned.fill(child: ColoredBox(color: Colors.white)),

          const Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            height: waveHeight,
            child: CustomPaint(painter: LuxeWavePainter()),
          ),

          if (showBackButton)
            Positioned(
              top: topInset + 4,
              right: 10,
              child: const ProfileLuxeBackButton(),
            ),

          Positioned(
            top: topInset + kLuxeHeaderTopPad,
            left: 0,
            right: 0,
            child: Center(
              child: ProfileLuxeAvatar(
                avatarUrl: avatarUrl,
                onEditTap: onEditTap,
              ),
            ),
          ),

          Positioned(
            left: 0,
            right: 0,
            bottom: waveHeight + waveGap,
            child: AnimatedBuilder(
              animation: fade,
              builder: (context, child) => Opacity(
                opacity: fade.value.clamp(0.0, 1.0),
                child: Transform.translate(
                  offset: Offset(0, rise.value),
                  child: child,
                ),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 32),
                    child: Text(
                      name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: AppTheme.darkText,
                        height: 1.2,
                      ),
                    ),
                  ),
                  if (phone != null && phone!.trim().isNotEmpty) ...[
                    const SizedBox(height: 3),
                    Text(
                      phone!,
                      textDirection: TextDirection.ltr,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.3,
                        color: AppTheme.mutedText.withValues(alpha: 0.88),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// زر رجوع صغير دائري.
class ProfileLuxeBackButton extends StatelessWidget {
  final double size;

  const ProfileLuxeBackButton({super.key, this.size = 32});

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      shape: const CircleBorder(),
      elevation: 2,
      shadowColor: kLuxeDeepA.withValues(alpha: 0.18),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: () => Navigator.of(context).maybePop(),
        child: SizedBox(
          width: size,
          height: size,
          child: Icon(
            Icons.chevron_right_rounded,
            size: size * 0.56,
            color: AppTheme.darkText,
            textDirection: TextDirection.ltr,
          ),
        ),
      ),
    );
  }
}

class ProfileLuxeAvatar extends StatelessWidget {
  final String? avatarUrl;
  final VoidCallback? onEditTap;

  const ProfileLuxeAvatar({
    super.key,
    this.avatarUrl,
    this.onEditTap,
  });

  @override
  Widget build(BuildContext context) {
    final hasImage = avatarUrl != null && avatarUrl!.trim().isNotEmpty;

    return SizedBox(
      width: ProfileLuxeHeader.avatarSize,
      height: ProfileLuxeHeader.avatarBoxHeight,
      child: Stack(
        clipBehavior: Clip.none,
        alignment: Alignment.topCenter,
        children: [
          Container(
            width: ProfileLuxeHeader.avatarSize,
            height: ProfileLuxeHeader.avatarSize,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: kLuxeDeepA.withValues(alpha: 0.14),
                  blurRadius: 14,
                  offset: const Offset(0, 5),
                ),
              ],
            ),
            padding: const EdgeInsets.all(2.5),
            child: ClipOval(
              child: hasImage
                  ? Image.network(
                      avatarUrl!,
                      fit: BoxFit.cover,
                      errorBuilder: (_, _, _) =>
                          const ProfileLuxeDefaultAvatar(),
                    )
                  : const ProfileLuxeDefaultAvatar(),
            ),
          ),
          if (onEditTap != null)
            Positioned(
              right: -1,
              bottom: 0,
              child: Material(
                color: Colors.white,
                shape: const CircleBorder(),
                elevation: 3,
                shadowColor: kLuxeDeepA.withValues(alpha: 0.28),
                child: InkWell(
                  onTap: onEditTap,
                  customBorder: const CircleBorder(),
                  child: const Padding(
                    padding: EdgeInsets.all(5.5),
                    child: Icon(
                      Icons.edit_outlined,
                      size: 13,
                      color: kLuxeDeepB,
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class ProfileLuxeDefaultAvatar extends StatelessWidget {
  const ProfileLuxeDefaultAvatar({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [AppTheme.primarySurface, AppTheme.primaryLight],
        ),
      ),
      alignment: Alignment.center,
      child: const Icon(
        Icons.person_rounded,
        size: 38,
        color: kLuxeDeepA,
      ),
    );
  }
}
