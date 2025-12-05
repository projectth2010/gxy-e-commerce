# PM/PO – Scope and Roadmap

## 1. Product Vision

The platform is a **Center Control + Tenant E-Commerce** system that enables a single provider (platform owner) to host and manage multiple independent online stores (tenants).

The platform should:
- Allow store owners to launch and operate their own online shops quickly.
- Give the platform owner centralized control over configuration, plans, and monitoring.
- Provide a scalable foundation for future e-commerce and growth features.

## 2. Target Users

- **Platform Owner / Operations Team**
  - Manages the overall platform, tenant onboarding, and global settings.

- **Tenant Owner / Staff**
  - Operates an individual store (catalog, orders, customers, promotions).

- **Customer (End User)**
  - Browses products and places orders on a specific tenant's store.

## 3. High-Level Objectives

- Support **multiple tenants** with clear separation of data and configuration.
- Provide a **central management interface** for creating and controlling tenants.
- Deliver **core e-commerce capabilities** for each tenant:
  - Product and category management.
  - Order and customer management.
  - Basic promotions and reporting.

## 4. Scope Overview

### 4.1 In-Scope for MVP (Phase 1)

**Platform / Center Control**
- Tenant lifecycle management:
  - Create tenant (basic information, domain/subdomain, plan).
  - Update tenant status (active, suspended, disabled).
- Tenant configuration management:
  - Basic settings: store name, logo, contact.
  - Domain/subdomain assignment.
  - Plan/feature assignment (e.g., Basic vs Pro).
- Platform configuration (initial/basic):
  - Supported payment providers.
  - Supported shipping providers.
- Basic platform reporting:
  - List of tenants and their status.
  - High-level KPIs per tenant (e.g., total orders, total revenue – aggregated from tenant data).

**Tenant E-Commerce (per store)**
- Storefront:
  - Product listing by category.
  - Product details page.
  - Cart and checkout flow.
  - Customer registration, login, and profile.
  - Order history for logged-in customers.
- Backoffice (Admin):
  - Product management: create, edit, delete, manage stock and images.
  - Category management.
  - Order management: view, filter by status, update status.
  - Basic customer management: view customer list and profile summary.
  - Basic settings: store information, logo, contact details.
- Payment & Shipping:
  - Integration with at least one payment provider (end-to-end flow).
  - Basic shipping options (flat rate or simple rules).

### 4.2 Out-of-Scope for MVP (Future Phases)

- Marketplace features (e.g., multi-tenant shared catalog, cross-tenant cart).
- Advanced loyalty and membership programs.
- Offline POS integration.
- Advanced warehouse or inventory management (multi-warehouse, complex rules).
- Advanced promotion engine (complex discount rules, segmentation-based targeting).

These may be added in later phases once the core platform is stable.

## 5. User Personas & Key Journeys

### 5.1 Platform Owner / Operations
- Onboard a new tenant:
  - Create tenant in Center Control.
  - Assign plan and features.
  - Configure domain/subdomain.
- Monitor tenants:
  - View tenant list and status.
  - See basic metrics (e.g., total orders, total revenue per tenant).

### 5.2 Tenant Owner / Staff
- Set up store:
  - Configure store information and branding.
  - Set up payment and shipping options (within what platform supports).
- Manage catalog:
  - Create and organize products into categories.
  - Manage inventory and pricing.
- Manage orders and customers:
  - View and update orders.
  - View customer list and basic details.

### 5.3 Customer
- Discover and purchase:
  - Browse products and categories.
  - Add items to cart and checkout.
  - Create an account or check out as guest (if supported).
  - View order history (if registered).

## 6. Release Plan / Roadmap

> This is a suggested phase breakdown and can be refined.

### Phase 1 – Core Platform & Single-Tenant Flow
- Implement core data model and infrastructure.
- Implement basic Center Control for creating and managing tenants.
- Implement tenant storefront and backoffice for core e-commerce flows.
- Integrate with at least one payment gateway.
- Support single-tenant production usage (even if technically multi-tenant enabled).

### Phase 2 – Multi-Tenant Scaling & Reporting
- Harden multi-tenant isolation and scalability.
- Enhance Center Control with better reporting and monitoring per tenant.
- Improve configuration options and feature flags per tenant.
- Optimize performance under multiple concurrent tenants.

### Phase 3 – Advanced Features (Optional)
- Advanced promotions and marketing tools.
- Loyalty, membership tiers, or reward points.
- Marketplace features (cross-tenant discovery, multi-store cart) if required.
- Integration with offline channels (POS) and warehouse systems.

## 7. KPIs / Success Metrics

- **Tenant Metrics**
  - Number of active tenants.
  - Average onboarding time for a new tenant.

- **Business Metrics**
  - Gross Merchandise Value (GMV) per tenant and overall.
  - Number of orders per tenant.
  - Conversion rate on tenant storefronts.

- **Operational Metrics**
  - Platform uptime and incident count.
  - Average response times for key user journeys.

## 8. Dependencies & Risks (High-Level)

- Dependencies on external payment and notification providers.
- Risks related to multi-tenant data isolation and security.
- Potential complexity in supporting custom domains and SSL certificates per tenant (if required later).

This document should be kept in sync with the SA architecture and DBA design documents as the solution evolves.
