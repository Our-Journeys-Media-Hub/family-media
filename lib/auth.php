<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/csrf.php';

$pdo = db_connect();

function require_login(): void {
  if (empty($_SESSION['user_id'])) {
    header('Location: /family-media/public/login.php');
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
