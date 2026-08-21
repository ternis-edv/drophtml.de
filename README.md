# DropHTML.de

> **Instant static site hosting for developers, creators, and teams.**  
> Drag, drop, and publish HTML in seconds — with GitHub CI/CD, password protection, and custom domains.

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.0-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Livewire](https://img.shields.io/badge/Livewire-3.0-4e56a6?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Tests](https://img.shields.io/badge/Tests-Pest_PHP-8957e5?style=flat-square&logo=php&logoColor=white)](https://pestphp.com)
[![License](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)

---

## ✨ Features

- **🚀 Drag-and-Drop Publishing:** Upload single `.html` files or full `.zip` archives directly from your browser.
- **🐙 GitHub CI/CD & Auto-Deploy:** Connect your GitHub account to deploy repositories. Webhooks automatically redeploy on pushes to default branches.
- **🔒 Password Protection:** Protect any site, staging environment, or client preview behind password authentication with session unlocking.
- **🌐 Custom Domains & Subdomains:** Route your sites through custom domains with real-time CNAME and DNS verification.
- **📁 In-Browser File Manager & Editor:** Browse nested directory structures (`css/`, `js/`, `assets/`, `images/`), edit code in real-time, preview binary assets, and use keyboard shortcuts (`Cmd+S`/`Ctrl+S`).
- **📊 Privacy-Friendly Analytics:** Track daily page views, unique visitors, referral sources, and device distribution with built-in Chart.js visualisations.
- **⚡ REST API & OpenAPI Docs:** Complete REST API with interactive API documentation powered by Scramble (`/docs/api`).
- **🛡️ Secure Authentication:** Full Two-Factor Authentication (2FA), signed email verification, and GitHub OAuth support with Laravel Fortify and Socialite.
- **⏳ Flexible Retention:** Keep sites as 30-day temporary shares or toggle to permanent with zero expiration.

---

## 👥 Project & Credits

DropHTML is developed, maintained, and operated in collaboration between:

* **[ternis-edv.de](https://ternis-edv.de)** & **[ternis.dev](https://ternis.dev)**
* **[xpsystems.eu](https://xpsystems.eu)** & **[xpsystems.de](https://xpsystems.de)**
* **[mtex.dev](https://mtex.dev)**

Domain & DNS infrastructure provided by:
* **[dnbx.de](https://dnbx.de)**

Part of our developer utilities network:
* [httpclient.de](https://httpclient.de) — Modern HTTP client & testing tool
* [api-sandbox.de](https://api-sandbox.de) — Mock API and sandbox environment

---

## 🛠 Tech Stack

* **Backend:** Laravel 13 (PHP 8.2+)
* **Frontend:** Livewire 3 + Alpine.js
* **UI Components:** Flux UI
* **Styling:** Tailwind CSS v4
* **Database:** SQLite (default) / PostgreSQL / MySQL / MariaDB
* **Analytics & Charts:** Chart.js
* **Auth & Security:** Laravel Fortify + Laravel Socialite + Laravel Sanctum
* **API Documentation:** Dedoc Scramble

---

## 🚀 Quick Start

### Prerequisites
* PHP >= 8.2 (with `zip`, `sqlite3`, `curl`, `mbstring`, `openssl` extensions)
* Composer
* Node.js & npm

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/ternis-edv/drophtml.de.git
   cd drophtml.de
   ```

2. **Install PHP and JavaScript dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Set up environment configuration:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Prepare database and storage:**
   ```bash
   touch database/database.sqlite
   php artisan migrate
   php artisan storage:link
   ```

5. **Build frontend assets:**
   ```bash
   npm run build
   ```

6. **Start the development server:**
   ```bash
   php artisan serve
   ```
   Visit `http://localhost:8000` in your browser.

---

## ⚙️ Environment Variables

| Variable | Description | Default |
|---|---|---|
| `APP_NAME` | Name of the platform | `DropHTML.de` |
| `APP_URL` | Base public URL | `http://localhost:8000` |
| `DB_CONNECTION` | Database driver (`sqlite`, `pgsql`, `mysql`) | `sqlite` |
| `MAIL_MAILER` | Mail driver (`smtp`, `log`, `ses`, `postmark`, `resend`) | `log` |
| `GITHUB_CLIENT_ID` | GitHub OAuth App Client ID | *(optional)* |
| `GITHUB_CLIENT_SECRET` | GitHub OAuth App Client Secret | *(optional)* |

---

## 📡 REST API Usage

DropHTML provides a full REST API for deploying and managing sites programmatically using API Tokens created in your dashboard settings (`/settings/api-tokens`).

### Create a Site with ZIP or HTML Upload
```bash
curl -X POST http://localhost:8000/api/sites \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -F "original_name=My Awesome Landing" \
  -F "file=@site.zip" \
  -F "is_permanent=true"
```

### List Your Sites
```bash
curl -X GET http://localhost:8000/api/sites \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

### Link a Custom Domain
```bash
curl -X POST http://localhost:8000/api/sites/{site_id}/domains \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"domain": "example.com"}'
```

Interactive OpenAPI documentation is available at `/docs/api`.

---

## 🧪 Testing & Code Quality

Run the automated test suite powered by Pest PHP:
```bash
php artisan test
```

Format code according to Laravel standards:
```bash
./vendor/bin/pint
```

---

## 🧹 Scheduled Maintenance

To automatically clean up expired temporary sites and their storage files:
```bash
php artisan sites:cleanup
```

Add this command to your server's cron or Laravel Scheduler in `routes/console.php`.

---

## 📄 License & Legal

* Core code and features licensed under the [MIT License](LICENSE).
* Trademarks and platform branding are owned by [ternis-edv.de](https://ternis-edv.de).
* Imprint, privacy policy, and terms of service are accessible at `/legal/imprint`, `/legal/privacy`, and `/legal/terms`.
