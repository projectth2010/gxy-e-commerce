# DBA Checklists – Multi-Tenant Platform & Tenant E-Commerce

This document provides database readiness checklists for DBAs, covering platform-level multi-tenant design and tenant e-commerce data.

It links to detailed DBA design documents:

- `dba-multi-tenant-db-design.md`
- `dba-tenant-data-design.md`
- Phase/state references (for context):
  - Center Control phases **C1–C6** in `center-control-implementation-phases.md`.
  - Tenant E-Commerce states **T1–T6** in `tenant-implementation-phases.md`.

---

## 1. Platform Multi-Tenant DB Checklist

Use this to validate readiness of the platform-wide multi-tenant DB layer (primarily C1–C4).

### Strategy and Core Model

- [ ] **Multi-tenant strategy formally chosen and documented**  
      (Shared DB with `tenant_id` vs DB-per-tenant; see `dba-multi-tenant-db-design.md` §2.)
- [ ] **Tenant catalog schema defined**  
      (Mapping `tenant_id` → DB/schema/connection; see `dba-multi-tenant-db-design.md` §3.1 and §4.2.)
- [ ] **Tenant identity and lifecycle fields confirmed**  
      (`tenant_id`, status, plan, domains; see `dba-multi-tenant-db-design.md` §3.1.)

### Isolation and Constraints

- [ ] **Tenant isolation rules implemented in schema**  
      (tenant_id on all business tables or per-tenant DB; see `dba-multi-tenant-db-design.md` §4.)
- [ ] **Foreign keys enforce tenant consistency**  
      (Composite FKs including `tenant_id` where needed; see `dba-multi-tenant-db-design.md` §4.1.)
- [ ] **Uniqueness scoped per tenant**  
      (e.g., SKU, coupon, domain; see `dba-multi-tenant-db-design.md` §4.1 and `dba-tenant-data-design.md` §4.3.)

### Performance, Scaling, and Operations

- [ ] **Indexing strategy for tenant and time-based queries defined**  
      (e.g., `(tenant_id, created_at)`, status fields; see `dba-multi-tenant-db-design.md` §6.)
- [ ] **Partitioning/archiving strategy for large tables considered**  
      (Orders, logs, etc.; see `dba-multi-tenant-db-design.md` §6 and §8.)
- [ ] **Backup and restore plan documented**  
      (Including per-tenant restore strategy where applicable; see `dba-multi-tenant-db-design.md` §8.)
- [ ] **Schema migration approach and tooling agreed**  
      (Liquibase/Flyway/etc., with multi-tenant propagation; see `dba-multi-tenant-db-design.md` §9.)
- [ ] **Monitoring and alerting for DB health configured**  
      (Connections, slow queries, resource usage; see `dba-multi-tenant-db-design.md` §10.)

---

## 2. Tenant E-Commerce Data Checklist by Capability

Use this to validate readiness of tenant-facing data structures, aligned with tenant states T2–T6.

### Core Catalog, Orders, Customers (T2–T3)

- [ ] **Core product, category, and inventory tables designed**  
      (See `dba-tenant-data-design.md` §3.1–3.2.)
- [ ] **Order, OrderItem, Payment, Refund tables designed**  
      (See `dba-tenant-data-design.md` §3.3.)
- [ ] **Customer and CustomerAddress tables designed**  
      (See `dba-tenant-data-design.md` §3.4.)
- [ ] **Indexes for common tenant queries defined**  
      (By tenant, status, date, SKU, customer; see `dba-tenant-data-design.md` §4.2.)

### Promotions and Campaigns (T3+)

- [ ] **Coupon, CouponRedemption, PromotionCampaign tables designed**  
      (See `dba-tenant-data-design.md` §3.5.)
- [ ] **Tenant-scoped uniqueness for coupons defined**  
      (e.g., (`tenant_id`, `coupon_code`); see `dba-tenant-data-design.md` §4.3.)

### Advanced Catalog & Multi-Store (T4)

- [ ] **Product attribute and attribute set tables designed**  
      (Attribute definition, sets, product-attribute values; see `dba-tenant-data-design.md` §7.1.)
- [ ] **Product type handling modelled**  
      (Simple, configurable/variants, etc.; see `dba-tenant-data-design.md` §7.2.)
- [ ] **Store/Storefront entity and store-scoped mappings defined**  
      (Store, StoreProduct mappings; see `dba-tenant-data-design.md` §7.3.)
- [ ] **Composite keys/indexes including `tenant_id` + `store_id` reviewed**  
      (Uniqueness and performance; see `dba-tenant-data-design.md` §7.3.)

### Integrations, POS, and External Orders (T5)

- [ ] **Order channel/source fields defined**  
      (Distinguish online, POS, external; see `dba-tenant-data-design.md` §8.1.)
- [ ] **POS location/register entities defined (if in scope)**  
      (For in-store reporting; see `dba-tenant-data-design.md` §8.1.)
- [ ] **ExternalOrderMapping or equivalent tables designed**  
      (For linking internal orders to external references; see `dba-tenant-data-design.md` §8.2.)

### Loyalty and Royalty (T6)

- [ ] **LoyaltyAccount and LoyaltyTransaction tables designed**  
      (Accrual, redemption, auditability; see `dba-tenant-data-design.md` §8.3.)
- [ ] **RoyaltyAllocation tables designed (if business requires)**  
      (Order/item-level allocations; see `dba-tenant-data-design.md` §8.4.)
- [ ] **Indexes and retention policies for loyalty/royalty data defined**  
      (For reporting and cost control.)

---

## 3. Data Lifecycle, Retention, and Analytics

- [ ] **Per-tenant data retention rules defined for orders, payments, logs**  
      (See `dba-tenant-data-design.md` §5 and §9.)
- [ ] **Customer data deletion/anonymization flows supported**  
      (Per-tenant, privacy-driven; see `dba-tenant-data-design.md` §5.2.)
- [ ] **Analytics integration fields present on all key entities**  
      (`tenant_id`, timestamps, business keys; see `dba-tenant-data-design.md` §6.)

---

## 4. How to Use This Document

- **During design and schema reviews:** use as a checklist to ensure nothing critical is missed for the targeted phase/state.
- **Before enabling new tenant capabilities (T4–T6):** confirm all related database checklist items are satisfied.
- **For audits or platform readiness reviews:** keep this document alongside SA checklists (`sa-checklists.md`) to provide a full view across architecture and data.

### 4.1 Usage in the delivery process

- **Phase planning:**
  - Align DBA work with Center/Tenant phases (`center-control-implementation-phases.md`, `tenant-implementation-phases.md`) and the mapping in `platform-phase-mapping.md`.
  - Identify which DB changes are required for each planned phase.

- **Implementation:**
  - Use this checklist with `implementation-tech-stack-laravel-vue.md` to ensure Laravel migrations and Eloquent models accurately reflect the multi-tenant and tenant data design.

- **Change management and go-live:**
  - Before enabling new features or states, verify that required schema, indexing, and operational items are complete and tested.
  - Capture any outstanding items as explicit risks or follow-up tasks.
