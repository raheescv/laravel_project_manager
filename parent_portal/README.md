# Parent Portal

Standalone Vue 3 app for a school's parents: their children's canteen card balance, bills,
card statement, "lost card" blocking and online top-up through QPay. It is fully
independent of the `project_manager` Laravel backend — no code is shared; the
`/api/v1/parent` REST API is the only integration surface.

Phone-first, installs as a home-screen web app, and works from any folder or host. On a
computer it becomes a web page (see [On a computer](#on-a-computer)).

## The screens

| Route (hash)             | Screen                                                        |
| ------------------------ | ------------------------------------------------------------- |
| `#/login`                | Sign in with mobile number (or email) + password              |
| `#/forgot-password`      | Ask for a new set-password link (mobile number or email)      |
| `#/set-password/:token`  | The link from the invite / reset email — choose a password    |
| `#/`                     | My children — balance and card status for each child          |
| `#/students/:id`         | One child — balance, top up, block a lost card, bills, statement |
| `#/students/:id/bills/:saleId` | One bill — lines, discount, tax, how it was paid        |
| `#/students/:id/topup`   | Choose an amount → QPay's payment page                        |
| `#/topups/:pun`          | The payment result QPay returns to (polls while confirming)   |
| `#/profile`              | The parent's details (read-only), children, password, sign out |
| `#/profile/password`     | Change password (current + new); other sign-ins end           |
| `#/students/:id/pre-orders` | Canteen meals — the weekly order and the next school days  |
| `#/students/:id/pre-orders/weekly` | Every week: the meal and the days                   |
| `#/students/:id/pre-orders/days/:date` | One day: that day's dishes, order or skip it    |

## On a computer

From 1024px wide (laptops, desktops, an iPad in landscape) the portal switches to the
**Wallet split** layout: the children's cards stay on the left, and the page opens on the right.
The open child's card moves to the top of the stack. The routes and API calls are the same
as on a phone; only the layout changes. Phones keep the app layout exactly as it is.

- Sign-in pages get a panel in the school's colour beside the form (`AuthFrame.vue`).
- Home becomes an overview: the total on the cards, which cards work, and who needs a top-up.
- A child's page leads with the numbers, with the bills/statement beside the card and meals.
- Meals show the next school days as a board; top-up shows a summary with the pay button.
- Sheets become centred dialogs, and the account sheet becomes a menu (`AccountMenu.vue`).

The breakpoint lives in two places that must match: `src/utils/viewport.js` (`desktop`, for
markup that differs) and the `@media (min-width:1024px)` block at the end of `app.css`.
The wallet and the home page share one list of children (`src/children.js`). Each page that
loads a child also updates that child in the list, so the wallet's balances stay current.
The design was picked from `docs/parent-portal-web-preview.html` in the main repo (direction C).

## Canteen meals (pre-orders)

The school switches pre-orders on in **Settings → Student Cards** (days the canteen is open,
the daily cut-off time, the categories on the menu) and writes each meal's dishes in
**Students → Canteen Menu** — courses down, school days across, the same every week.

A parent orders **one meal per day**: for a single day, or every week on chosen days (a
day's own order or a "don't order this day" wins over the weekly order). Nothing is
charged when ordering. When the child's card is tapped, QLOUD POS puts the meal in the
cart (with the parent's note); the cashier charges it to the card as usual, and the
completed sale marks that day's order collected.

## Online top-up (QPay)

1. The parent picks an amount. `POST /students/{id}/topups` opens the payment and returns
   QPay's gateway URL plus the **signed** form fields (the secret key never leaves the
   server).
2. The app posts that form, taking the browser to QPay's page.
3. QPay posts the result to the API (`POST /api/v1/parent/qpay/return`). The API checks
   QPay's secure hash (or asks QPay's inquiry API when the hash is wrong), credits the card
   through the ledger exactly once, and redirects the browser to this app at
   `#/topups/{pun}`.
4. The result page shows reference (PUN), amount, status, date & time — polling every few
   seconds while the payment is still being confirmed.

For step 3 the API must know where this app lives: set **Settings → Student Settings →
Parent portal address** in the school's admin (or `PARENT_PORTAL_URL` in the API's `.env`).
The same address is used for the links in invite and password-reset emails.

## Setup

```bash
cd parent_portal
cp .env.example .env   # then edit
npm install
npm run dev            # http://localhost:5190
npm run build          # static bundle in dist/ — deploy anywhere
```

In dev, `/api` is proxied to `VITE_PROXY_TARGET` (default `https://project_manager.test`),
so there is no CORS or self-signed certificate trouble.

## Configuration (.env)

| Variable            | Meaning                                                                        |
| ------------------- | ------------------------------------------------------------------------------ |
| `VITE_API_BASE_URL` | The school's API, e.g. `https://school.example.com/api/v1`. Empty = same host as the portal. |
| `VITE_TENANT`       | Tenant subdomain — sent as `X-Tenant-Subdomain` + `?tenant=`. Only needed when the API is reached on a bare IP / localhost. |
| `VITE_PROXY_TARGET` | Dev only: where `npm run dev` proxies `/api` and `/storage`.                   |

Every `VITE_` value is baked into the public bundle — never put a secret here.

## Deploying

`npm run build` produces a static `dist/`. Relative asset URLs and hash routing mean it can
be served from any host or sub-folder with no rewrite rules. Build once per school when
schools use different API hosts (`VITE_API_BASE_URL`), then set that school's **Parent
portal address** to where the build is published.

If the portal and the API are on different origins, the API's CORS config must allow the
portal's origin for `api/*` (the default `config/cors.php` allows all).

## Sign-in and security

- Sign-in returns a Sanctum bearer token. "Keep me signed in" keeps it in `localStorage`
  (30-day token); otherwise `sessionStorage` (12-hour token).
- The token only works on `/api/v1/parent/*`, and staff tokens never work there.
- A 401 from the API (token expired, signed out, or the parent disabled by the school)
  clears the session and returns to sign-in, remembering the page the parent was on.
- Setting a new password from a link ends the parent's other sign-ins; changing it on the
  Profile page (which asks for the current one) ends every sign-in but the current one.
- The parent's name, mobile and email are read-only in the portal. The school office owns
  them: the mobile links brothers and sisters to one login, and the email is a sign-in name.

## API endpoints consumed

All under `/api/v1/parent`, all using the `{ success, data, message }` envelope (the Axios
client unwraps it).

| Endpoint                                 | Used for                                  |
| ---------------------------------------- | ----------------------------------------- |
| `GET  /school`                           | Name, logo, theme colour (`--accent`), currency |
| `POST /login`                            | Sign in → token                           |
| `POST /forgot-password`                  | Send a new set-password link (`login` = mobile or email) |
| `GET  /set-password/{token}`             | Is the link still valid?                  |
| `POST /set-password`                     | Choose a password → token                 |
| `GET  /me` · `POST /logout`              | The signed-in parent · sign out           |
| `POST /password`                         | Change password; ends the other sign-ins  |
| `GET  /students`                         | Home                                      |
| `GET  /students/{id}`                    | Child page header, top-up limits          |
| `GET  /students/{id}/bills?month=&page=` | Bills tab                                 |
| `GET  /students/{id}/bills/{sale}`       | Bill page                                 |
| `GET  /students/{id}/statement?month=`   | Statement tab                             |
| `POST /students/{id}/card/block`         | "Lost card?"                              |
| `POST /students/{id}/topups`             | Start a QPay top-up                       |
| `GET  /topups/{pun}`                     | Top-up result                             |
| `GET  /pre-order-menu`                   | Meals with their weekly dishes            |
| `GET  /students/{id}/pre-orders`         | Weekly order + next school days           |
| `PUT  /students/{id}/pre-orders/weekly` · `POST …/weekly/pause` · `POST …/weekly/resume` · `DELETE …/weekly` | Weekly order |
| `PUT  /students/{id}/pre-orders/days/{date}` · `POST …/days/{date}/skip` · `DELETE …/days/{date}` | One day |

## Structure

```
src/
├── api/          client.js (axios + token + tenant + envelope), parent.js (per-endpoint fns)
├── session.js    the parent's token on this device
├── school.js     school branding → --accent, currency
├── children.js   the parent's children, shared by home and the desktop wallet
├── router/       hash routes + sign-in guard
├── utils/        format.js (money, dates, months), qpay.js (post to QPay), viewport.js (desktop)
├── components/   shared UI pieces
└── views/        one file per screen
```
