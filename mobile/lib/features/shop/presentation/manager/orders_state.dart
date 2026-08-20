part of 'orders_cubit.dart';

class OrdersState extends Equatable {
  final List<OrderEntity> orders;
  final bool loading;
  final String? error;

  const OrdersState({
    this.orders = const [],
    this.loading = false,
    this.error,
  });

  OrdersState copyWith({
    List<OrderEntity>? orders,
    bool? loading,
    String? error,
    bool clearError = false,
  }) =>
      OrdersState(
        orders: orders ?? this.orders,
        loading: loading ?? this.loading,
        error: clearError ? null : error ?? this.error,
      );

  @override
  List<Object?> get props => [orders, loading, error];
}
