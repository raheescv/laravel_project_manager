plugins {
    id("com.android.application")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

android {
    namespace = "com.astrasalon.invo"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    defaultConfig {
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "com.astrasalon.invo"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
    }

    buildTypes {
        release {
            // TODO: Add your own signing config for the release build.
            // Signing with the debug keys for now, so `flutter run --release` works.
            signingConfig = signingConfigs.getByName("debug")
            // Keep ML Kit / CameraX / mobile_scanner classes that R8 full mode
            // would otherwise strip — without this the barcode camera NPEs in
            // release builds only.
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro",
            )
            // No retail Android device is x86_64 — it only exists for emulators,
            // which run debug builds anyway. Shipping it tripled the native libs
            // (libapp/libflutter/libbarhopper are ~35MB per ABI). Release only, so
            // `flutter run` on an x86_64 emulator still works.
            //
            // AGP rejects ndk.abiFilters when --split-per-abi is in play, and
            // Flutter signals that mode with -Psplit-per-abi=true, so skip the
            // filter there — the split build already emits one APK per ABI.
            if (project.findProperty("split-per-abi")?.toString().toBoolean().not()) {
                ndk {
                    abiFilters += listOf("arm64-v8a", "armeabi-v7a")
                }
            }
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget = org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17
    }
}

flutter {
    source = "../.."
}
