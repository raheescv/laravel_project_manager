import 'dart:typed_data';

import 'package:invo/shared/api/end_points.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/router/http_utils/http_service.dart';

import '../repository/profile_repository.dart';

class ProfileService implements ProfileRepository {
  HttpService get _http => serviceLocator<HttpService>();

  @override
  Future<ApiUser> updateProfile({
    required String name,
    required String email,
    required String mobile,
  }) async {
    final data = await _http.put(EndPoints.profile, body: {
      'name': name,
      'email': email,
      'mobile': mobile,
    });
    return ApiUser.fromJson(Map<String, dynamic>.from(data));
  }

  @override
  Future<ApiUser> updatePhoto(Uint8List bytes) async {
    final data = await _http.postFileBytes(
      EndPoints.profilePhoto,
      field: 'photo',
      bytes: bytes,
      filename: 'avatar.png',
    );
    return ApiUser.fromJson(Map<String, dynamic>.from(data));
  }
}
