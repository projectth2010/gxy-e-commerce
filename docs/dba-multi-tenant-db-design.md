# DBA – Multi-Tenant Database Design

## 1. Database Strategy Overview

This document describes the database design for the Center Control + Tenant E-Commerce platform from a DBA perspective, focusing on multi-tenant data isolation, performance, and maintainability.

The platform is **multi-tenant**, meaning that multiple tenants (stores) share the same platform. The database layer must enforce strong logical separation of data and support efficient operations at scale.

> Note: This template assumes we will choose one primary multi-tenant strategy. The final decision should be explicitly documented in this section.

## 2. Multi-Tenant Patterns

There are two main multi-tenant database patterns under consideration:

### 2.1 Shared Database, Shared Schema (with `tenant_id`)
- All tenants share a single database and a common schema.
- Each business table includes a `tenant_id` column.
- Every query must filter by `tenant_id`.
- Pros:
  - Simpler to manage operationally (single DB).
  - Easier to implement cross-tenant reporting (if permitted).
- Cons:
  - Requires strict application discipline and constraints to prevent data leakage.
  - Very large tables may appear over time; partitioning strategies may be required.

### 2.2 Database-per-Tenant
- Each tenant has its own schema or database instance.
- A central catalog (in the Center Control DB) stores mapping from `tenant_id` to connection properties.
- Application selects the appropriate DB connection at runtime.
- Pros:
  - Stronger isolation between tenants.
  - Easier to perform per-tenant backup/restore and data operations.
- Cons:
  - Higher operational overhead (many DBs).
  - Cross-tenant reporting becomes more complex.

> **Final Choice**: [To be confirmed – choose one and provide justification here.]

## 3. Logical Data Model

### 3.1 Core Entities

High-level entities (simplified list):

- **Platform & Tenant Management**
  - `Tenant`: represents a store; includes name, status, plan, domain(s).
  - `TenantConfig`: configuration values per tenant (theme, features, payment/shipping options).
  - `Plan` / `Subscription` (if billing is in scope): defines plan limits and pricing.

- **Identity & Access Management**
  - `User`: platform and tenant users (or separate tables per context if preferred).
  - `Role`: roles such as SuperAdmin, TenantAdmin, Staff, Customer.
  - `Permission`: granular rights (optional, if RBAC needs more detail).
  - `UserRole` / `RolePermission` mapping tables.

- **E-Commerce Domain**
  - `Product`, `ProductVariant`, `ProductImage`.
  - `Category`, `ProductCategory` (many-to-many mapping).
  - `Inventory` (stock per product/variant per tenant).
  - `Order`, `OrderItem`, `OrderStatusHistory`.
  - `Payment`, `Refund`.
  - `Shipment`, `ShipmentTracking`.

- **Customer & Profile**
  - `Customer`.
  - `CustomerAddress`.
  - `CustomerActivity` (optional; for analytics/profile).

- **Promotions (if in scope)**
  - `Coupon`, `CouponRedemption`.
  - `PromotionCampaign`.

### 3.2 Relationships (Conceptual)

- One `Tenant` has many `Products`, `Categories`, `Orders`, `Customers`, etc.
- One `Order` belongs to one `Customer` (and one `Tenant`).
- One `Order` has many `OrderItems`.
- One `Product` can belong to many `Categories`.

## 4. Tenant Isolation Rules

### 4.1 Shared DB with `tenant_id` (if chosen)

- All business tables must include a `tenant_id` column.
- Foreign keys must reference both the primary key and `tenant_id` when applicable to prevent cross-tenant foreign key relationships.
- Application-level ORMs or query builders must always include `tenant_id` filters.
- Database-level constraints and indexes:
  - Composite indexes on `(tenant_id, <business_key>)` for frequent queries.
  - Unique constraints scoped by `tenant_id` where appropriate (e.g., product SKU unique per tenant).

### 4.2 DB-per-Tenant (if chosen)

- Each tenant's schema is logically identical.
- A central `TenantCatalog` or similar table stores:
  - `tenant_id`, DB host, DB name, credentials (or reference to secret).
- Connection pooling strategy:
  - Pooled per database with upper limits to avoid exhaustion.

## 5. Schema & Naming Conventions

- **Database Name**: `[env]_[platform]_[purpose]` (e.g., `prod_platform_core`).
- **Tenant DB Name (if db-per-tenant)**: `[env]_[platform]_tenant_[tenantid/hash]`.
- **Table Names**: snake_case, plural or consistent convention (e.g., `products`, `orders`, `tenants`).
- **Column Names**: snake_case, clear and descriptive (e.g., `created_at`, `updated_at`).
- **Primary Keys**: surrogate keys (e.g., `id` as INT/BIGINT or UUID), with tenant-level considerations.
- **Timestamps**: use consistent timezone (e.g., UTC) and naming (`created_at`, `updated_at`).

## 6. Performance & Scaling

- Index frequently used query columns, especially:
  - `tenant_id`.
  - Status fields (e.g., `order_status`).
  - Date ranges (e.g., `created_at` for order searches).
- Consider table partitioning strategies if table size grows large:
  - Time-based partitioning for `orders`, `payments`, `logs`.
  - Tenant-based partitioning where supported and beneficial.
- Periodic maintenance:
  - Index maintenance (rebuild/reorganize where applicable).
  - Statistics update.

## 7. Security & Access Control

- Separate DB users for application, reporting, and admin operations.
- Minimize privileges for application DB users (principle of least privilege).
- Secure storage for DB credentials (not in source code).
- Audit tables or logging for critical changes (e.g., price changes, order status changes, tenant configuration changes).

## 8. Backup, Restore, and Retention

- Regular full and incremental backups for all production databases.
- Tested restore procedures and RPO/RTO definitions.
- If using DB-per-tenant:
  - Ability to restore a single tenant DB without impacting others.
- Data retention policies (e.g., anonymization or deletion of old records) to comply with regulations.

## 9. Migration & Change Management

- Use a schema migration tool (e.g., Liquibase, Flyway, or framework-native migrations).
- Version control for migration scripts.
- Roll-forward and rollback strategies:
  - Backward-compatible changes where possible.
  - Clear process for handling failed migrations.
- For DB-per-tenant:
  - Automated propagation of schema changes to all tenant databases.

## 10. Monitoring

- Monitor:
  - Connection usage and pool health.
  - Query performance (slow query logs).
  - Disk usage, CPU, memory.
- Alerts on thresholds and anomalies.

## 11. Open Points / To Be Defined

- Final selection between shared DB vs DB-per-tenant.
- Exact indexing strategy based on real query patterns.
- Regulatory or compliance requirements (if any) that may affect data retention or encryption.
