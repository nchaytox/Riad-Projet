# Deployment (no Docker)

Guide to deploy the Laravel app on a classic LAMP/LEMP stack today, and how to evolve toward containerised environments later.

## 1. Prerequisites
- Ubuntu 22.04 LTS (or similar) with Apache 2.4 or Nginx 1.18+.
- PHP-FPM 8.2 with required extensions: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `curl`.
- MySQL 8 (or MariaDB 10.6+) with a dedicated database/user.
- Node.js 18+ and npm for asset build on CI or deploy host.
- Supervisor/systemd to run queues and websocket server (if using Reverb).

## 2. Virtual host setup
1. Clone repo to `/var/www/riad-projet`.
2. Set owner to the web user: `sudo chown -R www-data:www-data storage bootstrap/cache`.
3. Apache vhost example:
   ```
   <VirtualHost *:80>
       ServerName riad.example.com
       DocumentRoot /var/www/riad-projet/public

       <Directory /var/www/riad-projet/public>
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/riad-error.log
       CustomLog ${APACHE_LOG_DIR}/riad-access.log combined
   </VirtualHost>
   ```
4. Enable site (`a2ensite`), enable `mod_rewrite`, reload Apache.
5. For Nginx, proxy to PHP-FPM (`fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;`) with root `public/`.

## 3. Environment configuration
Copy `.env.example` to `.env` and adjust:
- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://riad.example.com`.
- `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_DOMAIN=riad.example.com`.
- Database credentials (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
- Cache/queue driver (`redis` if available, fallback `file`/`sync`).
- Mailer: use SMTP provider; never leave `log` in production.
- Broadcast: configure Pusher/Reverb keys and `REVERB_SCHEME=https`.
- CORS: restrict to trusted domains (`CORS_ALLOWED_ORIGINS`).
- APP_KEY: generate via `php artisan key:generate --ansi` once, then store securely.

## 4. Deployment steps (current process)
1. Pull latest release: `git fetch --all && git checkout <tag-or-branch>`.
2. Install PHP deps: `composer install --no-dev --optimize-autoloader`.
3. Build assets: `npm ci && npm run build` (or pull artefacts from CI).
4. Link storage: `php artisan storage:link` (once).
5. Run migrations: `php artisan migrate --force`.
6. Seed if needed: `php artisan db:seed --class=ProductionSeeder` (optional).
7. Cache configs:  
   ```
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
8. Restart queue/websocket workers.
9. Clear old caches only when debugging (`php artisan config:clear`) to avoid downtime.

## 5. Zero-downtime checklist (basic)
1. **Prepare code**: build artefacts on CI, upload to target (Capistrano, Envoy, Deployer).
2. **Put app in maintenance mode (optional)**: `php artisan down --secret="maintenance-token"`.
3. **Swap symlink** to new release directory (deploy tool handles).
4. **Run migrations** before bringing traffic back (if destructive, use blue/green).
5. **Cache config/routes/views**.
6. **Run health checks**: `curl -f https://riad.example.com/readyz`.
7. **Bring app back**: `php artisan up` (if maintenance mode used).
8. **Smoke test**: login, new booking, cancellation, admin view.

## 6. Post-deploy verification
- Check logs (`storage/logs/laravel.log`) for errors.
- Monitor `/health` endpoint returns 200 and includes DB/cache status.
- Ensure queue workers are processing jobs (e.g. `php artisan queue:failed` empty).

## 7. Future roadmap (container friendly)
| Phase | Goal | Notes |
| --- | --- | --- |
| Docker images | Build PHP-FPM + Nginx images with multi-stage Node build. | Use Laravel Sail or custom Dockerfile; publish to registry. |
| CI/CD | GitHub Actions pushes versioned images. | Tag with commit SHA; sign images (cosign). |
| Staging env | Stand-alone staging VM or k8s namespace with seeded data. | Automate `php artisan migrate --force --env=staging`. |
| Prod gate | Require staging ZAP success and manual approval before prod deploy. | Use GitHub environments with required reviewers. |

Keep this document updated as deployment automation matures (Ansible, Terraform, k8s manifests, etc.).
