import '../models/payment_reference.dart';
import '../models/shift.dart';
import '../models/transaction.dart';

/// A logged-in POS operator's session: the bearer token (already persisted
/// to [TokenStore] by the time this is returned) plus what it's scoped to.
class PosSession {
  const PosSession({required this.token, required this.abilities, required this.storeId});

  final String token;
  final List<String> abilities;
  final String storeId;
}

/// Every call the POS app makes against the PaymentGateway API surface.
/// Abstract so widget tests can substitute a hand-written fake instead of
/// mocking HTTP — see `apps/pos/test/support/fake_payment_gateway_service.dart`.
abstract class PaymentGatewayService {
  Future<PosSession> login({required String email, required String password, required String storeId});

  Future<PaymentReference> lookupReference(String reference);

  Future<Transaction> collectPayment({
    required String paymentReferenceId,
    required int amount,
    required String currency,
  });

  Future<Shift> openShift({required String storeId});

  Future<Shift> closeShift({required String shiftId, required int countedAmountMinor});
}
