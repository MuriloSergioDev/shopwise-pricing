# shopwise-pricing

Prices per SKU, coupons and quotes. Catalog owns SKUs; pricing learns them from catalog's events and only prices
those. Created from `shopwise-service-template`: structure, checks and how to run PHP are described there and still apply.

## Service facts
- **Owns**: `skus` (read model of catalog SKUs: `sku`, `active`, `changed_at`), `prices` (one per SKU, `price_amount` + `price_currency` via the kit's `MoneyCast`), `coupons` (ULID id, unique uppercase `code`, `percent`/`fixed`, `min_subtotal`, `starts_at`/`ends_at`, `active`). Database `pricing`.
- **Publishes** (key = sku): `pricing.price-changed` on `PUT /api/admin/prices/{sku}`, full state (`schemas/events/pricing/price-changed.v1.json`). Not recorded when amount and currency are unchanged.
- **Consumes** (consumer `catalog-skus`): `catalog.variant-changed` (upsert sku + active) and `catalog.product-published` (each listed variant's sku, active). Both via `App\Actions\RecordSku`, which ignores a state older than the stored `changed_at` (the two topics are not ordered relative to each other).
- **HTTP** (BFF only, checkout from P3) — contract `shopwise-contracts/openapi/pricing.yaml`. Public: `GET /api/prices?skus[]=`, `POST /api/quotes`. Admin (`auth.jwt:admin`): `PUT /api/admin/prices/{sku}`, `GET|POST /api/admin/coupons`, `DELETE /api/admin/coupons/{code}`.
- **REST dependencies**: identity's JWKS (kit `Jwks`, `IDENTITY_JWKS_URL`) for admin routes only.
- **Local URL**: http://localhost:8104 (Tilt port-forward).

## Rules
- Money is integer minor units, single currency `config('pricing.currency')` (USD). Arithmetic stays in `Brick\Money\Money`.
- Percent discounts round half-up to the cent (`Coupon::discountFor`); a discount never exceeds the subtotal.
- Quotes (`app/Queries/PriceQuote`) are read-only: nothing stored, no coupon redeemed, no events. Unpriced lines come back with null amounts and are left out of the subtotal; an invalid coupon is `valid: false` with a reason, never an error.
- Coupon reasons are checked in this order: `unknown` (missing or deactivated), `not_started`, `expired` (`endsAt` is exclusive), `min_subtotal_not_met`.
- Coupon codes are uppercased on write and lookup (case-insensitive). Deactivating keeps the row, so a code is never reused (a duplicate, even of a deactivated coupon, is a 422).
- `PUT /api/admin/prices/{sku}` returns 404 for a SKU not in `skus`; it does not care whether the SKU is active. It answers 200 even for a first price.
- `CouponResource` leaves `description` out when there is none (the contract types it as a non-nullable string).

## Data
- `db:seed` prices every SKU of catalog's demo seeder (28 SKUs; same per-product prices as cart's seeder) and creates coupons `WELCOME10` (10%) and `SAVE5` ($5 off, min subtotal $25). It also writes those SKUs into `skus` with a year-2000 `changed_at`, so pricing works without Kafka and real catalog events still win. Goes through the actions (records `pricing.price-changed`); a second run does nothing.
- Demo SKUs follow catalog's seeder rule (`TEE-MER-BLA-M`), not the contract example's `TEE-MER-BLK-M`.
