<?php
// Dev: Fehler zeigen
ini_set('display_errors', 1);
error_reporting(E_ALL);

/** ── Session-Härtung ───────────────────────────────────────────────── **/
ini_set('session.use_strict_mode', 1);

// === Zeitlimits (anpassen, wenn du willst) ===
$INACTIVITY_LIMIT = 15 * 60;   // 15 Min ohne Request -> Logout
$ABSOLUTE_LIMIT   = 2  * 3600; // nach 2 Std seit Login -> Logout
$COOKIE_LIFETIME  = $ABSOLUTE_LIMIT; // Cookie-Laufzeit 

ini_set('session.gc_maxlifetime', max($COOKIE_LIFETIME, $INACTIVITY_LIMIT, $ABSOLUTE_LIMIT));

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
  'lifetime' => $COOKIE_LIFETIME, // 0 = nur bis Browser zu; >0 = persistenter Cookie
  'path'     => '/',
  'domain'   => '',
  'secure'   => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/** ── Auto-Logout Helper ─────────────────────────────────────────────── **/
function force_logout_and_redirect() {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
  header('Location: /journeys-media/public/login.php?expired=1');
  exit;
}

/** ── Timeouts prüfen bei jedem Request (falls eingeloggt) ───────────── **/
if (!empty($_SESSION['user_id'])) {
  $now = time();

  // Absoluter Timeout
  if (!empty($_SESSION['login_time']) && $ABSOLUTE_LIMIT > 0
      && ($now - $_SESSION['login_time'] > $ABSOLUTE_LIMIT)) {
    force_logout_and_redirect();
  }

  // Inaktivitäts-Timeout (Sliding Window)
  if (!empty($_SESSION['last_activity']) && $INACTIVITY_LIMIT > 0
      && ($now - $_SESSION['last_activity'] > $INACTIVITY_LIMIT)) {
    force_logout_and_redirect();
  }

  // Aktivität updaten
  $_SESSION['last_activity'] = $now;
}
