allprojects {
    repositories {
        google()
        mavenCentral()
    }
}

val newBuildDir: Directory =
    rootProject.layout.buildDirectory
        .dir("../../build")
        .get()
rootProject.layout.buildDirectory.value(newBuildDir)

subprojects {
    val newSubprojectBuildDir: Directory = newBuildDir.dir(project.name)
    project.layout.buildDirectory.value(newSubprojectBuildDir)

    // Some plugins (e.g. file_picker) still compile against an older API than
    // their transitive dependencies now require. Force every Android subproject
    // to compileSdk 36 so AAR metadata checks pass.
    afterEvaluate {
        (extensions.findByName("android") as? com.android.build.gradle.BaseExtension)?.let { android ->
            val current = android.compileSdkVersion?.substringAfter("android-")?.toIntOrNull()
            if (current == null || current < 36) {
                android.compileSdkVersion(36)
            }
        }
    }
}
subprojects {
    project.evaluationDependsOn(":app")
}

tasks.register<Delete>("clean") {
    delete(rootProject.layout.buildDirectory)
}
