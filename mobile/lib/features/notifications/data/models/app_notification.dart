class AppNotification {
  final String id;
  final String title;
  final String body;
  final String type;
  final Map<String, dynamic> data;
  final bool read;
  final DateTime? createdAt;

  const AppNotification({
    required this.id,
    required this.title,
    required this.body,
    required this.type,
    this.data = const {},
    this.read = false,
    this.createdAt,
  });

  bool get isOrder => type == 'order';
  String? get orderId => data['order_id']?.toString();

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    return AppNotification(
      id: '${json['id']}',
      title: (json['title'] as String?) ?? '',
      body: (json['body'] as String?) ?? '',
      type: (json['type'] as String?) ?? 'general',
      data: data is Map ? Map<String, dynamic>.from(data) : const {},
      read: json['read'] == true || json['read_at'] != null,
      createdAt: DateTime.tryParse((json['created_at'] as String?) ?? ''),
    );
  }

  AppNotification copyWith({bool? read}) {
    return AppNotification(
      id: id,
      title: title,
      body: body,
      type: type,
      data: data,
      read: read ?? this.read,
      createdAt: createdAt,
    );
  }
}
