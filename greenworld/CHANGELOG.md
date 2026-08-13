# Changelog — GreenWorld Wellness

All notable changes to this theme are documented here. Versioning: each release bumps the `Version` header in `style.css`.

## 1.0.0 — Initial release
Premium, classic health & wellness WooCommerce theme for Green World Health Solutions.

### Design
- New botanical-green + warm-ivory design system with brass accents (`theme.json` + `assets/css/main.css`).
- Editorial typography: Fraunces (display serif) + Inter (body), preconnected with `display=swap`.
- Restrained single hero (no random slider), generous whitespace, subtle motion, elegant product and category cards.

### Navigation & interaction
- Multi-level header: thin utility bar, main bar (logo, large AJAX search, account/wishlist/cart), and a primary nav with a data-driven **health mega menu**.
- Off-canvas mobile drawer, sticky header, mini-cart drawer, quick view, filters drawer, load-more, sticky add-to-cart, wishlist (localStorage), back-to-top, WhatsApp button and a mobile bottom navigation bar.
- All interactivity is vanilla, deferred and dependency-free for speed.

### Commerce
- Premium WooCommerce loop and single-product presentation: sale/new/out-of-stock badges, trust signals, delivery estimate, Ingredients and How-to-Use tabs, WhatsApp order button, Merchant-Center identifiers.
- Reimagined homepage: trust strip, Shop by Health Category, Featured Products, Best Sellers, Customer/Distributor join band, Wellness Collections, consultation band, Why Choose Us, Journal, health disclaimer and newsletter.

### Accounts
- Dual **Customer / Distributor** registration with a distributor role, applicant fields (phone, county, sponsor/referral), admin notification and a Users "Account type" column.

### Health services
- Online **Health Consultation** intake: consent-gated form, private `gw_consultation` post type, AJAX submission and owner notification, framed as guidance not diagnosis.
- Site-wide and per-product **Health Information Disclaimer**.

### Foundations
- PSR-4 module container, JSON-LD schema (Organization, WebSite, Store, Product, Breadcrumb) that yields to SEO plugins, OWASP security headers, Core Web Vitals optimizer, setup wizard, and starter content (pages, categories, demo products) tailored for Kenya (KES, M-Pesa, Pay on Delivery).
