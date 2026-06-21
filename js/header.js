async function loadHeader() {
  const res = await fetch('/api/user');
  if (res.status === 401) {
    window.location.href = '/login';
    return null;
  }
  const user = await res.json();

  document.getElementById('header-placeholder').innerHTML = `
    <nav style="background:#065f46;" class="shadow-lg px-5 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button onclick="toggleSidebar()"
          class="lg:hidden p-1.5 rounded-lg transition-colors"
          style="color:rgba(255,255,255,0.7);background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);"
          onmouseover="this.style.background='rgba(255,255,255,0.18)';this.style.color='#fff'"
          onmouseout="this.style.background='rgba(255,255,255,0.08)';this.style.color='rgba(255,255,255,0.7)'"
          aria-label="Toggle menu">
          <i class="fa-solid fa-bars text-sm"></i>
        </button>
        <a href="/dashboard" class="flex items-center gap-2 no-underline">
          <img class="w-7 h-7" src="/img/logo.png" alt="Gradifier logo" />
          <span style="font-family:'Montserrat',sans-serif;" class="font-black text-lg text-white tracking-tight">
            GRADI<span style="color:#6ee7b7;">FIER</span>
          </span>
        </a>
      </div>
      <div class="flex items-center gap-3">
        <a href="/settings" class="flex items-center gap-2.5 no-underline group">
          <img src="${user.photo || '/img/fred.png'}" alt="Profile"
            class="w-8 h-8 rounded-full object-cover border-2 transition-colors"
            style="border-color:rgba(255,255,255,0.25);"
            onmouseover="this.style.borderColor='#6ee7b7'" onmouseout="this.style.borderColor='rgba(255,255,255,0.25)'" />
          <div class="hidden sm:flex flex-col leading-tight">
            <span class="text-white text-sm font-medium">${user.name || 'User'}</span>
            <span class="text-xs" style="color:rgba(110,231,183,0.7);">${user.email || ''}</span>
          </div>
        </a>
        <button onclick="logout()"
          class="flex items-center gap-1.5 text-xs font-medium rounded-lg px-3 py-1.5 transition-colors"
          style="color:rgba(255,255,255,0.75);background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15);"
          onmouseover="this.style.background='rgba(255,255,255,0.2)';this.style.color='#fff'"
          onmouseout="this.style.background='rgba(255,255,255,0.1)';this.style.color='rgba(255,255,255,0.75)'">
          <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
          <span class="hidden sm:inline">Logout</span>
        </button>
      </div>
    </nav>`;

  return user;
}

async function logout() {
  if (!confirm('Sign out of Gradifier?')) return;
  await fetch('/api/logout', { method: 'POST' });
  window.location.href = '/login';
}
