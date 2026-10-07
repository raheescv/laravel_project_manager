import 'dart:typed_data';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

import '../../domain/repository/profile_repository.dart';

part 'profile_state.dart';

/// Owns every account write the profile screens perform — editing the
/// profile, replacing the avatar, changing the PIN or password — so the
/// screens never reach a repository directly.
class ProfileCubit extends Cubit<ProfileState> {
  ProfileCubit(this._profile, this._auth) : super(const ProfileState());

  final ProfileRepository _profile;
  final AuthRepository _auth;

  /// Runs [action], reporting progress through the state. Returns the value on
  /// success and null on failure.
  Future<T?> _run<T>(Future<T> Function() action) async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final result = await action();
      if (!isClosed) emit(state.copyWith(status: DataFetchStatus.success));
      return result;
    } on ApiException catch (e) {
      if (!isClosed) emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
      return null;
    } catch (_) {
      if (!isClosed) {
        emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: AppStrings.somethingWentWrong));
      }
      return null;
    }
  }

  Future<ApiUser?> updateProfile({required String name, required String mobile, required String email}) =>
      _run(() => _profile.updateProfile(name: name, mobile: mobile, email: email));

  Future<ApiUser?> updatePhoto(Uint8List bytes) => _run(() => _profile.updatePhoto(bytes));

  /// True when the change succeeded (these endpoints return nothing).
  Future<bool> changePin(String current, String next) async {
    await _run<void>(() => _auth.changePin(current, next));
    return state.succeeded;
  }

  Future<bool> changePassword(String current, String next) async {
    await _run<void>(() => _auth.changePassword(current, next));
    return state.succeeded;
  }
}
