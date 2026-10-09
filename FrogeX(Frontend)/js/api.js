// Frontend and API are served from the same host in production and locally.
const API_BASE = "/api";

/**
 * Wrapper around fetch() for talking to the PHP backend.
 * Always sends/receives JSON and includes the PHP session cookie.
 */
async function apiFetch(endpoint, method = "GET", body = null) {
  const options = {
    method,
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin" // sends the PHPSESSID cookie so session_start() picks up the logged-in user
  };
  if (body) options.body = JSON.stringify(body);

  const res = await fetch(API_BASE + endpoint, options);
  let json;
  try {
    json = await res.json();
  } catch (e) {
    throw new Error("Server did not return valid JSON. Check the PHP file for errors.");
  }
  if (!res.ok) {
    const message = json.error || "Request failed";
    const details = json.details ? `: ${json.details}` : "";
    throw new Error(message + details);
  }
  return json;
}

function isLoggedIn() {
  return !!localStorage.getItem("forgex_logged_in");
}

function isAdmin() {
  return localStorage.getItem("forgex_is_admin") === "1";
}

function requireLogin() {
  if (!isLoggedIn()) {
    window.location.href = "login.html";
  }
}

function requireAdmin() {
  if (!isLoggedIn() || !isAdmin()) {
    window.location.href = "dashboard.html";
  }
}

// Shows/hides any element with id="adminNavLink" based on admin status.
// Call this on page load for any page that includes that nav link.
function showAdminLinkIfAdmin() {
  const link = document.getElementById("adminNavLink");
  if (link) link.style.display = isAdmin() ? "inline" : "none";
}

function logout() {
  apiFetch("/auth/logout.php", "POST").finally(() => {
    localStorage.removeItem("forgex_logged_in");
    localStorage.removeItem("forgex_is_admin");
    localStorage.removeItem("forgex_username");
    window.location.href = "index.html";
  });
}

// XP level math shared between profile.html and training.html displays.
// Must match api/config/helpers.php's recalc_level() -> 200 XP per level.
const XP_PER_LEVEL = 200;
function xpProgress(xp) {
  const level = Math.floor(xp / XP_PER_LEVEL) + 1;
  const intoLevel = xp % XP_PER_LEVEL;
  const percent = Math.round((intoLevel / XP_PER_LEVEL) * 100);
  return { level, intoLevel, percent, remaining: XP_PER_LEVEL - intoLevel };
}
