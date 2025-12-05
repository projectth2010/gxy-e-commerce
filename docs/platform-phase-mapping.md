# Platform Phase Mapping – Center Control ↔ Tenant E-Commerce

This document maps Center Control implementation states to Tenant E-Commerce states. It is used to understand dependencies when planning the overall roadmap.

## 1. Overview

- **Center Control** = control plane capabilities.
- **Tenant E-Commerce** = per-tenant capabilities.
- A Tenant state usually requires that certain Center Control states are already in place.

## 2. Phase Mapping Table

| Tenant State | Tenant Description | Recommended Minimum Center Control State(s) | Notes |
| ------------ | ------------------ | ------------------------------------------- | ----- |
| **T1** Tenant Not Onboarded | Tenant does not exist yet, or only as a concept. | **C1 – Platform Foundations – Multi-Tenant Ready** | Need basic multi-tenant model and environment to even create tenants. |
| **T2** Tenant Onboarded – Store Not Live | Tenant is created, has Backoffice access, store not yet public. | **C1**, **C2 – Tenant Lifecycle & Basic Governance** | Center must manage tenant lifecycle and propagate config to Tenant App. |
| **T3** Tenant Live – Core E-Commerce (MVP) | Store is live with full basic e-commerce. | **C2**, **C3 – Plan/Package, Feature Toggles & Billing Readiness** | Plan/feature model should exist so live tenants are governed by entitlements and usage. |
| **T4** Tenant Live – Advanced Catalog & Multi-Store | Tenant uses advanced catalog and multiple storefronts. | **C3**, **C4 – Cross-Tenant Monitoring, Audit & Compliance** | Feature flags and limits for advanced catalog/multi-store, plus monitoring impact across tenants. |
| **T5** Tenant Live – Integrations & POS | Tenant centralizes orders from online, POS, and external channels. | **C3**, **C4**, **C5 – Ecosystem Integrations & Marketplace Management** | Center must manage integrations catalog, activation, and global/tenant-level controls. |
| **T6** Tenant Live – Loyalty/Royalty & AI-Driven | Tenant uses loyalty/royalty and AI/analytics-driven features. | **C3**, **C4**, **C5**, **C6 – Data, Analytics & AI-Driven Operations** | Requires feature flags, auditability, integration management, and analytics/AI capabilities in control plane. |

Legend:

- **C1–C6** = Center Control states in `center-control-implementation-phases.md`.
- **T1–T6** = Tenant states in `tenant-implementation-phases.md`.

## 3. Usage Guidance

- **Roadmap planning**
  - When planning a Tenant feature phase, verify that required Center states are planned or completed.
  - Avoid enabling Tenant States T4–T6 in production tenants before Center has at least C3–C4 for control and observability.

- **Release / rollout strategy**
  - Start with C1–C2 + T1–T2 in internal or pilot tenants.
  - Move to C3 and T3–T4 for broader GA.
  - Introduce C5–C6 and T5–T6 with more controlled rollout and stronger monitoring.

- **Communication between teams**
  - Platform/Center team owns C1–C6.
  - Tenant product teams own T1–T6.
  - This mapping provides a shared language for dependencies and sequencing.
