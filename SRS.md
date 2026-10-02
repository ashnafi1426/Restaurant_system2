# Software Requirements Specification (SRS)
## Multi-Tenant Restaurant & Hotel Room Service Management System

**Document Version:** 2.0  
**Release Date:** September 2026  
**Standard Compliance:** IEEE Std 830-1998 / ISO/IEC/IEEE 29148  
**Architecture:** Multi-Tenant Cloud Architecture (Laravel REST API + Vue 3 Single Page Application)  
**Target Environments:** Multi-Property Hotel Chains, Luxury Resorts, Fine Dining & Multi-Outlet Restaurant Operations  

---

## Table of Contents

1. [Introduction](#1-introduction)
   - 1.1 Purpose
   - 1.2 Scope of the System
   - 1.3 Definitions, Acronyms, and Abbreviations
   - 1.4 References
   - 1.5 Document Overview
2. [Overall Description](#2-overall-description)
   - 2.1 Product Perspective & Context
   - 2.2 System Architecture Diagram
   - 2.3 User Classes & Personas (RBAC Matrix)
   - 2.4 Operating Environment & Tech Stack
   - 2.5 Design Constraints & Assumptions
3. [Specific System & Functional Requirements](#3-specific-system--functional-requirements)
   - 3.1 Multi-Tenant Hotel Scoping & Data Isolation Engine
   - 3.2 Authentication, Authorization & User Management
   - 3.3 Dynamic QR Code Resolution & Routing Subsystem
   - 3.4 Room Booking & Guest Reservation Subsystem
   - 3.5 Digital Menu & Category Catalog Management
   - 3.6 Ordering & Cart Processing Subsystem
   - 3.7 Intelligent Automatic Waiter Assignment Engine
   - 3.8 Kitchen Display System (KDS) & Order Lifecycle
   - 3.9 Multi-Channel Payment & Settlement Engine (Chapa, Room Charge, Cash)
   - 3.10 Table & Section Floor Plan Management
   - 3.11 Guest Review, Rating & Complaint Management
   - 3.12 Real-Time Audio-Visual Alerting & Notifications
   - 3.13 Managerial Analytics, Financial Reporting & Audit Logging
4. [External Interface Requirements](#4-external-interface-requirements)
   - 4.1 User Interfaces (UI/UX Standards)
   - 4.2 Hardware Interfaces (POS Thermal Printers, Scanners, Tablets)
   - 4.3 Software & Payment Gateway Interfaces (Chapa API)
   - 4.4 Communication Protocols & Network Interfaces
5. [Non-Functional Requirements (NFRs)](#5-non-functional-requirements-nfrs)
   - 5.1 Performance & Throughput
   - 5.2 Security & Data Privacy
   - 5.3 Reliability, Availability & Fault Tolerance
   - 5.4 Usability & Internationalization (i18n: EN / AM)
   - 5.5 Maintainability & Scalability
6. [Data Models & Database Schema Overview](#6-data-models--database-schema-overview)
7. [System Verification & Acceptance Criteria](#7-system-verification--acceptance-criteria)

---

## 1. Introduction

### 1.1 Purpose
The purpose of this document is to provide a complete, formal, and unambiguous specification of the requirements for the **Multi-Tenant Restaurant and Hotel Room Service Management System (RMS)**. It details functional and non-functional requirements, data schemas, API contracts, business rules, and security controls for developers, testers, hotel managers, and system administrators.

### 1.2 Scope of the System
The system is an enterprise-grade hospitality platform engineered to handle both multi-hotel property management and high-volume dining operations.
* **Core Capabilities:**
  1. Multi-tenant property isolation with tenant-specific branding, taxes, currencies, and service fees.
  2. Frictionless QR-code scanning for room service, restaurant dine-in tables, and walk-in guests.
  3. Digital room booking and folio management.
  4. Real-time Kitchen Display System (KDS) dispatch boards.
  5. Intelligent automated waiter assignment algorithms with workload balancing.
  6. Multi-channel payments featuring Chapa (Card, Telebirr, CBEBirr, Awash), Cash on Delivery, and direct Room Charge billing.
  7. Instant guest review, rating, and complaint escalation ticketing.
  8. Comprehensive manager dashboards with revenue analytics, tax breakdowns, and staff performance metrics.

### 1.3 Definitions, Acronyms, and Abbreviations
* **RMS:** Restaurant Management System.
* **KDS:** Kitchen Display System (station view for chefs and preparation staff).
* **RBAC:** Role-Based Access Control.
* **Tenant / Hotel ID:** Unique primary identifier that partitions and isolates all operational data.
* **Chapa:** Ethiopian fintech electronic payment gateway supporting cards and mobile wallets.
* **Room Charge:** Order billing method deferred to the guest's active room reservation folio.
* **QR Token:** Cryptographically unique or structured token bound to a specific table or room.
* **SLA:** Service Level Agreement (maximum turnaround time threshold).

### 1.4 References
* IEEE Std 830-1998: Recommended Practice for Software Requirements Specifications.
* ISO/IEC/IEEE 29148:2018: Systems and software engineering — Life cycle processes — Requirements engineering.
* REST API Architectural Constraints (RFC 7231).
* Chapa Payment Gateway API Documentation v1.0.

### 1.5 Document Overview
Section 2 describes high-level product context, users, and constraints. Section 3 outlines all detailed functional requirements categorized by feature domain. Section 4 specifies hardware, software, and interface protocols. Section 5 presents non-functional quality attributes. Section 6 provides data models and schemas, and Section 7 defines verification acceptance tests.

---

## 2. Overall Description

### 2.1 Product Perspective & Context
The RMS operates as a distributed multi-tenant web application. Guests interact with responsive, mobile-first web pages without requiring account registration, while hotel staff and administrators operate role-specific web dashboards.

### 2.2 System Architecture Diagram

```
+-----------------------------------------------------------------------------------+
|                                 CLIENT APPLICATIONS                               |
|                                                                                   |
|  [Guest PWA (QR / Mobile)]   [Waiter Terminal]   [Kitchen KDS]   [Admin Dashboard]|
+-----------------------------------------------------------------------------------+
                                         │ HTTPS / JSON API
                                         ▼
+-----------------------------------------------------------------------------------+
|                                LARAVEL API GATEWAY                                |
|                                                                                   |
|  [Tenant Scoping Middleware]  [Auth / Sanctum Guard]  [Rate Limiter] [CORS / CSRF]|
+-----------------------------------------------------------------------------------+
                                         │
        ┌────────────────────────────────┼────────────────────────────────┐
        ▼                                ▼                                ▼
+--------------------+         +--------------------+         +---------------------+
| BUSINESS SERVICES  |         | BACKGROUND WORKERS |         | EXTERNAL SERVICES   |
| - QR Resolver      |         | - Auto-Assign Job  |         | - Chapa Gateway     |
| - Order Machine    |         | - Audio Dispatcher |         | - Thermal POS Print |
| - Workload Engine  |         | - Email Dispatcher |         | - SMTP Notifications|
+--------------------+         +--------------------+         +---------------------+
        │                                │
        └────────────────────────────────┼────────────────────────────────┘
                                         ▼
+-----------------------------------------------------------------------------------+
|                         PERSISTENCE & STORAGE LAYER                               |
|                                                                                   |
|   MySQL / PostgreSQL (Tenanted Tables)   │   Redis (Cache / Session / Queues)     |
+-----------------------------------------------------------------------------------+
```

### 2.3 User Classes & Personas (RBAC Matrix)

| User Role | Access Scope | Key Permissions & Operational Functions |
|---|---|---|
| **Super Admin** | System-wide (All Tenants) | Register hotels, manage tenant subscriptions, configure global settings, inspect system audit logs. |
| **Hotel Admin / Manager** | Single Hotel Tenant | Configure hotel profile, tax rates, service charges, rooms, tables, categories, menu items, staff accounts, reports, and complaints. |
| **Kitchen Staff / Chef** | Kitchen Station | View live incoming orders on KDS, update prep status (`preparing` $\rightarrow$ `ready`), toggle item stock availability. |
| **Waiter / Server** | Floor / Room Service | Receive assigned orders, deliver items, mark as `served`, take manual walk-in orders, request payment settlements. |
| **Cashier** | Billing & POS | Confirm cash receipts, verify room charge transfers, print fiscal bills, reconcile daily shift balances. |
| **Guest (Unauthenticated)** | QR Session Scoped | Scan QR, browse menu, place orders, complete Chapa payments, track live order status, file complaints and reviews. |

### 2.4 Operating Environment & Tech Stack
* **Frontend:** Vue.js 3.5+ (`<script setup>`, TypeScript), Tailwind CSS 3.4+, Pinia State Management, Lucide Icons, Vite 8.
* **Backend:** PHP 8.2+, Laravel 11.x, Laravel Sanctum, Form Requests, Queues, Database Transactions.
* **Database:** MySQL 8.0+ / PostgreSQL 15+ with indexed foreign keys and strict referential integrity.
* **Third-Party Integrations:** Chapa API Gateway, HTML5 Camera API, Thermal ESC/POS browser print.

### 2.5 Design Constraints & Assumptions
1. **Network Connectivity:** Uninterrupted local or cloud network connectivity for real-time order dispatching.
2. **Zero-Friction Guest Access:** Guests must never be blocked by mandatory login screens when scanning a table/room QR code.
3. **Currency & Tax Localization:** System must calculate dynamic tax and service charge percentages on a per-hotel basis.

---

## 3. Specific System & Functional Requirements

### 3.1 Multi-Tenant Hotel Scoping & Data Isolation Engine

* **REQ-1.1:** Every core entity table (`users`, `rooms`, `restaurant_tables`, `categories`, `menu_items`, `orders`, `reservations`, `payments`, `complaint_tickets`, `reviews`) **MUST** contain a `hotel_id` foreign key column.
* **REQ-1.2:** Application models must implement an automated global scope (`TenantScope`) ensuring every SQL `SELECT`, `UPDATE`, and `DELETE` includes `WHERE hotel_id = :current_tenant_id`.
* **REQ-1.3:** Cross-tenant access attempts must immediately abort with an HTTP `403 Forbidden` status code and record an event in security audit logs.
* **REQ-1.4:** Hotel configuration must support tenant-specific settings:
  * Hotel Name, Brand Logo, Splash Cover Image.
  * Currency Code (e.g., `ETB`, `USD`, `EUR`).
  * VAT Rate Percentage (e.g., `15.00%`).
  * Service Charge Percentage (e.g., `10.00%`).
  * Contact Phone, Support Email, Physical Address.
---

### 3.2 Authentication, Authorization & User Management

* **REQ-2.1:** Staff users log in via `/api/auth/login` with email and password, returning an encrypted Bearer token and user profile payload.
* **REQ-2.2:** Passwords must be hashed using Bcrypt with a minimum cost factor of 10.
* **REQ-2.3:** The backend must provide role-checking middleware (`role:admin`, `role:manager`, `role:waiter`, `role:kitchen`, `role:cashier`).
* **REQ-2.4:** Managers shall be able to create, update, activate, and deactivate staff accounts within their hotel.

---

### 3.3 Dynamic QR Code Resolution & Routing Subsystem

* **REQ-3.1:** The system shall generate unique cryptographic or structured QR tokens for:
  1. **Room QR:** Maps to a specific hotel room number and its current active booking reservation.
  2. **Table QR:** Maps to a specific restaurant table number and dining floor section.
  3. **Walk-in / Lobby QR:** Maps to a general ordering terminal.
* **REQ-3.2:** The API endpoint `GET /api/guest/resolve-qr/{token}` shall resolve and return:
  ```json
  {
    "success": true,
    "data": {
      "type": "room",
      "hotel_id": "uuid-or-id",
      "hotel_name": "Grand Palace Hotel",
      "logo_url": "/storage/logos/hotel1.png",
      "currency": "ETB",
      "room_number": "104",
      "reservation": {
        "id": "res-uuid",
        "guest_name": "Abebe Kebede",
        "check_in_date": "2026-09-20",
        "check_out_date": "2026-09-25",
        "status": "checked_in"
      }
    }
  }
  ```
* **REQ-3.3:** If a room QR is scanned and no active reservation is found, the guest interface automatically directs the user to the Room Booking flow.

---

### 3.4 Room Booking & Guest Reservation Subsystem

* **REQ-4.1:** Guests can browse available rooms, filter by room type, capacity, price, and amenities.
* **REQ-4.2:** Booking creation requires guest details (`first_name`, `last_name`, `email`, `phone`), `check_in_date`, and `check_out_date`.
* **REQ-4.3:** System verifies room availability preventing double-booking overlaps:
$$\text{Overlap Condition: } (\text{Requested CheckIn} < \text{Existing CheckOut}) \land (\text{Requested CheckOut} > \text{Existing CheckIn})$$
* **REQ-4.4:** Reservations progress through lifecycle states:
  `pending` $\rightarrow$ `confirmed` $\rightarrow$ `checked_in` $\rightarrow$ `checked_out` $\rightarrow$ `cancelled`.

---

### 3.5 Digital Menu & Category Catalog Management

* **REQ-5.1:** Managers can organize menu items into hierarchical categories with display order priorities.
* **REQ-5.2:** Menu item entities must store:
  * `name`, `description`, `price`, `image_url`
  * `category_id`, `preparation_time` (mins)
  * `is_available` (boolean)
  * `dietary_tags` (e.g., `['vegan', 'gluten_free', 'halal']`)
  * `allergens` (e.g., `['nuts', 'dairy']`)
* **REQ-5.3:** Chefs and managers can toggle item availability with instantaneous reflection on the guest digital menu.

---

### 3.6 Ordering & Cart Processing Subsystem

* **REQ-6.1:** Guests can add items to cart, adjust quantities, specify special notes, and select payment methods.
* **REQ-6.2 Order Financial Calculation Formula:**
$$\text{Subtotal} = \sum_{i=1}^{n} (\text{item\_price}_i \times \text{quantity}_i)$$
$$\text{Service Charge Amount} = \text{Subtotal} \times \left(\frac{\text{Service Charge Rate}}{100}\right)$$
$$\text{Taxable Basis} = \text{Subtotal} + \text{Service Charge Amount}$$
$$\text{Tax (VAT) Amount} = \text{Taxable Basis} \times \left(\frac{\text{Tax Rate}}{100}\right)$$
$$\text{Total Payable} = \text{Taxable Basis} + \text{Tax (VAT) Amount} - \text{Discount}$$
* **REQ-6.3:** Orders generate an alphanumeric tracking reference (e.g., `ORD-000492`).

---

### 3.7 Intelligent Automatic Waiter Assignment Engine

* **REQ-7.1 Selection Algorithm Rules:**
  1. Retrieve all staff with role `waiter`, status `active`, and shift status `on_duty` in the same `hotel_id`.
  2. If the order is placed from a table assigned to a specific floor section, filter waiters assigned to that section.
  3. Calculate the active workload score $W$ for each candidate waiter:
     $$W = \text{Count}(\text{Orders with status } \in \{\text{'pending'}, \text{'preparing'}\})$$
  4. Assign the order to the candidate with the lowest workload score $\min(W)$.
  5. In case of ties, select the waiter with the earliest last assignment timestamp (Round-Robin).
* **REQ-7.2:** Managers can manually reassign orders to any staff member via the dashboard.

```
                    [ New Order Placed ]
                              │
             [ Filter On-Duty Waiters in Section ]
                              │
             [ Calculate Active Workload (W) ]
                              │
             [ Select Waiter with Min(W) ]
                              │
             [ Create WaiterAssignment Record ]
                              │
             [ Dispatch Push / Sound Notification ]
```

---

### 3.8 Kitchen Display System (KDS) & Order Lifecycle

* **REQ-8.1 State Transitions:**
```
  ┌───────────┐      ┌─────────────┐      ┌─────────┐      ┌──────────┐
  │  PENDING  │ ───► │  PREPARING  │ ───► │  READY  │ ───► │  SERVED  │
  └─────┬─────┘      └──────┬──────┘      └─────────┘      └──────────┘
        │                   │
        └─────────┬─────────┘
                  ▼
          ┌───────────────┐
          │   CANCELLED   │
          └───────────────┘
```
* **REQ-8.2:** KDS cards must visually highlight elapsed preparation times:
  * **Normal (Green/Slate):** $< 50\%$ of item preparation time.
  * **Warning (Amber):** Between $50\%$ and $100\%$ of preparation time.
  * **Critical / SLA Breach (Red Pulse):** $> 100\%$ of allocated preparation time.
* **REQ-8.3:** Moving status to `ready` sends an immediate pickup alert to the assigned waiter.

---

### 3.9 Multi-Channel Payment & Settlement Engine

* **REQ-9.1 Supported Payment Methods:**
  1. **Chapa Online Payment Gateway:** Telebirr, CBEBirr, Awash, Visa/Mastercard.
  2. **Room Charge (Post-Pay):** Validates active check-in and posts total to room bill.
  3. **Cash / POS Terminal:** Settled by waiter or cashier upon delivery.
* **REQ-9.2 Chapa Integration Workflow:**
  1. Client triggers checkout $\rightarrow$ Backend initiates transaction with Chapa API.
  2. Client receives `checkout_url` and redirects guest to payment portal.
  3. Upon payment completion, Chapa sends an asynchronous Webhook containing transaction reference and cryptographic hash.
  4. Backend verifies webhook signature $\rightarrow$ Updates payment status to `paid` $\rightarrow$ Moves order to `preparing`.

---

### 3.10 Table & Section Floor Plan Management

* **REQ-10.1:** System supports custom floor sections (e.g., Indoor, Outdoor Patio, Balcony, VIP Lounge).
* **REQ-10.2:** Tables record `table_number`, `capacity` (seats), `section_id`, and real-time status (`available`, `occupied`, `reserved`, `maintenance`).
* **REQ-10.3:** Single-click high-resolution QR code generation and printable PDF/PNG export for all tables.

---

### 3.11 Guest Review, Rating & Complaint Management

* **REQ-11.1:** Guests can submit a 1–5 star rating and optional comments after order fulfillment.
* **REQ-11.2 Urgent Complaint Escalation:**
  * Guests can submit instant complaints choosing categories (e.g., "Late Food", "Cold Dish", "Missing Item", "Staff Behavior").
  * Assigns a ticket ID (e.g., `TCK-8921`).
  * Alerts manager in real-time.
  * Managers can update ticket status (`open`, `investigating`, `resolved`, `dismissed`) with resolution logs.

---

### 3.12 Real-Time Audio-Visual Alerting & Notifications

* **REQ-12.1:** The frontend contains audio alert synthesizers / sound chimes for critical operational events:
  * **Kitchen:** Distinct ding chime when a new order lands on KDS.
  * **Waiter:** Alert vibration/tone when kitchen flags an order as `ready`.
  * **Manager:** Urgent alert when a guest submits a complaint ticket.
* **REQ-12.2:** Browser notifications and live polling updates (every 5–10 seconds) keep all terminals synchronized.

---

### 3.13 Managerial Analytics, Financial Reporting & Audit Logging

* **REQ-13.1:** Analytics dashboard must render live metrics:
  * Gross sales, net food sales, tax collected, service charge totals.
  * Order status distribution charts (Pie/Bar).
  * Peak sales hours and hourly order volume heatmaps.
  * Top 10 best-selling items and bottom-performing items.
  * Staff delivery times and waiter performance tables.
* **REQ-13.2:** Financial export in CSV and printable PDF formats with date-range filters.

---

## 4. External Interface Requirements

### 4.1 User Interfaces (UI/UX Standards)
* **Design Theme:** Dark mode & high-contrast Light mode, Tailwind CSS typography, glassmorphic status badges, responsive across mobile (320px+), tablets (768px+), and wide desktop monitors (1920px+).
* **Guest Flow:** Zero-login mobile web application designed for fast touch interactions and bottom-sheet drawers.

### 4.2 Hardware Interfaces
* **Receipt Printers:** Supports ESC/POS 58mm and 80mm thermal receipt printing via standard browser window print APIs.
* **Camera Scanners:** Access to mobile device rear cameras via HTML5 `MediaDevices.getUserMedia()` for scanning physical QR codes.

### 4.3 Software & Payment Gateway Interfaces
* **Chapa API:** REST endpoints for initialization (`POST /v1/transaction/initialize`) and verification (`GET /v1/transaction/verify/{tx_ref}`).
* **SMTP / Mail Service:** Standard mail drivers for sending booking confirmations and automated manager reports.

### 4.4 Communication Protocols
* **HTTPS:** Mandatory TLS 1.2 / 1.3 encryption on all public endpoints.
* **REST API:** Standard HTTP verbs (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`) with strict JSON request/response formats and RFC 7807 error structures.

---

## 5. Non-Functional Requirements (NFRs)

### 5.1 Performance & Throughput
* **API Response Time:** $95\%$ of API read/write transactions must respond in $\le 200\text{ms}$.
* **Concurrency:** Capable of handling $500+$ concurrent dining sessions per hotel tenant without degradation.
* **Database Optimization:** Indexed foreign keys, composite indexes on `[hotel_id, status]`, and eager loading (`with(...)`) to eliminate $N+1$ queries.

### 5.2 Security & Data Privacy
* **Tenant Scoping:** Zero cross-tenant data leakage. Tenant boundaries enforced at the database query level.
* **Vulnerability Defenses:** Strict defense against SQL Injection (parameterized queries), XSS (output escaping), and CSRF attacks.
* **Rate Limiting:** Public endpoints throttled to 60 requests/minute per IP address.

### 5.3 Reliability, Availability & Fault Tolerance
* **Uptime SLA:** $99.9\%$ operational uptime.
* **Database Transactions:** All multi-entity mutations (e.g. order creation + item insertion + waiter assignment) wrapped in `DB::transaction()` to ensure atomicity.

### 5.4 Usability & Internationalization (i18n)
* **Languages Supported:** Full dynamic interface translation between **English (EN)** and **Amharic (AM)**.
* **Accessibility:** Accessible touch targets ($\ge 44 \times 44\text{px}$) and WCAG 2.1 AA compliant contrast.

---

## 6. Data Models & Database Schema Overview

```
+-----------------------------------------------------------------------------------------------+
|                                  DATABASE ENTITY RELATIONSHIPS                                |
+-----------------------------------------------------------------------------------------------+

  [hotels]
     │
     ├──< [users] (role: admin | manager | waiter | kitchen | cashier)
     ├──< [categories] ───< [menu_items] ───< [order_items]
     ├──< [rooms] ───< [reservations]             │
     ├──< [restaurant_tables]                     │
     │         │                                  │
     │         └──────────────────────────┐       │
     │                                    │       │
     └──< [orders] ───────────────────────┼───────┘
            │                             │
            ├──< [order_items]            │
            ├──< [payments] (chapa_ref, amount, payment_type, status)
            ├──< [waiter_assignments] (waiter_id, status)
            ├──< [complaint_tickets] (ticket_number, subject, status)
            └──< [reviews] (rating, comment)
```

---

## 7. System Verification & Acceptance Criteria

| Test ID | Requirement | Verification Method | Acceptance Pass Criteria |
|---|---|---|---|
| **TC-01** | Multi-Tenancy Scoping | Integration Test | Querying orders as Hotel A manager never returns Hotel B orders under any circumstances. |
| **TC-02** | QR Code Resolution | End-to-End Test | Scanning Room 204 QR successfully loads Hotel branding, Room 204 details, and active booking. |
| **TC-03** | Order Placement | Functional Test | Order items calculate correct VAT & Service Charge and appear on KDS in $< 1\text{s}$. |
| **TC-04** | Auto Waiter Engine | Algorithmic Test | New order is automatically assigned to the on-duty waiter with the lowest active workload in that section. |
| **TC-05** | Chapa Payment Webhook | Gateway Simulation | Successful payment webhook updates order payment status to `paid` and moves order to `preparing`. |
| **TC-06** | Audio Alerting | UI/Browser Test | Sound notification triggers on KDS when new order arrives and on waiter device when order is `ready`. |
| **TC-07** | Complaint Escalation | Functional Test | Guest complaint immediately generates ticket `TCK-XXXX` and notifies hotel manager dashboard. |

---

*End of Specification Document.*
