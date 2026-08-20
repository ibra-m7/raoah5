import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/location/device_location.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/theme/app_theme.dart';
import '../manager/address_cubit.dart';

class AddAddressScreen extends StatefulWidget {
  static const routeName = '/add-address';

  const AddAddressScreen({super.key});

  @override
  State<AddAddressScreen> createState() => _AddAddressScreenState();
}

class _AddAddressScreenState extends State<AddAddressScreen> {
  final _formKey = GlobalKey<FormState>();
  final _mapController = MapController();
  final _searchCtrl = TextEditingController();
  final _nameCtrl = TextEditingController();
  final _detailsCtrl = TextEditingController();
  LatLng _point = LatLng(DeviceLocation.riyadh.latitude, DeviceLocation.riyadh.longitude);
  DeviceFix? _fix;
  bool _locating = false;
  bool _saving = false;
  bool _mapReady = false;
  String? _status;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _goToMyLocation(silent: true));
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _nameCtrl.dispose();
    _detailsCtrl.dispose();
    super.dispose();
  }

  Future<void> _applyFix(DeviceFix fix, {bool moveMap = true}) async {
    setState(() {
      _fix = fix;
      _point = LatLng(fix.latitude, fix.longitude);
      _status = null;
    });
    if (moveMap && _mapReady) {
      _mapController.move(_point, 16.4);
    }
  }

  Future<void> _goToMyLocation({bool silent = false}) async {
    setState(() {
      _locating = true;
      if (!silent) _status = null;
    });
    try {
      final fix = await DeviceLocation.current();
      if (!mounted) return;
      await _applyFix(fix);
    } on DeviceLocationException catch (e) {
      if (mounted && !silent) setState(() => _status = e.message);
    } catch (_) {
      if (mounted && !silent) {
        setState(() => _status = AppStrings.locationFailed);
      }
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  Future<void> _search() async {
    final query = _searchCtrl.text.trim();
    if (query.isEmpty) return;
    FocusScope.of(context).unfocus();
    setState(() => _locating = true);
    try {
      final fix = await DeviceLocation.search(query);
      if (!mounted) return;
      if (fix == null) {
        setState(() => _status = 'لم نجد هذا المكان. جرّب اسم حي أو شارع أوضح.');
        return;
      }
      await _applyFix(fix);
    } catch (_) {
      if (mounted) {
        setState(() => _status = 'تعذّر البحث عن هذا المكان.');
      }
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  Future<void> _onMapTap(LatLng point) async {
    setState(() {
      _point = point;
      _locating = true;
    });
    try {
      final fix = await DeviceLocation.reverse(point.latitude, point.longitude);
      if (!mounted) return;
      await _applyFix(fix, moveMap: false);
    } catch (_) {
      if (mounted) {
        setState(() {
          _fix = DeviceFix(
            latitude: point.latitude,
            longitude: point.longitude,
            city: 'السعودية',
          );
        });
      }
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  Future<void> _save() async {
    final valid = _formKey.currentState?.validate() ?? false;
    if (!valid) return;
    final name = _nameCtrl.text.trim();
    final details = _detailsCtrl.text.trim();
    final fix = _fix;
    if (fix == null) {
      _toast(AppStrings.deliveryPinRequired);
      return;
    }
    setState(() => _saving = true);
    try {
      await context.read<AddressCubit>().add(
            label: name,
            details: details,
            latitude: fix.latitude,
            longitude: fix.longitude,
            city: fix.city,
            district: fix.district,
            street: fix.street,
          );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (mounted) _toast(e.message);
    } catch (_) {
      if (mounted) _toast(AppStrings.errorUnknown);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _toast(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(message, textAlign: TextAlign.center),
      backgroundColor: AppTheme.darkText,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      margin: const EdgeInsets.all(16),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.dark,
        child: Scaffold(
          backgroundColor: Colors.white,
          appBar: AppBar(
            backgroundColor: Colors.white,
            surfaceTintColor: Colors.transparent,
            title: const Text(
              AppStrings.deliveryAddTitle,
              style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18),
            ),
            centerTitle: true,
          ),
          body: Column(
            children: [
              Expanded(
                child: Stack(
                  children: [
                    FlutterMap(
                      mapController: _mapController,
                      options: MapOptions(
                        initialCenter: _point,
                        initialZoom: 15.2,
                        onMapReady: () {
                          _mapReady = true;
                          _mapController.move(_point, 15.2);
                        },
                        onTap: (_, point) => _onMapTap(point),
                      ),
                      children: [
                        TileLayer(
                          urlTemplate:
                              'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}.png',
                          subdomains: const ['a', 'b', 'c', 'd'],
                          userAgentPackageName: 'com.raoah.raoah_alkhamsa',
                        ),
                        MarkerLayer(
                          markers: [
                            Marker(
                              point: _point,
                              width: 56,
                              height: 56,
                              alignment: Alignment.topCenter,
                              child: const Icon(
                                Icons.location_on_rounded,
                                color: Color(0xFFE53935),
                                size: 48,
                              ),
                            ),
                          ],
                        ),
                        const SimpleAttributionWidget(
                          source: Text('CARTO · OSM'),
                        ),
                      ],
                    ),
                    Positioned(
                      top: 12,
                      right: 16,
                      left: 16,
                      child: Material(
                        elevation: 8,
                        shadowColor: Colors.black26,
                        borderRadius: BorderRadius.circular(18),
                        child: TextField(
                          controller: _searchCtrl,
                          textInputAction: TextInputAction.search,
                          onSubmitted: (_) => _search(),
                          decoration: InputDecoration(
                            hintText: AppStrings.deliverySearch,
                            prefixIcon: IconButton(
                              onPressed: _search,
                              icon: const Icon(Icons.search_rounded),
                            ),
                            suffixIcon: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                if (_searchCtrl.text.isNotEmpty)
                                  IconButton(
                                    onPressed: () {
                                      _searchCtrl.clear();
                                      setState(() {});
                                    },
                                    icon: const Icon(Icons.close_rounded, size: 18),
                                  ),
                                IconButton(
                                  onPressed: _locating ? null : () => _goToMyLocation(),
                                  icon: _locating
                                      ? const SizedBox(
                                          width: 18,
                                          height: 18,
                                          child: CircularProgressIndicator(strokeWidth: 2),
                                        )
                                      : const Icon(Icons.gps_fixed_rounded),
                                ),
                              ],
                            ),
                            border: InputBorder.none,
                            contentPadding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 14,
                            ),
                          ),
                          onChanged: (_) => setState(() {}),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(20, 14, 20, 18),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  boxShadow: [
                    BoxShadow(
                      color: Color(0x14000000),
                      blurRadius: 18,
                      offset: Offset(0, -4),
                    ),
                  ],
                ),
                child: SafeArea(
                  top: false,
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Align(
                        alignment: Alignment.centerRight,
                        child: FilledButton.icon(
                          onPressed: _locating ? null : () => _goToMyLocation(),
                          icon: const Icon(Icons.near_me_rounded, size: 18),
                          label: const Text(AppStrings.deliveryMyLocation),
                          style: FilledButton.styleFrom(
                            backgroundColor: AppTheme.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 10,
                            ),
                            shape: const StadiumBorder(),
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      _AddressField(
                        controller: _nameCtrl,
                        hint: AppStrings.deliveryNameHint,
                        icon: Icons.location_on_outlined,
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return AppStrings.deliveryNameRequired;
                          }
                          if (value.trim().length < 2) {
                            return 'اسم العنوان قصير جداً';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 10),
                      _AddressField(
                        controller: _detailsCtrl,
                        hint: AppStrings.deliveryDescHint,
                        icon: Icons.apartment_outlined,
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return AppStrings.deliveryDescRequired;
                          }
                          if (value.trim().length < 3) {
                            return 'وصف العنوان قصير جداً';
                          }
                          return null;
                        },
                      ),
                      if (_status != null) ...[
                        const SizedBox(height: 8),
                        Text(
                          _status!,
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: Colors.red.shade700,
                            fontSize: 12.5,
                            height: 1.4,
                          ),
                        ),
                      ],
                      const SizedBox(height: 14),
                      SizedBox(
                        height: 54,
                        child: FilledButton(
                          onPressed: _saving ? null : _save,
                          style: FilledButton.styleFrom(
                            backgroundColor: AppTheme.primaryDark,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                          ),
                          child: _saving
                              ? const SizedBox(
                                  width: 22,
                                  height: 22,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2.4,
                                    color: Colors.white,
                                  ),
                                )
                              : const Text(
                                  AppStrings.deliverySave,
                                  style: TextStyle(
                                    fontSize: 17,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                        ),
                      ),
                    ],
                  ),
                ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _AddressField extends StatelessWidget {
  final TextEditingController controller;
  final String hint;
  final IconData icon;
  final String? Function(String?)? validator;

  const _AddressField({
    required this.controller,
    required this.hint,
    required this.icon,
    this.validator,
  });

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      autovalidateMode: AutovalidateMode.onUserInteraction,
      validator: validator,
      decoration: InputDecoration(
        hintText: hint,
        prefixIcon: Icon(icon, color: AppTheme.darkText),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        errorMaxLines: 2,
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: const BorderSide(color: Color(0xFFE0E6E2)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: const BorderSide(color: AppTheme.primaryDark, width: 1.4),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide(color: Colors.red.shade600),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide(color: Colors.red.shade600, width: 1.4),
        ),
      ),
    );
  }
}
