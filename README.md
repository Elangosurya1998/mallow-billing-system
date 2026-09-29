# High-Scale Multi-Tenant Subscription Billing & Usage-Metering System

[![PHPUnit](https://img.shields.io/badge/PHPUnit-12.5--Passing-brightgreen.svg)]()
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)]()
[![Laravel](https://img.shields.io/badge/Laravel-13.x-red.svg)]()
[![Code Style](https://img.shields.io/badge/Pint-PSR--12%20Compliant-orange.svg)]()

A high-scale, production-ready **Multi-Tenant Subscription Billing & Usage-Metering System** engineered in **Laravel 13** and **PHP 8.4+**.

Architected from first principles to ingest, meter, and bill **50L+ (5,000,000+) raw event records** per cycle, execute second-accurate mathematical proration, calculate graduated usage allowances, and process automated recurring billing cycles with zero financial drift.

---

## Architecture Summary & Scaling Strategy

Complete architectural derivations, schema designs, and formal mathematical proofs are documented in the [`/docs`](docs/) directory:

- 📐 **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)**: Deep dive into 50L+ row scaling, MySQL/MariaDB physical range partitioning on `usage_events.timestamp`, index pruning, and the two-tier roll-up aggregation architecture reducing monthly invoice scan complexity from $O(5,000,000)$ to $O(30)$.
- 🧮 **[docs/PRORATION_MATH.md](docs/PRORATION_MATH.md)**: Formal mathematical equations for mid-cycle signups and segmented plan transitions with worked numerical proofs demonstrating conservation of financial commitment ($B_1 + C_{\text{unused}} = B_{\text{initial}}$).
- 🌐 **[docs/API_REFERENCE.md](docs/API_REFERENCE.md)**: Complete REST API specifications for `/api/v1/usage` (exact-once idempotency replay, rate limiting at 120 req/min) and `/api/v1/merchants/{id}/dashboard`.
- 📜 **[docs/CHANGELOG.md](docs/CHANGELOG.md)**: Milestone logs, architectural decisions, and release notes across all 5 implementation phases.
- 📮 **[docs/POSTMAN_SETUP.md](docs/POSTMAN_SETUP.md)**: Complete step-by-step Postman setup, variable references, automated collection test assertions, and Newman CLI runbook.
- 📁 **[prompts/](prompts/)**: Prompt histories, instructions, and review artifacts (see [Prompts Directory](#reviewing-the-prompts-directory)).

### High-Scale Data Flow

```mermaid
flowchart TD
    API["POST /api/v1/usage\n(Rate Limit: 120 req/min)"] --> IDEMP{"Idempotency Engine\n(customer_id, idempotency_key)"}
    IDEMP -->|Duplicate Key| REPLAY["Return HTTP 200\nidempotent_replay: true\n(No Double Count)"]
    IDEMP -->|Fresh Event| RAW["Partitioned Table\nusage_events\n(RANGE on timestamp)"]
    RAW --> ROLLUP["Atomic Increment\ndaily_usage_summaries\n(customer_id, metric, date)"]
    ROLLUP --> RESP["Return HTTP 201 Created"]
    
    CRON["Daily / End-of-Cycle Queues"] --> JOB1["AggregateDailyUsageJob\nchunkById(5000)"]
    JOB1 --> ROLLUP
    CRON --> JOB2["GenerateCycleInvoicesJob\nchunkById(1000)"]
    ROLLUP -->|O(30) Lookups\nBypasses 50L+ rows| JOB2
    JOB2 --> PRORATE["Evaluate Segments &\nProrated Allowances"]
    PRORATE --> CREDIT["Deduct Customer Credit Balance"]
    CREDIT --> INV["Persist Invoice & InvoiceItems\n(Integer Cents)"]
```

---

## Key Features

1. **Modern PHP 8.4 & Laravel 13**:
   - Asymmetric visibility (`public private(set)`) and property hooks across DTOs.
   - Streamlined routing and rate limiting via `bootstrap/app.php`.
   - Strict typing (`declare(strict_types=1);`) and constructor property promotion.

2. **50L+ Event Ingestion Pipeline**:
   - Range-partitioned `usage_events` table for horizontal write scalability and partition pruning.
   - Pre-aggregated `daily_usage_summaries` table eliminating full-table scans during invoice generation.
   - Guaranteed exact-once idempotency: duplicate payloads return `HTTP 200 OK` with `idempotent_replay: true` without incrementing counters.
   - API rate limiting of 120 requests/minute per API key.

3. **Redis Plan Caching & Mid-Cycle Transitions**:
   - `PlanPricingService` with 10-minute Redis caching and cache invalidation via Eloquent `PlanObserver`.
   - Mid-cycle plan upgrades and downgrades segment the billing cycle into independent intervals ($T_1$ and $T_2$).
   - Prorates base fees and included allowances for each segment independently.
   - Unused base fees on plan downgrades automatically credited to `customer.credit_balance_cents`.

4. **Chunked Queue Processing**:
   - `AggregateDailyUsageJob`: Processes raw meter events using `chunkById(5000)` for bounded $O(1)$ memory consumption.
   - `GenerateCycleInvoicesJob`: Executes end-of-cycle billing using `chunkById(1000)`, queries daily rollups, computes overage rates in integer cents, offsets credit balances, and provisions subsequent cycles.

5. **Merchant Executive Dashboard**:
   - `GET /api/v1/merchants/{id}/dashboard`: Real-time operational intelligence including cycle usage vs quota, projected overage revenue, top 5 consumers ranked by percentage consumed, and churn-risk accounts (>50% MoM drop).

---

## Local Setup & Installation Instructions

### Prerequisites
- **PHP**: `^8.4` (with `pdo_sqlite`, `pdo_mysql`, `bcmath`, `mbstring`)
- **Composer**: `^2.x`
- **Database**: SQLite (default local) or MySQL 8.0+ / MariaDB 10.5+ (for physical table partitioning)

### Step-by-Step Setup

```bash
# 1. Clone repository
git clone <repository-url>
cd mallow-billing-system

# 2. Install dependencies
composer install

# 3. Environment Configuration
cp .env.example .env
php artisan key:generate

# 4. Database Setup & Migrations
# For SQLite (default):
touch database/database.sqlite
php artisan migrate

# For MySQL / MariaDB (enables physical RANGE partitioning on usage_events):
# Set DB_CONNECTION=mysql in .env and run:
# php artisan migrate

# 5. Run Test Suite
php artisan test --compact

# 6. Format Code with Laravel Pint
vendor/bin/pint --format agent
```

---

## Running the Test Suite

The test suite verifies all 5 architectural phases with 100% pass rates:

```bash
# Run all feature tests
php artisan test

# Or run specific phase suites
php artisan test tests/Feature/Phase1DatabaseArchitectureTest.php
php artisan test tests/Feature/Phase2IngestionPipelineTest.php
php artisan test tests/Feature/Phase3PlanCachingAndSwitchingTest.php
php artisan test tests/Feature/Phase4QueueInvoicingTest.php
php artisan test tests/Feature/Phase5MerchantDashboardTest.php
php artisan test tests/Feature/Phase5BillingEngineTest.php
php artisan test tests/Feature/MerchantDashboardWebViewTest.php
php artisan test tests/Feature/ApiConsoleWebViewTest.php
```

### Verified Test Matrix (30 Tests, 381 Assertions)

| Test Suite | Focus Area | Assertions | Result |
| :--- | :--- | :---: | :---: |
| `Phase1DatabaseArchitectureTest` | Schema integrity, UUIDs, integer cents, relationships | 78 | PASS |
| `Phase2IngestionPipelineTest` | Validation, exact-once idempotency replay, rate limiting | 83 | PASS |
| `Phase3PlanCachingAndSwitchingTest`| Redis caching (10m TTL), cache flush observer, mid-cycle switches | 41 | PASS |
| `Phase4QueueInvoicingTest` | `chunkById(5000)` aggregation, cycle invoices, segmented cycles | 40 | PASS |
| `Phase5MerchantDashboardTest` | Quota tracking, projected overages, top 5 consumers, churn risk | 38 | PASS |
| `Phase5BillingEngineTest` | Idempotency replay, signup proration, switch math, overage edges | 61 | PASS |
| `MerchantDashboardWebViewTest` | Blade layout, wireframe badge, metric cards, 30-day chart canvas, merchants portal, quick switcher | 28 | PASS |
| `ApiConsoleWebViewTest` | API Web Console, interactive meter event submissions, merchant scope | 12 | PASS |
| **Total** | **Full System Verification** | **381** | **PASS** |

---

## Postman Workspace & API Testing

A complete Postman workspace export is included under the [`/postman`](postman/) directory for automated API testing, interactive exploration, and regression verification against the live environment.

### Workspace Files

1. **Collection**: [`postman/Mallow_Billing_System.postman_collection.json`](postman/Mallow_Billing_System.postman_collection.json) (Postman Collection Schema v2.1.0)
2. **Environment (Without DDEV - Native `artisan serve`)**: [`postman/Mallow_Billing_System_Local.postman_environment.json`](postman/Mallow_Billing_System_Local.postman_environment.json) (`http://127.0.0.1:8000`)
3. **Environment (With DDEV - Docker)**: [`postman/Mallow_Billing_System.postman_environment.json`](postman/Mallow_Billing_System.postman_environment.json) (`https://mallow-billing-system.ddev.site`)

### Quick Import Steps

1. Open **Postman** (Desktop or Web).
2. Click **Import** (top left) and drag-and-drop the collection and your target environment:
   - `Mallow_Billing_System.postman_collection.json`
   - `Mallow_Billing_System_Local.postman_environment.json` (if running native `php artisan serve` at `http://127.0.0.1:8000`)
   - OR `Mallow_Billing_System.postman_environment.json` (if running via DDEV at `https://mallow-billing-system.ddev.site`)
3. In the environment dropdown in the top-right corner, select your active environment:
   - **`Mallow Billing System - Local Artisan (127.0.0.1:8000)`** (no SSL settings required)
   - OR **`Mallow Billing System - Local DDEV Environment`** (disable SSL verification in Settings > General)
4. The environment is pre-configured with the live seeded database state:
   - `api_key`: `ak_test_acme_12345`
   - `merchant_id`: `01a0e8d1-587b-70e7-96c9-c6f4a3911761` (Acme Corp)
   - `customer_id`: `01a0e8d1-588c-7011-aa03-58164aeb23ce` (Beta Retail Pvt Ltd)
   - `plan_id`: `01a0e8d1-587d-7056-8a07-dade9ff1cba0` (Growth Plan)
   - `subscription_id`: `01a0e8d1-587f-71a2-a9c6-1dba0aa60f8c`
   - `invoice_id`: `01a0e8d1-5903-7350-a1cf-75c8e6e55ced`

### Included Request Folders & Automated Tests

| Folder | Endpoints Covered | Automated Tests & Assertions |
| :--- | :--- | :--- |
| **1. Usage Metering & Ingestion** | `POST /api/v1/usage`<br>`GET /api/v1/usage/summary`<br>`GET /api/v1/usage/events` | - Fresh submission asserts `HTTP 201 Created` & `idempotent_replay: false`.<br>- **Idempotency Replay**: Re-sends same key, asserts `HTTP 200 OK` & `idempotent_replay: true` without incrementing counters. |
| **2. Merchant Dashboard & Analytics** | `GET /api/v1/merchants/{id}/dashboard`<br>`GET /merchants/{id}/dashboard` | - Asserts JSON response schema, quota percentages, projected overage currency, top 5 ranked accounts, and churn drops.<br>- Asserts Web View returns `HTTP 200 OK` with wireframe layout and Chart.js integration. |
| **3. Plans Catalog** | `GET /api/v1/plans`<br>`POST /api/v1/plans`<br>`GET /api/v1/plans/{id}` | - Asserts plan retrieval, tier pricing structure, and integer-cent validation. |
| **4. Subscriptions Lifecycle** | `POST /api/v1/subscriptions`<br>`PATCH /api/v1/subscriptions/{id}/switch-plan`<br>`POST /api/v1/subscriptions/{id}/cancel` | - Asserts mid-cycle plan segmenting and credit rollover balance. |
| **5. Invoices & Payments** | `GET /api/v1/invoices`<br>`GET /api/v1/invoices/{id}`<br>`POST /api/v1/invoices/{id}/pay` | - Asserts invoice line item breakdown (base subscription vs overage). |
| **6. Tenants / Merchant Accounts** | `POST /api/v1/tenants`<br>`GET /api/v1/tenants/{id}` | - Asserts multi-tenant merchant onboarding and API key generation. |

For detailed setup directions, Newman CLI execution, and troubleshooting, refer to **[`docs/POSTMAN_SETUP.md`](docs/POSTMAN_SETUP.md)**.

---

## Architectural Trade-offs & Documented Assumptions

### 1. Test Runner: PHPUnit 12 vs Pest 3
- **Constraint**: Laravel 13 requires and ships with `phpunit/phpunit ^12.5.12`. Pest 3 (`pestphp/pest ^3.0`) currently requires `phpunit/phpunit ^11.5` and has unresolved upstream composer dependency conflicts with PHPUnit 12.
- **Decision**: In strict adherence to project guidelines (*"Do not change the application's dependencies without approval"*), all required test scenarios (idempotency replay, mid-cycle signup proration, mid-cycle segmented plan switching, and overage threshold edge cases) are implemented natively with PHPUnit 12 using Laravel's fluent testing APIs and assertions.

### 2. Pre-Aggregated Rollups vs Raw Event Scans
- **Trade-off**: Slightly higher write amplification on ingestion vs $O(1)$ read performance at billing time.
- **Rationale**: Scanning 50L+ (5,000,000+) raw rows at month-end to generate invoices would trigger database lockups, high memory pressure, and query timeouts. Maintaining `daily_usage_summaries` reduces monthly query lookups from 5,000,000 to $\approx 30$ rows per subscription, enabling sub-second invoice generation.

### 3. All Currencies in Integer Cents (`BIGINT`)
- **Decision**: All financial figures (`base_price_cents`, `overage_rate_cents`, `subtotal_cents`, `total_cents`, `credit_balance_cents`) are stored strictly as integer cents.
- **Rationale**: Eliminates binary floating-point roundoff errors (e.g., `0.1 + 0.2 !== 0.3` in IEEE-754). Formatting into decimal representations (e.g. `USD 25.00`) is deferred strictly to presentation layers and DTO accessors.

### 4. Database Partitioning Across SQLite & MySQL
- **Decision**: SQLite is supported out of the box for testing and lightweight local environments using standard composite indexes. In MySQL/MariaDB environments, migrations dynamically apply physical `PARTITION BY RANGE (UNIX_TIMESTAMP(timestamp))` across calendar years and quarters, enabling physical partition pruning and instant historical drops.

### 5. Credit Rollovers on Plan Downgrades
- **Decision**: Unused prorated base fees from mid-cycle plan downgrades are credited to `customer.credit_balance_cents` and automatically applied against future invoices, rather than triggering immediate payment gateway refunds.
- **Rationale**: Aligns with standard SaaS billing conventions (e.g., Stripe Billing, AWS) and protects merchants from merchant gateway refund transaction fees.

---

## Reviewing the `/prompts` Directory

The [`/prompts`](prompts/) directory contains full workflow traces, task instructions, and review artifacts:

- Browse [`prompts/`](prompts/) to review the engineering prompts and requirements provided for each phase.
- Review [`prompts/screenshots/`](prompts/screenshots/) for visual captures, system output logs, and trajectory evidence.

---

## License

This software is open-sourced under the [MIT license](https://opensource.org/licenses/MIT).
