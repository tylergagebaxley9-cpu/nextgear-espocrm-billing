'use strict';
require('dotenv').config({ path: __dirname + '/.env' });

const Stripe = require('stripe');
const fetch = require('node-fetch');

const stripe = Stripe(process.env.STRIPE_SECRET_KEY);
const ESPOCRM_URL = process.env.ESPOCRM_URL;
const ESPOCRM_API_KEY = process.env.ESPOCRM_API_KEY;

async function updateEspoCRM(invoiceId, paymentLinkUrl) {
  const res = await fetch(`${ESPOCRM_URL}/api/v1/Invoice/${invoiceId}`, {
    method: 'PATCH',
    headers: {
      'X-Api-Key': ESPOCRM_API_KEY,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ stripePaymentLink: paymentLinkUrl }),
  });

  if (!res.ok) {
    const text = await res.text();
    throw new Error(`EspoCRM update failed (${res.status}): ${text}`);
  }

  return res.json();
}

async function main() {
  const [,, invoiceId, amountStr, ...descParts] = process.argv;
  const description = descParts.join(' ');

  if (!invoiceId || !amountStr) {
    console.error('Usage: node stripe_link.js <invoiceId> <amountInDollars> "<description>"');
    process.exit(1);
  }

  const amount = parseFloat(amountStr);
  if (isNaN(amount) || amount <= 0) {
    console.error('Error: Amount must be a positive number (in dollars, e.g. 1200.00)');
    process.exit(1);
  }

  const amountCents = Math.round(amount * 100);
  const formattedAmount = amount.toLocaleString('en-US', { style: 'currency', currency: 'USD' });

  console.log(`Generating Stripe Payment Link for Invoice ${invoiceId}...`);
  console.log(`Amount: ${formattedAmount} USD`);

  try {
    console.log('Creating Stripe price...');
    const price = await stripe.prices.create({
      currency: 'usd',
      unit_amount: amountCents,
      product_data: {
        name: description || `Invoice ${invoiceId}`,
      },
    });

    console.log('Creating payment link...');
    const paymentLink = await stripe.paymentLinks.create({
      line_items: [{ price: price.id, quantity: 1 }],
      metadata: {
        espocrm_invoice_id: invoiceId,
      },
    });

    console.log(`\u2713 Payment Link: ${paymentLink.url}`);

    console.log('Updating EspoCRM Invoice record...');
    await updateEspoCRM(invoiceId, paymentLink.url);
    console.log('\u2713 Updated EspoCRM Invoice record');

  } catch (err) {
    console.error('\nError:', err.message);
    process.exit(1);
  }
}

main();
