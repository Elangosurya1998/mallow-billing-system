# Postman Setup & API Testing Guide

This guide provides step-by-step instructions for importing, configuring, and executing the **Mallow Billing System Postman Workspace** across both **Native Local PHP (`php artisan serve` / without DDEV)** and **Containerized DDEV** setups.

---

## 1. Workspace Artifacts Overview

The Postman files are stored in the [`postman/`](../postman/) directory:

| File | Type | Target Environment | Description |
| :--- | :--- | :--- | :--- |
| [`postman/Mallow_Billing_System.postman_collection.json`](../postman/Mallow_Billing_System.postman_collection.json) | Collection (v2.1.0) | Universal | 21 structured REST requests across 7 feature modules with automated test assertions and pre-request dynamic generators. |
| [`postman/Mallow_Billing_System_Local.postman_environment.json`](../postman/Mallow_Billing_System_Local.postman_environment.json) | Environment | **Without DDEV** (Native PHP / `artisan serve` / Herd / Valet) | `base_url`: `http://127.0.0.1:8000`. Plain HTTP without SSL certificates. |
| [`postman/Mallow_Billing_System.postman_environment.json`](../postman/Mallow_Billing_System.postman_environment.json) | Environment | **With DDEV** (Docker / Traefik SSL) | `base_url`: `https://mallow-billing-system.ddev.site`. Uses local DDEV HTTPS. |

---

## 2. Choosing Your Setup: Without DDEV vs With DDEV

### Option A: Standard Local Setup (Without DDEV)

Use this option if you run Laravel natively using PHP 8.4 on your host machine via `php artisan serve`, Laravel Herd, or Laravel Valet.

#### 1. Start the Local Server
```bash
# 1. Ensure dependencies and environment are ready
cp .env.example .env
php artisan key:generate

# 2. Run migrations and seed the wireframe dataset (SQLite, MySQL, or PostgreSQL)
php artisan migrate:fresh --seed

# 3. Start the Laravel development server
php artisan serve
```
The application will listen at:
```
http://127.0.0.1:8000
```

#### 2. Select Postman Environment
- Import `postman/Mallow_Billing_System_Local.postman_environment.json`.
- In Postman's top-right environment selector, choose:
  **`Mallow Billing System - Local Artisan (127.0.0.1:8000)`**
- *Advantage*: Since `http://127.0.0.1:8000` is plain HTTP, **no SSL verification disabling is required**.

---

### Option B: Containerized Setup (With DDEV)

Use this option if you are running the project within Docker via DDEV.

#### 1. Start DDEV
```bash
# Start DDEV containers (Nginx, PHP 8.4, MariaDB 10.11)
ddev start

# Run migrations and seed wireframe mock data
ddev artisan migrate:fresh --seed
```
The application will be accessible at:
```
https://mallow-billing-system.ddev.site
```

#### 2. Select Postman Environment & Configure SSL
- Import `postman/Mallow_Billing_System.postman_environment.json`.
- In Postman's top-right environment selector, choose:
  **`Mallow Billing System - Local DDEV Environment`**
- Because DDEV uses Traefik self-signed certificates, disable SSL verification in Postman:
  1. Open Postman **Settings** (gear icon in the top right/left).
  2. In the **General** tab, turn **OFF** **SSL certificate verification**.

---

## 3. Step-by-Step Postman Import

### Step 1: Open Postman
Open the **Postman Desktop Application** or [Postman Web](https://web.postman.co/).

### Step 2: Import Files
1. Click the **Import** button in the upper left.
2. Drag and drop the collection and your desired environment file:
   - `postman/Mallow_Billing_System.postman_collection.json`
   - `postman/Mallow_Billing_System_Local.postman_environment.json` (for `http://127.0.0.1:8000`)
   - `postman/Mallow_Billing_System.postman_environment.json` (for `https://mallow-billing-system.ddev.site`)
3. Click **Import**.

```
Postman Workspace
├── Collections
│   └── 🚀 Mallow Billing System API
│       ├── 📁 1. Usage Metering & Ingestion
│       ├── 📁 2. Merchant Dashboard & Analytics
│       ├── 📁 3. Plans Catalog
│       ├── 📁 4. Subscriptions Lifecycle
│       ├── 📁 5. Invoices & Payments
│       ├── 📁 6. Tenants / Merchant Accounts
│       └── 📁 7. Customer Management
└── Environments
    ├── ⚙️ Mallow Billing System - Local Artisan (127.0.0.1:8000)  [WITHOUT DDEV]
    └── ⚙️ Mallow Billing System - Local DDEV Environment         [WITH DDEV]
```

### Step 3: Activate Your Environment
Select your active environment from the top-right dropdown:
- **Without DDEV**: `Mallow Billing System - Local Artisan (127.0.0.1:8000)`
- **With DDEV**: `Mallow Billing System - Local DDEV Environment`

---

## 4. Environment Variables Reference

Both environments come pre-populated with identical, deterministic demo values matching the seeded Acme Corp dataset:

| Variable Name | Default Value | Description |
| :--- | :--- | :--- |
| `base_url` | `http://127.0.0.1:8000` (Local)<br>`https://mallow-billing-system.ddev.site` (DDEV) | Application root URL. |
| `api_key` | `ak_test_acme_12345` | API key with merchant authorization. |
| `merchant_id` | `01a0e8d1-587b-70e7-96c9-c6f4a3911761` | Acme Corp UUID. |
| `customer_id` | `01a0e8d1-588c-7011-aa03-58164aeb23ce` | Beta Retail Pvt Ltd (top consumer). |
| `plan_id` | `01a0e8d1-587d-7056-8a07-dade9ff1cba0` | Growth Plan UUID (250,000 units quota). |
| `subscription_id`| `01a0e8d1-587f-71a2-a9c6-1dba0aa60f8c` | Active Subscription UUID. |
| `invoice_id` | `01a0e8d1-5903-7350-a1cf-75c8e6e55ced` | Seeded Closed Invoice UUID. |
| `tenant_id` | `01a0e8d1-587b-70e7-96c9-c6f4a3911761` | Multi-tenant isolation scope ID. |

> [!NOTE]
> If you re-run `migrate:fresh --seed` on a clean database and new UUIDs are generated, you can simply update `merchant_id` or `customer_id` in your Postman Environment or query `GET /api/v1/plans` to copy the new IDs.

---

## 5. Collection Folders & Automated Test Assertions

### Folder 1: Usage Metering & Ingestion
Tests high-throughput usage event ingestion, rate limiting (120 req/min), and exact-once idempotency semantics:

- **`Record Usage Event (Fresh Submission - HTTP 201)`**:
  - *Pre-request Script*: Automatically generates a dynamic key `evt_run_{{timestamp}}_{{random}}`.
  - *Assertions*:
    - Status is `201 Created`.
    - `idempotent_replay` is `false`.
    - Event persisted with valid UUID.
- **`Record Usage Event (Idempotent Replay - HTTP 200)`**:
  - Re-sends the exact same `(customer_id, idempotency_key)`.
  - *Assertions*:
    - Status is `200 OK`.
    - `idempotent_replay` is `true`.
    - Original event data returned without incrementing `daily_usage_summaries`.
- **`Get Aggregated Usage Summary`**:
  - `GET /api/v1/usage/summary?customer_id={{customer_id}}`
  - Validates pre-aggregated rollups from `daily_usage_summaries` ($O(1)$ complexity).
- **`List Raw Partitioned Usage Events`**:
  - `GET /api/v1/usage/events?customer_id={{customer_id}}`
  - Validates range-partitioned storage and chronological retrieval.

---

### Folder 2: Merchant Dashboard & Analytics
Tests operational metrics and presentation layers:

- **`Get Merchant Dashboard (JSON API)`**:
  - `GET /api/v1/merchants/{{merchant_id}}/dashboard`
  - *Assertions*:
    - `merchant_name === "Acme Corp"`.
    - `cycle_usage.total_usage_units === 184320`.
    - `projected_overage_revenue` is formatted in INR.
    - Top 5 ranked customers list includes `Beta Retail Pvt Ltd`, `Craft Foods Co.`.
    - Churn alert list identifies `Nova Traders` (>50% MoM drop).
- **`View Merchant Dashboard (HTML Web View)`**:
  - `GET /merchants/{{merchant_id}}/dashboard`
  - *Assertions*:
    - Status is `200 OK` HTML.
    - Contains `"WIREFRAME — layout reference only"` header badge.
    - Contains Chart.js 30-day spline chart canvas element.

---

### Folder 3: Plans Catalog
Tests multi-tier pricing structures, base fees, and included quotas:

- **`List All Plans`**: `GET /api/v1/plans`
- **`Create New Tiered Plan`**: `POST /api/v1/plans`
- **`Get Plan Details`**: `GET /api/v1/plans/{{plan_id}}` (Cached for 10 min via Redis / Cache driver).

---

### Folder 4: Subscriptions Lifecycle
Tests subscription state transitions and mid-cycle proration:

- **`Create Subscription`**: `POST /api/v1/subscriptions`
- **`Switch Plan Mid-Cycle`**: `PATCH /api/v1/subscriptions/{{subscription_id}}/switch-plan`
  - Submits mid-cycle transition payload dividing the billing cycle into two independent segments ($T_1$ and $T_2$).
  - Asserts customer credit balance adjustment on downgrade or prorated invoice item on upgrade.
- **`Cancel Subscription`**: `POST /api/v1/subscriptions/{{subscription_id}}/cancel`
- **`Resume Subscription`**: `POST /api/v1/subscriptions/{{subscription_id}}/resume`

---

### Folder 5: Invoices & Payments
Tests automated period-end invoice generation:

- **`List Invoices`**: `GET /api/v1/invoices?customer_id={{customer_id}}`
- **`Get Invoice Breakdown`**: `GET /api/v1/invoices/{{invoice_id}}`
  - Asserts discrete line items for base plan subscription fee and overage consumption.
- **`Pay Invoice`**: `POST /api/v1/invoices/{{invoice_id}}/pay`
  - Transitions invoice status from `issued` to `paid`.

---

### Folder 6: Tenants / Merchant Accounts
Tests multi-tenant isolation, merchant provisioning, and updating:

- **`Create Tenant / Merchant`**: `POST /api/v1/tenants` (or `POST /api/v1/merchants`)
- **`Get Tenant Details`**: `GET /api/v1/tenants/{{tenant_id}}`
- **`Update Merchant Profile`**: `PUT /api/v1/merchants/{{tenant_id}}` (Updates operational currency, email, timezone, and active status)

---

### Folder 7: Customer Management
Tests customer lifecycle under strict tenant isolation:

- **`List Customers`**: `GET /api/v1/customers` (Scoped to tenant via `X-Tenant-ID` header)
- **`Create Customer`**: `POST /api/v1/customers` (Supports initial credit balance and immediate plan subscription enrollment)
- **`Get Customer Details`**: `GET /api/v1/customers/{{customer_id}}`

---

## 6. Running Headless via CLI (Newman)

You can run automated test executions from your terminal or CI/CD pipelines using **Newman**:

### Running Against Native PHP (Without DDEV - Port 8000)
```bash
# Execute against php artisan serve (no SSL flags needed)
npx newman run postman/Mallow_Billing_System.postman_collection.json \
  -e postman/Mallow_Billing_System_Local.postman_environment.json
```

### Running Against DDEV (With DDEV - Port 443)
```bash
# Execute against DDEV container (with --insecure for local self-signed SSL)
npx newman run postman/Mallow_Billing_System.postman_collection.json \
  -e postman/Mallow_Billing_System.postman_environment.json \
  --insecure
```

---

## 7. Troubleshooting & FAQs

### Q: Which setup should I use if I don't use Docker or DDEV?
**A**: Use **Option A (Without DDEV)**:
1. Run `php artisan serve` in your terminal.
2. Select the `Mallow Billing System - Local Artisan (127.0.0.1:8000)` environment in Postman.
3. Everything works out of the box with standard HTTP (no certificate configuration needed).

### Q: Why do I get `SSL Error: self signed certificate` on DDEV?
**A**: DDEV provisions local HTTPS using self-signed development certificates. In Postman, open **Settings > General** and toggle **SSL certificate verification** to **OFF**, or switch to the Local Artisan environment (`http://127.0.0.1:8000`).

### Q: Why does `POST /api/v1/usage` return `HTTP 429 Too Many Requests`?
**A**: The API enforces a strict rate limit of **120 requests per minute per API key** configured in `bootstrap/app.php`. If you exceed this threshold during automated testing, wait 60 seconds or adjust the throttle limit.

### Q: How do I re-seed or test with clean data?
**A**:
- **Without DDEV**: `php artisan migrate:fresh --seed`
- **With DDEV**: `ddev artisan migrate:fresh --seed`
