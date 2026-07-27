// Throwaway visual harness for the Profile account panes — NOT part of the app.
//
//   flutter run -t tool/profile_preview.dart -d <ipad-simulator-udid>
//
// It mounts the REAL HomeShell + ProfileScreen (so what you see is the shipped
// layout and the shipped navigation) with a stub signed-in user, and drives it
// with synthetic taps: the dashboard avatar, then each section tile in the
// profile's left pane. Nothing here touches the live POS.
import 'dart:async';

import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';

import 'package:invo/app.dart';
import 'package:invo/features/auth/logic/auth_cubit/auth_cubit.dart';
import 'package:invo/shared/domain/constants/global_variables.dart';
import 'package:invo/shared/domain/models/models.dart';
import 'package:invo/shared/utils/service_locator_setup/setup.dart';
import 'package:invo/flavors.dart';

/// Tap targets in logical pixels, measured off screenshots on an iPad Pro 11"
/// in landscape (1210×834): the dashboard avatar, then the four section tiles.
const _avatar = Offset(1074, 83);
const _tiles = [Offset(212, 396), Offset(212, 460), Offset(212, 524), Offset(212, 588)];

/// The profile detail pane's Access → Permissions row.
const _permissions = Offset(601, 455);

Future<void> main() async {
  F.appFlavor ??= Flavor.dev;
  WidgetsFlutterBinding.ensureInitialized();
  await setUpServiceLocator();
  final auth = serviceLocator<AuthCubit>();
  auth.user = ApiUser(
    id: '7',
    name: 'Rahees Muhammed',
    code: 'EMP-014',
    email: 'rahees@astrasalon.qa',
    mobile: '+974 5540 2213',
    isAdmin: true,
    designation: 'Branch Manager',
    role: 'Administrator',
    branchId: '1',
    daySessionStatus: 'open',
    daySessionDate: '2026-07-27',
    permissions: const ['sale.view', 'sale.create', 'stock-check', 'day-session'],
  );
  auth.status = AuthStatus.signedIn;

  // Tap the avatar, then walk the sections — 6s apart, so each state can be
  // screenshotted.
  var step = 0;
  var pointer = 1;
  void tap(Offset at) {
    final b = GestureBinding.instance;
    final id = pointer++;
    b.handlePointerEvent(PointerDownEvent(pointer: id, position: at));
    b.handlePointerEvent(PointerUpEvent(pointer: id, position: at));
  }

  // avatar → Permissions row → back to the profile sections.
  Timer.periodic(const Duration(seconds: 6), (_) {
    // avatar → profile sections → Permissions, then stop so the last page can
    // be screenshotted at leisure.
    if (step < 6) tap(switch (step) { 0 => _avatar, 5 => _permissions, _ => _tiles[(step - 1) % 4] });
    step++;
  });

  runApp(const InvoApp());
}
