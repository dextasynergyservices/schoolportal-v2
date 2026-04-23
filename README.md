# DX-SchoolPortal

A multi-tenant school management platform built with Laravel 13, Livewire 4, and Flux UI. Schools access the portal on their own custom domain. Features AI-powered quiz and game generation, role-based dashboards, Cloudinary file management, and a complete teacher approval workflow.

**Live:** [https://dexta.website](https://dexta.website)

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running Locally](#running-locally)
- [Testing](#testing)
- [Code Quality](#code-quality)
- [Deployment](#deployment)
- [Project Structure](#project-structure)
- [Multi-Tenancy](#multi-tenancy)
- [AI Features](#ai-features)
- [Roles & Permissions](#roles--permissions)
- [License](#license)

---

## Features

### Multi-Tenancy
- Single database, shared schema with `school_id` scoping on every table
- Custom domain routing — each school uses their own domain (e.g., `dexta.website/portal`)
- Per-school branding (colors, logo, motto) via CSS custom properties
- Configurable portal settings per school (session timeout, upload limits, feature toggles)

### School Admin Dashboard
- Student management — CRUD, CSV bulk import, password reset, activate/deactivate
- Teacher management — CRUD, assign to classes, approve/reject teacher submissions
- Parent management — CRUD, link parents to students (many-to-many)
- Academic structure — School levels, classes, sessions (with auto-created terms)
- Result management — Upload PDFs to Cloudinary, bulk upload by filename matching
- Assignment management — Weekly per class/term
- Notice management — Targeted by level and/or role, with images
- Teacher approval queue — Approve or reject results, assignments, notices, quizzes, games
- Student promotions — Bulk promote entire classes across sessions
- AI credit management — Purchase via Paystack, allocate to levels, track usage
- Quiz & game oversight — Publish/unpublish, view results, export CSV
- Audit logs — Complete history of all actions
- Settings — Branding, portal configuration, notification preferences

### Teacher Portal
- Dashboard with assigned classes, recent submissions, approval status
- Upload results for students (pending admin approval)
- Create assignments (pending admin approval)
- Post notices (pending admin approval)
- Create quizzes — AI-generated (from document or prompt) or manual
- Create educational games — AI-generated or manual (Memory Match, Word Scramble, Quiz Race, Flashcard)
- View quiz results and game stats per class
- Export quiz results to CSV
- Track submission approval status

### Student Portal
- Dashboard with upcoming quizzes, games, assignments
- View approved results per session/term (PDF viewer with signed Cloudinary URLs)
- View class assignments
- Take quizzes — timed, with navigation dots, auto-submit on expiry
- View quiz results with score, pass/fail, and answer explanations
- Play educational games — all 4 types with leaderboards
- View school notices

### Parent Portal
- Dashboard showing all linked children with class/teacher info
- View each child's results per session/term
- View each child's assignments
- View each child's quiz scores and game progress
- View school notices

### Security
- bcrypt password hashing (via Laravel Fortify)
- Two-factor authentication (2FA)
- Email verification
- Google reCAPTCHA v3 on all auth forms
- CSRF protection on every form
- XSS prevention via Blade escaping
- SQL injection prevention via Eloquent ORM
- Security headers (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
- Session timeout (configurable per school)
- Login rate limiting (5 attempts/minute)
- Force password change on first login
- Signed, expiring URLs for sensitive files (result PDFs)

---

## Tech Stack

| Component          | Technology                    |
|--------------------|-------------------------------|
| Framework          | Laravel 13                    |
| PHP                | 8.4                           |
| Frontend           | Tailwind CSS 4 + Alpine.js    |
| UI Components      | Flux UI (Livewire)            |
| Interactivity      | Livewire 4                    |
| Database           | MySQL 8+                      |
| File Storage       | Cloudinary                    |
| AI                 | Google Gemini API (2.5 Flash) |
| Auth               | Laravel Fortify               |
| Payments           | Paystack                      |
| Email              | Resend                        |
| Error Monitoring   | Sentry                        |
| Bundler            | Vite 8                        |
| Package Manager    | Bun                           |
| Linting (PHP)      | Laravel Pint (PSR-12)         |
| Linting (JS/CSS)   | Biome                         |
| Git Hooks          | Husky + lint-staged           |
| Commit Linting     | commitlint (conventional)     |

---

## Requirements

- PHP 8.3+ (8.4 recommended)
- MySQL 8.0+
- Composer 2.x
- Bun (latest) — for frontend builds
- Cloudinary account
- Google Gemini API key (for AI features)
- Paystack account (for credit purchases)
- Resend account (for transactional emails)

---

## Installation

```bash
# Clone the repository
git clone https://github.com/dextasynergyservices/schoolportal-v2.git
cd schoolportal-v2

# Install PHP dependencies
composer install

# Install frontend dependencies
bun install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations and seed demo data
php artisan migrate --force
php artisan db:seed

# Build frontend assets
bun run build
```

---

## Configuration

Copy `.env.example` to `.env` and configure these variables:

### Required

```env
APP_URL=http://localhost

DB_DATABASE=schoolportal
DB_USERNAME=root
DB_PASSWORD=

CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
```

### Optional (for full functionality)

```env
# AI quiz/game generation
GEMINI_API_KEY=

# Payments (AI credit purchases)
PAYSTACK_SECRET_KEY=
PAYSTACK_PUBLIC_KEY=

# Email
MAIL_MAILER=resend
RESEND_API_KEY=

# reCAPTCHA v3
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
RECAPTCHA_THRESHOLD=0.5

# Error monitoring
SENTRY_LARAVEL_DSN=
```

---

## Running Locally

```bash
# Start all services (PHP server + queue worker + Vite dev server)
composer run dev

# Or start individually:
php artisan serve          # Laravel at http://localhost:8000
php artisan queue:listen   # Background jobs
bun run dev                # Vite HMR
```

### Demo Accounts

After seeding, log in at `http://localhost:8000/portal/login`:

| Role         | Username       | Password   |
|--------------|----------------|------------|
| School Admin | admin          | password   |
| Teacher      | teacher1       | password   |
| Student      | student1       | password   |
| Parent       | parent1        | password   |
| Super Admin  | superadmin     | password   |

---

## Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test --filter=TenantIsolationTest

# Run specific test method
php artisan test --filter=test_school_a_cannot_see_school_b_students
```

### Test Coverage

- **Authentication** — Login/logout, email verification, password reset, 2FA
- **Role access** — Each role can only access their routes
- **Tenant isolation** — School A cannot see School B data (critical)
- **Force password change** — Redirects on first login
- **Security headers** — CSP, X-Frame-Options, etc.
- **Admin CRUD** — Student create/edit/delete/import
- **Profile & settings** — Profile updates, security settings

---

## Code Quality

### Linting & Formatting

```bash
# PHP — Laravel Pint (PSR-12 + Laravel conventions)
bun run pint              # Auto-fix
bun run pint:check        # Check only

# JS/CSS/JSON — Biome
bun run lint              # Lint
bun run lint:fix          # Auto-fix
bun run format            # Format
bun run format:fix        # Auto-fix
bun run ci                # CI mode (fails on errors)
```

### Git Hooks (Husky + lint-staged)

On every commit:
- PHP files → auto-formatted with Pint
- JS/CSS/JSON files → linted and formatted with Biome

Commit messages must follow [Conventional Commits](https://www.conventionalcommits.org/):
```
feat: add student CSV import
fix: prevent XSS in notice display
chore: update dependencies
```

---

## Deployment

### Namecheap Shared Hosting

The app is deployed on Namecheap Stellar Plus shared hosting.

**Directory structure on server:**

```
/home/username/
├── schoolportal/        # Laravel app (above web root)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   └── .env
└── dexta.website/       # Public web root
    ├── index.php        # Points to ../schoolportal/public/index.php
    ├── build/           # Vite compiled assets
    └── .htaccess
```

**Deploy steps:**

```bash
# 1. Build assets locally
bun run build

# 2. Upload changed files to ~/schoolportal/
# 3. Upload public/build/ to ~/dexta.website/build/

# 4. SSH in and run:
cd ~/schoolportal
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
chmod -R 775 storage bootstrap/cache
```

Or use the deploy script:

```bash
bash deploy.sh          # Standard deploy
bash deploy.sh --fresh  # First-time deploy (runs seeders)
```

> **Note:** Do not use `php artisan config:cache` on this shared hosting — it produces NULL values. The app runs without config cache.

### Custom Domain Setup (Per School)

Each school can have a branded subdomain (e.g., `portal.pearschool.com`) that goes directly to the login page. The platform landing page (`dexta.website`) is **never shown** on school custom domains — visitors are automatically redirected to `/portal/login`.

**How it works:**
- The `/` route checks the request's Host header against the platform domain (`APP_URL`)
- If the domain is NOT the platform domain → redirect to `/portal/login`
- If the domain IS the platform domain → show the DX-SchoolPortal landing page
- The `ResolveTenant` middleware resolves the school from the `custom_domain` column in the schools table

**Recommended approach:** Use `portal.pearschool.com` (not the bare domain) so the school can keep their own website at `pearschool.com`.

| URL | What the user sees |
|-----|-------------------|
| `dexta.website` | DX-SchoolPortal landing page |
| `portal.pearschool.com` | Redirects to `portal.pearschool.com/portal/login` |
| `portal.pearschool.com/portal/login` | Login page (school resolved by middleware) |
| `portal.pearschool.com/portal/admin/dashboard` | Admin dashboard (after login) |

#### Scenario A: Domain on the Same Namecheap Account (Same Host)

No DNS changes needed — cPanel handles it internally.

```
1. cPanel → Domains (or Subdomains)
2. Create subdomain: portal.pearschool.com
3. Set Document Root to: /home/dextdqei/dexta.website
   (same folder as the platform — this is critical)
4. Wait ~15 minutes for AutoSSL to provision HTTPS
5. In the portal (super admin), set the school's custom_domain = 'portal.pearschool.com'
6. Done — portal.pearschool.com goes directly to the login page
```

**If cPanel won't let you set the same document root:**

```bash
# SSH into the server
# Remove the empty folder cPanel created and replace with a symlink
rm -rf ~/portal.pearschool.com
ln -s ~/dexta.website ~/portal.pearschool.com
```

#### Scenario B: Domain NOT on the Namecheap Host (External Domain)

The school or their registrar must add a DNS record pointing to your server.

```
1. School adds DNS record at their registrar:
   - A record:     portal → YOUR_SERVER_IP
   - OR CNAME:     portal → dexta.website
2. Wait for DNS propagation (5 min to 48 hours)
3. In cPanel → Domains → Add domain: portal.pearschool.com
4. Set Document Root to: /home/dextdqei/dexta.website
   (or use symlink if cPanel doesn't allow it)
5. Wait for AutoSSL to provision HTTPS (~15 min after DNS resolves)
6. In the portal (super admin), set the school's custom_domain = 'portal.pearschool.com'
7. Done — portal.pearschool.com goes directly to the login page
```

#### Quick Checklist for Onboarding a New School Domain

- [ ] Subdomain created in cPanel (document root → ~/dexta.website)
- [ ] DNS resolves correctly (`dig portal.pearschool.com` or `nslookup`)
- [ ] AutoSSL certificate provisioned (check cPanel → SSL/TLS Status)
- [ ] `custom_domain` set in school record via super admin panel
- [ ] Test: `portal.pearschool.com` redirects to `/portal/login`
- [ ] Test: Login works and resolves to the correct school
- [ ] Test: All portal routes work (dashboard, students, etc.)

#### Important Notes

- After adding a new domain, run: `php artisan route:clear && php artisan view:clear && php artisan config:clear`
- The `custom_domain` column must match **exactly** what the user types in the browser (e.g., `portal.pearschool.com`, not `https://portal.pearschool.com`)
- Schools that don't have a custom domain simply use the platform URL: `dexta.website/portal/login`

---

## Project Structure

```
schoolportal-v2/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # 20 controllers (full school management)
│   │   │   ├── Teacher/         # 8 controllers (class-scoped actions)
│   │   │   ├── Student/         # 6 controllers (own data + quizzes/games)
│   │   │   ├── Parent/          # 7 controllers (children's data)
│   │   │   ├── SuperAdmin/      # 6 controllers (platform management)
│   │   │   └── Auth/            # Password change
│   │   ├── Middleware/          # Tenant resolution, roles, security
│   │   └── Requests/           # Form request validation classes
│   ├── Models/                  # 23 Eloquent models
│   ├── Services/                # Business logic (AI, credits, uploads, CSV)
│   ├── Observers/               # Audit logging
│   ├── Traits/                  # BelongsToTenant, Auditable
│   └── Rules/                   # Custom validation (Recaptcha)
├── database/
│   ├── migrations/              # 12 migration files
│   └── seeders/                 # Demo data + super admin
├── resources/views/
│   ├── admin/                   # Admin dashboard + 15 sections
│   ├── teacher/                 # Teacher dashboard + 8 sections
│   ├── student/                 # Student dashboard + 6 sections
│   ├── parent/                  # Parent dashboard + 5 sections
│   ├── super-admin/             # Platform management
│   ├── components/              # Reusable Blade components
│   └── layouts/                 # App, guest, auth layouts
├── routes/
│   ├── web.php                  # All application routes
│   └── settings.php             # Fortify settings routes
├── tests/
│   ├── Feature/                 # 14 feature test files
│   └── Unit/                    # Unit tests
├── deploy.sh                    # Server deployment script
├── biome.json                   # Biome linter config
├── commitlint.config.js         # Conventional commits
└── vite.config.js               # Vite + Tailwind CSS plugin
```

---

## Multi-Tenancy

Every tenant-scoped table includes a `school_id` column. The `BelongsToTenant` trait on models:

1. Adds a global scope filtering all queries by the current school
2. Auto-sets `school_id` on new records
3. Works with the `ResolveTenant` middleware that resolves the school from the request's `Host` header

```
Request → ResolveTenant middleware → resolve school from domain
       → bind school to app container
       → BelongsToTenant global scope filters all queries
```

Schools access the portal on their own domain:
```
dexta.website/portal/login   → Dexta Schools portal
anotherschool.com/portal     → Another School portal
```

---

## AI Features

### Quiz Generation
Teachers create quizzes by uploading a document (PDF/DOCX) or typing a prompt. The Google Gemini API generates questions with options, correct answers, and explanations. Teachers review and edit before publishing.

### Game Generation
Same flow as quizzes, generating content for 4 game types:
- **Memory Match** — Flip cards to match term-definition pairs
- **Word Scramble** — Unscramble letters to form key terms
- **Quiz Race** — Rapid-fire timed questions with class leaderboard
- **Flashcard Study** — Swipeable study cards with mastery tracking

### Credit System
- 15 free credits per school per month (resets on the 1st)
- Purchase additional credits via Paystack (₦200/credit, multiples of 5)
- 1 credit = 1 AI generation (quiz or game)
- Manual creation is always free and unlimited
- Credits can be allocated to specific school levels

---

## Roles & Permissions

| Role         | Scope                                    |
|--------------|------------------------------------------|
| Super Admin  | Platform-wide — manage all schools       |
| School Admin | Full school control — manage everything  |
| Teacher      | Assigned classes only — submissions need approval |
| Student      | Own data only — take quizzes, play games |
| Parent       | Linked children only — view-only access  |

---

## License

Proprietary. All rights reserved.
