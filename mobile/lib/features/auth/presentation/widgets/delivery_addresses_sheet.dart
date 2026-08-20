import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/theme/app_theme.dart';
import '../../../../core/widgets/marquee_text.dart';
import '../../data/models/delivery_address.dart';
import '../../data/services/auth_session.dart';
import '../auth_flow.dart';
import '../manager/address_cubit.dart';
import '../pages/add_address_screen.dart';

class DeliveryAddressesSheet {
  static Future<void> show(BuildContext context) async {
    if (!AuthSession.instance.isLoggedIn) {
      await AuthFlow.requireLogin(
        context,
        message: AppStrings.guestAddressMessage,
      );
      return;
    }
    final cubit = context.read<AddressCubit>()..load();
    return showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => BlocProvider.value(
        value: cubit,
        child: const _DeliveryAddressesBody(),
      ),
    );
  }
}

class _DeliveryAddressesBody extends StatelessWidget {
  const _DeliveryAddressesBody();

  @override
  Widget build(BuildContext context) {
    final height = MediaQuery.sizeOf(context).height * 0.78;
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Container(
        height: height,
        decoration: const BoxDecoration(
          color: Color(0xFFF7FBF8),
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: Column(
          children: [
            const SizedBox(height: 10),
            Container(
              width: 46,
              height: 5,
              decoration: BoxDecoration(
                color: const Color(0xFFE8C9B0),
                borderRadius: BorderRadius.circular(20),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(22, 18, 22, 8),
              child: Align(
                alignment: Alignment.centerRight,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: const [
                    Text(
                      AppStrings.deliveryTo,
                      style: TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.w900,
                        color: AppTheme.darkText,
                      ),
                    ),
                    SizedBox(height: 4),
                    Text(
                      AppStrings.deliveryChooseAddress,
                      style: TextStyle(
                        fontSize: 13.5,
                        color: AppTheme.mutedText,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Expanded(
              child: BlocBuilder<AddressCubit, AddressState>(
                builder: (context, state) {
                  if (state.loading && state.addresses.isEmpty) {
                    return const Center(child: CircularProgressIndicator());
                  }
                  if (state.addresses.isEmpty) {
                    return const Center(
                      child: Text(
                        AppStrings.deliveryEmpty,
                        style: TextStyle(color: AppTheme.mutedText),
                      ),
                    );
                  }
                  return ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                    itemCount: state.addresses.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (context, index) {
                      final address = state.addresses[index];
                      return _AddressCard(
                        address: address,
                        busy: state.busy,
                        onOpen: () => _openDetails(context, address),
                        onDelete: () => _confirmDelete(context, address),
                      );
                    },
                  );
                },
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
              child: SafeArea(
                top: false,
                child: SizedBox(
                  width: double.infinity,
                  height: 54,
                  child: FilledButton(
                    onPressed: () async {
                      final added = await Navigator.of(context).pushNamed(
                        AddAddressScreen.routeName,
                      );
                      if (added == true && context.mounted) {
                        context.read<AddressCubit>().load();
                      }
                    },
                    style: FilledButton.styleFrom(
                      backgroundColor: AppTheme.primaryLight,
                      foregroundColor: AppTheme.primaryDark,
                      elevation: 0,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    child: const Text(
                      AppStrings.deliveryAddNew,
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _openDetails(BuildContext context, DeliveryAddress address) async {
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => BlocProvider.value(
        value: context.read<AddressCubit>(),
        child: _AddressDetailsSheet(address: address),
      ),
    );
  }

  Future<void> _confirmDelete(BuildContext context, DeliveryAddress address) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text(AppStrings.deliveryDeleteConfirm),
        content: const Text(AppStrings.deliveryDeleteBody),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text(AppStrings.cancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: FilledButton.styleFrom(backgroundColor: Colors.redAccent),
            child: const Text(AppStrings.delete),
          ),
        ],
      ),
    );
    if (ok != true || !context.mounted) return;
    try {
      await context.read<AddressCubit>().remove(address.id);
    } catch (_) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(
            context.read<AddressCubit>().state.error ?? 'تعذّر الحذف',
            textAlign: TextAlign.center,
          ),
        ));
      }
    }
  }
}

class _AddressCard extends StatelessWidget {
  final DeliveryAddress address;
  final bool busy;
  final VoidCallback onOpen;
  final VoidCallback onDelete;

  const _AddressCard({
    required this.address,
    required this.busy,
    required this.onOpen,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      elevation: address.isDefault ? 3 : 1.2,
      shadowColor: Colors.black12,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: onOpen,
        borderRadius: BorderRadius.circular(18),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
          child: Row(
            children: [
              Container(
                width: 52,
                height: 52,
                decoration: const BoxDecoration(
                  color: AppTheme.primary,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.location_on_rounded, color: Colors.white),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            address.label,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 16,
                              color: AppTheme.darkText,
                            ),
                          ),
                        ),
                        if (address.isDefault) ...[
                          const SizedBox(width: 6),
                          const Text(
                            'الحالي',
                            style: TextStyle(
                              color: AppTheme.primaryDark,
                              fontWeight: FontWeight.w800,
                              fontSize: 11,
                            ),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 4),
                    SizedBox(
                      height: 20,
                      child: MarqueeText(
                        text: address.subtitle.isEmpty
                            ? 'بدون وصف إضافي'
                            : address.subtitle,
                        style: const TextStyle(
                          color: AppTheme.mutedText,
                          fontSize: 12.5,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                onPressed: busy ? null : onDelete,
                icon: const Icon(Icons.delete_outline_rounded, color: Color(0xFFE53935)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AddressDetailsSheet extends StatelessWidget {
  final DeliveryAddress address;

  const _AddressDetailsSheet({required this.address});

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Container(
        padding: const EdgeInsets.fromLTRB(22, 12, 22, 24),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Center(
                child: Container(
                  width: 46,
                  height: 5,
                  decoration: BoxDecoration(
                    color: const Color(0xFFE0E6E2),
                    borderRadius: BorderRadius.circular(20),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              const Text(
                AppStrings.deliveryCurrentDetails,
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w900,
                  color: AppTheme.darkText,
                ),
              ),
              const SizedBox(height: 16),
              _detailRow('اسم الموقع', address.label),
              _detailRow('الوصف', address.subtitle.isEmpty ? '—' : address.subtitle),
              if (address.city != null) _detailRow('المدينة', address.city!),
              if (address.latitude != null && address.longitude != null)
                _detailRow(
                  'الإحداثيات',
                  '${address.latitude!.toStringAsFixed(5)} , ${address.longitude!.toStringAsFixed(5)}',
                ),
              _detailRow('عدد الطلبات من هنا', '${address.ordersCount}'),
              const SizedBox(height: 18),
              SizedBox(
                height: 52,
                child: FilledButton(
                  onPressed: address.isDefault
                      ? () => Navigator.pop(context)
                      : () async {
                          await context.read<AddressCubit>().select(address.id);
                          if (context.mounted) {
                            Navigator.pop(context);
                            Navigator.pop(context);
                          }
                        },
                  style: FilledButton.styleFrom(
                    backgroundColor: AppTheme.primaryDark,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                  child: Text(
                    address.isDefault
                        ? 'هذا هو عنوان التوصيل الحالي'
                        : AppStrings.deliveryUseThis,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _detailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(
              label,
              style: const TextStyle(color: AppTheme.mutedText, fontSize: 13),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
                color: AppTheme.darkText,
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
