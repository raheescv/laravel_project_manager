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

    buildFeatures {
        // AGP 8 turns AIDL off by default. The built-in printer on an
        // all-in-one POS terminal is only reachable over the vendor's AIDL
        // service (src/main/aidl/woyou/...), so it has to be back on.
        aidl = true
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

        // Release builds MUST use --split-per-abi, otherwise the APK ships every
        // native lib for all three ABIs and lands at ~113MB instead of ~41MB:
        //   flutter build apk --release --split-per-abi --target-platform android-arm64
        //
        // Do NOT try to do this with ndk.abiFilters. Measured on AGP 8 / Gradle
        // 9.1, it does not filter AAR-provided native libs (ML Kit's
        // libbarhopper_v3, CameraX, dartjni) from either defaultConfig or
        // buildTypes — output was byte-identical with and without it — and it
        // makes AGP reject --split-per-abi outright. --target-platform alone only
        // trims Flutter's own libs (libapp/libflutter), leaving ~9.6MB of
        // duplicated AAR libs. Only the splits path filters both.
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
