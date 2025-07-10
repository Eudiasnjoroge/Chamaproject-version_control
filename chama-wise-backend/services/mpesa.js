const axios = require('axios');
const crypto = require('crypto');
const { client } = require('../config/redis');

const generateAccessToken = async () => {
  const credentials = Buffer.from(
    `${process.env.MPESA_CONSUMER_KEY}:${process.env.MPESA_CONSUMER_SECRET}`
  ).toString('base64');

  const response = await axios.get(
    'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
    {
      headers: { Authorization: `Basic ${credentials}` }
    }
  );

  await client.set('mpesa:access_token', response.data.access_token, {
    EX: 3599 // expires in 59 minutes
  });

  return response.data.access_token;
};

const lipaNaMpesaOnline = async (phone, amount, campaignId, callbackUrl) => {
  let token = await client.get('mpesa:access_token');
  if (!token) token = await generateAccessToken();

  const timestamp = new Date()
    .toISOString()
    .replace(/[^0-9]/g, '')
    .slice(0, -3);
  const password = Buffer.from(
    `${process.env.MPESA_SHORTCODE}${process.env.MPESA_PASSKEY}${timestamp}`
  ).toString('base64');

  const response = await axios.post(
    'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
    {
      BusinessShortCode: process.env.MPESA_SHORTCODE,
      Password: password,
      TransactionType: 'CustomerPayBillOnline',
      Amount: amount,
      PartyA: phone,
      PartyB: process.env.MPESA_SHORTCODE,
      PhoneNumber: phone,
      CallBackURL: callbackUrl,
      AccountReference: `CHAMA${campaignId}`,
      TransactionDesc: 'Chamawise Contribution'
    },
    {
      headers: {
        Authorization: `Bearer ${token}`
      }
    }
  );

  return response.data;
};

module.exports = { lipaNaMpesaOnline, generateAccessToken };
