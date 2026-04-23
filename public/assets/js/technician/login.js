document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('loginForm');
  if (!form) return;

  const employeeId = document.getElementById('employeeId');
  const password = document.getElementById('password');
  const status = document.getElementById('loginStatus');
  const btn = document.getElementById('loginBtn');

  function setErr(id, msg) {
    const el = document.getElementById(id);
    if (el) el.textContent = msg || '';
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    setErr('employeeIdError', '');
    setErr('passwordError', '');
    status.textContent = '';

    const emp = (employeeId.value || '').trim();
    const pass = password.value || '';
    if (!emp) { setErr('employeeIdError', 'Employee ID is required'); return; }
    if (!pass) { setErr('passwordError', 'Password is required'); return; }

    btn.disabled = true;
    status.textContent = 'Signing in…';
    try {
      const data = await window.MRTS.api('/api/mock/auth.php?action=login', {
        method: 'POST',
        body: JSON.stringify({ employee_id: emp, password: pass }),
      });
      if (!data.success) throw new Error(data.message || 'Login failed');
      window.location.href = window.MRTS.APP_BASE + '/public/jobs.php';
    } catch (err) {
      status.textContent = err.message || 'Login failed';
    } finally {
      btn.disabled = false;
    }
  });
});

