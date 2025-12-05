# Implementation Plan – Laravel (PHP) + Vue

This document describes the proposed technology stack, high-level repository structure, and first implementation modules for building the Center Control + Tenant E-Commerce platform using **Laravel (PHP)** and **Vue 3**.

## 1. Audience and Usage

### 1.1 Audience

- Tech Lead / Solution Architect
- Senior Backend / Frontend Engineers
- DevOps engineers involved in environment and deployment setup

### 1.2 When to use this document

- When starting implementation of the platform based on the architecture and product documents.
- When aligning development tasks (issues/sprints) with Center/Tenant phases and SA/DBA checklists.
- When onboarding new engineers to the technical stack and structure of this project.

It focuses on the initial implementation target:

- Center Control phases: **C1–C3**  
  (Platform Foundations, Tenant Lifecycle & Governance, Plan/Feature Toggles)
- Tenant E-Commerce states: **T2–T3**  
  (Tenant Onboarded – Store Not Live, Tenant Live – Core E‑Commerce MVP)

## 2. Related documents

This implementation plan should be read together with:

- Architecture: `sa-architecture.md`, `sa-system-structure-and-planes.md`, `sa-tenant-architecture.md`
- Database: `dba-multi-tenant-db-design.md`, `dba-tenant-data-design.md`
- Product scope: `pm-po-tenant-scope-and-roadmap.md`
- Phases: `center-control-implementation-phases.md`, `tenant-implementation-phases.md`, `platform-phase-mapping.md`
- Checklists: `sa-checklists.md`, `dba-checklists.md`

---

## 3. Technology Stack Summary

### Backend

- **Language:** PHP 8.x
- **Framework:** Laravel (latest stable)
- **Architecture style:** Modular monolith with clear domain boundaries; future-ready for service extraction.
- **Database:** PostgreSQL or MySQL (to be chosen; both supported by Laravel and DBA docs).
- **ORM:** Eloquent ORM (with global scopes / query filters for multi-tenant `tenant_id`).
- **Authentication (user-facing):**
  - Laravel Sanctum or Passport (for Backoffice and Storefront users).
- **Service-to-service security:**
  - Custom HMAC-based signing per `sa-architecture.md` §8 (headers `x-api-key`, `x-timestamp`, `x-signature`, `x-tenant-id`).
- **Queues / async:**
  - Laravel Queues (e.g., Redis) for notifications, background tasks.

### Frontend

- **Framework:** Vue 3
- **Routing:** Vue Router
- **State management:** Pinia
- **Build tooling:** Vite
- **Apps (at least initially):**
  - `Center Admin` – platform operators.
  - `Tenant Backoffice` – tenant admins/staff.
  - `Tenant Storefront` – customers.

### DevOps / Environments

- **Environments:** dev, staging, prod (per SA docs).
- **Deployment:** Container-based recommended (Docker), but can start simple and evolve.
- **CI/CD:** Git-based pipeline (to be defined) running tests, lint, and deployments.

---

## 2. Repository and Project Structure (Proposed)

At the initial stage, a **single repository** is used, with one Laravel backend and multiple Vue frontends in subfolders.

```text
root/
  backend/
    laravel-app/            # Laravel project (Center + Tenant + Shared)
  frontend/
    center-admin/           # Vue app for Center Control (platform admin)
    tenant-backoffice/      # Vue app for tenant owner/staff
    tenant-storefront/      # Vue app for public shop
  docs/                     # Existing documentation
```

Within `backend/laravel-app`, use a domain-oriented structure:

```text
backend/laravel-app/
  app/
    Domain/
      CenterControl/        # Tenant management, plan/feature, monitoring hooks
      TenantApp/            # Core tenant catalog, orders, customers, CS workspace
      Shared/               # Shared capabilities (e.g., auth integration, payments facade)
    Http/
      Controllers/
        CenterControl/
        Tenant/
      Middleware/
      Requests/
  database/
    migrations/
    seeders/
  routes/
    api.php                 # Versioned APIs (e.g., /api/center, /api/tenant)
```

This keeps Center-related code and Tenant-related code separated logically while still sharing common Laravel infrastructure.

---

## 3. Multi-Tenant Strategy in Laravel

For the initial implementation, we follow the **shared DB + `tenant_id`** strategy as described in DBA docs.

Key implementation points:

- **Tenant context resolution:**
  - Resolve `tenant_id` from domain/subdomain or explicit header.
  - Implement a middleware that:
    - Determines `tenant_id` and attaches it to the request context.
    - Applies a global scope for Eloquent models that are tenant-scoped.

- **Global scopes:**
  - Define base model or trait (e.g., `TenantScoped`) that adds a global scope on `tenant_id`.
  - Use it for all tenant-specific entities (products, orders, customers, etc.).

- **Center vs Tenant DB access:**
  - Center metadata tables (Tenants, Plans, Features) share the same DB initially.
  - Tenant domain tables also share the DB, but always include `tenant_id` (and later `store_id` where needed).

This aligns with:

- `dba-multi-tenant-db-design.md` §2.1, §4.1.
- `dba-tenant-data-design.md` §4.1–4.3.

---

## 4. First Backend Modules (C1–C3, T2–T3)

This section lists the **first implementation modules** to build in Laravel, mapped to phases/states.

### 4.1 CenterControl\TenantManagement (C1–C2)

Scope:

- Tenant master data and lifecycle.

Entities (DB):

- `tenants`:
  - `id`, `code`, `name`, `status`, `plan_id`, domain/subdomain, created/updated timestamps.

Laravel components:

- Model: `App\Domain\CenterControl\Models\Tenant`
- Controller: `App\Http\Controllers\CenterControl\TenantController`
- Routes (example):
  - `POST /api/center/tenants` – create tenant.
  - `GET /api/center/tenants` – list tenants.
  - `GET /api/center/tenants/{id}` – view tenant.
  - `PATCH /api/center/tenants/{id}` – update tenant (status, plan, config summary).

Checklist alignment:

- SA: `sa-checklists.md` – C1/C2 tenant model & lifecycle.
- DBA: `dba-multi-tenant-db-design.md` – Tenant entity; `tenant_id` usage elsewhere.

### 4.2 CenterControl\PlanFeature (C3)

Scope:

- Plans and feature flags per tenant.

Entities (DB):

- `plans` (name, description, limits summary).
- `features` (code, description).
- `plan_features` (plan ↔ feature mapping).
- `tenant_plan_assignments` (tenant ↔ plan, effective dates).

Laravel components:

- Models & migrations for the above tables.
- Controller: `PlanController` for defining plans, `TenantPlanController` for assigning.
- Internal method or API to expose **effective feature set** for a tenant.

Checklist alignment:

- SA: `sa-architecture.md` §9.2, `sa-checklists.md` C3.
- PM/PO: plan/feature behaviour in `pm-po-tenant-scope-and-roadmap.md` §5.

### 4.3 TenantApp\Core (T2–T3)

Scope:

- Core tenant capabilities for MVP store:
  - Basic store configuration.
  - Catalog (simple products, categories, inventory).
  - Orders (basic lifecycle) and customers.

Entities (DB) – see `dba-tenant-data-design.md` §3:

- `store_configs` or part of `tenants` for basic store info (name, logo, contact, locale, currency).
- `products`, `product_images`, `categories`, `product_categories`, `inventory`.
- `customers`, `customer_addresses`.
- `orders`, `order_items`, `order_status_history`, `payments` (MVP-level fields).

Laravel components:

- Tenant-scoped models with `tenant_id`.
- Controllers (under `/api/tenant/...`):
  - `ProductController`, `CategoryController`, `OrderController`, `CustomerController`.
- Middleware to ensure every tenant API call has tenant context and enforces isolation.

Tenant states alignment:

- T2 – Tenant Onboarded – Store Not Live:
  - Backoffice can configure store basics and create simple products.
  - Internal test orders possible.
- T3 – Tenant Live – Core E‑Commerce (MVP):
  - Storefront APIs for browse/cart/checkout connect to this module.

### 4.4 TenantApp\CustomerService (T3+)

Scope:

- Customer service workspace as defined in PM/PO and SA/DBA docs.

Entities (DB):

- Use existing `customers`, `orders`, `order_status_history`, `payments`, plus:
- `customer_service_notes` (from `dba-tenant-data-design.md` §3.4.1).

Laravel components:

- Endpoints to:
  - Search customers/orders by email, phone, order number.
  - Retrieve consolidated view of customer + orders + events.
  - Create internal notes on customer/order.

This can be implemented incrementally on top of core tenant data.

---

## 5. Frontend Apps – Initial Scope

### 5.1 Center Admin (Vue)

Initial features (C1–C3):

- Tenant list/detail pages:
  - Create/edit tenants (name, plan, domain, status).
- Plan & feature management views:
  - Manage plans, feature sets, and tenant assignments.

### 5.2 Tenant Backoffice (Vue)

Initial features (T2–T3):

- Store setup:
  - Basic configuration (store info, logo, locale, currency, policies).
- Catalog management:
  - CRUD products and categories.
- Order management:
  - View orders, change status, add tracking.
- Customer management & customer service workspace:
  - View customer details, order history, add internal notes.

### 5.3 Tenant Storefront (Vue)

Initial features (T3):

- Product listing & detail.
- Cart & checkout UI calling tenant APIs.
- Basic account & order history pages.

---

## 6. UI/UX Guidelines (Initial)

This section defines initial UI/UX guidelines for the three main Vue applications. It is intentionally opinionated but lightweight so teams can start building screens without waiting for full design artifacts. It should evolve together with design system work.

### 6.1 Center Admin – Platform Operators

**Primary goals (C1–C3):**

- Provide a fast, information-dense workspace for SA/Ops to manage tenants, plans, and platform governance.
- Optimize for clarity and safety over marketing aesthetics.

**Layout**

- Left sidebar navigation with the following primary sections:
  - Dashboard (high-level platform status – can be stubbed in early phases).
  - Tenants (list/detail for tenant lifecycle operations).
  - Plans & Features (C3+).
  - System / Settings (future).
- Top bar:
  - Product name / logo.
  - Environment badge (dev/stage/prod where applicable).
  - User menu (profile, sign out).

**Tenants list view (C1–C2)**

- Table columns (minimum):
  - Tenant code.
  - Tenant name.
  - Status (visual badge: draft, active, suspended, terminated).
  - Plan (name or `—` if none).
  - Created at / Last updated.
  - Actions (View / Edit / Change status).
- Filters:
  - Status dropdown.
  - Free-text search on code/name.
- Row actions should be explicit and confirm destructive operations (e.g., suspend, terminate).

**Tenant detail/edit view**

- Group fields in logical sections:
  - **Identity**: code (read-only), name, description.
  - **Access & domains**: primary domain, additional domains (future), status.
  - **Plan & features**: current plan, start/end date, feature summary (read-only in early phases).
- Show a compact timeline or audit snippet (even if stubbed) to support operations and debugging.

### 6.2 Tenant Backoffice – Tenant Admin & Staff

**Primary goals (T2–T3):**

- Provide a structured operational console for catalog, orders, and customers.
- Favor consistency and predictability; avoid surprising navigation patterns.

**Layout**

- Left sidebar with main sections:
  - Dashboard.
  - Catalog (Products, Categories).
  - Orders.
  - Customers.
  - Promotions (future).
  - Reports / Analytics (where applicable).
  - Settings.
- Content area uses common patterns:
  - List view → Detail view → Edit form.
  - Primary actions in the top-right of the content area (e.g., "Add product").

**Catalog UX (MVP)**

- Product list:
  - Table with SKU, name, status, base price, stock, store (if multi-store enabled).
  - Filters for status, category, and text search.
- Product detail/edit:
  - Tabs or sections for Basics, Pricing, Inventory, Media.
  - Validation feedback inline, with clear error messages.

**Order management UX (MVP)**

- Order list:
  - Table with order number, date, customer, total, status, payment state.
  - Quick filters for status (new, paid, shipped, completed, cancelled).
- Order detail:
  - Left side: core order info (items, totals, shipping, payment).
  - Right side: status timeline and quick actions (change status, add tracking, add CS note).

**Customer & CS workspace (MVP)**

- Search bar that supports email, phone, and order number.
- Consolidated view with:
  - Customer profile (name, contact, tags).
  - Order history.
  - Internal notes timeline.
- Notes are clearly marked as internal and never exposed to the customer.

### 6.3 Tenant Storefront – End Customers

**Primary goals (T3 MVP):**

- Make it easy for customers to browse, understand products, and complete checkout.
- Keep visual design simple but clean, leaving room for future theming.

**Layout**

- Header:
  - Logo / store name.
  - Primary navigation (top categories).
  - Search box.
  - Cart icon with item count.
  - Account menu (Sign in / Profile) when supported.
- Footer:
  - Basic links (About, Contact, Policies), social links (optional).

**Key flows**

- Home:
  - Highlight featured categories and selected products.
- Category listing:
  - Grid/list of products with image, name, price, and status badges (e.g., sale, out of stock).
  - Filters and sort controls (price, popularity, newest first).
- Product detail:
  - Large image, gallery thumbnails.
  - Title, price, key attributes, stock indicator.
  - Clear "Add to cart" CTA, quantity selector.
- Cart & checkout:
  - Cart page with editable quantities and clear subtotal/total.
  - Step-by-step checkout (address → shipping → payment review), aligned with Payment/Shipping configuration from Backoffice.

**Responsiveness**

- All three apps should be responsive, but for early phases:
  - Center Admin and Tenant Backoffice prioritize desktop-first layouts.
  - Storefront must support mobile and tablet as first-class experiences.

---

## 7. Suggested Implementation Order (High-Level)

1. **Set up Laravel project and base infra**  
   - Install Laravel in `backend/laravel-app`.  
   - Configure DB connection, migrations, and basic health endpoint.

2. **Implement TenantManagement (C1–C2)**  
   - `tenants` table + model + CRUD APIs for Center.
   - Tenant resolution middleware (stubbed logic) and basic admin UI in Center Admin Vue app.

3. **Implement PlanFeature (C3)**  
   - Plan/feature entities and assignment APIs.
   - Simple UI to manage plans and assign to tenants.

4. **Implement TenantApp Core (T2–T3)**  
   - Core catalog, order, customer models and APIs.
   - Tenant Backoffice basic UI for catalog & orders.  
   - Storefront browse/checkout wired to APIs (at least for a single test tenant).

5. **Add CustomerService basics**  
   - `customer_service_notes` and CS endpoints.  
   - Backoffice CS workspace views.

Future phases (T4–T6, C4–C6) will extend this foundation without changing stack.
