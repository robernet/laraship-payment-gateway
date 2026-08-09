import 'package:core/core.dart';

/// Hand-written fake covering every [PaymentGatewayService] method. Each
/// screen test configures only the result/error fields it needs.
class FakePaymentGatewayService implements PaymentGatewayService {
  FakePaymentGatewayService({
    this.loginResult,
    this.loginError,
    this.lookupResult,
    this.lookupError,
    this.collectResult,
    this.collectError,
    this.openShiftResult,
    this.openShiftError,
    this.closeShiftResult,
    this.closeShiftError,
  });

  PosSession? loginResult;
  Object? loginError;
  PaymentReference? lookupResult;
  Object? lookupError;
  Transaction? collectResult;
  Object? collectError;
  Shift? openShiftResult;
  Object? openShiftError;
  Shift? closeShiftResult;
  Object? closeShiftError;

  Map<String, dynamic>? lastCollectArgs;
  int? lastCloseCountedAmountMinor;

  @override
  Future<PosSession> login({required String email, required String password, required String storeId}) async {
    if (loginError != null) throw loginError!;
    return loginResult!;
  }

  @override
  Future<PaymentReference> lookupReference(String reference) async {
    if (lookupError != null) throw lookupError!;
    return lookupResult!;
  }

  @override
  Future<Transaction> collectPayment({
    required String paymentReferenceId,
    required int amount,
    required String currency,
  }) async {
    lastCollectArgs = {'payment_reference_id': paymentReferenceId, 'amount': amount, 'currency': currency};
    if (collectError != null) throw collectError!;
    return collectResult!;
  }

  @override
  Future<Shift> openShift({required String storeId}) async {
    if (openShiftError != null) throw openShiftError!;
    return openShiftResult!;
  }

  @override
  Future<Shift> closeShift({required String shiftId, required int countedAmountMinor}) async {
    lastCloseCountedAmountMinor = countedAmountMinor;
    if (closeShiftError != null) throw closeShiftError!;
    return closeShiftResult!;
  }
}
