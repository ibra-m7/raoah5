import 'dart:async';
import 'dart:ui' as ui;

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
  final _searchFocus = FocusNode();

  LatLng _point =
      LatLng(DeviceLocation.riyadh.latitude, DeviceLocation.riyadh.longitude);
  DeviceFix? _fix;
  List<PlaceSuggestion> _suggestions = const [];
  bool _searchOpen = false;
  bool _locating = false;
  bool _resolvingPlace = false;
  bool _saving = false;
  bool _mapReady = false;
  bool _programmaticMove = false;
  String? _status;
  Timer? _suggestDebounce;
  int _suggestSeq = 0;
  int _reverseSeq = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance
        .addPostFrameCallback((_) => _goToMyLocation(silent: true));
  }

  @override
  void dispose() {
    _suggestDebounce?.cancel();
    _searchCtrl.dispose();
    _nameCtrl.dispose();
    _detailsCtrl.dispose();
    _searchFocus.dispose();
    _mapController.dispose();
    super.dispose();
  }

  String get _placeLabel {
    final fix = _fix;
    if (fix == null) return '';
    final preview = fix.preview.trim();
    if (preview.isNotEmpty) return preview;
    return fix.city ?? '';
  }

  Future<void> _applyFix(DeviceFix fix, {bool moveMap = true}) async {
    setState(() {
      _fix = fix;
      _point = LatLng(fix.latitude, fix.longitude);
      _status = null;
      _suggestions = const [];
      _resolvingPlace = false;
    });
    if (moveMap && _mapReady) {
      _programmaticMove = true;
      _mapController.move(_point, 16.4);
      _programmaticMove = false;
    }
  }

  Future<void> _goToMyLocation({bool silent = false}) async {
    setState(() {
      _locating = true;
      _resolvingPlace = true;
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
      if (mounted) {
        setState(() {
          _locating = false;
          _resolvingPlace = false;
        });
      }
    }
  }

  void _openSearch() {
    setState(() => _searchOpen = true);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _searchFocus.requestFocus();
    });
  }

  void _closeSearch() {
    _suggestDebounce?.cancel();
    _searchCtrl.clear();
    _searchFocus.unfocus();
    setState(() {
      _searchOpen = false;
      _suggestions = const [];
    });
  }

  void _onSearchChanged(String value) {
    setState(() {});
    _suggestDebounce?.cancel();
    final query = value.trim();
    if (query.length < 2) {
      setState(() => _suggestions = const []);
      return;
    }
    _suggestDebounce = Timer(const Duration(milliseconds: 350), () {
      _loadSuggestions(query);
    });
  }

  Future<void> _loadSuggestions(String query) async {
    final seq = ++_suggestSeq;
    try {
      final items = await DeviceLocation.suggest(query);
      if (!mounted || seq != _suggestSeq) return;
      setState(() => _suggestions = items);
    } catch (_) {
      if (!mounted || seq != _suggestSeq) return;
      setState(() => _suggestions = const []);
    }
  }

  Future<void> _selectSuggestion(PlaceSuggestion suggestion) async {
    _suggestDebounce?.cancel();
    _searchCtrl.text = suggestion.title;
    _searchFocus.unfocus();
    setState(() {
      _suggestions = const [];
      _searchOpen = false;
      _resolvingPlace = true;
    });
    try {
      final fix = await DeviceLocation.reverse(
        suggestion.latitude,
        suggestion.longitude,
      );
      if (!mounted) return;
      await _applyFix(fix);
    } catch (_) {
      if (mounted) {
        setState(() => _status = 'تعذّر تحديد هذا المكان.');
      }
    } finally {
      if (mounted) setState(() => _resolvingPlace = false);
    }
  }

  Future<void> _search() async {
    final query = _searchCtrl.text.trim();
    if (query.isEmpty) return;
    _suggestDebounce?.cancel();
    _searchFocus.unfocus();
    setState(() {
      _resolvingPlace = true;
      _suggestions = const [];
      _searchOpen = false;
    });
    try {
      final fix = await DeviceLocation.search(query);
      if (!mounted) return;
      if (fix == null) {
        setState(() =>
            _status = 'لم نجد هذا المكان. جرّب اسم حي أو شارع أوضح.');
        return;
      }
      await _applyFix(fix);
    } catch (_) {
      if (mounted) {
        setState(() => _status = 'تعذّر البحث عن هذا المكان.');
      }
    } finally {
      if (mounted) setState(() => _resolvingPlace = false);
    }
  }

  Future<void> _resolveCenter(LatLng point) async {
    final seq = ++_reverseSeq;
    setState(() {
      _point = point;
      _resolvingPlace = true;
    });
    try {
      final fix = await DeviceLocation.reverse(point.latitude, point.longitude);
      if (!mounted || seq != _reverseSeq) return;
      setState(() {
        _fix = fix;
        _status = null;
      });
    } catch (_) {
      if (!mounted || seq != _reverseSeq) return;
      setState(() {
        _fix = DeviceFix(
          latitude: point.latitude,
          longitude: point.longitude,
          city: 'السعودية',
        );
      });
    } finally {
      if (mounted && seq == _reverseSeq) {
        setState(() => _resolvingPlace = false);
      }
    }
  }

  void _onMapEvent(MapEvent event) {
    if (_programmaticMove) return;
    if (event is MapEventMoveEnd ||
        event is MapEventFlingAnimationEnd ||
        event is MapEventDoubleTapZoomEnd) {
      _resolveCenter(event.camera.center);
    }
  }

  Future<void> _onMapTap(LatLng point) async {
    if (!_mapReady) return;
    _programmaticMove = true;
    _mapController.move(point, _mapController.camera.zoom);
    _programmaticMove = false;
    await _resolveCenter(point);
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
    final topInset = MediaQuery.paddingOf(context).top;
    const sheetRadius = BorderRadius.vertical(top: Radius.circular(26));

    return Directionality(
      textDirection: TextDirection.rtl,
      child: AnnotatedRegion<SystemUiOverlayStyle>(
        value: SystemUiOverlayStyle.dark,
        child: Scaffold(
          backgroundColor: Colors.transparent,
          body: Stack(
            fit: StackFit.expand,
            children: [
              Positioned.fill(
                child: FlutterMap(
                  mapController: _mapController,
                  options: MapOptions(
                    initialCenter: _point,
                    initialZoom: 15.2,
                    interactionOptions: const InteractionOptions(
                      flags: InteractiveFlag.all,
                    ),
                    onMapReady: () {
                      _mapReady = true;
                      _programmaticMove = true;
                      _mapController.move(_point, 15.2);
                      _programmaticMove = false;
                    },
                    onMapEvent: _onMapEvent,
                    onTap: (_, point) => _onMapTap(point),
                  ),
                  children: [
                    TileLayer(
                      urlTemplate:
                          'https://mt{s}.google.com/vt/lyrs=m&hl=ar&x={x}&y={y}&z={z}',
                      subdomains: const ['0', '1', '2', '3'],
                      userAgentPackageName: 'com.raoah.raoah_alkhamsa',
                      maxNativeZoom: 20,
                    ),
                  ],
                ),
              ),
              Column(
                children: [
                  Expanded(
                    child: Stack(
                      children: [
                        const IgnorePointer(
                          child: Center(
                            child: Padding(
                              padding: EdgeInsets.only(bottom: 62),
                              child: _BrandMapPin(),
                            ),
                          ),
                        ),
                        Positioned(
                          top: topInset + 10,
                          right: 14,
                          left: 14,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Row(
                                children: [
                                  _RoundMapButton(
                                    icon: Icons.chevron_right_rounded,
                                    onTap: () => Navigator.of(context).pop(),
                                  ),
                                  const SizedBox(width: 8),
                                  if (!_searchOpen)
                                    _RoundMapButton(
                                      icon: Icons.search_rounded,
                                      onTap: _openSearch,
                                    )
                                  else
                                    Expanded(
                                      child: Material(
                                        elevation: 6,
                                        shadowColor: Colors.black26,
                                        borderRadius: BorderRadius.circular(18),
                                        color: Colors.white,
                                        clipBehavior: Clip.antiAlias,
                                        child: TextField(
                                          controller: _searchCtrl,
                                          focusNode: _searchFocus,
                                          textInputAction: TextInputAction.search,
                                          onSubmitted: (_) => _search(),
                                          style: const TextStyle(fontSize: 13.5),
                                          decoration: InputDecoration(
                                            hintText: AppStrings.deliverySearch,
                                            hintStyle: const TextStyle(fontSize: 12.5),
                                            prefixIcon: IconButton(
                                              onPressed: _search,
                                              icon: const Icon(
                                                Icons.search_rounded,
                                                size: 20,
                                              ),
                                            ),
                                            suffixIcon: IconButton(
                                              onPressed: _closeSearch,
                                              icon: const Icon(
                                                Icons.close_rounded,
                                                size: 18,
                                              ),
                                            ),
                                            border: InputBorder.none,
                                            contentPadding:
                                                const EdgeInsets.symmetric(
                                              horizontal: 10,
                                              vertical: 12,
                                            ),
                                          ),
                                          onChanged: _onSearchChanged,
                                        ),
                                      ),
                                    ),
                                ],
                              ),
                              if (_searchOpen && _suggestions.isNotEmpty) ...[
                                const SizedBox(height: 6),
                                Material(
                                  elevation: 6,
                                  shadowColor: Colors.black26,
                                  borderRadius: BorderRadius.circular(14),
                                  color: Colors.white,
                                  clipBehavior: Clip.antiAlias,
                                  child: ConstrainedBox(
                                    constraints:
                                        const BoxConstraints(maxHeight: 220),
                                    child: ListView.separated(
                                      padding: const EdgeInsets.symmetric(
                                        vertical: 4,
                                      ),
                                      shrinkWrap: true,
                                      itemCount: _suggestions.length,
                                      separatorBuilder: (_, __) => const Divider(
                                        height: 1,
                                        color: Color(0xFFE8EEEA),
                                      ),
                                      itemBuilder: (context, index) {
                                        final item = _suggestions[index];
                                        return InkWell(
                                          onTap: () => _selectSuggestion(item),
                                          child: Padding(
                                            padding: const EdgeInsets.symmetric(
                                              horizontal: 12,
                                              vertical: 9,
                                            ),
                                            child: Row(
                                              children: [
                                                const Icon(
                                                  Icons.place_outlined,
                                                  size: 16,
                                                  color: AppTheme.primaryDark,
                                                ),
                                                const SizedBox(width: 8),
                                                Expanded(
                                                  child: Column(
                                                    crossAxisAlignment:
                                                        CrossAxisAlignment.start,
                                                    children: [
                                                      Text(
                                                        item.title,
                                                        maxLines: 1,
                                                        overflow:
                                                            TextOverflow.ellipsis,
                                                        style: const TextStyle(
                                                          fontSize: 12,
                                                          fontWeight:
                                                              FontWeight.w700,
                                                          color:
                                                              AppTheme.darkText,
                                                        ),
                                                      ),
                                                      if (item.subtitle
                                                          .isNotEmpty) ...[
                                                        const SizedBox(height: 2),
                                                        Text(
                                                          item.subtitle,
                                                          maxLines: 2,
                                                          overflow: TextOverflow
                                                              .ellipsis,
                                                          style: const TextStyle(
                                                            fontSize: 10.5,
                                                            height: 1.25,
                                                            fontWeight:
                                                                FontWeight.w500,
                                                            color: AppTheme
                                                                .mutedText,
                                                          ),
                                                        ),
                                                      ],
                                                    ],
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        );
                                      },
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        Positioned(
                          right: 14,
                          bottom: 18,
                          child: _MyLocationCard(
                            loading: _locating,
                            onTap: _locating ? null : () => _goToMyLocation(),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Material(
                    color: Colors.transparent,
                    elevation: 12,
                    shadowColor: const Color(0x33000000),
                    borderRadius: sheetRadius,
                    clipBehavior: Clip.antiAlias,
                    child: ClipRRect(
                      borderRadius: sheetRadius,
                      child: ColoredBox(
                        color: Colors.white,
                        child: SafeArea(
                          top: false,
                          child: Padding(
                            padding: const EdgeInsets.fromLTRB(18, 18, 18, 16),
                            child: Form(
                              key: _formKey,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.stretch,
                                children: [
                                  _ResolvedPlaceBanner(
                                    loading: _resolvingPlace,
                                    label: _placeLabel,
                                  ),
                                  const SizedBox(height: 12),
                                  _AddressField(
                                    controller: _nameCtrl,
                                    hint: AppStrings.deliveryNameHint,
                                    icon: Icons.location_on_outlined,
                                    validator: (value) {
                                      if (value == null ||
                                          value.trim().isEmpty) {
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
                                      if (value == null ||
                                          value.trim().isEmpty) {
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
                                  Center(
                                    child: ConstrainedBox(
                                      constraints: const BoxConstraints(
                                        maxWidth: 180,
                                        minWidth: 140,
                                      ),
                                      child: SizedBox(
                                        height: 42,
                                        width: double.infinity,
                                        child: OutlinedButton(
                                          onPressed: _saving ? null : _save,
                                          style: OutlinedButton.styleFrom(
                                            backgroundColor: Colors.white,
                                            foregroundColor:
                                                AppTheme.primaryDark,
                                            side: const BorderSide(
                                              color: AppTheme.primaryDark,
                                              width: 1.4,
                                            ),
                                            shape: RoundedRectangleBorder(
                                              borderRadius:
                                                  BorderRadius.circular(14),
                                            ),
                                            textStyle: const TextStyle(
                                              fontSize: 14,
                                              fontWeight: FontWeight.w800,
                                            ),
                                          ),
                                          child: _saving
                                              ? const SizedBox(
                                                  width: 18,
                                                  height: 18,
                                                  child:
                                                      CircularProgressIndicator(
                                                    strokeWidth: 2.2,
                                                    color: AppTheme.primaryDark,
                                                  ),
                                                )
                                              : const Text(
                                                  AppStrings.deliverySave,
                                                ),
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _RoundMapButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;

  const _RoundMapButton({
    required this.icon,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      elevation: 4,
      shadowColor: Colors.black26,
      borderRadius: BorderRadius.circular(14),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: SizedBox(
          width: 40,
          height: 40,
          child: Icon(
            icon,
            size: 22,
            color: AppTheme.darkText,
            textDirection: TextDirection.ltr,
          ),
        ),
      ),
    );
  }
}

class _MyLocationCard extends StatelessWidget {
  final bool loading;
  final VoidCallback? onTap;

  const _MyLocationCard({
    required this.loading,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: BackdropFilter(
        filter: ui.ImageFilter.blur(sigmaX: 14, sigmaY: 14),
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(12),
            child: Container(
              width: 64,
              padding: const EdgeInsets.fromLTRB(6, 8, 6, 7),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.42),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: AppTheme.primaryDark.withValues(alpha: 0.85),
                  width: 1.15,
                ),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (loading)
                    const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.black,
                      ),
                    )
                  else
                    const Icon(
                      Icons.my_location_rounded,
                      size: 22,
                      color: Colors.black,
                    ),
                  const SizedBox(height: 4),
                  const Text(
                    'موقعي',
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w800,
                      color: Colors.black,
                      height: 1,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _ResolvedPlaceBanner extends StatelessWidget {
  final bool loading;
  final String label;

  const _ResolvedPlaceBanner({
    required this.loading,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const Text(
          'الموقع',
          style: TextStyle(
            fontSize: 12.5,
            fontWeight: FontWeight.w800,
            color: AppTheme.darkText,
          ),
        ),
        const SizedBox(height: 4),
        if (loading)
          const Align(
            alignment: AlignmentDirectional.centerStart,
            child: _ThreeDotsLoader(),
          )
        else
          Text(
            label.isEmpty ? 'حرّك الخريطة لتحديد الموقع' : label,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: label.isEmpty ? AppTheme.mutedText : AppTheme.bodyText,
              height: 1.35,
            ),
          ),
      ],
    );
  }
}

class _ThreeDotsLoader extends StatefulWidget {
  const _ThreeDotsLoader();

  @override
  State<_ThreeDotsLoader> createState() => _ThreeDotsLoaderState();
}

class _ThreeDotsLoaderState extends State<_ThreeDotsLoader>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, _) {
        return Row(
          mainAxisSize: MainAxisSize.min,
          children: List.generate(3, (index) {
            final start = index * 0.2;
            final t = ((_controller.value - start) % 1.0).clamp(0.0, 1.0);
            final scale = 0.55 + (0.45 * (1 - (t - 0.5).abs() * 2).clamp(0.0, 1.0));
            final opacity = 0.35 + (0.65 * scale);
            return Padding(
              padding: EdgeInsetsDirectional.only(end: index == 2 ? 0 : 5),
              child: Opacity(
                opacity: opacity,
                child: Transform.scale(
                  scale: scale,
                  child: Container(
                    width: 7,
                    height: 7,
                    decoration: const BoxDecoration(
                      color: AppTheme.primaryDark,
                      shape: BoxShape.circle,
                    ),
                  ),
                ),
              ),
            );
          }),
        );
      },
    );
  }
}

class _BrandMapPin extends StatelessWidget {
  const _BrandMapPin();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 34,
      height: 62,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 26,
            height: 26,
            decoration: BoxDecoration(
              color: AppTheme.primaryDark,
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 2.6),
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primaryDark.withValues(alpha: 0.35),
                  blurRadius: 8,
                  offset: const Offset(0, 3),
                ),
              ],
            ),
            child: const Center(
              child: Icon(
                Icons.circle,
                color: Colors.white,
                size: 8,
              ),
            ),
          ),
          CustomPaint(
            size: const Size(12, 16),
            painter: _PinTipPainter(color: AppTheme.primaryDark),
          ),
        ],
      ),
    );
  }
}

class _PinTipPainter extends CustomPainter {
  final Color color;

  const _PinTipPainter({required this.color});

  @override
  void paint(Canvas canvas, Size size) {
    final path = ui.Path()
      ..moveTo(0, 0)
      ..lineTo(size.width / 2, size.height)
      ..lineTo(size.width, 0)
      ..close();
    canvas.drawPath(path, Paint()..color = color);
  }

  @override
  bool shouldRepaint(covariant _PinTipPainter oldDelegate) {
    return oldDelegate.color != color;
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
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        errorMaxLines: 2,
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: const BorderSide(color: Color(0xFFE0E6E2)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide:
              const BorderSide(color: AppTheme.primaryDark, width: 1.4),
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
