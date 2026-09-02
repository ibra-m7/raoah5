import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../shop/data/models/home_feed.dart';

Future<void> showCustomerServiceDialog(
  BuildContext context,
  List<CustomerServiceContact> contacts,
) {
  return showDialog<void>(
    context: context,
    barrierDismissible: true,
    builder: (dialogContext) => Directionality(
      textDirection: TextDirection.rtl,
      child: Dialog(
        insetPadding: const EdgeInsets.symmetric(horizontal: 42),
        backgroundColor: Colors.transparent,
        elevation: 0,
        child: _CustomerServiceDialogBody(contacts: contacts),
      ),
    ),
  );
}

class _CustomerServiceDialogBody extends StatelessWidget {
  final List<CustomerServiceContact> contacts;

  const _CustomerServiceDialogBody({required this.contacts});

  Future<void> _call(BuildContext context, String digits) async {
    final uri = Uri.parse('tel:+$digits');
    if (!await launchUrl(uri)) {
      if (!context.mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تعذّر فتح الاتصال')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: AppTheme.primaryLight.withValues(alpha: 0.9)),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primaryDark.withValues(alpha: 0.1),
            blurRadius: 24,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      padding: const EdgeInsets.fromLTRB(18, 16, 18, 14),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: AppTheme.primarySurface,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.headset_mic_rounded,
              color: AppTheme.primaryDark,
              size: 22,
            ),
          ),
          const SizedBox(height: 10),
          const Text(
            AppStrings.profileCustomerService,
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w900,
              color: AppTheme.darkText,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            AppStrings.profileCustomerServiceHint,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 11,
              height: 1.35,
              color: AppTheme.mutedText.withValues(alpha: 0.95),
            ),
          ),
          const SizedBox(height: 12),
          if (contacts.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(
                AppStrings.profileCustomerServiceEmpty,
                style: TextStyle(
                  fontSize: 11.5,
                  color: AppTheme.mutedText.withValues(alpha: 0.9),
                ),
              ),
            )
          else
            ...contacts.map(
              (contact) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Material(
                  color: AppTheme.primarySurface.withValues(alpha: 0.55),
                  borderRadius: BorderRadius.circular(14),
                  child: InkWell(
                    onTap: () => _call(context, contact.phoneDigits),
                    borderRadius: BorderRadius.circular(14),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 10,
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 30,
                            height: 30,
                            decoration: BoxDecoration(
                              color: AppTheme.surface,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(
                              Icons.phone_in_talk_rounded,
                              size: 16,
                              color: AppTheme.primaryDark,
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  contact.name,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.w800,
                                    color: AppTheme.darkText,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  contact.phone,
                                  textDirection: TextDirection.ltr,
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: AppTheme.mutedText
                                        .withValues(alpha: 0.95),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Icon(
                            Icons.chevron_left_rounded,
                            size: 18,
                            color: AppTheme.mutedText.withValues(alpha: 0.7),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          const SizedBox(height: 4),
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            style: TextButton.styleFrom(
              minimumSize: Size.zero,
              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              foregroundColor: AppTheme.primaryDark,
            ),
            child: const Text(
              AppStrings.close,
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
            ),
          ),
        ],
      ),
    );
  }
}
