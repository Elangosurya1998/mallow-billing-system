# Mallow Billing System — Postman Workspace

This directory contains the official Postman Collection and Environment exports for testing and verifying the Mallow Multi-Tenant Subscription Billing & Usage-Metering System.

---

## Files

1. **[`Mallow_Billing_System.postman_collection.json`](Mallow_Billing_System.postman_collection.json)**:
   - Postman Collection Schema v2.1.0.
   - 17 API requests across 6 feature folders (Usage Ingestion, Merchant Dashboard, Plans, Subscriptions, Invoicing, Tenants).
   - Embedded JavaScript assertions and pre-request scripts (including exact-once idempotency verification).

2. **[`Mallow_Billing_System_Local.postman_environment.json`](Mallow_Billing_System_Local.postman_environment.json)** (**Without DDEV**):
   - Configured for standard local development (`http://127.0.0.1:8000` via `php artisan serve`, Laravel Herd, or Valet).
   - Standard HTTP — no SSL certificate toggle required.

3. **[`Mallow_Billing_System.postman_environment.json`](Mallow_Billing_System.postman_environment.json)** (**With DDEV**):
   - Configured for containerized local development (`https://mallow-billing-system.ddev.site`).
   - Uses local DDEV HTTPS with self-signed certificate.

---

## Quick Setup

### Scenario A: Running Without DDEV (Native `php artisan serve`)
1. In your terminal, run `php artisan serve` (server starts at `http://127.0.0.1:8000`).
2. In Postman, click **Import** and select:
   - `Mallow_Billing_System.postman_collection.json`
   - `Mallow_Billing_System_Local.postman_environment.json`
3. In the environment dropdown, select: **`Mallow Billing System - Local Artisan (127.0.0.1:8000)`**.
4. You are ready to run tests immediately.

### Scenario B: Running With DDEV
1. In your terminal, run `ddev start`.
2. In Postman, click **Import** and select:
   - `Mallow_Billing_System.postman_collection.json`
   - `Mallow_Billing_System.postman_environment.json`
3. In the environment dropdown, select: **`Mallow Billing System - Local DDEV Environment`**.
4. Turn **OFF** *SSL certificate verification* in Postman Settings > General (due to local DDEV self-signed SSL).

---

## Detailed Documentation

For full setup instructions, environment variable details, assertion breakdowns, and Newman CLI usage, see:
👉 **[`docs/POSTMAN_SETUP.md`](../docs/POSTMAN_SETUP.md)**
