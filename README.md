# RetailPay: KK Wholesalers Inventory System

Inventory movement system built with **Bare Metal Laravel 11**, no external dependencies. It addresses stock inconsistencies, race conditions, and audit challenges through a ledger-based approach.

## Implementation Overview

The project was implemented in the phases outlined below: 

### 1. Database Implementation 
- **Migrations**: Created clean, timestamped migrations for `branches`, `stores`, `products`, `stocks`, and `stock_movements`.
- **Eloquent Models**: Implemented Eloquent models with full relationship definitions and performance-optimized eager loading.
- **Seeding**: Populated the system with KK Wholesalers' actual structure (2 Branches, 3 Stores) and initial stock.

### 2. Core Inventory Service (The Engine)
- **`InventoryService`**: A centralized service class that handles all stock logic.
- **Double-Entry Ledger**: Every SKU change is recorded in the `stock_movements` table, providing an immutable audit trail.
- **Concurrency Control**: 
    - Used `DB::transaction()` for atomicity.
    - Implemented `lockForUpdate()` on stock rows to prevent race conditions during high-volume sales.

### 3. Permissions & Authorization (The Guards)
- **Role-Based Access Control (RBAC)**: Defined three core roles: `Administrator`, `Branch Manager`, and `Store Manager`.
- **Laravel Policies**: 
    - `StockPolicy` and `StorePolicy` enforce strict data boundaries.
    - Managers can only view or move stock within their assigned scope (Branch or Store).
    - Administrators have a "Master Key" via the `before()` interceptor.

### 4. User Interface (The Dashboard)
- **Blade & Tailwind**: A clean, responsive UI built using native Laravel Breeze components.
- **Context-Aware Dashboard**: 
    - Automatically filters stock levels and movement history based on the logged-in user's role.
    - Summary cards provide real-time stats (Total SKUs, Network Stock).
- **Movement Forms**: Simple forms to record Sales, Internal Transfers, Procurements, and Adjustments.
- **Live Audit Feed**: A "Security Camera" view of recent movements displayed directly on the dashboard.

### 5. Testing
- **Feature Tests**: 
    - `InventoryTest`: Verifies the core logic of stock changes.
    - `AuthorizationTest`: Ensures roles cannot bypass their permissions.
- **`ConcurrencyIntegrityTest`**: A custom stress test that runs 50+ sequential operations to verify that the Snapshot (`stocks`) and History (`stock_movements`) stay in perfect sync.


## Getting Started

1. **Install Dependencies**: `composer install && npm install`
2. **Setup Environment**: `cp .env.example .env` and configure your database.
3. **Migrate & Seed**: `php artisan migrate:fresh --seed`
4. **Run Dev Server**: `php artisan serve` & `npm run dev`
5. **Run Tests**: `php artisan test`
6. **Login Credentials**: Check under `database/seeders/DatabaseSeeder.php` for seeded users.
