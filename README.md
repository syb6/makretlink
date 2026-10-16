# MarketLink — eGreen Basket

Farmers-market pre-order platform: farmers publish weekly stock, customers reserve online,
then collect and pay in person at the market stall. Built with **Laravel 12 (API and
sessions) and Angular 22 with Bootstrap 5**.

## Local development

Install PHP/Composer, Node.js (supported by Angular 22), and npm. In one terminal:

```bash
composer install
```

Copy `.env.example` to `.env`, then generate the app key and set up the local SQLite
database:

```bash
php artisan key:generate
php artisan migrate --seed
```

Run Laravel and Angular in separate terminals:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

```bash
cd frontend
npm ci
npm start
```

Open <http://localhost:4200>. The Angular dev server proxies `/api` and `/sanctum` to
Laravel, so session login and CSRF protection work without enabling cross-origin API
access. For a production Angular build, run `npm run build` from `frontend/`; it writes
the app to `public/spa`, which Laravel serves at <http://localhost:8000/spa/>.

Demo accounts (seeded): `admin@marketlink.test`, `farmer1@marketlink.test`,
`customer1@marketlink.test` — password `password` for all.

- Email uses **Resend** when `RESEND_API_KEY` is set; otherwise it falls back to the log
  transport automatically (nothing crashes without a key).
- The AI assistant uses **Groq** when `GROQ_API_KEY` is set; otherwise it answers from a
  built-in rule engine.

## Deploying to Railway

The repo ships with `railway.json` (frontend/backend build and start commands). Its
`preDeployCommand` applies migrations, links storage and warms Laravel's production
caches. It deliberately does not run demo seeders: they create accounts with the
publicly documented `password` password and would overwrite sample data on redeploy.
The root `package.json` pins Railway's Nixpacks Node selection to Node 22 because the
Nixpkgs snapshot used by the reported build does not provide `nodejs_24`.

### One-time setup

1. Push this repo to GitHub, then in Railway: **New Project → Deploy from GitHub repo**.
2. Add a **MySQL** database service to the project (Database → **Add a Database → MySQL**).
3. On your app service, set these variables (Raw Editor):

   ```env
   APP_KEY=<paste output of: php artisan key:generate>
   APP_ENV=production
   APP_DEBUG=false
   APP_NAME=MarketLink
   APP_URL=https://<your-railway-domain>
   SANCTUM_STATEFUL_DOMAINS=<your-railway-domain>
   SESSION_SECURE_COOKIE=true

   DB_CONNECTION=mysql
   DB_URL=${{MySQL.DATABASE_URL}}

   LOG_CHANNEL=stderr
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database

   RESEND_API_KEY=<your key>            # real emails; omit = log transport
   GROQ_API_KEY=<your key>              # AI assistant; omit = rule-based answers
   MAIL_FROM_ADDRESS=you@yourdomain.com
   MAIL_FROM_NAME=MarketLink
   ```

4. **Deploy**. The pre-deploy command runs migrations and builds Laravel's caches.
5. In the app service: **Settings → Networking → Generate Domain** to get a public URL.
   HTTPS is enforced automatically behind Railway's proxy.
6. If users will upload profile, product, category, or market images, add a Railway
   volume mounted at `/app/storage/app/public` before accepting uploads. The app serves
   those files through `/storage`; the volume keeps them across deploys. Railway volumes
   are attached to a single replica, so use S3-compatible object storage before scaling
   the app horizontally.
7. Create the initial administrator through a controlled one-time operation. Do not
   run `php artisan db:seed` against production; it creates demo users. To promote a
   pre-registered account, open the app service shell and run `php artisan tinker`,
   then:

   ```php
   $user = App\Models\User::where('email', 'admin@example.com')->firstOrFail();
   $user->forceFill(['role' => 'admin', 'status' => 'active'])->save();
   ```

### Optional services

- **Worker** (only if you later queue notifications): add a service from the same repo
  with start command `php artisan queue:work --tries=3 --timeout=60`.
- **Cron** (for scheduled tasks): same repo, start command
  `php artisan schedule:run` on a minute schedule. (No tasks are scheduled today.)

### Notes

- New product, market, category, and profile uploads use Laravel's public storage disk;
  mount the Railway volume at `/app/storage/app/public` to persist them across deploys.
  Bundled placeholder images remain part of the application release.
- Run `php artisan migrate --force` through the pre-deploy step; seed demo data only in
  local/test environments.
- Logs go to **stderr** so they appear in `railway logs`.
