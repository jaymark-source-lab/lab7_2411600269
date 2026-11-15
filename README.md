# FitTrack Pro - Lab 7: Automated Inventory Replenishment and Supplier Management

Laravel 12 inventory system for a gym store (supplements, vitamins and gym accessories).
Lab 7 adds suppliers, purchase orders with an approval workflow, automated reordering,
email notifications, a procurement dashboard, supplier performance reports, advanced
search and CSV/PDF exports.

**Student:** Jay Mark P. Abangan (2411600269)
**Course:** Web Systems and Technologies - BSIT, Ramon Magsaysay Memorial Colleges
**Instructor:** Jim Jamero

## Features

### Supplier management
- Supplier CRUD with validation (unique email, phone format, payment terms, lead time)
- **Soft deletes**: deleted suppliers are hidden from lists but old purchase orders still show them; admins can restore
- Many-to-many `product_supplier` pivot with unit cost, minimum order quantity and preferred supplier
- Query scopes: `active()`, `withLeadTime($days)`

### Purchase orders and approval workflow
- Status flow: **draft -> pending -> approved / rejected -> sent -> received** (or cancelled)
- Dynamic line items with prices from the supplier pivot and 12% VAT
- Partial deliveries: receiving creates `stock_in` inventory transactions and updates stock
- Approval history stored in a polymorphic `activity_logs` table

### Roles and authorization
| Role | Can do |
|------|--------|
| admin | everything, delete/restore suppliers, delete drafts |
| manager | create, approve/reject (not their own orders), send to supplier |
| staff | create and submit purchase orders |
| warehouse | receive deliveries |

- `PurchaseOrderPolicy` (status and role based, with separation of duties) and an `admin` Gate
- 9 PHPUnit tests in `tests/Feature/PurchaseOrderAuthorizationTest.php`

### Automated reordering
- `ReorderService`: reorder point = average daily usage (30 days) x supplier lead time + 3 days safety stock
- Order quantity covers 30 days of usage, rounded up to the supplier's minimum order quantity
- Skips products already on an open order and groups products by preferred supplier
- Artisan command `php artisan inventory:check-reorder --dry-run`
- Scheduled daily at 7:00 AM (Asia/Manila) in `routes/console.php`

### Email notifications (queued)
- `PurchaseOrderCreated` (to approvers), `PurchaseOrderApproved` (to creator),
  `PurchaseOrderSent` (to supplier, with the PO attached as a PDF), `LowStockAlert`
- Mail previews at `/mail-preview/{created|approved|sent|low-stock}` (local only)

### Procurement dashboard
- Widgets: pending orders, recent orders, supplier performance, reorder alerts
- Data supplied by **View Composers** (`ViewServiceProvider`), cached and cleared when an order changes
- JSON endpoints under `/api/dashboard/*` for live refresh

### Supplier performance report
- On-time delivery %, average delivery days vs quoted lead time, order accuracy %, total value
- Score = 60% on-time + 40% accuracy (Excellent / Good / Fair / Poor)
- Delivery trend chart (Chart.js), top performers and suppliers needing attention

### Search, filters and exports
- `SearchService` for products, purchase orders and transactions, built on model query scopes
- Reusable Blade filter components (`x-filters.bar`, `x-filters.search`, `x-filters.select`, `x-filters.date-range`)
- CSV (streamed) and PDF (dompdf) exports that follow the current filters
- Printable purchase order PDF

## Tech stack
Laravel 12, PHP 8.2, MySQL (XAMPP), Bootstrap 5, Chart.js, barryvdh/laravel-dompdf, database queue and cache.

## Setup

~~~bash
git clone https://github.com/jaymark-source-lab/lab7_2411600269.git
cd lab7_2411600269
composer install
cp .env.example .env
php artisan key:generate
~~~

Edit `.env` with your database settings, then set:

~~~
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log
~~~

~~~bash
php artisan migrate --seed
php artisan db:seed --class=SupplierSeeder
php artisan db:seed --class=RoleUserSeeder
php artisan serve
~~~

In separate terminals:

~~~bash
php artisan queue:work      # sends queued emails
php artisan schedule:work   # runs the daily reorder check
~~~

## Demo accounts (local development only)
| Role | Email |
|------|-------|
| admin | test@example.com |
| manager | manager@example.com |
| warehouse | warehouse@example.com |
| staff | staff@example.com |

Passwords are set by the seeders.

## Useful commands
~~~bash
php artisan inventory:check-reorder --dry-run   # preview reorder suggestions
php artisan schedule:list                       # show the scheduled reorder check
php artisan test --filter=PurchaseOrderAuthorizationTest
~~~

## Key files
- `app/Models/Supplier.php`, `PurchaseOrder.php`, `PurchaseOrderItem.php`, `ActivityLog.php`
- `app/Policies/PurchaseOrderPolicy.php`
- `app/Services/ReorderService.php`, `SupplierPerformanceService.php`, `ProcurementStats.php`, `SearchService.php`
- `app/Console/Commands/CheckReorderPoints.php` and `routes/console.php`
- `app/Mail/*` and `resources/views/emails/*`
- `app/Exports/*` and `resources/views/exports/*`
- `app/Providers/ViewServiceProvider.php` and `resources/views/widgets/*`
