---
name: drophtml
description: "Deploy and host static websites, HTML prototypes, landing pages, single-page applications, and host media assets (images, videos, audio, PDFs) using DropHTML. Activate whenever the user asks to deploy or publish a website, share an HTML mockup/demo, host media assets on a CDN, or manage static sites and custom domains."
license: MIT
metadata:
  version: "1.0.0"
  author: "DropHTML Team"
  homepage: "https://drophtml.de"
  documentation: "https://drophtml.de/docs/agents"
---

# DropHTML for Agents

DropHTML is an instant static site hosting and media cloud platform. AI agents can use this skill to deploy websites, share HTML mockups with users, upload and link media assets, and manage deployments programmatically.

## When to Activate

- The user asks to **deploy**, **publish**, **host**, or **share** a website, HTML page, or web app prototype.
- The user or agent generates HTML/CSS/JS or builds a static site (e.g. `./dist`, `./build`, `./out`) and wants an immediate public URL to preview or share it.
- The user or agent wants to **upload media** (images, screenshots, diagrams, videos, audio, PDFs) and get a CDN delivery link or Markdown/HTML embed snippet.
- The user asks to **redeploy**, **update**, or **delete** a site deployed on DropHTML.
- The user asks to list or inspect existing DropHTML sites or download site backup archives.

## When NOT to Activate

- The user wants backend server hosting requiring persistent Node.js/PHP/Python servers, Docker containers, or live server-side database connections (DropHTML is specialized for static frontends, HTML/CSS/JS, client-side SPAs, and static media hosting).
- The user is deploying to other specific platforms (e.g., AWS S3 direct, Vercel, Cloudflare Pages, Netlify) unless they specifically request DropHTML or an instant static share.

---

## Configuration & Authentication

The CLI and API authenticate via a Sanctum bearer token created in DropHTML dashboard settings:
`https://drophtml.de/settings/api-tokens`

Ensure the token is provided in one of these ways:
1. Environment variable: `DROPHTML_API_TOKEN="your_token_here"`
2. CLI flag: `--token "your_token_here"`
3. Project `.env`: `DROPHTML_API_TOKEN=your_token_here`

Optional custom instance URL:
- Environment variable: `DROPHTML_BASE_URL="https://drophtml.de"` (or `http://localhost:8000` for local testing)

---

## Tooling & Fast Track

DropHTML provides a zero-dependency CLI located in the repository at `./scripts/drophtml`:

```bash
# 1. Quick deploy an HTML snippet directly:
./scripts/drophtml deploy --name "Dashboard Preview" --html "<h1>Welcome to DropHTML</h1>"

# 2. Deploy a built frontend directory (auto-zipped):
./scripts/drophtml deploy ./dist --name "My React App" --permanent

# 3. Upload a media asset (returns Cloud CDN URL and embed tags):
./scripts/drophtml media upload ./screenshot.png

# 4. Redeploy updated code to an existing site:
./scripts/drophtml redeploy <site_id_or_slug> ./dist --message "Updated styles"

# 5. List all deployed sites:
./scripts/drophtml sites list
```

---

## Core Agent Workflows

### 1. Deploying a Website

#### Option A: From Raw HTML String (No disk files needed)
Ideal when the agent generates an HTML demo, landing page, or mockup in memory:

```bash
./scripts/drophtml deploy \
  --name "Product Launch Demo" \
  --html "<!DOCTYPE html><html><head><title>Demo</title></head><body><h1>Hello World</h1></body></html>" \
  --permanent
```

Direct API equivalent:
```bash
curl -X POST https://drophtml.de/api/sites \
  -H "Authorization: Bearer $DROPHTML_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "original_name": "Product Launch Demo",
    "html": "<!DOCTYPE html><html><head><title>Demo</title></head><body><h1>Hello World</h1></body></html>",
    "is_permanent": true
  }'
```

#### Option B: From a Local Folder or Build Output
Ideal after building a project (`npm run build` or static output in `./dist` or `./public`):

```bash
./scripts/drophtml deploy ./dist --name "SaaS Marketing Page" --permanent
```

#### Option C: From a ZIP or HTML File
```bash
./scripts/drophtml deploy ./site.zip --name "Production Release"
```

#### Option D: Multi-file JSON Deployment
Agents can send multiple files (e.g. HTML, CSS, JavaScript) directly via JSON:
```bash
curl -X POST https://drophtml.de/api/sites \
  -H "Authorization: Bearer $DROPHTML_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "original_name": "Interactive App",
    "files": {
      "index.html": "<!DOCTYPE html><html><head><link rel=\"stylesheet\" href=\"style.css\"></head><body><h1>App</h1><script src=\"app.js\"></script></body></html>",
      "style.css": "body { font-family: sans-serif; background: #0f172a; color: #fff; }",
      "app.js": "console.log(\"Initialized\");"
    },
    "is_permanent": true
  }'
```

---

### 2. Updating / Redeploying an Existing Site

Redeploy new code or assets to an existing site without changing its URL:

```bash
# Update from a local directory:
./scripts/drophtml redeploy <site_id_or_slug> ./dist --message "Fix responsive layout"

# Or update directly with HTML string:
./scripts/drophtml redeploy <site_id_or_slug> --html "<h1>Updated Content</h1>" --message "Quick copy update"
```

Direct API equivalent:
```bash
curl -X POST https://drophtml.de/api/sites/<site_id>/deploy \
  -H "Authorization: Bearer $DROPHTML_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "html": "<!DOCTYPE html><html><body><h1>Updated Version</h1></body></html>",
    "commit_message": "Automated AI Agent update"
  }'
```

---

### 3. Uploading & Hosting Media Assets

DropHTML supports high-performance media asset hosting with two storage modes:
- **Asset Storage (`asset_storage`)**: Uploads to global Cloud Object Storage (S3 / Cloudflare R2) and serves via high-speed edge CDN. Best for images, audio, video, and PDFs to avoid bundle bloat.
- **Site Code (`code`)**: Stores the media asset directly inside a site's code repository (e.g. in `images/` or `assets/`).

#### Upload via CLI:
```bash
# Upload to Global Cloud CDN:
./scripts/drophtml media upload ./banner.png

# Upload to a specific site's code repository:
./scripts/drophtml media upload ./chart.svg --site <site_id> --driver code --subdir images
```

Output includes ready-to-use markdown and HTML tags:
```text
✔ Media uploaded successfully!
CDN URL:      https://assets.drophtml.de/sites/site_123/media/banner-a8f3.png
Markdown:     ![banner.png](https://assets.drophtml.de/sites/site_123/media/banner-a8f3.png)
HTML Tag:     <img src="https://assets.drophtml.de/sites/site_123/media/banner-a8f3.png" alt="banner.png" />
```

#### Upload via API (Multipart or Base64):
Agents that generate images (e.g. diagrams or AI assets) can upload via base64 data:
```bash
curl -X POST https://drophtml.de/api/media \
  -H "Authorization: Bearer $DROPHTML_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "original_name": "ai-diagram.png",
    "data": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==",
    "storage_driver": "asset_storage"
  }'
```

---

### 4. Site Management & Custom Domains

#### List Sites
```bash
./scripts/drophtml sites list
# For JSON output:
./scripts/drophtml sites list --json
```

#### Get Site Details
```bash
./scripts/drophtml site get <site_id_or_slug>
```

#### Download Full Site Backup
Downloads all site code and optionally includes cloud media assets:
```bash
./scripts/drophtml site download <site_id_or_slug> --output backup.zip
```

#### Delete a Site
```bash
./scripts/drophtml site delete <site_id_or_slug>
```

#### Add Custom Domain
```bash
curl -X POST https://drophtml.de/api/sites/<site_id>/domains \
  -H "Authorization: Bearer $DROPHTML_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"domain": "app.example.com"}'
```

---

## Security Boundaries & Best Practices

1. **Dangerous Extensions Prohibited**: Server-side executable scripts (`.php`, `.sh`, `.exe`, `.py`, `.cgi`, `.bat`, etc.) and sensitive configuration files (`.env`, `.htaccess`, `.git`) are strictly rejected.
2. **File Size Limits**:
   - Single site upload: Up to 50 MB.
   - Single media upload: Up to 100 MB.
3. **Retention & Expiration**:
   - Guest / temporary sites expire after 24 hours to 30 days unless `--permanent` is set.
   - For persistent demos or live projects, always include `--permanent` or `"is_permanent": true`.
4. **Password Protection**:
   - Protect client previews, work-in-progress sites, or sensitive documents with `--password <password>`.

---

## Response Guidelines for AI Agents

When reporting a deployment or media upload back to the user:
1. **Always provide a clickable link** to the live site: `[Site Title](https://drophtml.de/s/your-slug)`.
2. If password-protected, clearly provide the password to the user.
3. For media uploads, provide the direct CDN URL along with the ready-to-copy Markdown or HTML snippet.
4. Mention if the site is permanent or temporary.
