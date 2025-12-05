# Center Control + Tenant E-Commerce Platform

## 1. System Overview

This document provides a high-level overview of the Center Control + Tenant E-Commerce platform.

The system is a **multi-tenant e-commerce platform** where:
- A **Center Control (Central Management)** application manages the overall platform, tenants, global settings, and monitoring.
- Multiple **Tenant E-Commerce** stores (tenants) run on top of the platform, each representing an independent shop with its own storefront and backoffice.

The main goals are:
- Enable a single platform to host and manage many independent online stores.
- Provide centralized governance, configuration, and monitoring.
- Ensure tenant-level data isolation and configurable features per tenant.

## 2. Key Components

### 2.1 Center Management Application
- Manages tenant lifecycle (create, update, disable tenants).
- Configures tenant-level settings: domain/subdomain, plan/package, feature toggles.
- Manages global configurations (default payment providers, shipping providers).
- Provides platform-wide reporting and monitoring (across all tenants).

### 2.2 Tenant Storefront
- Public-facing e-commerce site for a specific tenant.
- Provides product browsing, search, and filtering.
- Supports cart, checkout, and payment.
- Supports customer registration, login, profile, and order history.

### 2.3 Tenant Backoffice (Admin Panel)
- Admin interface for each tenant (store owner and staff).
- Manages products, categories, inventory, and pricing.
- Manages orders, order statuses, shipments.
- Manages customers and basic customer segmentation.
- Manages promotions (discounts, coupons) in scope.

### 2.4 Shared Services
- **Authentication & Authorization** (multi-role, multi-tenant aware).
- **Payment Integration** with external payment gateways.
- **Notification Services** (email/SMS) for order and system events.
- **File Storage** for product images, logos, etc.

## 3. Tenant Model

The platform follows a **multi-tenant architecture** where multiple tenants (stores) share the same application runtime and infrastructure.

Each request is associated with a tenant by one or more of the following mechanisms (final choice will be detailed in SA/architecture docs):
- Subdomain mapping (e.g. `shopA.example.com`, `shopB.example.com`).
- Custom domain mapping for some tenants.
- Optional header-based tenant key for internal/admin APIs.

Data isolation model options (final selected model is defined in DBA documentation):
- **Shared database, shared schema with `tenant_id` column** on all business tables.
- **Database-per-tenant** (separate physical or logical databases per tenant).

Regardless of model, the system must ensure:
- Strong logical segregation of tenant data.
- No cross-tenant data leakage.

## 4. User Roles

High-level user roles across the platform:

- **Platform Super Admin (Center Control)**
  - Manages tenants, global configurations, and plans.
  - Has access to cross-tenant reporting and monitoring.

- **Tenant Admin / Tenant Staff**
  - Manages a specific tenant store only.
  - Has access to the tenant backoffice for products, orders, customers, and store settings.

- **Customer (End User)**
  - Registers and logs in to a specific tenant store.
  - Browses products, manages cart, places orders, and views order history.

## 5. Non-Functional Requirements (High-Level)

### 5.1 Security
- Tenant data isolation at all layers (application and database).
- Proper authentication and authorization per role and per tenant.
- Secure communication (HTTPS/TLS) for all external traffic.

### 5.2 Scalability
- Ability to onboard and serve many tenants without degrading performance.
- Ability to scale horizontally (application) and vertically/horizontally (database) depending on load.

### 5.3 Observability
- Centralized logging for all services.
- Monitoring and alerting on key metrics (errors, latency, throughput, resource usage).
- Basic audit logs for critical actions (tenant changes, pricing changes, order updates).

### 5.4 Availability & Performance
- Clear environment separation: development, staging, production.
- Performance targets defined per use case (e.g. average response time for storefront pages).
- Reasonable availability target (e.g. 99.5%+ for production; exact SLOs can be refined later).

## 6. Relationship with Other Documents

This overview document is complemented by role-specific documents:
- **SA Architecture Document**: detailed system architecture and technical design for Solution/Systems Architects.
- **DBA Database Design Document**: multi-tenant database strategy and schema design.
- **PM/PO Scope & Roadmap Document**: product scope, phases, and feature roadmap.

All documents should remain consistent and be updated together when the platform design evolves.
