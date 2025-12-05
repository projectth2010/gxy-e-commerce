# Tenant E-Commerce – Implementation Phases & States

This document summarizes the implementation plan and states for Tenant E-Commerce, to be used as a working plan and a way to check progress step-by-step.

It focuses on the tenant slice of the platform and assumes the multi-tenant control plane (Center Control) exists and can create tenants.

---

## 1. High-Level States (Per Tenant)

For each tenant, the platform can be seen as evolving through these states:

1. **Tenant Not Onboarded**
2. **Tenant Onboarded – Store Not Live**
3. **Tenant Live – Core E-Commerce (MVP)**
4. **Tenant Live – Advanced Catalog & Multi-Store**
5. **Tenant Live – Integrations & POS**
6. **Tenant Live – Loyalty/Royalty & AI-Driven**

These states map to phases of implementation and can be used as checkpoints for success.

---

## 2. State 1 – Tenant Not Onboarded

### Definition

- No tenant has been created yet in the platform, or the tenant exists only as a concept.

### Implementation Focus

- Multi-tenant foundation:
  - Center Control can create, update, and manage tenants.
  - Tenant context (`tenant_id`) is supported end-to-end in the platform.
- Tenant Application Service is ready to receive tenant metadata and configuration from Center Control.

### Success Criteria

- Center Control can create a tenant record.
- Tenant Application can recognize and load basic configuration for a given `tenant_id`.

---

## 3. State 2 – Tenant Onboarded – Store Not Live (Setup Phase)

### Definition

- Tenant is created and has access to Backoffice but has not yet opened the store to customers.

### Implementation Focus

- **Store setup basic**
  - Single store per tenant:
    - Store information (name, logo, contact, policies).
    - Domain or subdomain mapping.
    - Locale, currency, and basic theme configuration.

- **Minimal catalog**
  - Ability to create and manage simple products and categories.
  - Basic inventory per product/variant.

- **Basic checkout & order path (internal testing)**
  - Cart and checkout flow available for test orders.
  - Payment integration with at least one provider.
  - Orders persisted with correct tenant context and stock updates.

### Success Criteria

- Tenant Owner can log in to Backoffice and configure store basics.
- Simple products and categories can be created and viewed in a test storefront.
- A test order can be placed and recorded end-to-end (without necessarily being public to real customers).

---

## 4. State 3 – Tenant Live – Core E-Commerce (MVP)

### Definition

- Tenant store is open to customers and can operate a full basic online shop.

### Implementation Focus

- **Core catalog & order management**
  - Full product and category management with inventory tracking.
  - Order lifecycle: pending → paid → shipped → completed/cancelled.
  - Basic returns and refunds.

- **Customer accounts & self-service**
  - Customer registration and login (integrated with the platform auth model).
  - Customer profile, address book, and order history.

- **Notifications**
  - Transactional emails (order confirmation, shipping updates, password reset).

- **Tenant dashboard (basic)**
  - Sales overview, order counts, and top-selling products.

### Success Criteria

- Real customers can browse, add to cart, checkout, and receive notifications.
- Tenant Staff can manage orders and customers from Backoffice.
- Basic KPIs are visible in the tenant dashboard.

---

## 5. State 4 – Tenant Live – Advanced Catalog & Multi-Store

### Definition

- Tenant uses more advanced catalog capabilities and may operate multiple storefronts.

### Implementation Focus

- **Advanced catalog model**
  - Product attributes and attribute sets.
  - Configurable products/variants.
  - More flexible pricing and promotion rules.

- **Multi-store per tenant**
  - Introduce `store`/`storefront` as a domain concept within a tenant.
  - Per-store configuration (domain, locale, currency, theme).
  - Ability to share or override product visibility, pricing, and content per store.

### Success Criteria

- Tenant can define and use product templates (attribute sets) for different categories.
- Tenant can operate more than one storefront under the same tenant, with clear store-specific settings.
- Catalog and orders are correctly scoped by both `tenant_id` and `store_id` where applicable.

---

## 6. State 5 – Tenant Live – Integrations & POS

### Definition

- Tenant centralizes orders across online, POS (in-store), and external platforms.

### Implementation Focus

- **Tenant POS (Point of Sale)**
  - POS UI/channel for tenant admins and staff.
  - POS orders:
    - Created through a dedicated POS flow using the same order and inventory model.
    - Tagged with channel/source and optionally location/register.

- **External/third-party order ingestion**
  - Integration/adapter layer for marketplaces and other external channels.
  - Mapping from external orders into the internal order model.
  - Tracking of external order references for reconciliation.

### Success Criteria

- Tenant Staff can create POS orders that update inventory and appear in reports alongside online orders.
- Orders from at least one external platform can be imported and managed as native orders.
- Reporting and analytics can segment orders by channel (online, POS, external).

---

## 7. State 6 – Tenant Live – Loyalty/Royalty & AI-Driven

### Definition

- Tenant leverages loyalty programs, optional royalty models, and AI/analytics to optimize business.

### Implementation Focus

- **Loyalty program**
  - Loyalty accounts per customer within a tenant (and per store where required).
  - Points accrual rules based on order value/products.
  - Redemption flows during checkout (online and POS).
  - Optional tiering (e.g., Silver/Gold/Platinum) with associated benefits.

- **Royalty (optional)**
  - Rules and data structures for sharing revenue with brands/affiliates/licensors.
  - Allocation of revenue/margin per order or order item.

- **AI and analytics integration**
  - Use analytics data to:
    - Recommend plans/features to tenants.
    - Suggest promotions or catalog changes.
    - Detect anomalies in sales or operations.

### Success Criteria

- Customers can earn and redeem points across channels.
- Loyalty transactions and tiers are visible to both customers and staff.
- Royalty allocations (if used) are recorded and can be reported.
- Analytics/AI provide actionable insights (e.g., recommended campaigns or alerts).

---

## 8. Using This Plan

- **For Product / PM-PO**
  - Use states and success criteria as checkpoints in the roadmap.
  - Map features in `pm-po-tenant-scope-and-roadmap.md` to these phases.

- **For Architecture / SA**
  - Ensure that each state is supported structurally before enabling the next.
  - Confirm that control plane, data plane, and analytics plane can support the planned evolution.

- **For DBA / Data**
  - Plan schema evolution and data migrations according to when advanced catalog, multi-store, POS, external orders, and loyalty/royalty are introduced.

This phased view should be updated as the platform evolves, but it provides a clear starting structure for planning and tracking implementation and success for Tenant E-Commerce.
