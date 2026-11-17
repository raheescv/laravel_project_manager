import 'dart:typed_data';

import 'package:equatable/equatable.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import 'package:invo/features/auth/domain/repository/auth_repository.dart';
import 'package:invo/features/profile/domain/repository/profile_repository.dart';
import 'package:invo/shared/domain/constants/data_fetching_status.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/index.dart';
import 'package:invo/shared/utils/components/app_strings.dart';
import 'package:invo/shared/utils/router/http_utils/common_exception.dart';

part 'profile_state.dart';

/// Owns every account write the profile screens perform — editing the profile,
/// replacing the avatar, changing the PIN or password.
///
/// The screens used to call `serviceLocator<ProfileRepository>()` and
/// `serviceLocator<AuthRepository>()` straight from their `State`, each
/// re-implementing the same saving / error / success bookkeeping (§10: the flow
/// is Widget → Cubit → Repository, never Widget → Repository).
class ProfileCubit extends Cubit<ProfileState> {
  ProfileCubit({ProfileRepository? profile, AuthRepository? auth})
      : _profile = profile ?? serviceLocator<ProfileRepository>(),
        _auth = auth ?? serviceLocator<AuthRepository>(),
        super(const ProfileState());

  final ProfileRepository _profile;
  final AuthRepository _auth;

  /// Runs [action], reporting progress through the state. Returns the value on
  /// success and null on failure, so callers can branch without duplicating the
  /// try/catch.
  Future<T?> _run<T>(Future<T> Function() action) async {
    emit(state.copyWith(status: DataFetchStatus.waiting, clearError: true));
    try {
      final result = await action();
      emit(state.copyWith(status: DataFetchStatus.success));
      return result;
    } on ApiException catch (e) {
      emit(state.copyWith(status: DataFetchStatus.failed, errorMessage: e.message));
      return null;
    } catch (_) {
      emit(state.copyWith(
          status: DataFetchStatus.failed, errorMessage: AppStrings.somethingWentWrong));
      return null;
    }
  }

  Future<ApiUser?> updateProfile({
    required String name,
    required String mobile,
    required String email,
  }) =>
      _run(() => _profile.updateProfile(name: name, mobile: mobile, email: email));

  Future<ApiUser?> updatePhoto(Uint8List bytes) => _run(() => _profile.updatePhoto(bytes));

  /// True when the change succeeded. These return `void`, so the outcome is
  /// read from the state rather than from a return value.
  Future<bool> changePin(String current, String next) async {
    await _run<void>(() => _auth.changePin(current, next));
    return state.succeeded;
  }

  Future<bool> changePassword(String current, String next) async {
    await _run<void>(() => _auth.changePassword(current, next));
    return state.succeeded;
  }

  /// Clear a failure once the screen has shown it.
  void clearError() => emit(state.copyWith(clearError: true, status: DataFetchStatus.idle));
}
