require('dotenv').config();
const express = require('express');
const mongoose = require('mongoose');
const redis = require('redis');
const app = express();

// Middleware
app.use(express.json());

// Routes
app.use('/api', require('./routes/api'));

// DB and Redis connections
mongoose.connect(process.env.MONGODB_URI);
const redisClient = redis.createClient({ url: process.env.REDIS_URI });
redisClient.connect();

// Start server
const PORT = process.env.PORT || 5000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
