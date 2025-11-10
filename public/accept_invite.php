<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();

$token = $_GET['token'] ?? '';
$errors = [];

// Invite laden/prüfen
$invite = null;
if ($token !== '') {
  $st = $pdo->prepare('
    SELECT id, family_id, email, expires_at, used_at
    FROM family_invites
    WHERE token = ?
    LIMIT 1
  ');
  $st->execute([$token]);
  $invite = $st->fetch();

  if (!$invite) {
    $errors[] = 'Invalid invite.';
  } elseif (!empty($invite['used_at'])) {
    $errors[] = 'Invite already used.';
  } elseif (!empty($invite['expires_at']) && strtotime($invite['expires_at']) < time()) {
    $errors[] = 'Invite expired.';
  } elseif (!empty($invite['email']) && strcasecmp($invite['email'], $_SESSION['email']) !== 0) {
    // personalisierte Einladung an andere E-Mail
    $errors[] = 'This invite is addressed to another email.';
  }
} else {
  $errors[] = 'Missing token.';
}

$joined = false;
$familyId = $invite ? (int)$invite['family_id'] : 0;

// POST: Join
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errors) {
  if (!csrf_check($_POST['_csrf'] ?? '')) {
    $errors[] = 'Security token invalid.';
  } else {
    $relation = trim($_POST['relation_label'] ?? '') ?: null;

    // Wenn schon Mitglied, nur Invite als benutzt markieren
    $st = $pdo->prepare('SELECT 1 FROM family_memberships WHERE user_id=? AND family_id=? LIMIT 1');
    $st->execute([$_SESSION['user_id'], $familyId]);
    $already = (bool)$st->fetchColumn();

    $pdo->beginTransaction();
    try {
      if (!$already) {
        $st = $pdo->prepare('
          INSERT IGNORE INTO family_memberships (user_id,family_id,family_role,relation_label)
          VALUES (?,?, "member", ?)
        ');
        $st->execute([$_SESSION['user_id'], $familyId, $relation]);
      }
      $pdo->prepare('UPDATE family_invites SET used_at=NOW() WHERE token=?')->execute([$token]);
      $pdo->commit();
      $joined = true;

      header('Location: /Journeys-media/public/index.php?view=family&family_id='.$familyId);
      exit;
    } catch (Throwable $e) {
      $pdo->rollBack();
      $errors[] = 'Database error.';
    }
  }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Accept Invite</title></head>
<body>
<h1>Accept Invite</h1>

<?php foreach ($errors as $e): ?>
  <p style="color:#b00"><?= htmlspecialchars($e) ?></p>
<?php endforeach; ?>

<?php if ($invite && !$errors): ?>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <p>Joining family #<?= (int)$invite['family_id'] ?><?= $invite['email'] ? ' as '.htmlspecialchars($invite['email']) : '' ?></p>
    <label>Your Full Name<br>
      <input name="relation_label" placeholder="Marie Jones">
    </label><br><br>
    <button type="submit">Join family</button>
  </form>
<?php endif; ?>

<p><a href="/Journeys-media/public/index.php">Back</a></p>
</body>
</html>
