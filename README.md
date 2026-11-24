# ArtSphere — Project Documentation

## Architecture Overview

```
C:\xampp\htdocs\ArtSphere\
├── config/
│   └── database.php           # PDO connection (singleton pattern)
├── database/
│   ├── schema.sql             # Phase 1-2 base schema
│   ├── phase3_migration.sql   # Categories + listings tables + seed data
│   ├── phase5_migration.sql   # customization_requests + quotations
│   ├── phase6_migration.sql   # messages + notifications + project_events
│   ├── phase7_to_11_migration.sql  # design_versions, projects, reviews, rewards, indexes
│   └── admin_setup.sql        # One-time admin promotion (run manually)
├── includes/
│   ├── header.php             # HTML head, loads DB + auth
│   ├── navigation.php         # Role-specific nav (creator/customer/public)
│   ├── footer.php             # HTML foot, Bootstrap JS
│   └── auth.php               # Session, CSRF, require_login(), get_logged_in_user()
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── uploads/
│   └── designs/               # Creator-uploaded design files (auto-created)
└── [pages — see below]
```

## XAMPP Setup

1. Start Apache and MySQL in XAMPP Control Panel.
2. Open phpMyAdmin → create database `artsphere` (UTF8MB4).
3. Import SQL files **in order**:
   - `database/schema.sql`
   - `database/phase3_migration.sql`
   - `database/phase5_migration.sql`
   - `database/phase6_migration.sql`
   - `database/phase7_to_11_migration.sql`
4. Open `http://localhost/ArtSphere/` in your browser.

## Environment / Config

Edit `config/database.php` for local DB credentials:

```php
$db_host = 'localhost';
$db_name = 'artsphere';
$db_user = 'root';
$db_pass = '';
```

Never commit real passwords to version control.

## User Roles & Permissions

| Role | Can Do |
|------|--------|
| **customer** | Browse, search, request customization, view quotations, chat, approve designs, leave reviews |
| **creator** | All public pages + manage portfolio, view requests, create quotations, upload design versions, view analytics |
| **admin** | All of the above + admin.php (suspend/activate users, view platform stats) |

Admin accounts are **only** promotable via `database/admin_setup.sql` run manually in MySQL. No public admin registration path exists.

## Main Page List

| Page | Access | Purpose |
|------|--------|---------|
| `index.php` | Public | Landing page |
| `explore.php` | Public | Search, filter, browse listings (real DB) |
| `categories.php` | Public | Category directory (real DB) |
| `creators.php` | Public | Creator discovery + Hidden Gems |
| `profile.php?username=` | Public | Creator public profile |
| `listing.php?id=` | Public | Listing detail + Request CTA |
| `register.php` | Guest | Account creation (customer or creator) |
| `login.php` | Guest | Sign in |
| `logout.php` | Authenticated | Destroy session |
| `dashboard.php` | Authenticated | Role-specific dashboard |
| `edit_profile.php` | Creator | Edit bio/location/specialties |
| `manage_listings.php` | Creator | Manage portfolio (CRUD) |
| `add_listing.php` | Creator | Create new listing |
| `edit_listing.php?id=` | Creator (owner) | Edit own listing |
| `incoming_requests.php` | Creator | View all incoming requests |
| `create_quotation.php?request_id=` | Creator | Build and send a quotation |
| `design_workspace.php?request_id=` | Participants | Upload/approve design versions |
| `creator_projects.php` | Creator | Project tracker + status updates |
| `analytics.php` | Creator | Real analytics from DB |
| `manage_quotations.php` | Creator | View sent quotations |
| `request_customization.php?listing_id=` | Customer | Submit customization request |
| `my_requests.php` | Customer | View submitted requests |
| `my_quotations.php` | Customer | View received quotations |
| `customer_projects.php` | Customer | View active projects |
| `view_request.php?id=` | Participants | Request detail, accept/decline, quotation |
| `chat.php?request_id=` | Participants | Private messaging (request-scoped) |
| `messages.php` | Authenticated | All conversation threads |
| `leave_review.php?project_id=` | Customer (completed) | Leave verified review |
| `admin.php` | Admin only | User management + platform stats |

## Security Implementation

- **PDO prepared statements** on all queries
- **CSRF tokens** on every state-changing form (`$_SESSION['csrf_token']`)
- **Server-side ownership checks** — all edit/delete/approve actions verify `AND creator_id = ?` or equivalent
- **Role authorization** — protected pages call `require_login()` and check `$user['role']`
- **File uploads** validated via `finfo` MIME detection, size limit (10 MB), randomized filenames, no executable extensions
- **HTML escaping** via `htmlspecialchars()` on all user-generated content
- **Session regeneration** on login (in `auth.php`)
- **No card numbers or payment credentials stored** — payment gateway integration point is prepared but not implemented

## Upload Directory

`uploads/designs/` — created automatically when first design is uploaded.

Add to `.htaccess` if needed:
```
Options -Indexes
<FilesMatch "\.(php|phtml|php3|php4|php5|php7|php8|phar)$">
    Deny from all
</FilesMatch>
```

## Known Limitations / Future Work

- **Real-time chat**: Messages refresh on page reload only. Socket.io or SSE could be added later.
- **Payment gateway**: Integration point prepared. Connect Stripe/PayPal server-side; never trust client-submitted payment status.
- **Email notifications**: In-app notifications are implemented; email requires SMTP config (e.g., PHPMailer).
- **Image uploads for listings**: Currently uses Picsum seeds. Replace `image_url` column with actual file paths and add upload validation.
- **Multi-creator collaboration**: Schema and UI placeholder prepared; full workflow pending.
- **Rewards redemption**: Points are tracked but no redemption flow exists yet.

## Test Credentials (Demo)

After running `phase3_migration.sql`:
- Creator: `samplecreator` / password: `password`
- Creator: `janedesigner` / password: `password`

Register your own accounts at `http://localhost/ArtSphere/register.php`.
