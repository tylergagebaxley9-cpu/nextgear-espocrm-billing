'use strict';
require('dotenv').config({ path: __dirname + '/.env' });

const express = require('express');
const Stripe = require('stripe');
const fetch = require('node-fetch');

const stripe = Stripe(process.env.STRIPE_SECRET_KEY);
const ESPOCRM_URL = process.env.ESPOCRM_URL;
const ESPOCRM_API_KEY = process.env.ESPOCRM_API_KEY;
const WEBHOOK_PORT = process.env.WEBHOOK_PORT || 3001;
const STRIPE_WEBHOOK_SECRET = process.env.STRIPE_WEBHOOK_SECRET;

const app = express();

// Raw body needed for Stripe signature verification
app.use('/stripe-webhook', express.raw({ type: 'application/json' }));
app.use(express.json());

function timestamp() {
  return new Date().toISOString();
}

async function markInvoicePaid(invoiceId) {
  const res = await fetch(`${ESPOCRM_URL}/api/v1/Invoice/${invoiceId}`, {
    method: 'PATCH',
    headers: {
      'X-Api-Key': ESPOCRM_API_KEY,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      status: 'Paid',
      paymentMethod: 'Card/Stripe',
    }),
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`EspoCRM update failed (${res.status}): ${text}`);
  }

  return res.json();
}

app.post('/stripe-webhook', async (req, res) => {
  const sig = req.headers['stripe-signature'];
  let event;

  try {
    event = stripe.webhooks.constructEvent(req.body, sig, STRIPE_WEBHOOK_SECRET);
  } catch (err) {
    console.error(`[${timestamp()}] Webhook signature verification failed: ${err.message}`);
    return res.status(400).send(`Webhook Error: ${err.message}`);
  }

  console.log(`[${timestamp()}] Received event: ${event.type} (id: ${event.id})`);

  const handledEvents = ['checkout.session.completed', 'payment_intent.succeeded'];

  if (handledEvents.includes(event.type)) {
    const obj = event.data.object;
    const metadata = obj.metadata || {};
    const invoiceId = metadata.espocrm_invoice_id;

    if (!invoiceId) {
      console.warn(`[${timestamp()}] No espocrm_invoice_id in metadata for event ${event.id} — skipping`);
      return res.json({ received: true, skipped: true });
    }

    try {
      await markInvoicePaid(invoiceId);
      console.log(`[${timestamp()}] Invoice ${invoiceId} marked as Paid in EspoCRM`);
    } catch (err) {
      console.error(`[${timestamp()}] Failed to update EspoCRM for invoice ${invoiceId}: ${err.message}`);
      return res.json({ received: true, error: err.message });
    }
  } else {
    console.log(`[${timestamp()}] Unhandled event type: ${event.type} — ignoring`);
  }

  res.json({ received: true });
});

app.get('/health', (req, res) => {
  res.json({ status: 'ok' });
});

app.listen(WEBHOOK_PORT, () => {
  console.log(`[${timestamp()}] Stripe webhook server running on port ${WEBHOOK_PORT}`);
  console.log(`  POST http://localhost:${WEBHOOK_PORT}/stripe-webhook`);
  console.log(`  GET  http://localhost:${WEBHOOK_PORT}/health`);
});
