# shopwise-pricing

Prices per SKU, coupons and quotes for Shopwise. Conventions: `AGENTS.md`.

Run it from `shopwise-platform`: `tilt up -- --only=catalog,pricing`, then

```sh
kubectl exec deploy/pricing-http -- php artisan db:seed --force   # demo prices + coupons, once
curl -s 'localhost:8104/api/prices?skus[]=TEE-MER-BLA-M&skus[]=SKL-CST'
curl -s -X POST localhost:8104/api/quotes -H 'Content-Type: application/json' \
  -d '{"lines":[{"sku":"TEE-MER-BLA-M","quantity":2}],"couponCode":"welcome10"}'
```

Admin endpoints (`/api/admin/...`) need an access token with the `admin` role from identity
(`admin@shopwise.test` after seeding identity).
