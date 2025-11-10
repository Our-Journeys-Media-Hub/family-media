<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/csrf.php';

$pdo = db_connect();

function require_login(): void {
  if (empty($_SESSION['user_id'])) {
    header('Location: /Journeys-media/public/login.php');
    exit;
  }
}

function current_user(): ?array {
  if (empty($_SESSION['user_id'])) return null;
  return [
    'id'           => (int)$_SESSION['user_id'],
    'email'        => $_SESSION['email'] ?? '',
    'display_name' => $_SESSION['display_name'] ?? '',
  ];
}

function is_family_member(PDO $pdo, int $userId, int $familyId): bool {
  $st = $pdo->prepare('SELECT 1 FROM family_memberships WHERE user_id=? AND family_id=? LIMIT 1');
  $st->execute([$userId, $familyId]);
  return (bool)$st->fetchColumn();
}

function is_family_admin(PDO $pdo, int $userId, int $familyId): bool {
  $st = $pdo->prepare('SELECT family_role FROM family_memberships WHERE user_id=? AND family_id=? LIMIT 1');
  $st->execute([$userId, $familyId]);
  $role = $st->fetchColumn();
  return in_array($role, ['owner','admin'], true);
}


function can_view_image(PDO $pdo, int $userId, int $imageId): bool {
  $sql = "
    SELECT i.family_id, i.uploaded_by, i.visibility, p.user_id AS allowed_user
    FROM images i
    LEFT JOIN image_permissions p ON i.id = p.image_id AND p.user_id = ?
    WHERE i.id = ?
    LIMIT 1
  ";
  $st = $pdo->prepare($sql);
  $st->execute([$userId, $imageId]);
  $img = $st->fetch(PDO::FETCH_ASSOC);

  if (!$img) return false;

  // visibilité = famille
  if ($img['visibility'] === 'family') {
    return is_family_member($pdo, $userId, (int)$img['family_id']);
  }

  // visibilité = privée
  if ($img['visibility'] === 'private') {
    return (int)$img['uploaded_by'] === $userId;
  }

  // visibilité = personnalisée
  if ($img['visibility'] === 'custom') {
    return !empty($img['allowed_user']);
  }

  return false;
}