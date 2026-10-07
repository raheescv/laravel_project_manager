import 'dart:typed_data';

import 'package:invo/shared/domain/models/index.dart';

/// Profile self-service: the signed-in user edits their own name / email /
/// mobile and uploads an avatar. Both calls return the refreshed [ApiUser] so
/// the caller can re-cache it (shared `PUT /profile`, `POST /profile/photo`).
abstract class ProfileRepository {
  Future<ApiUser> updateProfile({
    required String name,
    required String email,
    required String mobile,
  });

  /// Uploads the cropped square image [bytes] as the new avatar.
  Future<ApiUser> updatePhoto(Uint8List bytes);
}
