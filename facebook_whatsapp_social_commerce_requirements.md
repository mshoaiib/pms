# Facebook + WhatsApp Social Commerce — MVP Requirements

**Status:** Approved scope. Ready for work breakdown.
**Stack:** Laravel 13 · PHP 8.4 · Filament · MySQL/MariaDB · Redis · Horizon

Every work item in Part E carries a stable ID (`P1-01`, `P3-07`, …). Those IDs are the
unit of planning — one ID becomes one ClickUp task.

---

## Part A — Product Definition

### A1. Goal

A social-commerce system where a customer discovers a product on Facebook, orders it
through a WhatsApp conversation, and staff fulfil the order from a Filament dashboard.

### A2. Primary flow

```text
Admin creates product
   → published as a Facebook post
   → customer sees post, taps through to WhatsApp
   → Meta webhook reaches Laravel
   → menu conversation collects product, variant, quantity, address
   → customer confirms
   → order created and visible in Filament
   → staff advance status
   → customer receives WhatsApp status updates
```

### A3. MVP acceptance

The MVP is done when the A2 flow runs end to end, unattended, without manual database
work at any step. AI/MCP is explicitly **not** part of that bar — it is added only after
this flow is reliable.

---

## Part B — Architecture Decisions

These are settled. Revisit only with a written reason.

| # | Decision | Rationale |
|---|---|---|
| D1 | WhatsApp Cloud API direct through Meta | No Twilio. Removes a vendor, a markup, and a second set of credentials. |
| D2 | Dedicated WhatsApp Business phone number | A personal number cannot be moved to the Business Platform without losing history, and cannot be shared by staff. |
| D3 | Webhook does no business work | Receive → validate signature → persist raw event → dispatch job → return 200. Meta retries aggressively on slow responses. |
| D4 | Redis + Laravel Queue + Horizon | All WhatsApp send/receive, Facebook publishing, and order side effects run as jobs. |
| D5 | Channel-independent core | Facebook, Instagram and WhatsApp are adapters feeding one Conversations layer. See B1. |
| D6 | Domain-oriented app structure | See B2. Business rules live in services and actions, never in controllers, jobs, or Filament resources. |
| D7 | Deterministic menu ordering first | Numbered menus, not natural language. Predictable, testable, no model cost per message. |
| D8 | AI/MCP is an interface, never an authority | Laravel owns products, prices, inventory, orders, customers, status and business rules. MCP tools call Laravel services; they never touch the database. |

### B1. Channel independence

```text
   Facebook     Instagram     WhatsApp
       └─────────────┼─────────────┘
                     ▼
               Conversations
                     ▼
              Automation Engine
                     ▼
                Order Engine
                     ▼
                  Database
```

Instagram (Phase 8) must require no change to the Order Engine.

### B2. Application structure

```text
app/
├── Domain/
│   ├── Products/      { Models, Services, Actions }
│   ├── Orders/        { Models, Services, Actions }
│   ├── Customers/
│   ├── Conversations/
│   └── Automation/
├── Integrations/
│   ├── Meta/{ Facebook, WhatsApp }
│   └── MCP/
├── Jobs/
└── Filament/{ Resources, Pages }
```

---

## Part C — External Prerequisites

Procurement and account setup. C1–C4 block Phase 5; C5–C9 block Phase 3. Nothing here
blocks Phases 1–2, so development starts immediately while these are obtained.

| # | Item | Blocks | Cost |
|---|---|---|---|
| C1 | Facebook account | — | $0 |
| C2 | Facebook Page with full admin access | Phase 5 | $0 |
| C3 | Meta Developer account | Phases 3, 5 | $0 |
| C4 | Meta Business Portfolio owning the Page, WABA, number and app | Phases 3, 5 | $0 |
| C5 | Meta App with Facebook + WhatsApp products configured | Phase 3 | $0 |
| C6 | WhatsApp Business Account (WABA) | Phase 3 | $0 |
| C7 | Dedicated business phone number | Phase 3 | varies |
| C8 | WhatsApp Cloud API access — Phone Number ID, WABA ID, permanent access token | Phase 3 | metered |
| C9 | Production domain and webhook host | Phase 3 | varies |
| C10 | VPS with PHP 8.4+, MySQL/MariaDB, Redis, Composer, Node, Supervisor | Phase 1 deploy | varies |
| C11 | HTTPS certificate via Let's Encrypt — Meta requires a public HTTPS endpoint | Phase 3 | $0 |
| C12 | Git repository | Phase 1 | $0 |

Optional, not MVP: AI API key, courier account, payment gateway, Instagram asset.

The webhook endpoint takes the form `https://<domain>/webhooks/whatsapp`.

### C13. Minimum production topology

One VPS runs Laravel, MySQL, Redis and Horizon workers. No separate servers, no
container orchestration, and no managed search for the MVP.

---

## Part D — Data Model

| Group | Tables |
|---|---|
| Identity | `users` |
| Customers | `customers`, `customer_addresses` |
| Catalogue | `categories`, `products`, `product_variants`, `product_images` |
| Facebook | `facebook_pages`, `facebook_posts` |
| WhatsApp | `whatsapp_accounts`, `whatsapp_contacts` |
| Messaging | `conversations`, `messages` |
| Orders | `orders`, `order_items`, `order_status_histories` |
| Automation | `automation_flows`, `automation_nodes` |
| Infrastructure | `webhook_events` |

### D1. Order lifecycle

```text
PENDING → CONFIRMED → PROCESSING → PACKED → SHIPPED → DELIVERED
PENDING → CANCELLED
```

Transitions are enforced in code, not left to the UI. Every transition writes an
`order_status_histories` row recording actor, from-status, to-status and timestamp.

---

## Part E — Delivery Plan

### Phase 0 — Prerequisites

| ID | Item |
|---|---|
| P0-01 | Register the Facebook account and create the Facebook Page (C1, C2) |
| P0-02 | Create the Meta Developer account (C3) |
| P0-03 | Create the Meta Business Portfolio and attach the Page (C4) |
| P0-04 | Create the Meta App and add the Facebook and WhatsApp products (C5) |
| P0-05 | Create the WhatsApp Business Account (C6) |
| P0-06 | Acquire and verify the dedicated business phone number (C7) |
| P0-07 | Obtain the Phone Number ID, WABA ID and a permanent access token (C8) |
| P0-08 | Register the domain and point DNS at the host (C9) |
| P0-09 | Provision the VPS with PHP 8.4, MySQL, Redis, Composer, Node and Supervisor (C10) |
| P0-10 | Install the TLS certificate and force HTTPS (C11) |
| P0-11 | Create the Git repository and CI baseline (C12) |

### Phase 1 — Core Commerce

Domain layer and database. No UI, no external APIs.

| ID | Item |
|---|---|
| P1-01 | Scaffold `app/Domain` and `app/Integrations` per B2 |
| P1-02 | `categories` migration, model, factory |
| P1-03 | `products` migration, model, factory — name, SKU, description, price, sale price, status, stock, category |
| P1-04 | `product_variants` migration, model, factory — product, SKU, size, colour, price, stock, status |
| P1-05 | `product_images` migration, model, factory |
| P1-06 | `customers` migration, model, factory |
| P1-07 | `customer_addresses` migration, model, factory |
| P1-08 | `orders` migration, model, factory — number, customer, subtotal, delivery fee, total, status |
| P1-09 | `order_items` migration, model, factory — captures price at time of order |
| P1-10 | `order_status_histories` migration, model, factory |
| P1-11 | `OrderStatus` enum with the allowed-transition map from D1 |
| P1-12 | Order number generator producing the `#00183` format |
| P1-13 | `CreateOrder` action — validate stock, compute totals, write items |
| P1-14 | `UpdateOrderStatus` action — enforce transitions, write history |
| P1-15 | `CancelOrder` action — restore stock |
| P1-16 | Inventory service — stock check, reservation, decrement on confirm |
| P1-17 | Pricing service — subtotal, delivery fee, total |
| P1-18 | Catalogue seeder with realistic products and variants |
| P1-19 | Feature tests — order creation, every valid transition, every invalid transition, cancellation restores stock |

### Phase 2 — Filament Dashboard

| ID | Item |
|---|---|
| P2-01 | Category resource |
| P2-02 | Product resource with image upload and variant repeater |
| P2-03 | Product variant management |
| P2-04 | Inventory view with low-stock indicator |
| P2-05 | Customer resource with addresses |
| P2-06 | Order list with per-status filters and search |
| P2-07 | Order detail page — customer, phone, product, variant, quantity, subtotal, delivery, total, status |
| P2-08 | Order status actions — Process, Pack, Ship, Deliver, Cancel — routed through `UpdateOrderStatus` |
| P2-09 | Order status history timeline on the detail page |
| P2-10 | Dashboard widgets showing order counts per status |
| P2-11 | Roles and permissions gating the order status actions |
| P2-12 | Resource tests — create, edit, validation, status actions, authorization |

### Phase 3 — WhatsApp Integration

| ID | Item |
|---|---|
| P3-01 | WhatsApp configuration in `config/services.php` and `.env` keys |
| P3-02 | `whatsapp_accounts` migration, model |
| P3-03 | `whatsapp_contacts` migration, model |
| P3-04 | `conversations` migration, model |
| P3-05 | `messages` migration, model |
| P3-06 | `webhook_events` migration, model |
| P3-07 | `GET /webhooks/whatsapp` — Meta verification handshake (`hub.challenge`) |
| P3-08 | `POST /webhooks/whatsapp` — validate `X-Hub-Signature-256` before anything else |
| P3-09 | Persist the raw webhook event and deduplicate on the Meta message ID |
| P3-10 | Dispatch `ProcessWhatsAppMessage` and return 200 immediately (D3) |
| P3-11 | WhatsApp Cloud API client — send text, list and button messages |
| P3-12 | `SendWhatsAppMessage` job with retry and exponential backoff |
| P3-13 | Contact and conversation resolution service |
| P3-14 | Redis queue connection, Horizon configuration, Supervisor process |
| P3-15 | Tests — invalid signature rejected, replayed message ignored, malformed payload does not 500 |

### Phase 4 — Menu Ordering

| ID | Item |
|---|---|
| P4-01 | Conversation state machine with state persisted on the conversation |
| P4-02 | Welcome and root menu step |
| P4-03 | Product selection step, including "view all products" |
| P4-04 | Size and variant selection step |
| P4-05 | Colour selection step |
| P4-06 | Quantity step with live stock validation |
| P4-07 | Delivery address step, offering saved addresses to returning customers |
| P4-08 | Order summary step — line items, subtotal, delivery, total |
| P4-09 | Confirmation branches — Confirm, Change Product, Change Address, Cancel |
| P4-10 | Invalid input handling and conversation timeout / session expiry |
| P4-11 | Create the order on confirmation via `CreateOrder` (P1-13) |
| P4-12 | End-to-end conversation tests covering the happy path and each branch |

### Phase 5 — Facebook Publishing

| ID | Item |
|---|---|
| P5-01 | `facebook_pages` migration, model, Page connection and token storage |
| P5-02 | `facebook_posts` migration, model, association to the product |
| P5-03 | Post composer and template — title, price, colours, sizes, WhatsApp call to action |
| P5-04 | `PublishFacebookPost` job |
| P5-05 | WhatsApp deep link (`wa.me`) carrying a product reference |
| P5-06 | Attribute an inbound conversation to the originating post |
| P5-07 | Filament resources for Pages and Posts |
| P5-08 | Tests — publish, failure handling, post-to-product attribution |

### Phase 6 — Automation & Notifications

| ID | Item |
|---|---|
| P6-01 | `automation_flows` and `automation_nodes` migrations, models |
| P6-02 | Notification templates for Processing, Shipped and Delivered |
| P6-03 | Send a WhatsApp notification on order status change |
| P6-04 | Submit and use approved WhatsApp message templates; respect the 24-hour session window |
| P6-05 | Queue retry policy and failed-job alerting |
| P6-06 | Webhook event log viewer in Filament |
| P6-07 | Tests — each status change produces the right message exactly once |

### Phase 7 — AI / MCP

Only after Phases 1–6 are reliable in production.

| ID | Item |
|---|---|
| P7-01 | MCP server scaffold for the storefront tools |
| P7-02 | Catalogue tools — `search_products`, `get_product`, `get_product_variants`, `check_stock` |
| P7-03 | Customer tools — `get_customer`, `get_customer_orders` |
| P7-04 | Order tools — `create_order`, `get_order`, `update_order`, `cancel_order`, all delegating to Phase 1 services |
| P7-05 | Route to AI only when the input does not match a menu option |
| P7-06 | Guardrails enforcing D8 — no direct database access from AI paths |
| P7-07 | FAQ handling |
| P7-08 | Tests including authorization and prompt-injection cases |

Target behaviours: *"I need something black for a wedding under Rs. 5000"* resolves to a
`search_products` call; *"can I change my order to size 43"* resolves to `get_order` then
`update_order`, with the write performed by the Laravel service.

### Phase 8 — Instagram

| ID | Item |
|---|---|
| P8-01 | Instagram channel adapter behind the existing Conversations interface |
| P8-02 | Instagram account and contact mapping |
| P8-03 | Instagram publishing |
| P8-04 | Tests proving the Order Engine was not modified (B1) |

---

## Part F — Out of Scope for MVP

Twilio · Shopify · WooCommerce · React frontend · mobile application · Kubernetes ·
microservices · Elasticsearch · separate AI server · courier API · payment gateway.

Each is addable later against a stated business requirement.

## Part G — Future Candidates

Instagram · courier APIs · online payment · COD verification · inventory suppliers ·
accounting · analytics · AI customer support · AI product recommendations · abandoned
order recovery · customer segmentation · automatic Facebook and Instagram posting.
