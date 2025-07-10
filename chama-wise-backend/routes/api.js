// routes/api.js
const express = require('express');
const router = express.Router();
const { protect } = require('../middleware/auth');
const {
  getAllCampaigns,
  getCampaign,
  createCampaign,
  joinCampaign
} = require('../controllers/campaignController');
const {
  initiateSTKPush,
  handleCallback
} = require('../controllers/paymentController');
const {
  getUser,
  getUserCampaigns,
  deleteUser
} = require('../controllers/userController');
const {
  getCampaignActivity
} = require('../controllers/activityController');

// Campaign routes
router.get('/campaigns', getAllCampaigns);
router.get('/campaigns/:id', getCampaign);
router.post('/campaigns', protect, createCampaign);
router.post('/campaigns/:id/join', protect, joinCampaign);
router.get('/campaigns/:id/activity', protect, getCampaignActivity);

// Payment routes
router.post('/mpesa/stk-push', protect, initiateSTKPush);
router.post('/mpesa/callback', handleCallback);

// User routes
router.get('/users/:id', getUser);
router.get('/users/:id/campaigns', getUserCampaigns);
router.delete('/users/:id', protect, deleteUser);

module.exports = router;
