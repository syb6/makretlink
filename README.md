# MarketLink — eGreen Basket

Farmers-market pre-order platform: farmers publish weekly stock, customers reserve online,
then collect and pay in person at the market stall. Built with **Laravel 12, Blade,
Bootstrap 5, vanilla CSS/JS and MySQL**.

## Local development

```bash
composer install
cp .env.example .env          # then set APP_KEY (php artisan key:generate)
php artisan migrate --seed    # SQLite works out of the box locally
php artisan serve
```

Demo accounts (seeded): `admin@marketlink.test`, `farmer1@marketlink.test`,
`customer1@marketlink.test` — password `password` for all.

- Email uses **Resend** when `RESEND_API_KEY` is set; otherwise it falls back to the log
  transport automatically (nothing crashes without a key).
- The AI assistant uses **Groq** when `GROQ_API_KEY` is set; otherwise it answers from a
  built-in rule engine.

## Deploying to Railway

The repo ships with `railway.json` (build + start commands). Its `preDeployCommand`
migrates, seeds idempotently, links storage and warms caches — so every deploy is
hands-off with no extra scripts to maintain.

### One-time setup

1. Push this repo to GitHub, then in Railway: **New Project → Deploy from GitHub repo**.
2. Add a **MySQL** database service to the project (Database → **Add a Database → MySQL**).
3. On your app service, set these variables (Raw Editor):

   ```env
   APP_KEY=<paste output of: php artisan key:generate>
   APP_ENV=production
   APP_DEBUG=false
   APP_NAME=MarketLink

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

4. **Deploy**. The pre-deploy command runs migrations + seed + caches automatically.
5. In the app service: **Settings → Networking → Generate Domain** to get a public URL.
   HTTPS is enforced automatically behind Railway's proxy.

### Optional services

- **Worker** (only if you later queue notifications): add a service from the same repo
  with start command `php artisan queue:work --tries=3 --timeout=60`.
- **Cron** (for scheduled tasks): same repo, start command
  `php artisan schedule:run` on a minute schedule. (No tasks are scheduled today.)

### Notes

- **Storage is ephemeral on Railway.** Uploaded images (products, profiles) live on disk
  and vanish on redeploy. For production durability move `FILESYSTEM_DISK` to S3-compatible
  storage — the code already goes through Laravel's Storage layer.
- Seeding is idempotent (`updateOrCreate` everywhere), so redeploys never duplicate data.
- Logs go to **stderr** so they appear in `railway logs`.
