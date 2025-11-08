<?php
require_once __DIR__ . '/../lib/auth.php';

$errors = [];
$redirect = '/family-media/public/index.php'; // Default-Ziel
if (!empty($_GET['r']) && str_starts_with($_GET['r'], '/family-media/')) {
  $redirect = $_GET['r']; // nur relative, eigene Pfade erlauben
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['_csrf'] ?? '')) {
    $errors[] = 'Security token invalid.';
  }

  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass  = $_POST['password'] ?? '';

  if (!$errors) {
    $st = $pdo->prepare('SELECT id, email, display_name, password_hash FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch();

    // Einheitliche Fehlermeldung – keine Info, ob Mail existiert
    $login_ok = $user && password_verify($pass, $user['password_hash']);

    if ($login_ok) {
      // ggf. Hash auffrischen (z. B. wenn Algorithmus/Cost geändert wurde)
      if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$newHash, (int)$user['id']]);
      }

      session_regenerate_id(true);
      $_SESSION['user_id']       = (int)$user['id'];
      $_SESSION['email']         = $user['email'];
      $_SESSION['display_name']  = $user['display_name'] ?? '';
      $_SESSION['login_time']    = time();
      $_SESSION['last_activity'] = time();

      header('Location: ' . $redirect);
      exit;
    } else {
      $errors[] = 'Invalid email or password.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <link rel="stylesheet" href="/family-media/public/css/app.css">
  <style>
    .center-wrap{min-height:100vh;display:grid;place-items:center;padding:24px}
    .auth-card{width:min(420px, 92vw)}
    .brand{display:flex;gap:10px;align-items:center;margin-bottom:8px}
    .brand .logo{width:34px;height:34px;border-radius:8px;background:#6366f1}
    .auth-footer{display:flex;justify-content:space-between;align-items:center;margin-top:12px}
    .link{color:#6366f1;text-decoration:none} .link:hover{text-decoration:underline}
  </style>
</head>
<body>
  <div class="center-wrap">
    <div class="card auth-card">
      <div class="brand">
        <div class="logo"></div>
        <strong>Family Media</strong>
      </div>
      <h2 style="margin-top:0">Sign in</h2>

      <?php if (!empty($_GET['expired'])): ?>
        <div class="alert alert-error">Your session expired. Please log in again.</div>
      <?php endif; ?>

      <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
      <?php endforeach; ?>

      <form method="post" autocomplete="on" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <?php if (!empty($_GET['r'])): ?>
          <input type="hidden" name="r" value="<?= htmlspecialchars($_GET['r']) ?>">
        <?php endif; ?>

        <label>Email</label>
        <input type="email" name="email" inputmode="email" autocomplete="username" required autofocus>

        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required>

        <div class="auth-footer">
          <a class="link" href="/family-media/public/register.php">Create account</a>
          <button type="submit" class="btn">Login</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
