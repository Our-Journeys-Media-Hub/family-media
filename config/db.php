<?php
function db_connect(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  // probiere zuerst localhost …
$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=demo_auth;charset=utf8mb4';  $user = 'root';
  $pass = 'Esemmutlu';

  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
