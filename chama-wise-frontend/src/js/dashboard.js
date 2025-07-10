import { getToken, getCurrentUser } from './utils.js';
import { loadGroups } from './groups.js';

document.addEventListener('DOMContentLoaded', async () => {
  const token = getToken();
  const user = getCurrentUser();

  if (!token || !user) {
    window.location.href = '/index.html';
    return;
  }

  // Set user info
  document.getElementById('user-name').textContent = user.name;
  document.getElementById('user-email').textContent = user.email || '';

  // Load groups into dashboard
  await loadGroups();

  // Load recent activity feed preview
  await loadActivityPreview();

  // Logout
  document.getElementById('logout').addEventListener('click', () => {
    localStorage.clear();
    window.location.href = '/index.html';
  });

  // Group form toggle
  const groupForm = document.getElementById('group-form');
  document.getElementById('create-group-btn')?.addEventListener('click', () => {
    groupForm.style.display = groupForm.style.display === 'block' ? 'none' : 'block';
  });

  document.getElementById('cancel-group')?.addEventListener('click', () => {
    groupForm.style.display = 'none';
  });

  // Tab switching
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      // Switch active sidebar tab
      document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
      item.classList.add('active');

      const section = item.dataset.section;
      document.querySelectorAll('.content-section').forEach(sec => {
        sec.classList.remove('active');
      });

      const targetSection = document.getElementById(`${section}-section`);
      if (targetSection) targetSection.classList.add('active');

      // Load full activity feed if section is "activity"
      if (section === 'activity') {
        loadFullActivityFeed();
      }
    });
  });

  // "View All" link click handler (dashboard cards)
  document.querySelectorAll('.view-all').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const section = link.dataset.section;
      const navLink = document.querySelector(`.nav-item[data-section="${section}"]`);
      if (navLink) navLink.click();
    });
  });
});

// ✅ Load a short preview feed into dashboard card
async function loadActivityPreview() {
  const token = getToken();
  const user = getCurrentUser();
  const previewContainer = document.getElementById('feed-container');

  try {
    const groupRes = await fetch('http://localhost:5000/api/groups', {
      headers: { Authorization: `Bearer ${token}` }
    });

    const groups = await groupRes.json();
    if (!groupRes.ok) return (previewContainer.innerHTML = '<p class="no-activity">Failed to load groups</p>');

    const adminGroups = groups.filter(g => g.admin === user._id);
    const allActivity = [];

    for (const group of adminGroups) {
      const res = await fetch(`http://localhost:5000/api/contributions/${group._id}/all`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      const contributions = await res.json();
      if (!res.ok) continue;

      contributions.forEach(c => {
        allActivity.push({
          message: `${c.user.name} contributed Ksh ${c.amount} to ${group.name}`,
          date: c.date,
          type: 'contribution'
        });
      });
    }

    allActivity.sort((a, b) => new Date(b.date) - new Date(a.date));
    const recent = allActivity.slice(0, 5);

    previewContainer.innerHTML = recent.length > 0
      ? recent.map(item => `
          <div class="activity-item">
            <div class="activity-icon"><i class="fas fa-${item.type === 'contribution' ? 'coins' : 'user-plus'}"></i></div>
            <div class="activity-content">
              <p>${item.message}</p>
              <p class="activity-time">${new Date(item.date).toLocaleString()}</p>
            </div>
          </div>
        `).join('')
      : '<p class="no-activity">No recent activity</p>';
  } catch (err) {
    console.error('Error loading preview feed:', err);
    previewContainer.innerHTML = '<p class="no-activity">Error loading preview</p>';
  }
}

// ✅ Load the full activity feed when navigating to "Activity" tab
async function loadFullActivityFeed() {
  const token = getToken();
  const user = getCurrentUser();
  const container = document.getElementById('feed-container-full');

  try {
    const groupRes = await fetch('http://localhost:5000/api/groups', {
      headers: { Authorization: `Bearer ${token}` }
    });

    const groups = await groupRes.json();
    if (!groupRes.ok) return (container.innerHTML = '<p class="no-activity">Failed to load groups</p>');

    const adminGroups = groups.filter(g => g.admin === user._id);
    const allActivity = [];

    for (const group of adminGroups) {
      const res = await fetch(`http://localhost:5000/api/contributions/${group._id}/all`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      const contributions = await res.json();
      if (!res.ok) continue;

      contributions.forEach(c => {
        allActivity.push({
          message: `${c.user.name} contributed Ksh ${c.amount} to ${group.name}`,
          date: c.date,
          type: 'contribution'
        });
      });
    }

    allActivity.sort((a, b) => new Date(b.date) - new Date(a.date));

    container.innerHTML = allActivity.length > 0
      ? allActivity.map(item => `
          <div class="activity-item">
            <div class="activity-icon"><i class="fas fa-${item.type === 'contribution' ? 'coins' : 'user-plus'}"></i></div>
            <div class="activity-content">
              <p>${item.message}</p>
              <p class="activity-time">${new Date(item.date).toLocaleString()}</p>
            </div>
          </div>
        `).join('')
      : '<p class="no-activity">No activity to display</p>';
  } catch (err) {
    console.error('Error loading full feed:', err);
    container.innerHTML = '<p class="no-activity">Error loading activity</p>';
  }
}
