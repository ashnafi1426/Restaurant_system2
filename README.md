# Restaurant System --- Multi-Tenant Restaurant & Hotel Room Service Management

A web-based restaurant and hotel room-service management system designed
to support QR-code ordering, digital menus, room reservations, kitchen
workflows, waiter assignment, payments, and hotel-level administration.

> **Project documentation:** This README summarizes the system described
> in `SRS.md` / `SRS_MULTI_HOTEL_SUPPORT.md`. The SRS defines the target
> requirements; individual features should be verified against the
> current source code before being considered complete.

## Table of Contents

-   [Overview](#overview)
-   [System Goals](#system-goals)
-   [User Roles](#user-roles)
-   [Main Modules](#main-modules)
-   [Technology Stack](#technology-stack)
-   [Architecture](#architecture)
-   [Repository Structure](#repository-structure)
-   [Getting Started](#getting-started)
-   [Configuration](#configuration)
-   [Core Workflows](#core-workflows)
-   [Security and Data Isolation](#security-and-data-isolation)
-   [Testing and Acceptance Criteria](#testing-and-acceptance-criteria)
-   [Internationalization](#internationalization)
-   [Contributing](#contributing)
-   [Project Documentation](#project-documentation)

## Overview

The Restaurant System brings restaurant operations and hotel
room-service workflows together in one platform. Guests can scan a QR
code to access the appropriate menu, place an order, choose an available
payment method, and follow order progress. Authorized staff use
role-specific interfaces to manage orders and daily operations.

The system is designed for multiple hotel or restaurant tenants. Each
tenant should have its own operational data and configurable branding,
currency, tax, and service-charge settings.

## System Goals

-   Provide a mobile-friendly, QR-based guest ordering experience.
-   Support restaurant tables, hotel rooms, and general walk-in ordering
    contexts.
-   Manage menu categories, menu items, availability, and pricing.
-   Process orders and calculate subtotal, tax, service charges,
    discounts, and totals.
-   Support online payments and operational settlement workflows.
-   Help kitchen and service staff track order progress.
-   Protect each tenant's data from access by other tenants.
-   Provide management reporting, audit trails, guest reviews, and
    complaint handling.

## User Roles

  -----------------------------------------------------------------------
  Role                                Intended responsibilities
  ----------------------------------- -----------------------------------
  Super Admin                         Manage tenants and global system
                                      configuration.

  Hotel Admin / Manager               Manage hotel settings, staff,
                                      rooms, tables, menus, reports, and
                                      complaints.

  Kitchen Staff / Chef                View incoming orders, update
                                      preparation status, and manage item
                                      availability.

  Waiter / Server                     Handle assigned orders, delivery,
                                      and service workflows.

  Cashier                             Handle payment settlement,
                                      receipts, and shift reconciliation.

  Guest                               Scan a QR code, browse the menu,
                                      place orders, pay where supported,
                                      and track order status.
  -----------------------------------------------------------------------

Access must be enforced by the backend as well as by the frontend UI.

## Main Modules

### 1. Multi-Tenant Management

-   Hotel-specific data boundaries.
-   Tenant-specific branding, currency, VAT, and service-charge
    configuration.
-   Tenant-scoped reporting and staff access.

### 2. QR Code and Guest Context

-   QR resolution for room, table, and walk-in contexts.
-   Room QR association with an active reservation where applicable.
-   Guest-facing routing based on the resolved QR context.

### 3. Digital Menu

-   Menu categories and items.
-   Item descriptions, images, prices, preparation times, and
    availability.
-   Dietary tags and allergen information where configured.

### 4. Cart and Ordering

-   Add and remove items and change quantities.
-   Support special instructions where available.
-   Generate order references for tracking.
-   Calculate item subtotal, service charge, VAT, discounts, and final
    total using the tenant's configuration.

### 5. Room Booking and Reservations

-   Browse room options and availability.
-   Capture guest and check-in/check-out details.
-   Prevent overlapping reservations.
-   Track reservation lifecycle states.

### 6. Kitchen Display and Order Lifecycle

Expected order lifecycle:

`pending` → `preparing` → `ready` → `served`

Cancellation should be supported according to the system's business
rules. Kitchen views should make delayed orders easy to identify, and
ready orders should notify the assigned service staff where
notifications are configured.

### 7. Waiter Assignment

-   Identify active, on-duty waiters in the relevant hotel and floor
    section.
-   Prefer eligible waiters with the lowest active workload.
-   Allow authorized managers to reassign orders where supported.

### 8. Payments

The SRS specifies these payment channels: - **Chapa:** online payment
methods supported by the configured Chapa account. - **Room charge:**
add an eligible guest's order to an active room folio. - **Cash / POS:**
staff-confirmed settlement.

Payment status must be verified by the backend. Never treat a browser
redirect alone as proof of successful payment.

### 9. Tables and Floor Sections

-   Manage tables, capacity, floor sections, and table status.
-   Generate QR codes for tables where supported.

### 10. Reviews and Complaints

-   Collect guest ratings and optional comments.
-   Create complaint tickets, track resolution status, and notify
    managers where configured.

### 11. Notifications and Reporting

-   Order and kitchen alerts.
-   Operational and financial metrics.
-   Date-filtered reporting and CSV/PDF exports where implemented.
-   Audit records for important administrative and security actions.

## Technology Stack

The SRS specifies the following target stack. Check the package and
dependency files in this repository for the exact versions currently
installed.

  -----------------------------------------------------------------------
  Layer                               Technology
  ----------------------------------- -----------------------------------
  Frontend                            Vue 3, TypeScript, Vite, Tailwind
                                      CSS

  Frontend state                      Pinia (where used by the
                                      application)

  Backend API                         PHP and Laravel

  Authentication                      Laravel Sanctum / role-based
                                      authorization as configured

  Database                            MySQL or PostgreSQL

  Queues and cache                    Redis where configured

  Payment gateway                     Chapa

  API communication                   HTTPS and JSON REST APIs
  -----------------------------------------------------------------------

## Architecture

``` text
Guest QR Menu / Staff Dashboards
              |
              | HTTPS + JSON
              v
        Laravel REST API
              |
     Authentication, roles,
     tenant scoping, validation
              |
     +--------+---------+
     |        |         |
   Orders   Payments   QR / Booking
     |        |         |
     +--------+---------+
              |
       MySQL / PostgreSQL
       Redis (if configured)
```

The frontend is responsible for the user experience. The backend must
validate permissions, tenant boundaries, prices, order totals,
reservation eligibility, and payment results.

## Repository Structure

The repository currently shows the frontend under `Client2/vue-project`
and the backend under `server`. The structure may evolve as the project
develops.

``` text
Restaurant_system2/
├── Client2/
│   └── vue-project/    # Vue frontend
├── server/             # Laravel backend
├── schema.sql          # Database schema / SQL reference
├── SRS.md              # Main software requirements (if present)
└── SRS_MULTI_HOTEL_SUPPORT.md
```

## Getting Started

### Prerequisites

Install the tools required by the actual project configuration:

-   Git
-   Node.js and npm
-   PHP and Composer
-   MySQL or PostgreSQL
-   A compatible web server or local development environment
-   Redis only if the current backend configuration uses it

### 1. Clone the repository

``` bash
git clone https://github.com/ashnafi1426/Restaurant_system2.git
cd Restaurant_system2
```

### 2. Start the frontend

``` bash
cd Client2/vue-project
npm install
```

Copy the frontend environment example file if the project provides one
(for example, `.env.example`) and configure the API base URL using the
variable name expected by the source code.

Start the development server using the script defined in `package.json`,
commonly:

``` bash
npm run dev
```

### 3. Set up the backend

Open a second terminal:

``` bash
cd server
composer install
```

If the backend provides `.env.example`, copy it to `.env` and set the
database, application URL, frontend URL, and other required environment
variables. Then run:

``` bash
php artisan key:generate
php artisan migrate
php artisan serve
```

Run migrations only after configuring the correct database. If the
project relies on an existing database schema or SQL import instead of
migrations, follow the project's current setup instructions and inspect
`schema.sql` first.

### 4. Configure integrations

Configure Chapa credentials and webhook settings only in the backend
environment. Do not commit API keys, passwords, tokens, or production
`.env` files to Git.

> **Note:** These are standard Laravel/Vue setup steps. Use the scripts,
> environment variable names, migrations, and seeders actually present
> in this repository; not every command applies to every project
> configuration.

## Configuration

At minimum, verify the following configuration areas before running the
system:

-   Database connection and migrations/schema.
-   Frontend API base URL and backend CORS settings.
-   Authentication and role permissions.
-   Tenant identification and tenant-scoping middleware.
-   Hotel currency, VAT rate, and service-charge rate.
-   QR token resolution and guest routing.
-   Chapa credentials, callback/return URLs, and webhook signature
    verification.
-   Queue workers, Redis, mail, and browser notifications if enabled.

Keep secrets in environment variables and never expose private payment
credentials in frontend code.

## Core Workflows

### Guest QR Ordering

1.  Guest scans a room, table, or walk-in QR code.
2.  The API resolves the token and its tenant/context.
3.  The guest sees the correct menu and applicable hotel details.
4.  The guest adds items to the cart and reviews the total.
5.  The backend validates availability, prices, taxes, fees, and
    ordering eligibility.
6.  The order is created and routed to the appropriate operational
    workflow.
7.  The guest can track progress using the order reference where
    supported.

### Room Charge

1.  Resolve the room QR and reservation.
2.  Confirm the guest has an eligible active check-in.
3.  Create the order and record the room-charge transaction in the
    backend.
4.  Update the room folio and order status consistently.

### Chapa Payment

1.  The backend initializes the payment.
2.  The guest is redirected to the returned checkout URL.
3.  The backend verifies the transaction using the gateway's
    verification mechanism and webhook validation.
4.  Only a verified successful payment is marked as paid.

## Security and Data Isolation

The system's target security requirements include:

-   Every tenant-owned record must be scoped to the correct hotel.
-   Backend authorization must prevent cross-tenant reads and writes.
-   Validate all request data on the server.
-   Hash passwords securely and use role-based access controls.
-   Use HTTPS in deployed environments.
-   Apply rate limits to public endpoints.
-   Verify payment callbacks/webhooks on the server.
-   Avoid storing sensitive payment credentials in source control.
-   Record important security and administrative events where audit
    logging is implemented.

## Testing and Acceptance Criteria

Before release, verify at least these scenarios:

  -----------------------------------------------------------------------
  Test                                Expected result
  ----------------------------------- -----------------------------------
  Tenant isolation                    A user from Hotel A cannot access
                                      Hotel B's data.

  QR resolution                       A valid QR opens the correct hotel,
                                      room, table, or walk-in context.

  Order totals                        Subtotal, service charge, VAT,
                                      discount, and total match the
                                      configured rules.

  Order placement                     Valid orders are saved and appear
                                      in the relevant operational view.

  Waiter assignment                   Eligible waiters are selected
                                      according to the configured
                                      assignment rules.

  Payment verification                An order is marked paid only after
                                      backend verification.

  Order lifecycle                     Authorized staff can perform
                                      permitted status transitions.

  Complaint workflow                  A complaint can be created and
                                      tracked through resolution where
                                      implemented.

  Responsive UI                       Guest and staff screens work on
                                      mobile, tablet, and desktop sizes.

  Localization                        English and Amharic translations
                                      display correctly where enabled.
  -----------------------------------------------------------------------

Run the project's available test, lint, type-check, and build scripts
before merging changes. Consult `package.json` and the backend test
configuration for the exact commands.

## Internationalization

The SRS specifies English and Amharic (አማርኛ). User-facing labels,
validation messages, order statuses, and payment instructions should use
the project's translation system rather than hard-coded text where
localization is supported.

## Contributing

1.  Create a feature branch.
2.  Keep changes focused and follow the existing Vue/Laravel
    conventions.
3.  Avoid changing API contracts without updating the frontend, backend,
    and SRS as needed.
4.  Add or update tests for business-critical changes.
5.  Run the available checks before committing.
6.  Never commit credentials, customer data, or production environment
    files.

## Project Documentation

-   `SRS.md` --- main Software Requirements Specification, if present.
-   `SRS_MULTI_HOTEL_SUPPORT.md` --- multi-hotel support requirements,
    if present.
-   `schema.sql` --- database schema reference.

Use the SRS as the requirements baseline, but confirm each feature in
the codebase and tests before describing it as fully implemented.

------------------------------------------------------------------------

**Repository:**
[ashnafi1426/Restaurant_system2](https://github.com/ashnafi1426/Restaurant_system2)
