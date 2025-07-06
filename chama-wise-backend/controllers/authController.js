const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const User = require('../models/User');
const { client } = require('../config/redis');
const { emitToCampaign } = require('../services/socket');

// Generate JWT Token
const generateToken = (userId) => {
  return jwt.sign({ id: userId }, process.env.JWT_SECRET, {
    expiresIn: '30d',
  });
};

// @desc    Register new user
// @route   POST /api/v1/register
// @access  Public
const register = async (req, res, next) => {
  try {
    const { name, email, phone, password, role } = req.body;

    // Validation
    if (!name || !email || !phone || !password) {
      return res.status(400).json({ success: false, message: 'Please fill all fields' });
    }

    // Check if user exists
    const userExists = await User.findOne({ email });
    if (userExists) {
      return res.status(400).json({ success: false, message: 'User already exists' });
    }

    // Create user
    const user = await User.create({
      name,
      email,
      phone,
      password,
      role: role || 'user'
    });

    // Store user in Redis cache
    await client.set(`user:${user._id}`, JSON.stringify(user), {
      EX: 3600 // 1 hour expiration
    });

    return res.status(201).json({
      success: true,
      token: generateToken(user._id),
      user: {
        id: user._id,
        name: user.name,
        email: user.email,
        phone: user.phone,
        role: user.role,
        premium: user.premium
      }
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Authenticate user
// @route   POST /api/v1/login
// @access  Public
const login = async (req, res, next) => {
  try {
    const { email, password } = req.body;

    // Check for user
    const user = await User.findOne({ email }).select('+password');
    if (!user) {
      return res.status(401).json({ success: false, message: 'Invalid credentials' });
    }

    // Check password
    const isMatch = await bcrypt.compare(password, user.password);
    if (!isMatch) {
      return res.status(401).json({ success: false, message: 'Invalid credentials' });
    }

    // Update Redis cache
    await client.set(`user:${user._id}`, JSON.stringify(user), {
      EX: 3600 // 1 hour expiration
    });

    return res.status(200).json({
      success: true,
      token: generateToken(user._id),
      user: {
        id: user._id,
        name: user.name,
        email: user.email,
        phone: user.phone,
        role: user.role,
        premium: user.premium
      }
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Get current user
// @route   GET /api/v1/me
// @access  Private
const getMe = async (req, res, next) => {
  try {
    // Check Redis cache first
    const cachedUser = await client.get(`user:${req.user.id}`);
    if (cachedUser) {
      return res.status(200).json({
        success: true,
        user: JSON.parse(cachedUser)
      });
    }

    // Fallback to database
    const user = await User.findById(req.user.id);
    if (!user) {
      return res.status(404).json({ success: false, message: 'User not found' });
    }

    // Cache the user
    await client.set(`user:${user._id}`, JSON.stringify(user), {
      EX: 3600
    });

    res.status(200).json({
      success: true,
      user
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Update user details
// @route   PUT /api/v1/me
// @access  Private
const updateMe = async (req, res, next) => {
  try {
    const fieldsToUpdate = {
      name: req.body.name,
      email: req.body.email,
      phone: req.body.phone
    };

    const user = await User.findByIdAndUpdate(req.user.id, fieldsToUpdate, {
      new: true,
      runValidators: true
    });

    // Update Redis cache
    await client.set(`user:${user._id}`, JSON.stringify(user), {
      EX: 3600
    });

    // Notify all campaigns this user is part of
    if (user.campaigns && user.campaigns.length > 0) {
      user.campaigns.forEach(campaignId => {
        emitToCampaign(
          campaignId,
          'user-updated',
          { userId: user._id, name: user.name }
        );
      });
    }

    res.status(200).json({
      success: true,
      user
    });
  } catch (err) {
    next(err);
  }
};

// @desc    Upgrade to premium
// @route   PUT /api/v1/upgrade
// @access  Private
const upgradeToPremium = async (req, res, next) => {
  try {
    const user = await User.findByIdAndUpdate(
      req.user.id,
      { premium: true },
      { new: true }
    );

    // Update Redis cache
    await client.set(`user:${user._id}`, JSON.stringify(user), {
      EX: 3600
    });

    res.status(200).json({
      success: true,
      user
    });
  } catch (err) {
    next(err);
  }
};

module.exports = {
  register,
  login,
  getMe,
  updateMe,
  upgradeToPremium
};
