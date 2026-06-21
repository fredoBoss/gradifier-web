async function checkAuth() {
  const res = await fetch('/api/user');
  if (res.status === 401) {
    window.location.href = '/login';
    return null;
  }
  return res.json();
}
