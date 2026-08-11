/// Money as integer **minor units** + ISO currency, per the API contract.
/// Never use doubles to store money — only for display.
class Money {
  const Money({required this.amount, required this.currency});

  /// Minor units, e.g. 150000 + 'MXN' = $1,500.00.
  final int amount;
  final String currency;

  factory Money.fromJson(Map<String, dynamic> json) => Money(
        amount: (json['amount'] as num).toInt(),
        currency: json['currency'] as String,
      );

  Map<String, dynamic> toJson() => {'amount': amount, 'currency': currency};

  /// Major-unit value for display only (assumes 2 minor digits — adjust for
  /// zero-decimal currencies).
  double get major => amount / 100;

  /// Display string, e.g. 123456 + 'MXN' -> '1,234.56 MXN'. Divides minor
  /// units by 100 and groups thousands. Currency is optional (discrepancy
  /// amounts have none handy). Assumes 2 minor digits.
  // ponytail: no intl dep — regex grouping. Swap to NumberFormat.currency if
  // locale-aware/zero-decimal formatting is ever needed.
  static String format(int minor, [String? currency]) {
    final fixed = (minor / 100).toStringAsFixed(2);
    final dot = fixed.indexOf('.');
    final intPart = fixed.substring(0, dot);
    final grouped = intPart.replaceAllMapped(
      RegExp(r'(\d)(?=(\d{3})+$)'),
      (m) => '${m[1]},',
    );
    final value = '$grouped${fixed.substring(dot)}';
    return currency == null ? value : '$value $currency';
  }

  @override
  bool operator ==(Object other) =>
      other is Money && other.amount == amount && other.currency == currency;

  @override
  int get hashCode => Object.hash(amount, currency);
}
