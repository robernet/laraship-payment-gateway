import '../auth/token_store.dart';
import '../models/payment_reference.dart';
import '../models/shift.dart';
import '../models/transaction.dart';
import '../network/api_client.dart';
import 'payment_gateway_service.dart';

class DioPaymentGatewayService implements PaymentGatewayService {
  DioPaymentGatewayService({required this._api, required this._tokens});

  final ApiClient _api;
  final TokenStore _tokens;

  @override
  Future<PosSession> login({
    required String email,
    required String password,
    required String storeId,
  }) async {
    final json = await _api.post<Map<String, dynamic>>(
      '/pos/login',
      (m) => m,
      body: {'email': email, 'password': password, 'store_id': storeId},
    );
    final session = PosSession(
      token: json['token'] as String,
      abilities: (json['abilities'] as List).cast<String>(),
      storeId: json['store_id'] as String,
    );
    await _tokens.write(session.token);
    return session;
  }

  @override
  Future<PaymentReference> lookupReference(String reference) {
    return _api.getOne('/payment-references/lookup/$reference', PaymentReference.fromJson);
  }

  @override
  Future<Transaction> collectPayment({
    required String paymentReferenceId,
    required int amount,
    required String currency,
  }) {
    return _api.post(
      '/transactions',
      Transaction.fromJson,
      body: {'payment_reference_id': paymentReferenceId, 'amount': amount, 'currency': currency},
    );
  }

  @override
  Future<Shift> openShift({required String storeId}) {
    return _api.post('/shifts', Shift.fromJson, body: {'store_id': storeId});
  }

  @override
  Future<Shift> closeShift({required String shiftId, required int countedAmountMinor}) {
    return _api.patch(
      '/shifts/$shiftId',
      Shift.fromJson,
      body: {'counted_amount': countedAmountMinor},
    );
  }
}
