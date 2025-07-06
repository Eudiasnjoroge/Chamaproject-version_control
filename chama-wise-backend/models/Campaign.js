const mongoose = require('mongoose');

const CampaignSchema = new mongoose.Schema({
  title: { type: String, required: true },
  description: { type: String, required: true },
  creator: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
  members: [{ type: mongoose.Schema.Types.ObjectId, ref: 'User' }],
  targetAmount: { type: Number, required: true },
  currentAmount: { type: Number, default: 0 },
  isRecurring: { type: Boolean, default: false },
  frequency: { type: String, enum: ['weekly', 'monthly'], default: 'weekly' },
  deadline: { type: Date },
  isActive: { type: Boolean, default: true },
  createdAt: { type: Date, default: Date.now }
});

CampaignSchema.index({ title: 'text', description: 'text' });

module.exports = mongoose.model('Campaign', CampaignSchema);
