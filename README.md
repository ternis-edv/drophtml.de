# DropHTML.de

Instant static site hosting for developers. Drag, drop, and publish.

## 🚀 Features

- **Drag-and-Drop Publishing:** Upload `.html` files or `.zip` archives directly from your browser.
- **GitHub Integration:** Deploy repositories directly from your GitHub account or organizations.
- **Auto-Deploy:** Enable webhooks to automatically re-deploy your site on every push to the main branch.
- **Advanced File Manager:** Create, rename, edit, and delete files directly in the dashboard.
- **Custom Domains:** Link your own domains with simple CNAME configuration.
- **Real-time Analytics:** Track views, unique visitors, and device distribution with built-in charts.
- **Secure ID System:** Uses UUIDs and secure hashes for all records and public URLs.
- **Activity Logging:** Comprehensive audit trail for every action on the platform.

## 🛠 Tech Stack

- **Framework:** Laravel 13
- **Frontend:** Livewire + Alpine.js
- **UI Components:** Flux UI
- **Styling:** Tailwind CSS 4.0
- **Database:** SQLite (default) / PostgreSQL / MySQL
- **Charts:** Chart.js
- **Auth:** Laravel Socialite (GitHub OAuth)

## 📦 Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/ternis-edv/drophtml.de.git
    cd drophtml.de
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    npm install
    ```

3.  **Setup environment:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Database setup:**
    ```bash
    touch database/database.sqlite
    php artisan migrate
    ```

5.  **GitHub OAuth (Optional):**
    Add your GitHub credentials to `.env`:
    ```env
    GITHUB_CLIENT_ID=your_client_id
    GITHUB_CLIENT_SECRET=your_client_secret
    ```

6.  **Compile assets and start:**
    ```bash
    npm run build
    php artisan serve
    ```

## 🧹 Maintenance

To automatically cleanup expired temporary sites, schedule the following command:
```bash
php artisan sites:cleanup
```

## 📄 License

The Laravel framework is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
DropHTML logic and branding are owned by [ternis-edv.de](https://ternis-edv.de).
