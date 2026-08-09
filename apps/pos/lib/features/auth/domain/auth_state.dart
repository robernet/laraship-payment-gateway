import 'package:core/core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core_providers.dart';

class AuthNotifier extends Notifier<PosSession?> {
  @override
  PosSession? build() => null;

  Future<void> login({required String email, required String password, required String storeId}) async {
    final session = await ref.read(paymentGatewayServiceProvider).login(
          email: email,
          password: password,
          storeId: storeId,
        );
    state = session;
  }

  void logout() => state = null;
}

final authProvider = NotifierProvider<AuthNotifier, PosSession?>(AuthNotifier.new);
