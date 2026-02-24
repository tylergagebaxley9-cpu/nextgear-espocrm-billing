# EspoCRM Stripe Billing Integration

Generates Stripe Payment Links for invoices and automatically marks them Paid in EspoCRM when payment is received.

## Setup

1. Install dependencies:
   ```
   npm install
   ```

2. Copy the example env file and fill in your keys:
   ```
   cp .env.example .env
   ```

   Edit `.env`:
   ```
   STRIPE_SECRET_KEY=sk_live_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   ESPOCRM_URL=https://subcommendatory-isopetalous-elianna.ngrok-free.dev
   ESPOCRM_API_KEY=6dea655b2ad4d2c4e46d3a128ddebb81
   WEBHOOK_PORT=3001
   ```

## Generate a Payment Link

```bash
node stripe_link.js <invoiceId> <amountInDollars> "<description>"
```

Example:
```bash
node stripe_link.js INV-2026-0001 1200.00 "Web Design Services - January 2026"
```

This will:
- Create a one-time Stripe Price and Payment Link
- Store the `espocrm_invoice_id` in Stripe metadata
- Update the `stripePaymentLink` field on the EspoCRM Invoice record
- Print the payment link URL to the console

## Run the Webhook Server

```bash
node stripe_webhook.js
```

The server listens on `WEBHOOK_PORT` (default 3001) and handles:
- `checkout.session.completed`
- `payment_intent.succeeded`

When a matching event arrives with `espocrm_invoice_id` in the metadata, it PATCHes the EspoCRM Invoice to `status: "Paid"` and `paymentMethod: "Card/Stripe"`.

## Getting Your Stripe Webhook Secret

### Local Testing (Stripe CLI)
```bash
stripe listen --forward-to localhost:3001/stripe-webhook
```
Copy the `whsec_...` secret it prints and put it in your `.env`.

### Production
1. Go to Stripe Dashboard > Developers > Webhooks
2. Add a separate ngrok tunnel to your local server:
   ```
   ngrok http 3001
   ```
   Then add `https://<your-ngrok-id>.ngrok.io/stripe-webhook` in the Stripe dashboard.
3. Copy the signing secret into `.env`.

## Notes

- Card payments only — Cash, Check, Zelle, and CashApp are recorded manually in EspoCRM.
- The EspoCRM ngrok URL is for API access only. The webhook server runs locally and needs its own public URL for Stripe to reach it.
- Health check: `GET http://localhost:3001/health`
