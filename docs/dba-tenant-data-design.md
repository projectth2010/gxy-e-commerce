# DBA – Tenant E-Commerce Data Design

## 1. Scope and Goals

This document focuses on the data design for the **Tenant E-Commerce** slice within the multi-tenant platform. It covers:

- Core tenant-level data model (products, orders, customers, etc.).
- Tenant context and isolation at the data layer.
- Indexing and performance considerations per tenant.
- How tenant-level data integrates with the platform-wide multi-tenant strategy.

It is complementary to the platform-wide multi-tenant database design.

---

## 2. Relationship to Platform Multi-Tenant Strategy

The platform-level DBA design defines the overall multi-tenant pattern (for example, shared DB with `tenant_id` vs database-per-tenant). This document assumes that choice is made and focuses on **tenant-facing domain entities**.

Two main patterns remain relevant when considering tenant e-commerce data:

- **Shared Schema, Tenant-Scoped Rows**
  - All tenants share central tables with a `tenant_id` column.
  - Tenant-level operations always filter and enforce `tenant_id`.

- **Separate Schemas/Databases per Tenant**
  - Each tenant has its own schema or database containing the same table structures.
  - Control Plane maintains mapping from `tenant_id` to connection/schema.

The same logical model applies in both cases.

---

## 3. Core Tenant Domain Model (Logical)

### 3.1 Catalog and Products

- **Product**
  - Represents a sellable item.
  - Key attributes (examples): name, description, status, base price, SEO data.
  - Relationships:
    - Belongs to a tenant.
    - May have variants and images.

- **ProductVariant** (optional, depending on complexity)
  - Represents a specific variant (size, color, etc.).
  - May carry its own SKU and pricing.

- **ProductImage**
  - References media resources (images hosted in a media service).

- **Category**
  - Represents a logical grouping of products.
  - Supports hierarchical relationships (parent/child categories) if required.

- **ProductCategory** (mapping table)
  - Many-to-many link between Product and Category.

### 3.2 Inventory and Pricing

- **Inventory**
  - Tracks stock quantity per product or variant per tenant.
  - May include reserved/available quantities.

- **Price / PriceList** (optional for advanced pricing)
  - Identifies base and promotional prices.
  - Supports time-bounded or channel-specific pricing where needed.

### 3.3 Orders and Payments

- **Order**
  - Represents a customer purchase.
  - Attributes (examples): order number, status, total amount, currency, timestamps.
  - Relationships:
    - Belongs to a tenant.
    - Belongs to a customer.

- **OrderItem**
  - Line items in an order.
  - References Product/Variant at the time of purchase (denormalized details).

- **OrderStatusHistory**
  - Tracks changes to order status over time.

- **Payment**
  - Records payment attempts and results.
  - Links to external payment transactions via identifiers.

- **Refund** (optional)
  - Captures partial or full refunds.

### 3.4 Customers and Addresses

- **Customer**
  - Represents an end user registered with a tenant store.
  - Attributes: name, contact details, authentication identifiers (referencing auth system), preferences.

- **CustomerAddress**
  - One-to-many addresses per customer.

- **CustomerActivity** (optional)
  - Tracks high-level activities (signups, logins, key actions) for analytics.

### 3.4.1 Customer Service Notes (Optional but Recommended)

- **CustomerServiceNote** (or similar, per tenant)
  - Captures internal notes made by tenant staff when handling customer inquiries.
  - May be linked to a `Customer`, an `Order`, or both, depending on context.
  - Key attributes (examples):
    - `tenant_id` (and optionally `store_id`).
    - Reference to `customer_id` and/or `order_id`.
    - Created-by staff identifier.
    - Note text and timestamps.
  - Supports building a simple case-style history without a full-blown ticketing system and can be expanded in future phases if needed.

### 3.5 Promotions (Optional Scope)

- **Coupon**
  - Defines discount codes and rules.

- **CouponRedemption**
  - Tracks coupon usage per order/customer.

- **PromotionCampaign**
  - Defines broader campaign-based offers.

---

## 4. Tenant Context and Isolation at the Data Layer

### 4.1 Tenant Identification in Tables

For shared-schema scenarios:

- All business tables that contain tenant-specific data must include a `tenant_id` column.
- Foreign keys between tables must ensure **tenant consistency**.
  - For example, `orders.tenant_id` and `customers.tenant_id` must match when linking orders to customers.

For per-tenant schemas/databases:

- The `tenant_id` is implicit in the schema/database selection.
- Tables do not necessarily require `tenant_id` as a column but may include it for explicitness and easier cross-tenant tooling.

### 4.2 Indexing and Performance

- Define indexes that support common query patterns:
  - By tenant, status, and time range (for example, orders per tenant per date range).
  - By SKU or product identifiers within tenant.
  - By customer identifiers within tenant.

- Composite indexes may include:
  - (`tenant_id`, `created_at`) for time series queries.
  - (`tenant_id`, `status`) for workflow/status filtering.

### 4.3 Constraints and Uniqueness

- Uniqueness should be scoped by tenant where relevant:
  - Product SKU unique per tenant, not globally.
  - Coupon codes may be unique per tenant.

- Consider composite unique keys:
  - (`tenant_id`, `sku`), (`tenant_id`, `coupon_code`).

---

## 5. Data Lifecycle and Retention (Per Tenant)

- **Order and payment data**
  - Retention rules may differ per tenant or per regulatory requirements.
  - Archival strategies should consider both tenant and time (for example, archive older orders while keeping recent ones hot).

- **Customer data**
  - Must support deletion or anonymization requests (for example, per privacy regulations).
  - Anonymization should be scoped by tenant and retain referential integrity where necessary.

- **Logs and events**
  - Typically moved to the Analytics Plane after initial retention in operational stores.

---

## 6. Integration with Analytics Plane

- Tenant e-commerce data acts as the **source** for analytics models:
  - Orders and payments feed revenue and order-related facts.
  - Products and categories feed catalog-related dimensions.
  - Customers feed customer dimensions and behavioral analytics.

- Each record sent to the Analytics Plane must include:
  - `tenant_id`.
  - Timestamps (event time and ingestion time).
  - Business keys and identifiers for joining with other fact/dimension tables.

This design ensures that analytical workloads can slice and aggregate data reliably per tenant and across tenants.

---

## 7. Advanced Catalog and Multi-Store Data Considerations

To support platform-level capabilities comparable to advanced e-commerce solutions, the tenant data model may need to support:

- A flexible product attribute system.
- Multiple product types.
- Multiple stores or storefronts per tenant.

### 7.1 Product Attributes and Attribute Sets

- **Attribute Definition**
  - Defines metadata for a reusable attribute (for example, name, type, allowed values, validation rules).
  - Scoped by tenant to allow different tenants to define different custom attributes.

  - Groups attributes into reusable sets for product types or categories.
  - Enables different product templates within a tenant (for example, clothing vs electronics).

- **Product-Attribute Values**
  - Store concrete values of attributes per product (or variant).
  - Designed to support efficient querying for common filters (for example, color, size, brand).

### 7.2 Product Types (Logical)

- Extend the model to support multiple product types:
  - Simple products (single SKU, no variation).
  - Configurable products/variants (parent/child relationships between products and variants).
  - Potential for bundles or virtual/downloadable products in future phases.

- Product type information can be captured via:
  - A `product_type` field.
  - Related tables to represent relationships (for example, parent product to child variants or bundle components).

### 7.3 Multi-Store per Tenant

- **Store / Storefront Entity**
  - Represents a logical store within a tenant (for example, brand site, region site, language variant).
  - Key attributes (examples): code, name, domain, locale, default currency, theme identifier.
  - Always associated with exactly one tenant.

- **Store-Scoped Configuration and Data**
  - Some data may be **tenant-wide** (shared across all stores), such as base product definitions and core inventory.
  - Other data may be **store-scoped**, such as:
    - Store-specific product visibility or pricing.
    - Store-specific content (descriptions, localized labels).
  - This can be represented using mapping tables, for example:
    - `StoreProduct` linking tenant products to specific stores with store-level overrides.

- **Tenant and Store Keys**
  - Where necessary, tables may include both `tenant_id` and `store_id` to:
    - Enforce that stores belong to the correct tenant.
    - Support queries scoped by tenant and store.
  - Unique constraints and indexes may include combinations such as (`tenant_id`, `store_id`, `sku`).

These advanced considerations should be introduced gradually, guided by product requirements and performance testing, but the logical model should leave room for them so that future extensions do not require disruptive redesign.

---

## 8. POS, External Orders, and Loyalty/Royalty Data

To support POS operations, ingestion of external orders, and loyalty/royalty programs, the data model should be extended with additional entities and fields.

### 8.1 Order Channels, POS, and Locations

- **Order Channel**
  - Each order should carry a `channel` or `source` field to distinguish between:
    - Online storefront orders.
    - POS (in-store) orders.
    - External/third-party channel orders.
  - Additional fields may include a `source_reference` identifier for external systems.

- **POS Location / Register (optional)**
  - Logical entities representing physical locations or registers for POS usage.
  - Orders created via POS can reference location/register identifiers to support reporting and reconciliation.

### 8.2 External/Third-Party Order References

- For orders originating from marketplaces or other external platforms, the model may include:
  - **ExternalOrderMapping** entity:
    - Links internal `order_id` to external order identifiers, marketplace IDs, or integration-specific references.
    - Stores metadata such as external channel name, import timestamps, and sync status.
  - This facilitates re-synchronization, reconciliation, and troubleshooting with third-party systems.

### 8.3 Loyalty Accounts and Transactions

- **LoyaltyAccount**
  - Represents the loyalty profile for a customer within a tenant (and possibly per store if needed).
  - Key fields (examples): `customer_id`, current points balance, tier level.

- **LoyaltyTransaction**
  - Records accrual and redemption of points.
  - Attributes (examples): type (accrual/redemption/adjustment), points amount, associated `order_id`, timestamps.
  - Enables full auditability of loyalty activities.

### 8.4 Royalty Allocation (Optional)

- **RoyaltyAllocation** (if business model requires)
  - Represents allocations of revenue or margin to specific parties (for example, brands, affiliates, licensors) per order or per order item.
  - Key fields (examples): `order_id` or `order_item_id`, beneficiary identifier, allocation type, amount or percentage, calculated amount, timestamps.
  - Supports downstream financial reporting and settlements.

These entities should be designed to respect tenant (and store) boundaries and integrate cleanly with the existing order and customer models, while emitting appropriate data to the Analytics Plane for reporting and optimization.

---

## 9. Operational Considerations

- **Backup and restore**
  - For shared-schema patterns: ensure that backups can be restored in ways that allow recovery of tenant-specific data when necessary.
  - For per-tenant schemas/DBs: backups can be tenant-granular.

- **Migration and schema changes**
  - Use migration tools and processes that can be applied consistently across all tenants.
  - For per-tenant databases, ensure migrations are automated and idempotent.

- **Monitoring**
  - Monitor query performance and table growth per tenant (or per shard/cluster), and plan partitioning or archiving strategies accordingly.
