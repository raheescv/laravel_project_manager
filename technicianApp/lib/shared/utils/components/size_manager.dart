import 'package:flutter_screenutil/flutter_screenutil.dart';

/// Responsive sizing tokens, backed by `flutter_screenutil`. Design size is
/// **393×865** (initialised in `app.dart` via `ScreenUtilInit`). New widgets
/// should size through these instead of raw literals so layouts scale across
/// phones and tablets.
class KFontSize {
  KFontSize._();
  static double get f10 => 10.sp;
  static double get f12 => 12.sp;
  static double get f14 => 14.sp;
  static double get f16 => 16.sp;
  static double get f18 => 18.sp;
  static double get f20 => 20.sp;
  static double get f24 => 24.sp;
  static double get f28 => 28.sp;
  static double get f32 => 32.sp;
}

class KRadius {
  KRadius._();
  static double get r8 => 8.r;
  static double get r10 => 10.r;
  static double get r12 => 12.r;
  static double get r14 => 14.r;
  static double get r16 => 16.r;
  static double get r18 => 18.r;
  static double get r22 => 22.r;
  static double get r30 => 30.r;
}

class KPadding {
  KPadding._();
  static double get v4 => 4.h;
  static double get v8 => 8.h;
  static double get v12 => 12.h;
  static double get v15 => 15.h;
  static double get v20 => 20.h;
  static double get h4 => 4.w;
  static double get h8 => 8.w;
  static double get h12 => 12.w;
  static double get h16 => 16.w;
  static double get h20 => 20.w;
}

class KHeight {
  KHeight._();
  static double get h4 => 4.h;
  static double get h8 => 8.h;
  static double get h12 => 12.h;
  static double get h16 => 16.h;
  static double get h20 => 20.h;
  static double get h24 => 24.h;
  static double get h32 => 32.h;
}

class KWidth {
  KWidth._();
  static double get w4 => 4.w;
  static double get w8 => 8.w;
  static double get w12 => 12.w;
  static double get w16 => 16.w;
  static double get w20 => 20.w;
}
