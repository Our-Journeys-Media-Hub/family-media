<?php
function db_connect(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $dsn  = 'mysql:host=127.0.0.1;dbname=demo_auth;charset=utf8mb4';
  $user = 'root';
  $pass = '';

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
