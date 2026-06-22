function showMsg(id, msg, isError) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = msg;
  el.className = `text-sm px-4 py-3 rounded-xl mb-4 ${isError ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'}`;
  el.classList.remove('hidden');
  setTimeout(() => el.classList.add('hidden'), 4000);
}

async function loadSettings() {
  const res = await fetch('/api/user');
  if (res.status === 401) { window.location.href = '/login'; return; }
  const user = await res.json();

  document.getElementById('currentName').textContent  = user.name  || 'Your Name';
  document.getElementById('currentEmail').textContent = user.email || 'your@email.com';
  document.getElementById('photoPreview').src = user.photo || '/img/fred.png';
  document.getElementById('inputName').value  = user.name  || '';
  document.getElementById('inputEmail').value = user.email || '';
}

function previewPhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => document.getElementById('photoPreview').src = e.target.result;
    reader.readAsDataURL(input.files[0]);
  }
}
window.previewPhoto = previewPhoto;

function resizeImage(file, maxPx = 300, quality = 0.82) {
  return new Promise((resolve, reject) => {
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      const scale = Math.min(maxPx / img.width, maxPx / img.height, 1);
      const w = Math.round(img.width * scale);
      const h = Math.round(img.height * scale);
      const canvas = document.createElement('canvas');
      canvas.width = w; canvas.height = h;
      canvas.getContext('2d').drawImage(img, 0, 0, w, h);
      URL.revokeObjectURL(url);
      resolve(canvas.toDataURL('image/jpeg', quality));
    };
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Failed to load image')); };
    img.src = url;
  });
}

function togglePasswordVisibility(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  const isHidden = input.type === 'password';
  input.type = isHidden ? 'text' : 'password';
  icon.classList.toggle('fa-eye',      !isHidden);
  icon.classList.toggle('fa-eye-slash', isHidden);
}

document.addEventListener('DOMContentLoaded', () => {
  loadSettings();

  ['toggleCurrentPassword','toggleNewPassword','toggleConfirmPassword'].forEach(id => {
    const inputMap = {
      toggleCurrentPassword: 'currentPassword',
      toggleNewPassword:     'newPassword',
      toggleConfirmPassword: 'confirmPassword',
    };
    const el = document.getElementById(id);
    if (el) el.addEventListener('click', () => togglePasswordVisibility(inputMap[id], id));
  });

  const pwRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
  const newPwEl  = document.getElementById('newPassword');
  const confPwEl = document.getElementById('confirmPassword');

  if (newPwEl) newPwEl.addEventListener('input', function() {
    const msg = document.getElementById('newPasswordMessage');
    if (!msg) return;
    if (!this.value) { msg.textContent=''; return; }
    if (!pwRegex.test(this.value)) {
      msg.textContent='Min 8 chars with uppercase, lowercase, number & special character.';
      msg.className='text-xs mt-1 text-red-500';
    } else {
      msg.textContent='✓ Strong password';
      msg.className='text-xs mt-1 text-emerald-600';
    }
    checkMatch();
  });

  if (confPwEl) confPwEl.addEventListener('input', checkMatch);

  function checkMatch() {
    const msg  = document.getElementById('confirmPasswordMessage');
    if (!msg) return;
    const newP = document.getElementById('newPassword')?.value || '';
    const conP = document.getElementById('confirmPassword')?.value || '';
    if (!conP) { msg.textContent=''; return; }
    if (newP !== conP) { msg.textContent='Passwords do not match.'; msg.className='text-xs mt-1 text-red-500'; }
    else { msg.textContent='✓ Passwords match'; msg.className='text-xs mt-1 text-emerald-600'; }
  }

  // Profile form
  const profileForm = document.getElementById('profileForm');
  if (profileForm) profileForm.addEventListener('submit', async e => {
    e.preventDefault();
    const name  = document.getElementById('inputName').value.trim();
    const email = document.getElementById('inputEmail').value.trim();
    const body  = { name, email };

    const picInput = document.getElementById('profilePic');
    if (picInput && picInput.files && picInput.files[0]) {
      try {
        body.photo = await resizeImage(picInput.files[0]);
      } catch {
        showMsg('profileMsg', 'Failed to process image. Try another file.', true);
        return;
      }
    }

    const res = await fetch('/api/update-profile', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body),
    });
    const data = await res.json();
    showMsg('profileMsg', data.message || data.error, !res.ok);
    if (res.ok) { picInput && (picInput.value = ''); loadSettings(); }
  });

  // Password form
  const pwForm = document.getElementById('passwordForm');
  if (pwForm) pwForm.addEventListener('submit', async e => {
    e.preventDefault();
    const body = {
      action:           'change_password',
      current_password: document.getElementById('currentPassword').value,
      new_password:     document.getElementById('newPassword').value,
      confirm_password: document.getElementById('confirmPassword').value,
    };
    const res = await fetch('/api/update-profile', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body),
    });
    const data = await res.json();
    showMsg('passwordMsg', data.message || data.error, !res.ok);
    if (res.ok) pwForm.reset();
  });
});
