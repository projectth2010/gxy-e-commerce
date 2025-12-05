# System Structure and Planes – Center Control Multi-Tenant Platform

This document describes the **structural architecture** of the multi-tenant e-commerce platform, focusing on:

- System planes (control, data, analytics).
- Service and layer structure (logical, not tied to specific technologies).
- Protocol structure between planes.
- Tenant metadata and routing structure.
- Infrastructure topology and scaling paths.

It complements the main SA Architecture document by concentrating on **structure and infra-level protocols**, independent of concrete implementation stacks.

---

## 1. System Planes Overview

The platform is organized into three primary planes:

- **Control Plane**
  - Center Control and related services.
  - Responsible for governance, configuration, lifecycle management, and platform-wide operations.

- **Data Plane**
  - Tenant-facing application services and shared domain services.
  - Responsible for serving end-user traffic (storefronts, backoffices) and executing business logic.

- **Analytics Plane**
  - Data ingestion, processing, storage, and analytics/AI services.
  - Responsible for reporting, insights, and intelligent features.

Clear separation between these planes enables independent scaling, failure isolation, and evolution.

---

## 2. Service and Layer Structure (Logical)

### 2.1 Control Plane

The Control Plane contains services that manage tenants and overall platform behavior:

- **Center–Tenant Management Service**
  - Manages tenant metadata and lifecycle.
  - Source of truth for tenant identity, lifecycle state, and routing metadata (region, shard, cluster).

- **Plan & Feature Management Service**
  - Defines plans and feature sets.
  - Assigns plans/features to tenants.
  - Exposes effective feature sets to the Data Plane.

- **Platform Monitoring & Operations Service**
  - Aggregates health and operational data across tenants.
  - Provides operational controls (suspend/resume tenant, read-only mode, configuration resync).

- **(Optional) Billing & Subscription Management Service**
  - Manages tenant subscriptions and usage records.
  - Coordinates with external billing/finance systems.

### 2.2 Data Plane

The Data Plane contains services that directly process business traffic and end-user interactions:

- **Tenant Application Services**
  - Storefront and backoffice logic, scoped per tenant context.
  - Implement e-commerce capabilities (products, orders, customers, promotions, etc.).

- **Shared Domain Services**
  - Cross-tenant or reusable capabilities, such as:
    - Payment processing.
    - Notification handling (email/SMS/push).
    - Media and file handling.
    - Other shared integrations.

Each service in the Data Plane is **tenant-aware**, meaning it understands and enforces tenant boundaries in all operations.

### 2.3 Analytics Plane

The Analytics Plane focuses on data collection, processing, and insight generation:

- **Ingestion Layer**
  - Receives events and logs from Data Plane and Control Plane.

- **Processing/ETL/ELT Layer**
  - Cleanses, normalizes, and aggregates data.
  - Builds analytical models (facts and dimensions).

- **Analytics and AI Services**
  - Provide read-only analytics APIs and AI/ML-driven insights.
  - Serve recommendations, anomaly detection results, forecasts, and other insights to Center Control and, where appropriate, to tenants.

---

## 3. Protocol Structure Between Planes

The platform uses a combination of **synchronous internal APIs** and **asynchronous events**. The following sections describe the structural patterns (not bound to specific protocols or products).

### 3.1 Common Metadata for Internal Calls

All internal calls between services should carry a consistent set of metadata, such as:

- **Service identity**: identifies the calling service/system.
- **Tenant context** (when applicable): tenant identifier(s).
- **Trace/Correlation ID**: used for end-to-end observability.
- **Timestamp** and basic request metadata.

This metadata is typically carried in headers or a dedicated envelope around the payload.

### 3.2 Control Plane ↔ Data Plane

**Direction and purpose:**

- **Control → Data** (command):
  - Manage tenant lifecycle (create/suspend/terminate).
  - Update tenant configuration.
  - Distribute effective feature sets to tenant services.

- **Data → Control** (query/notification):
  - Provide aggregated statistics to Center Control upon request.
  - Notify Center Control of important events, where needed.

**Patterns:**

- Command-style operations:
  - `CreateTenant`, `ChangeTenantStatus`, `UpdateTenantConfig`.
- Query-style operations:
  - `GetTenantFeatures`, `GetTenantStats`.

Each operation is framed as a well-defined internal API contract, with:

- Clear input (command/query) and output (result/acknowledgment).
- Tenant context explicitly included where relevant.

### 3.3 Data Plane ↔ Shared Domain Services

**Direction and purpose:**

- Tenant Application Services call Shared Services to perform reusable operations, such as payment processing or sending notifications.

**Patterns:**

- Synchronous calls for critical path operations (for example, payment authorization, order placement).
- Asynchronous event-driven patterns for non-critical or background tasks (for example, notification delivery, audit logging).

**Constraints:**

- Shared services should be **tenant-aware** but remain agnostic to detailed business workflows of each tenant.
- All calls and events carry `tenant_id` and minimal required business keys.

### 3.4 Control Plane ↔ Analytics Plane

**Direction and purpose:**

- Data Plane and Control Plane emit events and logs into the Analytics Plane.
- Control Plane consumes aggregated analytics and AI insights via read-only interfaces.

**Patterns:**

- **Ingestion**:
  - Event or log streams from Tenant Application Services, Center Control, and Shared Services.
- **Access**:
  - Read-only Analytics APIs for dashboards and decision support in Center Control.

The Analytics Plane does not modify operational state directly; it provides insights that Center Control and tenants may act upon.

---

## 4. Tenant Metadata and Routing Structure

Center Control maintains a **tenant catalog** that is the system of record for all tenant-related metadata. This catalog supports routing, governance, and lifecycle management.

### 4.1 Tenant Catalog – Core Attributes

The tenant catalog stores, at minimum, the following groups of attributes per tenant:

- **Identity**
  - Internal `tenant_id`.
  - Tenant codes, display names.

- **Lifecycle**
  - Current state (for example, draft, provisioning, active, suspended, terminated).
  - Creation and last updated timestamps.

- **Plan and Features**
  - Assigned plan identifier.
  - Effective feature set or feature plan binding.

- **Routing and Placement**
  - Region or locality (for example, logical region name).
  - Data-plane cluster or shard identifier.
  - Optional flags indicating special isolation requirements (for example, dedicated cluster).

- **Domains**
  - Primary domain or subdomain.
  - Additional/custom domains.

### 4.2 Routing Decisions

Control Plane uses the tenant catalog to drive routing and placement decisions, such as:

- Determining which Data Plane cluster or region should serve a given tenant.
- Determining where to send lifecycle and configuration commands.
- Providing routing metadata to external or internal routing components.

At a structural level:

- Requests that include a tenant context are mapped from domain or identifier → `tenant_id` → (region, cluster/shard).
- This mapping is used consistently by routing components and services.

---

## 5. Infrastructure Topology (Logical)

This section describes a logical view of the infrastructure layout. It does not assume any specific technology.

### 5.1 Edge and Routing Layer

- Terminates external client traffic (Center UI, tenant storefronts, tenant backoffices).
- Performs initial routing:
  - Requests for Center UI and Center APIs → Control Plane.
  - Requests for tenant storefront/backoffice → appropriate Data Plane cluster.
- May perform domain-to-tenant resolution using the tenant catalog (directly or via a dedicated lookup component).

### 5.2 Control Plane Clusters

- One or more logical clusters dedicated to Control Plane workloads.
- Host:
  - Center–Tenant Management.
  - Plan & Feature Management.
  - Platform Monitoring & Operations.
  - (Optional) Billing/Subscription Management.

Characteristics:

- Scaled for admin/API traffic and internal coordination, not for heavy end-user traffic.
- Isolated failure domain from Data Plane where possible.

### 5.3 Data Plane Clusters

- One or more clusters hosting Tenant Application Services and Shared Domain Services.
- Clusters may be:
  - Single shared cluster for all tenants (initial phase).
  - Multiple clusters/shards grouped by region, plan tier, or other criteria (growth phase).

Routing and placement use the tenant catalog to map `tenant_id` to cluster/shard/region.

### 5.4 Analytics and Data Platform

- Dedicated infrastructure for:
  - Event ingestion and stream processing.
  - Batch ETL/ELT jobs.
  - Analytical storage (warehouse/lakehouse).
  - Analytics and AI services.

This platform is logically separated from operational data stores to maintain performance and scalability for both OLTP and OLAP workloads.

---

## 6. Scaling and Evolution Scenarios

The structural design must support evolution from a smaller deployment to a larger, more complex one without fundamental redesign.

### 6.1 Single Cluster to Multi-Shard

Initial phase:

- A single Data Plane cluster serves all tenants.
- Tenant catalog still tracks routing attributes but most tenants map to the same cluster.

Growth phase:

- Additional clusters (shards) are introduced.
- Tenants are assigned to shards based on region, plan tier, or other policies.
- Tenant catalog updated with shard/cluster identifiers.
- Routing layer uses updated metadata to direct traffic and control commands.

### 6.2 Single Region to Multi-Region

Initial phase:

- All components run in a single region.

Growth phase:

- Data Plane clusters are deployed in multiple regions.
- Tenant catalog extended with region attributes.
- Center Control may remain:
  - Single-region global control plane, or
  - Multi-region control plane instances with coordinated state.

Considerations at the structural level:

- Region-aware routing for tenant traffic.
- Data residency policies encoded in tenant metadata.
- Cross-region coordination between Control Plane instances where applicable.

### 6.3 Analytics and AI Maturity

Initial phase:

- Basic event collection and simple aggregations for dashboards.

Growth phase:

- More comprehensive event schemas and dimensional models.
- Introduction of AI services consuming the analytics data platform.
- Center Control and tenant services integrate with these AI services for recommendations, anomaly detection, and forecasting.

---

This document provides a structural blueprint for how the platform is organized into planes, services, protocols, and infrastructure topology, without binding to specific technologies. It should be used together with the main SA Architecture document when designing and evolving the system.
