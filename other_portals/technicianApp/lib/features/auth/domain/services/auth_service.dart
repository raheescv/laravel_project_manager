import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import '../repository/auth_repository.dart';

class AuthService implements AuthRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<({String token, ApiUser user})> login(String pin) =>
      _login({'method': 'pin', 'pin': pin});

  @override
  Future<({String token, ApiUser user})> loginCredential(
          String username, String password) =>
      _login({'method': 'password', 'username': username, 'password': password});

  Future<({String token, ApiUser user})> _login(Map<String, dynamic> body) async {
    final data = await _http.post(EndPoints.login, body: body, auth: false);
    final map = Map<String, dynamic>.from(data);
    return (
      token: map['token'].toString(),
      user: ApiUser.fromJson(Map<String, dynamic>.from(map['user'])),
    );
  }

  @override
  Future<void> logout() => _http.post(EndPoints.logout);

  @override
  Future<void> changePin(String current, String next) =>
      _http.post(EndPoints.changePin, body: {
        'current_pin': current,
        'new_pin': next,
        'new_pin_confirmation': next,
      });

  @override
  Future<void> changePassword(String current, String next) =>
      _http.post(EndPoints.changePassword, body: {
        'current_password': current,
        'new_password': next,
        'new_password_confirmation': next,
      });
}
