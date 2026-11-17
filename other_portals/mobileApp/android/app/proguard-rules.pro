# R8 keep rules for the barcode scanner stack.
#
# Release builds shrink with R8 in full mode (AGP 8 default), which strips and
# renames ML Kit / CameraX internals that are reached reflectively at runtime.
# Without these keeps, MobileScannerController.start() dies with an NPE on
# obfuscated classes (e.g. "q5.c q5.b.a(m5.b) on a null object reference")
# in release builds only — debug builds don't shrink and work fine.

# ML Kit barcode scanning (bundled model) + its gms internals.
-keep class com.google.mlkit.** { *; }
-keep class com.google.android.gms.internal.mlkit_vision_barcode.** { *; }
-keep class com.google.android.gms.internal.mlkit_vision_barcode_bundled.** { *; }
-keep class com.google.android.gms.internal.mlkit_vision_common.** { *; }
-keep class com.google.android.gms.common.** { *; }
-dontwarn com.google.mlkit.**

# CameraX.
-keep class androidx.camera.** { *; }
-dontwarn androidx.camera.**

# mobile_scanner plugin (method channel bridge).
-keep class dev.steenbakker.mobile_scanner.** { *; }
