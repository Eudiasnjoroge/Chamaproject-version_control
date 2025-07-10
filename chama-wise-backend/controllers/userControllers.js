const User = require('../models/User');
const Campaign = require('../models/Campaign');
const { client } = require('../config/redis');
const { emitToCampaign } = require('../services/socket');

// @desc    Get user profile
// @route   GET /api/v1/users/:id
// @access  Public
const getUser = async (req, res, next) => {
  try {
    const user = await User.findById(req.params.id)
      .select('-password')
      .populate('campaigns', 'title targetAmount currentAmount deadline');

    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    res.status(200).json({
      success: true,
      data: user
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Get user campaigns
// @route   GET /api/v1/users/:id/campaigns
// @access  Public
const getUserCampaigns = async (req, res, next) => {
  try {
    const campaigns = await Campaign.find({
      members: req.params.id,
      isActive: true
    })
      .populate('creator', 'name email phone')
      .populate('members', 'name email phone');

    res.status(200).json({
      success: true,
      count: campaigns.length,
      data: campaigns
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Delete user account
// @route   DELETE /api/v1/users/:id
// @access  Private (Admin or self)
const deleteUser = async (req, res, next) => {
  try {
    // Check permissions
    if (req.user.id !== req.params.id && req.user.role !== 'admin') {
      return res.status(403).json({
        success: false,
        message: 'Not authorized to delete this user'
      });
    }

    const user = await User.findByIdAndDelete(req.params.id);

    if (!user) {
      return res.status(404).json({
        success: false,
        message: 'User not found'
      });
    }

    // Remove user from all campaigns
    await Campaign.updateMany(
      { members: user._id },
      { $pull: { members: user._id } }
    );

    // Clear caches
    await client.del(`user:${user._id}`);
    await client.del('campaigns:all');

    // Notify affected campaigns
    if (user.campaigns && user.campaigns.length > 0) {
      user.campaigns.forEach(campaignId => {
        emitToCampaign(
          campaignId,
          'member-left',
          { userId: user._id, userName: user.name }
        );
      });
    }

    res.status(200).json({
      success: true,
      data: {}
    });
  } catch (err) {
    next(err);
  }
};

module.exports = {
  getUser,
  getUserCampaigns,
  deleteUser
};
