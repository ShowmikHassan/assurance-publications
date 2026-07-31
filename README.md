# Assurance Publications — WordPress theme

Child theme of **[Blocksy](https://creativethemes.com/blocksy/)**, built for
[Assurance Publications](https://assurancepublications.org) — a Bangla
competitive-exam book store running WooCommerce.

Everything the shop needs beyond Blocksy lives here: a custom product card, an
off-canvas cart, a filtered shop archive, a Bangladesh-specific checkout with
tiered courier pricing, and a full set of branded Bangla transactional emails.

**Author:** [Showmik Hassan](https://showmik.com) · [showmik.com](https://showmik.com)

---

## Requirements

| | |
|---|---|
| WordPress | 6.5+ |
| PHP | 7.4+ |
| WooCommerce | 8.0+ (developed against 10.x) |
| Parent theme | Blocksy |

**Companion plugins** — not in this repository, and the theme degrades
gracefully without them:

- `assurance-blocks` — Gutenberg blocks for the homepage.
- `assurance-cod-bkash` — the COD-pays-courier-charge-via-bKash gateway.
- WebToffee *PDF Invoices, Packing Slips* — invoice buttons and email attachments.
- DCoders *bKash* — the payment processor the COD gateway delegates to.

---

## Installation

1. Install and activate Blocksy.
2. Drop this folder into `wp-content/themes/` and activate **Assurance Publications**.
3. Set **Settings → General → Timezone** to `Asia/Dhaka`. The delivery-date
   estimate has a 6 PM cut-off and reads the site timezone.
4. **WooCommerce → Settings → Shipping** → use the one-click
   *"Set up Bangladesh courier charge"* button the theme adds.
5. Leave the WooCommerce email subject/heading fields empty to inherit the
   Bangla defaults; anything typed there wins.

On a host with full-page caching, exclude `/cart`, `/checkout` and
`/my-account`. The theme sends `DONOTCACHEPAGE` and no-cache headers for those
routes, but an explicit exclusion is worth having.

---

## Architecture

Each module is a file in `inc/`, loaded by `assurance_load()` in
`functions.php`. WooCommerce-dependent modules load only when WooCommerce is
active, so deactivating it degrades the site rather than fataling it.

```
functions.php          Constants + module loader
inc/
  setup.php            Per-template asset loading, Blocksy interop, cache headers
  helpers.php          Formatting + shared render utilities, branded HTML mail
  icons.php            Inline SVG sprite (no icon font, no extra request)
  product-card.php     The card renderer used by every grid on the site
  ajax.php             All wp_ajax_* endpoints
  mini-cart.php        Off-canvas cart + WooCommerce fragments
  shop-filters.php     Archive filtering, price range, AJAX results
  single-product.php   Gallery, tabs, read-later
  cart.php             Cart page, suggestions carousel, shipping calculator
  checkout.php         Field trimming, payment tiles, delivery dates
  shipping.php         Inside/Outside Dhaka tiered courier rates
  districts.php        64 BD districts, Bangla labels
  read-later.php       "একটু পরে দেখুন" list
  emails.php           Bangla transactional email copy + PDF invoice wiring
```

Assets load **per template**, not globally — the checkout never parses
shop-filter CSS. Only `tokens.css`, the product card and the cart drawer are
global, because a card or the cart can appear on any page.

### Design tokens

`assets/css/tokens.css` is the single source of truth for colour, type scale,
spacing and radii, and it also feeds Blocksy's own CSS variables so the parent
theme inherits the palette. Email templates repeat the hex values by hand —
mail clients cannot read custom properties.

### Template overrides

`woocommerce/` overrides only what needed changing. Notable ones:

- `checkout/payment.php` — renders the method tiles and terms row **only**.
  The order button and payment breakdown are rendered separately into the
  summary column and refreshed through their own AJAX fragment.
- `checkout/payment-method.php` — the radio is hidden and the whole tile is
  the control.
- `cart/shipping-calculator.php` — reduced to the district dropdown.
- `emails/*` — the full branded Bangla email set.

---

## Domain rules

### Courier pricing

| Destination | 1 book | 2+ books |
|---|---|---|
| Inside Dhaka | ৳70 | ৳70 + ৳10 × qty |
| Outside Dhaka | ৳100 | ৳100 + ৳10 × qty |

Free over ৳2,000 subtotal. Defined once in `inc/shipping.php`
(`assurance_courier_cost()`) and reused by the cart, the checkout and the COD
gateway, so the three can never disagree.

### Delivery estimate

Orders after the 6 PM cut-off shift a day. Inside Dhaka is +1…+2 days, outside
+2…+3. Both the cut-off (`assurance_delivery_cutoff_hour`) and the offsets
(`assurance_delivery_day_offsets`) are filterable.

### Cash on delivery

COD collects the **courier charge up front via bKash** and the book price at
the door. The checkout says so explicitly — the order button and the summary
show what is being charged *now*, not the order total, because those differ.

---

## AJAX

Endpoints are registered through `assurance_ajax()` for both logged-in and
logged-out users, so guests can shop. The nonce is therefore a **CSRF control
only** and never treated as proof of identity. Every handler independently:

1. Verifies the nonce.
2. Re-derives every price and stock level from the database — nothing
   money-related is read from the request.
3. Validates that the product is published, visible and purchasable.
4. Escapes on output.

`ap_refresh_nonce` deliberately does not verify a nonce: it exists for the case
where the one printed into the page is already stale, which happens on any host
with full-page caching. `core.js` retries once with a fresh token, so a cached
page does not leave Add-to-cart dead.

---

## Third-party issues worked around

- **bKash plugin** — its `submit_error()` starts with
  `loader.style.display = 'none'`, but `loader` is only assigned in the popup
  flow. On the redirect flow it throws on any validation failure, killing
  WooCommerce's error rendering. Its object is a `const` inside an IIFE and
  cannot be patched, so `checkout.js` hooks `$.ajaxPrefilter` — which survives
  the throw — and renders the errors itself.
- **bKash plugin** — mis-declares `Processor::get_instance()` as returning
  `Processor`, and two `create_payment()` string parameters as `bool`.
- **Blocksy** — replaces the WooCommerce shipping totals row entirely; there is
  no `<th>`, and the label is a `.ct-shipping-heading` div.

---

## Accessibility

- Focus is trapped in the cart drawer, the filter panel and the policy modal,
  and restored to the trigger on close.
- The payment radios stay in the accessibility tree and the tab order; only
  their visual box is hidden.
- Checkout error messages are `role="button"` + `tabindex="0"` and jump to the
  field they describe, by keyboard as well as pointer.
- Icons are `aria-hidden` unless they carry the only label.
- Animation is dropped under `prefers-reduced-motion`.

---

## Conventions

- No build step. Plain ES5-compatible JS against `window.AP`, plain CSS with
  custom properties. Files can be edited on the server.
- Logical CSS properties (`inline-size`, `margin-block-start`) throughout.
- Comments explain **why**, not what — particularly where a rule exists to beat
  a parent-theme or plugin selector.
- All user-facing strings are translatable under the `assurance` text domain.

---

## Credits

Designed and developed by **[Showmik Hassan](https://showmik.com)**
— [showmik.com](https://showmik.com) · [github.com/ShowmikHassan](https://github.com/ShowmikHassan)

## Licence

GPL-2.0-or-later, matching WordPress and the parent theme.
