<?php
require_once __DIR__ . '/../lib/auth.php';

$errors = [];
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['_csrf'] ?? '')) {
    $errors[] = 'Security token invalid.';
  }

  $name  = trim($_POST['display_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $pass2 = $_POST['password_confirm'] ?? '';

  if ($name === '' || mb_strlen($name) > 100) {
    $errors[] = 'Please enter a name (max 100 chars).';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email.';
  }
  if (strlen($pass) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
  }
  if ($pass !== $pass2) {
    $errors[] = 'Passwords do not match.';
  }

  if (!$errors) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    try {
      $st = $pdo->prepare('INSERT INTO users (email, display_name, password_hash) VALUES (?, ?, ?)');
      $st->execute([$email, $name, $hash]);
      $done = true;
    } catch (PDOException $e) {
      $errors[] = ($e->getCode()==='23000') ? 'Email already registered.' : 'Database error.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create account</title>
  <link rel="stylesheet" href="/Journeys-media/public/css/app.css">
  <style>
    .center-wrap{min-height:100vh;display:grid;place-items:center;padding:24px;
    background-color: #7e7e7eff; color: black}}
    .auth-card{width:min(460px, 92vw)}
    .brand{display:flex;gap:10px;align-items:center;margin-bottom:8px}
    .brand .logo{width:34px;height:34px;border-radius:8px;background:#6366f1}
    .auth-footer{display:flex;justify-content:space-between;align-items:center;margin-top:12px}
    .link{color:#6366f1;text-decoration:none} .link:hover{text-decoration:underline}
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media (max-width:560px){.grid-2{grid-template-columns:1fr}}
  </style>
</head>
<body>
  <div class="center-wrap">
    <div class="card auth-card">
      <div class="brand">
        <div class="logo"></div>
        <strong>Family Media</strong>
      </div>

      <?php if ($done): ?>
        <h2 style="margin-top:0">Account created 🎉</h2>
        <div class="alert alert-success">Your account has been created successfully.</div>
        <p><a class="btn" href="/Journeys-media/public/login.php">Go to login</a></p>
      <?php else: ?>
        <h2 style="margin-top:0">Create account</h2>

        <?php foreach ($errors as $e): ?>
          <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>

        <form method="post" autocomplete="off">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

          <label>Display name</label>
          <input type="text" name="display_name" maxlength="100" required>

          <label>Email</label>
          <input type="email" name="email" required>

          <div class="grid-2">
            <div>
              <label>Password</label>
              <input type="password" name="password" minlength="8" required>
            </div>
            <div>
              <label>Confirm password</label>
              <input type="password" name="password_confirm" minlength="8" required>
            </div>
          </div>

          <div class="auth-footer">
            <a class="link" href="/Journeys-media/public/login.php">I have an account</a>
            <button type="submit" class="btn">Register</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
