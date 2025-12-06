# SA Architecture – Center Control + Tenant E-Commerce Platform

## 1. Architecture Overview

This document describes the high-level architecture for the Center Control + Tenant E-Commerce platform from a Solution/System Architect (SA) perspective.

The platform is designed as a **multi-tenant web application** consisting of:
- A **Center Control (Central Management)** application.
- **Tenant Applications** (storefront + backoffice) for each tenant.
- A set of **Shared Services** (auth, payments, notifications, file storage, etc.).

The exact technology stack (language/framework) can be adapted based on implementation decisions, but the architectural concepts below remain similar.

## 2. Application Architecture

### 2.1 Logical Components

- **API Gateway / Edge Layer**
  - Entry point for all external HTTP(S) requests.
  - Responsible for routing, basic security (rate limiting, WAF where applicable), and tenant resolution.

- **Center Control Service**
  - Provides APIs and UI for platform super admins.
  - Manages tenant lifecycle (create, update, disable).
  - Manages global configuration, plans, and feature toggle definitions.
  - Provides cross-tenant reporting and monitoring views.

- **Tenant Application Service**
  - Provides APIs and UI for tenant storefront and backoffice.
  - Handles e-commerce domain logic (products, orders, customers, promotions, etc.).
  - Enforces tenant isolation in all operations.

- **Shared Services**
  - **Auth Service**: Authentication, authorization, token issuing, role & permission management.
  - **Payment Integration Service**: Integrations with external payment gateways.
  - **Notification Service**: Sending emails/SMS/other notifications.
  - **File/Media Service**: Handling uploads, storage, and retrieval of media assets.

Depending on implementation size and complexity, these components may be deployed as:
- A modular monolith with clear bounded contexts, or
- A set of independent services (microservices) with clear boundaries and contracts.

### 2.2 Frontend Architecture

The frontend is not a single monolithic application. Instead, it is composed of three distinct, standalone Single-Page Applications (SPAs):

- **Center Control UI**: A dedicated web application for platform operators to manage tenants, plans, and global settings.
- **Tenant Backoffice UI**: A dedicated web application for store owners and staff to manage their specific store (products, orders, etc.).
- **Tenant Storefront UI**: The public-facing e-commerce shop for each tenant's customers.

This multi-project structure is a deliberate choice to support an enterprise model and provides several key advantages:

1.  **Independent Development and Deployment**: Each frontend application is a separate project with its own codebase and dependencies. This allows different teams to work on them in parallel and, most importantly, enables them to be deployed independently. A change in the Tenant Storefront does not require a redeployment of the Center Control, increasing agility and reducing risk.

2.  **Clear Boundaries and Ownership**: This separation creates clear lines of code ownership and aligns the frontend architecture with the backend's "Control Plane" (Center Control) and "Data Plane" (Tenant Applications) concept.

3.  **Scalability and Maintainability**: As the platform grows, managing three smaller, focused applications is significantly more scalable and maintainable than managing one large, monolithic frontend codebase.

4.  **Technological Flexibility**: While initially developed with a consistent stack (Vue.js), this architecture allows for future flexibility. Different applications could potentially be rewritten or developed using different frontend technologies if business needs evolve, as long as they adhere to the same backend API contract.

All UIs are self-contained and only interact with the backend via the centralized, well-defined API layer, sharing a consistent identity and authorization model.

## 3. Multi-Tenant Strategy

### 3.1 Tenant Resolution

Each incoming request must be mapped to a tenant (when tenant-specific). Typical strategies:
- **Subdomain-based**: `tenantA.example.com`, `tenantB.example.com`.
- **Custom domain mapping** per tenant, configured in Center Control.
- **Header-based key** for internal/admin APIs.

The chosen approach should be:
- Implemented at the edge layer (e.g., middleware in API gateway or web server).
- Exposed to downstream services as a `tenant_id` (normalized internal identifier).

### 3.2 Data Isolation

Two main strategies (one must be selected and enforced consistently):

1. **Shared Database, Shared Schema (with `tenant_id`)**
   - Single database for all tenants.
   - Each business table includes a `tenant_id` column used in all queries.
   - Application-layer guarantees that no cross-tenant queries are allowed.

2. **Database-per-Tenant**
   - Each tenant has its own schema or database instance.
   - A central configuration store maps `tenant_id` to connection information.
   - Application selects the correct DB connection on each request.

The detailed decision and design are provided in the DBA document.

### 3.3 Configuration Management

- **Global Configuration** (platform-level): payment providers, shipping providers, feature definitions.
- **Tenant Configuration**: theme, enabled features, payment/shipping options, store settings.

Configuration is:
- Stored centrally (e.g., configuration tables in the Center Control database).
- Cached in memory or distributed cache for performance.
- Refreshed on changes via events or short TTL.

## 4. Technology Stack (Template)

> Note: This section should be concretized once the implementation stack is finalized.

- **Backend**: [e.g. Node.js + NestJS / .NET + ASP.NET Core / Java + Spring Boot].
- **Frontend**: [e.g. React/Next.js, Vue/Nuxt, Angular] for Center, Backoffice, and Storefront UIs.
- **Database**: [e.g. PostgreSQL / MySQL] for relational data.
- **Cache**: [e.g. Redis] for sessions, caching configuration, and hot data.
- **Message Broker** (optional): [e.g. RabbitMQ, Kafka] for asynchronous tasks (notifications, background jobs).
- **Object Storage**: [e.g. S3-compatible] for media assets.

## 5. Integration Architecture

### 5.1 Payment Gateways
- Integrations abstracted behind a Payment Integration Service or module.
- Support for multiple providers (e.g., Provider A, Provider B), configurable per tenant.
- Common payment flow:
  - Create payment intent / charge.
  - Redirect or capture payment details.
  - Handle asynchronous callbacks/webhooks.
  - Update order status.

### 5.2 Notification Services
- Email and SMS providers integrated through a Notification Service.
- Supports templates and localization per tenant where applicable.
- Can be triggered synchronously (critical) or asynchronously (via queue) depending on use case.

### 5.3 External APIs
- Any additional external dependencies are abstracted behind clear interfaces.
- Timeouts, retries, and circuit breakers must be applied to external calls.

## 6. Cross-Cutting Concerns

### 6.1 Authentication & Authorization
- Unified identity model for:
  - Platform super admins.
  - Tenant admins/staff.
  - Customers.
- Use token-based authentication (e.g. JWT or opaque tokens).
- Role-based and, where needed, permission-based authorization.
- Tenant context must be part of the auth context for tenant-specific users.

### 6.2 Logging & Observability
- Centralized structured logging for all services.
- Correlation IDs for tracing requests across services.
- Metrics for:
  - Request rates, latencies, and error rates.
  - Resource usage and capacity.
- Dashboards and alerts for critical metrics.

### 6.3 Error Handling & Resilience
- Standardized error response format for APIs.
- Graceful degradation where possible (e.g. fallback behavior for non-critical features).
- Resilience patterns (retry, backoff, circuit breaker) for external dependencies.

### 6.4 Configuration & Secrets Management
- Environment-specific configurations.
- Secrets stored securely (e.g., secret manager, environment variables with restricted access).

## 7. Deployment & Environments

### 7.1 Environments
- **Development**: rapid iteration, feature branches.
- **Staging/UAT**: integration testing and user acceptance testing.
- **Production**: live tenant stores.

### 7.2 Deployment Model
- Container-based deployment recommended (e.g., Docker + orchestrator).
- Zero-downtime deployment strategy where possible (rolling updates, blue-green).

### 7.3 CI/CD
- Automated build, test, and deployment pipelines.
- Static checks (lint, security scanning) and automated tests.
- Controlled promotion of builds from dev → staging → production.

## 8. Service-to-Service API Communication

This section describes how internal systems (Center Control, Tenant Application Service, and Shared Services) communicate with each other using HTTPS APIs secured by API keys and private keys.

### 8.1 Protocol and Transport

- All service-to-service communication must use **HTTPS** with TLS enabled.
- APIs are typically REST-style HTTP APIs (JSON payloads). Other API styles (e.g., GraphQL) must still follow the same security model.

### 8.2 API Key and Private Key Model

- Each system or service that calls internal APIs is assigned an **API key** and a corresponding **private/secret key**.
- The API key is a public identifier and is included in each request.
- The private key is never transmitted over the network and is used only on the client and server side to compute and verify request signatures.
- API keys and private keys are stored securely (e.g., secret manager, environment variables with restricted access) and can be rotated when needed.

### 8.3 Request Signing (HMAC)

To protect against tampering and replay attacks, each request includes an HMAC-based signature over the request body and timestamp.

Standard headers:

- `x-api-key`: public API key that identifies the calling system.
- `x-timestamp`: Unix timestamp (seconds or milliseconds) indicating when the request was created.
- `x-signature`: HMAC-SHA256 of the canonical payload and timestamp using the private key corresponding to `x-api-key`.

Example signature input (conceptual):

- `HMAC_SHA256( body + "|" + timestamp, private_key )`

The exact canonicalization rules for the payload (e.g., sorted JSON, raw body) should be defined and implemented consistently in client and server libraries.

### 8.4 Validation Flow (Server Side)

When a service receives an authenticated internal API request, it must:

1. Read the `x-api-key` header.
2. Look up the corresponding private key from the secure configuration store.
3. Validate `x-timestamp`:
   - Ensure the timestamp is within an acceptable clock skew window (e.g., ±15 minutes).
4. Recompute the expected HMAC signature from the received body and timestamp.
5. Compare the recomputed signature with the `x-signature` header using a constant-time comparison.
6. If any step fails, reject the request with an appropriate error (e.g., 401/403).
7. If validation succeeds, associate the request with the calling system identity represented by the API key.

### 8.5 Tenant Context in API Calls

For APIs that act on tenant-specific data, the tenant context must be included in each call.

- Tenant context can be carried via:
  - Path parameter: `/api/tenants/{tenantId}/orders`.
  - Or header: `x-tenant-id: <tenant_id>` (or another agreed identifier).
- The receiving service must:
  - Validate the tenant identifier format.
  - Ensure the calling system (identified by `x-api-key`) is authorized to act on behalf of that tenant.

### 8.6 Additional Security Measures

- **Rate limiting** per `x-api-key` to prevent abuse or misconfigured clients.
- **IP allowlists** for known internal services where infrastructure permits.
- **Key lifecycle management and rotation**:
  - Maintain key metadata (status: active, deprecated, revoked; creation and expiration dates).
  - Support rolling key rotation without interrupting traffic (overlapping validity windows).

### 8.7 Center → Tenant API Flow

This flow describes how the Center Control calls a Tenant Application Service API using the API key + HMAC model.

**Use case example:** Center Control requests aggregated order statistics for a specific tenant.

1. **Center Control prepares request**
   - Determines the target tenant: `tenant_id`.
   - Builds the HTTP request:
     - Method and URL, for example:
       - `GET https://{tenant-host}/internal/api/tenants/{tenantId}/stats/orders`
     - Headers:
       - `x-api-key`: API key issued for Center Control.
       - `x-timestamp`: current Unix timestamp.
       - `x-tenant-id`: `{tenantId}` (optional if already in path).
   - Computes `x-signature`:
     - Concatenates canonical payload and timestamp (for GET, usually empty body + timestamp).
     - Calculates `HMAC_SHA256( body + "|" + timestamp, private_key_for_center )`.
     - Sets header `x-signature`.

2. **Tenant Application Service receives request**
   - Extracts headers:
     - `x-api-key`, `x-timestamp`, `x-signature`, `x-tenant-id`.
   - Looks up the private key for the received `x-api-key`.
   - Validates the timestamp window.
   - Recomputes HMAC and compares with `x-signature`.
   - Validates that:
     - The `tenantId` in path/header is a known tenant.
     - The calling system (Center Control) is allowed to access this tenant.

3. **Tenant Application executes business logic**
   - Queries its data (for the given `tenant_id`) and builds the response.
   - Returns HTTP 200 with JSON body (or an error status if validation/authorization fails).

4. **Center Control consumes response**
   - Uses the response data for dashboards, reporting, or further processing.

### 8.8 Tenant → Shared Service API Flow

This flow describes how a Tenant Application Service calls a Shared Service (for example, Notification or Payment Integration Service) with the same security model.

**Use case example:** Tenant Application requests the Notification Service to send an order confirmation email.

1. **Tenant Application prepares request**
   - Builds HTTP request to Shared Service, for example:
     - `POST https://notification.internal/api/v1/send-email`
   - Request body (example):
     - Recipient email, subject, template, payload, `tenant_id`, and related data.
   - Headers:
     - `x-api-key`: API key issued for Tenant Application Service.
     - `x-timestamp`: current Unix timestamp.
     - `x-tenant-id`: `{tenantId}`.
   - Computes `x-signature`:
     - `HMAC_SHA256( body + "|" + timestamp, private_key_for_tenant_service )`.
     - Sets header `x-signature`.

2. **Shared Service receives request**
   - Extracts `x-api-key`, `x-timestamp`, `x-signature`, `x-tenant-id`.
   - Looks up the private key for the Tenant Application Service.
   - Validates timestamp and recomputes signature.
   - Validates tenant context (for example, checks that this tenant exists and is active).
   - Applies rate limits per `x-api-key` if configured.

3. **Shared Service executes business logic**
   - Sends the email/SMS using configured providers.
   - Logs the operation with tenant and calling-service information.
   - Returns success or failure response with details.

4. **Tenant Application handles response**
   - Updates local state if needed (for example, logs notification status).
   - Handles errors according to retry and resilience policies.

## 9. Feature Protocol for Center Control

This section defines high-level protocol patterns used by Center Control when interacting with Tenant Application Services and Shared Services. It focuses on three core areas: tenant lifecycle and configuration, plan and feature flags, and monitoring and reporting.

All interactions follow the service-to-service communication model defined in section 8 (HTTPS, API key + private key with HMAC signatures, and standard headers such as `x-api-key`, `x-timestamp`, `x-signature`, and `x-tenant-id` for tenant-scoped operations).

### 9.1 Tenant Lifecycle and Configuration Protocol

Center Control manages the lifecycle and configuration of tenants. Typical operations include creating, updating, suspending, and terminating tenants, as well as updating store-level configuration.

- **Tenant creation**
  - Direction: Center Control  → Tenant Application Service (and provisioning pipeline).
  - Example endpoint (Tenant side): `POST /internal/api/tenants`.
  - Purpose: instruct Tenant Application to provision logical structures and default configuration for a new tenant.
  - Request body (high level):
    - Tenant identifiers (code, display name).
    - Plan or subscription identifier.
    - Initial configuration (for example, default locale, currency, time zone).
    - Domain or subdomain information.

- **Tenant status changes**
  - Direction: Center Control  → Tenant Application Service.
  - Example endpoints:
    - `PATCH /internal/api/tenants/{tenantId}/status` (activate, suspend, disable).
    - `PATCH /internal/api/tenants/{tenantId}/config` (update selected configuration fields).
  - Purpose: propagate lifecycle and configuration changes initiated from Center Control to tenant workloads.

Center Control is the system of record for tenant metadata and lifecycle state. Tenant services consume these instructions and keep their internal state aligned.

### 9.2 Plan and Feature Flags Protocol

Center Control defines and assigns plans and feature flags that determine which capabilities are available to each tenant.

- **Plan and feature definitions**
  - Maintained within Center Control (for example, `Plan`, `PlanFeature`, `TenantPlanAssignment`).
  - Not directly exposed to external callers except through controlled admin APIs and UI.

- **Exposing effective features to tenants**
  - Two complementary patterns can be used:
    - **Pull model (tenant-initiated)**:
      - Tenant Application calls: `GET /internal/api/tenants/{tenantId}/features`.
      - Center Control responds with the effective feature set for that tenant (for example, feature codes and configuration values).
    - **Push model (center-initiated)**:
      - When plans or feature flags change, Center Control calls a tenant endpoint such as `PUT /internal/api/tenants/{tenantId}/features` with the updated list of features.
  - Implementations can start with the pull model for simplicity and introduce push or event-based synchronization later.

- **Feature evaluation at runtime**
  - Tenant services are responsible for enforcing feature availability at runtime based on the effective feature set provided by Center Control.
  - Center Control remains the single point where plans and features are defined and assigned.

### 9.3 Monitoring and Reporting Protocol

Center Control needs visibility into tenant activity and health for reporting and operational purposes.

- **Aggregated metrics via pull APIs**
  - Direction: Center Control  → Tenant Application Service.
  - Example endpoint:
    - `GET /internal/api/tenants/{tenantId}/stats/orders?from=...&to=...`.
  - Response (conceptual):
    - Total orders, total revenue, average order value, and other aggregated KPIs for the requested time window.
  - Center Control uses these responses to build cross-tenant dashboards and reports.

- **Event or log streaming (optional / future)**
  - Direction: Tenant Application Service  → Shared logging/analytics infrastructure.
  - Events include tenant-specific metrics and operational events, always tagged with `tenant_id` and timestamps.
  - Center Control can consume these data sources (directly or indirectly) to enhance monitoring and analytics capabilities.

These protocol patterns should be refined into concrete API contracts as the implementation stack is finalized, but they provide a clear blueprint for how Center Control orchestrates tenant lifecycle, configuration, feature availability, and monitoring.

## 10. Future Extensions

The architecture should allow future extensions such as:

- Advanced loyalty and membership programs.
- Marketplace features (multi-tenant cart, cross-tenant search).
- Offline POS integration.

These should be implemented as separate modules/services where appropriate, maintaining clear boundaries between core platform and optional capabilities.

## 11. Enterprise Readiness – Center Control as Control Plane

Center Control acts as the **control plane** of the platform in an enterprise environment. It is responsible for governance, security, configuration, and operational control across all tenant workloads (data plane).

### 11.1 Governance and Tenant Management

- Center Control is the **source of truth** for tenant metadata:
  - Tenant identity (IDs, codes, names).
  - Tenant lifecycle state (draft, provisioning, active, suspended, terminated).
  - Tenant plan and feature assignments.
  - Domain and custom domain mappings.
- Center Control exposes controlled interfaces (UI and API) for:
  - Creating, updating, and deprovisioning tenants.
  - Changing tenant plans and feature sets with audit trails.
  - Applying global policies to groups of tenants (for example, region, plan tier).

### 10.2 Security, IAM, and Compliance

- Integrates with enterprise identity providers (for example, SAML/OIDC) for platform administrators and operators.
- Implements role-based access control (RBAC) within Center Control for:
  - Platform Owner, Platform Ops, Support, Auditor, and other roles as needed.
- Enforces strong authentication for administrative actions.
- Produces detailed **audit logs** for all sensitive operations, including:
  - Tenant creation, suspension, termination.
  - Plan and feature changes.
  - Access to sensitive configuration or data.
- Supports data protection and compliance requirements by:
  - Masking or limiting access to personally identifiable information where appropriate.
  - Providing mechanisms to export or delete tenant-related data when required by policy.

### 10.3 Operations, Observability, and Safety Controls

- Provides a **platform health view** across all tenants:
  - Aggregated status of tenant services and shared services.
  - Error rates, latency, and throughput per tenant where available.
- Offers operational controls for platform operators, such as:
  - Suspending or resuming specific tenants.
  - Placing a tenant into read-only mode during incidents or maintenance.
  - Triggering configuration refresh or resync for specific tenants.
- Integrates with centralized logging, metrics, and alerting systems to support SRE/operations teams.
- Supports environment separation (development, staging, production) with clear boundaries and safeguards to avoid cross-environment impact.

### 10.4 Integration into the Enterprise Landscape

- Exposes well-defined APIs and/or events for external enterprise systems to integrate with tenant lifecycle and configuration:
  - ERP and finance systems for billing and invoicing.
  - CRM and marketing systems for tenant and usage information.
  - ITSM tools for change and incident management.
- Publishes domain events (for example, `tenant_created`, `tenant_suspended`, `plan_changed`) to an event bus or webhook mechanism so that other systems can react in near real time.
- Applies API versioning and deprecation policies to maintain backward compatibility for dependent systems.

### 10.5 Lifecycle and Change Management

- Models tenant lifecycle as a state machine with explicit transitions and validations.
- Coordinates with provisioning and automation pipelines when tenant state changes (for example, provisioning infrastructure, DNS, certificates, or database resources).
- Provides guardrails and approval workflows where necessary for high-impact changes (for example, large plan upgrades or destructive operations).
- Ensures that platform and API changes are rolled out in a backward-compatible manner, with support for phased migrations across tenants.

## 12. Data & Analytics Architecture – Center Control & AI

This section describes how data from tenants and the platform is collected, processed, and made available for analytics and AI use cases. Center Control acts as a consumer of analytics data and insights, using them to power dashboards, reporting, and intelligent features.

### 12.1 Data Sources and Collection

Key data sources include:

- **Tenant Application Services**:
  - Business events (for example, `order_created`, `order_paid`, `customer_registered`, `inventory_low`).
  - Operational metrics (for example, error rates, latency, throughput per tenant).
- **Center Control**:
  - Tenant lifecycle changes (create, suspend, terminate).
  - Plan and feature changes.
  - Administrative actions and audit logs.
- **Shared Services**:
  - Payment status events (success, failure, refund).
  - Notification events (email/SMS delivered, bounced, failed).

Each event or record that is tenant-specific must be tagged with:

- `tenant_id` (or equivalent identifier).
- Timestamps (event time and ingestion time).
- Relevant business attributes (for example, amounts, product IDs, channels).

Data can be collected via:

- **Streaming/event pipelines** (for example, message bus or event hub) for near real-time processing.
- **Batch exports or pull APIs** for periodic aggregation or for systems that do not emit events.

### 12.2 ETL/ELT and Data Platform

The platform should include a dedicated data layer (data warehouse or data lakehouse) separate from operational databases.

- **Extract**:
  - Consume events and logs from tenant services, Center Control, and shared services.
  - Periodically pull summary statistics via internal APIs where streaming is not yet available.
- **Transform**:
  - Cleanse and normalize data.
  - Join with reference data (for example, tenant metadata, plan information).
  - Build analytical models such as fact and dimension tables:
    - `fact_orders`, `fact_payments`, `fact_customers`, `fact_traffic`.
    - Dimension tables for tenants, time, products, channels, and plans.
- **Load**:
  - Store transformed data in a data warehouse or lakehouse optimized for analytical queries.

This separation between OLTP (operational) and OLAP (analytical) workloads helps maintain performance and scalability for both.

### 12.3 Aggregation and Dashboards for Center Control

Center Control uses analytics data to power platform-level dashboards and reports, for example:

- Per-tenant KPIs: total orders, total revenue, average order value, conversion rates.
- Platform-wide KPIs: active tenants, GMV by region/plan, error rates and latency distributions.
- Operational views: health status of tenant services, incident trends.

To support this:

- Scheduled jobs or streaming aggregations precompute common metrics and store them in summary tables (for example, daily or hourly aggregates per tenant).
- Center Control interacts with the analytics layer via read-only **Analytics APIs** or direct BI integrations, not by querying operational databases directly.

### 12.4 Analytics and AI Integration

Analytics and AI services consume data from the data platform and expose insights back to Center Control and, where appropriate, to tenants.

Example AI/analytics use cases:

- **Recommendations for tenants**:
  - Suggest plan upgrades or feature enablement based on usage patterns.
  - Suggest best practices or configuration changes to improve performance or conversion.
- **Anomaly detection**:
  - Detect abnormal drops or spikes in orders or revenue per tenant.
  - Detect unusual error patterns or operational issues.
- **Forecasting**:
  - Forecast demand, revenue, or resource usage per tenant or per plan tier.

AI/analytics services can be implemented as separate services that:

- Train models using historical data from the warehouse.
- Serve predictions and recommendations through APIs.

Center Control then calls these services via secure internal APIs (following the same service-to-service communication model) to retrieve insights, for example:

- `GET /internal/api/analytics/tenants/{tenantId}/recommendations`
- `GET /internal/api/analytics/tenants/{tenantId}/alerts`

### 12.5 Governance and Data Protection

- Ensure that analytics data respects tenant isolation and data protection requirements.
- Apply appropriate anonymization, aggregation, or masking for sensitive fields, especially when used for cross-tenant analytics.
- Define clear data retention policies for raw events, logs, and aggregated data.

This data and analytics architecture enables Center Control to act not only as a governance and configuration control plane, but also as a central point for insights and AI-driven decision support across all tenants.
