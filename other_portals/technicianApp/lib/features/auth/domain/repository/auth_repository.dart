import 'package:invo/shared/domain/models/index.dart';

/// Authentication contract: unified PIN / credential login, logout and the
/// PIN / password change endpoints.
abstract class AuthRepository {
  Future<({String token, ApiUser user})> login(String pin);

  Future<({String token, ApiUser user})> loginCredential(
      String username, String password);

  Future<void> logout();

  Future<void> changePin(String current, String next);

  Future<void> changePassword(String current, String next);
}
