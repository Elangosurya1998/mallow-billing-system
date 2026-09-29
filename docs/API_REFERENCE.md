# Complete API Reference & Endpoints Specification

## 1. Overview & System Capabilities

The **Mallow Billing System** provides a high-scale, production-ready RESTful API suite designed for multi-tenant subscription management, graduated usage metering, exact-once idempotency ingestion, and second-accurate mid-cycle proration.

### Base URLs
- **Local Native (`artisan serve`)**: `http://127.0.0.1:8000/api/v1`
- **Local Containerized (DDEV)**: `https://mallow-billing-system.ddev.site/api/v1`

---

## 2. Interactive API Web Console & Playground

A full-featured interactive browser console is integrated directly into the application, enabling point-and-click execution and live JSON inspection for **all 20 system endpoints**:
- **Universal Console**: [`/console`](http://127.0.0.1:8000/console)
- **Merchant-Scoped Console**: [`/merchants/{merchant}/console`](http://127.0.0.1:8000/merchants)

The console features:
- Live dynamic idempotency key generation & replay tester.
- Multi-merchant switcher and pre-loaded active accounts.
- Real-time HTTP status badges (200, 201, 422, 429), millisecond roundtrip latency timer, and JSON syntax display.

---

## 3. Authentication, Multi-Tenancy & Rate Limiting

### Request Headers
| Header | Type | Description | Required |
| :--- | :--- | :--- | :--- |
| `X-API-Key` | String | API key identifying the calling merchant (e.g. `ak_test_acme_12345`) | Yes |
| `X-Tenant-ID`| UUID / Slug | Active merchant/tenant UUID or slug for strict multi-tenant data isolation | Yes (for tenant routes) |
| `Content-Type`| String | Must be `application/json` | Yes (for POST/PUT/PATCH) |
| `Accept` | String | Must be `application/json` | Yes |

### Rate Limiting Policy
- **Threshold**: **120 requests per minute per API key** (configured via `bootstrap/app.php`).
- **HTTP 429 Response Headers**:
  - `X-RateLimit-Limit`: Maximum requests per window (e.g. `120`).
  - `X-RateLimit-Remaining`: Remaining requests in current 60-second window.
  - `Retry-After`: Seconds to wait before retrying.

---

## 4. Usage Metering & Ingestion Pipeline

### 4.1 Ingest Meter Event (Exact-Once Idempotency)
`POST /api/v1/usage`

Ingests a raw resource consumption event. Replayed duplicate keys return `HTTP 200 OK` with `idempotent_replay: true` without incrementing counters.

#### Request Body
```json
{
  "customer_id": "01a0e8d1-588c-7011-aa03-58164aeb23ce",
  "metric_identifier": "api_requests",
  "quantity": 150,
  "idempotency_key": "evt_order_created_9921",
  "timestamp": "2026-09-29T10:00:00Z",
  "properties": {
    "endpoint": "/v1/orders",
    "region": "us-east-1"
  }
}
```

#### Responses
- **Fresh Event (`HTTP 201 Created`)**:
```json
{
  "data": {
    "id": "01a0ebb1-0432-71f4-8334-dc2e05ae5884",
    "customer_id": "01a0e8d1-588c-7011-aa03-58164aeb23ce",
    "metric_identifier": "api_requests",
    "quantity": 150,
    "idempotency_key": "evt_order_created_9921",
    "timestamp": "2026-09-29T10:00:00.000000Z"
  },
  "idempotent_replay": false
}
```
- **Duplicate Replay (`HTTP 200 OK`)**:
```json
{
  "data": { "id": "01a0ebb1-0432-71f4-8334-dc2e05ae5884", ... },
  "idempotent_replay": true
}
```

---

### 4.2 Batch Ingest Usage Events
`POST /api/v1/usage/batch`

Ingests up to 1,000 usage events in a single transactional batch.

#### Request Body
```json
{
  "events": [
    {
      "customer_id": "01a0e8d1-588c-7011-aa03-58164aeb23ce",
      "metric_identifier": "api_requests",
      "quantity": 100,
      "idempotency_key": "batch_item_1_1727599000"
    },
    {
      "customer_id": "01a0e8d1-588c-7011-aa03-58164aeb23ce",
      "metric_identifier": "compute_seconds",
      "quantity": 250,
      "idempotency_key": "batch_item_2_1727599000"
    }
  ]
}
```

#### Response (`HTTP 202 Accepted`)
```json
{
  "status": "processed",
  "ingested": 2,
  "duplicates": 0,
  "total_quantity": 350
}
```

---

### 4.3 Query Aggregated Usage Summaries
`GET /api/v1/usage/summary`

Fetches pre-aggregated daily rollups from `daily_usage_summaries` with bounded $O(30)$ scan complexity.

#### Query Parameters
- `customer_id` (optional): Customer UUID filter.
- `metric_identifier` (optional): Metric name filter.
- `start_date` / `end_date` (optional): `YYYY-MM-DD` range.

#### Response (`HTTP 200 OK`)
```json
{
  "data": [
    {
      "merchant_id": "01a0e8d1-587b-70e7-96c9-c6f4a3911761",
      "customer_id": "01a0e8d1-588c-7011-aa03-58164aeb23ce",
      "usage_date": "2026-09-29",
      "metric_identifier": "api_requests",
      "total_quantity": 38200,
      "event_count": 48
    }
  ]
}
```

---

### 4.4 List Raw Partitioned Usage Events
`GET /api/v1/usage/events`

Scans historical event partitions chronologically.

#### Query Parameters
- `customer_id` (optional): Customer UUID.
- `limit` (optional): 1 to 100 records (default: 20).

---

## 5. Customer Accounts Management

### 5.1 List Customers
`GET /api/v1/customers`

Returns paginated customer accounts scoped strictly to the authenticated tenant (`X-Tenant-ID`).

#### Response (`HTTP 200 OK`)
```json
{
  "data": [
    {
      "id": "01a0e8d1-588c-7011-aa03-58164aeb23ce",
      "merchant_id": "01a0e8d1-587b-70e7-96c9-c6f4a3911761",
      "name": "Beta Retail Pvt Ltd",
      "email": "finance@betaretail.com",
      "currency": "INR",
      "credit_balance_cents": 5000,
      "formatted_credit_balance": "INR 50.00",
      "external_reference": "ERP-CUST-883",
      "created_at": "2026-09-27T18:04:00Z"
    }
  ]
}
```

---

### 5.2 Create Customer
`POST /api/v1/customers`

Registers a new customer under the active tenant, with initial credit balance and optional immediate subscription plan assignment.

#### Request Body
```json
{
  "name": "HyperScale Cloud Inc",
  "email": "billing@hyperscale.io",
  "currency": "USD",
  "credit_balance": 25.50,
  "external_reference": "CRM-4019",
  "timezone": "UTC",
  "plan_id": "01a0e8d1-587d-7056-8a07-dade9ff1cba0"
}
```

#### Response (`HTTP 201 Created`)
```json
{
  "data": {
    "id": "01a0ebb9-a1b2-7c3d-8e4f-9a0b1c2d3e4f",
    "name": "HyperScale Cloud Inc",
    "email": "billing@hyperscale.io",
    "currency": "USD",
    "credit_balance_cents": 2550,
    "formatted_credit_balance": "USD 25.50",
    "external_reference": "CRM-4019"
  }
}
```

---

### 5.3 Get Customer Details
`GET /api/v1/customers/{customer}`

Retrieves profile, credit balances, and external reference for a customer.

---

## 6. Merchants & Tenants Lifecycle

### 6.1 Register Merchant / Tenant
`POST /api/v1/merchants` (or `POST /api/v1/tenants`)

Provisions a new merchant workspace. When created via Web UI, default starter and growth plans are automatically provisioned.

#### Request Body
```json
{
  "name": "Zenith Global Technologies",
  "slug": "zenith-global",
  "email": "finance@zenithglobal.com",
  "currency": "USD",
  "timezone": "America/New_York"
}
```

#### Response (`HTTP 201 Created`)
```json
{
  "data": {
    "id": "01a0ebc0-1122-3344-5566-778899aabbcc",
    "name": "Zenith Global Technologies",
    "slug": "zenith-global",
    "email": "finance@zenithglobal.com",
    "currency": "USD",
    "status": "active",
    "timezone": "America/New_York"
  }
}
```

---

### 6.2 Get Merchant Profile
`GET /api/v1/merchants/{tenant}`

Retrieves tenant operational configuration.

---

### 6.3 Update Merchant Profile
`PUT /api/v1/merchants/{tenant}` (or `PUT /api/v1/tenants/{tenant}`)

Updates name, operational currency, billing email, timezone, and active status.

#### Request Body
```json
{
  "name": "Zenith Global Enterprises Inc",
  "slug": "zenith-global",
  "email": "billing@zenithenterprises.com",
  "currency": "EUR",
  "timezone": "Europe/London",
  "status": "active"
}
```

---

### 6.4 Merchant Dashboard Executive Analytics JSON
`GET /api/v1/merchants/{id}/dashboard`

Returns real-time executive analytics derived from pre-aggregated daily summaries:
- Consumed vs quota cycle usage.
- Projected end-of-cycle overage revenue.
- Top 5 ranked consumers by percentage consumed.
- Churn-risk customers (>50% MoM usage drop).

#### Response (`HTTP 200 OK`)
```json
{
  "data": {
    "merchant_id": "01a0e8d1-587b-70e7-96c9-c6f4a3911761",
    "merchant_name": "Acme Corp",
    "currency": "INR",
    "cycle_usage": {
      "total_usage_units": 184320,
      "total_included_units": 250000,
      "consumption_percentage": 73.73
    },
    "projected_overage_revenue": {
      "incurred_cents": 0,
      "incurred_formatted": "INR 0.00",
      "projected_cents": 4260000,
      "projected_formatted": "INR 42600.00"
    },
    "top_customers_by_usage": [
      {
        "customer_id": "01a0e8d1-588c-7011-aa03-58164aeb23ce",
        "customer_name": "Beta Retail Pvt Ltd",
        "plan_name": "Growth",
        "used_units": 38200,
        "allowance_units": 41666,
        "percentage_consumed": 91.68
      }
    ],
    "churn_risk_customers": [
      {
        "customer_id": "01a0e8d1-588d-7112-bb14-69275bfc34df",
        "customer_name": "Nova Traders",
        "previous_period_usage": 50000,
        "current_period_usage": 19000,
        "drop_percentage": 62.0,
        "risk_level": "high"
      }
    ]
  }
}
```

---

## 7. Plans Catalog & Tiering

### 7.1 List Plans (Redis Cached)
`GET /api/v1/plans`

Returns all active plans. Responses are cached in Redis with a 10-minute TTL and flushed automatically upon updates.

---

### 7.2 Create Tiered Plan
`POST /api/v1/plans`

Creates a new plan and flushes the Redis plan cache.

#### Request Body
```json
{
  "name": "Enterprise Scale Tier",
  "slug": "enterprise-scale",
  "description": "High-volume compute and API metering",
  "invoice_interval": "month",
  "base_price_cents": 29900,
  "trial_period_days": 14,
  "is_active": true,
  "price_tiers": [
    {
      "metric_identifier": "api_requests",
      "tier_mode": "graduated",
      "first_unit": 0,
      "last_unit": 50000,
      "unit_price_cents": 0
    },
    {
      "metric_identifier": "api_requests",
      "tier_mode": "graduated",
      "first_unit": 50001,
      "last_unit": null,
      "unit_price_cents": 2
    }
  ]
}
```

---

### 7.3 Get Plan Details
`GET /api/v1/plans/{plan}`

Retrieves plan price tiers and included allowances.

---

## 8. Subscriptions Lifecycle & Mid-Cycle Proration

### 8.1 Create Subscription
`POST /api/v1/subscriptions`

Enrolls a customer into an active subscription with trial tracking.

---

### 8.2 Mid-Cycle Plan Switch (Proration Math)
`PATCH /api/v1/subscriptions/{subscription}`

Segments the current cycle into independent intervals ($T_1$ and $T_2$). Unused base fees from downgrades are credited to the customer's credit balance, while upgrades calculate prorated net charges.

#### Request Body
```json
{
  "plan_id": "01a0e8d1-587d-7056-8a07-dade9ff1cba0"
}
```

#### Response (`HTTP 200 OK`)
```json
{
  "subscription": {
    "id": "01a0e8d1-587f-71a2-a9c6-1dba0aa60f8c",
    "plan_id": "01a0e8d1-587d-7056-8a07-dade9ff1cba0",
    "status": "active"
  },
  "proration": {
    "net_adjustment_cents": 4200,
    "unused_credit_cents": 1800,
    "new_plan_charge_cents": 6000,
    "is_credit": false,
    "is_debit": true,
    "formatted": "$42.00"
  }
}
```

---

### 8.3 Cancel Subscription
`POST /api/v1/subscriptions/{subscription}/cancel`

Supports immediate cancellation or scheduling at period end (`immediately=true/false`).

---

### 8.4 Resume Subscription
`POST /api/v1/subscriptions/{subscription}/resume`

Reactivates a subscription scheduled for period-end cancellation.

---

## 9. Invoices & Payments

### 9.1 List Invoices
`GET /api/v1/invoices`

Returns paginated invoices filtered by `status`, `customer_id`, or `subscription_id`.

---

### 9.2 Get Invoice Breakdown
`GET /api/v1/invoices/{invoice}`

Returns full invoice line items (base subscription seats and metered overages calculated in integer cents).

---

### 9.3 Pay Invoice
`POST /api/v1/invoices/{invoice}/pay`

Transitions invoice status from `issued` to `paid` and records transaction audit data.

#### Response (`HTTP 200 OK`)
```json
{
  "success": true,
  "transaction_id": "txn_90218419",
  "amount_cents": 24900,
  "failure_reason": null,
  "invoice": {
    "id": "01a0e8d1-5903-7350-a1cf-75c8e6e55ced",
    "status": "paid",
    "amount_paid_cents": 24900,
    "amount_remaining_cents": 0
  }
}
```

---

## 10. Web Management Views Summary

| Route | Method | Description |
| :--- | :--- | :--- |
| `/merchants` | `GET` | All Merchants Directory with quick switcher |
| `/merchants/create` | `GET`, `POST` | Register Merchant with auto-provisioned plans |
| `/merchants/{id}/edit` | `GET`, `PUT` | Edit Merchant settings, currency, and timezone |
| `/merchants/{id}/dashboard` | `GET` | Wireframe Executive Visual Analytics Dashboard |
| `/merchants/{id}/customers/create` | `GET`, `POST` | Enroll Customer with credit balances & plan assignment |
| `/console` | `GET` | Universal API Web Console & Playground |
| `/merchants/{id}/console` | `GET` | Merchant-Scoped API Web Console & Playground |
