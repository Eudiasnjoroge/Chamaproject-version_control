import { getToken } from './utils.js';

document.addEventListener('DOMContentLoaded', () => {
  const submitBtn = document.getElementById('submit-contribution');

  if (submitBtn) {
    submitBtn.addEventListener('click', async () => {
      const groupId = document.getElementById('contribution-group-id').value;
      const amount = document.getElementById('contribution-amount').value;
      const proof = document.getElementById('contribution-proof').value;

      try {
        const res = await fetch('http://localhost:5000/api/contributions', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${getToken()}`
          },
          body: JSON.stringify({ groupId, amount, proof })
        });

        const data = await res.json();

        if (!res.ok) return alert(data.message || 'Failed to contribute');

        alert('✅ Contribution successful');
        document.getElementById('contribution-amount').value = '';
        document.getElementById('contribution-proof').value = '';
        loadContributions(groupId);

      } catch (err) {
        alert('Error contributing');
        console.error(err);
      }
    });
  }
});

// Load contributions for the feed
export async function loadContributions(groupId) {
  try {
    const res = await fetch(`http://localhost:5000/api/contributions/${groupId}`, {
      headers: {
        Authorization: `Bearer ${getToken()}`
      }
    });

    const data = await res.json();
    const feed = document.getElementById('feed-container');
    feed.innerHTML = '';

    if (!res.ok) return alert(data.message || 'Could not load activity feed');

    data.forEach(entry => {
      const div = document.createElement('div');
      div.innerHTML = `<p><strong>${entry.user.name}</strong> contributed Ksh ${entry.amount} (${new Date(entry.date).toLocaleDateString()})</p>`;
      feed.appendChild(div);
    });
  } catch (err) {
    alert('Failed to load activity feed');
    console.error(err);
  }
}
