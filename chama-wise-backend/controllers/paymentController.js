const Contribution = require('../models/Contribution');
const Campaign = require('../models/Campaign');
const User = require('../models/User');
const { lipaNaMpesaOnline } = require('../services/mpesa');
const { emitToCampaign } = require('../services/socket');
const { client } = require('../config/redis');

// @desc    Initiate M-Pesa STK Push
// @route   POST /api/v1/mpesa/stk-push
// @access  Private
const initiateSTKPush = async (req, res, next) => {
  try {
    const { phone, amount, campaignId } = req.body;

    // Validate campaign
    const campaign = await Campaign.findById(campaignId);
    if (!campaign) {
      return res.status(404).json({
        success: false,
        message: 'Campaign not found'
      });
    }

    // Check if user is a member
    if (!campaign.members.includes(req.user.id)) {
      return res.status(403).json({
        success: false,
        message: 'You must join the campaign before contributing'
      });
    }

    // Initiate payment
    const response = await lipaNaMpesaOnline(
      phone,
      amount,
      campaignId,
      `${process.env.BASE_URL}/api/v1/mpesa/callback`
    );

    // Store pending transaction in Redis (expires in 15 minutes)
    await client.set(
      `mpesa:${response.CheckoutRequestID}`,
      JSON.stringify({
        user: req.user.id,
        campaign: campaignId,
        amount,
        phone
      }),
      { EX: 900 }
    );

    res.status(200).json({
      success: true,
      data: response
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Handle M-Pesa Callback
// @route   POST /api/v1/mpesa/callback
// @access  Public
const handleCallback = async (req, res, next) => {
  try {
    const callbackData = req.body;

    // Verify this is a valid transaction
    const transaction = await client.get(`mpesa:${callbackData.CheckoutRequestID}`);
    if (!transaction) {
      return res.status(400).json({
        success: false,
        message: 'Invalid transaction'
      });
    }

    const { user, campaign, amount, phone } = JSON.parse(transaction);

    if (callbackData.ResultCode === '0') {
      // Payment was successful
      const contribution = await Contribution.create({
        user,
        campaign,
        amount,
        paymentMethod: 'mpesa',
        transactionCode: callbackData.MpesaReceiptNumber,
        phone
      });

      // Update campaign total
      await Campaign.findByIdAndUpdate(campaign, {
        $inc: { currentAmount: amount },
        $push: { contributions: contribution._id }
      });

      // Update user's total contributions
      await User.findByIdAndUpdate(user, {
        $inc: { totalContributions: amount }
      });

      // Notify campaign members
      emitToCampaign(
        campaign,
        'new-contribution',
        {
          userId: user,
          amount,
          transactionCode: callbackData.MpesaReceiptNumber
        }
      );

      // Clear relevant caches
      await client.del(`campaign:${campaign}`);
      await client.del('campaigns:all');
    }

    // Delete the pending transaction
    await client.del(`mpesa:${callbackData.CheckoutRequestID}`);

    res.status(200).json({ success: true });
  } catch (err) {
    next(err);
  }
};

module.exports = {
  initiateSTKPush,
  handleCallback
};
