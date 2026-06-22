const jwt = require('jsonwebtoken');

const SECRET = process.env.JWT_SECRET;
const COOKIE = 'grd_token';

function sign(payload) {
  return jwt.sign(payload, SECRET, { expiresIn: '1h' });
}

function parseCookies(str = '') {
  return Object.fromEntries(
    str.split(';')
      .filter(Boolean)
      .map(c => {
        const idx = c.indexOf('=');
        return [c.slice(0, idx).trim(), c.slice(idx + 1).trim()];
      })
  );
}

function getUser(req) {
  const cookies = parseCookies(req.headers.cookie || '');
  const token = cookies[COOKIE];
  if (!token) return null;
  try { return jwt.verify(token, SECRET); } catch { return null; }
}

function setCookie(res, token) {
  res.setHeader('Set-Cookie',
    `${COOKIE}=${token}; HttpOnly; Path=/; Max-Age=3600; SameSite=Lax`
  );
}

function clearCookie(res) {
  res.setHeader('Set-Cookie',
    `${COOKIE}=; HttpOnly; Path=/; Max-Age=0; SameSite=Lax`
  );
}

module.exports = { sign, getUser, setCookie, clearCookie };
