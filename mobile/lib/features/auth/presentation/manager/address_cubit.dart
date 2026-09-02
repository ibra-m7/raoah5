import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/network/api_exception.dart';
import '../../data/models/delivery_address.dart';
import '../../data/services/addresses_api.dart';
import '../../data/services/auth_session.dart';

part 'address_state.dart';

class AddressCubit extends Cubit<AddressState> {
  AddressCubit({AddressesApi? api})
      : _api = api ?? AddressesApi.instance,
        super(AddressState(
          addresses: AuthSession.instance.user?.addresses ?? const [],
        ));

  final AddressesApi _api;

  Future<void> load() async {
    if (!AuthSession.instance.isLoggedIn) {
      emit(const AddressState());
      return;
    }
    emit(state.copyWith(loading: true, clearError: true));
    try {
      final addresses = await _api.list();
      await _syncSession(addresses);
      emit(state.copyWith(loading: false, addresses: addresses));
    } on ApiException catch (e) {
      if (e.statusCode == 401) {
        emit(const AddressState());
        return;
      }
      emit(state.copyWith(loading: false, clearError: true));
    } catch (_) {
      emit(state.copyWith(loading: false, clearError: true));
    }
  }

  Future<void> select(int id) async {
    emit(state.copyWith(busy: true, clearError: true));
    try {
      final addresses = await _api.update(id, isDefault: true);
      await _syncSession(addresses);
      emit(state.copyWith(busy: false, addresses: addresses));
    } on ApiException catch (e) {
      emit(state.copyWith(busy: false, error: e.message));
      rethrow;
    } catch (_) {
      emit(state.copyWith(busy: false, error: 'تعذّر اختيار العنوان.'));
      rethrow;
    }
  }

  Future<void> add({
    required String label,
    String? details,
    required double latitude,
    required double longitude,
    String? city,
    String? district,
    String? street,
  }) async {
    emit(state.copyWith(busy: true, clearError: true));
    try {
      final addresses = await _api.create(
        label: label,
        details: details,
        latitude: latitude,
        longitude: longitude,
        city: city,
        district: district,
        street: street,
      );
      await _syncSession(addresses);
      emit(state.copyWith(busy: false, addresses: addresses));
    } on ApiException catch (e) {
      emit(state.copyWith(busy: false, error: e.message));
      rethrow;
    } catch (_) {
      emit(state.copyWith(busy: false, error: 'تعذّر حفظ العنوان.'));
      rethrow;
    }
  }

  Future<void> remove(int id) async {
    emit(state.copyWith(busy: true, clearError: true));
    try {
      final addresses = await _api.delete(id);
      await _syncSession(addresses);
      emit(state.copyWith(busy: false, addresses: addresses));
    } on ApiException catch (e) {
      emit(state.copyWith(busy: false, error: e.message));
      rethrow;
    } catch (_) {
      emit(state.copyWith(busy: false, error: 'تعذّر حذف العنوان.'));
      rethrow;
    }
  }

  Future<void> _syncSession(List<DeliveryAddress> addresses) async {
    final user = AuthSession.instance.user;
    if (user == null) return;
    await AuthSession.instance.updateUser(
      user.copyWith(
        addresses: addresses,
        needsLocation: addresses.isEmpty,
      ),
    );
  }
}
