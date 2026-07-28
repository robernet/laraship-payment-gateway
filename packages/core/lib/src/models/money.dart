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

  @override
  bool operator ==(Object other) =>
      other is Money && other.amount == amount && other.currency == currency;

  @override
  int get hashCode => Object.hash(amount, currency);
}
