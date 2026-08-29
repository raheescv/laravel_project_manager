import 'package:equatable/equatable.dart';

import '../helpers/formatters.dart';

/// Authenticated user (Laravel AuthUserResource).
class ApiUser extends Equatable {
  const ApiUser({
    required this.id,
    required this.name,
    required this.code,
    required this.email,
    required this.mobile,
    required this.isAdmin,
    this.type = 'user',
    required this.designation,
    required this.role,
    required this.branchId,
    required this.daySessionStatus,
    required this.daySessionDate,
    this.photoUrl = '',
    this.daySessionOpenedAt = '',
    this.lastClosedSessionAt = '',
    this.permissions = const [],
  });

  final String id;
  final String name;
  final String code;
  final String email;
  final String mobile;
  final bool isAdmin;
  // Account class from the backend: 'user' | 'employee'. Non-admin employees are
  // scoped to their own data server-side (e.g. the Sales list only returns the
  // invoices they created) — see [isNonAdminEmployee].
  final String type;
  final String designation;
  final String role; // Spatie role name(s), comma-joined; '' when none assigned
  final String? branchId;
  // Root-relative storage path to the avatar (e.g. /storage/users/…), '' when
  // none. Resolve to an absolute URL with AppConfig.assetUrl before display.
  final String photoUrl;
  final String daySessionStatus; // open | closed
  final String daySessionDate;
  final String daySessionOpenedAt; // 'Y-m-d H:i:s' while a day is open, else ''
  final String lastClosedSessionAt; // 'Y-m-d H:i:s' of the most recent close, else ''
  final List<String> permissions; // Spatie permission slugs granted to this user

  bool get dayOpen => daySessionStatus == 'open';

  /// Whether the user has an uploaded avatar (vs. the letter monogram fallback).
  bool get hasPhoto => photoUrl.isNotEmpty;

  /// Admins implicitly hold every permission; staff need the slug explicitly.
  bool hasPermission(String permission) => isAdmin || permissions.contains(permission);

  /// True when this is a staff (employee-type) account rather than a back-office
  /// 'user' account.
  bool get isEmployee => type == 'employee';

  /// A non-admin staff account — the class the backend restricts to their own
  /// data (their Sales list only shows sales they created).
  bool get isNonAdminEmployee => isEmployee && !isAdmin;

  /// Whether the API hands this account the whole branch's figures.
  ///
  /// The mirror of the server's `User::seesOnlyOwnRecords()`, inverted: only a
  /// rank-and-file *employee* is self-scoped. A back-office 'user' account is
  /// not an employee, so it gets the full branch view even with `is_admin` off
  /// — which is why anything asking "may I see other people's numbers?" must
  /// ask this and not [isAdmin]. [isAdmin] stays what it says: the admin flag,
  /// for the badge and for implicit permissions.
  bool get seesAllRecords => !isNonAdminEmployee;

  factory ApiUser.fromJson(Map<String, dynamic> j) => ApiUser(
        id: asStr(j['id']),
        name: asStr(j['name']),
        code: asStr(j['code']),
        email: asStr(j['email']),
        mobile: asStr(j['mobile']),
        isAdmin: j['is_admin'] == true,
        type: j['type'] == null ? 'user' : asStr(j['type']),
        designation: asStr(j['designation']),
        role: asStr(j['role']),
        branchId: j['branch_id']?.toString(),
        photoUrl: asStr(j['photo']),
        daySessionStatus: asStr(j['sale_day_session_status']),
        daySessionDate: asStr(j['sale_day_session_date']),
        daySessionOpenedAt: asStr(j['sale_day_session_opened_at']),
        lastClosedSessionAt: asStr(j['last_closed_session_at']),
        permissions: (j['permissions'] as List<dynamic>? ?? [])
            .map((e) => asStr(e))
            .toList(),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'code': code,
        'email': email,
        'mobile': mobile,
        'is_admin': isAdmin,
        'type': type,
        'designation': designation,
        'role': role,
        'branch_id': branchId,
        'photo': photoUrl,
        'sale_day_session_status': daySessionStatus,
        'sale_day_session_date': daySessionDate,
        'sale_day_session_opened_at': daySessionOpenedAt,
        'last_closed_session_at': lastClosedSessionAt,
        'permissions': permissions,
      };

  /// Returns a copy with selected fields replaced. Covers the day-session fields
  /// (after an open/close) and the editable profile fields + avatar (after a
  /// profile update) so the profile row and dashboard pill update live.
  ApiUser copyWith({
    String? name,
    String? email,
    String? mobile,
    String? photoUrl,
    String? daySessionStatus,
    String? daySessionDate,
    String? daySessionOpenedAt,
    String? lastClosedSessionAt,
  }) =>
      ApiUser(
        id: id,
        name: name ?? this.name,
        code: code,
        email: email ?? this.email,
        mobile: mobile ?? this.mobile,
        isAdmin: isAdmin,
        type: type,
        designation: designation,
        role: role,
        branchId: branchId,
        photoUrl: photoUrl ?? this.photoUrl,
        daySessionStatus: daySessionStatus ?? this.daySessionStatus,
        daySessionDate: daySessionDate ?? this.daySessionDate,
        daySessionOpenedAt: daySessionOpenedAt ?? this.daySessionOpenedAt,
        lastClosedSessionAt: lastClosedSessionAt ?? this.lastClosedSessionAt,
        permissions: permissions,
      );

  String get initial => name.isNotEmpty ? name[0].toUpperCase() : '?';


  @override
  List<Object?> get props => [
        id,
        name,
        code,
        email,
        mobile,
        isAdmin,
        type,
        designation,
        role,
        branchId,
        photoUrl,
        daySessionStatus,
        daySessionDate,
        daySessionOpenedAt,
        lastClosedSessionAt,
        permissions,
      ];
}
