class DeliveryQuote {
  final double fee;
  final bool isFree;
  final double? distanceKm;
  final String label;
  final String? note;
  final int? ruleId;
  final String? ruleName;
  final String? perkName;
  final double perkDiscount;

  const DeliveryQuote({
    required this.fee,
    required this.isFree,
    this.distanceKm,
    this.label = '',
    this.note,
    this.ruleId,
    this.ruleName,
    this.perkName,
    this.perkDiscount = 0,
  });

  factory DeliveryQuote.fromJson(Map<String, dynamic> json) {
    return DeliveryQuote(
      fee: (json['fee'] as num?)?.toDouble() ?? 0,
      isFree: json['is_free'] as bool? ?? false,
      distanceKm: (json['distance_km'] as num?)?.toDouble(),
      label: (json['label'] as String?) ?? '',
      note: (json['note'] as String?)?.trim(),
      ruleId: (json['rule_id'] as num?)?.toInt(),
      ruleName: json['rule_name'] as String?,
      perkName: json['perk_name'] as String?,
      perkDiscount: (json['perk_discount'] as num?)?.toDouble() ?? 0,
    );
  }
}
