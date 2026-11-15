# FixMate — Astra Technician

Field app for maintenance complaints and RentOut hand-over checklists, on `/api/v1/technician`.

## Run

```bash
cd technicianApp
flutter pub get
cp env.example.json env.json   # then edit env.json (see below)
flutter run --dart-define-from-file=env.json
```

In VS Code use the **FixMate (env.json)** launch configuration, which passes the same flag.

The connection is read at build time from `env.json` via `--dart-define-from-file`
(mapped into `AppConfig` in `lib/shared/domain/constants/app_config.dart`):

| Key | Meaning |
|---|---|
| `API_BASE_URL` | Laravel host, no trailing slash, no `/api`. |
| `API_TENANT` | Tenant subdomain, sent as `X-Tenant-Subdomain` / `?tenant=`. |
| `API_HOST` | `Host` header override so Valet/nginx routes a LAN-IP request to the right site (e.g. `project_manager.test`). Leave it out for a live domain. |
| `API_NAME` | Build-only: names the APK `build_apk.sh` produces (e.g. `orga` → `orga-fixmate-1.0.0+1.apk`). |

`env.json` is **gitignored** (per-machine); `env.example.json` is the committed template.
Values from `env.json` win over `.env` and over a connection saved in the app.
Without it the app falls back to `.env`, then to `https://project_manager.test`
(host machine / simulator only).

## Local server on a physical iPhone / Android

A real device can't resolve the Mac's `.test` domains, so point it at the Mac's LAN IP.

1. Find the Mac's WiFi IP: `ipconfig getifaddr en0` (e.g. `192.168.68.101`).
2. Put it in `env.json`:
   ```json
   {
     "API_BASE_URL": "https://192.168.68.101",
     "API_TENANT": "project_manager",
     "API_HOST": "project_manager.test"
   }
   ```
   HTTPS works: the dev flavor (a plain `flutter run`) accepts Valet's self-signed certificate.
3. Expose Valet on the LAN (it listens on `127.0.0.1` only by default):
   ```bash
   valet loopback 192.168.68.101    # revert with: valet loopback 127.0.0.1
   ```
   Check from the Mac:
   `curl -k -H "Host: project_manager.test" "https://192.168.68.101/api/v1/branches?tenant=project_manager"` should return JSON.
4. Phone on the **same WiFi**, unlocked, then:
   ```bash
   flutter devices
   flutter run -d <iphone-id> --dart-define-from-file=env.json
   ```
5. On first launch iOS asks for **Local Network** access — allow it. If it was denied:
   Settings → Privacy & Security → Local Network → FixMate.

> The IP comes from DHCP. If it changes, update `env.json` and re-run `valet loopback <new-ip>`.
> While loopback points at the LAN IP, `.test` sites don't work offline — revert to `127.0.0.1` when off WiFi.

## Build

```bash
./build_apk.sh                 # env.json → build/app/outputs/flutter-apk/<API_NAME>-fixmate-<version>.apk
./build_apk.sh env.orga.json   # another tenant's env file
flutter build ipa --release --dart-define-from-file=env.json --export-method ad-hoc
```

Never run `dart format` on this package — see `.claude/skills/flutter-apps`.
