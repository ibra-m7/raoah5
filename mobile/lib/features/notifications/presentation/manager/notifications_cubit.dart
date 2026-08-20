import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../auth/data/services/auth_session.dart';
import '../../data/models/app_notification.dart';
import '../../data/services/notifications_api.dart';

class NotificationsState extends Equatable {
  final List<AppNotification> items;
  final int unreadCount;
  final bool loading;
  final String? error;

  const NotificationsState({
    this.items = const [],
    this.unreadCount = 0,
    this.loading = false,
    this.error,
  });

  NotificationsState copyWith({
    List<AppNotification>? items,
    int? unreadCount,
    bool? loading,
    String? error,
    bool clearError = false,
  }) {
    return NotificationsState(
      items: items ?? this.items,
      unreadCount: unreadCount ?? this.unreadCount,
      loading: loading ?? this.loading,
      error: clearError ? null : (error ?? this.error),
    );
  }

  @override
  List<Object?> get props => [items, unreadCount, loading, error];
}

class NotificationsCubit extends Cubit<NotificationsState> {
  NotificationsCubit() : super(const NotificationsState());

  final _api = NotificationsApi.instance;

  Future<void> load({bool silent = false}) async {
    if (!AuthSession.instance.isLoggedIn) {
      emit(const NotificationsState());
      return;
    }
    if (!silent) {
      emit(state.copyWith(loading: true, clearError: true));
    }
    try {
      final page = await _api.list();
      emit(NotificationsState(
        items: page.items,
        unreadCount: page.unreadCount,
      ));
    } catch (e) {
      if (state.items.isNotEmpty) {
        emit(state.copyWith(loading: false, clearError: true));
        return;
      }
      if (silent) return;
      emit(state.copyWith(
        loading: false,
        error: e.toString().replaceFirst('Exception: ', ''),
      ));
    }
  }

  Future<void> markRead(AppNotification item) async {
    if (item.read) return;
    emit(state.copyWith(
      items: [
        for (final row in state.items)
          if (row.id == item.id) row.copyWith(read: true) else row,
      ],
      unreadCount: (state.unreadCount - 1).clamp(0, 9999),
    ));
    try {
      await _api.markRead(item.id);
    } catch (_) {
      await load();
    }
  }

  Future<void> markAllRead() async {
    emit(state.copyWith(
      items: [for (final row in state.items) row.copyWith(read: true)],
      unreadCount: 0,
    ));
    try {
      await _api.markAllRead();
    } catch (_) {
      await load();
    }
  }
}
