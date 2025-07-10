const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const User = require('../models/userModel');

// ✅ GET /api/users/me — Get current logged-in user
router.get('/me', auth, async (req, res) => {
  try {
    const user = await User.findById(req.user.id).select('-password');
    if (!user) return res.status(404).json({ message: 'User not found' });
    res.json(user);
  } catch (err) {
    res.status(500).json({ message: 'Server error', error: err.message });
  }
});

module.exports = router;
