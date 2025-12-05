# Implementation Sprint Plan – Laravel (PHP) + Vue

This document captures the initial sprint plan derived from the implementation plan in `implementation-tech-stack-laravel-vue.md` and the SA/DBA checklists.

It is intended for use in project management tools (e.g., GitHub Projects, Jira). Each row can be mapped to an issue/ticket.

Columns:
- **Key** – Suggested issue key (can be adjusted to your tracking system).
- **Title** – Short summary of the task.
- **Labels** – Suggested labels (Backend/Frontend/DB/etc.).
- **Sprint** – Target sprint (Sprint 1–3 for the initial phases).
- **Phase (C/T)** – Center/Tenant phase supported by this issue.
- **Summary** – High-level description.

---

## Sprint 1 – Foundation & Tenant Management (C1–C2, prepare T2)

| Key   | Title                                        | Labels             | Sprint   | Phase (C/T) | Checklist Ref | Summary |
|-------|----------------------------------------------|--------------------|----------|-------------|---------------|---------|
| BE-01 | Init Laravel project                         | Backend, Infra     | Sprint 1 | C1          | CHK-1.2       | Create `backend/laravel-app`, configure `.env`, DB connection, and a basic health endpoint `/api/health`. |
| BE-02 | Implement Tenant model & migration           | Backend, DB        | Sprint 1 | C1–C2       | CHK-2.1       | Create `tenants` migration and `Tenant` model to store tenant metadata (code, name, status, plan, domains). |
| BE-03 | TenantManagement API (Center)                | Backend            | Sprint 1 | C2          | CHK-4.1       | Implement `TenantController` and `/api/center/tenants` endpoints for create/list/get/update tenant records. |
| BE-04 | Tenant lifecycle status rules                | Backend            | Sprint 1 | C2          | CHK-4.1       | Define tenant status enum (draft/active/suspended/terminated) and enforce rules so suspended/terminated tenants are blocked from tenant APIs. |
| BE-05 | Tenant context middleware & global scope     | Backend            | Sprint 1 | C1          | CHK-2.2       | Implement `TenantContextMiddleware` to resolve `tenant_id` (domain/header) and a `TenantScoped` trait for global `tenant_id` filtering in Eloquent models. |
| DB-01 | Confirm multi-tenant DB strategy (shared DB) | DBA                | Sprint 1 | C1          | CHK-2.3       | Finalize the choice of shared DB + `tenant_id` in `dba-multi-tenant-db-design.md` and document implications for migrations and indexing. |
| FE-01 | Init Vue project: center-admin               | Frontend, Center   | Sprint 1 | C1–C2       | CHK-1.3       | Create `frontend/center-admin` (Vue 3 + Vite + Router + Pinia) with basic layout and auth placeholder. |
| FE-02 | Center Admin – Tenant list & detail UI       | Frontend, Center   | Sprint 1 | C2          | CHK-4.3       | Build tenant list/detail pages and integrate them with `/api/center/tenants` for basic tenant management. |

---

## Sprint 2 – Plans/Features & Core Tenant Models (C3, prepare T2–T3)

| Key   | Title                                        | Labels             | Sprint   | Phase (C/T) | Checklist Ref | Summary |
|-------|----------------------------------------------|--------------------|----------|-------------|---------------|---------|
| BE-06 | Plan & Feature entities (DB + models)        | Backend, DB        | Sprint 2 | C3          | CHK-4.2       | Create migrations and models for `plans`, `features`, `plan_features`, and `tenant_plan_assignments` to represent plan and feature configuration. |
| BE-07 | Plan & Feature management APIs               | Backend            | Sprint 2 | C3          | CHK-4.2       | Implement APIs (e.g., `PlanController`) to list/create/update plans and, if needed, manage features independently. |
| BE-08 | Tenant plan assignment APIs                  | Backend            | Sprint 2 | C3          | CHK-4.2       | Implement endpoints to assign a plan to a tenant and fetch a tenant's effective plan (`/api/center/tenants/{id}/assign-plan`, etc.). |
| BE-09 | Effective features API (internal)            | Backend            | Sprint 2 | C3, T2–T3   | CHK-4.2       | Implement internal API `GET /internal/api/tenants/{tenantId}/features` that resolves the effective feature set for a tenant. |
| BE-10 | Core tenant domain migrations                | Backend, DB        | Sprint 2 | T2–T3       | CHK-7.1       | Create migrations for tenant-facing domain tables (products, product_images, categories, product_categories, inventory, customers, customer_addresses, orders, order_items, order_status_history, payments). |
| BE-11 | Core tenant models with TenantScoped         | Backend            | Sprint 2 | T2–T3       | CHK-2.3       | Define tenant domain models (`Product`, `Category`, `Order`, `Customer`, etc.) and apply the `TenantScoped` trait with `tenant_id` columns. |
| FE-03 | Center Admin – Plans & assignment UI         | Frontend, Center   | Sprint 2 | C3          | CHK-4.2       | Extend Center Admin with UI for managing plans and viewing/changing a tenant's plan/feature setup. |
| FE-04 | Init Vue project: tenant-backoffice          | Frontend, Tenant   | Sprint 2 | T2          | CHK-6.1       | Create `frontend/tenant-backoffice` with basic layout, navigation, and auth placeholder for tenant admins/staff. |

---

## Sprint 3 – Tenant Core Flows & Customer Service (T2–T3)

| Key   | Title                                        | Labels                 | Sprint   | Phase (C/T) | Checklist Ref | Summary |
|-------|----------------------------------------------|------------------------|----------|-------------|---------------|---------|
| BE-12 | Tenant Backoffice – Product & Category APIs  | Backend                | Sprint 3 | T2–T3       | CHK-7.2       | Implement `ProductController` and `CategoryController` for CRUD operations on `/api/tenant/products` and `/api/tenant/categories`. |
| BE-13 | Tenant – Order APIs (Backoffice & Storefront)| Backend                | Sprint 3 | T3          | CHK-9.2       | Implement order APIs for Backoffice (view/update orders, add tracking) and Storefront (place orders via checkout). |
| BE-14 | Tenant – Customer APIs                      | Backend                | Sprint 3 | T3          | CHK-7.1       | Implement customer APIs for listing and viewing customers and managing their addresses in the tenant context. |
| BE-15 | Customer service notes migration & model    | Backend, DB            | Sprint 3 | T3          | CHK-9.1       | Create `customer_service_notes` migration and model `CustomerServiceNote` to store internal customer service notes linked to customers/orders. |
| BE-16 | Customer service workspace APIs             | Backend                | Sprint 3 | T3          | CHK-10.1      | Implement customer service endpoints for search (by order/email/phone), consolidated customer+orders+notes view, and note creation. |
| FE-05 | Tenant Backoffice – Catalog screens         | Frontend, Tenant       | Sprint 3 | T2–T3       | CHK-7.2       | Build product and category management screens in `tenant-backoffice` (list/create/edit). |
| FE-06 | Tenant Backoffice – Order management screens| Frontend, Tenant       | Sprint 3 | T3          | CHK-10.1      | Build Backoffice order list/detail screens with status updates and tracking info management. |
| FE-07 | Tenant Backoffice – Customer & CS workspace | Frontend, Tenant       | Sprint 3 | T3          | CHK-10.1      | Build customer list/detail screens and a customer service workspace (search, timeline, internal notes). |
| FE-08 | Tenant Storefront – Browse & checkout MVP   | Frontend, Storefront   | Sprint 3 | T3          | CHK-8.1       | Create `frontend/tenant-storefront` to support product browsing, product detail, cart, and checkout using the tenant APIs. |

---

## Usage

- **GitHub Projects / Jira:**
  - Copy these rows into your project board and adjust `Key` to match your project key scheme.
  - Use `Labels` to drive filtering by Backend/Frontend/DB.
  - Use `Phase (C/T)` to track which Center/Tenant phase each issue supports.

- **Alignment with other docs:**
  - `implementation-tech-stack-laravel-vue.md` – technical details for how to implement each issue.
  - `sa-checklists.md` / `dba-checklists.md` – use for readiness checks before closing groups of issues per phase.
  - `dba-checklists.md` – database-specific readiness and isolation checks.

- **Checklist for execution & progress tracking:**
  - Use `implementation-checklist-c1-c3-t2-t3.md` as the operational checklist for C1–C3 / T2–T3.
  - When you create issues from this table, link each issue to one or more checklist items; closing the issue should correspond to ticking the relevant `[ ]` → `[x]` in the checklist.
  - At the end of a sprint or phase review, use the checklist file to quickly see which groups of capabilities (Center/Tenant) are functionally complete, even if some refinement issues remain in the board.
