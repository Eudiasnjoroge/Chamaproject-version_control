const jwt = require('jsonwebtoken');
const { client } = require('../config/redis');

const protect = async (req, res, next) => {
  let token;

  if (
    req.headers.authorization &&
    req.headers.authorization.startsWith('Bearer')
  ) {
    token = req.headers.authorization.split(' ')[1];
  }

  if (!token) {
    return res.status(401).json({ success: false, message: 'Not authorized' });
  }

  try {
    // Verify token
    const decoded = jwt.verify(token, process.env.JWT_SECRET);

    // Check Redis cache first
    const cachedUser = await client.get(`user:${decoded.id}`);
    if (cachedUser) {
      req.user = JSON.parse(cachedUser);
      return next();
    }

    // Fallback to database
    req.user = await User.findById(decoded.id);
    next();
  } catch (err) {
    return res.status(401).json({ success: false, message: 'Not authorized' });
  }
};

const authorize = (...roles) => {
  return (req, res, next) => {
    if (!roles.includes(req.user.role)) {
      return res.status(403).json({
        success: false,
        message: `User role ${req.user.role} is not authorized`
      });
    }
    next();
  };
};

module.exports = { protect, authorize };
