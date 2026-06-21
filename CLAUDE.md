# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Gradifier** is a banana sorting and grading management system built as a Progressive Web App (PWA). It tracks banana classifications from farms, analyzes quality metrics, and provides grade-based analytics through a dashboard. The system integrates with a banana sorting machine that classifies batches into grades (25BCP, 30BCP, 33BCP, 30TR, IF36TR, IF38TR).

## Tech Stack

- **Backend**: PHP 8.1 + MySQL 8.0 via MySQLi
- **Frontend**: Vanilla JS + jQuery 3.5.1 + Chart.js
- **Styling**: Tailwind CSS v3 (compiled via CLI), custom fonts: Montserrat, Poppins
- **PWA**: Service Worker (`sw.js`) + `manifest.json`
- **PDF export**: jsPDF + jsPDF AutoTable
- **Auth**: PHP sessions with 1-hour inactivity timeout

## Development Commands

```bash
npm install          # Install Tailwind CSS
npm run build-css    # Compile Tailwind CSS (watch mode)
```

To run the app, XAMPP must be running (Apache + MySQL). Access at:
`http://localhost/Grade/templates/index.php`

## Database Setup

1. Create a `grade` database in MySQL
2. Import `grade.sql` then `form.sql`
3. DB credentials are in `templates/config.php`:
   - Host: `localhost`, User: `root`, DB: `grade`

## Architecture

### Request Flow

All pages are PHP files under `templates/`. Protected pages include `auth_check.php` at the top to enforce login and session timeout. The login flow: `index.php` → `login.php` → `login_backend.php` → `dashboard.php`.

### Key Files

| File | Purpose |
|------|---------|
| `php/auth.php` | `requireLogin()` and `isLoggedIn()` helpers |
| `php/config.php` | DB connection (`$conn`), `BASE_URL` constant |
| `templates/config.php` | Alternate DB config used by most templates |
| `templates/auth_check.php` | Session guard — included at top of protected pages |
| `templates/DashBackend.php` | JSON API endpoint consumed by `javascript/chart.js` |
| `sw.js` | Service Worker — caches static assets for offline use |

### Primary Database Table: `finger_classes`

Stores banana batch records: `weight`, `classes_name`, `size`, `farm`, `classes` (enum of grade codes), `confidence`, bounding box coordinates, `timestamp`.

### Frontend JS (`javascript/`)

- `chart.js` — fetches from `DashBackend.php`, renders Chart.js pie chart
- `dropdown.js` — farm/date filter controls
- `table.js` + `pagination.js` — data table with client-side pagination
- `load_content.js` — dynamic section loading

### CSS

Edit `src/input.css` and run `npm run build-css` — output goes to `src/styles.css`. Do not edit `styles.css` directly.

### PWA

`php/pwa_head.php` is included in page `<head>` sections to register the service worker and link the manifest. Icons live in `icons/`.

## Authentication Notes

- Session key: `$_SESSION['userid']`
- Timeout: 1 hour of inactivity (enforced in `auth_check.php`)
- `index.php` redirects logged-in users directly to `dashboard.php` — users cannot return to index without logging out
- CSRF token is generated on the login form
