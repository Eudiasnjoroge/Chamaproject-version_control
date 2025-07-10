// const express = require('express');
// const router = express.Router();
// const {
//   createContribution,
//   getGroupContributions,
//   getMyContributions,
//   getAllGroupContributions,
//   getGroupProgress
// } = require('../controllers/contributionController');
// const auth = require('../middleware/auth');

// router.post('/', auth, createContribution);
// router.get('/:groupId', auth, getGroupContributions); // may be deprecated later
// router.get('/:groupId/my', auth, getMyContributions); // user's own
// router.get('/:groupId/all', auth, getAllGroupContributions); // admin only
// router.get('/:groupId/progress', auth, getGroupProgress); // group stats

// module.exports = router;





const express = require('express');
const router = express.Router();
const auth = require('../middleware/auth');
const {
  createContribution,
  getAllGroupContributions,
  getMyContributions,
  getGroupProgress
} = require('../controllers/contributionController');

router.post('/', auth, createContribution);
router.get('/:groupId/all', auth, getAllGroupContributions);
router.get('/:groupId/my', auth, getMyContributions);
router.get('/:groupId/progress', auth, getGroupProgress);

module.exports = router;
