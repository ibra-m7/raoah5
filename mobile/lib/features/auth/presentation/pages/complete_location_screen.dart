import 'package:flutter/material.dart';
import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';

import '../../../../core/constants/app_strings.dart';
import '../../../../core/error/exceptions.dart';
import '../../../../core/network/api_exception.dart';
import '../../../../core/widgets/brand_logo.dart';
import '../../data/services/phone_auth_api.dart';
import '../auth_flow.dart';
import '../widgets/auth_widgets.dart';

class CompleteLocationScreen extends StatefulWidget {
  static const routeName = '/complete-location';

  const CompleteLocationScreen({super.key});

  @override
  State<CompleteLocationScreen> createState() => _CompleteLocationScreenState();
}

class _CompleteLocationScreenState extends State<CompleteLocationScreen> {
  final _formKey = GlobalKey<FormState>();
  final _detailsCtrl = TextEditingController();
  final _labelCtrl = TextEditingController();
  bool _locating = false;
  bool _saving = false;
  Position? _position;
  String? _city;
  String? _district;
  String? _street;
  String? _status;

  @override
  void dispose() {
    _detailsCtrl.dispose();
    _labelCtrl.dispose();
    super.dispose();
  }

  Future<void> _locate() async {
    setState(() {
      _locating = true;
      _status = null;
    });
    try {
      final enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) {
        setState(() => _status = AppStrings.locationServiceDisabled);
        await Geolocator.openLocationSettings();
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied) {
        setState(() => _status = AppStrings.locationPermissionDenied);
        return;
      }
      if (permission == LocationPermission.deniedForever) {
        setState(() => _status = AppStrings.locationPermissionDeniedForever);
        await Geolocator.openAppSettings();
        return;
      }

      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 20),
        ),

      );

      String? city;
      String? district;
      String? street;
      try {
        final marks = await placemarkFromCoordinates(
          position.latitude,
          position.longitude,
        );
        if (marks.isNotEmpty) {
          final place = marks.first;
          city = _firstNonEmpty([
            place.locality,
            place.subAdministrativeArea,
            place.administrativeArea,
          ]);
          district = _firstNonEmpty([
            place.subLocality,
            place.subAdministrativeArea,
          ]);
          street = _firstNonEmpty([place.street, place.thoroughfare]);
        }
      } catch (_) {
        city = 'السعودية';
      }

      setState(() {
        _position = position;
        _city = city ?? 'السعودية';
        _district = district;
        _street = street;
        _status = null;
      });
    } catch (_) {
      setState(() => _status = AppStrings.locationFailed);
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  String? _firstNonEmpty(List<String?> values) {
    for (final value in values) {
      if (value != null && value.trim().isNotEmpty) return value.trim();
    }
    return null;
  }

  Future<void> _save() async {
    final valid = _formKey.currentState?.validate() ?? false;
    if (!valid) return;
    final position = _position;
    if (position == null) {
      _showError(AppStrings.locationRequired);
      return;
    }
    setState(() => _saving = true);
    try {
      await PhoneAuthApi.instance.saveLocation(
        latitude: position.latitude,
        longitude: position.longitude,
        city: _city,
        district: _district,
        street: _street,
        details: _detailsCtrl.text.trim(),
        label: _labelCtrl.text.trim(),
      );
      if (!mounted) return;
      AuthFlow.returnToMain(context);
    } on ApiException catch (e) {
      if (mounted) _showError(e.message);
    } on NetworkException catch (e) {
      if (mounted) _showError(e.message);
    } on ServerException catch (e) {
      if (mounted) _showError(e.message);
    } catch (_) {
      if (mounted) _showError(AppStrings.errorUnknown);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg, textAlign: TextAlign.center),
      backgroundColor: Colors.red.shade700,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.all(16),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final hasFix = _position != null;
    final addressParts = <String>[
      if (_city != null && _city!.isNotEmpty) _city!,
      if (_district != null && _district!.isNotEmpty) _district!,
      if (_street != null && _street!.isNotEmpty) _street!,
    ];

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (didPop) return;
        AuthFlow.leaveAuth(context);
      },
      child: Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          const AuthGradientBackground(),
          SafeArea(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Column(
                children: [
                  Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: TextButton(
                      onPressed: () => AuthFlow.leaveAuth(context),
                      child: const Text(
                        AppStrings.completeLocationSkip,
                        style: TextStyle(
                          color: Colors.white70,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Hero(
                    tag: 'app_logo',
                    child: BrandLogoMark(size: 200),
                  ),
                  const SizedBox(height: 18),
                  const Text(
                    AppStrings.completeLocationTitle,
                    style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.w900,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    AppStrings.completeLocationSubtitle,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 15,
                      color: Colors.white.withValues(alpha: 0.75),
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 32),
                  AuthGlassCard(
                    child: Form(
                      key: _formKey,
                      child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: Colors.white.withValues(alpha: 0.16),
                            ),
                          ),
                          child: Column(
                            children: [
                              Icon(
                                hasFix
                                    ? Icons.location_on_rounded
                                    : Icons.location_searching_rounded,
                                color: const Color(0xFFA5D6A7),
                                size: 42,
                              ),
                              const SizedBox(height: 10),
                              Text(
                                hasFix
                                    ? (addressParts.isEmpty
                                        ? AppStrings.locationCaptured
                                        : addressParts.join('، '))
                                    : AppStrings.locationEmpty,
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 15,
                                  fontWeight: FontWeight.w700,
                                  height: 1.4,
                                ),
                              ),
                              if (hasFix) ...[
                                const SizedBox(height: 6),
                                Text(
                                  '${_position!.latitude.toStringAsFixed(5)} , ${_position!.longitude.toStringAsFixed(5)}',
                                  textDirection: TextDirection.ltr,
                                  style: TextStyle(
                                    color: Colors.white.withValues(alpha: 0.55),
                                    fontSize: 12,
                                  ),
                                ),
                              ],
                              if (_status != null) ...[
                                const SizedBox(height: 10),
                                Text(
                                  _status!,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    color: Color(0xFFEF9A9A),
                                    fontSize: 13,
                                    height: 1.4,
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        const SizedBox(height: 16),
                        OutlinedButton.icon(
                          onPressed: _locating ? null : _locate,
                          icon: _locating
                              ? const SizedBox(
                                  width: 16,
                                  height: 16,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Icon(Icons.my_location_rounded),
                          label: Text(
                            hasFix
                                ? AppStrings.locationRetry
                                : AppStrings.locationDetect,
                          ),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: Colors.white,
                            side: BorderSide(
                              color: Colors.white.withValues(alpha: 0.35),
                            ),
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(14),
                            ),
                          ),
                        ),
                        const SizedBox(height: 16),
                        AuthGlassField(
                          controller: _labelCtrl,
                          label: AppStrings.deliveryNameHint,
                          icon: Icons.location_on_outlined,
                          maxLength: 80,
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
                        const SizedBox(height: 16),
                        AuthGlassField(
                          controller: _detailsCtrl,
                          label: AppStrings.deliveryDescHint,
                          icon: Icons.home_work_outlined,
                          maxLength: 255,
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
                        const SizedBox(height: 22),
                        AuthPrimaryButton(
                          label: AppStrings.completeLocationButton,
                          isLoading: _saving,
                          onPressed: _save,
                        ),
                      ],
                    ),
                    ),
                  ),
                  const SizedBox(height: 28),
                ],
              ),
            ),
          ),
          if (_saving)
            const AuthLoadingOverlay(message: 'جاري حفظ الموقع...'),
        ],
      ),
    ),
    );
  }
}
