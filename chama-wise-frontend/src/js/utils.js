export function getToken() {
  return localStorage.getItem('token');
}

// ✅ Show an element by ID
export function showElement(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'block';
}

// ✅ Hide an element by ID
export function hideElement(id) {
  const el = document.getElementById(id);
  if (el) el.style.display = 'none';
}

// ✅ Fetch the currently logged-in user (requires /api/users/me route)
export async function getUser() {
  const token = getToken();
  try {
    const res = await fetch('http://localhost:5000/api/users/me', {
      headers: {
        Authorization: `Bearer ${token}`
      }
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Failed to get user');
    return data;
  } catch (err) {
    console.error('getUser error:', err);
    return null;
  }
}
