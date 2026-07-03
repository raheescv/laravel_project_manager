# Environment Configuration Setup

This project uses environment variables via the `flutter_dotenv` package to manage configuration across different environments (dev, staging, production).

## Files

- **`.env`** — Development environment variables (local, not committed to git)
- **`.env.example`** — Template showing all available configuration options (committed to git)

## Setup Instructions

1. **Copy the example file** (if .env doesn't exist):
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env`** with your local configuration:
   ```
   API_BASE_URL=http://localhost:8000
   API_VERSION=v1
   APP_NAME=FixMate
   ENABLE_BIOMETRIC_LOGIN=true
   ENABLE_OFFLINE_MODE=true
   ```

3. **Access variables in code**:
   ```dart
   import 'package:invo/shared/config/environment.dart';

   // Usage
   final apiUrl = Environment.apiBaseUrl;
   final enableBiometric = Environment.enableBiometricLogin;
   ```

## Available Configuration

### API Configuration
- `API_BASE_URL` — Base URL for API calls (default: `https://primedemo.astraqatar.com`)
- `API_VERSION` — API version prefix (default: `v1`)

### LAN Configuration (Local Development)
For testing on physical devices connected to your local development machine:

- `API_BASE_URL_LAN` — LAN API endpoint (default: `http://192.168.68.104:8090`)
- `API_TENANT_LAN` — Tenant name for LAN API (default: `project_manager`)
- `USE_LAN_API` — Switch to use LAN endpoint instead of production (default: `false`)

**To use LAN configuration:**
1. Update your machine's IP: `ipconfig getifaddr en0`
2. Set `API_BASE_URL_LAN=http://<YOUR_IP>:8090`
3. Set `USE_LAN_API=true`
4. Hot restart the app (changes to .env require full restart, not just hot reload)

### App Configuration
- `APP_NAME` — Application name (default: `FixMate`)
- `APP_VERSION` — Application version (default: `1.0.0`)

### Feature Flags
- `ENABLE_BIOMETRIC_LOGIN` — Enable biometric authentication (default: `true`)
- `ENABLE_OFFLINE_MODE` — Enable offline functionality (default: `true`)
- `ENABLE_RECEIPT_PRINTING` — Enable receipt printing (default: `true`)

### Logging
- `ENABLE_LOGGING` — Enable application logging (default: `true`)
- `LOG_LEVEL` — Logging level: `debug`, `info`, `warning`, `error` (default: `debug`)

## For Production

When deploying to production:

1. Update `.env` with production values
2. Ensure `.env` is in `.gitignore` (already configured)
3. Manage production `.env` securely (not in version control)
4. For CI/CD, inject environment variables via build configuration or secrets management

## Notes

- `.env` is loaded automatically during app initialization in `main.dart`
- Changes to `.env` require a hot restart (not just hot reload)
- Boolean values are parsed from strings: `true`, `1` = true, anything else = false
- The `.env` file must be in the project root directory
