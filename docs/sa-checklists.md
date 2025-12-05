# SA Checklists – Center Control & Tenant E-Commerce

This document provides architecture readiness checklists for Solution / System Architects (SA), structured around platform phases and tenant states.

It links to detailed design documents:

- `sa-architecture.md`
- `sa-system-structure-and-planes.md`
- `sa-tenant-architecture.md`
- Phase/state references:
  - Center Control phases **C1–C6** in `center-control-implementation-phases.md`.
  - Tenant E-Commerce states **T1–T6** in `tenant-implementation-phases.md`.

---

## 1. Center Control – Architecture Readiness by Phase (C1–C6)

Use this section as a checklist to validate that Center Control (control plane) is ready for each phase.

### C1 – Platform Foundations – Multi-Tenant Ready

- [ ] **Tenant master model defined**  
      (See `sa-architecture.md` §3 and `sa-system-structure-and-planes.md` §4 – tenant catalog & metadata.)
- [ ] **Tenant resolution strategy decided and documented**  
      (Domain/subdomain vs header-based; see `sa-architecture.md` §3.1.)
- [ ] **Control/Data/Analytics planes separated at logical level**  
      (See `sa-system-structure-and-planes.md` §1–2.)
- [ ] **Baseline environments and CI/CD defined**  
      (Dev/Staging/Prod, pipelines; see `sa-architecture.md` §7.)

### C2 – Tenant Lifecycle & Basic Governance

- [ ] **Center–Tenant Management service responsibilities defined**  
      (See `sa-system-structure-and-planes.md` §2.1.)
- [ ] **Tenant lifecycle states and transitions modelled**  
      (Draft, provisioning, active, suspended, terminated; see `sa-architecture.md` §11.1.)
- [ ] **Internal APIs/commands for tenant lifecycle documented**  
      (Create/update/status change; see `sa-architecture.md` §9.1 and `sa-system-structure-and-planes.md` §3.2.)
- [ ] **Tenant configuration propagation pattern agreed**  
      (Push, pull, or mixed; see `sa-architecture.md` §9.1.)

### C3 – Plan/Package, Feature Toggles & Billing Readiness

- [ ] **Plan & Feature Management service defined**  
      (See `sa-system-structure-and-planes.md` §2.1.)
- [ ] **Plan/feature protocol between Center and Tenant documented**  
      (Pull/push of effective features; see `sa-architecture.md` §9.2.)
- [ ] **Feature enforcement responsibility clearly assigned to tenant layer**  
      (See `sa-tenant-architecture.md` §3.2.)
- [ ] **Usage/entitlement metrics required for billing identified**  
      (Number of orders, stores, MAU, etc.; see `center-control-implementation-phases.md` C3.)

### C4 – Cross-Tenant Monitoring, Audit & Compliance

- [ ] **Platform Monitoring & Operations service defined**  
      (See `sa-system-structure-and-planes.md` §2.1.)
- [ ] **Tenant-level metrics and logs tagged with tenant context**  
      (See `sa-tenant-architecture.md` §6.3.)
- [ ] **Audit logging responsibilities documented**  
      (For tenant lifecycle, plan changes, feature toggles; see `sa-architecture.md` §11.2.)
- [ ] **Operational controls (suspend/read-only/resync) described**  
      (See `sa-architecture.md` §11.3.)

### C5 – Ecosystem Integrations & Marketplace Management

- [ ] **Shared integration services identified and scoped**  
      (Payment, notification, media, external connectors; see `sa-architecture.md` §5–6.)
- [ ] **Center-level integration catalog and activation flow outlined**  
      (See `center-control-implementation-phases.md` C5.)
- [ ] **Tenant–integration boundaries and responsibilities defined**  
      (Which data moves via shared services vs tenant adapters.)

### C6 – Data, Analytics & AI-Driven Operations

- [ ] **Analytics Plane structure and data flows documented**  
      (See `sa-architecture.md` §12 and `sa-system-structure-and-planes.md` §2.3, §3.4.)
- [ ] **Key per-tenant and platform-wide KPIs listed**  
      (See `sa-architecture.md` §12.3.)
- [ ] **Analytics/AI services and APIs identified**  
      (Read-only Analytics APIs, AI recommendation/alert endpoints; see `sa-architecture.md` §12.4.)
- [ ] **Governance and data-protection requirements captured**  
      (Tenant isolation in analytics, masking/anonymization; see `sa-architecture.md` §12.5.)

---

## 2. Tenant E-Commerce – Architecture Readiness by State (T1–T6)

Use this section to validate that tenant-facing architecture is ready for each tenant state.

### T1 – Tenant Not Onboarded

- [ ] **Tenant context resolution path to Tenant Application defined**  
      (Even before tenants exist, the path domain → tenant_id → routing is clear; see `sa-architecture.md` §3.1 and `sa-system-structure-and-planes.md` §4.)
- [ ] **Tenant Application Service ready to accept provisioning commands**  
      (Create tenant, apply initial configuration; see `sa-tenant-architecture.md` §3.1.)

### T2 – Tenant Onboarded – Store Not Live

- [ ] **Backoffice entry points and auth flows defined**  
      (Tenant admin login and basic store configuration flows; see `sa-tenant-architecture.md` §2.1, §3.)
- [ ] **Minimal catalog/order capabilities for internal testing designed**  
      (Simple products, basic inventory, non-public checkout; see `sa-tenant-architecture.md` §2–4.)
- [ ] **Integration points with Payment/Notification services specified**  
      (For test orders; see `sa-tenant-architecture.md` §4.1–4.2.)

### T3 – Tenant Live – Core E-Commerce (MVP)

- [ ] **Full storefront & backoffice flow mapped end-to-end**  
      (Browse → cart → checkout → payment → fulfillment; see `sa-tenant-architecture.md` §2–4.)
- [ ] **Order lifecycle and state transitions defined**  
      (Pending → paid → shipped → completed/cancelled; see `sa-tenant-architecture.md` domain sections.)
- [ ] **Tenant-level observability requirements defined**  
      (Metrics/logs tagged with tenant_id; see `sa-tenant-architecture.md` §6.3.)

### T4 – Advanced Catalog & Multi-Store

- [ ] **Advanced catalog domain model designed**  
      (Attributes, attribute sets, multiple product types; see `sa-tenant-architecture.md` §8.1 and `dba-tenant-data-design.md` §7.1–7.2.)
- [ ] **Store/Storefront concept introduced in architecture**  
      (Per-tenant multiple stores; routing includes tenant + store; see `sa-tenant-architecture.md` §8.2.)
- [ ] **Configuration and routing for multi-store clearly documented**  
      (Domain → tenant → store mapping; see `sa-system-structure-and-planes.md` §4.)

### T5 – Integrations & POS

- [ ] **Tenant POS channel defined in architecture**  
      (POS UI as client of Tenant Application, unified order/inventory model; see `sa-tenant-architecture.md` §7.1.)
- [ ] **Order channel/source concept designed**  
      (Online vs POS vs external, with flags for analytics and reporting; see `sa-tenant-architecture.md` §7.2 and `dba-tenant-data-design.md` §8.1.)
- [ ] **External/third-party order ingestion pattern defined**  
      (Adapter layer, mapping into internal order model; see `sa-tenant-architecture.md` §7.2.)

### T6 – Loyalty/Royalty & AI-Driven

- [ ] **Loyalty domain component designed**  
      (Loyalty accounts, accrual/redemption hooks; see `sa-tenant-architecture.md` §7.3 and `dba-tenant-data-design.md` §8.3.)
- [ ] **Optional royalty model integration points defined**  
      (Revenue allocation at order/item level; see `sa-tenant-architecture.md` §7.3 and `dba-tenant-data-design.md` §8.4.)
- [ ] **Events and data flows to Analytics Plane for loyalty/royalty and AI use cases documented**  
      (See `sa-architecture.md` §12.1–12.4.)

---

## 3. How to Use This Document

- **During design:** use as a guide to ensure architectural concerns for each phase/state are covered in detailed designs.
- **Before enabling a phase/state:** quickly tick through the relevant checklist (C1–C6, T1–T6) to validate readiness.
- **For reviews:** reference back to the detailed SA documents when discussing any unchecked item.

### 3.1 Usage in the delivery process

- **Planning:**
  - Combine this document with the phase descriptions in `center-control-implementation-phases.md`, `tenant-implementation-phases.md`, and the mapping in `platform-phase-mapping.md` to decide which capabilities to implement in each release.

- **Implementation:**
  - Use together with `implementation-tech-stack-laravel-vue.md` to translate checklist items (C1–C6, T1–T6) into concrete Laravel/Vue modules and tasks.

- **Readiness reviews / go-live:**
  - For a given phase/state, walk through the relevant checklist sections and confirm all items are satisfied or explicitly deferred.
  - Record any gaps as follow-up actions or technical debt.
