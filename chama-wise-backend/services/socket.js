const socketIO = require('socket.io');
const { client } = require('../config/redis');

let io;

const init = (server) => {
  io = socketIO(server, {
    cors: {
      origin: process.env.FRONTEND_URL,
      methods: ['GET', 'POST']
    }
  });

  io.on('connection', (socket) => {
    console.log('New client connected');

    socket.on('joinCampaign', (campaignId) => {
      socket.join(campaignId);
      console.log(`User joined campaign room: ${campaignId}`);
    });

    socket.on('disconnect', () => {
      console.log('Client disconnected');
    });
  });

  return io;
};

const emitToCampaign = (campaignId, event, data) => {
  io.to(campaignId).emit(event, data);
};

module.exports = { init, emitToCampaign };const socketIO = require('socket.io');
const { client } = require('../config/redis');

let io;

const init = (server) => {
  io = socketIO(server, {
    cors: {
      origin: process.env.FRONTEND_URL,
      methods: ['GET', 'POST']
    }
  });

  io.on('connection', (socket) => {
    console.log('New client connected');

    socket.on('joinCampaign', (campaignId) => {
      socket.join(campaignId);
      console.log(`User joined campaign room: ${campaignId}`);
    });

    socket.on('disconnect', () => {
      console.log('Client disconnected');
    });
  });

  return io;
};

const emitToCampaign = (campaignId, event, data) => {
  io.to(campaignId).emit(event, data);
};

module.exports = { init, emitToCampaign };
