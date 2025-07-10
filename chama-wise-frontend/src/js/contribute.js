import { getToken } from './utils.js';

document.addEventListener('DOMContentLoaded', () => {
  const groupSelect = document.getElementById('contribution-group-id');
  const amountInput = document.getElementById('contribution-amount');
  const proofInput = document.getElementById('contribution-proof');
  const submitBtn = document.getElementById('submit-contribution');

  // Load groups the user is a member of
  async function loadUserGroups() {
    try {
      const res = await fetch('http://localhost:5000/api/groups/my', {
        headers: {
          Authorization: `Bearer ${getToken()}`
        }
      });

      const data = await res.json();
      if (!res.ok) return alert(data.message || 'Failed to load groups');

      groupSelect.innerHTML = '<option disabled selected>Select a group</option>';
      data.forEach(group => {
        const option = document.createElement('option');
        option.value = group._id;
        option.textContent = group.name;
        groupSelect.appendChild(option);
      });
    } catch (err) {
      console.error(err);
      alert('Error loading groups');
    }
  }

  // Handle contribution submission
  async function submitContribution() {
    const groupId = groupSelect.value;
    const amount = parseFloat(amountInput.value);
    const proof = proofInput.value.trim();

    if (!groupId || isNaN(amount) || amount <= 0) {
      return alert('Please enter a valid group and amount');
    }

    try {
      const res = await fetch(`http://localhost:5000/api/contributions/${groupId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${getToken()}`
        },
        body: JSON.stringify({ amount, proof })
      });

      const data = await res.json();
      if (!res.ok) return alert(data.message || 'Contribution failed');

      alert('✅ Contribution successful');
      amountInput.value = '';
      proofInput.value = '';
      groupSelect.selectedIndex = 0;
    } catch (err) {
      console.error(err);
      alert('Something went wrong');
    }
  }

  submitBtn.addEventListener('click', submitContribution);

  // Load the groups on page load
  loadUserGroups();
});
