# Implementation Checklist – C1–C3 / T2–T3

> ใช้ไฟล์นี้เป็นเช็กลิสต์เพื่อติดตามความคืบหน้าการพัฒนา
> ติ๊ก `[x]` เมื่องานเสร็จเรียบร้อย

---

## C1 + T2 – Foundation & Skeleton

### 1. Repository & Project Skeleton (CHK-1)

- [ ] (CHK-1.1) ตั้งค่า repository
  - [ ] สร้าง Git repo `gxy-e-commerce`
  - [ ] สร้างโครง `backend/`, `frontend/`, `docs/`
  - [ ] ตั้งค่า `.gitignore` (PHP, Laravel, Node, Vite)

- [ ] (CHK-1.2) Laravel backend
  - [ ] ติดตั้ง Laravel (PHP 8.2+, Laravel 10+)
  - [ ] ตั้ง `.env.example` สำหรับ local/dev
  - [ ] ตั้งค่า connection MySQL, Redis
  - [ ] สร้างโครง module / namespace
    - [ ] `App/Core`
    - [ ] `App/Center`
    - [ ] `App/Tenant`
    - [ ] `App/Shared`

- [ ] (CHK-1.3) Vue frontend
  - [ ] สร้าง Vite + Vue 3 project สำหรับ `tenant-app`
  - [ ] (ถ้าต้องการ) สร้าง project สำหรับ `center-admin`
  - [ ] ตั้งค่า TypeScript (ถ้าจะใช้), ESLint, Prettier
  - [ ] ตั้งโครง `src/modules`, `src/pages`, `src/components`, `src/stores`

---

### 2. Multi-Tenant Core (DB + Resolution) (CHK-2)

- [ ] (CHK-2.1) DB schema multi-tenant core
  - [ ] Migration ตาราง `tenants`
    - [ ] ฟิลด์หลัก (`id`, `code`, `name`, `status`)
    - [ ] domain/subdomain (`primary_domain` ฯลฯ)
    - [ ] การเชื่อมกับแผน (`plan_id` หรือ JSON config)
  - [ ] Index & unique keys (`code`, `primary_domain` ฯลฯ)

- [ ] (CHK-2.2) Tenant resolution
  - [ ] ออกแบบวิธีระบุ tenant (hostname / `X-Tenant-Key`)
  - [ ] Implement `TenantResolver` service
  - [ ] Middleware ดึง tenant จาก request และใส่เข้า context
  - [ ] Behavior เมื่อไม่พบ tenant (error code/response มาตรฐาน)

- [ ] (CHK-2.3) ฐานข้อมูลแบบ shared schema
  - [ ] นิยาม convention `tenant_id` (และ `store_id` ที่จำเป็น)
  - [ ] สร้าง trait/helper `BelongsToTenant` (global scope + set `tenant_id`)

---

### 3. Basic Auth & User Model (C1 Scope)

- [ ] User & role model ขั้นต้น
  - [ ] Migration `users` (รองรับ center/tenant users)
  - [ ] ตัดสินใจโครงสร้าง center users vs tenant users
  - [ ] Seed center super admin
  - [ ] Seed tenant demo admin (ถ้าต้องการ)

- [ ] Auth mechanism
  - [ ] ติดตั้ง Laravel Sanctum/Passport
  - [ ] Endpoint login
    - [ ] `POST /api/auth/login` (Center)
    - [ ] `POST /api/tenant/auth/login` (Tenant)
  - [ ] Middleware/guard
    - [ ] `auth:center`
    - [ ] `auth:tenant`

---

## C2 – Center Control Core (เชื่อม T2–T3) (CHK-4)

### 4. Center Control – Tenant Lifecycle (CHK-4)

- [ ] (CHK-4.1) Tenant management APIs
  - [ ] `POST /api/center/tenants` – สร้าง tenant
  - [ ] `GET /api/center/tenants` – list + filter
  - [ ] `GET /api/center/tenants/{id}` – detail
  - [ ] `PUT /api/center/tenants/{id}` – update
  - [ ] `PATCH /api/center/tenants/{id}/status` – เปลี่ยนสถานะ

- [ ] (CHK-4.2) Plan & feature models
  - [ ] Migration `plans`
  - [ ] Migration `plan_features`
  - [ ] การผูก tenant กับ plan/feature (pivot หรือ JSON)
  - [ ] Seed แผนเริ่มต้น (Basic, Pro, Enterprise)

- [ ] (CHK-4.3) Center admin UI (minimal)
  - [ ] หน้า list tenants
  - [ ] ฟอร์มสร้าง/แก้ไข tenant
  - [ ] ฟิลเตอร์ตาม status/plan

---

### 5. Center → Tenant Provisioning / Config Propagation (CHK-5)

- [ ] Event model / service call
  - [ ] นิยาม message/contract เมื่อสร้าง tenant
  - [ ] เลือกวิธีส่ง (internal call / queue event)

- [ ] Initial tenant config
  - [ ] สร้าง default `store` แรกสำหรับ tenant
  - [ ] สร้าง default settings (currency, locale ฯลฯ)
  - [ ] สร้าง tenant admin user แรก (จาก Center)

---

## T2 – Tenant Store Not Live (Backoffice & Catalog Skeleton) (CHK-6–7)

### 6. Tenant Backoffice Shell (CHK-6)

- [ ] (CHK-6.1) Route & layout
  - [ ] สร้าง backoffice layout (sidebar + topbar)
  - [ ] Route group `/admin` สำหรับ tenant
  - [ ] ป้องกันด้วย `auth:tenant` + tenant context

- [ ] Tenant dashboard basic
  - [ ] แสดงข้อมูล: tenant name, plan, status
  - [ ] แสดง counter เบื้องต้น (orders, products ฯลฯ)

---

### 7. Catalog Core (Product, Category, Store) (CHK-7)

- [ ] (CHK-7.1) DB catalog core
  - [ ] Migration `stores` (ผูก `tenant_id`)
  - [ ] Migration `categories`
  - [ ] Migration `products`
  - [ ] Migration ที่เกี่ยวกับราคา/stock (เช่น `product_prices`, `product_inventory`)
  - [ ] ใส่ `tenant_id` / `store_id` และ index สำคัญ

- [ ] (CHK-7.2) Backoffice catalog UI (MVP)
  - [ ] หน้า list/create/edit Category
  - [ ] หน้า list/create/edit Product
    - [ ] name, sku, price, status, stock (basic)
    - [ ] เลือก category, store (ถ้ามี multi-store)
  - [ ] Validation พื้นฐาน + error display

- [ ] Public API สำหรับ storefront
  - [ ] `GET /api/tenant/catalog/categories`
  - [ ] `GET /api/tenant/catalog/products` (+ filters)
  - [ ] `GET /api/tenant/catalog/products/{id or slug}`

---

## T3 – Storefront Live (Cart & Checkout MVP) + C3 Hardening (CHK-8–10)

### 8. Storefront (Customer-facing) (CHK-8)

- [ ] (CHK-8.1) Routing & pages
  - [ ] หน้า Home + Category listing
  - [ ] หน้า Product listing / detail
  - [ ] หน้า Cart
  - [ ] หน้า Checkout (MVP: COD / simple payment stub)

- [ ] State & API integration
  - [ ] Pinia store สำหรับ catalog
  - [ ] Pinia store สำหรับ cart
  - [ ] เชื่อม API สำหรับโหลด catalog
  - [ ] ส่งคำสั่งสร้าง order จาก checkout

---

### 9. Order & Checkout Backend (CHK-9)

- [ ] (CHK-9.1) DB orders
  - [ ] Migration `orders` (tenant_id, store_id, status, totals)
  - [ ] Migration `order_items`
  - [ ] Index สำคัญ (tenant_id + created_at, status)

- [ ] (CHK-9.2) Order API
  - [ ] `POST /api/tenant/orders` – สร้าง order
  - [ ] `GET /api/tenant/orders` – list (backoffice)
  - [ ] `GET /api/tenant/orders/{id}` – detail

- [ ] Payment & status (MVP)
  - [ ] รองรับวิธีจ่ายเงินแบบ offline (COD/โอน)
  - [ ] สร้าง status flow เบื้องต้น

---

### 10. Backoffice Order Management (MVP) (CHK-10)

- [ ] (CHK-10.1) Backoffice UI
  - [ ] หน้า list orders + filter
  - [ ] หน้า order detail (items, customer, address)
  - [ ] ปุ่มเปลี่ยนสถานะ (confirm, ship, complete, cancel)

---

## C3 – Platform Hardening & Readiness

### 11. Security & Governance Basics

- [ ] RBAC & permissions
  - [ ] กำหนด roles ขั้นต้น (center / tenant)
  - [ ] Map route group → role/permission
  - [ ] ตรวจว่าทุก endpoint สำคัญถูกป้องกัน

- [ ] Validation & error model
  - [ ] ใช้ FormRequest / custom validator ใน endpoint สำคัญ
  - [ ] กำหนดรูปแบบ error response กลาง

---

### 12. Observability & Non-Functional Basics

- [ ] Logging & monitoring
  - [ ] ตั้งค่า logging channel (stack + daily / stdout)
  - [ ] Log เหตุการณ์สำคัญ (tenant created, order status change)
  - [ ] Health check endpoint (`/health` หรือคล้ายกัน)

- [ ] Performance basics
  - [ ] เพิ่ม index DB ที่สำคัญ
  - [ ] เปิดใช้ caching ที่จำเป็น (เช่น catalog per tenant)
  - [ ] ตรวจสอบให้ทุก list endpoint มี pagination

---

### 13. Operational Readiness

- [ ] Environment & config
  - [ ] `.env` template สำหรับ dev/stage/prod
  - [ ] แยก config สำหรับ HMAC/API keys, queue/worker settings

- [ ] Deployment script/guide (พื้นฐาน)
  - [ ] ระบุคำสั่ง deploy (migrate, config cache, build frontend)
  - [ ] ระบุขั้นตอน rollback เบื้องต้น

---

> หมายเหตุ: สามารถเพิ่มคอลัมน์ Phase/Sprint ใน issue tracker แล้วโยงแต่ละ checkbox นี้เข้ากับสปรินต์ตาม capacity ทีมได้เลย
