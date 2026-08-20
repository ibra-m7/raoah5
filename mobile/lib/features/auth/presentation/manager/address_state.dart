part of 'address_cubit.dart';

class AddressState extends Equatable {
  final List<DeliveryAddress> addresses;
  final bool loading;
  final bool busy;
  final String? error;

  const AddressState({
    this.addresses = const [],
    this.loading = false,
    this.busy = false,
    this.error,
  });

  DeliveryAddress? get selected {
    for (final address in addresses) {
      if (address.isDefault) return address;
    }
    return addresses.isEmpty ? null : addresses.first;
  }

  AddressState copyWith({
    List<DeliveryAddress>? addresses,
    bool? loading,
    bool? busy,
    String? error,
    bool clearError = false,
  }) =>
      AddressState(
        addresses: addresses ?? this.addresses,
        loading: loading ?? this.loading,
        busy: busy ?? this.busy,
        error: clearError ? null : error ?? this.error,
      );

  @override
  List<Object?> get props => [addresses, loading, busy, error];
}
