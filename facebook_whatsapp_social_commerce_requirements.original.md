# Facebook + WhatsApp Social Commerce — MVP Requirements

## 1. Project Overview

A Laravel-based social-commerce system where:

```text
Facebook Product Post
        ↓
Customer
        ↓
WhatsApp
        ↓
WhatsApp Webhook
        ↓
Laravel Application
        ↓
Menu-Based Ordering
        ↓
Order Confirmation
        ↓
Filament Dashboard
        ↓
Order Processing
```

The first version will support **Facebook + WhatsApp**.

Instagram can be added later without changing the core order system.

---

# 2. Required External Accounts / Services

## 2.1 Facebook Page — REQUIRED

A Facebook Page is required for publishing product-related posts.

The Page will eventually be connected to the Laravel application through Meta APIs.

Required:

- Facebook account
- Facebook Page
- Admin/full access to the Page

---

## 2.2 Meta Developer Account — REQUIRED

Create a Meta Developer account to create and manage the Meta application.

The Meta application will provide access to the required APIs and webhooks.

The application will eventually contain/configure:

- Facebook integration
- WhatsApp integration
- Webhooks
- Access tokens
- Required permissions

---

## 2.3 Meta Business Portfolio — REQUIRED

A Meta Business Portfolio is required to manage the business assets used by the system.

It will be used to manage/connect assets such as:

- Facebook Page
- WhatsApp Business Account
- Business phone number
- Meta application

---

# 3. WhatsApp Business Platform — REQUIRED

Use the **WhatsApp Business Platform / WhatsApp Cloud API directly through Meta** for the initial implementation.

Twilio is NOT required for this architecture.

The Laravel application will communicate with WhatsApp through Meta's API.

### Required WhatsApp components

- WhatsApp Business Account
- WhatsApp Business phone number
- WhatsApp Cloud API
- Phone Number ID
- WhatsApp Business Account ID
- Access token
- Webhook configuration

---

# 4. Dedicated Business Phone Number — REQUIRED

Use a dedicated phone number for the WhatsApp Business API.

Do not build the production system around a personal WhatsApp number.

Architecture:

```text
Business Phone Number
        ↓
WhatsApp Business Platform
        ↓
Meta
        ↓
Laravel Webhook
```

---

# 5. Hosting — REQUIRED

A production Laravel server is required.

The server should support:

- PHP 8.4+
- Laravel 13
- MySQL/MariaDB
- Redis
- Composer
- Node.js/npm if required for asset compilation
- Supervisor or an equivalent process manager
- HTTPS

The following can run on the same VPS initially:

```text
Laravel
MySQL
Redis
Queue Workers
Laravel Horizon
```

No separate servers are required for the MVP.

---

# 6. Domain — REQUIRED

A domain is required for the production application and webhook endpoints.

Example:

```text
https://example.com
```

Webhook example:

```text
https://example.com/webhooks/whatsapp
```

---

# 7. SSL / HTTPS — REQUIRED

WhatsApp and Meta webhooks require a publicly accessible HTTPS endpoint.

Use Let's Encrypt for the initial deployment.

Cost:

```text
$0
```

---

# 8. Database — REQUIRED

Use MySQL or MariaDB.

The database should contain at least:

```text
users

customers
customer_addresses

categories
products
product_variants
product_images

facebook_pages
facebook_posts

whatsapp_accounts
whatsapp_contacts

conversations
messages

orders
order_items
order_status_histories

automation_flows
automation_nodes

webhook_events
```

---

# 9. Redis — RECOMMENDED

Redis should be used for:

- Laravel queues
- Background jobs
- Caching
- WhatsApp message processing
- API jobs
- Facebook publishing jobs

Recommended stack:

```text
Redis
+
Laravel Queue
+
Laravel Horizon
```

Redis can run on the same VPS.

---

# 10. Laravel Queue — REQUIRED FOR PRODUCTION

Do not process the complete WhatsApp conversation inside the webhook HTTP request.

Recommended flow:

```text
WhatsApp
   ↓
Webhook
   ↓
Validate Request
   ↓
Store Webhook Event
   ↓
Dispatch Queue Job
   ↓
ProcessWhatsAppMessage
   ↓
Conversation Engine
   ↓
Send Response
```

Example jobs:

```text
ProcessWhatsAppMessage
SendWhatsAppMessage
PublishFacebookPost
ProcessOrder
UpdateOrderStatus
```

---

# 11. Laravel + Filament

The main application will be Laravel with Filament for the administration dashboard.

Suggested dashboard sections:

```text
Dashboard

Products
Categories
Product Variants
Inventory

Customers
Conversations
Messages

Orders
Order Status History

Facebook
    Pages
    Posts

WhatsApp
    Accounts
    Contacts
    Conversations

Automation
    Flows
    Nodes

Settings
```

---

# 12. Product Management

Products should be created and managed from the Laravel dashboard.

Example:

```text
Product
--------------------
Name
SKU
Description
Price
Sale Price
Status
Stock
Category
Images
```

For products with variations:

```text
Product Variant
--------------------
Product
SKU
Size
Color
Price
Stock
Status
```

Example:

```text
Nike Air Max

Size: 42
Color: Black
Stock: 12
Price: Rs. 8,999
```

---

# 13. Facebook Product Posting

The system should eventually allow an administrator to publish product posts to Facebook.

Example:

```text
🔥 Nike Air Max

Price: Rs. 8,999

Available:
Black / White
Sizes 40–45

Order through WhatsApp
```

Each post should be associated internally with the Laravel product.

Example:

```text
Facebook Post
      ↓
Product ID
      ↓
Laravel Product
```

---

# 14. WhatsApp Webhook

Create a Laravel webhook endpoint:

```text
POST /webhooks/whatsapp
```

The webhook should:

1. Receive Meta webhook events.
2. Validate the webhook.
3. Store the event.
4. Identify the WhatsApp contact.
5. Identify/create the conversation.
6. Store the message.
7. Dispatch processing to the queue.
8. Generate the appropriate response.
9. Send the response through WhatsApp Cloud API.

---

# 15. Menu-Based Ordering

The first version should use deterministic menu-based ordering.

Example:

```text
Customer:
Hi

Bot:
👋 Welcome to ABC Store.

What would you like to order?

1. Nike Air Max
2. Adidas Superstar
3. T-Shirts
4. View all products
```

Customer:

```text
1
```

System:

```text
Nike Air Max

Price: Rs. 8,999

Select size:

1. 40
2. 41
3. 42
4. 43
5. 44
6. 45
```

Customer:

```text
42
```

System:

```text
Select color:

1. Black
2. White
```

Then:

```text
Select quantity:
```

Then:

```text
Enter delivery address:
```

Finally:

```text
Order Summary

Nike Air Max
Size: 42
Color: Black
Quantity: 2

Subtotal: Rs. 17,998
Delivery: Rs. 250

Total: Rs. 18,248

1. Confirm Order
2. Change Product
3. Change Address
4. Cancel
```

---

# 16. Order Lifecycle

Initial order lifecycle:

```text
PENDING
   ↓
CONFIRMED
   ↓
PROCESSING
   ↓
PACKED
   ↓
SHIPPED
   ↓
DELIVERED
```

Alternative:

```text
PENDING
   ↓
CANCELLED
```

The dashboard should allow authorized staff to update order status.

---

# 17. Admin Dashboard

The dashboard should show order statistics.

Example:

```text
Orders

New             24
Confirmed       18
Processing      31
Packed           9
Shipped         12
Delivered       87
Cancelled        6
```

Order detail:

```text
Order #00183

Customer:
Muhammad

Phone:
0300xxxxxxx

Product:
Nike Air Max

Size:
42

Color:
Black

Quantity:
2

Subtotal:
Rs. 17,998

Delivery:
Rs. 250

Total:
Rs. 18,248

Status:
Confirmed
```

Actions:

```text
Process
Pack
Ship
Deliver
Cancel
```

---

# 18. Customer Notifications

Order status changes can automatically send WhatsApp messages.

### Processing

```text
Your order #00183 is now being processed.
```

### Shipped

```text
Your order #00183 has been shipped.
```

### Delivered

```text
Your order #00183 has been delivered.
Thank you for shopping with us!
```

---

# 19. MCP / AI

MCP and AI should NOT control the core business logic.

Laravel should remain responsible for:

- Product data
- Prices
- Inventory
- Orders
- Customer data
- Order status
- Business rules

AI/MCP should act as an intelligent interface to Laravel services.

Architecture:

```text
WhatsApp
    ↓
Laravel Conversation Engine
    ↓
AI Agent
    ↓
MCP Tools
    ↓
Laravel Services
    ↓
Database
```

Possible MCP tools:

```text
search_products
get_product
get_product_variants
check_stock
get_customer
get_customer_orders
create_order
get_order
update_order
cancel_order
```

---

# 20. AI Use Cases

AI should handle unexpected/natural-language requests that do not fit the menu.

Example:

```text
Customer:

I need something black for a wedding under Rs. 5000.
```

AI can call:

```text
search_products(
    category="clothing",
    color="black",
    max_price=5000
)
```

Another example:

```text
Customer:

Can I change my order to size 43?
```

AI can call:

```text
get_order()
update_order()
```

The actual database modification must still be performed by Laravel business services.

---

# 21. Recommended Architecture

The system should be channel-independent.

Instead of building:

```text
Facebook → Orders
```

build:

```text
                 Facebook
                    │
                 Instagram
                    │
                 WhatsApp
                    │
                    ▼
              Conversations
                    │
                    ▼
            Automation Engine
                    │
                    ▼
               Order Engine
                    │
                    ▼
              Laravel Database
```

This makes it possible to add Instagram later without rebuilding the order system.

---

# 22. Suggested Laravel Structure

```text
app/
├── Domain/
│   ├── Products/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Actions/
│   │
│   ├── Orders/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Actions/
│   │
│   ├── Customers/
│   │
│   ├── Conversations/
│   │
│   └── Automation/
│
├── Integrations/
│   ├── Meta/
│   │   ├── Facebook/
│   │   └── WhatsApp/
│   │
│   └── MCP/
│
├── Jobs/
│   ├── ProcessWhatsAppMessage.php
│   ├── SendWhatsAppMessage.php
│   └── PublishFacebookPost.php
│
└── Filament/
    ├── Resources/
    │   ├── Products/
    │   ├── Orders/
    │   ├── Customers/
    │   └── Conversations/
    │
    └── Pages/
```

---

# 23. Things NOT Required for the MVP

Do NOT add these initially:

```text
Twilio
Shopify
WooCommerce
React frontend
Mobile application
Kubernetes
Microservices
Elasticsearch
Separate AI server
Courier API
Payment gateway
```

They can be added later when there is a business requirement.

---

# 24. Optional Future Integrations

After the core system works:

```text
Instagram
Courier APIs
Online Payment
COD Verification
Inventory Suppliers
Accounting
Analytics
AI Customer Support
AI Product Recommendations
Abandoned Order Recovery
Customer Segmentation
Automatic Facebook Posting
Automatic Instagram Posting
```

---

# 25. MVP External Requirements Checklist

Before development/deployment, obtain:

- [ ] Domain
- [ ] VPS/Hosting
- [ ] Facebook Page
- [ ] Meta Developer Account
- [ ] Meta Business Portfolio
- [ ] Meta App
- [ ] WhatsApp Business Account
- [ ] Dedicated WhatsApp Business phone number
- [ ] WhatsApp Cloud API access/configuration
- [ ] HTTPS/SSL
- [ ] MySQL/MariaDB
- [ ] Redis
- [ ] Git repository

Optional:

- [ ] AI API key
- [ ] Courier account/API
- [ ] Payment gateway
- [ ] Instagram integration

---

# 26. MVP Development Order

Build the system in this order:

## Phase 1 — Core Commerce

```text
Products
Categories
Variants
Inventory
Customers
Orders
Order Status
```

## Phase 2 — Filament Dashboard

```text
Product management
Customer management
Order management
Order processing
```

## Phase 3 — WhatsApp

```text
Meta App
WhatsApp Cloud API
Webhook
Contacts
Conversations
Messages
```

## Phase 4 — Menu Ordering

```text
Product selection
Variant selection
Quantity
Customer information
Address
Order summary
Order confirmation
```

## Phase 5 — Facebook

```text
Facebook Page connection
Product posting
Post → Product association
WhatsApp CTA
```

## Phase 6 — Automation

```text
Order status notifications
WhatsApp notifications
Queue workers
Retries
Webhook event logging
```

## Phase 7 — AI/MCP

```text
Product search
Natural language product requests
Customer lookup
Order lookup
Order modifications
FAQ
```

## Phase 8 — Instagram

Add Instagram as another channel using the same:

```text
Product
Customer
Conversation
Order
Automation
```

architecture.

---

# 27. Minimum Production Stack

The initial production environment can be:

```text
                    VPS
                     │
          ┌──────────┼──────────┐
          │          │          │
       Laravel     MySQL      Redis
          │                     │
          │                 Queue/Horizon
          │
          ├──────── Meta APIs
          │
          └──────── WhatsApp
```

External:

```text
Facebook Page
Meta Developer
Meta Business
WhatsApp Business
Business Phone Number
```

Optional:

```text
AI API
Courier API
Payment Gateway
```

---

# 28. Initial Goal

The first complete working flow should be:

```text
Admin creates product
        ↓
Product published on Facebook
        ↓
Customer sees product
        ↓
Customer contacts WhatsApp
        ↓
WhatsApp webhook reaches Laravel
        ↓
Laravel identifies product/customer
        ↓
Menu guides customer through order
        ↓
Customer confirms
        ↓
Laravel creates order
        ↓
Order appears in Filament
        ↓
Admin confirms/processes order
        ↓
Customer receives WhatsApp status updates
```

This is the core MVP.

AI/MCP should be added after this flow works reliably.
