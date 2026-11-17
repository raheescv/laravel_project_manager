#!/usr/bin/env bash
# Release APK build, guarded against the two failure modes that cost us 40 min.
#
#  1. Stacked builds. Flutter does not detect a build already in flight — each
#     run spawns its own Dart AOT compile and they all write the SAME
#     .dart_tool/flutter_build/<hash>/app.dill. Four of them plus two 8GB Gradle
#     daemons put a 16GB machine into swap thrashing (measured load avg 596).
#
#  2. Suspended `flutter run` sessions. Ctrl+Z leaves the process in state T
#     holding both its memory and the attached device — we found ten of them,
#     the oldest three days old. That is also the cause of the iOS
#     white-screen-on-launch bug (two runs fighting over one device).
#
# Usage: tool/build_apk.sh [--clean-runs] [extra flutter args...]
set -euo pipefail
cd "$(dirname "$0")/.."

clean_runs=0
args=()
for a in "$@"; do
  case "$a" in
    --clean-runs) clean_runs=1 ;;
    *) args+=("$a") ;;
  esac
done

pids_of() { pgrep -f "flutter_tools.snapshot $1" 2>/dev/null || true; }

# --- guard 1: never stack a second build -----------------------------------
others=$(pids_of "build apk" | grep -v "^$$\$" || true)
live_build=""
for p in $others; do
  # ignore our own subshell and anything already stopped/defunct
  st=$(ps -o stat= -p "$p" 2>/dev/null | tr -d ' ')
  case "$st" in R*|S*|U*) live_build="$live_build $p" ;; esac
done
if [ -n "${live_build// /}" ]; then
  echo "✗ A release build is already running (pid:$live_build) — refusing to stack." >&2
  echo "  Wait for it, or kill it: kill$live_build" >&2
  exit 1
fi

# --- guard 2: suspended / stale `flutter run` sessions ----------------------
stale=""
for p in $(pids_of run); do
  st=$(ps -o stat= -p "$p" 2>/dev/null | tr -d ' ')
  [ "${st:0:1}" = "T" ] && stale="$stale $p"
done
if [ -n "${stale// /}" ]; then
  n=$(echo $stale | wc -w | tr -d ' ')
  echo "⚠ $n suspended 'flutter run' session(s) are holding memory and devices:"
  for p in $stale; do printf "    %-7s %s\n" "$p" "$(ps -o etime= -p $p | tr -d ' ') (Ctrl+Z'd)"; done
  if [ "$clean_runs" = "1" ]; then
    kill -9 $stale 2>/dev/null || true
    echo "  → killed. (Quit flutter run with 'q', not Ctrl+Z.)"
  else
    echo "  Re-run with --clean-runs to clear them first."
  fi
fi

# --- guard 3: enough free memory to actually finish -------------------------
free_pct=$(memory_pressure 2>/dev/null | awk -F: '/free percentage/{gsub(/[^0-9]/,"",$2); print $2}')
if [ -n "${free_pct:-}" ] && [ "$free_pct" -lt 15 ]; then
  echo "⚠ Only ${free_pct}% memory free — this build will swap and crawl." >&2
  echo "  Free some up:  ./android/gradlew --stop   (and close idle editor windows)" >&2
  exit 1
fi

# --split-per-abi is what gets the APK from ~51MB to ~41MB: it filters the
# AAR-provided native libs (ML Kit, CameraX, dartjni) that --target-platform
# alone leaves behind. See the note in android/app/build.gradle.kts.
echo "→ building release APK (arm64, split-per-abi)…"
time flutter build apk --release \
  --dart-define-from-file=env.json \
  --split-per-abi --target-platform android-arm64 ${args+"${args[@]}"}

ls -lh build/app/outputs/flutter-apk/*arm64*.apk
