# SA – Tenant E-Commerce Architecture

## 1. Overview

This document describes the architecture of a single **Tenant E-Commerce** slice within the multi-tenant platform, from the tenant-facing perspective. It focuses on:

- Tenant storefront and backoffice applications.
- Tenant-specific business capabilities and boundaries.
- Interactions with Center Control (control plane) and Shared Domain Services.
- Structural and protocol-level concerns, independent of specific technologies.

Each tenant instance shares the same application code but operates under its own **tenant context**, plans, features, and data boundaries.

---

## 2. Tenant-Facing Application Architecture

### 2.1 Main Components (Per Tenant Context)

- **Storefront Application**
  - Public-facing shop for end customers.
  - Provides product discovery, cart, checkout, customer account, and order history.

- **Backoffice Application (Tenant Admin Panel)**
  - Used by tenant owners and staff.
  - Provides management of products, categories, inventory, orders, customers, promotions, and customer service workflows.

- **Tenant Application Service Layer**
  - Exposes APIs for both Storefront and Backoffice.
  - Implements tenant-specific business rules.
  - Enforces tenant isolation in all operations.

### 2.2 Logical Layers (Within Tenant Application Service)

- **Presentation Layer**
  - Storefront and Backoffice UIs.

- **Application/API Layer**
  - HTTP APIs / application services handling tenant-specific use cases:
    - Product browsing, search, cart operations.
    - Order placement and management.
    - Customer registration, authentication, and profile management.
    - Customer service operations (for example, order lookups, returns/refunds initiation, customer notes).

- **Domain/Business Layer**
  - Core domain logic:
    - Product/catalog.
    - Pricing and promotions.
    - Orders, payments, fulfillment.
    - Customer and segmentation basics.

- **Persistence Layer**
  - Access to tenant-scoped data storage (shared or per-tenant DB with tenant context).
  - Ensures that all queries and updates are scoped to the current tenant.

---

## 3. Interaction with Center Control (Control Plane)

Tenant E-Commerce applications do not manage their own lifecycle, plan, or global configuration. Instead, they rely on **Center Control** as the control plane.

### 3.1 Tenant Lifecycle and Configuration

- **Tenant provisioning and updates**
  - Initiated by Center Control through internal commands (for example, create/update tenant).
  - Tenant Application Service receives lifecycle and configuration commands, such as:
    - Create tenant logical structures (initial settings, defaults).
    - Update tenant configuration (store info, domains, settings).
    - Change status (activate, suspend, disable).

- **Source of truth**
  - Center Control is the system of record for tenant metadata, lifecycle state, and plans.
  - Tenant Application caches and applies received configuration but does not override global definitions.

### 3.2 Plan and Feature Flags

- **Plan/feature assignment**
  - Center Control assigns plans and feature flags per tenant.
  - Tenant Application fetches or receives the effective feature set and applies it at runtime.

- **Feature enforcement**
  - Tenant Application uses the effective feature set to:
    - Enable or disable modules (for example, advanced promotions, loyalty features).
    - Enforce limits (for example, product count, staff accounts) where applicable.

- **Protocol alignment**
  - Tenant Application uses agreed internal APIs to:
    - Pull feature sets from Center Control.
    - Accept pushed feature configuration when plans or flags change.

---

## 4. Interaction with Shared Domain Services

Tenant E-Commerce relies on shared services for cross-cutting capabilities.

### 4.1 Payment Service

- Tenant Application calls a shared Payment Service to:
  - Initiate, confirm, and track payments.
  - Handle asynchronous payment callbacks/notifications.

- Tenant Application:
  - Keeps minimal payment state needed for orders.
  - Delegates external payment provider complexity to the shared service.

### 4.2 Notification Service

- Tenant Application triggers:
  - Order confirmation emails/SMS.
  - Password reset or account notifications.

- Notification Service:
  - Handles templates, channels, and delivery details.
  - Logs notification status and errors.

### 4.3 Media/File Service

- Tenant Application uses a Media/File Service to:
  - Upload and retrieve product images, banners, and logos.
  - Store assets under tenant-specific namespaces.

---

## 5. Tenant Context and Isolation

### 5.1 Tenant Context Propagation

- Every request to Tenant Application Services carries explicit tenant context:
  - Resolved by domain/subdomain or headers (for example, `x-tenant-id`).
- Tenant context is propagated through all layers:
  - Presentation → Application/API → Domain → Persistence.

### 5.2 Data Isolation

- All read/write operations are explicitly scoped by tenant context.
- Depending on chosen multi-tenant strategy:
  - Shared DB with `tenant_id` on all business tables.
  - Or database/schema per tenant.

- Isolation is enforced consistently at:
  - Application layer (queries always include tenant context).
  - Data model (keys and constraints are tenant-scoped where appropriate).

### 5.3 Configuration Isolation

- Tenant configuration (store settings, theme, payment/shipping options) is stored and resolved per tenant.
- Shared global defaults can be overridden by tenant-specific configuration within allowed constraints from Center Control.

---

## 6. Non-Functional Considerations (Tenant Slice)

### 6.1 Performance and Scalability

- Tenant Application Services must handle varying load per tenant:
  - Some tenants may receive significantly more traffic than others.
- The architecture should allow:
  - Horizontal scaling of Tenant Application instances.
  - Separation of hot tenants (placement to different clusters or replicas where needed).

### 6.2 Reliability and Fault Isolation

- Failures affecting a specific tenant should not impact others.
- Shared services should be robust and rate-limited to prevent one tenant from exhausting shared resources.

### 6.3 Observability

- Tenant-level metrics and logs:
  - Request rates, errors, and latencies tagged with `tenant_id`.
  - Business events (orders, payments, signups) also tagged with tenant context.
- Center Control and operations tools use these signals to monitor individual tenants and the platform as a whole.

---

## 7. Extended Tenant Channels and Programs

In addition to the primary web storefront, the tenant architecture should support additional channels and programs that increase flexibility and revenue opportunities.

### 7.1 Tenant POS (Point of Sale)

- Provide an internal POS interface for tenant admins and staff:
  - Runs as an additional client of the Tenant Application Service (for example, specialized backoffice views or dedicated POS UI).
  - Uses the same core order and inventory domain models as the web storefront.
- POS orders:
  - Are represented as orders within the same order domain, with clear flags indicating POS origin and, where applicable, store location or register.
  - Participate in the same payment, fulfillment, and analytics flows as online orders.
- This ensures a unified view of orders, customers, and inventory across online and in-store sales.

### 7.2 Third-Party Order Integration

- The architecture should support ingesting orders originating from external platforms (for example, marketplaces or partner systems) into the tenant’s order domain.
- Integration can be modeled as:
  - A dedicated integration or adapter layer that receives normalized order payloads from third-party systems.
  - A mapping component that translates external order structures into the internal order model (products, prices, customers, and promotions where applicable).
- Third-party orders should:
  - Be tagged with their source and channel identifiers.
  - Follow the same downstream flows as native orders (fulfillment, notifications, analytics, loyalty accrual).

### 7.3 Loyalty and Royalty Programs

- Introduce a dedicated domain component for **loyalty** (and, where needed, royalty) programs:
  - Loyalty:
    - Points accumulation rules based on order value, products, or campaigns.
    - Redemption rules for using points as discounts or benefits.
    - Optional tiers (for example, Silver/Gold/Platinum) with associated perks.
  - Royalty (if applicable to business model):
    - Rules to allocate a portion of revenue to specific parties (for example, brands, affiliates) per order or per product.
- Integration points:
  - Hooks on order creation and updates to compute loyalty/royalty outcomes.
  - Data emitted to the Analytics Plane for reporting on loyalty performance and royalty liabilities.
- The loyalty/royalty component must be tenant-aware and, where applicable, store-aware, while maintaining clear boundaries and not leaking data across tenants.

---

## 8. Evolution and Extensions (Per Tenant)

The tenant e-commerce architecture must support future extensions to reach platform-level capabilities comparable to established solutions such as Magento or Shopify.

### 7.1 Advanced Catalog and Attribute Model

- Support a flexible product attribute system:
  - Attributes (for example, color, size, material) and groups/sets of attributes.
  - Reusable attribute definitions across multiple products.
- Support multiple product types at the domain level (where required):
  - Simple products.
  - Configurable products/variants.
  - Potential for bundles or virtual/downloadable products in later phases.
- Ensure the domain and persistence layers can represent these structures without forcing global uniqueness constraints across tenants.

### 7.2 Multi-Store per Tenant

- Allow a single tenant to operate multiple storefronts/websites under the same tenant account, for example:
  - Different brands.
  - Different regions or languages.
- Structurally, introduce the concept of **stores** or **storefronts** within the tenant domain model:
  - A tenant can have one or more stores.
  - Each store has its own configuration (domain, theme, locale, currency) within the tenant context.
  - Catalog and inventory can be shared or partially shared between stores, according to configuration.
- Routing and configuration resolution must consider both tenant and store identifiers.

### 7.3 Advanced Promotion Engine

- Extend the promotion capabilities to support rule-based engines:
  - Conditions on customer groups, order history, product attributes, and cart contents.
  - Separate catalog rules (price adjustments at product/category level) and cart rules (discounts at order level).
- Design promotion evaluation as a distinct domain component or module:
  - Clearly defined inputs (cart state, customer context, active promotions).
  - Deterministic outputs (discounts, messages, applied promotions).
- Ensure that the promotion engine integrates cleanly with the Tenant Application Service APIs and shared services (for example, analytics for promotion performance).

### 7.4 Extensibility and Integration Hooks

- Define extension points within the Tenant Application Service:
  - Hooks for pre-/post-processing of key flows (checkout, order placement, customer registration).
  - Event emission for external extensions to react to (for example, via webhooks or internal events).
- Ensure extensibility mechanisms do not break tenant isolation:
  - Extensions must operate within a single tenant’s context unless explicitly designed for cross-tenant operations.
  - Resource usage by extensions should be controlled to prevent negative impact on other tenants.

These extensions should be introduced as new modules or integrations within the Tenant Application Service and Shared Services, while preserving existing responsibilities, tenant isolation, and compatibility with the Control Plane and Analytics Plane.
