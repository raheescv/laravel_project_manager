# Core Connecta (WhatsApp gateway) integration

Project Manager can send WhatsApp messages through three drivers:

- **`meta`**: Meta / wa-api.cloud (existing integration).
- **`localhost`**: Legacy `public/node` whatsapp-web.js server.
- **`core_connecta`**: Multi-tenant Node gateway (Core Connecta). The previous driver name `personal_gateway` is still accepted at runtime and is normalized to `core_connecta` when you open **Settings → WhatsApp**.

## What “Core Connecta” is

**Core Connecta** is your own WhatsApp gateway deployment. Project Manager talks to it over HTTP using:

- The **Gateway URL** (public base URL of the gateway, without `/api`).
- A **tenant API key** (`x-api-key` header), created in the gateway’s admin UI at that same base URL.

Legacy environment variables `PERSONAL_WHATSAPP_GATEWAY_*` are still read as fallbacks if `CORE_CONNECTA_*` are not set.

## Setup (Settings UI)

1. Deploy or run Core Connecta so it is reachable at a stable base URL (for example `https://wa.yourcompany.com` or `http://localhost:3000`).
2. Open that URL in a browser, create or select a **tenant**, and copy its **API key**.
3. In Project Manager: **Settings → WhatsApp**.
4. Set **Driver** to **Core Connecta**.
5. Enter **Gateway URL**, **Tenant API key**, and optionally **Session ID** / **Session name**.
6. Click **Save integration**, then **Refresh QR** and scan with WhatsApp if needed.

## Message behaviour

Application code uses `WhatsappHelper` (facade → `WhatsappManager`). The active driver is chosen from the `whatsapp_driver` configuration value; Core Connecta uses `App\Helpers\CoreConnectaHelper`.

Core Connecta supports text sends via:

```http
POST /api/messages/send
x-api-key: <tenant-api-key>
Content-Type: application/json
```

Project Manager sends `session_id`, `to` (digits-only), and `message` for new conversations, or `session_id`, `conversation_id`, and `message` when replying inside an existing conversation.

`sendTemplateWithImage` is implemented as a **text fallback** that includes the invoice URL, because the gateway may not expose a media/template endpoint yet.

## Environment variables

Settings are stored in the `configurations` table and mirrored to `.env` when you save from the UI. Prefer these names:

```dotenv
WHATSAPP_DRIVER=core_connecta
CORE_CONNECTA_URL=http://localhost:3000
CORE_CONNECTA_API_KEY=
CORE_CONNECTA_SESSION_ID=
CORE_CONNECTA_SESSION_NAME="${APP_NAME}"
```

Configuration keys in the database use the prefix `core_connecta_` (for example `core_connecta_url`). Older installs may still have `personal_whatsapp_gateway_*` keys; those are read as fallbacks until you save again from Settings.

Use `WHATSAPP_DRIVER=meta` for Meta only, or `WHATSAPP_DRIVER=localhost` for the legacy `public/node` server.
