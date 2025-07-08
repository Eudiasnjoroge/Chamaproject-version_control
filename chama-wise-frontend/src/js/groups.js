import { getToken, getUser } from './utils.js';

// Utility function to format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat('en-KE', { style: 'currency', currency: 'KES' }).format(amount);
}

// Utility function to format date
function formatDate(dateString) {
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('en-US', options);
}

export async function loadGroups() {
  try {
    const res = await fetch('http://localhost:5000/api/groups', {
      headers: { Authorization: `Bearer ${getToken()}` }
    });

    const data = await res.json();
    if (!res.ok) return showAlert(data.message || 'Failed to load groups', 'error');

    const currentUser = await getUser();
    const container = document.getElementById('groups-list');
    const fullContainer = document.getElementById('groups-list-full');
    container.innerHTML = '';
    if (fullContainer) fullContainer.innerHTML = '';

    const groupSelect = document.getElementById('contribution-group-id');
    if (groupSelect) {
      groupSelect.innerHTML = '<option disabled selected>Select a group</option>';
    }

    // Update groups count
    document.getElementById('groups-count').textContent = data.length;

    data.forEach(group => {
      const groupCard = document.createElement('div');
      groupCard.className = 'group-card';
      
      const isAdmin = group.admin === currentUser._id;
      const progressPercentage = Math.min(Math.round((group.totalContributions / group.targetAmount) * 100), 100);
      
      groupCard.innerHTML = `
        <div class="group-header">
          <h4>${group.name}</h4>
          ${isAdmin ? '<span class="admin-badge">Admin</span>' : ''}
        </div>
        <p class="group-description">${group.description || 'No description provided'}</p>
        
        <div class="progress-container">
          <div class="progress-bar" style="width: ${progressPercentage}%"></div>
          <span class="progress-text">${progressPercentage}%</span>
        </div>
        
        <div class="group-stats">
          <div class="stat-item">
            <i class="fas fa-bullseye"></i>
            <span>${group.goal || 'General Savings'}</span>
          </div>
          <div class="stat-item">
            <i class="fas fa-money-bill-wave"></i>
            <span>${formatCurrency(group.monthlyContribution)} monthly</span>
          </div>
          <div class="stat-item">
            <i class="fas fa-trophy"></i>
            <span>${formatCurrency(group.targetAmount)} target</span>
          </div>
        </div>
        
        <div class="group-actions">
          ${isAdmin ? `
            <div class="invite-section">
              <input type="email" placeholder="Invite by email" class="invite-input" id="invite-${group._id}" />
              <button class="btn-sm invite-btn" data-id="${group._id}">
                <i class="fas fa-user-plus"></i> Invite
              </button>
            </div>
          ` : ''}
          
          <div class="action-buttons">
            <button class="btn-sm view-members-btn" data-id="${group._id}" data-admin="${group.admin}">
              <i class="fas fa-users"></i> Members
            </button>
            
            <button class="btn-sm view-my-contributions-btn" data-id="${group._id}">
              <i class="fas fa-coins"></i> My Contributions
            </button>
            
            <button class="btn-sm view-progress-btn" data-id="${group._id}">
              <i class="fas fa-chart-line"></i> Progress
            </button>
            
            ${group.admin === currentUser._id ? `
              <button class="btn-sm view-all-contributions-btn" data-id="${group._id}">
                <i class="fas fa-list"></i> All Contributions
              </button>
              <button class="btn-sm view-activity-btn" data-id="${group._id}">
                <i class="fas fa-bell"></i> Activity
              </button>
            ` : ''}
            
            ${group.admin !== currentUser._id ? `
              <button class="btn-sm danger leave-btn" data-id="${group._id}">
                <i class="fas fa-sign-out-alt"></i> Leave
              </button>
            ` : ''}
          </div>
        </div>
        
        <div class="member-list-container" id="member-list-${group._id}"></div>
        <div class="contributions-container" id="contributions-${group._id}"></div>
      `;

      if (container) container.appendChild(groupCard.cloneNode(true));
      if (fullContainer) fullContainer.appendChild(groupCard);
      
      if (groupSelect) {
        const option = document.createElement('option');
        option.value = group._id;
        option.textContent = group.name;
        groupSelect.appendChild(option);
      }
    });
    
    // Load quick stats for dashboard
    if (document.getElementById('dashboard-section').classList.contains('active')) {
      loadQuickStats();
    }
  } catch (err) {
    showAlert('Failed to load groups', 'error');
    console.error(err);
  }
}

async function loadQuickStats() {
  try {
    const res = await fetch('http://localhost:5000/api/contributions/quick-stats', {
      headers: { Authorization: `Bearer ${getToken()}` }
    });
    
    const data = await res.json();
    if (res.ok) {
      document.getElementById('monthly-total').textContent = formatCurrency(data.monthlyTotal);
      document.getElementById('yearly-total').textContent = formatCurrency(data.yearlyTotal);
      document.getElementById('total-contributions').textContent = formatCurrency(data.totalContributions);
    }
  } catch (err) {
    console.error('Error loading quick stats:', err);
  }
}

function showAlert(message, type = 'success') {
  const alert = document.createElement('div');
  alert.className = `alert alert-${type}`;
  alert.textContent = message;
  document.body.appendChild(alert);
  
  setTimeout(() => {
    alert.classList.add('fade-out');
    setTimeout(() => alert.remove(), 500);
  }, 3000);
}

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', () => {
  // Load user info
  getUser().then(user => {
    document.getElementById('user-name').textContent = user.name;
    document.getElementById('user-email').textContent = user.email;
  });
  
  // Load groups
  loadGroups();
  
  // Navigation handling
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
      e.preventDefault();
      const section = this.dataset.section;
      
      // Hide all sections
      document.querySelectorAll('.content-section').forEach(s => {
        s.classList.remove('active');
      });
      
      // Show selected section
      document.getElementById(`${section}-section`).classList.add('active');
      
      // Update active nav item
      document.querySelectorAll('.nav-item').forEach(nav => {
        nav.classList.remove('active');
      });
      this.classList.add('active');
      
      // Load specific content if needed
      if (section === 'activity') {
        loadActivityFeed();
      }
    });
  });
  
  // Quick contribute button
  const quickContributeBtn = document.getElementById('quick-contribute');
  if (quickContributeBtn) {
    quickContributeBtn.addEventListener('click', () => {
      document.querySelectorAll('.nav-item').forEach(nav => {
        nav.classList.remove('active');
      });
      document.querySelector('.nav-item[data-section="contributions"]').classList.add('active');
      
      document.querySelectorAll('.content-section').forEach(s => {
        s.classList.remove('active');
      });
      document.getElementById('contributions-section').classList.add('active');
    });
  }
  
  // Group form handling
  const groupForm = document.getElementById('group-form');
  if (groupForm) {
    groupForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const groupData = {
        name: document.getElementById('group-name').value,
        description: document.getElementById('group-description').value,
        goal: document.getElementById('group-goal').value,
        targetAmount: document.getElementById('group-target').value,
        monthlyContribution: document.getElementById('group-contribution').value
      };
      
      try {
        const res = await fetch('http://localhost:5000/api/groups', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${getToken()}`
          },
          body: JSON.stringify(groupData)
        });
        
        const data = await res.json();
        if (!res.ok) return showAlert(data.message || 'Failed to create group', 'error');
        
        showAlert('Group created successfully!');
        document.getElementById('group-modal').classList.remove('active');
        groupForm.reset();
        loadGroups();
      } catch (err) {
        showAlert('Error creating group', 'error');
        console.error(err);
      }
    });
  }
  
  // Show group form modal
  document.getElementById('show-group-form').addEventListener('click', () => {
    document.getElementById('group-modal').classList.add('active');
  });
  
  // Close modal
  document.querySelector('.close-modal').addEventListener('click', () => {
    document.getElementById('group-modal').classList.remove('active');
  });
  
  // Contribution form handling
  const contributionForm = document.getElementById('contribution-form');
  if (contributionForm) {
    contributionForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      const contributionData = {
        groupId: document.getElementById('contribution-group-id').value,
        amount: document.getElementById('contribution-amount').value,
        proof: document.getElementById('contribution-proof').value
      };
      
      try {
        const res = await fetch('http://localhost:5000/api/contributions', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${getToken()}`
          },
          body: JSON.stringify(contributionData)
        });
        
        const data = await res.json();
        if (!res.ok) return showAlert(data.message || 'Failed to submit contribution', 'error');
        
        showAlert('Contribution submitted successfully!');
        contributionForm.reset();
        loadGroups();
        loadQuickStats();
      } catch (err) {
        showAlert('Error submitting contribution', 'error');
        console.error(err);
      }
    });
  }
});

// Event delegation for dynamic elements
document.addEventListener('click', async (e) => {
  const token = getToken();
  const currentUser = await getUser();

  // ✅ INVITE MEMBER
  if (e.target.classList.contains('invite-btn') || e.target.closest('.invite-btn')) {
    const btn = e.target.classList.contains('invite-btn') ? e.target : e.target.closest('.invite-btn');
    const groupId = btn.dataset.id;
    const input = document.getElementById(`invite-${groupId}`);
    const email = input.value.trim();
    if (!email) return showAlert('Please enter an email address', 'error');

    try {
      const res = await fetch(`http://localhost:5000/api/groups/${groupId}/invite`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify({ email })
      });

      const data = await res.json();
      if (!res.ok) return showAlert(data.message || 'Failed to invite member', 'error');
      
      showAlert(data.message || 'Member invited successfully!');
      input.value = '';
    } catch (err) {
      console.error(err);
      showAlert('Error inviting member', 'error');
    }
  }

  // ✅ VIEW MEMBERS
  if (e.target.classList.contains('view-members-btn') || e.target.closest('.view-members-btn')) {
    const btn = e.target.classList.contains('view-members-btn') ? e.target : e.target.closest('.view-members-btn');
    const groupId = btn.dataset.id;
    const adminId = btn.dataset.admin;
    const list = document.getElementById(`member-list-${groupId}`);

    try {
      const res = await fetch(`http://localhost:5000/api/groups/${groupId}/members`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      const data = await res.json();
      if (!res.ok) return showAlert(data.message || 'Cannot view members', 'error');

      list.innerHTML = '';
      if (data.length === 0) {
        list.innerHTML = '<p class="no-members">No members found</p>';
        return;
      }

      data.forEach(member => {
        const memberItem = document.createElement('div');
        memberItem.className = 'member-item';
        memberItem.innerHTML = `
          <div class="member-info">
            <i class="fas fa-user-circle"></i>
            <div>
              <span class="member-name">${member.name || 'Unnamed'}</span>
              <span class="member-email">${member.email}</span>
            </div>
          </div>
        `;

        if (currentUser._id === adminId && member._id !== adminId) {
          const removeBtn = document.createElement('button');
          removeBtn.className = 'btn-sm danger remove-member-btn';
          removeBtn.dataset.groupId = groupId;
          removeBtn.dataset.memberId = member._id;
          removeBtn.innerHTML = '<i class="fas fa-user-minus"></i> Remove';
          memberItem.appendChild(removeBtn);
        }

        list.appendChild(memberItem);
      });
      
      list.style.display = 'block';
    } catch (err) {
      console.error(err);
      showAlert('Error loading members', 'error');
    }
  }

  // ✅ REMOVE MEMBER
  if (e.target.classList.contains('remove-member-btn') || e.target.closest('.remove-member-btn')) {
    const btn = e.target.classList.contains('remove-member-btn') ? e.target : e.target.closest('.remove-member-btn');
    const groupId = btn.dataset.groupId;
    const memberId = btn.dataset.memberId;
    
    if (!confirm('Are you sure you want to remove this member?')) return;

    try {
      const res = await fetch(`http://localhost:5000/api/groups/${groupId}/remove`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify({ memberId })
      });

      const data = await res.json();
      if (!res.ok) return showAlert(data.message || 'Failed to remove member', 'error');
      
      showAlert(data.message || 'Member removed successfully');
      loadGroups();
    } catch (err) {
      console.error(err);
      showAlert('Error removing member', 'error');
    }
  }

  // ✅ LEAVE GROUP
  if (e.target.classList.contains('leave-btn') || e.target.closest('.leave-btn')) {
    const btn = e.target.classList.contains('leave-btn') ? e.target : e.target.closest('.leave-btn');
    const groupId = btn.dataset.id;
    
    if (!confirm('Are you sure you want to leave this group?')) return;

    try {
      const res = await fetch(`http://localhost:5000/api/groups/${groupId}/leave`, {
        method: 'POST',
        headers: { Authorization: `Bearer ${token}` }
      });

      const data = await res.json();
      if (!res.ok) return showAlert(data.message || 'Could not leave group', 'error');
      
      showAlert(data.message || 'You have left the group');
      loadGroups();
    } catch (err) {
      console.error(err);
      showAlert('Error leaving group', 'error');
    }
  }

  // ✅ VIEW MY CONTRIBUTIONS
  if (e.target.classList.contains('view-my-contributions-btn') || e.target.closest('.view-my-contributions-btn')) {
    const btn = e.target.classList.contains('view-my-contributions-btn') ? e.target : e.target.closest('.view-my-contributions-btn');
    const groupId = btn.dataset.id;
    const container = document.getElementById(`contributions-${groupId}`);

    try {
      const res = await fetch(`http://localhost:5000/api/contributions/${groupId}/my`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      const data = await res.json();
      if (!res.ok) return showAlert(data.message, 'error');

      container.innerHTML = `
        <div class="section-header">
          <h4><i class="fas fa-coins"></i> My Contributions</h4>
          <button class="btn-sm close-contributions" data-target="${groupId}">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="contributions-list">
          ${data.length > 0 ? 
            data.map(c => `
              <div class="contribution-item">
                <div class="contribution-amount">${formatCurrency(c.amount)}</div>
                <div class="contribution-details">
                  <span class="contribution-date">${formatDate(c.date)}</span>
                  ${c.proof ? `<span class="contribution-proof"><i class="fas fa-receipt"></i> ${c.proof}</span>` : ''}
                </div>
              </div>
            `).join('') : 
            '<p class="no-contributions">You haven\'t made any contributions yet.</p>'
          }
        </div>
      `;
      
      container.style.display = 'block';
    } catch (err) {
      console.error(err);
      showAlert('Error loading your contributions', 'error');
    }
  }

  // ✅ VIEW ALL CONTRIBUTIONS
  if (e.target.classList.contains('view-all-contributions-btn') || e.target.closest('.view-all-contributions-btn')) {
    const btn = e.target.classList.contains('view-all-contributions-btn') ? e.target : e.target.closest('.view-all-contributions-btn');
    const groupId = btn.dataset.id;
    const container = document.getElementById(`contributions-${groupId}`);

    try {
      const res = await fetch(`http://localhost:5000/api/contributions/${groupId}/all`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      const data = await res.json();
      if (!res.ok) return showAlert(data.message, 'error');

      container.innerHTML = `
        <div class="section-header">
          <h4><i class="fas fa-list"></i> All Contributions</h4>
          <button class="btn-sm close-contributions" data-target="${groupId}">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="contributions-list">
          ${data.length > 0 ? 
            data.map(c => `
              <div class="contribution-item">
                <div class="user-avatar">
                  <i class="fas fa-user-circle"></i>
                </div>
                <div class="contribution-details">
                  <span class="user-name">${c.user.name}</span>
                  <div class="contribution-info">
                    <span class="contribution-amount">${formatCurrency(c.amount)}</span>
                    <span class="contribution-date">${formatDate(c.date)}</span>
                  </div>
                  ${c.proof ? `<span class="contribution-proof"><i class="fas fa-receipt"></i> ${c.proof}</span>` : ''}
                </div>
              </div>
            `).join('') : 
            '<p class="no-contributions">No contributions have been made yet.</p>'
          }
        </div>
      `;
      
      container.style.display = 'block';
    } catch (err) {
      console.error(err);
      showAlert('Error loading contributions', 'error');
    }
  }

  // ✅ GROUP PROGRESS
  if (e.target.classList.contains('view-progress-btn') || e.target.closest('.view-progress-btn')) {
    const btn = e.target.classList.contains('view-progress-btn') ? e.target : e.target.closest('.view-progress-btn');
    const groupId = btn.dataset.id;
    const container = document.getElementById(`contributions-${groupId}`);

    try {
      const res = await fetch(`http://localhost:5000/api/contributions/${groupId}/progress`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      const data = await res.json();
      if (!res.ok) return showAlert(data.message, 'error');

      const progressPercentage = Math.min(Math.round((data.totalContributed / data.target) * 100), 100);
      
      container.innerHTML = `
        <div class="section-header">
          <h4><i class="fas fa-chart-line"></i> Group Progress</h4>
          <button class="btn-sm close-contributions" data-target="${groupId}">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="progress-container large">
          <div class="progress-bar" style="width: ${progressPercentage}%"></div>
          <span class="progress-text">${progressPercentage}%</span>
        </div>
        <div class="progress-stats">
          <div class="stat-item">
            <span class="stat-label">Target Amount</span>
            <span class="stat-value">${formatCurrency(data.target)}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Total Contributed</span>
            <span class="stat-value">${formatCurrency(data.totalContributed)}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Remaining</span>
            <span class="stat-value">${formatCurrency(data.remainingBalance)}</span>
          </div>
        </div>
      `;
      
      container.style.display = 'block';
    } catch (err) {
      console.error(err);
      showAlert('Error loading progress', 'error');
    }
  }

  // ✅ ACTIVITY FEED
  if (e.target.classList.contains('view-activity-btn') || e.target.closest('.view-activity-btn')) {
    const btn = e.target.classList.contains('view-activity-btn') ? e.target : e.target.closest('.view-activity-btn');
    const groupId = btn.dataset.id;
    const container = document.getElementById(`contributions-${groupId}`);

    try {
      const res = await fetch(`http://localhost:5000/api/contributions/${groupId}/all`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      const data = await res.json();
      if (!res.ok) return showAlert(data.message, 'error');

      container.innerHTML = `
        <div class="section-header">
          <h4><i class="fas fa-bell"></i> Activity Feed</h4>
          <button class="btn-sm close-contributions" data-target="${groupId}">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="activity-feed">
          ${data.length > 0 ? 
            data.map(c => `
              <div class="activity-item">
                <div class="activity-icon">
                  <i class="fas fa-coins"></i>
                </div>
                <div class="activity-content">
                  <p><strong>${c.user.name}</strong> contributed <strong>${formatCurrency(c.amount)}</strong></p>
                  <p class="activity-time">${new Date(c.date).toLocaleString()}</p>
                </div>
              </div>
            `).join('') : 
            '<p class="no-activity">No activity yet in this group.</p>'
          }
        </div>
      `;
      
      container.style.display = 'block';
    } catch (err) {
      console.error(err);
      showAlert('Error loading activity feed', 'error');
    }
  }

  // Close contributions/members view
  if (e.target.classList.contains('close-contributions') || e.target.closest('.close-contributions')) {
    const btn = e.target.classList.contains('close-contributions') ? e.target : e.target.closest('.close-contributions');
    const targetId = btn.dataset.target;
    const container = document.getElementById(`contributions-${targetId}`);
    if (container) container.style.display = 'none';
    
    const memberList = document.getElementById(`member-list-${targetId}`);
    if (memberList) memberList.style.display = 'none';
  }
});

// Logout functionality
document.getElementById('logout').addEventListener('click', () => {
  localStorage.removeItem('token');
  window.location.href = '/login.html';
});

// Mobile menu toggle
document.querySelector('.menu-toggle').addEventListener('click', () => {
  document.querySelector('.sidebar').classList.toggle('active');
});

// Load activity feed for dashboard
async function loadActivityFeed() {
  try {
    const res = await fetch('http://localhost:5000/api/contributions/activity', {
      headers: { Authorization: `Bearer ${getToken()}` }
    });
    
    const data = await res.json();
    if (!res.ok) return;
    
    const container = document.getElementById('feed-container-full') || document.getElementById('feed-container');
    if (!container) return;
    
    container.innerHTML = data.length > 0 ? 
      data.map(item => `
        <div class="activity-item">
          <div class="activity-icon">
            <i class="fas fa-${item.type === 'contribution' ? 'coins' : 'user-plus'}"></i>
          </div>
          <div class="activity-content">
            <p>${item.message}</p>
            <p class="activity-time">${new Date(item.date).toLocaleString()}</p>
          </div>
        </div>
      `).join('') : 
      '<p class="no-activity">No recent activity</p>';
  } catch (err) {
    console.error('Error loading activity feed:', err);
  }
}
