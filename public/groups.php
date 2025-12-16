<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();

$errors = [];
$flash  = null;

$view     = $_GET['view']   ?? 'list'; // list | family | myinvites
$familyId = isset($_GET['family_id']) ? (int)$_GET['family_id'] : 0;

/* ---------- POST-Actions: Familie anlegen / Invite erstellen ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['_csrf'] ?? '')) {
    $errors[] = 'Security token invalid.';
  } else {
    $action = $_POST['action'] ?? '';
    try {
      if ($action === 'create_family') {
        $name = trim($_POST['family_name'] ?? '');
        $rel  = trim($_POST['relation_label'] ?? '');
        if ($name === '') throw new RuntimeException('Family name required.');

        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO families (family_name) VALUES (?)')->execute([$name]);
        $fid = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO family_memberships (user_id,family_id,family_role,relation_label)
                       VALUES (?,?, "owner", ?)')->execute([$_SESSION['user_id'], $fid, $rel ?: null]);
        $pdo->commit();

        $flash = 'Family created.';
        $view = 'family'; $familyId = $fid;
      }

      if ($action === 'create_invite') {
        $fid   = (int)($_POST['family_id'] ?? 0);
        if (!is_family_admin($pdo, $_SESSION['user_id'], $fid)) throw new RuntimeException('Forbidden.');
        $email = trim($_POST['email'] ?? '');
        $days  = max(1, (int)($_POST['expires_days'] ?? 7));

        $token = bin2hex(random_bytes(32));
        $pdo->prepare('
          INSERT INTO family_invites (family_id, created_by, token, email, expires_at)
          VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))
        ')->execute([$fid, $_SESSION['user_id'], $token, $email ?: null, $days]);

        $flash = 'Invite created.';
        $view = 'family'; $familyId = $fid;
      }

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Database error.';
    }
  }
}

/* ---------- Daten laden (gemeinsam genutzt) ---------- */
// Meine Familien
$st = $pdo->prepare('
  SELECT f.id, f.family_name, fm.family_role
  FROM family_memberships fm
  JOIN families f ON f.id = fm.family_id
  WHERE fm.user_id = ?
  ORDER BY f.family_name
');
$st->execute([$_SESSION['user_id']]);
$families = $st->fetchAll();

// Meine personalisierten offenen Einladungen (für Sidebar & myinvites)
$st = $pdo->prepare('
  SELECT fi.id, fi.family_id, fi.token, fi.email, fi.expires_at, f.family_name
  FROM family_invites fi
  JOIN families f ON f.id = fi.family_id
  LEFT JOIN family_memberships fm ON fm.family_id = fi.family_id AND fm.user_id = ?
  WHERE fi.used_at IS NULL
    AND (fi.expires_at IS NULL OR fi.expires_at > NOW())
    AND fi.email = ?
    AND fm.user_id IS NULL
  ORDER BY fi.expires_at IS NULL DESC, fi.expires_at ASC, fi.id DESC
');
$st->execute([$_SESSION['user_id'], $_SESSION['email']]);
$myInvites = $st->fetchAll();

// Familiendetail (Mitglieder & offene Invites)
$family = null; $members = $openInvites = [];
if ($view === 'family' && $familyId > 0) {
  if (!is_family_member($pdo, $_SESSION['user_id'], $familyId)) {
    $errors[] = 'You are not a member of this family.'; $view = 'list';
  } else {
    $st = $pdo->prepare('SELECT id,family_name FROM families WHERE id=?');
    $st->execute([$familyId]); $family = $st->fetch();

    $st = $pdo->prepare('
      SELECT u.id, u.email, u.display_name, fm.family_role, fm.relation_label
      FROM family_memberships fm
      JOIN users u ON u.id=fm.user_id
      WHERE fm.family_id=?
      ORDER BY u.display_name IS NULL, u.display_name, u.email
    ');
    $st->execute([$familyId]); $members = $st->fetchAll();

    $st = $pdo->prepare('
      SELECT id, token, email, expires_at
      FROM family_invites
      WHERE family_id=? AND used_at IS NULL
            AND (expires_at IS NULL OR expires_at > NOW())
      ORDER BY id DESC
    ');
    $st->execute([$familyId]); $openInvites = $st->fetchAll();
  }
}

// Dynamische Basis-URL für Links
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
function accept_link(string $token, string $scheme, string $host, string $base): string {
  return $scheme . '://' . $host . $base . '/accept_invite.php?token=' . urlencode($token);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Groups</title>
  <link rel="stylesheet" href="/Journeys-media/public/css/app.css">
</head>
<body>

<nav>
  <div class="nav-inner">
    <a href="/Journeys-media/public/index.php">Home</a>
    <a href="/Journeys-media/public/groups.php?view=list">Groups</a>
    <a href="/Journeys-media/public/groups.php?view=myinvites">My invites</a>
    <a href="/Journeys-media/public/logout.php">Logout</a>
  </div>
</nav>

<div class="container page">
  <h1>Groups</h1>

  <?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>
  <?php if ($flash): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <?php if ($view === 'list'): ?>
    <div class="col">
      <div class="card" style="flex:2">
        <h2>Your families</h2>
        <?php if (!$families): ?>
          <p class="muted">You are not in any family yet.</p>
        <?php else: ?>
          <ul>
            <?php foreach ($families as $f): ?>
              <li>
                <strong><?= htmlspecialchars($f['family_name']) ?></strong>
                (<?= htmlspecialchars($f['family_role']) ?>)
                — <a class="btn btn-secondary" href="/Journeys-media/public/groups.php?view=family&family_id=<?= (int)$f['id'] ?>">Manage</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <div class="spacer"></div>
        <h2>Create new family</h2>
        <form method="post">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="action" value="create_family">

          <label>Family name</label>
          <input name="family_name" required>

          <label></label>
          <input name="relation_label" placeholder="Your Name">

          <div class="spacer"></div>
          <button type="submit" class="btn">Create</button>
        </form>
      </div>

      <!-- Sidebar: My invites direkt in Groups -->
      <div class="card" style="max-width:420px">
        <h2>My invites</h2>
        <?php if (!$myInvites): ?>
          <p class="muted">No personal invites found for <?= htmlspecialchars($_SESSION['email']) ?>.</p>
          <p class="muted">Offene Einladungen ohne E-Mail erscheinen hier nicht; die nutzt man per Link.</p>
        <?php else: ?>
          <ul>
            <?php foreach ($myInvites as $i): ?>
              <li>
                <strong><?= htmlspecialchars($i['family_name']) ?></strong>
                <?php if (!empty($i['expires_at'])): ?>
                  — expires: <?= htmlspecialchars($i['expires_at']) ?>
                <?php endif; ?>
                <br>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(accept_link($i['token'], $scheme, $host, $base)) ?>">Accept invite</a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <p style="margin-top:8px"><a href="/Journeys-media/public/groups.php?view=myinvites">Open full view</a></p>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($view === 'myinvites'): ?>
    <div class="card">
      <h2>My invites (for <?= htmlspecialchars($_SESSION['email']) ?>)</h2>
      <?php if (!$myInvites): ?>
        <p class="muted">No personal invites found.</p>
      <?php else: ?>
        <ul>
          <?php foreach ($myInvites as $i): ?>
            <li>
              <strong><?= htmlspecialchars($i['family_name']) ?></strong>
              <?php if (!empty($i['expires_at'])): ?>
                — expires: <?= htmlspecialchars($i['expires_at']) ?>
              <?php endif; ?>
              <br>
              <a class="btn btn-secondary" href="<?= htmlspecialchars(accept_link($i['token'], $scheme, $host, $base)) ?>">Accept invite</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($view === 'family' && $family): ?>
    <div class="row">
      <div class="card" style="flex:2">
        <h2>Family: <?= htmlspecialchars($family['family_name']) ?></h2>
        <h3>Members</h3>
        <ul>
          <?php foreach ($members as $m): ?>
            <li>
              <?= htmlspecialchars($m['display_name'] ?: $m['email']) ?>
              (<?= htmlspecialchars($m['family_role']) ?><?= $m['relation_label'] ? ', '.htmlspecialchars($m['relation_label']) : '' ?>)
              — <span class="muted"><?= htmlspecialchars($m['email']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="card">
        <?php if (is_family_admin($pdo, $_SESSION['user_id'], (int)$family['id'])): ?>
          <h3>Create invite</h3>
          <form method="post">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="create_invite">
            <input type="hidden" name="family_id" value="<?= (int)$family['id'] ?>">

            <div class="form-row">
              <div>
                <label>Invitee email (optional)</label>
                <input type="email" name="email" placeholder="someone@example.com">
              </div>
              <div>
                <label>Expires in days</label>
                <input type="number" name="expires_days" value="7" min="1" max="30">
              </div>
            </div>

            <div class="spacer"></div>
            <button type="submit" class="btn">Create invite</button>
          </form>

          <h4 class="section-title">Open invites</h4>
          <?php if (!$openInvites): ?>
            <p class="muted">No open invites.</p>
          <?php else: ?>
            <ul>
              <?php foreach ($openInvites as $i): ?>
                <li>
                  <?= $i['email'] ? htmlspecialchars($i['email']) : '<em>open link</em>' ?>
                  <?php if (!empty($i['expires_at'])): ?>
                    — expires: <?= htmlspecialchars($i['expires_at']) ?>
                  <?php endif; ?>
                  <br>
                  <code><?= htmlspecialchars(accept_link($i['token'], $scheme, $host, $base)) ?></code>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        <?php else: ?>
          <p class="muted">You are a member. Only admins/owner can create invites.</p>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
