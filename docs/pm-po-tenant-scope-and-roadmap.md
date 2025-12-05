# PM/PO – Tenant E-Commerce Scope and Roadmap

## 1. Overview and Vision

This document defines the scope and roadmap for the **Tenant E-Commerce** part of the platform from a Product Management / Product Owner perspective.

It focuses on:

- What a tenant (store owner) can do with the platform.
- Core capabilities required to operate an online store.
- Relationship with Center Control (plans, features, governance).

The goal is to provide a clear, tenant-centric product view that complements the platform-level documents.

---

## 2. Tenant Personas and Roles

- **Tenant Owner**
  - Sets up and configures the store.
  - Oversees catalog, pricing, and overall performance.

- **Tenant Staff / Operator**
  - Manages daily operations: orders, customer service, inventory updates.

- **Customer (End User)**
  - Browses the store, purchases products, and interacts with the brand.

These personas operate within boundaries defined by Center Control (plans, features, policies).

---

## 3. Core Capabilities (Tenant E-Commerce)

### 3.1 Store Setup & Configuration

- Configure basic store information:
  - Store name, logo, contact details, address.
- Customize basic appearance:
  - Theme options, colors, and banners within limits defined by the platform.
- Configure store policies:
  - Shipping methods and fees.
  - Payment methods (as enabled by Center Control).
  - Return and refund policies.

### 3.2 Catalog Management

- Manage products:
  - Create, edit, and deactivate products.
  - Upload product images and manage galleries.
  - Configure variants (size, color, etc.) where supported.

- Manage categories:
  - Create and maintain category structures.
  - Assign products to multiple categories.

- Inventory management:
  - View and update stock levels.
  - Optional low-stock alerts.

### 3.3 Order Management

- Order processing:
  - View new orders, update order status (e.g., pending, paid, shipped, completed, cancelled).
  - Add tracking information for shipments.

- Payment status tracking:
  - View payment status (authorized, captured, failed, refunded).

- Basic returns and refunds (scope to be refined):
  - Initiate and track refund requests, where permitted by the platform.

### 3.4 Customer Management & Customer Service

- Customer list and profiles:
  - View recent orders, contact information, and basic activity.

- Customer segments (simple level in MVP):
  - Tag or group customers (e.g., VIP, frequent buyers) for basic targeting.

- Customer service workspace (initial scope):
  - Allow staff to search by customer, order number, email, or phone.
  - Show a consolidated view of customer profile, order history, and key events (order status changes, payments, refunds).
  - Provide actions commonly used by customer service, such as:
    - Update order status where permitted.
    - Initiate returns/refunds (within platform rules).
    - Add internal notes on orders or customers for follow-up.

### 3.5 Promotions and Marketing (MVP Level)

- Coupon codes:
  - Create fixed/percentage discount codes.
  - Define basic rules (validity period, minimum order value, max redemptions).

- Basic campaigns:
  - Simple sale events with discounted products or categories.

Future phases may extend promotions with more advanced logic.

### 3.6 Analytics (Tenant-Level View)

- Dashboard for tenant owners and staff:
  - Sales overview (revenue, orders) over selected time periods.
  - Top-selling products.
  - Basic conversion metrics (where data available).

- These views are powered by the Analytics Plane but presented in a tenant-friendly format.

### 3.7 Tenant POS (Point of Sale)

- In-store / backoffice POS interface for tenant admins and staff:
  - Create orders directly on behalf of customers (phone orders, in-store orders).
  - Support basic cart, discounts, and payment capture flows suitable for staff usage.
  - Optionally link POS orders to physical locations or registers, where defined.

### 3.8 Third-Party Order Integration

- Ingest orders from external platforms (e.g., marketplaces, social media):
  - Normalize orders into tenant’s catalog and order management.
  - Support for multiple external platforms via well-defined integrations.

---

## 4. Tenant Feature Overview & Key Processes

This section provides a concise view of what tenants can do and how the main processes flow.

- **Store setup & multi-store**
  - Create and configure one or more stores under a tenant (name, domain, locale, currency, theme).
  - Process: Tenant Owner configures stores → Center Control policies/plans applied → Tenant storefronts rendered accordingly.

- **Catalog & product management**
  - Manage products, variants, attributes/attribute sets, categories, and media.
  - Process: Staff defines product templates (attribute sets) → creates/updates products and variants → assigns to categories and stores → storefront uses this data for browsing/search.

- **Inventory & pricing**
  - Maintain stock levels and base/special prices per product/variant (and per store where applicable).
  - Process: Inventory updated on catalog changes and orders → low-stock conditions can trigger alerts or reporting.

- **Promotions & coupons**
  - Configure basic to advanced promotions (cart/catalog rules, coupons) according to plan.
  - Process: Tenant Owner defines rules/coupons → promotion engine evaluates them during browsing/checkout → applied promotions recorded on orders.

- **Checkout & order management**
  - Full order lifecycle from cart to fulfillment and refund.
  - Process: Customer browses and adds items to cart → checkout (address, shipping, payment) → payment processed via shared Payment Service → order created and updated by staff through Backoffice.

- **Customer & account management / customer service**
  - Manage customer profiles, addresses, segmentation, and service interactions.
  - Process: Customers register or log in → profile and orders linked to tenant → staff uses consolidated views and tools to:
    - Answer inquiries about orders, payments, and deliveries.
    - Initiate allowed changes (for example, address changes before shipment, order cancellation where possible).
    - Record internal notes and context so the next staff member can continue the conversation effectively.

- **Notifications & communication**
  - Trigger transactional notifications (orders, shipping, password reset) through shared Notification Service.
  - Process: Tenant events (order created/shipped, account events) → notification requests sent to shared service → messages delivered and logged.

- **Tenant-level analytics**
  - View key KPIs and trends in Backoffice dashboards.
  - Process: Events and transactions sent to Analytics Plane → aggregated into summaries → Tenant dashboards query read-only analytics APIs.

---

## 5. Relationship with Center Control (Plans & Governance)

- **Plans and feature sets**
  - Tenants are assigned a plan that defines which capabilities are available:
    - Number of products, staff accounts, and integrations.
    - Access to advanced features (e.g., advanced promotions, integrations, loyalty).

- **Feature flags**
  - Platform can toggle features on/off per tenant or per plan.
  - Tenant UI adapts to show/hide features accordingly.

- **Policies and compliance**
  - Center Control may enforce policies on data retention, payment methods, and shipping options.
  - Tenants operate within these boundaries but have flexibility to configure details.

---

## 6. MVP Scope vs Future Enhancements

### 5.1 MVP Scope (Tenant-Facing)

- Store setup and basic customization.
- Core catalog management (products, categories, inventory).
- Order management and basic payment tracking.
- Basic customer management.
- Simple promotions (coupon codes and basic campaigns).
- Tenant-level dashboard with essential KPIs.

### 5.2 Out of Scope for MVP (Future Phases)

- Advanced promotions and dynamic pricing rules.
- Loyalty and membership programs (points, tiers, benefits) usable per tenant and, where needed, per store.
- Advanced segmentation and marketing automation.
- Native marketplace integrations (selling on external channels) and third-party order ingestion.
- POS and advanced warehouse/fulfillment capabilities beyond basic POS flows.

These can be added in future phases as the platform matures.

---

## 7. Roadmap (High-Level)

### Phase 1 – Tenant MVP

- Deliver core capabilities for a single tenant store to operate end-to-end.
- Ensure full integration with Center Control for tenant creation, configuration, and plan/feature assignment.
- Provide basic dashboards and reporting.

### Phase 2 – Operational Maturity & Extensions

- Enhance order management (returns, partial refunds, more detailed statuses).
- Expand promotions (more rule types, scheduling).
- Improve analytics views and filtering for tenants.

### Phase 3 – Advanced Features and Integrations

- Introduce loyalty/membership features.
- Add deeper integrations (external marketplaces, CRM, marketing tools).
- Introduce tenant POS flows suitable for in-store use (basic level).
- Add support for ingesting orders from third-party platforms via well-defined integrations.
- Develop advanced warehouse and multi-location stock management.

---

## 8. Platform & Tenant Phase Alignment (Summary)

From a PM/PO view, Tenant capabilities are introduced in phases that depend on the underlying platform (Center Control) being ready.

- Tenant phases **T1–T6** are defined in `tenant-implementation-phases.md`.
- Center Control phases **C1–C6** are defined in `center-control-implementation-phases.md`.
- The detailed mapping between C1–C6 and T1–T6 is captured in `platform-phase-mapping.md`.

Practical implications for planning:

- **Before scaling advanced Tenant features**, ensure platform phases are in place:
  - Tenant MVP (core store live – roughly T2–T3) should be rolled out only when at least C1–C3 are available (multi-tenant ready, lifecycle, basic plans/feature flags).
  - Advanced catalog and multi-store (T4) should align with stronger governance and monitoring (C3–C4).
  - Integrations, POS, and loyalty/royalty (T5–T6) require ecosystem integrations and analytics/AI capabilities (C3–C6).
- **For roadmap discussions**, PM/PO can use the mapping table to:
  - Explain to stakeholders why some Tenant features must wait for platform work.
  - Plan pilot vs GA rollouts (for example, run T4–T6 only on selected tenants while Center matures to C5–C6).

This keeps Tenant roadmap ambitions grounded in realistic platform readiness while still communicating a clear end-state vision.

---

## 9. Success Metrics (Tenant Perspective)
