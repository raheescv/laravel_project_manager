# QLOUD POS — Flutter app (mobile + tablet)

Premium **POS + admin** for iOS / Android (and web for review), wired to the
Laravel `api/v1` backend in this repo.

Brand: the logomark is a vector in `lib/shared/widgets/qloud_logo.dart`
(`QloudLogomark`); its source artwork is `assets/brand/qloud_mark.svg`, which is
also what the launcher icons in `assets/icon/` are rendered from. The default
theme preset is **Qloud Blue**, sampled off the logomark.

The design system predates the QLOUD rebrand and still uses the `Astra*` prefix
internally (`AstraPalette`, `AstraCard`, `AstraSkin`, …) — those are code-level
names only and are never shown to users. Settings → Theme still offers the four
older presets (Aurora Glass, Luminous Indigo, Emerald & Gold, Editorial Noir)
alongside Qloud Blue.

The original design source lives in `designs/astra-mobile/` (the Claude Design
handoff bundle).

## Run

```bash
cd mobileApp
flutter pub get
cp env.example.json env.json   # then edit env.json (see below)
flutter run --dart-define-from-file=env.json   # device / emulator
flutter run -d chrome          # quick design review in the browser
```

The default connection is read at build time from `env.json` via
`--dart-define-from-file` (mapped into `AppConfig` in `lib/core/config.dart`):

| Key | Meaning |
|---|---|
| `API_BASE_URL` | Laravel host, no trailing slash, no `/api`. |
| `API_TENANT` | Tenant subdomain, sent as `X-Tenant-Subdomain` / `?tenant=`. |

`env.json` is **gitignored** (per-machine); `env.example.json` is the committed
template. Without an env file, `flutter run` falls back to
`https://project_manager.test` (works on the host machine / simulator only).

You can still override the connection at runtime: tap the **gear** on the login
screen (Settings → Server connection). Then log in with a staff/admin **MPIN**
(auto-submits at 4 digits).

### Connecting a physical device over WiFi

A real iPhone/Android can't resolve the host's `.test` domain, so point the app
at the **LAN IP** of the machine running Valet.

1. Find the Mac's WiFi IP: `ipconfig getifaddr en0` (e.g. `192.168.68.106`).
2. Set it in `env.json`, e.g. `"API_BASE_URL": "https://192.168.68.106"`.
   (HTTPS works — debug builds accept Valet's self-signed cert; HTTP also works.)
3. Expose Valet on the LAN (it binds to `127.0.0.1` by default):
   ```bash
   valet loopback 192.168.68.106    # revert with: valet loopback 127.0.0.1
   ```
4. Keep the phone on the **same WiFi** and run with `--dart-define-from-file=env.json`.

> **Note:** this ties everything to the Mac's current DHCP IP. If the IP changes
> (reconnect/reboot), update `env.json` and re-run `valet loopback <new-ip>`. To
> avoid that, reserve a **static DHCP lease** for the Mac on your router so it
> always gets the same IP. While `valet loopback` points at the LAN IP, the `.test`
> domains resolve there too and won't work offline — revert to `127.0.0.1` when off WiFi.

### Device can't reach the server (after the IP changed)

Re-point everything at the Mac's current IP in one go (paste as **one** line so
`$IP` is set):

```bash
IP=$(ipconfig getifaddr en0) && [ -n "$IP" ] && valet loopback "$IP" && sed -i '' -E "s#(\"API_BASE_URL\": *\")[^\"]*#\1https://$IP#" env.json && grep API_BASE_URL env.json
flutter run --dart-define-from-file=env.json
```

`env.json` **must** also contain `"API_HOST": "project_manager.test"`. Valet picks
the site by host name, so a request to a bare IP without that Host header gets
Valet's own **"404 - Not Found"** page instead of Laravel.

Check each step if it still fails:

```bash
grep loopback ~/.config/valet/config.json         # must equal $(ipconfig getifaddr en0)
lsof -nP -iTCP:443 -sTCP:LISTEN | grep -v 127.0.0.1   # nginx must listen on <IP>:443
curl -k -s -H 'Host: project_manager.test' -H 'Accept: application/json' -X POST https://$(ipconfig getifaddr en0)/api/v1/login
# {"message":"The PIN is required."...} = server OK · "Valet - Not Found" = Host header missing · no output = not reachable
```

- Open `https://<IP>` in the **phone's browser** — if that fails, it's the network
  (different WiFi / guest network / macOS firewall blocking nginx), not the app.
- A connection saved from the login-screen **gear** wins over `env.json`. If the
  app still uses the old IP, open the gear and tap **"This build was made for … · Use it"**
  (or re-enter the IP) and Save.
- iOS: allow **Settings → QLOUD POS → Local Network**, otherwise LAN calls are silently blocked.
- See which URL the app is **actually** calling (Android):
  ```bash
  adb logcat -d | grep "could not reach"    # e.g. "[auth] sign-in could not reach https://<old-ip>"
  ```
  If that shows an old IP, it's the saved connection above — fix it from the gear, not env.json.

### `flutter run` doesn't see the device

```bash
flutter devices                                   # list what Flutter can see
adb kill-server && adb start-server && adb devices   # Android: restart adb, then re-accept the USB-debugging prompt
xcrun devicectl list devices                      # iOS: State must be "available (paired)", not "unavailable"
flutter run -d <device-id> --device-timeout 30 --dart-define-from-file=env.json
```

**Android device:**

1. On the phone: **Settings → About phone → tap Build number 7×** → back to
   **Developer options → USB debugging → On**.
2. Plug in USB, accept **Allow USB debugging** on the phone, then:
   ```bash
   adb devices -l                                   # must say "device", not "unauthorized"/"offline"
   flutter run -d <serial> --dart-define-from-file=env.json
   ```
3. Check the phone can reach the Mac's server (same WiFi, port 443 open):
   ```bash
   adb shell 'ip -4 addr show wlan0 | grep inet'    # phone IP — same 192.168.x subnet as the Mac
   adb shell 'echo | nc -w 3 <mac-ip> 443 && echo OPEN || echo CLOSED'
   ```
4. Optional, no cable (Android 11+): **Developer options → Wireless debugging → Pair
   device with pairing code**, then `adb pair <ip>:<pair-port>` and
   `adb connect <ip>:<port>` (ports shown on that screen).

**iPhone shows `unavailable` / "Browsing on the local area network … (code -27)"** —
wireless debugging can't find the phone. Fix it once over a cable:

1. Plug the iPhone in with USB, unlock it, tap **Trust This Computer**.
2. On the phone: **Settings → Privacy & Security → Developer Mode → On** (it reboots).
3. Xcode → **Window → Devices and Simulators** → select the phone → tick
   **Connect via network**. Wait until it finishes "Preparing device".
4. Unplug. From now on it works wirelessly **only while the phone is unlocked and
   on the same WiFi as the Mac** (not a guest/isolated network). If it drops,
   just run with the cable plugged in — USB is always faster and more reliable.

## Build

```bash
# Android — debug APK (quick device sideload)
flutter devices
flutter build apk --dart-define-from-file=env.json
flutter run -d 1A291FDEE002SK 2>&1 --dart-define-from-file=env.json
flutter run -d 1A291FDEE002SK 2>&1 --dart-define-from-file=env.json
flutter run -d 00008120-0014609A2E44201E  --release --dart-define-from-file=env.json


flutter build apk --dart-define-from-file=env.json --target-platform android-arm64

# Android — release APK (signed, for distribution)
flutter build apk --release --dart-define-from-file=env.json
flutter build apk --release --dart-define-from-file=env.json --target-platform android-arm64
flutter build apk --release --split-per-abi --dart-define-from-file=env.json --target-platform android-arm,android-arm64

# Android — App Bundle (Play Store upload)
flutter build appbundle --release --dart-define-from-file=env.json

# iOS — development build (registered devices, no Distribution cert needed)
flutter build ipa --release --dart-define-from-file=env.json --export-method development

# iOS — ad-hoc (specific registered devices, needs Ad Hoc provisioning profile)
flutter build ipa --release --dart-define-from-file=env.json --export-method ad-hoc

# iOS — App Store (requires iOS Distribution certificate in Keychain)
flutter build ipa --release --dart-define-from-file=env.json --export-method app-store

# Web (browser review / staging)
flutter build web --dart-define-from-file=env.json
```
flutter build windows --release --dart-define-from-file=env.json


### Production build (SIZERUN)

The production connection is committed in `.env` and turned into `env.json` by
`gen_env.sh` (no manual editing of `env.json` needed):

```
ENV=prod
API_BASE_URL_PROD=https://sizerun.astraqatar.com
API_TENANT_PROD=sizerun
```

Generate the env file, then build the target you need:

```bash
cd mobileApp
bash gen_env.sh                                             # writes env.json from .env (ENV=prod)
flutter build apk --release --dart-define-from-file=env.json      # Android APK
flutter build appbundle --release --dart-define-from-file=env.json # Play Store bundle
flutter build ipa --release --dart-define-from-file=env.json --export-method app-store  # iOS
```

`gen_env.sh` reads `ENV` in `.env` (`lan` | `prod`) and writes the matching
`API_BASE_URL` / `API_TENANT` into `env.json`. To build against the LAN backend
instead, set `ENV=lan` in `.env` and re-run `gen_env.sh`.

Output locations:
| Target | Path |
|---|---|
| APK | `build/app/outputs/flutter-apk/app-release.apk` |
| App Bundle | `build/app/outputs/bundle/release/app-release.aab` |
| IPA | `build/ios/archive/Runner.xcarchive` |
| Web | `build/web/` |

> **iOS signing:** `--export-method development` only needs an Apple Developer
> account + Xcode auto-signing (good for device testing). `ad-hoc` needs an Ad Hoc
> provisioning profile; `app-store` needs an **iOS Distribution certificate** in
> your Keychain (issued via Certificates, Identifiers & Profiles on developer.apple.com).
>
> **Android signing:** release builds require `android/key.properties` pointing to a
> keystore — see the [Flutter docs](https://docs.flutter.dev/deployment/android).

## What's implemented

| Area | Screens | API |
|---|---|---|
| Auth | PIN login, change MPIN | `POST /login`, `/change-pin`, `/logout` |
| Sale flow | New Sale (search/scan/category), Cart, Edit-line sheet, Review & Pay, Invoice | `GET /products`,`/categories`, `POST /sale` |
| Sales | Sales list + open invoice | `GET /sale`, `GET /sale/{id}` |
| Admin | Dashboard (KPIs, revenue trend, top stylists) | `GET /admin/dashboard`, `/admin/reports` |
| Reports | Overview, by-day chart, by-invoice & by-stylist tables | `GET /admin/reports` |
| Settings | Live colour-preset picker (5 presets), connection, logout | local |
| Profile | Profile, edit profile | local / `is_admin` gated |

Role routing: **admins** land on the bottom-nav shell (`/home`); **staff** land
directly on **New Sale** (`/sale`).

## Theming

`lib/theme/palette.dart` defines the 5 presets (Emerald & Gold, Slate Blue,
Plum & Rose, Copper Sand, Midnight Teal). Selecting one in **Settings** re-skins
the whole app instantly and persists the choice. All layout/tokens come from one
place (`lib/theme/theme.dart`); presets only swap the palette.

## Architecture

```
lib/
  app/        router (auth guard) + app root
  core/       config, dio client (tenant + bearer + envelope), storage, formatters, icons
  models/     typed models mapped to the api/v1 resources
  state/      auth / theme / cart / catalog / admin controllers (Provider)
  theme/      palette + presets, tokens, ThemeData builder
  widgets/    shared component kit + chart painters
  features/   auth, sale, sales, admin, settings, profile
```

State: `provider` · Routing: `go_router` · HTTP: `dio` · Storage:
`flutter_secure_storage` (token) + `shared_preferences` (config/preset) ·
Fonts: `google_fonts` (Marcellus + Manrope).

## Tenant resolution note

The backend resolves the tenant from the **host subdomain**. It also honours the
`X-Tenant-Subdomain` header / `?tenant=` hint (which this app always sends) when
the host is a literal `localhost` **or a bare IP address** — see
`extractFromIp()` in `app/Http/Middleware/IdentifyTenant.php`. That's what makes
LAN-IP device access resolve the right tenant (set `API_TENANT` in `env.json`).
Connecting via a non-tenant subdomain host that isn't an IP would still 404.

## Known backend gaps (see BUILD_PROMPT.md §8)

- **Payment methods** aren't listed by an endpoint — the Review screen offers
  Card / Cash / UPI; the server matches the name to a configured method.
- **Employees** aren't listed by an endpoint — the per-line stylist defaults to
  the logged-in user (a `GET /employees` endpoint would enable a picker).
- **Profile update** and **barcode camera** are stubbed (manual barcode entry
  works; camera needs `mobile_scanner`).


<!-- pkill -9 -f "bin/flutter" ; pkill -9 -f dart -->
<!-- rm -f "$(dirname "$(dirname "$(which flutter)")")/bin/cache/lockfile" -->
