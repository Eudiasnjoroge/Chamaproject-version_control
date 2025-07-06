const Campaign = require('../models/Campaign');
const User = require('../models/User');
const Contribution = require('../models/Contribution');
const { client } = require('../config/redis');
const { emitToCampaign } = require('../services/socket');
const { lipaNaMpesaOnline } = require('../services/mpesa');

// @desc    Get all campaigns
// @route   GET /api/v1/campaigns
// @access  Public
const getAllCampaigns = async (req, res, next) => {
  try {
    // Check Redis cache first
    const cachedCampaigns = await client.get('campaigns:all');
    if (cachedCampaigns) {
      return res.status(200).json({
        success: true,
        count: JSON.parse(cachedCampaigns).length,
        data: JSON.parse(cachedCampaigns)
      });
    }

    const campaigns = await Campaign.find({ isActive: true })
      .populate('creator', 'name email phone')
      .populate('members', 'name email phone');

    // Cache for 5 minutes
    await client.set('campaigns:all', JSON.stringify(campaigns), { EX: 300 });

    res.status(200).json({
      success: true,
      count: campaigns.length,
      data: campaigns
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Get single campaign
// @route   GET /api/v1/campaigns/:id
// @access  Public
const getCampaign = async (req, res, next) => {
  try {
    const campaign = await Campaign.findById(req.params.id)
      .populate('creator', 'name email phone')
      .populate('members', 'name email phone')
      .populate({
        path: 'contributions',
        populate: { path: 'user', select: 'name email phone' }
      });

    if (!campaign) {
      return res.status(404).json({
        success: false,
        message: 'Campaign not found'
      });
    }

    res.status(200).json({
      success: true,
      data: campaign
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Create new campaign
// @route   POST /api/v1/campaigns
// @access  Private
const createCampaign = async (req, res, next) => {
  try {
    // Check if user has reached campaign limit (3 for non-premium)
    const userCampaigns = await Campaign.countDocuments({ creator: req.user.id });
    if (userCampaigns >= 3 && !req.user.premium) {
      return res.status(400).json({
        success: false,
        message: 'Free users can only create 3 campaigns. Upgrade to premium.'
      });
    }

    const campaign = await Campaign.create({
      ...req.body,
      creator: req.user.id,
      members: [req.user.id] // Creator is automatically a member
    });

    // Add campaign to user's campaigns array
    await User.findByIdAndUpdate(req.user.id, {
      $push: { campaigns: campaign._id }
    });

    // Clear campaigns cache
    await client.del('campaigns:all');

    res.status(201).json({
      success: true,
      data: campaign
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Join campaign
// @route   POST /api/v1/campaigns/:id/join
// @access  Private
const joinCampaign = async (req, res, next) => {
  try {
    const campaign = await Campaign.findById(req.params.id);

    if (!campaign) {
      return res.status(404).json({
        success: false,
        message: 'Campaign not found'
      });
    }

    // Check if user is already a member
    if (campaign.members.includes(req.user.id)) {
      return res.status(400).json({
        success: false,
        message: 'User is already a member of this campaign'
      });
    }

    // Add user to campaign members
    campaign.members.push(req.user.id);
    await campaign.save();

    // Add campaign to user's campaigns array
    await User.findByIdAndUpdate(req.user.id, {
      $push: { campaigns: campaign._id }
    });

    // Notify all members
    emitToCampaign(
      campaign._id,
      'member-joined',
      { userId: req.user.id, userName: req.user.name }
    );

    res.status(200).json({
      success: true,
      data: campaign
    });
  } catch (err) {
    next(err);
  }
};

module.exports = {
  getAllCampaigns,
  getCampaign,
  createCampaign,
  joinCampaign
};
