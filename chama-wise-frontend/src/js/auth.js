import { showElement, hideElement } from './utils.js';

document.addEventListener('DOMContentLoaded', () => {
  const registerForm = document.getElementById('register');
  const loginForm = document.getElementById('login');

  if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const name = document.getElementById('register-name').value;
      const email = document.getElementById('register-email').value;
      const password = document.getElementById('register-password').value;

      try {
        const res = await fetch('http://localhost:5000/api/auth/register', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ name, email, password })
        });

        const data = await res.json();

        if (!res.ok) {
          return alert(data.message || 'Registration failed');
        }

        alert('✅ Registered successfully. Now log in.');
        registerForm.reset();
      } catch (err) {
        alert('Error registering.');
        console.error(err);
      }
    });
  }

  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('login-email').value;
      const password = document.getElementById('login-password').value;

      try {
        const res = await fetch('http://localhost:5000/api/auth/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password })
        });

        const data = await res.json();

    if (!res.ok) return alert(data.message || 'Login failed');

    // Save token and redirect
    localStorage.setItem('token', data.token);
    
    localStorage.setItem('user', JSON.stringify(data.user));
    window.location.href = './dashboard.html';
  } catch (err) {
        alert('Error logging in.');
        console.error(err);
      }
    });
  }
});
