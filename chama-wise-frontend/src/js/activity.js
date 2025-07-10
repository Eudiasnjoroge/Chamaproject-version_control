import { getToken } from './utils.js';

export async function loadActivity(groupId) {
  try {
    const token = getToken();
    if (!token) return;
    
    const res = await fetch(`http://localhost:5000/api/activities/group/${groupId}`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    const activities = await res.json();
    const container = document.getElementById('activity-list');
    container.innerHTML = '';

    if (!res.ok) {
      console.error('Failed to load activity:', activities.message);
      return;
    }

    if (activities.length === 0) {
      container.innerHTML = '<p class="no-activity">No recent activity</p>';
      return;
    }

    activities.forEach(activity => {
      const activityElement = document.createElement('div');
      activityElement.className = 'activity-item';
      activityElement.innerHTML = `
        <div class="activity-message">
          <span class="user">${activity.user?.name || 'System'}:</span>
          <span>${activity.message}</span>
        </div>
        <div class="activity-meta">
          <span class="date">${new Date(activity.createdAt).toLocaleString()}</span>
        </div>
      `;
      container.appendChild(activityElement);
    });

  } catch (err) {
    console.error('Error loading activity:', err);
  }
}
