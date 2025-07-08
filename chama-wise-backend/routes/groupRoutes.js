const express = require('express');
const router = express.Router();

const groupController = require('../controllers/groupController');
const auth = require('../middleware/auth'); // ✅ backend middleware!

router.post('/', auth, groupController.createGroup);
router.get('/', auth, groupController.getGroups);
router.get('/:id', auth, groupController.getGroup);
router.post('/:id/invite', auth, groupController.inviteMember);
router.get('/:id/members', auth, groupController.getGroupMembers);
router.post('/:id/remove', auth, groupController.removeMember);
router.post('/:id/leave', auth, groupController.leaveGroup);

module.exports = router;
