import 'package:equatable/equatable.dart';
import 'package:invo/shared/domain/helpers/formatters.dart';

/// One user who has successfully signed in on this device before.
///
/// Kept so that user can sign in again **with no network**. A shared till hands
/// over between cashiers all day, and an offline till that cannot authenticate the
/// next cashier cannot sell at all — which defeats the point of holding sales on
/// the device in the first place.
///
/// [token] is the API token that sign-in issued. Replaying it is what lets the
/// outbox drain post under the right user once the network is back; if it has been
/// revoked server-side the first request 401s and the session is dropped, which is
/// the same path any expired session already takes.
///
/// **Where the secrets live.** The whole roster is written to
/// `flutter_secure_storage` — Keychain on iOS, EncryptedSharedPreferences on
/// Android — which is exactly where this app already keeps the PIN it replays for
/// biometric sign-in. Storing a verifier hash instead would be defence in depth,
/// but there is no hashing dependency in this project and hand-rolling one is
/// worse than relying on the platform keystore that is already trusted with the
/// same secret.
class DeviceAccount extends Equatable {
  const DeviceAccount({
    required this.userId,
    required this.name,
    required this.token,
    required this.userJson,
    required this.lastSignInAt,
    this.pin = '',
    this.username = '',
    this.password = '',
  });

  factory DeviceAccount.fromJson(Map<String, dynamic> json) => DeviceAccount(
        userId: asStr(json['user_id']),
        name: asStr(json['name']),
        token: asStr(json['token']),
        userJson: asStr(json['user_json']),
        lastSignInAt:
            DateTime.tryParse(asStr(json['last_sign_in_at'])) ?? DateTime.fromMillisecondsSinceEpoch(0),
        pin: asStr(json['pin']),
        username: asStr(json['username']),
        password: asStr(json['password']),
      );

  final String userId;
  final String name;
  final String token;

  /// The `ApiUser` payload as it was cached at sign-in — permissions included, so
  /// an offline session grants exactly what the last online one did and never more.
  final String userJson;

  final DateTime lastSignInAt;

  /// Whichever of these the user actually signs in with. A PIN user has no
  /// username; a credential user has no PIN.
  final String pin;
  final String username;
  final String password;

  bool matchesPin(String candidate) => pin.isNotEmpty && pin == candidate;

  bool matchesCredential(String user, String pass) =>
      username.isNotEmpty && username == user.trim() && password == pass;

  /// The credential blob replayed for biometric sign-in, stamped with the user
  /// it belongs to.
  ///
  /// The stamp is what makes it safe on a shared till: the blob is a single slot
  /// and several people use the device, so without it a saved PIN cannot be told
  /// apart from a colleague's — see `AuthCubit._isCurrentUsersPin`.
  Map<String, String> get credential => {
        'user_id': userId,
        if (pin.isNotEmpty)
          ...{'mode': 'pin', 'pin': pin}
        else
          ...{'mode': 'cred', 'username': username, 'password': password},
      };

  DeviceAccount copyWith({String? pin, DateTime? lastSignInAt}) => DeviceAccount(
        userId: userId,
        name: name,
        token: token,
        userJson: userJson,
        lastSignInAt: lastSignInAt ?? this.lastSignInAt,
        pin: pin ?? this.pin,
        username: username,
        password: password,
      );

  Map<String, dynamic> toJson() => {
        'user_id': userId,
        'name': name,
        'token': token,
        'user_json': userJson,
        'last_sign_in_at': lastSignInAt.toIso8601String(),
        if (pin.isNotEmpty) 'pin': pin,
        if (username.isNotEmpty) 'username': username,
        if (password.isNotEmpty) 'password': password,
      };

  @override
  List<Object?> get props => [userId, name, token, userJson, lastSignInAt, pin, username, password];
}
