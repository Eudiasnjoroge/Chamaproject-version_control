const { client } = require('../config/redis');
const Campaign = require('../models/Campaign');

// @desc    Get campaign activity feed
// @route   GET /api/v1/campaigns/:id/activity
// @access  Private (Campaign members only)
const getCampaignActivity = async (req, res, next) => {
  try {
    // Check if user is a campaign member
    const campaign = await Campaign.findById(req.params.id);
    if (!campaign.members.includes(req.user.id)) {
      return res.status(403).json({
        success: false,
        message: 'Not authorized to view this campaign'
      });
    }

    // Get last 50 activities from Redis
    const activities = await client.lRange(
      `campaign:${req.params.id}:activity`,
      0,
      49
    );

    res.status(200).json({
      success: true,
      data: activities.map(JSON.parse)
    });
  } catch (err) {
    next(err);
  }
};

module.exports = {
  getCampaignActivity
};
